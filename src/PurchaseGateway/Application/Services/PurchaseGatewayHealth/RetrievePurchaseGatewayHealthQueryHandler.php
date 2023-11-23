<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth;

use ProBillerNG\PurchaseGateway\Application\DTO\PurchaseGatewayHealth\PurchaseGatewayHealthDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\PurchaseGatewayHealth\PurchaseGatewayHealthHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidQueryException;
use ProBillerNG\PurchaseGateway\Domain\Projector\BundleAddonsProjector;
use ProBillerNG\PurchaseGateway\Domain\Projector\BusinessGroupSitesProjector;
use ProBillerNG\PurchaseGateway\Domain\Repository\PostbackJobsRepositoryInterface;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly as SiteRepository;
use ProBillerNG\PurchaseGateway\Domain\Services\ServiceStatusVerifier;

class RetrievePurchaseGatewayHealthQueryHandler
{
    const HEALTH_OK   = 'OK';
    const HEALTH_DOWN = 'DOWN';

    /**
     * @var SiteRepository
     */
    private $siteRepository;

    /**
     * @var PurchaseGatewayHealthDTOAssembler
     */
    private $assembler;

    /**
     * @var PostbackJobsRepositoryInterface
     */
    private $postbackJobsRepository;

    /** @var ServiceStatusVerifier */
    private $serviceStatusVerifier;

    /** @var BundleServiceStatus */
    private $bundleServiceStatus;

    /**
     * @var SiteServiceStatus
     */
    private $siteServiceStatus;

    /**
     * RetrieveCrossSaleHealthQueryHandler constructor.
     * @param SiteRepository                    $siteRepository         Site Configuration Repository.
     * @param PurchaseGatewayHealthDTOAssembler $assembler              PurchaseGatewayHealth DTO Assembler.
     * @param PostbackJobsRepositoryInterface   $postbackJobsRepository Postback jobs repository
     * @param ServiceStatusVerifier             $serviceStatusVerifier  Circuit Breaker Service
     * @param BundleServiceStatus               $bundleServiceStatus    Bundle Service Status
     * @param SiteServiceStatus                 $siteServiceStatus      Site Service Status
     */
    public function __construct(
        SiteRepository $siteRepository,
        PurchaseGatewayHealthDTOAssembler $assembler,
        PostbackJobsRepositoryInterface $postbackJobsRepository,
        ServiceStatusVerifier $serviceStatusVerifier,
        BundleServiceStatus $bundleServiceStatus,
        SiteServiceStatus $siteServiceStatus
    ) {
        $this->siteRepository         = $siteRepository;
        $this->assembler              = $assembler;
        $this->postbackJobsRepository = $postbackJobsRepository;
        $this->serviceStatusVerifier  = $serviceStatusVerifier;
        $this->bundleServiceStatus    = $bundleServiceStatus;
        $this->siteServiceStatus      = $siteServiceStatus;
    }

    /**
     * @param RetrievePurchaseGatewayHealthQuery $query Query
     * @return mixed
     * @throws \Exception
     */
    public function execute(RetrievePurchaseGatewayHealthQuery $query)
    {
        if (!$query instanceof RetrievePurchaseGatewayHealthQuery) {
            throw new InvalidQueryException(RetrievePurchaseGatewayHealthQuery::class, $query);
        }

        $status = self::HEALTH_OK;
        $count  = 0;

        try {
            $count = $this->siteRepository->countAll();
            if ($count == 0) {
                $status = self::HEALTH_DOWN;
            }
        } catch (\Throwable $e) {
            $status = self::HEALTH_DOWN;
        }

        $fraudCircuitBreakerStatusRetrieve        = $this->getStatus($this->serviceStatusVerifier->retrieveFraudRecommendation());
        $fraudCircuitBreakerStatus                = $this->getStatus($this->serviceStatusVerifier->fraudServiceStatus());
        $cascadeCircuitBreakerStatus              = $this->getStatus($this->serviceStatusVerifier->cascadeServiceStatus());
        $billerMappingCircuitBreakerStatus        = $this->getStatus($this->serviceStatusVerifier->billerMappingServiceStatus());
        $emailCircuitBreakerStatus                = $this->getStatus($this->serviceStatusVerifier->emailServiceStatus());
        $transactionCircuitBreakerStatus          = $this->getStatus($this->serviceStatusVerifier->transactionServiceStatus());
        $paymentTemplateCircuitBreakerStatus      = $this->getStatus($this->serviceStatusVerifier->paymentTemplateServiceStatus());
        $fraudCsCircuitBreakerStatus              = $this->getStatus($this->serviceStatusVerifier->transactionServiceStatus());
        $memberProfileGatewayCircuitBreakerStatus = $this->getStatus($this->serviceStatusVerifier->memberProfileGatewayStatus());

        $bundleServiceStatus = $this->getStatus(
            !$this->bundleServiceStatus->ledgerStatus(BundleAddonsProjector::WORKER_NAME)
        );

        $siteServiceStatus = $this->getStatus(
            !$this->siteServiceStatus->ledgerStatus(BusinessGroupSitesProjector::WORKER_NAME)
        );

        $health = [
            PurchaseGatewayHealthHttpDTO::STATUS                                 => $status,
            PurchaseGatewayHealthHttpDTO::NUMBER_OF_CONFIGURATIONS               => $count,
            PurchaseGatewayHealthHttpDTO::FRAUD_ADVICE_SERVICE_COMMUNICATION     => $fraudCircuitBreakerStatus,
            PurchaseGatewayHealthHttpDTO::CASCADE_SERVICE_COMMUNICATION          => $cascadeCircuitBreakerStatus,
            PurchaseGatewayHealthHttpDTO::BILLER_MAPPING_SERVICE_COMMUNICATION   => $billerMappingCircuitBreakerStatus,
            PurchaseGatewayHealthHttpDTO::EMAIL_SERVICE_COMMUNICATION            => $emailCircuitBreakerStatus,
            PurchaseGatewayHealthHttpDTO::TRANSACTION_SERVICE_COMMUNICATION      => $transactionCircuitBreakerStatus,
            PurchaseGatewayHealthHttpDTO::PAYMENT_TEMPLATE_SERVICE_COMMUNICATION => $paymentTemplateCircuitBreakerStatus,
            PurchaseGatewayHealthHttpDTO::FRAUD_ADVICE_CS_SERVICE_COMMUNICATION  => $fraudCsCircuitBreakerStatus,
            PurchaseGatewayHealthHttpDTO::MEMBER_PROFILE_GATEWAY_COMMUNICATION   => $memberProfileGatewayCircuitBreakerStatus,
            PurchaseGatewayHealthHttpDTO::BUNDLE_PROJECTION_STATUS               => $bundleServiceStatus,
            PurchaseGatewayHealthHttpDTO::SITE_PROJECTION_STATUS                 => $siteServiceStatus,
            PurchaseGatewayHealthHttpDTO::FRAUD_ADVICE_RETRIEVE                  => $fraudCircuitBreakerStatusRetrieve,
        ];

        if ($query->retrievePostbackStatus()) {
            $health[PurchaseGatewayHealthHttpDTO::POSTBACK_QUEUE_LENGTH] = $this->postbackJobsRepository->getQueueLengthOfPostbackJobs();
            $health[PurchaseGatewayHealthHttpDTO::POSTBACK_FAILED_JOBS]  = $this->postbackJobsRepository->getNumberOfFailedPostbackJobs();
        }

        return $this->assembler->assemble($health);
    }

    /**
     * @param bool $service Service Status
     * @return string
     */
    private function getStatus(bool $service): string
    {
        return $service ? 'failed' : 'successful';
    }
}

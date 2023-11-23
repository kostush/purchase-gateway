<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping;

use ProbillerNG\BillerMappingServiceClient\Model\ErrorResponse;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingAdapter as BillerMappingAdapterInterface;

class BillerMappingAdapter implements BillerMappingAdapterInterface
{
    /**
     * @var BillerMappingClient
     */
    private $billerMappingRetrievalForPurchaseProcessClient;

    /**
     * @var BillerMappingTranslator
     */
    private $billerMappingRetrievalForPurchaseProcessTranslator;

    /**
     * BillerMappingAdapter constructor.
     *
     * @param BillerMappingClient     $billerMappingRetrievalForPurchaseProcessClient     Client
     * @param BillerMappingTranslator $billerMappingRetrievalForPurchaseProcessTranslator Translator
     */
    public function __construct(
        BillerMappingClient $billerMappingRetrievalForPurchaseProcessClient,
        BillerMappingTranslator $billerMappingRetrievalForPurchaseProcessTranslator
    ) {
        $this->billerMappingRetrievalForPurchaseProcessClient     = $billerMappingRetrievalForPurchaseProcessClient;
        $this->billerMappingRetrievalForPurchaseProcessTranslator = $billerMappingRetrievalForPurchaseProcessTranslator;
    }

    /**
     * @param string $billerName      Biller Name.
     * @param string $businessGroupId Business group id
     * @param string $siteId          Site UUID
     * @param string $currencyCode    Currency Code
     * @param string $sessionId       Session UUID
     *
     * @return BillerMapping
     * @throws Exceptions\BillerMappingApiException
     * @throws Exceptions\BillerMappingErrorException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException
     */
    public function retrieveBillerMapping(
        string $billerName,
        string $businessGroupId,
        string $siteId,
        string $currencyCode,
        string $sessionId
    ): BillerMapping {
        $result = $this->billerMappingRetrievalForPurchaseProcessClient->retrieve(
            $billerName,
            $businessGroupId,
            $siteId,
            $currencyCode,
            $sessionId
        );

        if ($result instanceof ErrorResponse) {
            throw new Exceptions\BillerMappingErrorException(null, $result->getError(), $result->getCode());
        }

        return $this->billerMappingRetrievalForPurchaseProcessTranslator->translate($result);
    }
}

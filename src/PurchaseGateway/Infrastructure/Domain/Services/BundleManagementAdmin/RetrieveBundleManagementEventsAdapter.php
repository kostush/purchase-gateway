<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin;

use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveBundleManagementEventsAdapter as RetrieveBundleEventsAdapter;

class RetrieveBundleManagementEventsAdapter implements RetrieveBundleEventsAdapter
{
    /** @var  BundleManagementAdminClient */
    private $client;

    /** @var BundleManagementAdminTranslator */
    private $translator;

    /**
     * EmailServiceAdapter constructor.
     * @param BundleManagementAdminClient     $client     BundleManagementAdminClient
     * @param BundleManagementAdminTranslator $translator BundleManagementAdminTranslator
     */
    public function __construct(
        BundleManagementAdminClient $client,
        BundleManagementAdminTranslator $translator
    ) {
        $this->client     = $client;
        $this->translator = $translator;
    }

    /**
     * @param int|null $lastProjectedItemId Last Projected Item Id
     * @param int      $batchSize           Batch Size
     * @return array
     * @throws Exceptions\BundleManagementAdminApiException
     * @throws Exceptions\BundleManagementAdminCodeErrorException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveEvents(?int $lastProjectedItemId, int $batchSize): array
    {
        $result = $this->client->retrieveDomainEvents(
            $lastProjectedItemId,
            $batchSize
        );

        return $this->translator->translate($result);
    }
}

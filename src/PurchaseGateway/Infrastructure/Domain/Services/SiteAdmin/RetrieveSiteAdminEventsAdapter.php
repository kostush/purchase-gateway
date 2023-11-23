<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin;

use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveSiteAdminEventsAdapter as RetrieveAdminEventsAdapter;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\Exceptions\SiteAdminCodeErrorException;

class RetrieveSiteAdminEventsAdapter implements RetrieveAdminEventsAdapter
{
    /** @var SiteAdminClient */
    private $client;

    /** @var SiteAdminTranslator */
    private $translator;

    /**
     * RetrieveSiteAdminEventsAdapter constructor.
     * @param SiteAdminClient     $client     SiteAdminClient
     * @param SiteAdminTranslator $translator SiteAdminTranslator
     */
    public function __construct(
        SiteAdminClient $client,
        SiteAdminTranslator $translator
    ) {
        $this->client     = $client;
        $this->translator = $translator;
    }

    /**
     * @param int|null $lastProjectedItemId Last Projected Item Id
     * @param int      $batchSize           Batch Size
     * @return array
     * @throws Exception
     * @throws Exceptions\SiteAdminApiException
     * @throws SiteAdminCodeErrorException
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

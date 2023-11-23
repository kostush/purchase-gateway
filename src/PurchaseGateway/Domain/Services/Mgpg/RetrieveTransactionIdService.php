<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;

class RetrieveTransactionIdService
{
    /**
     * @var ItemRepositoryReadOnly
     */
    private $itemRepository;

    /**
     * RetrieveTransactionIdService constructor.
     * @param ItemRepositoryReadOnly $itemRepository
     */
    public function __construct(ItemRepositoryReadOnly $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    /**
     * @param string $itemId
     *
     * @return string
     * @throws Exception
     */
    public function findByItemIdOrReturnItemId(string $itemId): string
    {
        Log::info('RetrievingTransactionId retrieving transaction by itemId', ['itemId' => $itemId]);

        $item = $this->itemRepository->findById($itemId);

        if ($item instanceof ProcessedBundleItem && $transactionId = $item->retrieveTransactionId()) {
            Log::info(
                'RetrievingTransactionId TransactionId found on item: Previous purchase was made on Purchase Gateway',
                ['transactionId' => $transactionId , 'itemId' => $itemId]
            );
            return (string) $transactionId;
        }

        Log::info(
            'RetrievingTransactionId ItemId returned in place of transactionId',
            ['itemId' => $itemId, 'transactionId' => $itemId]
        );
        //Reason: MGPG does not have itemId.
        //        We are using transactionId in place of itemId on adaptor.
        return $itemId;
    }
}

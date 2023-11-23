<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\CCForBlackList;

use Probiller\Service\Config\CreditCardBlacklistRequest;
use Probiller\Service\Config\CreditCardBlacklistStatus;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\ErrorClassification;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use const Grpc\STATUS_OK;

class CCForBlackListTranslatingService implements CCForBlackListService
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * BillerMappingTranslatingService constructor.
     *
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @param string           $firstSix        First Six
     * @param string           $lastFour        Last Four
     * @param string           $expirationMonth Expiration Month
     * @param string           $expirationYear  Expiration Year
     * @param string           $sessionId       Session id
     * @param Transaction|null $transaction     Transaction
     * @return bool
     * @throws LoggerException
     */
    public function addCCForBlackList(
        string $firstSix,
        string $lastFour,
        string $expirationMonth,
        string $expirationYear,
        string $sessionId,
        ?Transaction $transaction
    ): bool {
        if (!$transaction instanceof Transaction
            || !$transaction->errorClassification() instanceof ErrorClassification
            || $transaction->errorClassification()->toArray()['errorType'] !== ErrorClassification::ERROR_TYPE_HARD
        ) {
            return false;
        }

        Log::info(
            'AddCreditCardBlacklist REQUEST to config service',
            [
                'requestType'    => 'GRPC',
                'method'         => 'AddCreditCardBlacklist',
                'host'           => env('CONFIG_SERVICE_HOST'),
                'requestPayload' => [
                    'firstSix'        => $firstSix,
                    'lastFour'        => $lastFour,
                    'expirationMonth' => $expirationMonth,
                    'expirationYear'  => $expirationYear,
                ],
                'sessionId'      => $sessionId
            ]
        );

        $CCForBlacklistRequest = $this->createCCForBlacklistRequest(
            $firstSix,
            $lastFour,
            $expirationMonth,
            $expirationYear
        );

        [$CCForBlacklistResponse, $responseStatus] = $this->configService->getClient()
            ->AddCreditCardBlacklist(
                $CCForBlacklistRequest,
                $this->configService->getMetadata()
            )->wait();

        if ($responseStatus->code == STATUS_OK) {
            Log::info('Credit card was blacklisted');
            return true;
        }

        Log::info(
            'AddCreditCardBlacklist RESPONSE fail from config service',
            [
                'requestType'  => 'GRPC',
                'method'       => 'CheckCreditCardBlacklist',
                'host'         => env('CONFIG_SERVICE_HOST'),
                'responseCode' => $responseStatus->code,
                'details'      => $responseStatus->details,
                'sessionId'    => $sessionId
            ]
        );

        return false;
    }

    /**
     * @param string $firstSix        First Six
     * @param string $lastFour        Last Four
     * @param string $expirationMonth Expiration Month
     * @param string $expirationYear  Expiration Year
     * @param string $sessionId       Session id
     * @return bool
     * @throws LoggerException
     */
    public function checkCCForBlacklist(
        string $firstSix,
        string $lastFour,
        string $expirationMonth,
        string $expirationYear,
        string $sessionId
    ): bool {
        Log::info(
            'CheckCreditCardBlacklist REQUEST to config service',
            [
                'requestType'    => 'GRPC',
                'method'         => 'CheckCreditCardBlacklist',
                'host'           => env('CONFIG_SERVICE_HOST'),
                'requestPayload' => [
                    'firstSix'        => $firstSix,
                    'lastFour'        => $lastFour,
                    'expirationMonth' => $expirationMonth,
                    'expirationYear'  => $expirationYear,
                ],
                'sessionId'      => $sessionId
            ]
        );

        $CCForBlacklistRequest = $this->createCCForBlacklistRequest(
            $firstSix,
            $lastFour,
            $expirationMonth,
            $expirationYear
        );

        /**
         * @var CreditCardBlacklistStatus $CCForBlacklistResponse
         */
        [$CCForBlacklistResponse, $responseStatus] = $this->configService->getClient()
            ->CheckCreditCardBlacklist(
                $CCForBlacklistRequest,
                $this->configService->getMetadata()
            )->wait();

        if ($responseStatus->code != STATUS_OK) {
            Log::info(
                'CheckCreditCardBlacklist RESPONSE fail from config service',
                [
                    'requestType'  => 'GRPC',
                    'method'       => 'CheckCreditCardBlacklist',
                    'host'         => env('CONFIG_SERVICE_HOST'),
                    'responseCode' => $responseStatus->code,
                    'details'      => $responseStatus->details,
                    'sessionId'    => $sessionId
                ]
            );

            return false;
        }

        Log::info(
            'CheckCreditCardBlacklist RESPONSE success from config service',
            [
                'requestType'     => 'GRPC',
                'method'          => 'CheckCreditCardBlacklist',
                'host'            => env('CONFIG_SERVICE_HOST'),
                'responsePayload' => $CCForBlacklistResponse->serializeToJsonString(),
                'status'          => 'OK',
                'sessionId'       => $sessionId
            ]
        );

        return $CCForBlacklistResponse->getIsBlackListed();
    }

    /**
     * @param string $firstSix        First six
     * @param string $lastFour        Last four
     * @param string $expirationMonth Expiration month
     * @param string $expirationYear  Expiration year
     * @return CreditCardBlacklistRequest
     */
    private function createCCForBlacklistRequest(
        string $firstSix,
        string $lastFour,
        string $expirationMonth,
        string $expirationYear
    ): CreditCardBlacklistRequest {
        $creditCardBlacklistRequest = new CreditCardBlacklistRequest();

        $creditCardBlacklistRequest->setFirstSix($firstSix);
        $creditCardBlacklistRequest->setLastFour($lastFour);
        $creditCardBlacklistRequest->setExpirationMonth($expirationMonth);
        $creditCardBlacklistRequest->setExpirationYear($expirationYear);

        return $creditCardBlacklistRequest;
    }
}
<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\EmailSettings;

use Probiller\Common\EmailSettings;
use Probiller\Common\Enums\BillerType\BillerType;
use Probiller\Common\Enums\MemberType\MemberType;
use Probiller\Common\Enums\TaxType\TaxType;
use Probiller\Service\Config\GetEmailSettingsRequest;
use Probiller\Service\Config\GetEmailSettingsRequest\EmailSettingsOwnerFilter;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\SendEmails\SendEmailsCommandHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use UnexpectedValueException;
use const Grpc\STATUS_OK;
use const Grpc\STATUS_NOT_FOUND;

class EmailSettingsService
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * EmailSettingsService constructor.
     *
     * @param ConfigService $configService ConfigService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @param string $siteId        Site id
     * @param string $taxType       Tax type
     * @param string $billerName    Biller name
     * @param string $memberType    Member type
     * @param int    $templateType  Template type
     * @param string $sessionId     Session id
     * @param string $transactionId Transaction id
     * @param bool   $subscriptionPurchaseIncludesNonRecurring
     *
     * @return EmailSettings|null
     * @throws EmailSettingsException
     * @throws Exception
     */
    public function retrieveEmailSettings(
        string $siteId,
        string $taxType,
        string $billerName,
        string $memberType,
        int $templateType,
        string $sessionId,
        string $transactionId,
        bool $subscriptionPurchaseIncludesNonRecurring = false
    ): ?EmailSettings {

        $ownerFilterParams = $this->buildOwnerFilterParams(
            $siteId,
            $taxType,
            $memberType,
            $billerName,
            $templateType,
            $subscriptionPurchaseIncludesNonRecurring
        );

        Log::info(
            'RetrieveEmailSettings REQUEST starting retrieve EmailSettings from config service',
            [
                'requestType'    => 'GRPC',
                'method'         => 'getEmailSettings',
                'host'           => env('CONFIG_SERVICE_HOST'),
                'requestPayload' => [
                    'owner' => $ownerFilterParams
                ],
                'sessionId'      => $sessionId,
                'transactionId'  => $transactionId
            ]
        );

        /**
         * @var EmailSettings $emailSettings
         */
        [
            $emailSettings,
            $responseStatus
        ] = $this->getEmailSettings($ownerFilterParams);

        if ($responseStatus->code == STATUS_OK) {
            Log::info(
                'RetrieveEmailSettings RESPONSE EmailSettings retrieved with success from config service',
                [
                    'requestType'     => 'GRPC',
                    'method'          => 'getEmailSettings',
                    'host'            => env('CONFIG_SERVICE_HOST'),
                    'responsePayload' => $emailSettings->serializeToJsonString(),
                    'sessionId'       => $sessionId,
                    'transactionId'   => $transactionId,
                    'status'          => 'OK'
                ]
            );

            if ($emailSettings->getDisabled()) {
                return null;
            }

            return $emailSettings;
        }

        if ($responseStatus->code == STATUS_NOT_FOUND) {
            Log::warning(
                'RetrieveEmailSettings RESPONSE EmailSettings not found from config service',
                [
                    'responseType'   => 'GRPC',
                    'method'         => 'getEmailSettings',
                    'host'           => env('CONFIG_SERVICE_HOST'),
                    'sessionId'      => $sessionId,
                    'transactionId'  => $transactionId,
                    'requestPayload' => [
                        'owner' => $ownerFilterParams
                    ],
                    'status'         => 'NOT_FOUND'
                ]
            );

            return null;
        }

        if ($responseStatus->code != STATUS_OK) {
            Log::warning(
                'RetrieveEmailSettings RESPONSE fail to retrieve EmailSettings from config service',
                [
                    'responseType'   => 'GRPC',
                    'method'         => 'getEmailSettings',
                    'sessionId'      => $sessionId,
                    'transactionId'  => $transactionId,
                    'requestPayload' => [
                        'owner' => $ownerFilterParams
                    ],
                    'responseCode'   => $responseStatus->code,
                    'details'        => $responseStatus->details,
                    'status'         => 'ERROR'
                ]
            );
            throw new EmailSettingsException($responseStatus->details, $responseStatus->code);
        }
    }

    /**
     * @param array $ownerFilterParams
     *
     * @return array
     * @throws Exception
     */
    private function getEmailSettings(array $ownerFilterParams): array
    {

        $request = new GetEmailSettingsRequest(
            [
                'owner' => (new EmailSettingsOwnerFilter(
                    $ownerFilterParams
                ))
            ]
        );

        return $this->configService->getClient()
            ->GetEmailSettingsConfig($request, $this->configService->getMetadata())
            ->wait();
    }

    /**
     * @param string $siteId
     * @param string $taxType
     * @param string $memberType
     * @param string $billerName
     * @param int    $templateType
     * @param bool   $subscriptionPurchaseIncludesNonRecurring
     *
     * @return array
     */
    private function buildOwnerFilterParams(
        string $siteId,
        string $taxType,
        string $memberType,
        string $billerName,
        int $templateType,
        bool $subscriptionPurchaseIncludesNonRecurring
    ): array {
        $filterParams = [
            'siteId'       => $siteId,
            'taxType'      => $this->getValueEnum(TaxType::class, $taxType, TaxType::unknown),
            'memberType'   => $this->translateMemberType($memberType),
            'biller'       => $this->getValueEnum(BillerType::class, $billerName),
            'templateType' => $templateType,
        ];

        if($subscriptionPurchaseIncludesNonRecurring){
            $filterParams['isNonRecurringSubscriptionPurchase'] = $subscriptionPurchaseIncludesNonRecurring;
        }

        return $filterParams;
    }

    /**
     * @param string $PGmemberType
     *
     * @return mixed
     */
    private function translateMemberType(string $PGmemberType)
    {
        $typesMap = [
            SendEmailsCommandHandler::MEMBER_TYPE_EXISTING => MemberType::existing,
            SendEmailsCommandHandler::MEMBER_TYPE_NEW      => MemberType::PBnew
        ];
        if (!array_key_exists($PGmemberType, $typesMap)) {
            throw new UnexpectedValueException(
                sprintf(
                    'MemberType %s is invalid',
                    $PGmemberType
                )
            );
        }

        return $typesMap[$PGmemberType];
    }

    /**
     * @param string $class
     * @param string $name
     * @param null   $default
     *
     * @return mixed
     */
    private function getValueEnum(string $class, string $name, $default = null)
    {
        $const = $class . '::' . $name;

        if (defined($const)) {
            return constant($const);
        }

        if ($default) {
            return $default;
        }

        throw new UnexpectedValueException(
            sprintf(
                'Enum %s has no value defined for name %s',
                $class,
                $name
            )
        );
    }
}
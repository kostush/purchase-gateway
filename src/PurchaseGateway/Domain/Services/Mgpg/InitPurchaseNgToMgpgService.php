<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use Exception;
use ProbillerMGPG\Common\PaymentMethod;
use ProbillerMGPG\Common\PaymentType;
use ProbillerMGPG\Dws\Dws;
use ProbillerMGPG\Exception\InvalidPaymentMethodException;
use ProbillerMGPG\Exception\InvalidPaymentTypeException;
use ProbillerMGPG\Purchase\Init\Invoice;
use ProbillerMGPG\Purchase\Init\Item;
use ProbillerMGPG\Purchase\Init\Item\OneChargeItem;
use ProbillerMGPG\Purchase\Init\Item\OneChargeItemWithTax;
use ProbillerMGPG\Purchase\Init\Item\Price\PriceInfo;
use ProbillerMGPG\Purchase\Init\Item\Price\PriceInfoWithTax;
use ProbillerMGPG\Purchase\Init\Item\Price\RebillInfo;
use ProbillerMGPG\Purchase\Init\Item\Price\RebillInfoWithTax;
use ProbillerMGPG\Purchase\Init\Item\Price\TaxInfo;
use ProbillerMGPG\Purchase\Init\Item\RecurringChargeItem;
use ProbillerMGPG\Purchase\Init\Item\RecurringChargeItemWithTax;
use ProbillerMGPG\Purchase\Init\Operation\BaseOperation;
use ProbillerMGPG\Purchase\Init\Operation\SingleChargePurchase;
use ProbillerMGPG\Purchase\Init\Operation\SubscriptionPurchase;
use ProbillerMGPG\Purchase\Init\PaymentInfo;
use ProbillerMGPG\Purchase\Init\PurchaseInitRequest;
use ProbillerMGPG\Request as MgpgRequest;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ValidationException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SiteNotExistException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\NgResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\InitPurchaseRequest;
use Ramsey\Uuid\Uuid;

class InitPurchaseNgToMgpgService extends InitNgToMgpgService
{
    const TAX_PRODUCT_CLASSIFICATION_UNKNOWN = 'unknown';

    const DEFAULT_ASN = "29789";

    const DEFAULT_QUANTITY = 1;

    /**
     * @var InitPurchaseRequest
     */
    protected $initRequest;

    /**
     * @var NgResponseService
     */
    protected $ngResponseService;

    /**
     * @var string
     */
    protected $memberId;

    /**
     * @var string
     */
    protected $subscriptionId;

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var array Hash map of bundleId/addonId/siteId -> chargeId(used for process additional purchase items)
     */
    protected $crossSaleChargeIdMap;

    /**
     * InitPurchaseNgToMgpgService constructor.
     * @param InitPurchaseRequest $initRequest
     * @param NgResponseService   $ngResponseService Utility to inspect NG input response
     * @param TokenGenerator      $tokenGenerator
     * @param CryptService        $cryptService
     * @param ConfigService       $configService
     * @throws Exception
     */
    public function __construct(
        InitPurchaseRequest $initRequest,
        NgResponseService $ngResponseService,
        TokenGenerator $tokenGenerator,
        CryptService $cryptService,
        ConfigService $configService
    ) {
        $this->initRequest          = $initRequest;
        $this->configService        = $configService;
        $this->ngResponseService    = $ngResponseService;
        $this->crossSaleChargeIdMap = [];

        parent::__construct(
            $tokenGenerator,
            $cryptService
        );

        // In case none are provided by client, we generate them on init for them.
        $this->memberId       = (string) $initRequest->input('memberId', Uuid::uuid4()->toString());
        $this->subscriptionId = $initRequest->input('subscriptionId', Uuid::uuid4()->toString());
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return $this->subscriptionId;
    }

    /**
     * @return string
     */
    public function getMemberId(): string
    {
        return $this->memberId;
    }

    /**
     * @param string $correlationId
     * @return MgpgRequest
     * @throws InvalidPaymentMethodException
     * @throws InvalidPaymentTypeException
     */
    public function translate(string $correlationId): MgpgRequest
    {
        return new PurchaseInitRequest(
            $this->getBusinessGroupId(),
            $this->createPurchaseInvoice($correlationId),
            $this->createDws()
        );
    }

    /**
     * @return string
     */
    private function getBusinessGroupId(): string
    {
        return (string) $this->initRequest->attributes->get('site')->businessGroupId();
    }

    /**
     * @param string $correlationId
     * @return Invoice
     * @throws InvalidPaymentMethodException
     * @throws InvalidPaymentTypeException
     * @throws Exception
     */
    private function createPurchaseInvoice(string $correlationId): Invoice
    {
        $sessionId   = $this->initRequest->attributes->get('sessionId');
        $publicKeyId = (string) $this->initRequest->attributes->get('publicKeyId');

        return new Invoice(
            Uuid::uuid4()->toString(),
            $this->memberId,
            $this->initRequest->getUsingMemberProfile(),
            $this->initRequest->getClientIp(),
            $this->createReturnUrl(
                $this->initRequest->input('redirectUrl', ''),
                $publicKeyId,
                $sessionId,
                $correlationId
            ),
            $this->createPostbackUrl(
                $this->initRequest->input('postbackUrl', ''),
                $publicKeyId,
                $sessionId,
                $correlationId
            ),
            new PaymentInfo(
                $this->initRequest->getCurrency(),
                $this->initRequest->getPaymentType(),
                $this->getPaymentMethod()
            ),
            $this->createCharges()
        );
    }

    /**
     * Determines cascade result, if cc type is provided but no payment method is provided, we default to cc as well
     * on the method, otherwise we send whatever the client has set. If he has set nothing and the type is not cc, we
     * let mgpg throw an exception since we are a pass-through.
     * @return string|null Payment Method
     */
    public function getPaymentMethod(): ?string
    {
        if ($this->initRequest->getPaymentMethod() == null && $this->initRequest->getPaymentType() == PaymentType::CC) {
            return PaymentMethod::CC;
        }

        return $this->initRequest->getPaymentMethod() ?? '';
    }

    /**
     * Main purchase and cross-sales on MGPG are all added in one `charges` array.
     * @return array
     * @throws Exception
     */
    private function createCharges(): array
    {
        $charges                = [];
        $ngPayload              = $this->initRequest->toArray();
        $crossSellCharges       = $this->initRequest->getCrossSales();
        $entitlementsFromClient = $this->initRequest->getEntitlementsFromClient();
        
        $charges[] = $this->createPurchase($ngPayload, true, $entitlementsFromClient);

        foreach ($crossSellCharges as $charge) {
            $entitlementsCrossSale = $charge['entitlements'] ?? [];
            $charges[] = $this->createPurchase($charge, false, $entitlementsCrossSale);
        }

        return $charges;
    }

    /**
     * @param array $charge                 Main payload or cross-sale entry
     * @param bool  $isMainCharge
     * @param array $entitlementsFromClient Entitlements provided by the client to be used in the internal entitlements
     *
     * @return BaseOperation
     * @throws ValidationException
     * @throws Exception|SiteNotExistException
     */
    protected function createPurchase(array $charge, bool $isMainCharge = false, array $entitlementsFromClient = []): BaseOperation
    {
        $purchaseClass = SubscriptionPurchase::class;

        if ($this->ngResponseService->isSubscription($charge) == false) {
            if ($this->ngResponseService->hasRebillOnTaxes($charge)) {
                throw new ValidationException(
                    null,
                    "Cannot have tax.rebillAmount when initialDays is greater than 0"
                );
            }

            $purchaseClass = SingleChargePurchase::class;
        }

        // If main charge, use site provided to us by Key/Token authentification middleware.
        $site = $this->initRequest->attributes->get('site');

        if ($isMainCharge == false) {
            $site = $this->configService->getSite($charge['siteId']);
        }

        if ($site == null) {
            // Guaranteed to be on cross-sale since middleware also throws this exception for main charge.
            throw new SiteNotExistException(null, $charge['siteId']);
        }

        $chargeId = Uuid::uuid4()->toString();

        $this->addToCrossSaleMap($chargeId, $charge);

        return new $purchaseClass(
            $chargeId,
            $charge['siteId'],
            // Eventually we want to use config-service email-settings value instead.
            $site->name(),
            [$this->createSingleChargeItem($charge, $site->name(), $entitlementsFromClient)],
            $isMainCharge,
            $charge['isTrial'] ?? false
        );
    }

    /**
     * @param string $chargeId
     * @param array  $charge
     */
    protected function addToCrossSaleMap(string $chargeId, array $charge)
    {
        $compositeKey = $this->ngResponseService->selectedCrossSaleCompositeKey(
            $charge['siteId'],
            $charge['bundleId'],
            $charge['addonId']
        );

        $this->crossSaleChargeIdMap[$compositeKey] = $chargeId;
    }

    /**
     * @param array  $charge   NG charge
     * @param string $siteName
     * @param array  $entitlementsFromClient Entitlements provided by the client to be used in the internal entitlements
     *
     * @return OneChargeItem|OneChargeItemWithTax|RecurringChargeItem|RecurringChargeItemWithTax
     * @throws \ProBillerNG\Logger\Exception
     */
    private function createSingleChargeItem(array $charge, string $siteName, array $entitlementsFromClient = [])
    {
        $businessRevenueStream = Item::REVENUE_STREAM_INITIAL_SALE;
        if ($this->initRequest->input('memberId')) {
            $businessRevenueStream = Item::REVENUE_STREAM_SECONDARY_SALE;
        }

        if ($this->ngResponseService->hasTaxes($charge)) {
            if ($this->ngResponseService->hasRebillOnTaxes($charge)) {

                Log::info('RecurringChargeItemWithTax  creating', ['charge' => $charge]);

                return new RecurringChargeItemWithTax(
                    $businessRevenueStream,
                    $charge['bundleId'],
                    $siteName,
                    $siteName,
                    self::DEFAULT_QUANTITY,
                    $this->createPrice($charge),
                    $this->createRebill($charge),
                    $this->createTax($charge['tax']),
                    $this->createEntitlements($charge, $entitlementsFromClient),
                    $this->createLegacyMapping($charge)
                );
            }

            Log::info('OneChargeItemWithTax creating', ['charge' => $charge]);

            return new OneChargeItemWithTax(
                $businessRevenueStream,
                $charge['bundleId'],
                $siteName,
                $siteName,
                self::DEFAULT_QUANTITY,
                $this->createPrice($charge),
                $this->createTax($charge['tax']),
                $this->createEntitlements($charge, $entitlementsFromClient),
                $this->createLegacyMapping($charge),
                $this->createOtherData($charge)
            );
        }

        if ($this->ngResponseService->hasRebill($charge)) {

            Log::info('RecurringChargeItem creating', ['charge' => $charge]);

            return new RecurringChargeItem(
                $businessRevenueStream,
                $charge['bundleId'],
                $siteName,
                $siteName,
                self::DEFAULT_QUANTITY,
                $this->createPrice($charge),
                $this->createRebill($charge),
                $this->createEntitlements($charge, $entitlementsFromClient),
                $this->createLegacyMapping($charge)
            );
        }

        Log::info('OneChargeItem creating', ['charge' => $charge]);

        return new OneChargeItem(
            $businessRevenueStream,
            $charge['bundleId'],
            $siteName,
            $siteName,
            self::DEFAULT_QUANTITY,
            $this->createPrice($charge),
            $this->createEntitlements($charge, $entitlementsFromClient),
            $this->createLegacyMapping($charge),
            $this->createOtherData($charge)
        );
    }

    /**
     * @param array $charge NG charge
     * @return PriceInfo|PriceInfoWithTax
     */
    private function createPrice(array $charge)
    {
        if ($this->ngResponseService->hasTaxes($charge)) {
            return new PriceInfoWithTax(
                $charge['tax']['initialAmount']['beforeTaxes'],
                (int) $charge['initialDays'],
                $charge['tax']['initialAmount']['taxes'],
                $charge['tax']['initialAmount']['afterTaxes']
            );
        }

        return new PriceInfo(
            $charge['amount'],
            $charge['initialDays'],
            $charge['amount']
        );
    }

    /**
     * @param array $charge NG Charge
     * @return RebillInfo|RebillInfoWithTax
     */
    private function createRebill(array $charge)
    {
        if ($this->ngResponseService->hasRebillOnTaxes($charge)) {
            return new RebillInfoWithTax(
                $charge['tax']['rebillAmount']['beforeTaxes'],
                $charge['rebillDays'] ?? 0,
                $charge['tax']['rebillAmount']['taxes'],
                $charge['tax']['rebillAmount']['afterTaxes']
            );
        }

        return new RebillInfo(
            $charge['rebillAmount'],
            $charge['rebillDays'] ?? 0,
            $charge['rebillAmount'] ?? 0
        );
    }

    /**
     * @param array $tax NG tax
     * @return TaxInfo
     */
    private function createTax(array $tax): TaxInfo
    {
        return new TaxInfo(
            $tax['taxApplicationId'],
            self::TAX_PRODUCT_CLASSIFICATION_UNKNOWN,
            $tax['taxName'] ?? '',
            $tax['taxRate'] ?? 0.0,
            $tax['taxType'] ?? '',
            $this->ngResponseService->displayChargedAmount($tax)
        );
    }

    /**
     * @param array $charge
     * @return \array[][]
     */
    private function createOtherData(array $charge): ?array
    {
        $data = null;

        if (
            $this->initRequest->has('otherData')
            && $this->initRequest->has('otherData.paygarden')
        ) {
            $data['paygarden'] = $charge['otherData']['paygarden'];
        }
        return $data;
    }

    /**
     * Creates entitlements, which are pass-through and used to keep certain NG-specific id's in MGPG.
     *
     * @param array $charge                 Main or Cross-Sale charge
     * @param array $entitlementsFromClient Entitlements provided by the client to be used in the internal entitlements
     *
     * @return array
     */
    private function createEntitlements(array $charge, array $entitlementsFromClient = []): array
    {
        $entitlements = [
            'siteId'   => $charge['siteId'],
            'bundleId' => $charge['bundleId'],
            'addonId'  => $charge['addonId'],
        ];

        $entitlements['subscriptionId'] = $this->subscriptionId;
        $entitlements['memberId']       = $this->memberId;

        if (isset($charge['entrySiteId'])) {
            $entitlements['entrySiteId'] = $charge['entrySiteId'];
        }

        $entitlementArray = [
            'memberProfile' => [
                'data' => array_filter($entitlements)
            ],
        ];
        if(!empty($entitlementsFromClient)) {
            $entitlementArray['extended'] = $entitlementsFromClient;
        }
        
        return [
            $entitlementArray
        ];
    }

    /**
     * For the moment, we use entitlements to pass the addonId and retrieve it back on process response.
     * @param array $charge Main charge or cross sale charge
     * @return array
     */
    private function createLegacyMapping(array $charge): ?array
    {
        $mapping = null;

        $legacyMappingProperties = NgResponseService::getLegacyMappingProperties();

        foreach ($legacyMappingProperties as $property => $typesOfProperty) {
            if(isset($charge['legacyMapping'][$property])) {
                $value = $charge['legacyMapping'][$property];
                settype($value, $typesOfProperty);
                $mapping[$property] = $value;
            }
        }

        if (empty($mapping)) {
            return null;
        }

        return $mapping;
    }

    private function getLegacyMappingProperties()
    {
        // we don't see legacyMemberId on swagger https://mgpg-api-3.dev.pbk8s.com/swagger/index.html but it was on the code
        return [
            'legacyProductId'                 => 'int',
            'legacyMemberId'                  => 'int',
            'bypassUi'                        => 'boolean',
            'hideUsernameField'               => 'boolean',
            'hideUsernamePasswordFields'      => 'boolean',
            'hideUsernamePasswordEmailFields' => 'boolean',
            'hidePasswordFromEmail'           => 'boolean',
            'requireActiveParent'             => 'boolean',
            'parentSubscriptionId'            => 'string',
            'templateId'                      => 'string',
            'packageId'                       => 'string',
            'subSiteId'                       => 'string',
            'crossSellType'                   => 'string',
            'crossSellDefaultValue'           => 'boolean'
        ];
    }

    /**
     * Create DWS MGPG payload, the fields provided are all required.
     * @return Dws
     */
    private function createDws(): Dws
    {
        $data = [
            'atlasCode'          => $this->initRequest->getAtlasCode(),
            'atlasData'          => $this->initRequest->getAtlasData(),
            'fiftyOneDegree'     => [
                "x-51d-browsername"       => "Unknown Crawler",
                "x-51d-browserversion"    => "Unknown",
                "x-51d-platformname"      => "Unknown",
                "x-51d-platformversion"   => "Unknown",
                "x-51d-deviceType"        => "Desktop",
                "x-51d-ismobile"          => "False",
                "x-51d-hardwaremodel"     => "Unknown",
                "x-51d-hardwarefamily"    => "Emulator",
                "x-51d-javascript"        => "Unknown",
                "x-51d-javascriptversion" => "Unknown",
                "x-51d-viewport"          => "Unknown",
                "x-51d-html5"             => "Unknown",
                "x-51d-iscrawler"         => "True"
            ],
            'parsedAtlasDetails' => [
                'atlasTrafficSourceId' => '5',
            ],
            'maxMind'            => [
                'x-geo-country-code'        => $this->initRequest->getClientCountryCode(),
                'x-geo-region'              => '',
                'x-geo-city'                => '',
                'x-geo-postal-code'         => '',
                'x-geo-city-continent-code' => '',
                "x-geo-asn"                 => self::DEFAULT_ASN,
            ]
        ];

        if ($this->initRequest->has('redirectUrl') && $this->initRequest->has('postbackUrl')) {
            /*
             * Save-guarding the client's postback/redirect URL's here as a hack because MGPG doesn't alter
             * the dws data. We will retrieve these two when MGPG calls us after receiving postback & return.
             */
            $data["otherData"]["redirectUrl"] = $this->initRequest->getRedirectUrl();
            $data["otherData"]["postbackUrl"] = $this->initRequest->getPostbackUrl();
        }
        
        return new Dws(array_replace_recursive($data, $this->initRequest->getDws()));
    }

    /**
     * @return string json
     */
    public function getCrossSaleChargeIdMap(): string
    {
        return json_encode($this->crossSaleChargeIdMap);
    }

    /**
     * @return InitPurchaseRequest
     */
    public function getInitRequest(): InitPurchaseRequest
    {
        return $this->initRequest;
    }
}

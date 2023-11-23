<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use Exception;
use ProbillerMGPG\Dws\Dws;
use ProbillerMGPG\Exception\InvalidPaymentMethodException;
use ProbillerMGPG\Exception\InvalidPaymentTypeException;
use ProbillerMGPG\Request as MgpgRequest;
use ProbillerMGPG\SubsequentOperations\Init\InitRequest;
use ProbillerMGPG\SubsequentOperations\Init\Invoice as RebillUpdateInvoice;
use ProbillerMGPG\SubsequentOperations\Init\PaymentInfo;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\InitRebillUpdateRequest;
use Ramsey\Uuid\Uuid;

class InitRebillUpdateNgToMgpgService extends InitNgToMgpgService
{
    /**
     * @var InitRebillUpdateRequest
     */
    protected $initRequest;

    /**
     * @var RebillUpdateChargesService
     */
    private $rebillUpdateChargesService;

    /**
     * InitPurchaseNgToMgpgService constructor.
     *
     * @param InitRebillUpdateRequest    $initRequest
     * @param TokenGenerator             $tokenGenerator
     * @param RebillUpdateChargesService $rebillUpdateChargesService
     * @param CryptService               $cryptService
     */
    public function __construct(
        InitRebillUpdateRequest $initRequest,
        TokenGenerator $tokenGenerator,
        RebillUpdateChargesService $rebillUpdateChargesService,
        CryptService $cryptService
    ) {
        $this->initRequest                = $initRequest;
        $this->rebillUpdateChargesService = $rebillUpdateChargesService;

        parent::__construct(
            $tokenGenerator,
            $cryptService
        );
    }

    /**
     * @param string $correlationId
     *
     * @return MgpgRequest
     * @throws InvalidPaymentMethodException
     * @throws InvalidPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\RebillFieldRequiredException
     */
    public function translate(string $correlationId): MgpgRequest
    {
        return new InitRequest(
            $this->getBusinessGroupId(),
            $this->createRebillUpdateInvoice($correlationId),
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
     *
     * @return RebillUpdateInvoice
     * @throws InvalidPaymentMethodException
     * @throws InvalidPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\RebillFieldRequiredException
     */
    private function createRebillUpdateInvoice(string $correlationId): RebillUpdateInvoice
    {
        $sessionId   = $this->initRequest->attributes->get('sessionId');
        $publicKeyId = (string) $this->initRequest->attributes->get('publicKeyId');

        return new RebillUpdateInvoice(
            Uuid::uuid4()->toString(),
            $this->initRequest->getMemberId(),
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
                $this->initRequest->getPaymentMethod()
            ),
            $this->rebillUpdateChargesService->create($this->initRequest)
        );
    }

    /**
     * Create DWS MGPG payload, the fields provided are all required.
     * @return Dws
     */
    private function createDws(): Dws
    {
        $dwsFromRequest = $this->initRequest->getDws();

        $defaultDws = [
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
                "x-geo-asn"                 => "29789",
            ]
        ];

        return new Dws(array_replace_recursive($defaultDws, $dwsFromRequest));
    }

    /**
     * @return InitRebillUpdateRequest
     */
    public function getInitRequest(): InitRebillUpdateRequest
    {
        return $this->initRequest;
    }
}

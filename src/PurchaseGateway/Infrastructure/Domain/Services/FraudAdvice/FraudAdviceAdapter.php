<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use ProbillerNG\FraudServiceClient\ApiException;
use ProbillerNG\FraudServiceClient\Model\FraudAdvicePayload;
use ProbillerNG\FraudServiceClient\Model\FraudAdvicePayloadBlacklist;
use ProbillerNG\FraudServiceClient\Model\FraudAdvicePayloadCaptcha;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\Exceptions\FraudAdviceApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\Exceptions\FraudAdviceTranslationException;

class FraudAdviceAdapter implements FraudAdapter
{
    /**
     * @var FraudAdviceClient
     */
    private $client;

    /**
     * @var FraudAdviceTranslator
     */
    private $translator;

    /**
     * FraudAdviceForPurchaseInitAdapter constructor.
     * @param FraudAdviceClient     $client     Client
     * @param FraudAdviceTranslator $translator Translator
     */
    public function __construct(
        FraudAdviceClient $client,
        FraudAdviceTranslator $translator
    ) {
        $this->client     = $client;
        $this->translator = $translator;
    }

    /**
     * @param SiteId         $siteId    Site id
     * @param array          $params    Params
     * @param string         $for       For which step
     * @param SessionId|null $sessionId Session Id
     *
     * @return FraudAdvice
     *
     * @throws FraudAdviceApiException
     * @throws FraudAdviceTranslationException
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveAdvice(SiteId $siteId, array $params, string $for, SessionId $sessionId = null): FraudAdvice
    {
        Log::info(
            'Retrieving the fraud advice by providing the following parameters: ',
            array_merge(['siteId' => (string) $siteId], $params)
        );

        $captchaPayload   = new FraudAdvicePayloadCaptcha($params);
        $blacklistPayload = new FraudAdvicePayloadBlacklist($params);

        $payload = new FraudAdvicePayload();

        $payload->setCaptcha($captchaPayload);
        $payload->setBlacklist($blacklistPayload);

        if ($sessionId !== null) {
            $payload->setSessionId((string) $sessionId);
        }

        try {
            $fraudAdvice = $this->client->retrieveAdvice($siteId, $payload);

            return $this->translator->translate($fraudAdvice, $params, $for);
        } catch (ApiException $apiException) {
            throw new FraudAdviceApiException($apiException->getMessage());
        }
    }
}

<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade;

use ProbillerNG\CascadeServiceClient\Model\InlineObject;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade\Exceptions\CascadeTranslatorException;

class RetrieveCascadeAdapter implements CascadeAdapter
{
    /**
     * @var CascadeClient
     */
    private $cascadeClient;

    /**
     * @var CascadeTranslator
     */
    private $cascadeTranslator;

    /**
     * CascadeAdapter constructor.
     * @param CascadeClient     $cascadeClient     Cascade Client
     * @param CascadeTranslator $cascadeTranslator Cascade Translator
     */
    public function __construct(CascadeClient $cascadeClient, CascadeTranslator $cascadeTranslator)
    {
        $this->cascadeClient     = $cascadeClient;
        $this->cascadeTranslator = $cascadeTranslator;
    }

    /**
     * @param string      $sessionId       Session Id
     * @param string      $siteId          Site Id
     * @param string      $businessGroupId Business Group Id
     * @param string      $country         Country
     * @param string      $paymentType     Payment type
     * @param string|null $paymentMethod   Payment method
     * @param string|null $trafficSource   Traffic source
     * @return mixed
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProbillerNG\CascadeServiceClient\ApiException
     */
    public function get(
        string $sessionId,
        string $siteId,
        string $businessGroupId,
        string $country,
        string $paymentType,
        ?string $paymentMethod,
        ?string $trafficSource
    ): Cascade {
        Log::info(
            'Retrieving the biller cascade by providing the following parameters: ',
            [
                'sessionId'       => $sessionId,
                'siteId'          => $siteId,
                'businessGroupId' => $businessGroupId,
                'country'         => $country,
                'paymentType'     => $paymentType,
                'paymentMethod'   => $paymentMethod,
                'trafficSource'   => $trafficSource,
            ]
        );

        $cascadePayload = new InlineObject(
            [
                'sessionId'       => $sessionId,
                'siteId'          => $siteId,
                'businessGroupId' => $businessGroupId,
                'country'         => $country,
                'paymentType'     => $paymentType,
                'paymentMethod'   => $paymentMethod,
                'trafficSource'   => $trafficSource,
            ]
        );

        $cascadeResult = $this->cascadeClient->retrieveCascade($sessionId, $cascadePayload);

        Log::info("Cascade retrieved:", $cascadeResult->getBillers());

        return $this->cascadeTranslator->translateCascade($cascadeResult);
    }
}

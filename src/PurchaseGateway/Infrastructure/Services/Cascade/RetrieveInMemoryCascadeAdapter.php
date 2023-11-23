<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;

class RetrieveInMemoryCascadeAdapter implements CascadeAdapter
{
    /**
     * @var CascadeTranslator
     */
    private $cascadeTranslator;

    /**
     * CascadeAdapter constructor.
     * @param CascadeTranslator $cascadeTranslator Cascade Translator
     */
    public function __construct(CascadeTranslator $cascadeTranslator)
    {
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
     * @return mixed|Cascade
     * @throws \Exception
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
            'RetrieveDefaultCascade:',
            [
                'biller' => RocketgateBiller::BILLER_NAME
            ]
        );

        return $this->cascadeTranslator->translate(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller(), new RocketgateBiller()]))
        );
    }
}

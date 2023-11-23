<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\BillerMapping;

use Probiller\Common\BillerMapping as CommonBillerMapping;
use Probiller\Common\Fields\BillerData;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\BillerMappingException;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFieldsFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;

class BillerMappingTranslator
{
    /**
     * @param CommonBillerMapping $commonBillerMapping Biller mapping
     * @param string              $currencyCode        Currency code
     * @param string              $businessGroupId     Business group id
     * @param string              $siteId              Site id
     *
     * @return BillerMapping
     * @throws BillerMappingException
     * @throws UnknownBillerNameException
     * @throws Exception
     */
    public static function translate(
        CommonBillerMapping $commonBillerMapping,
        string $currencyCode,
        string $businessGroupId,
        string $siteId
    ): BillerMapping {
        $commonBiller = $commonBillerMapping->getBiller();

        if (!$commonBiller) {
            throw new BillerMappingException();
        }

        self::validateCurrency($commonBillerMapping, $currencyCode);

        $billerFieldsData = self::extractBillerFields($commonBillerMapping, $commonBiller);

        $biller = BillerFactoryService::create($commonBiller->getName());

        $billerFields = BillerFieldsFactoryService::create($biller, $billerFieldsData);

        return BillerMapping::create(
            SiteId::createFromString($siteId),
            BusinessGroupId::createFromString($businessGroupId),
            $currencyCode,
            $commonBiller->getName(),
            $billerFields
        );
    }

    /**
     * @param CommonBillerMapping $commonBillerMapping CommonBillerMapping
     * @param BillerData          $commonBiller        BillerData
     *
     * @return mixed
     * @throws BillerMappingException
     * @throws UnknownBillerNameException
     * @throws Exception
     */
    private static function extractBillerFields(
        CommonBillerMapping $commonBillerMapping,
        BillerData $commonBiller
    ): array {
        $commonBillerFields = $commonBillerMapping->getBiller()->getBillerFields();
        $functionName       = 'get' . $commonBiller->getName();

        if (!method_exists($commonBillerFields, $functionName)) {
            throw new UnknownBillerNameException($functionName);
        }

        if (!$commonBillerFields->$functionName()) {
            throw new BillerMappingException();
        }

        $billerFieldsData = json_decode($commonBillerFields->$functionName()->serializeToJsonString(), true);

        return $billerFieldsData;
    }

    /**
     * @param CommonBillerMapping $commonBillerMapping Common biller mapping
     * @param string              $currencyCode        Currency code
     *
     * @return bool
     * @throws BillerMappingException
     * @throws Exception
     */
    private static function validateCurrency(CommonBillerMapping $commonBillerMapping, string $currencyCode)
    {
        foreach ($commonBillerMapping->getAvailableCurrencies()->getIterator() as $currency) {
            if ($currency == $currencyCode) {
                return true;
            }
        }

        throw new BillerMappingException();
    }
}

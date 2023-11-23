<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use MyCLabs\Enum\Enum;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrency;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrencySymbol;

/**
 * Class CurrencyCode
 *
 * @method static CurrencyCode USD()
 * @method static CurrencyCode EUR()
 * @method static CurrencyCode GBP()
 * @method static CurrencyCode CAD()
 * @method static CurrencyCode AUD()
 * @method static CurrencyCode CHF()
 * @method static CurrencyCode NOK()
 * @method static CurrencyCode DKK()
 * @method static CurrencyCode JPY()
 * @method static CurrencyCode SEK()
 * @method static CurrencyCode INR()
 *
 * @package ProBillerNG\PurchaseGateway\Domain\Model
 * @see     https://www.iso.org/iso-4217-currency-codes.html
 */
class CurrencyCode extends Enum
{
    public const USD = 'USD';       // United States Dollar
    public const EUR = 'EUR';       // Euro
    public const GBP = 'GBP';       // United Kingdom Pounds
    public const CAD = 'CAD';       // Canadian Dollar
    public const AUD = 'AUD';       // Australian dollar
    public const CHF = 'CHF';       // Swiss Franc
    public const NOK = 'NOK';       // Norwegian Krone
    public const DKK = 'DKK';       // Danish Krone
    public const JPY = 'JPY';       // Japanese Yen
    public const SEK = 'SEK';       // Sweden Krona
    public const INR = 'INR';       // Indian Rupees

    public const USD_SYMBOL = '$';
    public const EUR_SYMBOL = '€';
    public const GBP_SYMBOL = '£';
    public const CAD_SYMBOL = '$';
    public const AUD_SYMBOL = '$';
    public const CHF_SYMBOL = 'Fr';
    public const NOK_SYMBOL = 'Kr';
    public const DKK_SYMBOL = 'Kr';
    public const JPY_SYMBOL = '¥';
    public const SEK_SYMBOL = 'Kr';
    public const INR_SYMBOL = '₹';

    /**
     * @param string $currencyCode CurrencyCode
     * @return string
     * @throws \Exception
     */
    public static function symbolByCode(string $currencyCode)
    {
        switch ($currencyCode) {
            case self::USD():
                return self::USD_SYMBOL;
            case self::EUR():
                return self::EUR_SYMBOL;
            case self::GBP():
                return self::GBP_SYMBOL;
            case self::CAD():
                return self::CAD_SYMBOL;
            case self::AUD():
                return self::AUD_SYMBOL;
            case self::CHF():
                return self::CHF_SYMBOL;
            case self::NOK():
                return self::NOK_SYMBOL;
            case self::DKK():
                return self::DKK_SYMBOL;
            case self::SEK():
                return self::SEK_SYMBOL;
            case self::JPY():
                return self::JPY_SYMBOL;
            case self::INR():
                return self::INR_SYMBOL;
            default:
                throw new InvalidCurrencySymbol();
        }
    }

    /**
     * @param string $currency Country
     *
     * @return CurrencyCode
     * @throws InvalidCurrency
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(string $currency): self
    {
        try {
            return new self(
                $currency
            );
        } catch (\UnexpectedValueException $e) {
            throw new InvalidCurrency($currency);
        }
    }
}

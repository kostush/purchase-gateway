<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ControlKeyWordNotFoundException;

/**
 * This class is to keep cgi control key words from NG purchases
 * based on account id number. Theses control key words don't have on legacy
 * and for now we are not using this concept on NG side.
 * If you need to use another account id put in this class.
 * To know where to find the control key word to specific and new account id/ site tag
 * contact risk team
 * Class ControlKeyWord
 * @deprecated
 * @package Billing\Import\NG\Transaction\Request
 */
class ControlKeyword
{
    /**
     * @var array
     */
    private const ACCOUNT_ID_MAP = [
        "113785993259" => 'BtgN3kAIpPtbmudgMRo5',
        "113785976879" => 'BtgN3kAIpPtbmudgMRo5',
        "113785976878" => 'BtgN3kAIpPtbmudgMRo5',
    ];

    /**
     * @param string $accountId account id
     * @return string
     * @throws ControlKeyWordNotFoundException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function get(string $accountId): string
    {
        if (!in_array($accountId, array_keys(self::ACCOUNT_ID_MAP))) {
            throw new ControlKeyWordNotFoundException($accountId);
        }

        return self::ACCOUNT_ID_MAP[$accountId];
    }
}

<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

/**
 * Interface IncreasePurchaseAttempts
 * @package ProBillerNG\PurchaseGateway\Domain
 *
 * Exceptions implementing this class will increase the purchase attempt counter
 * Further control is possible using Returns400Code and Returns500Code interfaces
 * to dictate the response code of the API call
 */
interface IncreasePurchaseAttempts
{

}

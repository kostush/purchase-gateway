<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Fraud\UserInputFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;

interface CheckFraudOnUserInput
{
    /**
     * @param SiteId    $siteId    Site Id.
     * @param Email     $email     Email.
     * @param Bin       $bin       Bin.
     * @param Zip       $zip       Zip Code.
     * @param SessionId $sessionId Session Id.
     *
     * @return UserInputFraudAdvice
     */
    public function retrieveAdviceOnUserInput(
        SiteId $siteId,
        Email $email,
        Bin $bin,
        Zip $zip,
        SessionId $sessionId
    ): UserInputFraudAdvice;
}

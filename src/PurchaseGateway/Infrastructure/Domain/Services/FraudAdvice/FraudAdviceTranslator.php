<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use ProbillerNG\FraudServiceClient\Model\Error;
use ProbillerNG\FraudServiceClient\Model\InlineResponse200;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\Exceptions\FraudAdviceTranslationException;

class FraudAdviceTranslator
{
    /**
     * @param InlineResponse200|Error $result Result
     * @param array                   $params Params
     * @param string                  $for    For which step
     *
     * @return FraudAdvice
     *
     * @throws FraudAdviceTranslationException
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     */
    public function translate($result, array $params, string $for)
    {
        if (!($result instanceof InlineResponse200)) {
            throw new FraudAdviceTranslationException('Invalid Response from Fraud service');
        }

        $ip    = isset($params['ip']) ? Ip::create($params['ip']) : null;
        $email = isset($params['email']) ? Email::create($params['email']) : null;
        $zip   = isset($params['zip']) ? Zip::create($params['zip']) : null;
        $bin   = isset($params['bin']) ? Bin::createFromString($params['bin']) : null;

        $fraudAdvice = FraudAdvice::create($ip, $email, $zip, $bin);

        if ($result->getCaptcha()) {
            switch ($for) {
                case FraudAdvice::FOR_INIT:
                    $fraudAdvice->markInitCaptchaAdvised();
                    break;
                case FraudAdvice::FOR_PROCESS:
                    $fraudAdvice->markProcessCaptchaAdvised();
                    break;
            }
        }

        if ($result->getBlacklist()) {
            switch ($for) {
                case FraudAdvice::FOR_INIT:
                    $fraudAdvice->markBlacklistedOnInit();
                    break;
                case FraudAdvice::FOR_PROCESS:
                    $fraudAdvice->markBlacklistedOnProcess();
                    break;
            }
        }

        return $fraudAdvice;
    }
}

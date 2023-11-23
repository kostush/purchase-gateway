<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use DateTime;
use DateTimeImmutable;
use Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrency;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Repository\PurchaseProcessRepositoryInterface;
use ProBillerNG\PurchaseGateway\Domain\Model\Session\SessionInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use Ramsey\Uuid\Uuid;
use function json_encode;

class DatabasePurchaseProcessHandler implements PurchaseProcessHandler
{
    /**
     * @var PurchaseProcess
     */
    protected $purchaseProcess;

    /**
     * @var PurchaseProcessRepositoryInterface
     */
    private $purchaseProcessRepository;

    /**
     * DatabaseSessionHandler constructor.
     * @param PurchaseProcessRepositoryInterface $purchaseProcessRepository The session repository interface
     */
    public function __construct(PurchaseProcessRepositoryInterface $purchaseProcessRepository)
    {
        $this->purchaseProcessRepository = $purchaseProcessRepository;
    }

    /**
     * @param string $sessionId The session Id
     *
     * @return PurchaseProcess
     * @throws InitPurchaseInfoNotFoundException
     * @throws Exception
     */
    public function load(string $sessionId): PurchaseProcess
    {
        Log::info('Retrieving PurchaseProcess object', ['sessionId' => $sessionId]);

        if ($this->purchaseProcess === null) {
            /** @var SessionInfo $sessionInfo */
            $sessionInfo = $this->purchaseProcessRepository->findOne(
                Uuid::fromString($sessionId)
            );

            $this->restorePurchaseProcess($sessionInfo);
        }

        if ($this->purchaseProcess === null) {
            throw new InitPurchaseInfoNotFoundException();
        }

        return $this->purchaseProcess;
    }

    /**
     * @param SessionInfo|null $sessionInfo SessionInfo
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
     * @throws InvalidCurrency
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoFirstName
     * @throws InvalidUserInfoLastName
     * @throws InvalidUserInfoPassword
     * @throws InvalidUserInfoPhoneNumber
     * @throws InvalidUserInfoUsername
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException
     * @throws UnknownBillerNameException
     */
    private function restorePurchaseProcess(?SessionInfo $sessionInfo): void
    {
        if (is_null($sessionInfo)) {
            return;
        }

        $sessionPayload = json_decode($sessionInfo->payload(), true);

        if (is_null($sessionPayload)) {
            return;
        }

        $this->purchaseProcess = PurchaseProcess::restore($sessionPayload);
    }

    /**
     * @param PurchaseProcess|null $purchaseProcess The purchase process object
     * @return bool
     * @throws Exception
     */
    public function create(?PurchaseProcess $purchaseProcess): bool
    {
        if (is_null($purchaseProcess)) {
            return false;
        }

        $sessionInfo = SessionInfo::create(
            (string) $purchaseProcess->sessionId(),
            json_encode($purchaseProcess->toArray()),
            new DateTime()
        );

        return $this->purchaseProcessRepository->create($sessionInfo);
    }

    /**
     * @param PurchaseProcess $purchaseProcess The PurchaseProcess object
     *
     * @return bool
     * @throws Exception
     */
    public function update(PurchaseProcess $purchaseProcess): bool
    {
        $purchaseProcess = SessionInfo::create(
            (string) $purchaseProcess->sessionId(),
            json_encode($purchaseProcess->toArray())
        );

        return $this->purchaseProcessRepository->update($purchaseProcess);
    }

    /**
     * @param DateTimeImmutable|null $anEventDate  Start event date
     * @param DateTimeImmutable      $endEventDate End event date
     * @param int                    $batchSize    Bach size
     * @return array
     * @throws Exception
     */
    public function retrieveSessionsBetween(
        ?DateTimeImmutable $anEventDate,
        DateTimeImmutable $endEventDate,
        int $batchSize
    ): array {

        $sessionsInfo = $this->purchaseProcessRepository->retrieveSessionsBetween(
            $anEventDate,
            $endEventDate,
            $batchSize
        );

        return $sessionsInfo;
    }
}

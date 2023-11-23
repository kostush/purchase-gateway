<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidNextBillerException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\NoBillersInCascadeException;
use ProBillerNG\PurchaseGateway\Domain\RemovedBillerCollectionForThreeDS;

class Cascade
{
    /**
     * @var BillerCollection
     */
    private $billers;

    /**
     * @var Biller
     */
    private $currentBiller;

    /**
     * @var int
     */
    private $currentBillerSubmit;

    /**
     * @var int
     */
    private $currentBillerPosition;

    /**
     * @var RemovedBillerCollectionForThreeDS
     */
    private $currentBillersRemovedForNotSupportingThreeDS;

    /**
     * Cascade constructor.
     * @param BillerCollection                       $billers               Billers
     * @param Biller|null                            $currentBiller         Current Biller
     * @param int                                    $currentBillerSubmit   Current Biller Submit
     * @param int                                    $currentBillerPosition Current Biller Position
     * @param RemovedBillerCollectionForThreeDS|null $removedBillersFor3Ds  Removed Biller Collection
     */
    private function __construct(
        BillerCollection $billers,
        ?Biller $currentBiller,
        int $currentBillerSubmit,
        int $currentBillerPosition,
        ?RemovedBillerCollectionForThreeDS $removedBillersFor3Ds
    ) {
        $this->billers = $billers;
        $this->initCurrentBiller($currentBiller);
        $this->currentBillerSubmit   = $currentBillerSubmit;
        $this->currentBillerPosition = $currentBillerPosition;

        $this->currentBillersRemovedForNotSupportingThreeDS = $this->initRemovedBillerCollection($removedBillersFor3Ds);
    }

    /**
     * @param RemovedBillerCollectionForThreeDS|null $removedBillersFor3Ds Removed Biller
     * @return RemovedBillerCollectionForThreeDS
     */
    private function initRemovedBillerCollection(?RemovedBillerCollectionForThreeDS $removedBillersFor3Ds
    ): RemovedBillerCollectionForThreeDS {
        if (empty($removedBillersFor3Ds) || (!$removedBillersFor3Ds instanceof RemovedBillerCollectionForThreeDS)) {
            return new RemovedBillerCollectionForThreeDS();
        }

        return $removedBillersFor3Ds;
    }

    /**
     * @param BillerCollection                       $billers               Billers
     * @param Biller|null                            $currentBiller         Current Biller
     * @param int                                    $currentBillerSubmit   Current Biller Submit
     * @param int                                    $currentBillerPosition Current Biller Position
     * @param RemovedBillerCollectionForThreeDS|null $removedBillersFor3Ds  Removed Biller Collection
     * @return Cascade
     */
    public static function create(
        BillerCollection $billers,
        Biller $currentBiller = null,
        int $currentBillerSubmit = 0,
        int $currentBillerPosition = 0,
        ?RemovedBillerCollectionForThreeDS $removedBillersFor3Ds = null
    ): self {
        return new static(
            $billers,
            $currentBiller,
            $currentBillerSubmit,
            $currentBillerPosition,
            $removedBillersFor3Ds
        );
    }

    /**
     * @return Biller
     * @throws InvalidNextBillerException
     */
    public function nextBiller(): Biller
    {
        if (!$this->hasSubmitsLeft()) {
            throw new InvalidNextBillerException();
        }

        $this->currentBiller = $this->retrieveBiller();

        $this->incrementCurrentBillerSubmit();

        return $this->currentBiller;
    }

    /**
     * @return BillerCollection
     */
    public function billers(): BillerCollection
    {
        return $this->billers;
    }

    /**
     * @return Biller
     */
    public function firstBiller(): Biller
    {
        return $this->billers()->first();
    }

    /**
     * @return int
     */
    public function currentBillerSubmit(): int
    {
        return $this->currentBillerSubmit;
    }

    /**
     * @return Biller
     */
    public static function defaultBiller(): Biller
    {
        return new RocketgateBiller();
    }

    /**
     * @return void
     */
    public function incrementCurrentBillerSubmit(): void
    {
        $this->currentBillerSubmit++;
    }

    /**
     * @return bool
     * @throws \ProBillerNG\Logger\Exception
     */
    public function hasSubmitsLeft(): bool
    {
        $hasSubmitsLeft = ($this->isLastBiller() && !$this->hasCurrentBillerSubmitsLeft());

        Log::info("Cascade has submits left: " . $hasSubmitsLeft);

        if ($hasSubmitsLeft) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isTheNextBillerThirdParty(): bool
    {
        if ($this->hasCurrentBillerSubmitsLeft()) {
            return $this->currentBiller->isThirdParty();
        }

        if ($this->simulateNextBillerFromCascade() instanceof Biller) {
            return $this->simulateNextBillerFromCascade()->isThirdParty();
        }

        return false;
    }

    /**
     * @return Biller
     */
    public function currentBiller(): Biller
    {
        return $this->currentBiller;
    }

    /**
     * @return void
     * @throws NoBillersInCascadeException
     */
    public function removeEpochBiller(): void
    {
        foreach ($this->billers as $key => $biller) {
            if ($biller->name() !== EpochBiller::BILLER_NAME) {
                continue;
            }
            $this->billers->remove($key);
        }

        if ($this->billers->count() === 0) {
            throw new NoBillersInCascadeException();
        }
    }

    /**
     * @return null|RemovedBillerCollectionForThreeDS
     */
    public function removedBillersFor3DS(): ?RemovedBillerCollectionForThreeDS
    {
        return $this->currentBillersRemovedForNotSupportingThreeDS;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function removeNonThreeDSBillers(): void
    {
        if (!$this->currentBiller->isThirdParty()
            && ($this->currentBillerSubmit > 0
                || $this->currentBillerPosition > 0)
        ) {
            return;
        }

        $this->billers = $this->billers()->filter(
            function ($biller) {
                /** @var Biller $biller */
                if (!$biller->isThreeDSupported()) {
                    if ($this->currentBiller()->name() === $biller->name()) {
                        $this->currentBillersRemovedForNotSupportingThreeDS->add($biller->name());
                    }
                    return null;
                }
                return $biller;
            }
        );

        if ($this->billers->count() === 0) {
            throw new NoBillersInCascadeException();
        }

        $this->resetCascade();

        Log::info('Billers on cascade after non 3ds filtering: ', array_map('strval', $this->billers->toArray()));
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $billers = [];
        foreach ($this->billers as $biller) {
            $billers[] = $biller->name();
        }

        $removedBillers = [];
        if (!empty($this->removedBillersFor3DS()) && $this->currentBillersRemovedForNotSupportingThreeDS->count() > 0) {
            foreach ($this->currentBillersRemovedForNotSupportingThreeDS as $removedBiller) {
                $removedBillers[] = $removedBiller;
            }
        }

        return [
            'billers'               => $billers,
            'currentBiller'         => (string) $this->currentBiller,
            'currentBillerSubmit'   => $this->currentBillerSubmit,
            'currentBillerPosition' => $this->currentBillerPosition,
            'removedBillersFor3DS'  => $removedBillers
        ];
    }

    /**
     * @return mixed
     */
    private function retrieveBiller(): Biller
    {
        if ($this->hasCurrentBillerSubmitsLeft()) {
            return $this->currentBiller;
        }

        return $this->useNextBillerFromCascade();
    }

    /**
     * @return bool
     * @throws \ProBillerNG\Logger\Exception
     */
    private function hasCurrentBillerSubmitsLeft(): bool
    {
        $hasCurrentBillerSubmitsLeft = ($this->currentBillerSubmit < $this->currentBiller->maxSubmits());

        Log::info("Cascade has current biller submit left: " . $hasCurrentBillerSubmitsLeft);

        if ($hasCurrentBillerSubmitsLeft) {
            return true;
        }

        return false;
    }

    /**
     * @return Biller
     */
    private function useNextBillerFromCascade(): Biller
    {
        $this->currentBillerSubmit = 0;
        $this->currentBillerPosition++;

        return $this->billers()->get($this->currentBillerPosition);
    }

    /**
     * @return null|Biller
     */
    private function simulateNextBillerFromCascade(): ?Biller
    {
        return $this->billers()->get($this->currentBillerPosition + 1);
    }

    /**
     * @return bool
     * @throws \ProBillerNG\Logger\Exception
     */
    private function isLastBiller(): bool
    {
        $isLastBiller = (($this->currentBillerPosition + 1) === $this->billers->count());

        Log::info("Cascade is last biller: " . $isLastBiller);

        return $isLastBiller;
    }

    /**
     * @param Biller|null $currentBiller Current Biller
     * @return void
     */
    private function initCurrentBiller(?Biller $currentBiller): void
    {
        if ($currentBiller instanceof Biller) {
            $this->currentBiller = $currentBiller;
        } else {
            $this->currentBiller = $this->billers->first();
        }
    }

    /**
     * @return void
     */
    private function resetCascade(): void
    {
        $this->currentBillerPosition = 0;
        $this->currentBillerSubmit   = 0;
        $this->initCurrentBiller(null);
    }
}

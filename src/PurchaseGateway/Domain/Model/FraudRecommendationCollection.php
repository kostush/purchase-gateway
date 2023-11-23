<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Base\Domain\Collection;

class FraudRecommendationCollection extends Collection
{
    /**
     * Validates the object
     * @param mixed $object object
     * @return bool
     */
    protected function isValidObject($object)
    {
        return $object instanceof FraudRecommendation;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = [];

        foreach ($this->getValues() as $object) {
            /** @var FraudRecommendation $object */
            $data[] = $object->toArray();
        }

        return $data;
    }

    /**
     * With this update blacklist and velocity block
     * will be handle here, blocking immediately.
     * Captcha and 3DS still be handled in the old fraud advice
     * Because they are soft block.
     * @return bool
     */
    public function hasHardBlock(): bool
    {
        foreach ($this->getValues() as $object) {
            /** @var FraudRecommendation $object */
            if ($object->isHardBlock()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function hasBypassPaymentTemplateValidation(): bool
    {
        foreach ($this->getValues() as $object) {
            /** @var FraudRecommendation $object */
            if ($object->isBypassTemplateValidation()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Creating when restore the session
     * @param array $arrayFraudRecommendationCollection Fraud
     * @return FraudRecommendationCollection
     */
    public static function createFromArray(array $arrayFraudRecommendationCollection): self
    {
        $collection = new FraudRecommendationCollection();
        foreach ($arrayFraudRecommendationCollection as $arrayFraudRecommendation) {
            $collection->add(
                FraudRecommendation::create(
                    $arrayFraudRecommendation['code'],
                    $arrayFraudRecommendation['severity'],
                    $arrayFraudRecommendation['message']
                )
            );
        }
        return $collection;
    }
}

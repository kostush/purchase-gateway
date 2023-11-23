<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

trait Validators
{
    /**
     * Checks if the value is a valid integer
     *
     * @param string|int $value Value
     * @return bool
     */
    protected function isValidInteger($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) !== null;
    }

    /**
     * Checks if a date is valid
     *
     * @param string $date Date
     * @return bool
     */
    protected function isValidDate(string $date): bool
    {
        return strtotime($date) !== false;
    }

    /**
     * Checks if the value is a valid boolean
     *
     * @param string|bool $value Value
     * @return bool
     */
    protected function isValidBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
    }

    /**
     * Checks if the value is a valid float
     *
     * @param string|float $value Value
     * @return bool
     */
    protected function isValidFloat($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) !== null;
    }

    /**
     * @param string $value Value
     * @return bool
     */
    protected function isValidEmail($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE) !== null;
    }
}

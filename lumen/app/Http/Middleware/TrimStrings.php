<?php

declare(strict_types=1);

namespace App\Http\Middleware;

class TrimStrings extends TransformsRequest
{
    /**
     * The attributes that should not be trimmed.
     *
     * @var array
     */
    protected $except = [
        'password'
    ];

    /**
     * Transform the given value.
     *
     * @param  string $key   Key
     * @param  mixed  $value Value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        if (in_array($key, $this->except, true)) {
            return $value;
        }

        return is_string($value) ? trim($value) : $value;
    }
}

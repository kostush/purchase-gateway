<?php
declare(strict_types=1);

namespace App\Http\Middleware\Utils;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

trait UuidValidatorTrait
{

    /**
     * @param mixed $siteId
     *
     * @return Validator
     */
    public function getUuidValidator($siteId): Validator
    {
        return ValidatorFacade::make(
            ['siteId' => $siteId],
            ['siteId' => 'required|uuid'],
            ['uuid' => 'The :attribute value :input is not uuid.']
        );
    }
}
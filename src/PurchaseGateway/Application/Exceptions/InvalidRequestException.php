<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

/**
 * Class InvalidRequestException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class InvalidRequestException extends ValidationException
{
    /**
     * InvalidRequestException constructor.
     *
     * @param Validator $validator Request validator
     */
    public function __construct(Validator $validator)
    {
        $response = new JsonResponse($validator->errors(), Response::HTTP_BAD_REQUEST);

        parent::__construct($validator, $response);
    }
}

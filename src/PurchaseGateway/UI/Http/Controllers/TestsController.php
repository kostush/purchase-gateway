<?php

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class TestsController
 * The purpose of this class is to mock whatever endpoint is required to mimic responses.
 *
 * @package ProBillerNG\PurchaseGateway\UI\Http\Controllers
 */
class TestsController
{
    /**
     * @param $statusCode
     *
     * @return Response
     */
    public function returnHttpStatusCode($statusCode = Response::HTTP_OK)
    {
        return (new Response())->setStatusCode((int) $statusCode);
    }
}

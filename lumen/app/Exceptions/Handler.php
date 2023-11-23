<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidRequestException;
use App\Exceptions\InvalidRequestException as RequestException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidTokenException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidTokenSessionException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionTokenExpiredException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TokenIsExpiredException as TokenExpired;
use ProBillerNG\PurchaseGateway\Exception as DomainException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Exception $exception Exception
     *
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }


    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request Request
     * @param  \Exception               $e       Exception
     *
     * @return Response
     * @throws Exception
     */
    public function render($request, Exception $e)
    {
        if (env('APP_DEBUG')) {
            return parent::render($request, $e);
        }

        // For this errors we need to render a html template for error (eg. case of threed auth)
        if ($e instanceof InvalidTokenException
            || $e instanceof InvalidTokenSessionException
            || $e instanceof SessionTokenExpiredException
        ) {
            $params = [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            return response(view('error', ['response' => $params]), Response::HTTP_BAD_REQUEST);
        }

        if ($e instanceof ApplicationException) {
            return response()->json(
                [
                    'status' => $e->getStatusCode(),
                    'error'  => $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Set the status (default is 400)
        $status  = Response::HTTP_INTERNAL_SERVER_ERROR;
        $headers = [];
        if ($e instanceof UnauthorizedRequestException) {
            $headers = $e->getHeaders();
            $status  = $e->getStatusCode();
        } elseif ($e instanceof HttpException) {
            $status = $e->getStatusCode();
        } elseif ($e instanceof ModelNotFoundException) {
            $status = Response::HTTP_NOT_FOUND;
        } elseif ($e instanceof TokenExpired) {
            $status = Response::HTTP_GATEWAY_TIMEOUT;
        } elseif ($e instanceof InvalidRequestException
            || $e instanceof RequestException
            || $e instanceof \InvalidArgumentException
            || $e instanceof \DomainException
            || $e instanceof DomainException
        ) {
            $status = Response::HTTP_BAD_REQUEST;
        } elseif ($e instanceof BadGateway) {
            $status = Response::HTTP_BAD_GATEWAY;
        }

        // Set the message
        $error = $e->getMessage();
        if ($error === '' && $status == Response::HTTP_NOT_FOUND) {
            $error = 'The requested URL ' . $request->path() . ' was not found on this server';
        }
        $error = $error ?: 'Whoops, looks like something went wrong.';

        // Set the validation_errors
        if ($e instanceof ValidationException && $e->validator) {
            //Concat validation messages
            $validationMessages = $e->validator->getMessageBag()->all();
            $implodedMessages   = \implode(' | ', $validationMessages);

            $error .= ' ' . $implodedMessages;
        }

        // Set the error code
        $code     = $e->getCode();
        $response = \compact('code', 'error');

        return response($response, $status, $headers);
    }
}

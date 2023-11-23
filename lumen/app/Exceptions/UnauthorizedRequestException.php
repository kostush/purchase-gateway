<?php
declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UnauthorizedRequestException extends UnauthorizedHttpException
{
    public const AUTH_KEY   = 'Basic';
    public const AUTH_TOKEN = 'Bearer';

    /**
     * UnauthorizedRequestException constructor.
     * @param string $authType Auth type
     * @param string $message  Message
     * @throws \Exception
     */
    public function __construct(string $authType, $message = '')
    {
        if ($authType === self::AUTH_KEY) {
            $challenge = self::AUTH_KEY . ' realm="X-API-Key"';
        } elseif ($authType === self::AUTH_TOKEN) {
            $challenge = self::AUTH_TOKEN . ' realm="JWT"';
        } else {
            throw new \Exception('Invalid authentication type');
        }

        parent::__construct($challenge, $message);
    }
}

<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Mgpg;

use Lcobucci\JWT\Parser;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\ProcessRebillUpdateRequest;
use Ramsey\Uuid\Uuid;

class ProcessRebillUpdateCommand extends ProcessCommand
{
    /**
     * @param ProcessRebillUpdateRequest $request
     * @return ProcessRebillUpdateCommand
     * @throws \Exception
     */
    public static function createCommandFromRequest(ProcessRebillUpdateRequest $request)
    {
        $token                = (new Parser())->parse($request->bearerToken());
        $fallbackPostbackUrl  = $request->attributes->get('site')->postbackUrl();

        return new ProcessRebillUpdateCommand(
            $request->attributes->get('sessionId'),
            $request->headers->get('X-CORRELATION-ID'),
            $token->getClaim('X-Mgpg-Session-Id'),
            (int) $token->getClaim('X-Public-Key-Id'),
            $token->getClaim('X-Postback-Url') ?? $fallbackPostbackUrl,
            $token->getClaim('X-Return-Url') ?? '',
            $token->getClaim('X-Mgpg-Auth-Token')
        );
    }
}

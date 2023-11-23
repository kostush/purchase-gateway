<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\Exceptions\SiteAdminCodeErrorException;
use ProBillerNG\Logger\Log;
use ProbillerNG\SiteAdminServiceClient\Model\Error;

class SiteAdminTranslator
{
    /**
     * @param mixed $result Result
     * @return array
     * @throws SiteAdminCodeErrorException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function translate($result): array
    {
        if ($result instanceof Error) {
            throw new SiteAdminCodeErrorException(null, $result->getError(), $result->getCode());
        }

        $jsonResponse = $result->getData();

        $data = [];
        foreach ($jsonResponse as $value) {
            $data[] = json_decode($value->toHeaderValue(), true);
        }

        return $data;
    }
}

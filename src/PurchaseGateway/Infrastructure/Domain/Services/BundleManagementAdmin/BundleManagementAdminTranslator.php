<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin;

use ProbillerNG\BundleManagementAdminServiceClient\Model\Error;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\Exceptions\BundleManagementAdminCodeErrorException;

class BundleManagementAdminTranslator
{
    /**
     * @param mixed $result Result
     * @return array
     * @throws BundleManagementAdminCodeErrorException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function translate($result): array
    {
        if ($result instanceof Error) {
            throw new BundleManagementAdminCodeErrorException(null, $result->getError(), $result->getCode());
        }

        $jsonResponse = $result->getData();

        $data = [];
        if ($jsonResponse !== null && count($jsonResponse) > 0) {
            foreach ($jsonResponse as $value) {
                $data[] = json_decode($value->toHeaderValue(), true);
            }
        }

        if (!empty($data)) {
            Log::info('Bundle Management Admin Events Response received');
        }

        return $data;
    }
}

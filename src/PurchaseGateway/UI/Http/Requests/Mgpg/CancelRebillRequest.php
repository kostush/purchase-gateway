<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg;

use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidRequestException;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Request;

/**
 * Class CancelRebillRequest
 * @package ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg
 */
class CancelRebillRequest extends Request
{
    /**
     * CancelRebillRequest constructor.
     * @throws InvalidRequestException
     */
    public function __construct()
    {
        //constructor was added in order to have the ability to mock the class for testing purposes
        parent::__construct();
    }

    /**
     * Validation Rules.
     *
     * @var array
     */
    protected $rules = [
        'businessGroupId'    => 'required|uuid',
        'memberId'           => 'required|uuid',
        'itemId'             => 'required|uuid',
        'siteId'             => 'required|uuid',
        'usingMemberProfile' => 'boolean',
        'cancellationReason' => 'string',
    ];

    /**
     * @return array
     */
    public function toArray()
    {
        return parent::toArray();
    }

    /**
     * @return array
     */
    protected function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return bool
     */
    public function getUsingMemberProfile(): bool
    {
        return $this->json('usingMemberProfile', true);
    }

    /**
     * @return string
     */
    public function getCancellationReason(): string
    {
        return $this->json('cancellationReason', 'Cancelled Online');
    }
}
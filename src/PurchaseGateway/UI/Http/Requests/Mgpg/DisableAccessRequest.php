<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg;

use ProBillerNG\PurchaseGateway\UI\Http\Requests\Request;

/**
 * Class DisableAccessRequest
 * @package ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg
 */
class DisableAccessRequest extends Request
{
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
        'usingMemberProfile' => 'boolean'
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
}
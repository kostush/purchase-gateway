<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg;

use ProBillerNG\PurchaseGateway\UI\Http\Requests\Request;

class PostbackRequest extends Request
{
    /**
     * Validation Rules.
     *
     * @var array
     */
    protected $rules = [
        'invoice' => 'required|array',
        'digest'  => 'required|string',
    ];

    /**
     * @return array
     */
    protected function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return string JSON string representing purchase process result
     */
    public function getInvoice(): array
    {
        return $this['invoice'];
    }

    /**
     * @return string JSON string representing purchase process result
     */
    public function getDigest(): string
    {
        return $this['digest'];
    }
}
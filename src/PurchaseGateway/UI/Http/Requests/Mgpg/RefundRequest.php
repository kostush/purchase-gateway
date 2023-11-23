<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg;

use ProBillerNG\PurchaseGateway\UI\Http\Requests\Request;

class RefundRequest extends Request
{
    /**
     * @return array
     */
    protected function getRules(): array
    {
        return [
            'businessGroupId' => 'required|uuid',
            'siteId'          => 'required|uuid',
            'memberId'        => 'required|uuid',
            'itemId'          => 'required|uuid',
            'amount'          => 'nullable|numeric|min:0',
            'reason'          => 'required|string|min:5',
        ];
    }

    public function messages()
    {
        return [
            'businessGroupId.required' => 'Business Group is required.',
            'businessGroupId.uuid'     => 'Business Group has to be a valid UUID.',
            'siteId.required'          => 'SiteId is required.',
            'siteId.uuid'              => 'SiteId has to be a valid UUID..',
            'memberId.required'        => 'MemberId is required.',
            'memberId.uuid'            => 'MemberId is has to be a valid UUID.',
            'itemId.required'          => 'ItemId is required.',
            'itemId.uuid'              => 'ItemId is has to be a valid UUID.',
            'amount.numeric'           => 'Amount has to be numeric value.',
            'reason.required'          => 'Reason for refund is required.',
            'reason.string'            => 'Reason for refund has to be string value.',
            'reason.min'               => 'Reason for refund have to have minimum of 5 character.'
        ];
    }
}
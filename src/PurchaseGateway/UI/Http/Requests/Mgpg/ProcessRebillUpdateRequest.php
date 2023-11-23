<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg;

use ProBillerNG\PurchaseGateway\UI\Http\Requests\ProcessPurchaseRequest as BaseProcessPurchaseRequest;

class ProcessRebillUpdateRequest extends BaseProcessPurchaseRequest
{
    /**
     * Validation Rules.
     *
     * @var array
     */
    protected $rules = [
            'siteId'                                               => 'required|uuid',
            'payment.paymentTemplateInformation'                   => 'array',
            'payment.paymentTemplateInformation.paymentTemplateId' => 'required_with:payment.paymentTemplateInformation|uuid',
            'payment.cardInformation'                              => 'array',
            'payment.type'                                         => 'string',
            'payment.method'                                       => 'string|required_with:payment.paymentTemplateInformation',
            'payment.cardInformation.ccNumber'                     => 'required_with:payment.cardInformation|numeric',
            'payment.cardInformation.cvv'                          => 'required_with:payment.cardInformation|numeric',
            'payment.cardInformation.cardExpirationMonth'          => 'required_with:payment.cardInformation|numeric',
            'payment.cardInformation.cardExpirationYear'           => 'required_with:payment.cardInformation|numeric',
            'member'                                               => 'array|required_with:payment.cardInformation',
            'member.email'                                         => 'required_with:member.firstName',
            'member.firstName'                                     => 'required_with:member.email',
            'member.lastName'                                      => 'required_with:member.firstName',
            'payment.checkInformation'                             => 'array',
            'payment.checkInformation.routingNumber'               => 'required_with:payment.checkInformation|numeric',
            'payment.checkInformation.accountNumber'               => 'required_with:payment.checkInformation|numeric',
            'payment.checkInformation.savingAccount'               => 'required_with:payment.checkInformation',
            'payment.checkInformation.socialSecurityLast4'         => 'required_with:payment.checkInformation|numeric',
            'payment.checkInformation.label'                       => 'required_with:payment.checkInformation',
        ];
}

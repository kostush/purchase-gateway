<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg;

use ProBillerNG\PurchaseGateway\UI\Http\Requests\ProcessPurchaseRequest as BaseProcessPurchaseRequest;

class ProcessPurchaseRequest extends BaseProcessPurchaseRequest
{
    /**
     * Validation Rules.
     *
     * @var array
     */
    protected $rules = [
        'payment.paymentTemplateInformation'                   => 'array',
        'payment.paymentTemplateInformation.paymentTemplateId' => 'required_with:payment.paymentTemplateInformation|uuid',
        'payment.checkInformation.label'                       => 'required_with:payment.checkInformation|string',
        'payment.checkInformation.routingNumber'               => 'required_with:payment.checkInformation|numeric',
        'payment.checkInformation.accountNumber'               => 'required_with:payment.checkInformation|numeric',
        'payment.checkInformation.savingAccount'               => 'required_with:payment.checkInformation',
        'payment.checkInformation.socialSecurityLast4'         => 'required_with:payment.checkInformation|numeric|digits:4',
        'member.email'                                         => 'required_with:member.firstName',
        'member.firstName'                                     => 'required_with:member.email',
        'member.lastName'                                      => 'required_with:member.firstName',
        'payment.cryptoCurrency'                               => 'required_if:payment.type,cryptocurrency|required_if:payment.method,cryptocurrency',
    ];

    /**
     * @return string|null
     */
    public function cryptoCurrency(): ?string
    {
        return (string) ($this->json('payment.cryptoCurrency', null));
    }
}

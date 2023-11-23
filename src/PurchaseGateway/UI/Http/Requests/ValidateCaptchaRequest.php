<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests;

class ValidateCaptchaRequest extends Request
{

    /** @var array */
    protected $rules = [
        'siteId' => 'required|uuid'
    ];

    /**
     * @var array
     */
    protected $messages = [
        'uuid' => 'The :attribute value :input is not uuid.'
    ];

    /**
     * @return array
     */
    protected function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    protected function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @return string
     */
    public function siteId(): string
    {
        return (string) $this->json('siteId');
    }
}

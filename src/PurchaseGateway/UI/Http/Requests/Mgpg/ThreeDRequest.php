<?php

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg;

use Lcobucci\JWT\Parser;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Request;

class ThreeDRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function __construct()
    {
        return;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            'jwt'             => 'required',
            'authenticateUrl' => 'required|url',
            'paReq'           => 'required|string',
            'termUrl'         => 'required|url',
        ]);
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(CryptService $cryptService)
    {
        parent::prepareForValidation();

        $token = (new Parser())->parse($this->jwt);

        if ($token) {
            $this->merge([
                 'jwt'             => $this->jwt,
                 'authenticateUrl' => $this->cryptService->decrypt($token->getClaim('authenticateUrl')),
                 'paReq'           => $this->cryptService->decrypt($token->getClaim('paReq')),
                 'termUrl'         => $this->cryptService->decrypt($token->getClaim('termUrl')),
                 'isExpired'       => $token->isExpired(),
                 'isValid'         => $token->isValid(),
             ]);

            return;
        }

        $this->merge([]);
    }

    public function messages()
    {
        return [
            'jwt.required'             => '{jwt} is required.',
            'authenticateUrl.required' => 'Malformed JWT: `authenticateUrl` is required.',
            'authenticateUrl.url'      => 'Malformed JWT: `authenticateUrl` must contain a valid url.',
            'paReq.required'           => 'Malformed JWT: `paReq` is required.',
            'paReq.string'             => 'Malformed JWT: `paReq` must contain a string value.',
            'termUrl.required'         => 'Malformed JWT: `termUrl` is required.',
            'termUrl.url'              => 'Malformed JWT: `termUrl` is must contain a valid url.',
        ];
    }

    /**
     * @return array
     */
    protected function getRules(): array
    {
        return [
            'jwt'             => 'required',
            'authenticateUrl' => 'required|url',
            'paReq'           => 'required|string',
            'termUrl'         => 'required|url',
        ];
    }
}

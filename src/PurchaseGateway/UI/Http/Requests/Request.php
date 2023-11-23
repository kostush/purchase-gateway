<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests;

use Illuminate\Http\Request as BaseRequest;
use Illuminate\Support\Facades\Validator;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidRequestException;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;

/**
 * Class Request
 * @package ProBillerNG\PurchaseGateway\UI\Http\Requests
 */
abstract class Request extends BaseRequest
{
    /**
     * @var array
     */
    private static $messages = [
        'uuid'          => 'The :attribute with value :input is not uuid.',
        'country_two'   => 'The :attribute with value :input is not valid  2 characters country code.',
        'country_three' => 'The :attribute with value :input is not valid 3 characters country code.',
        'alpha_spaces'  => 'The :attribute with value :input should not contain other characters than letters and spaces',
        'required_with' => 'The :attribute field is required.',
        'integer_only'  => 'The :attribute must be an integer',
        'numeric'       => 'The :attribute with value :input has to be numeric',
    ];

    /**
     * Request constructor.
     *
     * {@inheritdoc}
     * @throws InvalidRequestException
     */
    public function __construct()
    {
        parent::__construct(
            app(BaseRequest::class)->query->all(),
            app(BaseRequest::class)->request->all(),
            app(BaseRequest::class)->attributes->all(),
            app(BaseRequest::class)->cookies->all(),
            app(BaseRequest::class)->files->all(),
            app(BaseRequest::class)->server->all(),
            app(BaseRequest::class)->content
        );

        $this->json()->replace(app(BaseRequest::class)->json()->all());

        $this->validate();
    }

    /**
     * Validates the request
     *
     * @return void
     * @throws InvalidRequestException
     */
    public function validate(): void
    {
        $validator = Validator::make($this->json()->all(), $this->getRules(), $this->getMessages());

        if ($validator->fails()) {
            throw new InvalidRequestException($validator);
        }
    }

    /**
     * @return array
     */
    abstract protected function getRules(): array;

    /**
     * @return array
     */
    protected function getMessages(): array
    {
        return self::$messages;
    }

    /**
     * @return JsonWebToken
     */
    public function decodedToken(): JsonWebToken
    {
        return $this->attributes->get('decodedToken');
    }

    /**
     * Get all the headers required by Fraud team and map them to specific names so that they receive all headers
     * in the requested format and with the requested names.
     *
     * @return array
     */
    public function getFraudRequiredHeaders(): array
    {
        /*
         * The following array contains the headers required by Fraud service to be sent.
         * Also, their request is to send them with a specific name for easier tracking and processing,
         * therefore a mapping has been created.
         *
         * If we need to add or remove a specific header all we have to do is update this array.
         */
        $requiredHeadersMapping = [
            'x-51d-browsername'       => 'browserName',
            'x-51d-browserversion'    => 'browserVersion',
            'x-51d-platformname'      => 'platformName',
            'x-51d-platformversion'   => 'platformVersion',
            'x-51d-devicetype'        => 'deviceType',
            'x-51d-ismobile'          => 'isMobile',
            'x-51d-hardwaremodel'     => 'hardwareModel',
            'x-51d-hardwarefamily'    => 'hardwareFamily',
            'x-51d-javascript'        => 'javascript',
            'x-51d-javascriptversion' => 'javascriptVersion',
            'x-51d-viewport'          => 'viewport',
            'x-51d-html5'             => 'html5',
            'x-51d-iscrawler'         => 'isCrawler',
            'x-geo-connection-type'   => 'connectionType',
            'x-geo-isp'               => 'isp',
            'x-anonymous-type'        => 'anonymousType',
        ];

        $mappedHeaders = [];

        $headers = $this->header();

        foreach ($requiredHeadersMapping as $requiredHeaderKey => $requiredHeaderValue) {
            if (isset($headers[$requiredHeaderKey])) {
                $mappedHeaders[$requiredHeaderValue] = $headers[$requiredHeaderKey];
            }
        }

        return $mappedHeaders;
    }

    /**
     * @param mixed $value Cast to string if not null
     * @return string|null
     */
    protected function stringify($value): ?string
    {
        return !is_null($value) ? (string) $value : $value;
    }
}

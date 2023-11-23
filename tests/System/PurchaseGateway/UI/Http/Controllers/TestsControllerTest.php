<?php

declare(strict_types=1);

namespace Tests\System\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\Response;
use Laravel\Lumen\Testing\Concerns\MakesHttpRequests;
use Tests\SystemTestCase;

/**
 * Class TestsControllerTest
 * @package Tests\System\PurchaseGateway\UI\Http\Controllers
 */
class TestsControllerTest extends SystemTestCase
{
    use MakesHttpRequests;

    /**
     * @test
     * @dataProvider clientPostbackDataProvider
     *
     * @param int $givenStatusCode    Given Http Status Code.
     * @param int $expectedStatusCode Expected Http Status Code.
     *
     * @return void
     */
    public function client_postback_should_return_http_status_code_according_to_given_status_code(
        int $givenStatusCode,
        int $expectedStatusCode
    ) {
        $this->post('/tests/clientPostbackUrl/' . $givenStatusCode);
        $this->assertResponseStatus($expectedStatusCode);
    }

    public function clientPostbackDataProvider()
    {
        return [
            '100 Continue'              => [
                'statusCode'       => 100,
                'expectedResponse' => Response::HTTP_CONTINUE,
            ],
            '102 Processing'            => [
                'statusCode'       => 102,
                'expectedResponse' => Response::HTTP_PROCESSING,
            ],
            '200 OK'                    => [
                'statusCode'       => 200,
                'expectedResponse' => Response::HTTP_OK,
            ],
            '201 Created'               => [
                'statusCode'       => 201,
                'expectedResponse' => Response::HTTP_CREATED,
            ],
            '301 Moved Permanently'     => [
                'statusCode'       => 301,
                'expectedResponse' => Response::HTTP_MOVED_PERMANENTLY,
            ],
            '302 Found'                 => [
                'statusCode'       => 302,
                'expectedResponse' => Response::HTTP_FOUND,
            ],
            '400 Bad Request'           => [
                'statusCode'       => 400,
                'expectedResponse' => Response::HTTP_BAD_REQUEST,
            ],
            '404 Not Found'             => [
                'statusCode'       => 404,
                'expectedResponse' => Response::HTTP_NOT_FOUND,
            ],
            '424 Failed Dependency'     => [
                'statusCode'       => 424,
                'expectedResponse' => Response::HTTP_FAILED_DEPENDENCY,
            ],
            '500 Internal Server Error' => [
                'statusCode'       => 500,
                'expectedResponse' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ],
            '504 Timeout'               => [
                'statusCode'       => 504,
                'expectedResponse' => Response::HTTP_GATEWAY_TIMEOUT,
            ],
        ];
    }

    /**
     * @test
     * @return void
     */
    public function client_postback_should_return_200_status_code_when_calling_without_status_code(): void
    {
        ($this->post('/tests/clientPostbackUrl'));
        $this->assertResponseOk();
    }
}

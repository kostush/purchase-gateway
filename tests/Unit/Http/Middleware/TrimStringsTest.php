<?php
namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\TrimStrings;
use Tests\UnitTestCase;
use Illuminate\Http\Request;

class TrimStringsTest extends UnitTestCase
{

    /**
     * @var Request
     */
    private $request;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->request = new Request;

        $this->request->headers->set('Content-Type', 'application/json');
        $this->request->json()->replace($this->processPurchasePayloadWithSpaces());
    }
    /**
     * @test
     * @return void
     */
    public function request_fields_are_trimmed()
    {
        $middleware = new TrimStrings();

        $middleware->handle(
            $this->request,
            function (Request $req) {
                $this->assertEquals('Mister François', $req->json('member.firstName'));
            }
        );
    }

    /**
     * @test
     * @return void
     */
    public function password_request_field_is_not_trimmed()
    {
        $middleware = new TrimStrings();

        $middleware->handle(
            $this->request,
            function (Request $req) {
                $this->assertEquals('test12345   ', $req->json('member.password'));
            }
        );
    }

    private function processPurchasePayloadWithSpaces()
    {
        return [
            'siteId'  => '8e34c94e-135f-4acb-9141-58b3a6e56c74',
            'member'  => [
                'email'       => 'test-purchasegateway@test.mindgeek.com',
                'username'    => '    test-purchasegateway    ',
                'password'    => 'test12345   ',
                'firstName'   => '    Mister François     ',
                'lastName'    => '    Axe Second    ',
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                'ccNumber'            => $this->faker->creditCardNumber('Visa'),
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }
}

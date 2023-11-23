<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\PurchaseInit;

use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use Tests\IntegrationTestCase;

class PurchaseInitCommandTest extends IntegrationTestCase
{
    /**
     * @return array
     * @throws \Exception
     */
    private function getRequestConfig()
    {
        return [
            'site'              => $this->createSite(),
            'amount'            => 14.97,
            'initialDays'       => 365,
            'rebillDays'        => 365,
            'rebillAmount'      => 14.97,
            'currency'          => 'USD',
            'bundleId'          => '5fd44440-2956-11e9-b210-d663bd873d93',
            'addOnId'           => '670af402-2956-11e9-b210-d663bd873d93',
            'clientIp'          => '10.10.109.185',
            'paymentType'       => 'cc',
            'clientCountryCode' => 'CA',
            'sessionId'         => $this->faker->uuid,
            'atlasCode'         => null,
            'atlasData'         => null,
            'publicKeyIndex'    => 0,
            'tax'               => [],
            'crossSales'        => [],
            'isTrial'           => false,
            'memberId'          => null,
            'subscriptionId'    => null,
            'entrySiteId'       => null,
            'forceCascade'      => null,
            'paymentMethod'     => null,
            'trafficSource'     => null,
            'redirectUrl'       => null,
            'postbackUrl'       => null,
            'requestHeaders'    => [],
            'skipVoid'          => false
        ];
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_contain_amount_zero_when_zero_is_given()
    {
        $requestConfig           = $this->getRequestConfig();
        $requestConfig['amount'] = 0;

        $command = new PurchaseInitCommand(...array_values($requestConfig));

        $this->assertEquals(0, $command->amount());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_an_invalid_amount_is_given()
    {
        $this->expectException(InvalidAmountException::class);

        $requestConfig           = $this->getRequestConfig();
        $requestConfig['amount'] = 'invalidAmount';

        new PurchaseInitCommand(...array_values($requestConfig));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_get_and_translate_headers_for_fraud_service()
    {
        $requestConfig = $this->getRequestConfig();
        $command       = new PurchaseInitCommand(...array_values($requestConfig));

        $this->assertSame([], $command->fraudHeaders());
    }
}

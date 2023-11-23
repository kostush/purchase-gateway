<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Cascade;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\CascadeTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\RetrieveInMemoryCascadeAdapter;
use Tests\UnitTestCase;

class InMemoryCascadeAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_retrieve_correct_cascade_when_correct_data_is_provided(): void
    {
        $expectedCascade = Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]));

        $translator = $this->createMock(CascadeTranslator::class);
        $translator->method('translate')
            ->willReturn($expectedCascade);

        /** @var RetrieveInMemoryCascadeAdapter|MockObject $adapter */
        $adapter = $this->getMockBuilder(RetrieveInMemoryCascadeAdapter::class)
            ->setConstructorArgs([$translator])
            ->setMethods(null)
            ->getMock();

        $this->assertEquals(
            $expectedCascade,
            $adapter->get(
                $this->faker->uuid,
                $this->faker->uuid,
                $this->faker->uuid,
                $this->faker->countryCode,
                'cc',
                'visa',
                'ALL'
            )
        );
    }
}

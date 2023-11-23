<?php

namespace PurchaseGateway\Application;

use ProBillerNG\PurchaseGateway\Application\PurchaseGatewayErrorClassifier;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\RepositoryConnectionException;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception\TransientConfigServiceException;
use ProBillerNG\ServiceBus\ExceptionErrorClassification\Permanent;
use ProBillerNG\ServiceBus\ExceptionErrorClassification\Transient;
use ProBillerNG\ServiceBus\ExceptionErrorClassification\Unknown;
use Tests\UnitTestCase;

class PurchaseGatewayErrorClassifierTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_correct_error_when_an_exception_is_passed()
    {
        $classifier = new PurchaseGatewayErrorClassifier();

        $this->assertInstanceOf(
            Transient::class,
            $classifier->classify((new TransientConfigServiceException()))
        );

        $this->assertInstanceOf(
            Transient::class,
            $classifier->classify((new RepositoryConnectionException()))
        );

       $probillerException =  new class() extends Exception {};

        $this->assertInstanceOf(
            Permanent::class,
            $classifier->classify($probillerException)
        );

        $this->assertInstanceOf(
            Unknown::class,
            $classifier->classify((new \Exception()))
        );

    }
}
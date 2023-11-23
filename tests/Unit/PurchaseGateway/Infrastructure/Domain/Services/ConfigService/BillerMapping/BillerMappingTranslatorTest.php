<?php

namespace PurchaseGateway\Infrastructure\Domain\Services\ConfigService\BillerMapping;

use Google\Protobuf\StringValue;
use Google\Protobuf\Timestamp;
use Probiller\Centrobill\CentrobillFields;
use Probiller\Common\BillerMapping as CommonBillerMapping;
use Probiller\Common\Fields\BillerData;
use Probiller\Common\Fields\BillerFields;
use Probiller\Epoch\EpochFields;
use Probiller\Netbilling\NetbillingFields;
use Probiller\Qysso\QyssoFields;
use Probiller\Rocketgate\RocketgateFields;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\BillerMapping\BillerMappingTranslator;
use Tests\UnitTestCase;

class BillerMappingTranslatorTest extends UnitTestCase
{


    /**
     * @param BillerData $biller
     *
     * @return CommonBillerMapping
     */
    private function getBillerMapping(BillerData $biller): CommonBillerMapping
    {
        $billerMapping = new CommonBillerMapping(
            [
                'billerMappingId'     => $this->faker->uuid,
                'businessGroupId'     => $this->faker->uuid,
                'active'              => true,
                'siteId'              => $this->faker->uuid,
                'createdAt'           => new Timestamp(),
                'updatedAt'           => new Timestamp(),
                'availableCurrencies' => [
                    'USD',
                    'CAD'
                ],
                'biller'              => $biller,
            ]
        );

        return $billerMapping;
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_rocketgate_domain_biller_mapping_when_common_biller_mapping_is_passed(): void
    {
        $rocketgate = new RocketgateFields(
            [
                'merchantId'       => 'test-id',
                'merchantPassword' => 'test-pass',
                'merchantSiteId'   => '12341234',
                'sharedSecret'     => 'sharedSecret',
                'simplified3DS'    => true,
            ]
        );

        $biller = new BillerData(
            [
                'name'         => 'rocketgate',
                'supports3DS1' => false,
                'supports3DS2' => false,
                'billerFields' => new BillerFields(['rocketgate' => $rocketgate])
            ]
        );

        $commonBillerMapping = $this->getBillerMapping($biller);
        $billerMapping       = BillerMappingTranslator::translate(
            $commonBillerMapping,
            'CAD',
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(BillerMapping::class, $billerMapping);
        $this->assertEquals('rocketgate', $billerMapping->billerName());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_netbilling_domain_biller_mapping_when_netbilling_is_passed(): void
    {
        $netbilling = new NetbillingFields(
            [
                'accountId'          => 'test-id',
                'merchantPassword'   => 'test-pass',
                'siteTag'            => 'abc',
                'disableFraudChecks' => true,
                'initialDays'        => 10,
            ]
        );

        $biller = new BillerData(
            [
                'name'         => 'netbilling',
                'supports3DS1' => false,
                'supports3DS2' => false,
                'billerFields' => new BillerFields(['netbilling' => $netbilling])
            ]
        );

        $commonBillerMapping = $this->getBillerMapping($biller);
        $billerMapping       = BillerMappingTranslator::translate($commonBillerMapping,
            'CAD',
            $this->faker->uuid,
            $this->faker->uuid);
        $this->assertInstanceOf(BillerMapping::class, $billerMapping);
        $this->assertEquals('netbilling', $billerMapping->billerName());
    }


    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_epoch_domain_biller_mapping_when_epoch_is_passed(): void
    {
        $epoch = new EpochFields(
            [
                'clientId'              => $this->faker->uuid,
                'clientKey'             => $this->faker->uuid,
                'clientVerificationKey' => $this->faker->uuid,
                'redirectUrl'           => $this->faker->url,
                'notificationUrl'       => $this->faker->url,
            ]
        );

        $biller = new BillerData(
            [
                'name'         => 'epoch',
                'supports3DS1' => false,
                'supports3DS2' => false,
                'billerFields' => new BillerFields(['epoch' => $epoch])
            ]
        );

        $commonBillerMapping = $this->getBillerMapping($biller);
        $billerMapping       = BillerMappingTranslator::translate($commonBillerMapping,
            'CAD',
            $this->faker->uuid,
            $this->faker->uuid);
        $this->assertInstanceOf(BillerMapping::class, $billerMapping);
        $this->assertEquals('epoch', $billerMapping->billerName());
    }


    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_qysso_domain_biller_mapping_when_qysso_is_passed(): void
    {
        $qysso = new QyssoFields(
            [
                'companyNum'      => $this->faker->uuid,
                'personalHashKey' => $this->faker->md5,
                'redirectUrl'     => $this->faker->url,
                'notificationUrl' => $this->faker->url,
            ]
        );

        $biller = new BillerData(
            [
                'name'         => 'qysso',
                'supports3DS1' => false,
                'supports3DS2' => false,
                'billerFields' => new BillerFields(['qysso' => $qysso])
            ]
        );

        $commonBillerMapping = $this->getBillerMapping($biller);
        $billerMapping       = BillerMappingTranslator::translate($commonBillerMapping,
            'CAD',
            $this->faker->uuid,
            $this->faker->uuid);
        $this->assertInstanceOf(BillerMapping::class, $billerMapping);
        $this->assertEquals('qysso', $billerMapping->billerName());
    }


    /**
     * @test
     * @return void
     */
    public function it_should_throw_exception_when_a_non_supported_biller_is_passed(): void
    {

        $this->expectException(UnknownBillerNameException::class);
        $centrobill = new CentrobillFields(
            [

            ]
        );

        $biller = new BillerData(
            [
                'name'         => 'centrobill',
                'supports3DS1' => false,
                'supports3DS2' => false,
                'billerFields' => new BillerFields(['centrobill' => $centrobill])
            ]
        );

        $commonBillerMapping = $this->getBillerMapping($biller);
        $billerMapping       = BillerMappingTranslator::translate($commonBillerMapping,
            'CAD',
            $this->faker->uuid,
            $this->faker->uuid);
        $this->assertInstanceOf(BillerMapping::class, $billerMapping);
        $this->assertEquals('centrobill', $billerMapping->billerName());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_exception_when_the_currency_list_not_have_currency_passed(): void
    {
        $this->expectException(Exception::class);
        $rocketgate = new RocketgateFields(
            [
                'merchantId'       => 'test-id',
                'merchantPassword' => 'test-pass',
                'merchantSiteId'   => '12341234'
            ]
        );

        $biller = new BillerData(
            [
                'name'         => 'rocketgate',
                'supports3DS1' => false,
                'supports3DS2' => false,
                'billerFields' => new BillerFields(['rocketgate' => $rocketgate])
            ]
        );

        $commonBillerMapping = new CommonBillerMapping(
            [
                'billerMappingId'     => $this->faker->uuid,
                'businessGroupId'     => $this->faker->uuid,
                'active'              => true,
                'siteId'              => $this->faker->uuid,
                'createdAt'           => new Timestamp(),
                'updatedAt'           => new Timestamp(),
                'availableCurrencies' => [
                    'USD',
                    'CAD'
                ],
                'biller'              => $biller,
            ]
        );

        $billerMapping = BillerMappingTranslator::translate($commonBillerMapping,
            'EUR',
            $this->faker->uuid,
            $this->faker->uuid);

        $this->assertInstanceOf(BillerMapping::class, $billerMapping);
        $this->assertEquals('rocketgate', $billerMapping->billerName());
    }
}
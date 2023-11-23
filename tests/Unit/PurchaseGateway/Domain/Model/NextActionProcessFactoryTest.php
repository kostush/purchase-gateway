<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\AuthenticateThreeD;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\DeviceDetectionThreeD;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RedirectToUrl;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RenderGateway;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\WaitForReturn;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\FinishProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextAction;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextActionProcessFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Redirected;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Model\ThirdParty;
use Tests\UnitTestCase;

class NextActionProcessFactoryTest extends UnitTestCase
{
    public $url                   = '/api/v1/purchase/threed/authenticate/jwt';
    public $thirdPartyRedirectUrl = '/api/v1/purchase/thirdParty/redirect/jwt';
    public $deviceCollectionUrl   = 'https://centinelapistag.cardinalcommerce.com/V1/Cruise/Collect';
    public $deviceCollectionJwt   = 'eyJhbGciOiJIUzI1NiJ9.eyJqdGkiOiI3MjA1OTE4NzgyNTk3NjM0NSIsImlhdCI6MTU5Mzc4ODA0OCwiaXNzIjoiNWNmYTVhMDFhZmE4MGQyMjUwZmQzMTc5IiwiT3JnVW5pdElkIjoiNWNmNzA2OWRiYjg3NjgxOTZjMTBhNGU5In0.Xs_zq65Z8af1u38tUdO9be1liZ7LN8PiCzRSAjVz6Lg"';

    /**
     * @test
     * @return AuthenticateThreeD
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     * @throws Exception
     */
    public function it_should_return_authenticate_three_d_object(): NextAction
    {
        $state = Pending::create();

        $nextAction = NextActionProcessFactory::create(
            $state,
            $this->url
        );

        $this->assertInstanceOf(AuthenticateThreeD::class, $nextAction);

        return $nextAction;
    }

    /**
     * @test
     * @param AuthenticateThreeD $nextAction NextAction
     * @depends it_should_return_authenticate_three_d_object
     * @return void
     */
    public function authenticate_three_d_object_should_have_three_d_data(AuthenticateThreeD $nextAction): void
    {
        $this->assertArrayHasKey('threeD', $nextAction->toArray());
    }

    /**
     * @test
     * @param AuthenticateThreeD $nextAction NextAction
     * @depends it_should_return_authenticate_three_d_object
     * @return void
     */
    public function three_d_should_have_same_authenticate_url(AuthenticateThreeD $nextAction): void
    {
        $this->assertSame($this->url, $nextAction->toArray()['threeD']['authenticateUrl']);
    }


    /**
     * @test
     * @return DeviceDetectionThreeD
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     * @throws Exception
     */
    public function it_should_return_device_detection_three_d_object(): NextAction
    {
        $state = Pending::create();

        $nextAction = NextActionProcessFactory::create(
            $state,
            $this->url,
            null,
            null,
            $this->deviceCollectionUrl,
            $this->deviceCollectionJwt
        );

        $this->assertInstanceOf(DeviceDetectionThreeD::class, $nextAction);

        return $nextAction;
    }

    /**
     * @test
     * @param DeviceDetectionThreeD $nextAction NextAction
     * @depends it_should_return_device_detection_three_d_object
     * @return void
     */
    public function device_detection_three_d_object_should_have_type_data(DeviceDetectionThreeD $nextAction): void
    {
        $this->assertArrayHasKey('type', $nextAction->toArray());
    }

    /**
     * @test
     * @param DeviceDetectionThreeD $nextAction NextAction
     * @depends it_should_return_device_detection_three_d_object
     * @return void
     */
    public function device_detection_three_d_should_have_the_correct_type(DeviceDetectionThreeD $nextAction): void
    {
        $this->assertSame(DeviceDetectionThreeD::TYPE, $nextAction->toArray()['type']);
    }

    /**
     * @test
     * @param DeviceDetectionThreeD $nextAction NextAction
     * @depends it_should_return_device_detection_three_d_object
     * @return void
     */
    public function device_detection_three_d_object_should_have_three_d_data(DeviceDetectionThreeD $nextAction): void
    {
        $this->assertArrayHasKey('threeD', $nextAction->toArray());
    }

    /**
     * @test
     * @param DeviceDetectionThreeD $nextAction NextAction
     * @depends it_should_return_device_detection_three_d_object
     * @return void
     */
    public function device_detection_three_d_should_have_same_device_collection_url(DeviceDetectionThreeD $nextAction): void
    {
        $this->assertSame($this->deviceCollectionUrl, $nextAction->toArray()['threeD']['deviceCollectionUrl']);
    }

    /**
     * @test
     * @param DeviceDetectionThreeD $nextAction NextAction
     * @depends it_should_return_device_detection_three_d_object
     * @return void
     */
    public function device_detection_three_d_should_have_same_device_collection_jwt(DeviceDetectionThreeD $nextAction): void
    {
        $this->assertSame($this->deviceCollectionJwt, $nextAction->toArray()['threeD']['deviceCollectionJWT']);
    }

    /**
     * @test
     * @return void
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     * @throws Exception
     */
    public function it_should_return_finish_process_object(): void
    {
        $state = Processed::create();

        $nextAction = NextActionProcessFactory::create(
            $state,
            $this->url
        );

        $this->assertInstanceOf(FinishProcess::class, $nextAction);
    }

    /**
     * @test
     * @return void
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     * @throws Exception
     */
    public function it_should_throw_exception_when_invalid_state_provided(): void
    {
        $this->expectException(InvalidStateException::class);

        $state = Created::create();

        NextActionProcessFactory::create(
            $state,
            $this->url
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidStateException
     * @throws Exception
     * @throws IllegalStateTransitionException
     */
    public function it_should_return_render_gateway_next_action(): void
    {
        $state      = Valid::create();
        $nextAction = NextActionProcessFactory::create($state);

        $this->assertInstanceOf(RenderGateway::class, $nextAction);
    }

    /**
     * @test
     * @return void
     * @throws InvalidStateException
     * @throws Exception
     * @throws IllegalStateTransitionException
     */
    public function it_should_restart_process_next_action(): void
    {
        $state      = Valid::create();
        $thirdParty = ThirdParty::create($this->thirdPartyRedirectUrl);
        $nextAction = NextActionProcessFactory::create($state, null, $thirdParty, false);

        $this->assertInstanceOf(RestartProcess::class, $nextAction);
    }

    /**
     * @test
     * @return void
     * @throws InvalidStateException
     * @throws Exception
     * @throws IllegalStateTransitionException
     */
    public function it_should_redirect_to_url_next_action(): void
    {
        $state      = Valid::create();
        $thirdParty = ThirdParty::create($this->thirdPartyRedirectUrl);
        $nextAction = NextActionProcessFactory::create($state, null, $thirdParty, true);

        $this->assertInstanceOf(RedirectToUrl::class, $nextAction);
    }

    /**
     * @test
     * @return void
     * @throws InvalidStateException
     * @throws Exception
     * @throws IllegalStateTransitionException
     */
    public function it_should_return_wait_for_return_next_action(): void
    {
        $state      = Redirected::create();
        $nextAction = NextActionProcessFactory::create($state);

        $this->assertInstanceOf(WaitForReturn::class, $nextAction);
    }
}

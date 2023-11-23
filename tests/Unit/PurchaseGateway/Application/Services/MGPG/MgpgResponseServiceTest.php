<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\MGPG;

use ProbillerMGPG\Purchase\Common\NextAction;
use ProbillerMGPG\Purchase\Common\ReasonDetails;
use ProbillerMGPG\Purchase\Common\ThirdParty;
use ProbillerMGPG\Purchase\Complete3ds\ThreeD;
use ProbillerMGPG\Purchase\Lookup3ds\ThreeDS2;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\MgpgResponseService;
use Tests\UnitTestCase;

class MgpgResponseServiceTest extends UnitTestCase
{
    private $nextAction;

    private $service;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->nextAction = $this->getMockBuilder(NextAction::class)->disableOriginalConstructor()->getMock();
        $this->service    = new MgpgResponseService();
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_when_reason_is_cascade_billers_exhausted(): void
    {
        $this->nextAction->reason = 'cascadeBillersExhausted';
        $this->assertTrue($this->service->cascadeBillersExhausted($this->nextAction));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_when_type_is_device_detection(): void
    {
        $this->nextAction->type   = 'deviceDetection3D';
        $this->nextAction->threeD = new ThreeD();
        $this->assertTrue($this->service->isDeviceDetection($this->nextAction));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_when_type_is_authenticate3DS2(): void
    {
        $this->nextAction->type     = 'authenticate3DS2';
        $this->nextAction->threeDS2 = new ThreeDS2();
        $this->assertTrue($this->service->isAuth3Dv2($this->nextAction));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_when_type_is_redirect_to_url(): void
    {
        $this->nextAction->type            = 'redirectToUrl';
        $this->nextAction->thirdParty      = new ThirdParty();
        $this->nextAction->thirdParty->url = 'http://some.url';
        $this->assertTrue($this->service->isRedirectUrl($this->nextAction));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_when_type_is_render_gateway(): void
    {
        $this->nextAction->type = 'renderGateway';
        $this->assertTrue($this->service->isRenderGateway($this->nextAction));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_when_reason_is_blocked_due_fraud_advice(): void
    {
        $this->nextAction->reason = 'BlockedDueToFraudAdvice';
        $this->assertTrue($this->service->blockedDueToFraudAdvice($this->nextAction));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_when_type_is_validate_captha(): void
    {
        $this->nextAction->type = 'validateCaptcha';
        $this->assertTrue($this->service->hasCaptcha($this->nextAction));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_when_type_is_blacklisted(): void
    {
        $this->nextAction->type                   = 'finishProcess';
        $this->nextAction->reasonDetails          = new ReasonDetails();
        $this->nextAction->reasonDetails->message = 'Blacklist';
        $this->assertTrue($this->service->isBlacklisted($this->nextAction));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_true_when_type_is_auth3d(): void
    {
        $this->nextAction->type   = 'authenticate3D';
        $this->nextAction->threeD = new ThreeD();
        $this->assertTrue($this->service->isAuth3D($this->nextAction));
    }
}

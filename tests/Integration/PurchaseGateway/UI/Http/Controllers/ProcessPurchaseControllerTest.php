<?php

namespace Tests\Integration\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProBillerNG\Base\Application\Services\TransactionalSession;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\PurchaseProcessCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\ProcessPurchaseController;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\ProcessPurchaseRequest;
use Tests\IntegrationTestCase;

/**
 * Class ProcessPurchaseControllerTest
 * @package Tests\Integration\PurchaseGateway\UI\Http\Controllers
 */
class ProcessPurchaseControllerTest extends IntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|PurchaseProcessCommandHandlerFactory */
    protected $purchaseProcessHandlerFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TransactionalSession */
    protected $session;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessPurchaseRequest */
    protected $purchaseRequest;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Request */
    protected $request;

    /** @var ProcessPurchaseController */
    protected $processPurchaseController;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcessHandlerFactory = $this->createMock(PurchaseProcessCommandHandlerFactory::class);
        $this->session = $this->createMock(TransactionalSession::class);
        $this->purchaseRequest = $this->createMock(ProcessPurchaseRequest::class);

        $this->processPurchaseController = new ProcessPurchaseController(
            $this->purchaseProcessHandlerFactory,
            $this->session,
            $this->purchaseRequest
        );
    }

    /**
     * @test
     */
    public function is_should_not_fail_and_continue_purchase_process_if_user_agent_is_missing()
    {
        // fake method $request->bearerToken()
        $this->purchaseRequest->expects($this->any())
            ->method('bearerToken')
            ->willReturn('someBearerToken');

        // fake method $request->decodedToken()->getSessionId()
        $jsonWebToken = $this->createMock(JsonWebToken::class);
        $jsonWebToken->method('getSessionId')->willReturn($this->faker->uuid);

        $this->purchaseRequest->expects($this->any())
            ->method('decodedToken')
            ->willReturn($jsonWebToken);

        // fake method $request->getRequestUri()
        $this->purchaseRequest->expects($this->any())
            ->method('getRequestUri')
            ->willReturn('/api/v1/someUri');

        // fake method $request->userAgent()
        $this->purchaseRequest->expects($this->any())
            ->method('userAgent')
            ->willReturn(null);

        $response = $this->processPurchaseController->post($this->purchaseRequest);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}

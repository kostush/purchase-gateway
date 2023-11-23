<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\ControlKeyword;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ControlKeyWordNotFoundException;
use Tests\UnitTestCase;

/**
 * Class ControlKeywordTest
 * @package Tests\Unit\PurchaseGateway\Domain\Model
 * @deprecated
 */
class ControlKeywordTest extends UnitTestCase
{
    /**
     * @var string
     */
    private $accountId;

    /**
     * @var string
     */
    private $invalidAccountId;

    /**
     * Init
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->accountId        = $_ENV['NETBILLING_ACCOUNT_ID'];
        $this->invalidAccountId = '1111111111111';
    }

    /**
     * @test
     * @return void
     * @throws ControlKeyWordNotFoundException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_control_keyword_for_configured_account_id(): void
    {
        $controlKeyword = ControlKeyword::get($this->accountId);
        $this->assertNotEmpty($controlKeyword);
    }

    /**
     * @test
     * @return void
     * @throws ControlKeyWordNotFoundException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_an_exception_when_invalid_acccount_id_is_provided(): void
    {
        $this->expectException(ControlKeyWordNotFoundException::class);
        ControlKeyword::get($this->invalidAccountId);
    }
}

<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\ExistingCardPerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use ProbillerNG\TransactionServiceClient\Model\NetbillingExistingCardSaleRequestBody;
use Tests\UnitTestCase;

class ExistingCardPerformTransactionAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_a_retrieve_transaction_result_when_valid_data_provided()
    {
        $processTransactionWithBillerAdapter = new ExistingCardPerformTransactionAdapter(
            $this->createMock(TransactionServiceClient::class),
            $this->createMock(TransactionTranslator::class)
        );

        $this->assertInstanceOf(ExistingCardPerformTransactionAdapter::class, $processTransactionWithBillerAdapter);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws UnknownBillerNameException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoPassword
     * @throws InvalidUserInfoUsername
     * @throws \Throwable
     */
    public function netbilling_request_should_have_member_information(): void
    {
        $processTransactionWithBillerAdapter = new ExistingCardPerformTransactionAdapter(
            $this->createMock(TransactionServiceClient::class),
            $this->createMock(TransactionTranslator::class)
        );

        $password = 'password';
        $useName  = 'username';

        $userInfo = UserInfo::create(CountryCode::create('US'), Ip::create('10.10.10.10'));
        $userInfo->setPassword(Password::create($password));
        $userInfo->setUsername(Username::create($useName));

        $billerFields = NetbillingBillerFields::create(
            'accountId',
            'siteTag',
            null,
            'merchantPassword'
        );

        $billeMapping = $this->createMock(BillerMapping::class);
        $billeMapping->method('billerFields')
            ->willReturn($billerFields);

        $result = $processTransactionWithBillerAdapter->getNetbillingSaleRequest(
            SiteId::create(),
            new NetbillingBiller(),
            CurrencyCode::USD(),
            $userInfo,
            BundleSingleChargeInformation::create(Amount::create(10), Duration::create(10)),
            ExistingCCPaymentInfo::create('cardhash', 'templateId', null, []),
            $billeMapping,
            $this->createMock(BinRouting::class)
        );

        $this->assertInstanceOf(NetbillingExistingCardSaleRequestBody::class, $result);
        $this->assertEquals($result->getMember()->getPassword(), $password);
        $this->assertEquals($result->getMember()->getUserName(), $useName);
    }
}

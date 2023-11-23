<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services\EmailTemplateService;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\EmailTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\Templates\EmailTemplateGenericProbiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;
use Tests\UnitTestCase;

class EmailTemplateServiceTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_generic_probiller_email_template(): void
    {
        /** @var MockObject|Site $site */
        $site = $this->createMock(Site::class);

        /** @var MockObject|PurchaseProcessed $purchaseProcessedMock */
        $purchaseProcessedMock = $this->createMock(PurchaseProcessed::class);

        $transactionInformationMock = $this->createMock(TransactionInformation::class);
        $transactionInformationMock->method('amount')->willReturn((float) 123);

        /** @var MockObject|RetrieveTransactionResult $retrieveTransactionResultMock */
        $retrieveTransactionResultMock = $this->createMock(RetrieveTransactionResult::class);
        $retrieveTransactionResultMock->method('transactionInformation')
            ->willReturn($transactionInformationMock);

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            'REAL_NAME1',
            'REAL_NAME2'
        );

        $this->assertInstanceOf(
            EmailTemplateGenericProbiller::class,
            (new EmailTemplateService())->getTemplate(
                $site,
                $purchaseProcessedMock,
                $retrieveTransactionResultMock,
                $memberInfo,
                null,
                null,
                null
            )
        );
    }
}
<?php
declare(strict_types=1);

namespace Tests\Integration\Mgpg\ProcessPurchase;

use ProbillerMGPG\Common\Mappings\ClientMapper;
use ProbillerMGPG\Common\PaymentMethod;
use ProbillerMGPG\Common\PaymentType;
use ProbillerMGPG\Purchase\Common\NextAction;
use ProbillerMGPG\Purchase\Process\PurchaseProcessResponse;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\HttpCommandDTOAssembler as PurchaseProcessAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\CompletedProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\ProcessPurchaseMgpgToNgService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Code;
use Tests\IntegrationTestCase;

class PurchaseProcessTest extends IntegrationTestCase
{
    protected const MGPG_TRANSACTION_STATUS_SUCCESS = "success";
    protected const MGPG_TRANSACTION_STATUS_ABORTED = "aborted";

    /**
     * @test
     */
    public function it_should_return_success_false_on_failed_transaction()
    {
        $mgpgPurchaseProcess = (ClientMapper::build())->map(
            [
                'nextAction' => new NextAction(),
                'invoice'    => $this->mockMgpgInvoice(self::MGPG_TRANSACTION_STATUS_ABORTED)["invoice"],
                'digest'     => $this->mockMgpgDigest(),
            ],
            PurchaseProcessResponse::class
        );

        $uuid = "8e6eadf0-5a01-485b-b197-827b4249b755";

        $command         = new CompletedProcessPurchaseCommand($uuid, $uuid, 0);
        $purchaseProcess = new PurchaseProcess($mgpgPurchaseProcess, $command);
        $dtoAssembler    = new PurchaseProcessAssembler(
            app(TokenGenerator::class),
            app(ConfigService::class)->getSite(
                $mgpgPurchaseProcess->invoice->charges[0]->siteId
            ),
            app(CryptService::class)
        );

        $mgpgToNgService = app(ProcessPurchaseMgpgToNgService::class);

        $dto = $mgpgToNgService->translate($purchaseProcess, $dtoAssembler);

        $result = $mgpgToNgService->buildPostbackDto($purchaseProcess, $dto)->jsonSerialize();

        $this->assertFalse($result['success']);
    }

    /**
     * @test
     */
    public function it_should_return_success_true_on_approved_transactions()
    {
        $mgpgPurchaseProcess = (ClientMapper::build())->map(
            [
                'nextAction' => new NextAction(),
                'invoice'    => $this->mockMgpgInvoice()["invoice"],
                'digest'     => $this->mockMgpgDigest(),
            ],
            PurchaseProcessResponse::class
        );

        $uuid = "8e6eadf0-5a01-485b-b197-827b4249b755";

        $command         = new CompletedProcessPurchaseCommand($uuid, $uuid, 0);
        $purchaseProcess = new PurchaseProcess($mgpgPurchaseProcess, $command);
        $dtoAssembler    = new PurchaseProcessAssembler(
            app(TokenGenerator::class),
            app(ConfigService::class)->getSite(
                $mgpgPurchaseProcess->invoice->charges[0]->siteId
            ),
            app(CryptService::class)
        );

        $mgpgToNgService = app(ProcessPurchaseMgpgToNgService::class);

        $dto = $mgpgToNgService->translate($purchaseProcess, $dtoAssembler);

        $result = $mgpgToNgService->buildPostbackDto($purchaseProcess, $dto)->jsonSerialize();

        $this->assertTrue($result['success']);
    }

    /**
     * @test
     */
    public function it_should_return_success_true_on_approved_crypto_transactions()
    {
        $invoice = $this->mockMgpgInvoice();
        $invoice["invoice"]["paymentInfo"] = [
            "currency"      => "USD",
            "paymentType"   => PaymentType::CRYPTOCURRENCY,
            "paymentMethod" => PaymentMethod::CRYPTOCURRENCY
        ];

        $mgpgPurchaseProcess = (ClientMapper::build())->map(
            [
                'nextAction' => new NextAction(),
                'invoice'    => $invoice["invoice"],
                'digest'     => $this->mockMgpgDigest(),
            ],
            PurchaseProcessResponse::class
        );

        $uuid = "8e6eadf0-5a01-485b-b197-827b4249b755";

        $command         = new CompletedProcessPurchaseCommand($uuid, $uuid, 0);
        $purchaseProcess = new PurchaseProcess($mgpgPurchaseProcess, $command);
        $dtoAssembler    = new PurchaseProcessAssembler(
            app(TokenGenerator::class),
            app(ConfigService::class)->getSite(
                $mgpgPurchaseProcess->invoice->charges[0]->siteId
            ),
            app(CryptService::class)
        );

        $mgpgToNgService = app(ProcessPurchaseMgpgToNgService::class);

        $dto = $mgpgToNgService->translate($purchaseProcess, $dtoAssembler);

        $result = $mgpgToNgService->buildPostbackDto($purchaseProcess, $dto)->jsonSerialize();

        $this->assertTrue($result['success']);
    }

    /**
     * @test
     */
    public function it_should_return_is_nsf_true_if_status_declined_and_nsf_error_classification(): void
    {
        $invoice = $this->mockMgpgInvoice();
        $invoice["invoice"]["paymentInfo"] = [
            "currency"      => "USD",
            "paymentType"   => PaymentType::CRYPTOCURRENCY,
            "paymentMethod" => PaymentMethod::CRYPTOCURRENCY
        ];
        $invoice['invoice']['charges'][0]['status'] = 'decline';
        $invoice['invoice']['charges'][0]['errorClassification']['groupDecline'] = "105";
        $invoice['invoice']['charges'][0]['errorClassification']['errorType'] = Code::ERROR_CLASSIFICATION_NSF;
        $invoice['invoice']['charges'][0]['errorClassification']['groupMessage'] = "Insufficient Funds";
        $invoice['invoice']['charges'][0]['errorClassification']['recommendedAction'] = "Try lower amount/check account balance/advise customer of NSF";

        $mgpgPurchaseProcess = (ClientMapper::build())->map(
            [
                'nextAction' => new NextAction(),
                'invoice'    => $invoice["invoice"],
                'digest'     => $this->mockMgpgDigest(),
            ],
            PurchaseProcessResponse::class
        );

        $uuid = "8e6eadf0-5a01-485b-b197-827b4249b755";

        $command         = new CompletedProcessPurchaseCommand($uuid, $uuid, 0);
        $purchaseProcess = new PurchaseProcess($mgpgPurchaseProcess, $command);
        $dtoAssembler    = new PurchaseProcessAssembler(
            app(TokenGenerator::class),
            app(ConfigService::class)->getSite(
                $mgpgPurchaseProcess->invoice->charges[0]->siteId
            ),
            app(CryptService::class)
        );

        $mgpgToNgService = app(ProcessPurchaseMgpgToNgService::class);

        $dto = $mgpgToNgService->translate($purchaseProcess, $dtoAssembler);

        $result = $mgpgToNgService->buildPostbackDto($purchaseProcess, $dto)->jsonSerialize();

        $this->assertTrue($result['isNsf']);
        $this->assertFalse($result['success']);
    }

    /**
     * @return array[]
     */
    protected function mockMgpgInvoice($status = self::MGPG_TRANSACTION_STATUS_SUCCESS): array
    {
        return [
            "invoice" => [
                "invoiceId"          => "4f306a15-ba1c-45c5-a27a-fc00e448563f",
                "memberId"           => "65088f6c-4535-451f-8739-d18a460f3d4f",
                "usingMemberProfile" => true,
                "clientIp"           => "24.135.17.173",
                "redirectUrl"        => "https=>\\/\\/purchase-gateway.probiller.com\\/mgpg\\/api\\/v1\\/return\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2MzY2Mjc2ODUsIm5iZiI6MTYzNjYyNzY4NSwiZXhwIjoxNjM2NjI5NDg1LCJjbGllbnRVcmwiOiIySVR0Y1g5ZU0wK0xVVk80aHlzM2RRSk16RWlpcmt5RThMN1ljU3Z1K1FUNStocXAyeWZlNlwvMWVqS25lS3R4XC9iajFEcWYzbWlyKzUrbFpJRGFBTDVoMmtNZE9jVEVnY2hHcWwxS0E1aXdRa3RTRWciLCJzZXNzaW9uSWQiOiJhOFI4QjJaTmFhMFVlSGlNSm1kM3MzcWY1aHV3QkJaUTlMVlpWajV6djRcL1VjRmRvUEZaS0N5NUN5UkZuRW9adDdsUllwZDh2WmxWQ1U1cVBQeCs3RE5hTGJnYnpLZjZpaUZZXC9DQT09IiwicHVibGljS2V5SWQiOiJudEYzRXVvM0Vpd3V1NW9VZ3hDMk5pd0tLWGMwRlVPRUdWYTNYcWdLamx5XC9mbU5sZkkwY3paQT0iLCJjb3JyZWxhdGlvbklkIjoiZloxd2grclBpRlpuU2x5THp4dmZjV010MGRVQk91cFkwd1htYkprVG9MVjNvQ2thbXJ6UFlacndZRjBOSEVzcndtOHhUYmpsakF1ZG1rTlROQUxJMVU0ZmJiQzJmaW1vRExxUW1nPT0ifQ.cE0XDARMaG3UoR8zmdMSaKuXwioKo-QVIRBJXa1YYPSWM4LnSyd_wAuLn_oiOQANb-hXKodwAF9txEsQlxmA0w",
                "postbackUrl"        => "https=>\\/\\/purchase-gateway.probiller.com\\/mgpg\\/api\\/v1\\/postback\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2MzY2Mjc2ODUsIm5iZiI6MTYzNjYyNzY4NSwiZXhwIjoxNjM2NjI5NDg1LCJjbGllbnRVcmwiOiJ3RUJwYzBKOFFFaDV0eGl3dEpcL3dYcndOeENXdld2N080TUNoT0FleU1meGx3S3hwZm1LbGdCdWdlRUV5dlpUaHRheFBlWXQ3MGNlajVzaUFLSW9oVXBrNlRcLzhlZkF3RTZudTlCXC83Q1pWOHpVd0pxMVlGNkVyTGF2Y2FldVpEeU5aTE84RE9vRkttakMwUU9ZcDVMeWdlUEFtZks2OUM1UUV2RWRGRFJhYm8rZ2FXNjgzRGJFaENadWhXaUNIXC9BVlwvNllPRk9ab1cwZEpuQ1wvRGJjMjFLaEpcL0hwb0E5TWxRUnRqVnorZDRxU0JFQ2pJNE5KeWFrV2VqSnQyNFNHbHpua2xibXhrZ2srUCIsInNlc3Npb25JZCI6Ik1WbjB5a1RYMmZJWWhOT0JsakplZ3NlaGdnZFVmRzV1aUJzMjZsYWhIMUVpSGRlcksxUWEzNjQrelhVSFlPcmg4Rk5obXhLaFkySWdLUEgxUDZteHBVUWNnd1ZZdHRhN2FzOHZJQT09IiwicHVibGljS2V5SWQiOiJcL2g4d3ErXC80Zmc4TFI5WTFiZnBGY0QzV1d6T1dNVzdqOHViRFFtOVdPWmNTdjUrNXJtK3RHdDA9IiwiY29ycmVsYXRpb25JZCI6IncwZ3dXU1B6c3g2XC9vMTlGdFhqTzc4YTczeExxUE1wNDF2dzdVc2Vva2hnYW5kcmx6U01kN1wvQW5jdEhOXC9jYmhMYmtQRUUram5xZWs3dkF4SlBDdGdMSWRIYjNJcjBmWVQwd3MxUT09In0.HlDwf52vsJXg5VPuEEMVRlUYMu01is1racFo4BoTZlt2cRj9ygBZSXAl_JBFV59oQqa2Ps9zvOXqG_rqjaTYUQ",
                "paymentInfo"        => [
                    "currency"      => "USD",
                    "paymentType"   => "cc",
                    "paymentMethod" => "cc"
                ],
                "charges"            => [
                    [
                        "businessTransactionOperation" => "singleChargePurchase",
                        "chargeId"                     => "660f5d8a-fa35-4a3b-85e2-48a57fa8b8ab",
                        "siteId"                       => "b8e9f9d4-bd17-47e3-ac9c-04261a0c1904",
                        "chargeDescription"            => "Brazzersplus",
                        "items"                        => [
                            [
                                "businessRevenueStream" => "Initial Sale",
                                "skuId"                 => "0e1b89ab-f25d-41be-9779-f67c49e192f0",
                                "displayName"           => "Brazzersplus",
                                "itemDescription"       => "Brazzersplus",
                                "quantity"              => 1,
                                "priceInfo"             => [
                                    "basePrice"     => 0.1,
                                    "expiresInDays" => 0,
                                    "finalPrice"    => 0.1
                                ],
                                "entitlements"          => [
                                    [
                                        "memberProfile" => [
                                            "data" => [
                                                "siteId"         => "b8e9f9d4-bd17-47e3-ac9c-04261a0c1904",
                                                "bundleId"       => "0e1b89ab-f25d-41be-9779-f67c49e192f0",
                                                "addonId"        => "45c2bb07-2947-4011-bcda-cc1570d3aa73",
                                                "subscriptionId" => "1d4cf5b7-1d00-4522-a46c-13fbb8e6cd2f",
                                                "memberId"       => "65088f6c-4535-451f-8739-d18a460f3d4f"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "isPrimaryCharge"              => true,
                        "isTrial"                      => false,
                        "status"                       => $status,
                        "transactionId"                => "9cc57ce9-4b05-462b-a32c-33c9ed5492a7"
                    ]
                ]
            ]
        ];
    }

    /**
     * @return string
     */
    protected function mockMgpgDigest(): string
    {
        return "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJEaWdlc3QiOiI1NGRmOWZkYWMzNGYxNGMxYWZjOWVhNjY5Zjk3NTYyZmExODk2ZmUxNDZiOTQxNzUwOTQyMGI3ZDM3OWVkYWYyZGJkMTFmZDg3ZTQwN2FjYWVhMWFlNTc2NjIzYmQ5ZTJhZTZjMDBiNWEwYTVlNzgwYjQ1MDM0ZDc3ZmU5NjY5YiIsImlzcyI6InByb2JpbGxlci5jb20iLCJhdWQiOiJiOGU5ZjlkNC1iZDE3LTQ3ZTMtYWM5Yy0wNDI2MWEwYzE5MDQifQ.lDk_vAXoY3hlsDrWozZq5NvdAYlEulzSsztfKcm25u";
    }
}

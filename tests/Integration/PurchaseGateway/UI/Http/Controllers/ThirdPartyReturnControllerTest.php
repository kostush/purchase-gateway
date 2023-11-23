<?php

namespace Tests\Integration\PurchaseGateway\UI\Http\Controllers;

use ProbillerMGPG\Common\Mappings\ClientMapper;
use ProbillerMGPG\Purchase\Process\PurchaseProcessResponse;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\HttpCommandDTOAssembler as PurchaseProcessAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\CompletedProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\ProcessPurchaseMgpgToNgService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg\ThirdPartyReturnController;
use Tests\IntegrationTestCase;

/**
 * Class ProcessPurchaseControllerTest
 * @package Tests\Integration\PurchaseGateway\UI\Http\Controllers
 */
class ThirdPartyReturnControllerTest extends IntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ThirdPartyReturnController */
    protected $thirdPartyReturnController;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenGenerator  */
    protected $tokenGenerator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CryptService  */
    protected $cryptService;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessPurchaseMgpgToNgService  */
    protected $mgpgToNgService;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenGenerator = new JsonWebTokenGenerator();

        $this->cryptService    = $this->createMock(CryptService::class);
        $this->mgpgToNgService = new ProcessPurchaseMgpgToNgService($this->createMock(PostbackService::class));

        $this->thirdPartyReturnController = new ThirdPartyReturnController(
            $this->tokenGenerator,
            $this->cryptService,
            $this->mgpgToNgService
        );
    }

    /**
     * @test
     * @dataProvider chargesScenariosForPaygarden
     */
    public function is_should_return_success_when_giftcards_paygarden($ngSessionId, $corrolationId, $mgpgResponsePayload, $successfullTransactionId)
    {
        $mgpgPurchaseProcess = (ClientMapper::build())->map(
            $mgpgResponsePayload,
            PurchaseProcessResponse::class
        );

        $command         = new CompletedProcessPurchaseCommand(
            $ngSessionId,
            $corrolationId,
            0
        );
        $purchaseProcess = new PurchaseProcess($mgpgPurchaseProcess, $command);
        $dtoAssembler    = new PurchaseProcessAssembler(
            $this->tokenGenerator,
            app(ConfigService::class)->getSite(
                $mgpgPurchaseProcess->invoice->charges[0]->siteId
            ),
            $this->cryptService
        );

        $dto = $this->mgpgToNgService->translate($purchaseProcess, $dtoAssembler);

        $this->assertTrue($dto->jsonSerialize()['success']);
        $this->assertSame($successfullTransactionId,$dto->jsonSerialize()["transactionId"]);
    }

    /**
     * @return array[]
     */
    public function chargesScenariosForPaygarden(): array
    {
        return [
            //singlecharge
            [
                "34f7f692-ae59-4b79-a0b0-d9bba9472b51",
                "adf85dc2-3fa3-49b7-a21c-acb03d9d1d2f",
                $this->getDecodedReturnPayloadSingleCharge(),
                'f0c7129c-0d90-43e9-8398-d77a45a7ce13'
            ],
            //multiples charges
            [
                "cb82b5de-b3e0-4883-a34d-61d415339bcd",
                "45d53c45-4b3f-4eeb-86b2-e61f10cab5e9",
                $this->getDecodedReturnPayloadMultipleCharge(),
                '505c7b6f-cdec-4466-8202-e1a4769e33d0'
            ],
        ];
    }


    /**
     * @test
     * @dataProvider failedChargesScenariosForPaygarden
     */
    public function is_should_return_failure_when_giftcards_paygarden_missing($ngSessionId, $corrolationId, $mgpgResponsePayload, $successfullTransactionId)
    {
        $this->expectException(UnsupportedPaymentTypeException::class);
        $mgpgPurchaseProcess = (ClientMapper::build())->map(
            $mgpgResponsePayload,
            PurchaseProcessResponse::class
        );

        $command         = new CompletedProcessPurchaseCommand(
            $ngSessionId,
            $corrolationId,
            0
        );
        $purchaseProcess = new PurchaseProcess($mgpgPurchaseProcess, $command);
        $dtoAssembler    = new PurchaseProcessAssembler(
            $this->tokenGenerator,
            app(ConfigService::class)->getSite(
                $mgpgPurchaseProcess->invoice->charges[0]->siteId
            ),
            $this->cryptService
        );

        $this->mgpgToNgService->translate($purchaseProcess, $dtoAssembler);
    }


    /**
     * @return array[]
     */
    public function failedChargesScenariosForPaygarden(): array
    {
        $singleChargePayload    = $this->getDecodedReturnPayloadSingleCharge();
        $singleChargePayload['invoice']["paymentInfo"]['paymentType'] = '';

        $multipleChargePayload  = $this->getDecodedReturnPayloadMultipleCharge()    ;
        $multipleChargePayload['invoice']["paymentInfo"]['paymentType'] = '';

        return [
            //singlecharge wrong type
            [
                "34f7f692-ae59-4b79-a0b0-d9bba9472b51",
                "adf85dc2-3fa3-49b7-a21c-acb03d9d1d2f",
                $singleChargePayload,
                'f0c7129c-0d90-43e9-8398-d77a45a7ce13'
            ],
            //multiples charges wrong type
            [
                "cb82b5de-b3e0-4883-a34d-61d415339bcd",
                "45d53c45-4b3f-4eeb-86b2-e61f10cab5e9",
                $multipleChargePayload,
                '505c7b6f-cdec-4466-8202-e1a4769e33d0'
            ],
        ];
    }



    /**
     * @return array
     */
    protected function getDecodedReturnPayloadSingleCharge(): array
    {
        return json_decode('
        {
        "nextAction": {
            "type": "finishProcess",
            "resolution": "server"
        },
        "invoice": {
            "invoiceId": "82f60f70-508d-464b-a68e-91ddd3cf94d7",
            "memberId": "1804652d-361f-4a0f-ab5e-c47602f2128e",
            "usingMemberProfile": true,
            "clientIp": "101.1.132.106",
            "redirectUrl": "http://localhost:8008/mgpg/api/v1/return/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2Mzk5Njg3MDQsIm5iZiI6MTYzOTk2ODcwNCwiZXhwIjoxNjM5OTcwNTA0LCJjbGllbnRVcmwiOiJoeHpxV2JtdGs2eURwMWRJcDNsUFwvYzFYK1BOYklnTGZyaG5rR1RNZjJBRDFNdCtGOFJ0a2liVGJtSHYxeU9PR2xkRklpTXZcL2g3OXl6WmJ4cWlcL29RU0xGcjdqUUg1bVhxbTN3WjhWTkJoend5V2UyTStLSHNpd3E3Q3hZRWlZQm1LeWtqNDAwVTdpNXdpeEsxWVJjeXQwPSIsInNlc3Npb25JZCI6ImNGanBYVHduY0NyRWtSa1RJNzdaVjlDUXJZdFJVc2VKRUdma2MyeG5LNno2Mm5rOHBOTmtZT0kzbHg3eDRvYlJKN2MzQnF1S2xwUHFvZDU0ZVZzdW5mTWpkS1dYSU16eWpycEpQZz09IiwicHVibGljS2V5SWQiOiJXM3NTc1VVUTZkM052bkRwZmJwdGJSWThmUE56VGdlajl3aE94VjNFd0hRdnZ1R2FKTGQybnN3PSIsImNvcnJlbGF0aW9uSWQiOiJ6bUg2TUJaNFQwQm9SbGNORVkwNlJMeXdkdUtGbmNUV2x0MGpLOVhEUlNWOHRaSXJPQ0hLbTY1VEZcL1NKelRlaGt2ZDdyTlZEVnMrbGd0Z3FJNTZWSTgyZDRcL3M2R29TZlRIek56UT09In0.wi6A9yM3zmayM0Pr7Hdtj1xiwTzF4t7-8FXW3_9cHpP-wOhbgnnMa7pSMHhGe1_aEufYtp5_Eyw5fdAytiyjIg",
            "postbackUrl": "http://localhost:8008/mgpg/api/v1/postback/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2Mzk5Njg3MDUsIm5iZiI6MTYzOTk2ODcwNSwiZXhwIjoxNjM5OTcwNTA1LCJjbGllbnRVcmwiOiIyWnlGQzBBd1doeUZSenJ5elUyMDhcL3REckxybXhyUnh1eEgzb2VlaXpjMzBoUzU3a3U3aU1ndXNwdlFDQ1BsSDM2MkdPTmZ4Yjh5djRqeFwvb28xODJPUTNXTTZ1Yk9KdlhFRDZIWlwvelc1UnczTHducitTZDlvWExXaGw4RWVoXC92d3VRcnB6RVZIOFFhY3lqbHA2MDR5az0iLCJzZXNzaW9uSWQiOiJoZmRLXC8yUHNhcUZqNXRWR21aYmtxaFVUZVJuZXQ5aktDUlowM3N5QlFRQkFmTzAwVUpLQWNUamFjb1JhK0R2M3hCWFF1UDROZ01aa000c28wWWNEaDVTTHFMVTFPM3NVWDlWXC9Vdz09IiwicHVibGljS2V5SWQiOiJoSzFIR2lIK3NNUEZkaGdrZytpbVA2QUJcL2xcL2lZZ3RsYXAzS3pLbkE5T3MxckJxYlg1bXVRVUk9IiwiY29ycmVsYXRpb25JZCI6ImU1VGtVZTFLTXhIQnVybnVZYTdoS0E0KzlHa2VURkZtYmJ5UjQ1clFJc0V4K0czUWJsbzdFcDdqN2J4TjZFd1JlSmNzR3BBQ2FxMjk2K0U0UU1vWDhmZmRaemZVQ2ZHcWFhdFJpUT09In0.YaR-n4b1OSYB59Gu-pYXBuvgButcU5pfEHuKgVYbZl09236-1mAv3vCW5cL_un4afCQPa4qSf8aUxwnwyC09dg",
            "paymentInfo": {
                "currency": "USD",
                "paymentType": "giftcards",
                "paymentMethod": "giftcards"
            },
            "charges": [
                {
                    "businessTransactionOperation": "singleChargePurchase",
                    "chargeId": "d6887b93-e9a1-451d-a81b-cd1ada683b4a",
                    "siteId": "a2d4f06f-afc8-41c9-9910-0302bd2d9531",
                    "chargeDescription": "PB Engineering Testing Site",
                    "items": [
                        {
                            "businessRevenueStream": "Initial Sale",
                            "skuId": "632f967d-8009-4bc9-949d-0c26feb7c64b",
                            "displayName": "PB Engineering Testing Site",
                            "itemDescription": "PB Engineering Testing Site",
                            "quantity": 1,
                            "priceInfo": {
                                "basePrice": 6,
                                "expiresInDays": 0,
                                "taxes": 0,
                                "finalPrice": 6
                            },
                            "tax": {
                                "taxApplicationId": "60bf5bcb-ac64-496c-acc5-9c7cf54a1869",
                                "productClassification": "unknown",
                                "taxName": "VAT",
                                "taxRate": 0.05,
                                "taxType": "type1",
                                "displayChargedAmount": false
                            },
                            "entitlements": [
                                {
                                    "memberProfile": {
                                        "data": {
                                            "siteId": "a2d4f06f-afc8-41c9-9910-0302bd2d9531",
                                            "bundleId": "632f967d-8009-4bc9-949d-0c26feb7c64b",
                                            "addonId": "0c1e9aae-dc30-42cc-a714-bf9d53ed8e32",
                                            "subscriptionId": "872048cf-434c-4dc1-acb9-0e9d3d9e4f2f",
                                            "memberId": "1804652d-361f-4a0f-ab5e-c47602f2128e"
                                        }
                                    }
                                }
                            ],
                            "otherData": {
                                "paygarden": {
                                    "data": {   
                                        "credit": 31,
                                        "sku": "AAAA"
                                    }
                                }
                            }
                        }
                    ],
                    "isPrimaryCharge": true,
                    "isTrial": false,
                    "status": "success",
                    "transactionId": "f0c7129c-0d90-43e9-8398-d77a45a7ce13"
                }
            ]
        },
        "digest": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJEaWdlc3QiOiJlNjhmNzI3ZTg5NDA3YWYxMThjZDk1MDE5ZTAwNTY3NTRiNWM3ODNlNWM4YTM4MjdhZWEyODg4YTU1NDY2ZGJjNmYzYzdjMWZlMDQ2ZDhlNzdmMDg1MWIyMzZkZDVhY2MwMDRhOTM5NjZmNjQ1YjM0NjkwZDQxZTVkMTc4YmIxYSIsImlzcyI6InByb2JpbGxlci5jb20iLCJhdWQiOiJhMmQ0ZjA2Zi1hZmM4LTQxYzktOTkxMC0wMzAyYmQyZDk1MzEifQ.YvYDhrS4V3vFKkNgXhjqslsyUZxnXmvfZvjBtE-oFHc"
    }', true);
    }

    /**
     * @return array
     */
    function getDecodedReturnPayloadMultipleCharge() : array
    {
        return json_decode('{
	"nextAction": {
		"type": "finishProcess",
		"resolution": "server"
	},
	"invoice": {
		"invoiceId": "a40b752d-df78-4a91-a699-d98b19a382d4",
		"memberId": "4de4b1a6-f8c5-4c37-b07b-bdf089ebe0bd",
		"usingMemberProfile": true,
		"clientIp": "101.1.132.106",
		"redirectUrl": "http://localhost:8008/mgpg/api/v1/return/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2NDAwMjUwNDUsIm5iZiI6MTY0MDAyNTA0NSwiZXhwIjoxNjQwMDI2ODQ1LCJjbGllbnRVcmwiOiJKUEJTZTEzWTBQQTM1eXdSSUg3YWx0XC84UlVCQ2k3STNKY01nemd5UUNoSzZVc25qS3M5VTM1QkVaMGVKUFh0REN2Z1ltcXdHUlZrSzNkMGdFZklVRmp1U0NcLyszN29UemMxNjdFVmpNemNQenZVQ3d0Zm9Ma3R3PSIsInNlc3Npb25JZCI6ImxQYXY3dHUxaFRkNWxHSTBzOXZqM1hqMjlcL3I5Rm5YQ25HZnlZYVwvMnJoSWlaUmhiSkFHb1VXOUFNNnFiNkFwTU43Y2NWZ2pMaVU0T2o3MmUweGVtNGtlbGZCSTVIQnExUjZ6UVlBPT0iLCJwdWJsaWNLZXlJZCI6Im94U1hxd3NXMEUxanJOcUlZTDdscGRyb0NmVnFnUzFRR21Td1wvdlA5c2NHTjhVenJmXC9ZU1VIRT0iLCJjb3JyZWxhdGlvbklkIjoibXVwVmdBbXlXczlXeDNxRUoxV2hLVkdZcGRcL1FMa0NXUUcraWVsSjhpazJiRTVncmRGM2pZK3Zab0xqeTljRG1sME94RWE0RXdtSTBlTXZTSGdlYmg1b1JMRjl6b0J4MGo5Mm5Ndz09In0.trhKTq5xYFMBV9WZatzKlXcTGkwaBGoRmbt4oirkzrNZqexq0MdVp2BOOksno8bLTE0pCUbve5pAhu4Ah64iZA",
		"postbackUrl": "http://localhost:8008/mgpg/api/v1/postback/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2NDAwMjUwNDYsIm5iZiI6MTY0MDAyNTA0NiwiZXhwIjoxNjQwMDI2ODQ2LCJjbGllbnRVcmwiOiJ2OEdHR2xsSlJwTW9XUFl2eGpBbmJGSnNGZkNtWGpIY1wvNVhwUW92am9DbVd6cmF1Z3BsWWlaZGxLRWhQc1ZyVGRNMnNiNUsyOXhPZEZDK2VieXFKajhkNko0MklTS0tIaUtKdEtseFVSK1lsRGlUQ3owZTlTdlFhcUdcL05PNVExdEh5K2ZKR3I5ZGhYcENITStIMTd1MkU9Iiwic2Vzc2lvbklkIjoicVJXMUVMcXArS05HeXZ0Y3RPN1IzUUpZRzhvVzBLZWVzUUYwbkpIejl4V0J1dGx5bU9Kc29YUzdVOXlSWWdpa2FCME9JRm95RWJjTFhLU0ZXazdhbThFQmxHWlwvanIwMXJ6VEU5QT09IiwicHVibGljS2V5SWQiOiJNR0t0M0hKVEhzbk55Qmt0UklXOWN6RDlZTHJkcGZQSXBnUGhVNWIyaE5JVFZ5MERyR3dnXC9Vaz0iLCJjb3JyZWxhdGlvbklkIjoickFBdnFJKzd3UTIya01tNEtvUXB0TStRRVVhMERcLzdzemRJZTFjNnJJeXlkejlNeGdPQ3pcL0dMcFdDMkwrK0hrdUZ0YUIxMmRncUE1aXd0UlV0cmk4dXY1ZXByRVV5NlZaNTg3RXc9PSJ9.FQoHkiaDNnmHE6rTLoIhXDLrDh4rFGpPTrpk62p8uq3ABB3ToIIMaDKSjjLa7xROVwvatjyeqYAqritcR_o_Yg",
		"paymentInfo": {
			"currency": "USD",
			"paymentType": "giftcards",
			"paymentMethod": "giftcards"
		},
		"charges": [
			{
				"businessTransactionOperation": "nonRecurringMembershipCharge",
				"chargeId": "49fcae81-1b45-4056-95c9-dcbb42c79d29",
				"siteId": "a2d4f06f-afc8-41c9-9910-0302bd2d9531",
				"chargeDescription": "PB Engineering TestingSite",
				"items": [
					{
						"businessRevenueStream": "Initial Sale",
						"skuId": "2c7a627e-3773-4213-98e0-469d2d52ca93",
						"displayName": "PB Engineering Testing Site",
						"itemDescription": "PB Engineering Testing Site",
						"quantity": 1,
						"priceInfo": {
							"basePrice": 50,
							"expiresInDays": 365,
							"taxes": 0,
							"finalPrice": 50
						},
						"tax": {
							"taxApplicationId": "60bf5bcb-ac64-496c-acc5-9c7cf54a1869",
							"productClassification": "unknown",
							"taxName": "VAT",
							"taxRate": 0.05,
							"taxType": "",
							"displayChargedAmount": false
						},
						"entitlements": [
							{
								 "memberProfile": {
                                        "data": {
                                            "siteId": "a2d4f06f-afc8-41c9-9910-0302bd2d9531",
                                            "bundleId": "2c7a627e-3773-4213-98e0-469d2d52ca93",
                                            "addonId": "ff362b08-173f-4964-8b46-ad09d050b162",
                                            "subscriptionId": "e591fca8-9ff7-42d1-9293-8894d37a83ee",
                                            "memberId": "4de4b1a6-f8c5-4c37-b07b-bdf089ebe0bd"
                                        }
                                    }
							}
						],
						"otherData": {
							"paygarden": {
                                    "data": {
                                        "credit": 50,
                                        "sku": "AAAB"
                                    }
                            }
						}
					}
				],
				"isPrimaryCharge": true,
				"isTrial": false,
				"status": "cancel",
				"transactionId": "ad9bce4e-c624-45e7-92b6-692353f1bb9d"
			},
			{
				"businessTransactionOperation": "nonRecurringMembershipCharge",
				"chargeId": "cc33489f-f736-497b-8a7e-90a0b01c9eed",
				"siteId": "a2d4f06f-afc8-41c9-9910-0302bd2d9531",
				"chargeDescription": "PB Engineering Testing Site",
				"items": [
					{
						"businessRevenueStream": "Initial Sale",
						"skuId": "4475820e-2956-11e9-b210-d663bd873d93",
						"displayName": "PB Engineering Testing Site",
						"itemDescription": "PB Engineering Testing Site",
						"quantity": 1,
						"priceInfo": {
							"basePrice": 30,
							"expiresInDays": 30,
							"taxes": 0,
							"finalPrice": 30
						},
						"tax": {
							"taxApplicationId": "60bf5bcb-ac64-496c-acc5-9c7cf54a1869",
							"productClassification": "unknown",
							"taxName": "VAT",
							"taxRate": 0.05,
							"taxType": "",
							"displayChargedAmount": false
						},
						"entitlements": [
							{
								 "memberProfile": {
                                        "data": {
                                            "siteId": "a2d4f06f-afc8-41c9-9910-0302bd2d9531",
                                            "bundleId": "4475820e-2956-11e9-b210-d663bd873d93",
                                            "addonId": "4e1b0d7e-2956-11e9-b210-d663bd873d93",
                                            "subscriptionId": "e591fca8-9ff7-42d1-9293-8894d37a83ee",
                                            "memberId": "4de4b1a6-f8c5-4c37-b07b-bdf089ebe0bd"
                                        }
                                 }
							}
						],
						"otherData": {
							"paygarden": {
                                    "data": {
                                        "credit": 30,
                                        "sku": "AAAA"
                                    }
                            }
						}
					}
				],
				"isPrimaryCharge": false,
				"isTrial": false,
				"status": "cancel",
				"transactionId": "985cb40b-5231-4d74-9407-404f324ed834"
			},
			{
				"businessTransactionOperation": "nonRecurringMembershipCharge",
				"chargeId": "6d214d43-bcdd-471c-b413-f8c141214699",
				"siteId": "a2d4f06f-afc8-41c9-9910-0302bd2d9531",
				"chargeDescription": "PB Engineering Testing Site",
				"items": [
					{
						"businessRevenueStream": "Initial Sale",
						"skuId": "4475820e-2956-11e9-b210-d663bd873d93",
						"displayName": "PB Engineering Testing Site",
						"itemDescription": "PB Engineering Testing Site",
						"quantity": 1,
						"priceInfo": {
							"basePrice": 5,
							"expiresInDays": 5,
							"taxes": 0,
							"finalPrice": 5
						},
						"tax": {
							"taxApplicationId": "60bf5bcb-ac64-496c-acc5-9c7cf54a1869",
							"productClassification": "unknown",
							"taxName": "VAT",
							"taxRate": 0.05,
							"taxType": "",
							"displayChargedAmount": false
						},
						"entitlements": [
							{
								"memberProfile": {
                                        "data": {
                                            "siteId": "a2d4f06f-afc8-41c9-9910-0302bd2d9531",
                                            "bundleId": "4475820e-2956-11e9-b210-d663bd873d93",
                                            "addonId": "4e1b0d7e-2956-11e9-b210-d663bd873d93",
                                            "subscriptionId": "e591fca8-9ff7-42d1-9293-8894d37a83ee",
                                            "memberId": "4de4b1a6-f8c5-4c37-b07b-bdf089ebe0bd"
                                        }
                                    }
							}
						],
						"otherData": {
							"paygarden": {
								"paygarden": {
                                    "data": {
                                        "credit": 5,
                                        "sku": "AAAA"
                                    }
                                }
							}
						}
					}
				],
				"isPrimaryCharge": false,
				"isTrial": false,
				"status": "success",
				"transactionId": "505c7b6f-cdec-4466-8202-e1a4769e33d0"
			}
		]
	},
	"digest": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJEaWdlc3QiOiI5OWZmMDBlYjVjZWRmYWE3OWE5MDJlZTU0MzhiZmFiYWI1YTNiZDhlNTljMTY5MTFmMWVkOGEzNWEwOTVlMTQ4NmExNDNiZWFkMTM4M2E4ZWVhZDk1M2I4YmUxZjVhZjMyNzAwMzY4NjVkMzg3YjNlMGIwOTAyNzM1Yzg2NGY1MSIsImlzcyI6InByb2JpbGxlci5jb20iLCJhdWQiOiJhMmQ0ZjA2Zi1hZmM4LTQxYzktOTkxMC0wMzAyYmQyZDk1MzEifQ.dw-b59ATTdAE20UNu9BeorjQCixyyqHlRkyd4SxHjpU"
}', true);
    }
}

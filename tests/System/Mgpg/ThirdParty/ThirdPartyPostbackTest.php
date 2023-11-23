<?php

namespace Tests\System\Mgpg\ThirdParty;

use Exception;
use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;

class ThirdPartyPostbackTest extends ThirdPartyTestCase
{
    /**
     * @var TokenGenerator
     */
    protected $tokenGenerator;

    /**
     * @var CryptService
     */
    protected $cryptService;

    /**
     * @var string
     */
    private $uri = '/mgpg/api/v1/postback/';

    /**
     * @var string
     */
    protected $payload = '{"invoice":{"invoiceId":"9067fcfe-4fa8-482b-aa7e-a2c8be2567ce","memberId":"ae30f120-d996-4985-823a-783bea978a20","userAgent":"","usingMemberProfile":false,"clientIp":"1.1.1.1","redirectUrl":"http://127.0.0.1:5001/api/TestRedirect","postbackUrl":"https://us-central1-mg-probiller-dev.cloudfunctions.net/postback-catchall","paymentInfo":{"currency":"EUR","paymentType":"banktransfer","paymentMethod":"sepadirectdebit"},"charges":[{"businessTransactionOperation":"subscriptionPurchase","chargeId":"91defd17-572a-45c1-8a4a-9aeb1ac6e278","siteId":"a2d4f06f-afc8-41c9-9910-0302bd2d9531","isPrimaryCharge":true,"chargeDescription":"subscriptionPurchase","isTrial":false,"items":[{"businessRevenueStream":"Initial Sale","skuId":"e765097a-182e-4903-8cad-a47ac9fc32b0","productInventoryId":"Marketplace","displayName":"Brazzers","itemDescription":"BrazzersDesc","quantity":1,"priceInfo":{"basePrice":1,"expiresInDays":2,"taxes":0,"finalPrice":1},"rebill":{"basePrice":10,"rebillDays":30,"taxes":0,"finalPrice":10},"tax":{"taxApplicationId":"60bf5bcb-ac64-496c-acc5-9c7cf54a1869","productClassification":"Product","taxName":"Tax Name","taxRate":0,"taxType":"vat","displayChargedAmount":false},"entitlements":[{"memberProfile":{"data":{"siteId":"4c22fba2-f883-11e8-8eb2-f2801f1b9fd1","bundleId":"4475820e-2956-11e9-b210-d663bd873d93","addonId":"4e1b0d7e-2956-11e9-b210-d663bd873d93","subscriptionId":"a37b92e6-88dd-4ed1-bfa4-92e32328e876","memberId":"77d45457-ad8d-434a-8705-9716fa0d7ec0"}}}],"legacyMapping":{"legacyProductId":15,"bypassUi":false,"hideUsernameField":false,"hideUsernamePasswordFields":false,"hideUsernamePasswordEmailFields":false,"hidePasswordFromEmail":false,"requireActiveParent":false,"parentSubscriptionId":"","templateId":"","packageId":"","subSiteId":"","crossSellType":"dropdown","crossSellDefaultValue":false},"otherData":{"any":{"data":{}}}}],"status":"aborted","transactionId":"0307e3f9-f8d9-48dd-9dac-a1e3a9bbb476"}]},"digest":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJEaWdlc3QiOiI0ZGZkZDU4YzU4OTMxNzgwNGNjNjU1ZWUwYjJkOTdlZTBkNmQzYTU1YjVkMjUwMjBiMTAzODJiYmRiNWE0MGQ5N2I0ODM4MDU3OWQ0ODdmZDgwMDhjOTFiNDg1MmIwOTQxZDBmNmMzZWU0ODI2NmVlOGE5NjAyYmZiNjI1ZTBhOCIsImlzcyI6InByb2JpbGxlci5jb20iLCJhdWQiOiJhMmQ0ZjA2Zi1hZmM4LTQxYzktOTkxMC0wMzAyYmQyZDk1MzEifQ.69Ea9xIl9CgIkEkl52pI6SK-7ITyG3hJF3xatLJZIkU"}';

    protected $clientPostbackUrl = 'http://client-test-postback.example';

    protected $postbackResponseValue = '{"correlationId":"2e27b6c9-3d75-467a-ad62-a5d95536bfc6","sessionId":"44e05917-f405-4091-aeea-5909b3836bf6","success":false,"purchaseId":"9067fcfe-4fa8-482b-aa7e-a2c8be2567ce","memberId":"ae30f120-d996-4985-823a-783bea978a20","nextAction":{"type":"finishProcess"},"transactionId":"0307e3f9-f8d9-48dd-9dac-a1e3a9bbb476","bundleId":"e765097a-182e-4903-8cad-a47ac9fc32b0","addonId":"4e1b0d7e-2956-11e9-b210-d663bd873d93","itemId":"0307e3f9-f8d9-48dd-9dac-a1e3a9bbb476","digest":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2MzUxNjY2ODcsIm5iZiI6MTYzNTE2NjY4NywiZXhwIjoxNjM1MTY4NDg3LCJoYXNoIjoiMWI1NDg1MjQ5NGQ0ZmNmNDkzNzNkYzM5NjljOTJiZGJlNWQzY2Q4NjBjNWFjYTZkZWNjNGNiN2Y4M2ZhNTIyN2QyMDk3ZGQzNjNlZjUyY2U0ZDJmMTZiNDcxYTY3ODZkZTQ1ZmQ4ZTQ3MTc2YzY1MzVlZDkxZmIyODE5ZjI3ZGMifQ.ZxJT5jkh2pRJLvXnLwseXHhMlWjInPFU_W8-kzJvKfDE-l2rLMpgZBrHsVILUW-TVTvoD3a9TwsPXS4dlWlXdQ"}';

    public function setUp(): void
    {
        parent::setUp();

        $this->tokenGenerator = app()->make(TokenGenerator::class);
        $this->cryptService   = app()->make(CryptService::class);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_success_200_for_postback_from_third_party(): string
    {
        $response = $this->json(
            'POST',
            $this->uri . $this->getJwtTokenValueByKey(self::CORRECT_KEYS),
            json_decode($this->payload, true)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return $response->response->getContent();
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_400_when_request_data_invalid()
    {
        $response = $this->json(
            'POST',
            $this->uri . $this->getJwtTokenValueByKey(self::CORRECT_KEYS),
            []
        );

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @dataProvider jwtTokenWithExpectedCodesProvider
     */
    public function it_should_return_appropriate_response_codes(string $jwt, int $httpStatusCode)
    {
        $response = $this->json(
            'POST',
            $this->uri . $jwt,
            json_decode($this->payload, true)
        );

        $response->assertResponseStatus($httpStatusCode);
    }

    /**
     * @test
     * @depends it_should_return_success_200_for_postback_from_third_party
     *
     * @param string $processResult
     *
     * @return void
     */
    public function it_should_have_valid_response_for_translated_return_from_third_party(string $processResult): void
    {
        $expectedProcessResult = json_decode($this->postbackResponseValue, true);
        $receivedProcessResult = json_decode($processResult, true);

        $this->assertArrayHasKey('digest', $receivedProcessResult);

        // Remove digests as they have time-based details that will never match.
        unset($expectedProcessResult['digest']);
        unset($receivedProcessResult['digest']);

        $this->assertEquals($expectedProcessResult, $receivedProcessResult);
    }
}
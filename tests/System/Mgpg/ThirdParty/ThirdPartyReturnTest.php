<?php

namespace Tests\System\Mgpg\ThirdParty;

use DOMDocument;
use Exception;
use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;

class ThirdPartyReturnTest extends ThirdPartyTestCase
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
    private $uri = '/mgpg/api/v1/return/';

    /**
     * @var string
     */
    protected $payload = '{"nextAction":{"type":"finishProcess","resolution":"server"},"invoice":{"invoiceId":"3328839b-b663-4aa1-acc3-ff1ffd0b02d4","memberId":"e00e03a2-011c-49e8-9079-ed3bbe05d87a","userAgent":"","usingMemberProfile":false,"clientIp":"1.1.1.1","redirectUrl":"https://mgpg-api-2.dev.pbk8s.com/api/TestRedirect","postbackUrl":"https://us-central1-mg-probiller-dev.cloudfunctions.net/postback-catchall","paymentInfo":{"currency":"EUR","paymentType":"banktransfer","paymentMethod":"sepadirectdebit"},"charges":[{"businessTransactionOperation":"subscriptionPurchase","chargeId":"f2f4cfa5-62dd-4f6f-a697-5e0d333cea81","siteId":"a2d4f06f-afc8-41c9-9910-0302bd2d9531","isPrimaryCharge":true,"chargeDescription":"subscriptionPurchase","isTrial":false,"items":[{"businessRevenueStream":"Initial Sale","skuId":"e765097a-182e-4903-8cad-a47ac9fc32b0","productInventoryId":"Marketplace","displayName":"Brazzers","itemDescription":"BrazzersDesc","quantity":1,"priceInfo":{"basePrice":1,"expiresInDays":2,"taxes":0,"finalPrice":1},"tax":{"taxApplicationId":"60bf5bcb-ac64-496c-acc5-9c7cf54a1869","productClassification":"Product","taxName":"Tax Name","taxRate":0,"taxType":"vat","displayChargedAmount":false},"entitlements":[{"memberProfile":{"data":{"siteId":"a2d4f06f-afc8-41c9-9910-0302bd2d9531","bundleId":"5fd44440-2956-11e9-b210-d663bd873d93","addonId":"670af402-2956-11e9-b210-d663bd873d93","subscriptionId":"69119993-105e-4465-a8c9-eff08b2e162f","memberId":"77d45457-ad8d-434a-8705-9716fa0d7ec0"}}}],"legacyMapping":{"legacyProductId":15,"bypassUi":false,"hideUsernameField":false,"hideUsernamePasswordFields":false,"hideUsernamePasswordEmailFields":false,"hidePasswordFromEmail":false,"requireActiveParent":false,"parentSubscriptionId":"","templateId":"","packageId":"","subSiteId":"","crossSellType":"dropdown","crossSellDefaultValue":false},"otherData":{"any":{"data":{}}}}],"status":"success","transactionId":"af2f4db5-789a-44d3-acf8-da54419553f8"}]},"digest":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJEaWdlc3QiOiIyMTJlZWQ2ZjBiZDc0NzY0Y2RiODdmY2QxZDdiZGIxZWIxNzM4ZjAzYmJiOGU5OTgyMjUwYWFkNDU2MjIxMGM2OTAxYmY0Yjg2OWYyMTFjMDU0MGJiYTU1NjVhN2NjNTY2Zjc2ZjMzMTUwMmYwYjhhNTMxNDEyZjZhZDg5NDE3NCIsImlzcyI6InByb2JpbGxlci5jb20iLCJhdWQiOiJhMmQ0ZjA2Zi1hZmM4LTQxYzktOTkxMC0wMzAyYmQyZDk1MzEifQ.mMOLKSjxuEWZA7vV481NQoEGr5LcmJSFS2nIeDmKF7Q"}';

    protected $clientPostbackUrl = 'http://client-test-postback.example';

    protected $returnResponseValue = '{"correlationId":"2e27b6c9-3d75-467a-ad62-a5d95536bfc6","sessionId":"44e05917-f405-4091-aeea-5909b3836bf6","success":true,"purchaseId":"3328839b-b663-4aa1-acc3-ff1ffd0b02d4","memberId":"e00e03a2-011c-49e8-9079-ed3bbe05d87a","nextAction":{"type":"finishProcess","resolution":"server"},"transactionId":"af2f4db5-789a-44d3-acf8-da54419553f8","bundleId":"e765097a-182e-4903-8cad-a47ac9fc32b0","addonId":"670af402-2956-11e9-b210-d663bd873d93","itemId":"af2f4db5-789a-44d3-acf8-da54419553f8","subscriptionId":"69119993-105e-4465-a8c9-eff08b2e162f","digest":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2MzQ3NTEyNTksIm5iZiI6MTYzNDc1MTI1OSwiZXhwIjoxNjM0NzUzMDU5LCJoYXNoIjoiNzhkNjVlNDE4Yjk0OGE5YzM3OWZlMzMyYzYwZGU3NGZkNTRhNDEzZTdlOTJjN2Q0Mjk2ZTliYjNmODA0NTI2ZDRkYWQ2OTRhN2VjMjg2YzBjZjNhYjA5NWNmZDE1ZDAzYjc0NzBjZjJhNTFlYzZhMWEyMjM3N2IyNDcxY2NlZWYifQ.9hr2qHJ6VkRXZj67O0KEE3DWNCbDER6PJBANVYvwSfUoGFhUWjZeqFWaUyhfcZtaVeh8CzbRhhGH5oFYPeQDaQ"}';

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
    public function it_should_return_success_200_for_return_from_third_party(): string
    {
        $response = $this->json(
            'POST',
            $this->uri . $this->getJwtTokenValueByKey(self::CORRECT_KEYS),
            ['payload' => $this->payload]
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $responseContent = $response->response->getContent();

        $doc = new DOMDocument();
        $doc->loadHTML($responseContent);

        $responseContentValue = $doc->getElementsByTagName('input')
            ->item(0)->attributes->getNamedItem('value')->nodeValue;

        return $responseContentValue;
    }

    public function it_should_return_400_when_json_is_malformed()
    {
        $response = $this->json(
            'POST',
            $this->uri . $this->getJwtTokenValueByKey(self::CORRECT_KEYS),
            ['payload' => '"key": malformed_json"}']
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
            ['payload' => $this->payload]
        );

        $response->assertResponseStatus($httpStatusCode);
    }

    /**
     * @test
     * @depends it_should_return_success_200_for_return_from_third_party
     *
     * @param string $processResult
     *
     * @return void
     */
    public function it_should_have_valid_response_for_translated_return_from_third_party(string $processResult): void
    {
        $expectedProcessResult = json_decode($this->returnResponseValue, true);
        $receivedProcessResult = json_decode($processResult, true);

        $this->assertArrayHasKey('digest', $receivedProcessResult);

        // Remove digests as they have time-based details that will never match.
        unset($expectedProcessResult['digest']);
        unset($receivedProcessResult['digest']);

        $this->assertEquals($expectedProcessResult, $receivedProcessResult);
    }
}
<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase\NewPaymentPurchase\NewMember;

use ProBillerNG\PurchaseGateway\Code;
use Illuminate\Http\Response;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 * @group common-fraud-service-integration
 */
class ProcessPurchaseFraudCheckWithEnabledDisabledFraudRulesTest extends ProcessPurchaseBase
{
    /**
     * @var string
     */
    protected $siteId;

    /**
     * @var string
     */
    protected $ipBlacklisted = '1.2.3.4';

    /**
     * Mocked ip updated
     */
    protected $blackListMockedIp = '1.2.3.4';

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->siteId = $this->processPurchasePayloadWithNoSelectedCrossSale()['siteId'];
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function it_should_contain_x_auth_token_for_init_purchase_with_fraud_rules_enabled(): array
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('the ip used is not on black list anymore');
        }

        $isFraudServiceEnabled = $this->isFraudServiceEnabled($this->siteId);

        // for this test we need to have the fraud rules enabled so that the fraud check will block the purchase process
        if (!$isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, true);
        }

        $request = $this->initPurchasePayload();
        if (config('app.feature.common_fraud_enable_for.init.join')) {
            // Update payload with blacklisted IP
            $request['clientIp'] = $this->blackListMockedIp;
        }

        $headers                    = $this->initPurchaseHeaders();
        $headers['X-Force-Cascade'] = 'test-rocketgate';

        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $request,
            $headers
        );

        $response->seeHeader('X-Auth-Token');

        return [
            'header'                => (string) $this->response->headers->get('X-Auth-Token'),
            'responseContent'       => json_decode($this->response->getContent(), true),
            'isFraudServiceEnabled' => $isFraudServiceEnabled
        ];
    }

    /**
     * @test
     * @depends it_should_contain_x_auth_token_for_init_purchase_with_fraud_rules_enabled
     * @return void
     * @param array $params Token.
     */
    public function it_should_block_process_purchase_when_fraud_is_disabled_and_the_IP_is_fraudulent($params): void
    {
        $isFraudServiceEnabled = $params['isFraudServiceEnabled'];

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($params['header'])
        );

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
        $decodedResponse = json_decode($this->response->getContent(), true);

        $this->assertEquals(Code::BLOCKED_DUE_TO_FRAUD_EXCEPTION, $decodedResponse['code']);
        $this->assertEquals(Code::getMessage($decodedResponse['code']), $decodedResponse['error']);

        // if the fraud was disabled update to initial value so that we dont break the other tests that may use fraud service value
        if (!$isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, false);
        }
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function it_should_contain_x_auth_token_for_init_purchase_with_fraud_rules_disabled(): array
    {
        $isFraudServiceEnabled = $this->isFraudServiceEnabled($this->siteId);

        // for this test we need to have the fraud rules enabled so that the fraud check will block the purchase process
        if ($isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, false);
        }

        $request             = $this->initPurchasePayload(self::TESTING_SITE_NO_FRAUD);
        $request['clientIp'] = $this->ipBlacklisted;

        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $request,
            $this->initPurchaseHeaders()
        );

        $response->seeHeader('X-Auth-Token');

        return [
            'header'                => (string) $this->response->headers->get('X-Auth-Token'),
            'responseContent'       => json_decode($this->response->getContent(), true),
            'isFraudServiceEnabled' => $isFraudServiceEnabled
        ];
    }

    /**
     * @test
     * @depends it_should_contain_x_auth_token_for_init_purchase_with_fraud_rules_disabled
     *
     * @param array $params Token.
     *
     * @return void
     * @throws \Exception
     */
    public function it_should_not_block_process_purchase_when_fraud_is_disabled_and_the_IP_is_fraudulent($params): void
    {
        $isFraudServiceEnabled = $params['isFraudServiceEnabled'];

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(self::TESTING_SITE_NO_FRAUD),
            $this->processPurchaseHeaders($params['header'])
        );

        $decodedResponse = json_decode($this->response->getContent(), true);

        $response->assertResponseStatus(Response::HTTP_OK);
        $this->assertTrue($decodedResponse['success']);

        // if the fraud was disabled update to initial value so that we dont break the other tests that may use fraud service value
        if ($isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, true);
        }
    }
}

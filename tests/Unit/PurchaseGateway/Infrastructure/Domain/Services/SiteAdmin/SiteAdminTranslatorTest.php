<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\Exceptions\SiteAdminCodeErrorException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\SiteAdminTranslator;
use ProbillerNG\SiteAdminServiceClient\Model\Error;
use ProbillerNG\SiteAdminServiceClient\Model\InlineResponse2004;
use ProbillerNG\SiteAdminServiceClient\Model\InlineResponse2004Data;
use Tests\UnitTestCase;

class SiteAdminTranslatorTest extends UnitTestCase
{
    const AGGREGATE_ID              = '29a1ee81-cf3d-11e9-8c91-0cc47a283dd2';
    const TYPE_NAME                 = 'ProBillerNG\SiteAdmin\Domain\Model\Event\SiteCreatedEvent';
    const DATE                      = '2019-11-22T16:07:44+00:00';
    const SITE_ID                   = '29a1ee81-cf3d-11e9-8c91-0cc47a283dd2';
    const BUSINESS_GROUP_ID         = '07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1';
    const SITE_NAME                 = 'Brazzers Premium';
    const SITE_URL                  = 'http://www.brazzerspremium.com';
    const PHONE_NUMBER              = '1-855-232-9555';
    const SKYPE_NUMBER              = 'https://is.gd/HathAW';
    const SUPPORT_LINK              = 'https://support.brazzers.com';
    const MAIL_SUPPORT_LINK         = 'brazzers@probiller.com';
    const MESSAGE_SUPPORT_LINK      = 'http://supportchat.contentabc.com/?domain=brazzerssupport.com';
    const POSTBACK_URL              = 'http://localhost';
    const TEMPLATE_ID               = 'eeed8906-4c34-4ea8-89ee-445f3291b1a3';
    const SENDER_NAME               = 'Probiller';
    const SENDER_EMAIL              = 'welcome@probiller.com';
    const REBILL_DAYS               = '30';
    const INITIAL_DAYS              = '2';
    const PAYMENT_METHOD            = 'cc';
    const SUPPORT_CANCELATION_LINK  = 'https://support.brazzers.com/cancel';

    /**
     * @var SiteAdminTranslator
     */
    private $translator;

    /**
     * setup function
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->translator = new SiteAdminTranslator();
    }

    /**
     * @test
     * @return void
     * @throws SiteAdminCodeErrorException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_when_an_error_is_received_from_service(): void
    {
        $this->expectException(SiteAdminCodeErrorException::class);

        $result = new Error(
            [
                'code'  => 1,
                'error' => 'Internal server error'
            ]
        );

        $this->translator->translate($result);
    }

    /**
     * @test
     * @return array
     * @throws SiteAdminCodeErrorException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_an_array_of_events(): array
    {
        $response = new InlineResponse2004();
        $body[0]  = new InlineResponse2004Data();

        $body[0]->setId(1);
        $body[0]->setAggregateId(self::AGGREGATE_ID);
        $body[0]->setEventBody(
            json_encode(
                [
                    'aggregate_id'         => self::AGGREGATE_ID,
                    'occurred_on'          => self::DATE,
                    'site_id'              => self::SITE_ID,
                    'business_group_id'    => self::BUSINESS_GROUP_ID,
                    'name'                 => self::SITE_NAME,
                    'url'                  => self::SITE_URL,
                    'phone_number'         => self::PHONE_NUMBER,
                    'skype_number'         => self::SKYPE_NUMBER,
                    'support_link'         => self::SUPPORT_LINK,
                    'support_cancellation_link' => self::SUPPORT_CANCELATION_LINK,
                    'message_support_link' => self::MESSAGE_SUPPORT_LINK,
                    'postback_url'         => self::POSTBACK_URL,
                    'service_collection'   => [
                        [
                            'name'    => 'fraud',
                            'enabled' => true
                        ],
                        [
                            'name'    => 'bin-routing',
                            'enabled' => true
                        ],
                        [
                            'name'    => 'email-service',
                            'options' => [
                                'templateId'  => self::TEMPLATE_ID,
                                'senderName'  => self::SENDER_NAME,
                                'senderEmail' => self::SENDER_EMAIL
                            ],
                            'enabled' => true
                        ]
                    ],
                    'REBILL_DAYS'           => self::REBILL_DAYS,
                    'INITIAL_DAYS'          => self::INITIAL_DAYS,
                    'PAYMENT_METHOD'        => self::PAYMENT_METHOD
                ]
            )
        );
        $body[0]->setTypeName(self::TYPE_NAME);
        $body[0]->setOccurredOn((new \DateTimeImmutable())->format('Y-m-d H:i:s.u'));

        $response->setData($body);
        $result = $this->translator->translate($response);
        $this->assertIsArray($result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_an_array_of_events
     * @param array $result Result
     * @return void
     */
    public function the_first_event_should_have_the_correct_id(array $result): void
    {
        $this->assertEquals(1, $result[0]['id']);
    }

    /**
     * @test
     * @depends it_should_return_an_array_of_events
     * @param array $result Result
     * @return void
     */
    public function the_first_event_should_have_the_correct_aggregate_id(array $result): void
    {
        $this->assertEquals(self::AGGREGATE_ID, $result[0]['aggregateId']);
    }

    /**
     * @test
     * @depends it_should_return_an_array_of_events
     * @param array $result Result
     * @return void
     */
    public function the_first_event_should_have_the_correct_event_type(array $result): void
    {
        $this->assertEquals(self::TYPE_NAME, $result[0]['typeName']);
    }

    /**
     * @test
     * @depends it_should_return_an_array_of_events
     * @param array $result Result
     * @return void
     */
    public function the_first_event_should_have_the_correct_event_body(array $result): void
    {
        $this->assertEquals(
            json_encode(
                [
                    'aggregate_id'              => self::AGGREGATE_ID,
                    'occurred_on'               => self::DATE,
                    'site_id'                   => self::SITE_ID,
                    'business_group_id'         => self::BUSINESS_GROUP_ID,
                    'name'                      => self::SITE_NAME,
                    'url'                       => self::SITE_URL,
                    'phone_number'              => self::PHONE_NUMBER,
                    'skype_number'              => self::SKYPE_NUMBER,
                    'support_link'              => self::SUPPORT_LINK,
                    'support_cancellation_link' => self::SUPPORT_CANCELATION_LINK,
                    'message_support_link'      => self::MESSAGE_SUPPORT_LINK,
                    'postback_url'              => self::POSTBACK_URL,
                    'service_collection'        => [
                        [
                            'name'    => 'fraud',
                            'enabled' => true
                        ],
                        [
                            'name'    => 'bin-routing',
                            'enabled' => true
                        ],
                        [
                            'name'    => 'email-service',
                            'options' => [
                                'templateId'  => self::TEMPLATE_ID,
                                'senderName'  => self::SENDER_NAME,
                                'senderEmail' => self::SENDER_EMAIL
                            ],
                            'enabled' => true
                        ]
                    ],
                    'REBILL_DAYS'    => self::REBILL_DAYS,
                    'INITIAL_DAYS'   => self::INITIAL_DAYS,
                    'PAYMENT_METHOD' => self::PAYMENT_METHOD,
                ]
            ),
            $result[0]['eventBody']
        );
    }
}

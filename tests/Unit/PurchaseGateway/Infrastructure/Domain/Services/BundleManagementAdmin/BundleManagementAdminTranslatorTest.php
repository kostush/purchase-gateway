<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin;

use ProbillerNG\BundleManagementAdminServiceClient\Model\Error;
use ProbillerNG\BundleManagementAdminServiceClient\Model\EventObject;
use ProbillerNG\BundleManagementAdminServiceClient\Model\InlineResponse2001;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\BundleManagementAdminTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\Exceptions\BundleManagementAdminCodeErrorException;
use Tests\UnitTestCase;

class BundleManagementAdminTranslatorTest extends UnitTestCase
{

    const AGGREGATE_ID = '6609648d-0714-452a-a5a3-07ca29c4931d';
    const EVENT_TYPE   = 'AddonCreatedEvent';
    const ADDON_ID     = '3205e25a-1539-49a2-94e9-9c02e917f2ca';
    const ADDON_NAME   = 'addonName';
    const ADDON_TYPE   = 'content';
    const DATE         = '2019-10-16 15:15:15';

    /**
     * @var BundleManagementAdminTranslator
     */
    private $translator;

    /**
     * setup function
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->translator = new BundleManagementAdminTranslator();
    }

    /**
     * @test
     * @return void
     * @throws BundleManagementAdminCodeErrorException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_when_an_error_is_received_from_service(): void
    {
        $this->expectException(BundleManagementAdminCodeErrorException::class);

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
     * @throws BundleManagementAdminCodeErrorException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_an_array_of_events(): array
    {
        $response               = new InlineResponse2001();
        $bundleManagementObject = [
            new EventObject(
                [
                    'id'          => 1,
                    'aggregateId' => self::AGGREGATE_ID,
                    'typeName'    => self::EVENT_TYPE,
                    'eventBody'   => json_encode(
                        [
                            'aggregate_id' => self::AGGREGATE_ID,
                            'addon_id'     => self::ADDON_ID,
                            'occurred_on'  => self::DATE,
                            'name'         => self::ADDON_NAME,
                            'type'         => self::ADDON_TYPE
                        ]
                    ),
                    'occuredOn'   => self::DATE
                ]
            )
        ];
        $response->setData($bundleManagementObject);

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
        $this->assertEquals(self::EVENT_TYPE, $result[0]['typeName']);
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
                    'aggregate_id' => self::AGGREGATE_ID,
                    'addon_id'     => self::ADDON_ID,
                    'occurred_on'  => self::DATE,
                    'name'         => self::ADDON_NAME,
                    'type'         => self::ADDON_TYPE
                ]
            ),
            $result[0]['eventBody']
        );
    }
}

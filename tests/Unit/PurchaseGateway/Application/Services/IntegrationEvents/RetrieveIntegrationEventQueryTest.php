<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\IntegrationEvents;

use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidDateTimeException;
use ProBillerNG\PurchaseGateway\Application\Services\IntegrationEvent\RetrieveIntegrationEventQuery;
use Tests\UnitTestCase;

class RetrieveIntegrationEventQueryTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_invalid_date_time_exception_if_invalid_data_provided()
    {
        $this->expectException(InvalidDateTimeException::class);
        new RetrieveIntegrationEventQuery('test');
    }

    /**
     * @test
     * @return RetrieveIntegrationEventQuery
     * @throws \Exception
     */
    public function it_should_return_a_retrieve_integration_event_query_object_if_valid_data_provided()
    {
        $retrieveIntegrationEnvetQuery = new RetrieveIntegrationEventQuery('2016-05-20 19:36:26.900794');
        $this->assertInstanceOf(RetrieveIntegrationEventQuery::class, $retrieveIntegrationEnvetQuery);
        return $retrieveIntegrationEnvetQuery;
    }

    /**
     * @test
     * @param RetrieveIntegrationEventQuery $retrieveIntegrationEnvetQuery RetrieveIntegrationEventQuery
     * @depends it_should_return_a_retrieve_integration_event_query_object_if_valid_data_provided
     * @return void
     */
    public function it_should_contain_an_event_date(RetrieveIntegrationEventQuery $retrieveIntegrationEnvetQuery)
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $retrieveIntegrationEnvetQuery->eventDate());
    }
}

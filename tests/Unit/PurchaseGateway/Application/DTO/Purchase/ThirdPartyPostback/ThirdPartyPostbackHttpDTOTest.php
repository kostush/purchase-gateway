<?php

namespace Tests\Unit\PurchaseGateway\Application\DTO\Purchase\ThirdPartyPostback;

use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback\ThirdPartyPostbackHttpDTO;
use Tests\UnitTestCase;

class ThirdPartyPostbackHttpDTOTest extends UnitTestCase
{
    /**
     * @test
     * @return array
     */
    public function it_should_return_an_array_when_serialized(): array
    {
        $postbackDTO = new ThirdPartyPostbackHttpDTO(
            'bf858ce0-6434-434b-9eec-df8a53172877',
            'approved'
        );
        $result      = $postbackDTO->jsonSerialize();

        $this->assertIsArray($result);

        return $result;
    }

    /**
     * @test
     * @param array $result Result of postbackDTO after serializationn
     * @return void
     * @depends it_should_return_an_array_when_serialized
     */
    public function it_should_contain_the_correct_session_id(array $result): void
    {
        $this->assertSame('bf858ce0-6434-434b-9eec-df8a53172877', $result['sessionId']);
    }

    /**
     * @test
     * @param array $result Result of postbackDTO after serializationn
     * @return void
     * @depends it_should_return_an_array_when_serialized
     */
    public function it_should_contain_correct_result(array $result): void
    {
        $this->assertSame('success', $result['result']);
    }
}

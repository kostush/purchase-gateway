<?php

namespace Middleware\Utils;

use App\Http\Middleware\Utils\UuidValidatorTrait;
use Illuminate\Contracts\Validation\Validator;
use Tests\UnitTestCase;

class UuidValidatorTraitTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_should_fails_error_when_an_array_is_passed()
    {
        $uuidValidator = $this->getObjectForTrait(UuidValidatorTrait::class);
        /**
         * @var Validator $validator
         */
        $validator = $uuidValidator->getUuidValidator([$this->faker->uuid, $this->faker->uuid]);
        $messages = $validator->errors();
        $msg = $messages->first('siteId');

        $this->assertTrue($validator->fails());
        $this->assertEquals('The site id value :input is not uuid.', $msg);
    }

    /**
     * @test
     */
    public function it_should_valid_when_an_valid_uuid_is_passed()
    {
        $uuidValidator = $this->getObjectForTrait(UuidValidatorTrait::class);
        /**
         * @var Validator $validator
         */
        $validator = $uuidValidator->getUuidValidator($this->faker->uuid);
        $messages = $validator->errors();
        $this->assertFalse($validator->fails());
        $this->assertEmpty($messages);
    }

    /**
     * @test
     */
    public function it_should_fails_error_when_an_random_string_is_passed()
    {
        $randomString = $this->faker->city;
        $uuidValidator = $this->getObjectForTrait(UuidValidatorTrait::class);
        /**
         * @var Validator $validator
         */
        $validator = $uuidValidator->getUuidValidator($randomString);
        $messages = $validator->errors();
        $msg = $messages->first('siteId');

        $this->assertTrue($validator->fails());
        $this->assertEquals("The site id value $randomString is not uuid.", $msg);
    }
}
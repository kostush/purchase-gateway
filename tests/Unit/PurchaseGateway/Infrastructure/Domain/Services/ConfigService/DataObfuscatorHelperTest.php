<?php

namespace PurchaseGateway\Infrastructure\Domain\Services\ConfigService;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\DataObfuscatorHelper;
use Tests\UnitTestCase;

class DataObfuscatorHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function should_obfuscate_when_have_data_that_have_to_be_obfuscated()
    {
        $data = [
            'businessGroup' => [
                'businessGroupId' => '806fb039-047d-3ec1-806b-c22d02062e1e',
                'name'            => 'Paysites',
                'description'     => 'PROBILLER.COM 855-232-9555',
                'privateKey'      => 'a8007617-7e81-3292-b070-11e6e5c3f639',
                'publicKeys'      => [
                    0 => '7350ef12-2a81-32fa-87df-a05643cef876',
                    1 => 'ab96b11c-4074-36d3-b291-0039eb667f6b',
                ],
                'createdAt'       => '2020-10-22T08:42:02.477276Z',
            ]
        ];

        $keys = [
            'privateKey',
            'publicKeys'
        ];

        $result = DataObfuscatorHelper::obfuscateSensitiveData($data, $keys);

        $this->assertEquals(
            [
                'businessGroup' => [
                    'businessGroupId' => '806fb039-047d-3ec1-806b-c22d02062e1e',
                    'name'            => 'Paysites',
                    'description'     => 'PROBILLER.COM 855-232-9555',
                    'privateKey'      => DataObfuscatorHelper::OBFUSCATED_STRING,
                    'publicKeys'      => [
                        0 => DataObfuscatorHelper::OBFUSCATED_STRING,
                        1 => DataObfuscatorHelper::OBFUSCATED_STRING,
                    ],
                    'createdAt'       => '2020-10-22T08:42:02.477276Z',
                ]
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function should_obfuscate_when_have_multi_level_data_that_have_to_be_obfuscated()
    {
        $data = [
            'businessGroup' => [
                'businessGroupId' => '806fb039-047d-3ec1-806b-c22d02062e1e',
                'name'            => 'Paysites',
                'description'     => 'PROBILLER.COM 855-232-9555',
                'privateKey'      => 'a8007617-7e81-3292-b070-11e6e5c3f639',
                'level1'          => [
                    'level2' =>
                        [
                            0 => '7350ef12-2a81-32fa-87df-a05643cef876',
                            1 => 'ab96b11c-4074-36d3-b291-0039eb667f6b',
                        ]
                ],
                'createdAt'       => '2020-10-22T08:42:02.477276Z'
            ]
        ];

        $keys = [
            'privateKey',
            'level2'
        ];

        $result = DataObfuscatorHelper::obfuscateSensitiveData($data, $keys);

        $this->assertEquals(
            [
                'businessGroup' => [
                    'businessGroupId' => '806fb039-047d-3ec1-806b-c22d02062e1e',
                    'name'            => 'Paysites',
                    'description'     => 'PROBILLER.COM 855-232-9555',
                    'privateKey'      => DataObfuscatorHelper::OBFUSCATED_STRING,
                    'level1'          => [
                        'level2' => [
                            0 => DataObfuscatorHelper::OBFUSCATED_STRING,
                            1 => DataObfuscatorHelper::OBFUSCATED_STRING,
                        ]
                    ],
                    'createdAt'       => '2020-10-22T08:42:02.477276Z',
                ]
            ],
            $result
        );
    }


    /**
     * @test
     */
    public function should_not_fail_when_dont_have_data_to_be_obfuscated()
    {
        $data = [
            'businessGroup' => [
                'businessGroupId' => '806fb039-047d-3ec1-806b-c22d02062e1e',
                'name'            => 'Paysites',
                'description'     => 'PROBILLER.COM 855-232-9555',
                'privateKey'      => 'a8007617-7e81-3292-b070-11e6e5c3f639',
                'publicKeys'      => [
                    0 => '7350ef12-2a81-32fa-87df-a05643cef876',
                    1 => 'ab96b11c-4074-36d3-b291-0039eb667f6b',
                ],
                'createdAt'       => '2020-10-22T08:42:02.477276Z',
            ]
        ];

        $keys = [
            'key1',
            'key2'
        ];

        $result = DataObfuscatorHelper::obfuscateSensitiveData($data, $keys);

        $this->assertEquals(
            $data,
            $result
        );
    }
}
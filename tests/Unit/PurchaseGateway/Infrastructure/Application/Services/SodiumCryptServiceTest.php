<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Application\Services;

use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use Tests\UnitTestCase;

class SodiumCryptServiceTest extends UnitTestCase
{
    /** @var string */
    protected $input = 'test';

    /**
     * @test
     * @throws \ProBillerNG\Crypt\UnableToEncryptException
     * @return string
     */
    public function it_should_encrypt_and_the_input_and_output_strings_should_be_different(): string
    {
        /** @var SodiumCryptService $cryptService */
        $cryptService = app(CryptService::class);

        $output = $cryptService->encrypt($this->input);

        $this->assertNotSame($this->input, $output);

        return $output;
    }

    /**
     * @test
     * @depends it_should_encrypt_and_the_input_and_output_strings_should_be_different
     * @param string $encryptedString Previous encrypted string
     * @throws \ProBillerNG\Crypt\UnableToDecryptException
     * @return void
     */
    public function it_should_decrypt_and_the_result_should_match_the_initial_input(string $encryptedString): void
    {
        /** @var SodiumCryptService $cryptService */
        $cryptService = app(CryptService::class);

        $output = $cryptService->decrypt($encryptedString);

        $this->assertSame($this->input, $output);
    }
}

<?php

namespace Tests\System\InitPurchase;

use Illuminate\Http\Response;

class WithLowercaseCurrencyTest extends InitPurchase
{
    /**
     * @test
     * @return void
     */
    public function it_should_successfully_do_the_init_if_currency_is_with_lowercases(): void
    {
        $this->payload['currency'] = 'usd';

        $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $this->assertResponseStatus(Response::HTTP_OK);
    }
}

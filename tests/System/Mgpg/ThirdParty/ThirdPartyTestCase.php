<?php

namespace Tests\System\Mgpg\ThirdParty;

use DOMDocument;
use Exception;
use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use Tests\SystemTestCase;

class ThirdPartyTestCase extends SystemTestCase
{
    const MISSING_KEYS  = 'MISSING_KEYS';

    const CORRECT_KEYS  = 'CORRECT_KEYS';

    const MALFORMED_JWT = 'MALFORMED_JWT';

    public function getJwtTokenValueByKey($key)
    {
        return $this->jwtTokenWithExpectedCodesProvider()[$key][0];
    }

    /**
     * @return array[]
     */
    public function jwtTokenWithExpectedCodesProvider(): array
    {
        return [
            self::MISSING_KEYS  => [
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2MzQ2NDgzNjUsIm5iZiI6MTYzNDY0ODM2NSwiZXhwIjoxNjM0NjUwMTY1LCJjbGllbnRSZXR1cm5VcmwiOiJvYlZEa1BhWitUZW9qc2lScitDdWxPZzNhQVozSDlZeFJGU3BlTjlQXC90cEp0YmNMM3ZnbmlNMCtiYVFRRXRva010U3Q1QnVGdGJHVElSOXdBUitwOVwvaFNYQUVNaUdRNHVpbFZXR3NtQ1BqT3Nrb2lMRDVEd0lRPSJ9.yxdbj_QFZtEL5xf0sO6yeg8MITxdX9KV6yzGa-Q7Dd3LXRgdc0BybsxoViKoRW_CVDwTPZUgfdriOza4cMI4pA',
                Response::HTTP_BAD_REQUEST
            ],
            self::CORRECT_KEYS  => [
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2MzQ3Mzc5MjUsIm5iZiI6MTYzNDczNzkyNSwiZXhwIjoxNjM0NzM5NzI1LCJjbGllbnRVcmwiOiI1alFMZklWK0ZERU5NTVZzOFwvVzVMSU1Hc2ZUS04wSlROUXlBNXkyYVNlcE9kNnNvTVVZT01BMmwrOWdvRVRIZzdIa2xBTU4rM2NmTlRtTGRRZCsrQThDMnc4QnFZTE41RFhJSG9QT1NoaFFleWV0aHhFRXJ4U289Iiwic2Vzc2lvbklkIjoiektTTE1PRUFXdmQydnpRczhROGhhTDFcL29Tazl0WDZhaXBmUmR2aENDTVB0RjFSS3BuSjljbklFdDlqZ3RmQ2lRRHloclZZN09QSTFUR1FGV0FyWWpJbDV1VFJ0emR4M1diRUxmQT09IiwicHVibGljS2V5SWQiOiJLdjhcL09LQUVYbU1ZamdUQmpUYmlNcjdVNllhS3c3YlVCRTZUXC9YdllnUjJNYXhxMGpRNGdsRTQ9IiwiY29ycmVsYXRpb25JZCI6IkVvK1RpalZySW5jMTdWU1lFclNvenhicEtlUVpleElCNlVPVG1vUU9tNjZlS3lEQWtiVkRNSUF1MHFvQXRhU1FQejZHaHpqcU5TdlJ2MGd6d0phMERzZW10bThNenhjdXE1R05wQT09In0.CaXEA7Ek4MxlTF2M03XbPrTRf3sMhG0-IQypoJcd3DZcEMrC8rjfpFpJgQyEa7TrewnDS_SXHRn9W9nxLN2eFA',
                Response::HTTP_OK
            ],
            self::MALFORMED_JWT => [
                '123123123.eyJpYXQiOjE2MzQ2NDgzNjUsIm5iZiI6MTYzNDY0ODM2NSwiZXhwIjoxNjM0NjUwMTY1LCJjbGllbnRSZXR1cm5VcmwiOiJvYlZEa1BhWitUZW9qc2lScitDdWxPZzNhQVozSDlZeFJGU3BlTjlQXC90cEp0YmNMM3ZnbmlNMCtiYVFRRXRva010U3Q1QnVGdGJHVElSOXdBUitwOVwvaFNYQUVNaUdRNHVpbFZXR3NtQ1BqT3Nrb2lMRDVEd0lRPSJ9.yxdbj_QFZtEL5xf0sO6yeg8MITxdX9KV6yzGa-Q7Dd3LXRgdc0BybsxoViKoRW_CVDwTPZUgfdriOza4cMI4pA',
                Response::HTTP_BAD_REQUEST
            ]
        ];
    }
}
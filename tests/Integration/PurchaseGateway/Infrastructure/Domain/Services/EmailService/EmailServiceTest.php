<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use CommonServices\EmailServiceClient\Model\SendEmailResponseDto;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Overrides;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\SendEmailResponse;
use Tests\IntegrationTestCase;

class EmailServiceTest extends IntegrationTestCase
{
    /**
     * @test
     * @throws \Exception
     * @return SendEmailResponse
     */
    public function it_should_return_a_send_email_response_given_correct_data_is_provided(): SendEmailResponse
    {
        $emailServiceResponse = new SendEmailResponseDto(
            [
                'sessionId' => $this->faker->uuid,
                'traceId'   => $this->faker->uuid
            ]
        );

        $emailApiMock = $this->createMock(EmailServiceClient::class);
        $emailApiMock->method('send')->willReturn($emailServiceResponse);

        $emailService = new EmailService(
            new EmailServiceAdapter(
                $emailApiMock,
                new EmailServiceTranslator()
            )
        );

        $result = $emailService->send(
            'testTemplateId',
            Email::create($this->faker->email),
            [
                'Name' => 'name',
                'Surname' => 'surname',
                'Items' => [
                    'item 1',
                    'item 2'
                ]
            ],
            Overrides::create(
                'override subject',
                'override name'
            ),
            SessionId::create(),
            "1"
        );

        $this->assertInstanceOf(SendEmailResponse::class, $result);

        return $result;
    }

    /**
     * @test
     * @param SendEmailResponse $result The send result
     * @depends it_should_return_a_send_email_response_given_correct_data_is_provided
     * @return void
     */
    public function it_should_return_a_send_email_response_with_a_session_id(SendEmailResponse $result): void
    {
        $this->assertInstanceOf(SessionId::class, $result->sessionId());
    }

    /**
     * @test
     * @param SendEmailResponse $result The send result
     * @depends it_should_return_a_send_email_response_given_correct_data_is_provided
     * @return void
     */
    public function it_should_return_a_send_email_response_with_a_trace_id(SendEmailResponse $result)
    {
        $this->assertNotEmpty($result->traceId());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_send_login_name_padded_email() {
        $this->markTestSkipped('Test works! To test please uncomment this line and set the email variable to YOUR email.');
        $jobData = 'YTozOntzOjU6ImVtYWlsIjtzOjE1OiJlbWFpbEBlbWFpbC5jb20iO3M6NDoiZGF0YSI7YTo3OntzOjY6InN0YXR1cyI7YjoxO3M6Nzoic3ViamVjdCI7czoxNzoiUmVhY3RpdmF0ZUV4cGlyZWQiO3M6MTI6ImZyaWVuZGx5TmFtZSI7czoxNzoiUmVhY3RpdmF0ZUV4cGlyZWQiO3M6NDoiZnJvbSI7czo5OiJQcm9iaWxsZXIiO3M6MTA6InRlbXBsYXRlSWQiO3M6MzY6ImVlZWQ4OTA2LTRjMzQtNGVhOC04OWVlLTQ0NWYzMjkxYjFhMyI7czo4OiJzZW5kZXJJZCI7czozNjoiZDExZDdmYTYtNWQyZi00NWI0LThlYjQtNWVmZDhkODhmMmJiIjtzOjEyOiJ0ZW1wbGF0ZURhdGEiO2E6Mjc6e3M6ODoiU0lURV9VUkwiO3M6Mjc6Imh0dHA6Ly93d3cucmVhbGl0eWtpbmdzLmNvbSI7czo5OiJTSVRFX05BTUUiO3M6MTM6IlJlYWxpdHkgS2luZ3MiO3M6MTU6IkRFU0NSSVBUT1JfTkFNRSI7czoxMzoiUmVhbGl0eSBLaW5ncyI7czoxNDoiQ1VSUkVOQ1lTWU1CT0wiO3M6MToiJCI7czo4OiJDVVJSRU5DWSI7czozOiJDQUQiO3M6MTE6IlJFTU9URV9BRERSIjtzOjExOiIxOTIuMTY4LjEuMSI7czo3OiJUQVhOQU1FIjtzOjE1OiJteXJhbmRvbXRheG5hbWUiO3M6NzoiVEFYUkFURSI7czo0OiIwLjA1IjtzOjE5OiJvbmxpbmVfc3VwcG9ydF9saW5rIjtzOjMzOiJodHRwczovL3N1cHBvcnQucmVhbGl0eWtpbmdzLmNvbS8iO3M6MjU6InN1cHBvcnRfY2FuY2VsbGF0aW9uX2xpbmsiO3M6Mzk6Imh0dHBzOi8vc3VwcG9ydC5yZWFsaXR5a2luZ3MuY29tL2NhbmNlbCI7czoxNzoiY2FsbF9zdXBwb3J0X2xpbmsiO3M6MTg6InRlbDoxLTg3Ny0yNzEtNjQyMyI7czoxNzoibWFpbF9zdXBwb3J0X2xpbmsiO3M6MzM6Im1haWx0bzpyZWFsaXR5a2luZ3NAcHJvYmlsbGVyLmNvbSI7czoyMDoibWVzc2FnZV9zdXBwb3J0X2xpbmsiO3M6MzM6Imh0dHBzOi8vc3VwcG9ydC5yZWFsaXR5a2luZ3MuY29tLyI7czoxODoic2t5cGVfc3VwcG9ydF9saW5rIjtzOjIwOiJodHRwczovL2lzLmdkLzZBZVdkNiI7czoxMDoiTE9HSU5fTkFNRSI7czo4OiIqKioqKioqKiI7czoxNToiQ0FSRF9OVU1CRVJfWFhYIjtzOjQ6IiMjIyMiO3M6MTQ6IlBBWU1FTlRfTUVUSE9EIjtzOjI6ImNjIjtzOjE2OiJUUkFOU0FDVElPTl9EQVRFIjtzOjEwOiIyMDIxLTAzLTEwIjtzOjEyOiJBTU9VTlRQUkVUQVgiO3M6NToiMTYwLjgiO3M6MTM6IkFNT1VOVFRBWE9OTFkiO3M6NDoiMjYuOCI7czoxMzoiQU1PVU5UV0lUSFRBWCI7czo1OiIxODcuNiI7czoxODoiUkVCSUxMSU5HX0RVRV9EQVRFIjtzOjEwOiIyMDIxLTA0LTA5IjtzOjI4OiJORVhUX1JFQklMTElOR19BTU9VTlRfUFJFVEFYIjtzOjU6IjE2MC44IjtzOjI5OiJORVhUX1JFQklMTElOR19BTU9VTlRfVEFYT05MWSI7czo0OiIyNi44IjtzOjI5OiJORVhUX1JFQklMTElOR19BTU9VTlRfV0lUSFRBWCI7czo1OiIxODcuNiI7czoxMToiUkVCSUxMX0RBWVMiO3M6MjoiMjAiO3M6MTI6IklOSVRJQUxfREFZUyI7czoyOiIxMCI7fX1zOjk6InNlc3Npb25JZCI7czozNjoiNjIyMWIyZWItYTNlZS00YTdlLWI2OGQtZjc3YzE0ODA2MWU2Ijt9';
        $emailData = unserialize(base64_decode($jobData));
        $emailData['email'] = 'youremail@mindgeek.com';
        $emailData['data']['senderId'] = '1';
        $emailData['data']['from'] = 'noreply@probiller.com';
        $emailData['data']['subject'] = 'LOGIN NAME PADDED TEST SUBJECT ';
        $emailData['data']['friendlyName'] = 'LOGIN NAME PADDED TEST FRIENDLY NAME';
        $emailData['data']['templateData']['LOGIN_NAME_PADDED'] = true;
        $emailService = app(EmailServiceClient::class);

        $emailResponse = $emailService->send(
            $emailData['data']['templateId'],
            Email::create($emailData['email']),
            $emailData['data']['templateData'],
            Overrides::create(
                $emailData['data']['subject'],
                isset($emailData['data']['friendlyName'])? $emailData['data']['friendlyName'] : null,
                $emailData['data']['from']
            ),
            SessionId::createFromString($emailData['sessionId']),
            $emailData['data']['senderId']
        );

        $this->assertNotEmpty($emailResponse['traceId']);
    }
}

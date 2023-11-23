# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.64.4] - 2022-02-23
### Fixed
- [BG-56500](https://jira.mgcorp.co/browse/BG-56500) - Change validation rules and retrieving savingAccount value as bool.

## [1.64.3] - 2022-02-22
### Fixed
- [BG-56859](https://jira.mgcorp.co/browse/BG-56859) - Send the two different padded usernames in the email when an initial and xsell are executed.

## [1.64.2] - 2022-02-21
### Added
- [BG-56059](https://jira.mgcorp.co/browse/BG-56059) - Integrate NSF Purchase flow with MGPG adaptor API.

## [1.64.1] - 2022-02-03
### Added
- [BG-56703](https://jira.mgcorp.co/browse/BG-56703) - Expose the flag usingMemberProfile in the MGPG adaptor API.
- [BG-57124](https://jira.mgcorp.co/browse/BG-57124) - Support memberId as a string and entitlements as optional parameter.

## [1.64.0] - 2022-02-02
### Added
- [BG-56444](https://jira.mgcorp.co/browse/BG-56444) - MGPG Adaptor support for cancel-rebill & disable-access.

## [1.63.8] - 2022-1-31
### Added
- [BG-53970](https://jira.mgcorp.co/browse/BG-53970) - Script to handle the restart of the failing workers.

## [1.63.7] - 2022-1-26
### Fixed
- [BG-57111](https://jira.mgcorp.co/browse/BG-57111) - Remove non recurring charge item for Mgpg adapter.

## [1.63.6] - 2022-1-18
### Fixed
- [BG-55821](https://jira.mgcorp.co/browse/BG-55821) - Change email behavior for non-recurring memberships. Add fix for non-recurring and one time charge products purchased. 

## [1.63.5] - 2022-1-17
### Add
- [BG-55799](https://jira.mgcorp.co/browse/BG-55799) - Updated CorrelationID generation to keep it consistent between all logs for MGPGAdapter

## [1.63.4] - 2022-1-13
### Add
- [BG-55816](https://jira.mgcorp.co/browse/BG-55816) - Change email behavior for non-recurring memberships. Removed non recurring business operation and represented as a parameter which supposed to be sent to Config service.

## [1.63.3] - 2022-01-12
### Fixed
- [BG-55994](https://jira.mgcorp.co/browse/BG-55994) - Issue with wrong email sent to the clients due to incorrect username validation

## [1.63.2] - 2022-01-11
### Fixed
- [BG-56367](https://jira.mgcorp.co/browse/BG-56367) - Return/Postback payload's success and transactionid for Paygarden.

## [1.63.1] - 2021-12-15
### Add
- [BG-56047](https://jira.mgcorp.co/browse/BG-56047) - MGPG Adaptor support paygarden.

## [1.63.0] - 2021-12-15
### Added
- [BG-54326](https://jira.mgcorp.co/browse/BG-54326) - Event to be sent to fraud if last4 validation fails.
- [BG-55680](https://jira.mgcorp.co/browse/BG-55680) - Cross sale site id validation.
### Updated
- [BG-55257](https://jira.mgcorp.co/browse/BG-55257) - "Fraud_Purchase_Velocity" event includes a parameter that indicates if payment template validation was skipped for a transaction.
- [BG-56274](https://jira.mgcorp.co/browse/BG-56274) - Tests using sensitive data from secret manager.

## [1.62.14] - 2021-12-14
### Fixed
- [BG-56219](https://jira.mgcorp.co/browse/BG-56219) - The way we handle invalid responses from config service.
### Changed
- [BG-56219](https://jira.mgcorp.co/browse/BG-56219) - ProBiller NG Logger with Lumen Logger to prevent errors while getting Azure Auth Token.

## [1.62.13] - 2021-12-13
### Fixed
- [BG-55456](https://jira.mgcorp.co/browse/BG-55456) - Fix payment template transformation issue for MGPG rebill update init with SEPA.

## [1.62.12] - 2021-12-13
### Added
- [BG-55407](https://jira.mgcorp.co/browse/BG-55407) - Support to mir and unionpay payment methods on MGPG Adaptor.
### Updated
- [BG-56087](https://jira.mgcorp.co/browse/BG-56087) - Set usingMemberProfile to true on Mgpg adapter for Init Rebill Update operation.

## [1.62.11] - 2021-12-08
### Added
- [BG-55809](https://jira.mgcorp.co/browse/BG-55809) - Coinpayment in MGPG Adaptor.

## [1.62.10] - 2021-12-07
### Fixed
- [BG-55955](https://jira.mgcorp.co/browse/BG-55955) - Prevent sending client postbacks to invalid urls.
- [BG-55955](https://jira.mgcorp.co/browse/BG-55955) - Split credit card validation between expired and invalid expiration dates.

## [1.62.9] - 2021-12-07
### Changed
- [BG-56133](https://jira.mgcorp.co/browse/BG-56133) - Purchase_Processed event return expiryDate null if the date is invalid.

## [1.62.8] - 2021-12-06
### Added
- [BG-55038](https://jira.mgcorp.co/browse/BG-55038) - Validation to adapter check payment fields.

## [1.62.7] - 2021-12-02
### Fixed
- [BG-55380](https://jira.mgcorp.co/browse/BG-55380) - Disable hardcoded template for email params.
### Updated
- [BG-55901](https://jira.mgcorp.co/browse/BG-55901) - Tests with new x-api-key.

## [1.62.6] - 2021-11-22
### Updated
- [BG-55857](https://jira.mgcorp.co/browse/BG-55857) - AAD secret key.

## [1.62.5] - 2021-11-18
### Fixed
- [BG-55731](https://jira.mgcorp.co/browse/BG-55731) - Show captcha as the default advice whenever Fraud Service takes too long to respond or an error in communication happens.

## [1.62.4] - 2021-11-16
### Fixed
- [BG-55585](https://jira.mgcorp.co/browse/BG-55585) - Add functionality for two submits on sec rev with simplified 3DS.

## [1.62.3] - 2021-11-12
### Fixed
- [BG-55142](https://jira.mgcorp.co/browse/BG-55142) - Rebill-Update returns renderGateway nextAction for declined requests
- [BG-55622](https://jira.mgcorp.co/browse/BG-55622) - Set success to false on mgpg adaptor if any transaction fails.

## [1.62.2] - 2021-11-11
### Fixed
- [BG-55618](https://jira.mgcorp.co/browse/BG-55618) - Internal logic to send emails for existing member purchase.

## [1.62.1] - 2021-11-11
### Fixed
- [BG-55429](https://jira.mgcorp.co/browse/BG-55429) - Fix the encoded postback URL on rebill init

## [1.62.0] - 2021-11-11
### Added
- [BG-54347](https://jira.mgcorp.co/browse/BG-54347) - 3DS simplified flow.

## [1.61.3] - 2021-11-10
### Fixed
- [BG-55588](https://jira.mgcorp.co/browse/BG-55588) - Send the site id into payload to fraud advice request on process calls.

## [1.61.2] - 2021-11-10
### Fixed
- [BG-55583](https://jira.mgcorp.co/browse/BG-55583) - Always add subscriptionId to `entitlements`

## [1.61.1] - 2021-11-08
### Added
- [BG-55180](https://jira.mgcorp.co/browse/BG-55180) - Mir and UnionPay as new payment methods. 
### Fixed
- [BG-55314](https://jira.mgcorp.co/browse/BG-55314) - Remove "billerName": "unknown" field/value from NG adapter payment template response.

## [1.61.0] - 2021-11-04
### Added
- [PASE-642](https://jira.mgcorp.co/browse/PASE-642) - Send cheque events to EIS.

## [1.60.0] - 2021-11-01
### Updated
- [BG-54951](https://jira.mgcorp.co/browse/BG-54951) - Update 3rd Party Biller Postback Endpoint to correctly parse input from MGPG
- [BG-54950](https://jira.mgcorp.co/browse/BG-54950) - Update 3rd Party Biller Return Endpoint to correctly parse input from MGPG
- [BG-55100](https://jira.mgcorp.co/browse/BG-55100) - Handle empty MGPG payment template info gracefully.

## [1.59.3] - 2021-10-28
### Added
- [BG-54906](https://jira.mgcorp.co/browse/BG-54906) - Add non recurring membership charge operation support for MGPG adapter.

## [1.59.2] - 2021-10-27
### Fixed
- [BG-54034](https://jira.mgcorp.co/browse/BG-54034) - Add remaining days support on rebill update for MGPG adapter.

## [1.59.1] - 2021-10-26
### Fixed
- [BG-53633](https://jira.mgcorp.co/browse/BG-53633) - MGPG adapter supporting render gateway next action. 

## [1.59.0] - 2021-10-26
### Added
- [BG-54328](https://jira.mgcorp.co/browse/BG-54328) - Support for safe bin response code from fraud service

## [1.58.2] - 2021-10-25
### Fixed
- [BG-54073](https://jira.mgcorp.co/browse/BG-54073) - Remove "billerName": "unknown" field/value from NG adapter process response.

## [1.58.1] - 2021-10-21
### Added
- [BG-53784](https://jira.mgcorp.co/browse/BG-53784) - Dws geolocation fields on adapter purhcase init request.

## [1.58.0] - 2021-10-20
### Added
- [BG-54133](https://jira.mgcorp.co/browse/BG-54133) - Send routing number to fraud service for checks purchases.

## [1.57.9] - 2021-10-14
### Updated
- [BG-54535](https://jira.mgcorp.co/browse/BG-54535) - Change email behavior for non-recurring memberships.

## [1.57.8] - 2021-10-13
### fixed
- [BG-54642](https://jira.mgcorp.co/browse/BG-54642) - Mgpg adapter fix next action issue if captcha

## [1.57.7] - 2021-10-06
### Updated
- [BG-54635](https://jira.mgcorp.co/browse/BG-54635) - Configuration to send bigger batches of emails.

## [1.57.6] - 2021-09-30
### Fixed
- [BG-54314](https://jira.mgcorp.co/browse/BG-54314) - Stops to send Fraud Velocity Event for sites that don't have fraud service enabled.

## [1.57.5] - 2021-09-28
### Fixed
- [BG-54469](https://jira.mgcorp.co/browse/BG-54469) - Fixing MGPG adapter init call request validation. Fixing system tests.

## [1.57.4] - 2021-09-23
### Fixed
- [BG-53846](https://jira.mgcorp.co/browse/BG-53846) - Support P1 onboarding to MGPG adapter. Adding validation for taxApplicationId field, let rebillDays and rebillAmount to be 0 for MGPG Adapter

## [1.57.3] - 2021-09-22
### Fixed
- [BG-53906](https://jira.mgcorp.co/browse/BG-53906) - Return 404 through the circuit breaker when transaction not found.
### Fixed
- [BG-53913](https://jira.mgcorp.co/browse/BG-53913) - Update for username validation to cannot be exactly 16 digits

## [1.57.2] - 2021-09-16
### Fixed
- [BG-54069](https://jira.mgcorp.co/browse/BG-54069) - Bin routing collection creation for cross sale to only have valid bin routing.

## [1.57.1] - 2021-09-16
### Fixed
- [BG-54083](https://jira.mgcorp.co/browse/BG-54083) - Fallback to cardHash in case originId is not present in the payment template.

## [1.57.0] - 2021-09-07
### Added
- [BG-53316](https://jira.mgcorp.co/browse/BG-53316) - NG/MGPG Rebill Update Adaptor.

## [1.56.0] - 2021-08-31
### Updated
- [BG-53821](https://jira.mgcorp.co/browse/BG-53821) - Enable Visa compliance flows.

## [1.55.2] - 2021-08-24
### Added
- [BG-53079](https://jira.mgcorp.co/browse/BG-53079) - Validations to block a cross sale purchase if the card used is blacklisted.

## [1.55.1] - 2021-08-24
### Updated
- [BG-52967](https://jira.mgcorp.co/browse/BG-52967) - Purchase entity creation to be bypassed in the case if NSF transaction when is a secondary revenue with payment template.

## [1.55.0] - 2021-08-23
### Added
- [BG-53334](https://jira.mgcorp.co/browse/BG-53334) - MerchantId on RG biller fields payment template creation.
- [BG-53339](https://jira.mgcorp.co/browse/BG-53339) - Referring Merchant Id on transaction service request payload for existing cc.

## [1.54.2] - 2021-08-19
### Fixed
- [BG-53634](https://jira.mgcorp.co/browse/BG-53634) - Fix retrieval of transaction based on item id for users migrated through user sync.

## [1.54.1] - 2021-08-19
### Fixed
- [BG-53567](https://jira.mgcorp.co/browse/BG-53567) - make amount optional for refund operation

## [1.54.0] - 2021-08-17
### Added
- [BG-53160](https://jira.mgcorp.co/browse/BG-53160) - Refund operation on NG-MGPG adapter

## [1.53.3] - 2021-08-12
### Fixed
- [BG-53115](https://jira.mgcorp.co/browse/BG-53115) - Make Member Info Optional for Process Request on Sec Rev Mgpg Adapter.

## [1.53.2] - 2021-08-09
### Added
- [BG-52704](https://jira.mgcorp.co/browse/BG-52704) - Validations for purchase init requests when member id is missing but subscription id or entry site id is provided.

## [1.53.1] - 2021-08-05
### Fixed
- [BG-52976](https://jira.mgcorp.co/browse/BG-52976) - Resolve the issue on Mgpg adaptor with ACH payment template validation parameter accountLast4 to make identity verification work on secondary revenue.

## [1.53.0] - 2021-08-05
### Added
- [BG-52429](https://jira.mgcorp.co/browse/BG-52429) - Trigger / Update BI events for blacklist flows.

## [1.52.3] - 2021-08-02
### Added
- [BG-52916](https://jira.mgcorp.co/browse/BG-52916) - Fields for the BI / DWS Purchase Processed event.

## [1.52.2] - 2021-07-29
### Added
- [BG-50567](https://jira.mgcorp.co/browse/BG-50567) - Add rocketgate ach template label in purchase payload.

## [1.52.1] - 2021-07-28
### Fixed
- [BG-52555](https://jira.mgcorp.co/browse/BG-52555) - Not setting isNFS flag on response DTO and on LegacyImport event when purchase is done with payment template.

## [1.52.0] - 2021-07-28
### Added
- [BG-49624](https://jira.mgcorp.co/browse/BG-49624) - Support for voiding test transactions - feature flag off.

## [1.51.2] - 2021-07-19
### Updated
- [BG-52462](https://jira.mgcorp.co/browse/BG-52462) - Path for BI events to write them in the same location as the other logs, to avoid loosing data during the releases.

## [1.51.1] - 2021-07-13
### Updated
- [BG-52726](https://jira.mgcorp.co/browse/BG-52726) - Fraud connection and execution timeout from 5 to 1 sec.

## [1.51.0] - 2021-07-12
### Added
- [BG-50568](https://jira.mgcorp.co/browse/BG-50568) - Support to dynamic CORS configuration from config service.

## [1.50.1] - 2021-07-12
### Fixed
- [BG-52664](https://jira.mgcorp.co/browse/BG-52664) - Swap the "non-US" condition on email templates with "is-US" to show cancel verbiage only for US customer.

## [1.50.0] - 2021-07-08
### Added
- [BG-50947](https://jira.mgcorp.co/browse/BG-50947) - Integrate new config service endpoints for Visa compliance - feature flag off.

## [1.49.9] - 2021-07-07
### Added
- [BG-50364](https://jira.mgcorp.co/browse/BG-50364) - Modify the payment template data (convert tax rate to percentage(%) format, add another flag IS_TRIAL, change the datetime format to date) to meet new email template compliance changes.

## [1.49.8] - 2021-06-22
### Fixed
- [BG-51642](https://jira.mgcorp.co/browse/BG-51642) - Remove data nesting from legacyMapping and add other fields on legacy mapping according to MGPG. Refactoring JWT tocken generator due to the new library which mgpg sdk depends on. 

## [1.49.7] - 2021-06-14
### Updated
- [BG-52104](https://jira.mgcorp.co/browse/BG-52104) - Purchase to finish process when it is a declined transaction for non sufficient funds reason.
### Added
- [BG-50888](https://jira.mgcorp.co/browse/BG-50888) - Functionality to handle duplicate purchase process requests sent by clients.

## [1.49.6] - 2021-06-14
### Fixed
- [BG-52197](https://jira.mgcorp.co/browse/BG-52197) - Handling on send email to avoid checking for CC details on check transaction type.

## [1.49.5] - 2021-06-03
### Removed
- [BG-50910](https://jira.mgcorp.co/browse/BG-50910) - Worker from all envs to stop creating and publishing async events when there is no need (the templates are created sync).

## [1.49.4] - 2021-06-03
### Updated
- [BG-50910](https://jira.mgcorp.co/browse/BG-50910) - Payment template to be asynchronously created, as a fallback, when the creation fails during the purchase.

## [1.49.3] - 2021-06-01
### Added
- [BG-51252](https://jira.mgcorp.co/browse/BG-51252) - Propagation of isNSFSupported value from Purchase Gateway to Transaction Service on 3ds lookup end point to support card upload for 3ds bypassing with NFS

## [1.49.2] - 2021-05-26
### Fixed
- [BG-51639](https://jira.mgcorp.co/browse/BG-51639) - Handling of config service failures on MP event creation worker.

## [1.49.1] - 2021-05-20
### Added
- [BG-51059](https://jira.mgcorp.co/browse/BG-51059) - Logs to have more visibility on the email data that is sent to the customers and fix the issue for missing first6 and last4.

## [1.49.0] - 2021-05-13
### Added
- [BG-50224](https://jira.mgcorp.co/browse/BG-50224) - Fallback solution for Billing Gateway import by api, in case of failure import should be done by Rabbit MQ

## [1.48.2] - 2021-5-12
### Fixed
- [BG-51150](https://jira.mgcorp.co/browse/BG-51150) - Config service usage instead SiteRepository on legacy import process   

## [1.48.1] - 2021-5-11
### Fixed
- [BG-51059](https://jira.mgcorp.co/browse/BG-51059) - Email payment information so that it displays correctly firstSix and LastFour for cross sale purchases.

## [1.48.0] - 2021-04-28
### Added
- [BG-50017](https://jira.mgcorp.co/browse/BG-50017) - NG/MGPG Adaptor(First and 2nd Purchase, 3DS v1/v2, Fraud).

## [1.47.3] - 2021-04-27
### Fixed
- [BG-50764](https://jira.mgcorp.co/browse/BG-50764) - Initial days for cross sale email.

### Fixed
- [BG-51050](https://jira.mgcorp.co/browse/BG-51050) - Exception "Illegal state of transition" when a transaction starts as 3DS, and it finishes as a non 3ds declined.

## [1.47.2] - 2021-04-22
### Fixed
- [BG-51010](https://jira.mgcorp.co/browse/BG-51010) - How to choose the template email type for purchases.

## [1.47.1] - 2021-04-15
### Fixed
- [BG-50858](https://jira.mgcorp.co/browse/BG-50858) - Missing cancellation link usage on email templates.

## [1.47.0] - 2021-04-13
### Added
- [BG-49369](https://jira.mgcorp.co/browse/BG-49369) - Integrate the authentication with config service.
- [BG-48634](https://jira.mgcorp.co/browse/BG-48634) - Integrate config service for Biller Mapping call.
- [BG-48765](https://jira.mgcorp.co/browse/BG-48765) - Update Docker Image with gRPC extensions.
- [BG-48666](https://jira.mgcorp.co/browse/BG-48666) - Integrate config service to receive the sites and business groups.
- [BG-48655](https://jira.mgcorp.co/browse/BG-48655) - Integrate config service instead of PANS to get the email advice and template id.
- [BG-48694](https://jira.mgcorp.co/browse/BG-48694) - Integrate config service instead using Safe Bin list and update the last 4 validation.
- [BG-50740](https://jira.mgcorp.co/browse/BG-50740) - Get max attempts (numberOfAttempts) from Config Service instead of hardcoded values.

## [1.46.13] - 2021-04-12
### Fixed
- [BG-50660](https://jira.mgcorp.co/browse/BG-50660) - Exception thrown on secondary revenue purchases for NSF transactions. 

## [1.46.12] - 2021-04-12
### Fixed
- [BG-50087](https://jira.mgcorp.co/browse/BG-50087) - Add more logs to trace if email were send.

## [1.46.11] - 2021-04-12
### Added
- [BG-50087](https://jira.mgcorp.co/browse/BG-50087) - Display cancel verbiage in transaction confirmation email only for US customer`s subscriptions

## [1.46.10] - 2021-04-06
### Fixed
- [BG-50477](https://jira.mgcorp.co/browse/BG-50477) - Handle undefined paymentTemplateId index when sending email on purchase.

## [1.46.9] - 2021-03-29
### Added
- [BG-50381](https://jira.mgcorp.co/browse/BG-50381) - Specific headers required by Fraud Team to help them prevent fraudulent activity.   

## [1.46.8] - 2021-03-25
### Added
- [BG-50252](https://jira.mgcorp.co/browse/BG-50252) - CORS whitelist domains to be allowed to access the gateway.
    - bigdicksatschool.com
    - toptobottom.com
    - str8togay.com

## [1.46.7] - 2021-03-25
### Fixed
- [BG-50385](https://jira.mgcorp.co/browse/BG-50385) - Create payment template synchronously covering ThreeDS flow too.

## [1.46.6] - 2021-03-25
### Added
- [BG-50365](https://jira.mgcorp.co/browse/BG-50365) - CORS whitelist domains to be allowed to access the gateway.
        - www.lilhumpers.com
        - www.lookathernow.com
   
## [1.46.5] - 2021-03-24
### Fixed
- [BG-50466](https://jira.mgcorp.co/browse/BG-50466) - Don't call create payment template if there is no transaction.

## [1.46.4] - 2021-03-24
### Updated
- [BG-50438](https://jira.mgcorp.co/browse/BG-50438) - The transaction import response handling.

## [1.46.3] - 2021-03-23
### Added
- [BG-50385](https://jira.mgcorp.co/browse/BG-50385) - Create payment template synchronously.

## [1.46.2] - 2021-03-23
### Updated
- [BG-49686](https://jira.mgcorp.co/browse/BG-49686) - (Feature flag on) Retrieve the correct username during the import to legacy through api && append a flag on purchase process domain event

## [1.46.1] - 2021-03-23
### Fixed
- [BG-50102](https://jira.mgcorp.co/browse/BG-50102) - Handling exception "Transaction Already Processed" to return proper error message and status code.

## [1.46.0] - 2021-03-18
### Added
- [BG-49686](https://jira.mgcorp.co/browse/BG-49686) - (Feature flag off) Retrieve the correct username during the import to legacy through api && append a flag on purchase process domain event

## [1.45.4] - 2021-03-18
### Fixed
- [BG-50172](https://jira.mgcorp.co/browse/BG-50172) - Handling exception on qysso return and postback for invalid signature. 

## [1.45.3] - 2021-03-17
### Fixed
- [BG-50172](https://jira.mgcorp.co/browse/BG-50172) - Handling exception "Transaction not Found" to return proper error message and error code. 

## [1.45.2] - 2021-03-15
### Fixed
- [BG-50223](https://jira.mgcorp.co/browse/BG-50223) - Empty result from fraud recommendation should return exception.

## [1.45.1] - 2021-03-11
### Added
- [BG-47073](https://jira.mgcorp.co/browse/BG-47073) - Propagation of isNSFSupported value from Purchase Gateway to Transaction Service.

## [1.45.0] - 2021-03-11
### Added
- [BG-49427](https://jira.mgcorp.co/browse/BG-49427) - Support for 3DS verification as a second tier for Velocity Rules or Spending Limits.

## [1.44.15] - 2021-03-10
### Updated
- [BG-49377](https://jira.mgcorp.co/browse/BG-49377) - Connection and execution timeouts when communicating with transaction service.

## [1.44.14] - 2021-03-10
### Fixed
- [BG-49915](https://jira.mgcorp.co/browse/BG-49915) - Rebill date on cross sales via email service.

## [1.44.13] - 2021-03-08
### Added
- [BG-49983](https://jira.mgcorp.co/browse/BG-49983) - CORS whitelist domains to be allowed to access the gateway.
        - www.bigdickatschool.com
        - www.jizzorgy.com
        - www.menofuk.com
        - www.toptopbottom.com
        - www.thegayoffice.com
        - www.godsofmen.com
        - www.teenslovehugecocks.com
        - www.sneakysex.com
        - www.momslickteens.com
        - www.milfhunter.com
        - www.roundandbrown.com
        - www.cumfiesta.com
        - www.8thstreetlatinas.com
        - www.lookathernow.com
        - www.welivetogether.com
        - www.moneytalks.com
        - www.brazzerspromo.com
        - www.brazzerssfw.com
        - www.lilhumpers.com
        - www.str8chaser.com

## [1.44.12] - 2021-03-01
### Fixed
- [BG-49611](https://jira.mgcorp.co/browse/BG-49611) - No billers in cascade error when using Netbilling and force3ds.

## [1.44.11] - 2021-02-22
### Fixed
- [BG-48910](https://jira.mgcorp.co/browse/BG-48910) - Flag 3ds usage by removing it from cross sales.

## [1.44.10] - 2021-02-17
### Added
- [BG-48406](https://jira.mgcorp.co/browse/BG-48406) - New information, threeDchallenged, to the PurchaseProcessed Bi event.

## [1.44.9] - 2021-02-15
## Added
- [BG-49397](https://jira.mgcorp.co/browse/BG-49397) - CORS whitelist domains to be allowed to access the gateway.
        - www.realitykingspremium.com
        - www.brazzerspremium.com

## [1.44.8] - 2021-02-08
### Fixed
- [BG-49305](https://jira.mgcorp.co/browse/BG-49305) - Any HTTP request to be enforced to HTTPS. 

## [1.44.7] - 2021-02-08
### Fixed
- [BG-49233](https://jira.mgcorp.co/browse/BG-49233) - Missing ThreeD version on the BI events. 

## [1.44.6] - 2021-02-08
### Fixed
- [BG-49204](https://jira.mgcorp.co/browse/BG-49204) - Third party tax information setup.

## [1.44.5] - 2021-02-03
### Updated
- [BG-48917](https://jira.mgcorp.co/browse/BG-48917) - Obfuscation pattern used to publish BI events related to RG ACH.

## [1.44.4] - 2021-02-02
### Updated
- [BG-49161](https://jira.mgcorp.co/browse/BG-49161) - Secondary Revenue for Third-Party (currently Qysso) to always send to the biller the collected information from request.

## [1.44.3] - 2021-02-02
### Updated
- [BG-48341](https://jira.mgcorp.co/browse/BG-48341) - Email template information for Rocketgate ACH.

## [1.44.2] - 2021-02-01
### Added
- [BG-48902](https://jira.mgcorp.co/browse/BG-48902) - Payment method, rebill days, initial days and support cancellation link fields to the email.

## [1.44.1] - 2021-01-26
### Fixed
- [BG-48689](https://jira.mgcorp.co/browse/BG-48689) - Session expired issue when Rocketgate swiches 3DS2 flow to 3DS.
### Added
- [BG-48576](https://jira.mgcorp.co/browse/BG-48576) - CORS whitelist for domains to be allowed to access the purchase gateway.
        - www.fakehuboriginals.com

## [1.44.0] - 2021-01-25
### Added
- [BG-48250](https://jira.mgcorp.co/browse/BG-48250) - Initial and SecRev cheque purchase with rocketgate but without payment template

## [1.43.2] - 2021-01-20
### Added
- [BG-48416](https://jira.mgcorp.co/browse/BG-48416) - CORS whitelist for new domains to be allowed to access the purchase gateway.
        - www.babes3ds2.com
        - www.men3ds2.com
        - www.fakehub3ds2.com
        - www.seancody3ds2.com
        - www.bigstr3ds2.com
        - www.transangels3ds2.com
        - www.digitalplayground3ds2.com

## [1.43.1] - 2021-01-19
### Updated
- [BG-47725](https://jira.mgcorp.co/browse/BG-47725) - InitialDays and RebillDays limits by setting their maximum to 10000 days.

## [1.43.0] - 2021-01-11
### Added
- [BG-48012](https://jira.mgcorp.co/browse/BG-48012) - Support for Qysso (new Biller).
- [BG-48185](https://jira.mgcorp.co/browse/BG-48185) - CORS whitelist for new domains to be allowed to access the purchase gateway.
        - www.trafficjunky.com
        - www.nutaku.com
        - www.nutaku.net

## [1.42.8] - 2021-01-07
### Added
- [BG-48360](https://jira.mgcorp.co/browse/BG-48360) - CORS whitelist for new subdomains to be allowed to access the gateway.
        - www.bellesahouse.com
        - www.devianthardcore.com
        - www.dickdorm.com
        - www.doghousedigital.com
        - www.fakeagent.com
        - www.faketaxi.com
        - www.familyhookups.com
        - www.familysinners.com
        - www.femaleagent.com
        - www.girlgrind.com
        - www.iconmalenetwork.com
        - www.kinkyspa.com
        - www.lesbea.com
        - www.massagerooms.com
        - www.momxxx.com
        - www.publicagent.com
        - www.realitydudesnetwork.com
        - www.realityjunkies.com
        - www.shewillcheat.com
        - www.sweetheartvideo.com
        - www.sweetsinner.com

## [1.42.7] - 2020-12-24
### Updated
- [BG-48227](https://jira.mgcorp.co/browse/BG-48227) - www.adult.com is now allowed to make purchases by MasterCard.

## [1.42.6] - 2020-12-23
### Changed
- [BG-48227](https://jira.mgcorp.co/browse/BG-48227) - Validation to block transactions for Visa and Master credit cards for certain sites.

## [1.42.5] - 2020-12-17
### Updated
- [BG-47941](https://jira.mgcorp.co/browse/BG-47941) - Disabled nuData on all environments.

## [1.42.4] - 2020-12-16
### Added
- [BG-47952](https://jira.mgcorp.co/browse/BG-47952) - Validation for Visa credit card to avoid processing transactions, except for www.clip4sale.com and www.clipcash.com.

## [1.42.3] - 2020-12-15
### Added
- [BG-47752](https://jira.mgcorp.co/browse/BG-47752) - CORS whitelist for new domains to be allowed to access the purchase gateway.
        - www.extremetubepremium.com
        - www.lezdombliss.com
        - www.keezmoviespremium.com
        - www.johnnyrapid.com
        - www.iconmale.com
        - www.hentaipros.com
        - www.bellesafilms.com
        - www.familysinners.com
        - www.metrohd.com
        - www.erito.com
        - www.elitexxx.com
        - www.bromo.com
        - www.blackmaleme.com
        - www.bigstr.com
        - www.biempire.com
        - www.hardcorekings.com
        - www.sexyhub.com
        - www.twistedfamilies.com
        - www.tube8vip.com
        - www.trueamateurs.com
        - www.transsensual.com
        - www.transharder.com
        - www.taboomale.com
        - www.maleaccess.com
        - www.spankwirepremium.com
        - www.matthewcamp.com
        - www.propertysex.com
        - www.noirmale.com
        - www.mofosexpremium.com
        - www.premiummh.pornportal.com
        - www.milehighmedia.com
        - www.whynotbi.com
        - www.squirted.com

## [1.42.2] - 2020-12-10
### Added
- [BG-47801](https://jira.mgcorp.co/browse/BG-47801) - CORS whitelist for www.mofos3ds2.com to be allowed to access the purchase gateway.

## [1.42.1] - 2020-12-10
### Fixed
- [BG-47475](https://jira.mgcorp.co/browse/BG-47475) - Remove unnecessary query when site is not sent in the request.

## [1.42.0] - 2020-12-07
### Updated
- [BG-46672](https://jira.mgcorp.co/browse/BG-46672) - Use payment templates to determine the biller to use on secondary purchase.

## [1.41.8] - 2020-12-02
### Added
- [BG-47442](https://jira.mgcorp.co/browse/BG-47442) - CORS whitelist for www.3ds2testing.com to be allowed to access the purchase gateway.

## [1.41.7] - 2020-11-19
### Fixed
- [BG-47314](https://jira.mgcorp.co/browse/BG-47314) - Return 424 instead of 500 when attempting to complete an aborted threeD transaction.

## [1.41.6] - 2020-11-19
### Added
- [BG-47176](https://jira.mgcorp.co/browse/BG-47176) - CORS whitelist for www.menpop.com and some www.mofos.com subdomains to allow purchase gateway requests.
- [BG-46884](https://jira.mgcorp.co/browse/BG-46884) - CORS whitelist for www.clipcash.com to allow purchase gateway requests. 
### Changed
- [BG-47057](https://jira.mgcorp.co/browse/BG-47057) - Secondary revenue to not call bin routing.

## [1.41.5] - 2020-11-19
### Fixed
- [BG-47077](https://jira.mgcorp.co/browse/BG-47077) - Send email for 3DS purchase cross sales when payment template is missing.

## [1.41.4] - 2020-11-12
### Added
- [BG-45656](https://jira.mgcorp.co/browse/BG-45656) - SessionId in the purchase process responses.

## [1.41.3] - 2020-11-10
### Updated
- [BG-42308](https://jira.mgcorp.co/browse/BG-42308) - Validations for first name, last name and email to accept special characters.

## [1.41.2] - 2020-11-05
### Added
- [BG-46662](https://jira.mgcorp.co/browse/BG-46662) - Added param isStickyGateway in projector and property to Site entity.

## [1.41.1] - 2020-11-02
### Updated
- [BG-47036](https://jira.mgcorp.co/browse/BG-47036) - Timeout for postbacks from 5 to 10 seconds.

## [1.41.0] - 2020-11-02
### Added
- [BG-44678](https://jira.mgcorp.co/browse/BG-44678) - Epoch support for secondary purchases.

## [1.40.3] - 2020-10-20
### Updated
- [BG-46617](https://jira.mgcorp.co/browse/BG-46617) - Paypal purchase handling for Epoch.

## [1.40.2] - 2020-10-19
### Added
- [BG-46338](https://jira.mgcorp.co/browse/BG-46338) - CORS whitelist for new domains: www.boyfriendtvpremium.com, www.ashemaletubeplus.com, www.vrtemptation.com, www.twistys.com, www.babes.com to be allowed to access the purchase gateway. 

## [1.40.1] - 2020-10-15
### Added
- [BG-43531](https://jira.mgcorp.co/browse/BG-43531) - Error classification in the purchase process response for declined transactions when using Rocketgate and Netbilling. 

## [1.40.0] - 2020-10-13
### Added
- [BG-44076](https://jira.mgcorp.co/browse/BG-44076) - New RocketgateCC3DS2PurchaseImportEvent.
- [BG-43899](https://jira.mgcorp.co/browse/BG-43899) - 3DS 2.X support for init, process and lookup requests.
- [BG-44332](https://jira.mgcorp.co/browse/BG-44332) - 3DS 2.X support for Complete and AUTH endpoints.

## [1.39.10] - 2020-10-08
### Updated
- [BG-44427](https://jira.mgcorp.co/browse/BG-44427) - After the Fraud Velocity event we also send Fraud Velocity Approved/Declined events

## [1.39.9] - 2020-09-28
### Added
- [BG-45728](https://jira.mgcorp.co/browse/BG-45728) - CORS whitelist for new domains: www.deviante.com, www.teenoverload.com to be allowed to access the gateway. 

## [1.39.8] - 2020-09-23
### Fixed
- [BG-44500](https://jira.mgcorp.co/browse/BG-44500) - Netbilling 3DS Bypass flag on Purchase Process and Purchase Initialized Events
- [BG-42768](https://jira.mgcorp.co/browse/BG-42768) - Modify the Purchase Process and Purchase Initialized Events for cascade event

## [1.39.7] - 2020-09-16
### Added
- [BG-45139](https://jira.mgcorp.co/browse/BG-45139) - CORS whitelist for new domain: www.papi.com to be allowed to access Purchase Gateway.

## [1.39.6] - 2020-09-08
### Added
- [BG-43993](https://jira.mgcorp.co/browse/BG-43993) - CORS whitelist for new domain: *probiller.com and *pbk8s.com so that demo gateway is allowed to access Purchase Gateway. 

## [1.39.5] - 2020-09-03
### Added
- [BG-45189](https://jira.mgcorp.co/browse/BG-45189) - Subscription id in the response for purchase process, in case of NSF.

## [1.39.4] - 2020-08-27
### Fixed
- [BG-44293](https://jira.mgcorp.co/browse/BG-44293) - Update user info with all required data before sending to a biller during a payment with new credit card.

## [1.39.3] - 2020-08-26
### Added
- [BG-44501](https://jira.mgcorp.co/browse/BG-44501) - CORS whitelist for new domains: www.twinkpop.com, www.taboomale.com to be allowed to access the gateway.

## [1.39.2] - 2020-08-20
### Fixed
- [BG-44492](https://jira.mgcorp.co/browse/BG-44492) - To not have cross sell transaction imported into Member Profile when entry site have NSF featured flag disabled.

## [1.39.1] - 2020-08-13
### Fixed
- [BG-41981](https://jira.mgcorp.co/browse/BG-41981) - Pass disable Fraud Check flag for cross-sales to transaction-service for netbilling biller

## [1.39.0] - 2020-08-13
### Added
- [BG-41615](https://jira.mgcorp.co/browse/BG-41615) - Epoch support for other payment types.

## [1.38.3] - 2020-08-10
### Fixed
- [BG-44465](https://jira.mgcorp.co/browse/BG-44465) - Import to MP when cross sale is aborted.

## [1.38.2] - 2020-08-05
### Added
- [BG-43759](https://jira.mgcorp.co/browse/BG-41472) - BillerName in the purchase process response and the postback payload send to the clients.

## [1.38.1] - 2020-08-04
### Fixed
- [BG-44321](https://jira.mgcorp.co/browse/BG-44321) - CustomerId mandatory when building a NB transaction.

## [1.38.0] - 2020-08-03
### Added
- [BG-43397](https://jira.mgcorp.co/browse/BG-43397) - Support for NSF purchases for Rocketgate and Netbilling.
### Updated
- [BG-43148](https://jira.mgcorp.co/browse/BG-43148) - Cascade biller to remove non 3DS billers.

## [1.37.0] - 2020-07-20
### Fixed
- [BG-43267](https://jira.mgcorp.co/browse/BG-43267) - System tests that are failing due to the cascade behaviour.
### Added
- [BG-41191](https://jira.mgcorp.co/browse/BG-41191) - Worker to update expired pending sessions.
- [BG-41283](https://jira.mgcorp.co/browse/BG-41283) - Third party return endpoint.
- [BG-41275](https://jira.mgcorp.co/browse/BG-41275) - Third party postback endpoint.

## [1.36.5] - 2020-07-16
### Updated
- [BG-43633](https://jira.mgcorp.co/browse/BG-43633) - Event Ingestion Client - adding session id for tracking and remove failed connections from alerts 

## [1.36.4] - 2020-07-15
### Added
- [BG-42752](https://jira.mgcorp.co/browse/BG-42752) - CORS whitelist for new domains: www.mofos.com, www.seancody.com, www.transangels.com, www.transangelspremium.com, www.elitexxx.com to be allowed to access the gateway. 

## [1.36.3] - 2020-07-09
### Updated
- [BG-43302](https://jira.mgcorp.co/browse/BG-43302) - Setup EIS Config and start sending Fraud Velocity event to EIS

## [1.36.2] - 2020-07-06
### Changed
- [BG-40935](https://jira.mgcorp.co/browse/BG-40935) - The way we handle client postbacks, further details [here](https://wiki.mgcorp.co/pages/viewpage.action?pageId=119869034#PurchaseAPI(Integration)-PostbacksPostbacks).
### Updated
- [BG-43234](https://jira.mgcorp.co/browse/BG-43234) - Credit card number to be sent to COSE so that the customer can see first six digits and last four digits in the email receipt.

## [1.36.1] - 2020-06-29
### Updated
- [BG-42201](https://jira.mgcorp.co/browse/BG-42201) - Logger version to include new obfuscation methods.
### Added
- [BG-41472](https://jira.mgcorp.co/browse/BG-41472) - TransactionId in the purchase process response and the postback payload send to the clients.

## [1.36.0] - 2020-06-29
### Added
- [BG-42490](https://jira.mgcorp.co/browse/BG-42490) - Fraud Velocity rule on purchase process.
- [BG-41008](https://jira.mgcorp.co/browse/BG-41008) - EIS Integration sending new Fraud velocity event.
### Fixed
- [BG-43036](https://jira.mgcorp.co/browse/BG-43036) - First name to be sent to External Fraud System (CFS).
- [BG-43147](https://jira.mgcorp.co/browse/BG-43147) - Total Amount to be sent to External Fraud System (CFS) w/o taxes.
- [BG-42762](https://jira.mgcorp.co/browse/BG-42762) - Invalid state for second process request when captcha validated on process.
- [BG-43300](https://jira.mgcorp.co/browse/BG-43300) - Include a field called identifier on the call to Fraud Service.

## [1.35.7] - 2020-06-25
### Fixed
- [BG-42827](https://jira.mgcorp.co/browse/BG-42827) - Update lastUsed date event of payment template for Netbilling.

## [1.35.6] - 2020-06-25
### Changed
- [BG-43037](https://jira.mgcorp.co/browse/BG-43037) - Revert to one submit per biller.

## [1.35.5] - 2020-06-23
### Added
- [BG-41472](https://jira.mgcorp.co/browse/BG-41472) - TransactionId in the purchase process response and the postback payload send to the clients.

## [1.35.4] - 2020-06-18
### Fixed
- [BG-42723](https://jira.mgcorp.co/browse/BG-42723) - Header User-Agent to be optional instead of required in the process purchase request.

## [1.35.3] - 2020-06-16
### Fixed
- [BG-41049](https://jira.mgcorp.co/browse/BG-41049) - The card expiration month and expiration year added to CCPurchaseEvent to be imported to Legacy.

## [1.35.2] - 2020-06-16
### Fixed
- [BG-42859](https://jira.mgcorp.co/browse/BG-42859) - NuData redirect url.

## [1.35.1] - 2020-06-15
### Fixed
- [BG-42858](https://jira.mgcorp.co/browse/BG-42858) - Cascade production urls.

## [1.35.0] - 2020-06-15
### Added
- [BG-42580](https://jira.mgcorp.co/browse/BG-42580) - Enable cascade communication with dynamic biller submits. 

## [1.34.5] - 2020-06-11
## Added
- [BG-42686](https://jira.mgcorp.co/browse/BG-42686) - CORS whitelist for new domains: www.cumfu.com, www.milfed.com, www.premiummh.pornportal.com to be allowed to access the gateway. 

## [1.34.4] - 2020-06-10
### Fixed
- [BG-42532](https://jira.mgcorp.co/browse/BG-42532) - Open api docs to send netbilling and rocketgate biller fields on item retrieval.

## [1.34.3] - 2020-06-09
### Fixed
- [BG-41944](https://jira.mgcorp.co/browse/BG-41944) - Session validation to be always UUID when invalid one is sent in the requests.
### Added
- [BG-41463](https://jira.mgcorp.co/browse/BG-41463) - Type casting to purchase process fields in order to avoid type error.

## [1.34.2] - 2020-06-08
### Added
- [BG-42324](https://jira.mgcorp.co/browse/BG-42324) - Add Sticky gateway MID feature on rocketgate for clips 4 sale. 

## [1.34.1] - 2020-05-27
### Fixed
- [BG-42067](https://jira.mgcorp.co/browse/BG-42067) - System tests after enabling communication with CFS.
- [BG-42071](https://jira.mgcorp.co/browse/BG-42071) - Fix misleading 500 log when Common Fraud Service returns 500.

## [1.34.0] - 2020-05-27
### Added
- [BG-41086](https://jira.mgcorp.co/browse/BG-41086) - Epoch support on init flow.
- [BG-41109](https://jira.mgcorp.co/browse/BG-41109) - Epoch support on process flow.
- [BG-41176](https://jira.mgcorp.co/browse/BG-41109) - Redirect endpoint to support epoch flow.

## [1.33.4] - 2020-05-26
### Updated
- [BG-41443](https://jira.mgcorp.co/browse/BG-41443) - Handling of errors for payment template validation through CB.

## [1.33.3] - 2020-05-22
### Fixed
- [BG-42224](https://jira.mgcorp.co/browse/BG-42224) - Allowed headers to all on CORS.

## [1.33.2] - 2020-05-21
### Fixed
- [BG-42171](https://jira.mgcorp.co/browse/BG-42171) - Sensitive data obfuscation.

## [1.33.1] - 2020-05-20
### Fixed
- [BG-42080](https://jira.mgcorp.co/browse/BG-42080) - Handling exceptions on second return to complete endpoint.

## [1.33.0] - 2020-05-19
### Removed
- [BG-40877](https://jira.mgcorp.co/browse/BG-40877) - Hardcoded control keyword mapping.

## [1.32.1] - 2020-05-19
### Fixed
- [BG-42036](https://jira.mgcorp.co/browse/BG-42036) - Process with 3DS generates two pending transactions, one for each bin routing attempt.

## [1.32.0] - 2020-05-12
### Added
- [BG-41343](https://jira.mgcorp.co/browse/BG-41343) - Currency and site-id on transaction retrieval.

## [1.31.1] - 2020-05-11
### Fixed
- [BG-41978](https://jira.mgcorp.co/browse/BG-41978) - LastName not sent to CFS.
- [BG-41979](https://jira.mgcorp.co/browse/BG-41979) - BI events not updated to have Fraud Recommendation Collection

## [1.31.0] - 2020-05-11
### Added
- [BG-40962](https://jira.mgcorp.co/browse/BG-40962) - Enable Common Fraud Service retrieval

## [1.30.3] - 2020-05-11
### Added
- [BG-40962](https://jira.mgcorp.co/browse/BG-40962) - Support Common Fraud Service combined codes

## [1.30.2] - 2020-05-11
### Fixed
- [BG-41672](https://jira.mgcorp.co/browse/BG-41672) - Biller transaction creation was preventing cross-sale to go through.

## [1.30.1] - 2020-04-28
## Added
- [BG-40852](https://jira.mgcorp.co/browse/BG-40852) - CORS whitelist the domains of tubes and modelhub allowed to access the gateway. 

## [1.30.0] - 2020-04-28
### Updated
-[BG-38846](https://jira.mgcorp.co/browse/BG-38846) -  The Process Flow so that the nextAction is available to the client.

## [1.29.1] - 2020-04-27
### Fixed
- [BG-40947](https://jira.mgcorp.co/browse/BG-40947) -  Send username and password on Netbilling transactions
- [BG-40961](https://jira.mgcorp.co/browse/BG-40961) -  Remove Netbilling biller member id from cross sale purchase transaction

### Added
- [BG-40796](https://jira.mgcorp.co/browse/BG-40796) - Add CardHash to Payment Template create event.

## [1.29.0] - 2020-04-22
### Added
-[BG-38290](https://jira.mgcorp.co/browse/BG-38290) - The complete flow for 3DS.

## [1.28.9] - 2020-04-21
### Added
- [BG-40948](https://jira.mgcorp.co/browse/BG-40948) - Domain brazzersplus.com as allowed origin for CORS to be able to do purchase process.

## [1.28.8] - 2020-04-15
### Fixed
- [BG-40702](https://jira.mgcorp.co/browse/BG-40702) - Block blacklisted user on the third attempt.
- [BG-40711](https://jira.mgcorp.co/browse/BG-40711) - Reset previous advice flags during fraud recommendation mapping. 

## [1.28.7] - 2020-04-14
### Fixed
- [BG-40846](https://jira.mgcorp.co/browse/BG-40846) - Missing Netbilling biller account control keyword mapping.

## [1.28.6] - 2020-04-02
### Fixed
- [BG-40686](https://jira.mgcorp.co/browse/BG-40686) - Turn of the communication with CFS.

## [1.28.5] - 2020-03-31
### Added
- [BG-39169](https://jira.mgcorp.co/browse/BG-39169) - Receive the response of Fraud Recommendation from Common Fraud service.

## [1.28.4] - 2020-03-26
### Added
- [BG-40335](https://jira.mgcorp.co/browse/BG-40335) - Domain clips4sale.com as allowed origin for CORS to be able to do purchase process.

## [1.28.3] - 2020-03-25
### Fixed
- [BG-38541](https://jira.mgcorp.co/browse/BG-38541) - Added new parameter postbackUrl to the init payload to override site postbackUrl on process flow.
- [BG-40187](https://jira.mgcorp.co/browse/BG-40187) - Double encoding on attemptedTransactions NU Data BI event.

## [1.28.2] - 2020-03-19
### Fixed
- [BG-40046](https://jira.mgcorp.co/browse/BG-40046) - Default billerName and newCCUsed in TransactionCollectionJsonSerializer for older transactions.

## [1.28.1] - 2020-03-19
### Fixed
- [BG-39988](https://jira.mgcorp.co/browse/BG-39988) - NuDetect score BI event double json encoding on score response.

## [1.28.0] - 2020-03-19
### Added
- [BG-39970](https://jira.mgcorp.co/browse/BG-39970) - More test for projection integration.
- [BG-38892](https://jira.mgcorp.co/browse/BG-38892) - The init flow for secondary revenue to retrieve the payment templates for each biller in the cascade.
- [BG-37797](https://jira.mgcorp.co/browse/BG-37797) - The Process Flow so Process Manager can interact with local Cascade Model.
- [BG-38829](https://jira.mgcorp.co/browse/BG-38829) - The domain event and BI events to include attempted transactions, traffic source and payment method.
- [BG-38829](https://jira.mgcorp.co/browse/BG-37812) - Update process manager for 3DS.
- [BG-37818](https://jira.mgcorp.co/browse/BG-37818) - Authenticate ThreeD endpoint.

## [1.27.0] - 2020-03-12
### Added
- [BG-38505](https://jira.mgcorp.co/browse/BG-38505) - Bin Routing service call by business group for initial purchase with RG and NB.
- [BG-37830](https://jira.mgcorp.co/browse/BG-37830) - Bin Routing service call by business group for SecRev purchase with new credit card with RG and NB.

## [1.26.1] - 2020-03-05 
### Fixed
- [BG-39754](https://jira.mgcorp.co/browse/BG-39754) Error validation for nu data widget.

## [1.26.0] - 2020-03-05 
### Added
- [BG-38283](https://jira.mgcorp.co/browse/BG-38283) - When NuData is enabled on a transaction attempt the NuData score should be received and send a BI event.
- [BG-39667](https://jira.mgcorp.co/browse/BG-39667) - Support for currency code, on init flow, on both lowercase and uppercase format.
- [BG-37796](https://jira.mgcorp.co/browse/BG-37796) - Support for init flow to use a new cascade model.
### Updated
- [BG-37931](https://jira.mgcorp.co/browse/BG-37931) - Fraud Recommendation OpenAPI description and example.
- [BG-38803](https://jira.mgcorp.co/browse/BG-38803) - Removed the usage of biller member id.
- [BG-38802](https://jira.mgcorp.co/browse/BG-38802) - Removed the usage deprecated purchase enriched event properties.

## [1.25.0] - 2020-02-26 
### Added
- [BG-39304](https://jira.mgcorp.co/browse/BG-39304) - New RocketgateCC3DSPurchaseImportEvent.
- [BG-38951](https://jira.mgcorp.co/browse/BG-38951) - As a Client on the init flow, I want to know if 3DS is forced.
- [BG-38284](https://jira.mgcorp.co/browse/BG-38284) - Retrieve NuData settings on purchase init.

## [1.24.0] - 2020-02-18
### Added
- [BG-38703](https://jira.mgcorp.co/browse/BG-38703) - Update FraudAdvice with 3D information.
- [BG-38707](https://jira.mgcorp.co/browse/BG-38707) - Update Rocketgate/Netbilling (billers) with support3D information.
- [BG-37931](https://jira.mgcorp.co/browse/BG-37931) - Added new common fraud service call for process using existing credit card.
- [BG-37937](https://jira.mgcorp.co/browse/BG-37937) - Added new common fraud service call for init on secondary revenue.
- [BG-38664](https://jira.mgcorp.co/browse/BG-38664) - Mock endpoints for complete3D.
- [BG-38692](https://jira.mgcorp.co/browse/BG-38692) - Mock endpoints for authenticate3D.
- [BG-38432](https://jira.mgcorp.co/browse/BG-38432) - Created the skeleton classes for Purchase Init Response with NuData.
- [BG-38708](https://jira.mgcorp.co/browse/BG-38708) - Update openAPI for init + process + authenticate + complete.
- [BG-38430](https://jira.mgcorp.co/browse/BG-38430) - Added mocked implementation for NuDataSettings retrieval needed for purchase init response.
- [BG-37898](https://jira.mgcorp.co/browse/BG-37898) - Added DTO for the failed billers endpoint.
- [BG-37925](https://jira.mgcorp.co/browse/BG-37925) - Added new common fraud service call for process using new credit card.
- [BG-37919](https://jira.mgcorp.co/browse/BG-37919) - Added new common fraud service call for init on join.
- [BG-37919](https://jira.mgcorp.co/browse/BG-37919) - Added new object fraudRecommendation at the response keeping the old fraud rules.
- [BG-38526](https://jira.mgcorp.co/browse/BG-38526) - Added specific biller implementation for Rocketgate and Netbilling.
- [BG-38164](https://jira.mgcorp.co/browse/BG-38164) - Added communication with Cascade Service.
### Updated
- [BG-38431](https://jira.mgcorp.co/browse/BG-38431) - Updated open API with nuData client ID for purchase init response.
- [BG-37894](https://jira.mgcorp.co/browse/BG-37894) - Updated open API purchase init request and the init command with payment method type and traffic source.
- [BG-38156](https://jira.mgcorp.co/browse/BG-38156) - Updated the cascade model.
- [BG-38796](https://jira.mgcorp.co/browse/BG-38796) - Transaction service client increased.

## [1.23.2] - 2020-02-07
### Added
- [BG-38109](https://jira.mgcorp.co/browse/BG-38109) - Add connection timeouts to service calls.

## [1.23.1] - 2020-02-06
### Added
- [BG-36698](https://jira.mgcorp.co/browse/BG-36698) - Retry mechanism for purchase processed events we fail to publish after purchase.

## [1.23.0] - 2020-02-03
### Added
- [BG-37891](https://jira.mgcorp.co/browse/BG-37891) - Added mock application layer to failed billers endpoint.
### Updated
- [BG-37899](https://jira.mgcorp.co/browse/BG-37899) - Updated the application layer of the failed billers endpoint layer and added new VO.

## [1.22.4] - 2020-01-27
### Fixed
- [BG-38099](https://jira.mgcorp.co/browse/BG-38099) - Fraud check on purchase process to consider the site configuration.  

## [1.22.3] - 2020-01-27
### Fixed
- [BG-38331](https://jira.mgcorp.co/browse/BG-38331) - Return the existent member id on secondary revenue purchase unsuccessful response instead a random one.

## [1.22.2] - 2020-01-22
### Fixed
- [BG-28705](https://jira.mgcorp.co/browse/BG-28705) - Older domain events were not converted properly to newest version.
- [BG-28705](https://jira.mgcorp.co/browse/BG-28705) - Aborted transactions are being ignored for legacy import.

## [1.22.1] - 2020-01-21
### Fixed
- [BG-38274](https://jira.mgcorp.co/browse/BG-38274) -  BusinessGroupUpdated event projection into database because the date for public key was stored as string instead of DateTimeImmutable object.

## [1.22.0] - 2020-01-20
### Added
- [BG-38204](https://jira.mgcorp.co/browse/BG-38204) - Payment with new card on netbilling secondary purchase.
- [BG-38241](https://jira.mgcorp.co/browse/BG-38241) - Associate cross sale with main sale membership for netbilling.
### Added
- [BG-37890](https://jira.mgcorp.co/browse/BG-37890) - Added new retrieve failed billers endpoint.

## [1.21.2] - 2020-01-16
### Fixed
- [BG-37458](https://jira.mgcorp.co/browse/BG-37458) - Get the biller name dynamically for sending email notification. 

## [1.21.1] - 2020-01-14
### Updated
- [BG-38182](https://jira.mgcorp.co/browse/BG-38182) - BusinessGroupSitesProjection supports the possibility to update private/public keys for a site.
### Fixed
- [BG-38222](https://jira.mgcorp.co/browse/BG-38222) - Update projection worker sleep cycle configuration to match the expected value of 5 minutes instead of 5 seconds. This avoids hitting the event store too often.

## [1.21.0] - 2020-01-13
### Added 
- [BG-37876](https://jira.mgcorp.co/browse/BG-37876) - Use bin routing for Netbilling purchases.
- [BG-36484](https://jira.mgcorp.co/browse/BG-36484) - Support Netbilling secondary purchases.
- [BG-37840](https://jira.mgcorp.co/browse/BG-37840) - CORS Whitelist new domains for Clips for Sale.

## [1.20.2] - 2020-01-09
### Added
- [BG-37494](https://jira.mgcorp.co/browse/BG-37494) - Exception handling on service bus consumers.

## [1.20.1] - 2020-01-09
### Fixed
- [BG-38145](https://jira.mgcorp.co/browse/BG-38145) - Revert composer dependencies on transaction and payment-template service.

## [1.20.0] - 2020-01-09
### Added
- [BG-33302](https://jira.mgcorp.co/browse/BG-33302) - Enable the synchronization from site admin service in purchase gateway.
### Fixed
- [BG-37984](https://jira.mgcorp.co/browse/BG-37984) - Update last modified date after each projectionist run.

## [1.19.3] - 2020-01-06
### Added
- [BG-37840](https://jira.mgcorp.co/browse/BG-37840) - Site Clips4Sale so that the purchases can be done successfully.

## [1.19.2] - 2019-12-18
### Updated
- [BG-37494](https://jira.mgcorp.co/browse/BG-37494) - Log messages structure for consistency.
- [BG-36696](https://jira.mgcorp.co/browse/BG-36696) - Create only one legacy integration event per purchase instead of one per item purchased.

## [1.19.1] - 2019-12-17
### Fixed
- [BG-37759](https://jira.mgcorp.co/browse/BG-37759) - Postback should not have crossSells key when main product failed.

## [1.19.0] - 2019-12-12
### Added
- [BG-37343](https://jira.mgcorp.co/browse/BG-37343) - Save payment template for Netbilling.
- [BG-37359](https://jira.mgcorp.co/browse/BG-37359) - Call bin routing service version 2 to retrieve bin codes for Netbilling.

## [1.18.1] - 2019-12-10
### Fixed
- [BG-37321](https://jira.mgcorp.co/browse/BG-37321) - Generate composer lock file in order to use bin routing client version 1.0.3.

## [1.18.0] - 2019-12-10
### Added 
- [BG-37317](https://jira.mgcorp.co/browse/BG-37317) - Netbilling – Initial and Free purchases support.

## [1.17.1] - 2019-12-10
### Fixed 
- [BG-37565](https://jira.mgcorp.co/browse/BG-37565) Digest generation for postback.

## [1.17.0] - 2019-12-10
### Added 
- [BG-36515](https://jira.mgcorp.co/browse/BG-36515) - Session id and transaction id on process postback.

## [1.16.18] - 2019-12-09
### Updated
- [BG-37323](https://jira.mgcorp.co/browse/BG-37323) - Allow new currencies other than USD for purchases using biller Rocketgate.

## [1.16.17] - 2019-12-04
### Updated
- [BG-37402](https://jira.mgcorp.co/browse/BG-37402) - Enable fraud check for Brazzer Network and Brazzers Premium.

## [1.16.16] - 2019-11-27
### Updated 
- [BG-37199](https://jira.mgcorp.co/browse/BG-37199) - Updating transaction-service-client to use the newest version with rebill start 550 days (before it was 365).

## [1.16.15] - 2019-11-22
### Updated 
- [BG-35385](https://jira.mgcorp.co/browse/BG-35385) - Logs handling (improvement to remove unnecessary logs).

## [1.16.14] - 2019-11-21
### Added
- [BG-37030](https://jira.mgcorp.co/browse/BG-37030) - Add atlas code decoded on BI event on init purchase and process purchase.

## [1.16.13] - 2019-11-21
### Fixed
- [BG-36873](https://jira.mgcorp.co/browse/BG-36873) - Fix services that have circuit break issue.

## [1.16.12] - 2019-11-20
### Updated
- [BG-36874](https://jira.mgcorp.co/browse/BG-36874) - Only attempt the cross sale once, with the same bin routing as the main item.

## [1.16.11] - 2019-11-20
### Updated
- [BG-36874](https://jira.mgcorp.co/browse/BG-36874) - Use the Bin routing advice that was successful with the main purchase with allñ cross sales.

## [1.16.10] - 2019-11-19
### Updated
- [BG-37027](https://jira.mgcorp.co/browse/BG-37027) - Allow username to contain underscore (_) character for new subscription.

## [1.16.9] - 2019-11-19
### Fixed
- Use latest version for payment-template-service-client.

## [1.16.8] - 2019-11-18
### Added
- [BG-36190](https://jira.mgcorp.co/browse/BG-36190) - RabbitMQ communication for the member profile update.

## [1.16.7] - 2019-11-14
### Fixed
- [BG-36919](https://jira.mgcorp.co/browse/BG-36919) - Take the bin from the payment information not the fraud advice.

## [1.16.6] - 2019-11-14
### Updated
- [BG-36919](https://jira.mgcorp.co/browse/BG-36919) - Disable Brazzers fraud check.

## [1.16.5] - 2019-11-14
### Added
- [BG-36035](https://jira.mgcorp.co/browse/BG-36035) - Expose field "card description" on retrieve transaction.
### Updated
- [BG-36919](https://jira.mgcorp.co/browse/BG-36919) - Disable fraud service for Brazzers.com


## [1.16.4] - 2019-11-13
### Updated
- [BG-36683](https://jira.mgcorp.co/browse/BG-36683) - Update username & password length to be [1, 60], Allow space in zip code.

## [1.16.3] - 2019-11-13
### Updated
- [BG-36683](https://jira.mgcorp.co/browse/BG-36683) - Allow normal space on first name and last name.

## [1.16.2] - 2019-11-13
### Added
- [BG-36190](https://jira.mgcorp.co/browse/BG-36190) - RabbitMQ communication for the legacy import.

## [1.16.1] - 2019-11-07
### Fixed
- [BG-36791](https://jira.mgcorp.co/browse/BG-36791) - Inverted billerTransactionId and transactionId on retrieve transaction endpoint.

## [1.16.0] - 2019-11-05
### Added 
- [BG-33947](https://jira.mgcorp.co/browse/BG-33947) - Added new properties for PurchaseProcessed BI event (process).

## [1.15.1] - 2019-11-01
### Fixed
- [BG-36190](https://jira.mgcorp.co/browse/BG-36190) - Retrieval of old events from event store.

## [1.15.0] - 2019-10-31
### Added 
- [BG-35563](https://jira.mgcorp.co/browse/BG-35563) - Added the retrieve transaction data by item id endpoint.
- [BG-36292](https://jira.mgcorp.co/browse/BG-36292) - Enable the bundle - addon validation.
- [BG-36107](https://jira.mgcorp.co/browse/BG-36107) - Site Premium Pornportal and its cross sale products so that the purchases can be done successfully.

## [1.14.1] - 2019-10-24
### Added 
- [BG-35284](https://jira.mgcorp.co/browse/BG-35284) - Sites Brazzers and Brazzers Premium, including the given products so that the purchases can be done successfuly.

## [1.14.0] - 2019-10-24
### Added
- [BG-33303](https://jira.mgcorp.co/browse/BG-33303) - Enable the synchronization from bundle admin management service in purchase gateway.
- [BG-35734](https://jira.mgcorp.co/browse/BG-35734) - Update last used date only if the payment template exists.

## [1.13.0] - 2019-10-22
### Added 
- [BG-35591](https://jira.mgcorp.co/browse/BG-35591) - Updated the open-api and created the mocked response for the retrieve transaction by item id endpoint.

## [1.12.4] - 2019-10-21
### Fixed
- [BG-36172](https://jira.mgcorp.co/browse/BG-36172) - Retrieving bin routing codes for sec rev.

## [1.12.3] - 2019-10-21
### Fixed
- [BG-36169](https://jira.mgcorp.co/browse/BG-36169) - Enable bin routing and fraud service for RK Premium site.

## [1.12.2] - 2019-10-16
### Fixed
- [BG-36047](https://jira.mgcorp.co/browse/BG-36047) - Acquisition of bin routing codes on NG.

## [1.12.1] - 2019-10-15
### Fixed
- [BG-34733](https://jira.mgcorp.co/browse/BG-34733) - Missing bearer auth for fraud service and enable fraud client logging.

## [1.12.0] - 2019-10-15
### Added
- [BG-33305](https://jira.mgcorp.co/browse/BG-33305) - SecRev support on process call.
- [BG-35149](https://jira.mgcorp.co/browse/BG-35149) - Full member information on Purchase Enriched integration event.

### Fixed
- [BG-35328](https://jira.mgcorp.co/browse/BG-35323) - Better handling of the communication with Biller Mapping and Payment Template service.

## [1.11.9] - 2019-10-02
### Fixed
- [BG-35515](https://jira.mgcorp.co/browse/BG-35515) - Prevent failure to generate event when no cross-sell transaction was returned.

## [1.11.8] - 2019-09-26
### Added 
- [BG-35385](https://jira.mgcorp.co/browse/BG-35385) - Purchase Gateway worker log to filebeat stage, pre-prod and production.

## [1.11.7] - 2019-09-26
### Fixed
- [BG-34555](https://jira.mgcorp.co/browse/BG-34555) - Handle the missing null values and missing rebill data on generation of domain events. 

## [1.11.6]
### Fixed
- [BG-35343](https://jira.mgcorp.co/browse/BG-35343) - Handle unsupported payment type to return bad request instead of internal server error.

## [1.11.5] - 2019-09-19
### Changed
- [BG-35356](https://jira.mgcorp.co/browse/BG-35356) - Email from support@probiller.com to welcome@probiller.com.

## [1.11.4] - 2019-09-19
### Fixed
- [BG-35328](https://jira.mgcorp.co/browse/BG-35328) - Prefix “tel” and “mailto” email notification support data to have them as link.

### Updated
- [BG-35328](https://jira.mgcorp.co/browse/BG-35328) - Mail support address to be “support@probiller.com”.

## [1.11.3] - 2019-09-18
### Fixed
- [BG-35328](https://jira.mgcorp.co/browse/BG-35328) - “Transaction confirmation” email to have “mail sender email” as “support@probiller.com” and “mail sender name” as “Probiller”.

## [1.11.2] - 2019-09-18
### Fixed
- [BG-35286](https://jira.mgcorp.co/browse/BG-35286) - The email is not sent without tax info.

## [1.11.1] - 2019-09-17
### Removed
- [BG-35170](https://jira.mgcorp.co/browse/BG-35170) - Backward compatibility to send email notification.

### Added
- [BG-35221](https://jira.mgcorp.co/browse/BG-35221) - Tax type validation to accept only string values.

## [1.11.0] - 2019-09-12
### Added
- [BG-33304](https://jira.mgcorp.co/browse/BG-33304) - SecRev support on init call.

## [1.10.0] - 2019-09-10
### Added
- [BG-34703](https://jira.mgcorp.co/browse/BG-34703) - Communication to Purchase Advice Notification Service whether to send Email or Not to customer’s purchase.
- [BG-34702](https://jira.mgcorp.co/browse/BG-34702) - Tax type field to tax information.

## [1.9.7] - 2019-09-02
### Fixed
- Fixed broken healthCheck.

## [1.9.6] - 2019-08-29
### Added
- [BG-31780](https://jira.mgcorp.co/browse/BG-31780) - Session Id validation on the url so that it does not break the filebeat config.

## [1.9.5] - 2019-08-19
### Fixed
- [BG-34554](https://jira.mgcorp.co/browse/BG-34554) - The detection of cross sales selection while we are not receiving the correct site id for the cross sales.

## [1.9.4] - 2019-08-16
### Fixed
- [BG-34554](https://jira.mgcorp.co/browse/BG-34554) - Hard coding the cross sale site to RKPremium until the proper one is passed on the request.

## [1.9.3] - 2019-08-14
### Fixed
- [BG-34513](https://jira.mgcorp.co/browse/BG-34513) - As a developer, I want to add a fallback for siteId for cross sales if we do not receive it on the purchase request.

## [1.9.2] - 2019-08-14
### Fixed
- [BG-34502](https://jira.mgcorp.co/browse/BG-34506) - Rebill amount validation & toArray on BinRouting VO.

## [1.9.1] - 2019-08-14
### Fixed
- [BG-34502](https://jira.mgcorp.co/browse/BG-33306) - Returned phone number, address and city back to optional.

## [1.9.0] - 2019-08-14
### Changed
- [BG-33306](https://jira.mgcorp.co/browse/BG-33306) - Prepare Purchase Gateway so it allows easier integration of the Secondary Revenue Requirements.

## [1.8.3] - 2019-08-12
### Updated
- [BG-33741](https://jira.mgcorp.co/browse/BG-33741) - Composer dependencies.

## [1.8.2] - 2019-08-08
### Changed
- [BG-34456](https://jira.mgcorp.co/browse/BG-34456) - Tax initial amount is mandatory when tax payload is present.

## [1.8.1] - 2019-08-07
### Changed
- [BG-33927](https://jira.mgcorp.co/browse/BG-33927) - Tax breakdown exposition in event retrieval and new custom field in tax payload.

## [1.8.0] - 2019-08-05
### Removed
- [BG-32710](https://jira.mgcorp.co/browse/BG-32710) - Price id.

## [1.7.8] - 2019-07-29
### Updated
- [BG-33771](https://jira.mgcorp.co/browse/BG-33771) - Biller mapping service in order to have the correct docker network.

## [1.7.7] - 2019-07-25
### Fixed
- [BG-33759](https://jira.mgcorp.co/browse/BG-33759) - We need to be able to make a purchase of a product that does not have a cross sell.

## [1.7.6] - 2019-07-24
### Fixed 
- [BG-33402](https://jira.mgcorp.co/browse/BG-33402) - Bin routing now properly sends bin-routing codes to transaction.

### Updated
- [BG-33799](https://jira.mgcorp.co/browse/BG-33799) - Email Service environment variables, so that we are in sync with common-services requirements.

## [1.7.5] - 2019-07-22
### Changed
- [BG-33840](https://jira.mgcorp.co/browse/BG-33840) - Debug log tag to info on worker.
  
## [1.7.4] - 2019-07-17
### Added
- [BG-33433](https://jira.mgcorp.co/browse/BG-33433) - Configuration for site marketplace.com and new product 10 days recurring.

## [1.7.3] - 2019-07-17
### Added
- [BG-33086](https://jira.mgcorp.co/browse/BG-33086) - Validate after taxes amounts.

## [1.7.2] - 2019-07-16
### Fixed
- [BG-33661](https://jira.mgcorp.co/browse/BG-33661) - Multiple cross sales validation between init and process and use amount key instead of transactionAmount for cross-sale process.

## [1.7.1] - 2019-07-15
### Updated
- [BG-33457](https://jira.mgcorp.co/browse/BG-33457) - Tax keys for integration events.

## [1.7.0] - 2019-07-09
### Changed
- [BG-32441](https://jira.mgcorp.co/browse/BG-32441) - Refactored vo's related to the process.
- [BG-32901](https://jira.mgcorp.co/browse/BG-32901) - Rename price biller mapping to biller mapping.

### Added
- [BG-31406](https://jira.mgcorp.co/browse/BG-31406) - Create Payment Template events.

## [1.6.2] - 2019-07-08
### Added
- [BG-33321](https://jira.mgcorp.co/browse/BG-33321) - New product for Reality Kings: Free membership for 7 days and 19.99 monthly recurring.

## [1.6.1] - 2019-07-04
### Added
- [BG-33137](https://jira.mgcorp.co/browse/BG-33137) - New product for Reality Kings: Free membership for 7 days and 14.99 monthly recurring.

## [1.6.0] - 2019-07-03
### Added
- [BG-32866](https://jira.mgcorp.co/browse/BG-32866) - Enable Email for Tax Support.

## [1.5.2] - 2019-07-02
### Changed
- Revert cross-sale validation.

### Updated
- Fallback repository data.

### Added
- The following fields on integration-events api doc endpoint: tax, purchase_id, is_trial, is_unlimited, is_disabled, is_expired, is_nsf, is_prepaid, is_low_risk, require_active_content.
  
## [1.5.1] - 2019-06-27
### Fixed
- New cross sales received.

## [1.5.0] - 2019-06-26
### Added
- [BG-31787](https://jira.mgcorp.co/browse/BG-31787) - Tax Integration Support.

## [1.4.3] - 2019-06-17
### Updated
- [BG-31124](https://jira.mgcorp.co/browse/BG-31124) – Stringify token to ensure it will not be logged as object on the response headers.

## [1.4.2] - 2019-06-13
### Added
- [BG-32691](https://jira.mgcorp.co/browse/BG-32691) - New products for P1 to increase traffic.

## [1.4.1] - 2019-06-06
### Added
- [BG-31748](https://jira.mgcorp.co/browse/BG-31748) - Validations on user information.

### Changed
- [BG-31005](https://jira.mgcorp.co/browse/BG-31005) – Strip bad characters before calling the fraud service.

## [1.4.0] - 2019-05-16
### Added
- [BG-30147](https://jira.mgcorp.co/browse/BG-30147) – Purchase Bundle and support for Member Profile.

## [1.3.0] - 2019-05-13
### Added
- [BG-30986](https://jira.mgcorp.co/browse/BG-30986) - Free sale for joins.

## [1.2.2] - 2019-05-06 
### Added
- [BG-31631](https://jira.mgcorp.co/browse/BG-31631) - Enable CORS.

### Fixed
- [BG-29588](https://jira.mgcorp.co/browse/BG-29588) - "Upgrade to Lumen 5.8, PHPUnit 8 and vfsstream".

## [1.2.1] - 2019-04-29
### Fixed
- [BG-30392](https://jira.mgcorp.co/browse/BG-30392) - Handle Internal Server Errors so that the request does not fail.

## [1.2.0] - 2019-04-24
### Added
- [BG-29907](https://jira.mgcorp.co/browse/BG-29907) - PTS integration (turned off).

## [1.1.1] - 2019-04-23
### Fixed
- [BG-30592](https://jira.mgcorp.co/browse/BG-30592) - Handle token expired validation to return http error code 504.

## [1.1.0] - 2019-04-16
### Removed
- Unused EventStore methods.

### Updated
- Auth token validation in the process call.
- Supervisor config command signatures.

### Added
- [BG-30056](https://jira.mgcorp.co/browse/BG-30056) - Session version conversion.
- [BG-30194](https://jira.mgcorp.co/browse/BG-30194) - Item id on process purchase event.

## [1.0.18] - 2019-04-15
### Fixed
- [BG-30691](https://jira.mgcorp.co/browse/BG-30391) - Obfuscating sensitive data on Domain Events.

## [1.0.17] - 2019-04-11
### Fixed
- [BG-30398](https://jira.mgcorp.co/browse/BG-30398) - Subscription details should be null when cross sale transaction status is declined.

## [1.0.16] - 2019-04-02
### Added
- [BG-30292](https://jira.mgcorp.co/browse/BG-30292) - TrimStrings middleware and alpha spaces validation for member firstName and lastName.

## [1.0.15] - 2019-04-01
### Fixed
- Using same exception handler similar to other projects.

## [1.0.14] - 2019-03-11
### Fixed
- [BG-29901](https://jira.mgcorp.co/browse/BG-29901) - Using a package to deal with cors allowing just our domains (whitelist) and Cors just allowed for purchase process.

## [1.0.13] - 2019-03-06
### Fixed
- [BG-29908](https://jira.mgcorp.co/browse/BG-29908) - The "Allow headers" tag in cors configuration was specified to work in the firefox browser.

## [1.0.12] - 2019-03-05
### Added
- Worker that creates integration event now waits one second if there are no events to create before retrying.
- Sleep inside cleanup command in order to prevent processor overload.

## [1.0.11] - 2019-03-01
### Fixed
- Postback url.

## [1.0.10] - 2019-02-28
### Added
- Docker volume for mysql container.

### Removed
- Member password from request logging.

## [1.0.9] - 2019-02-28
### Fixed
- [BG-29563](https://jira.mgcorp.co/browse/BG-29563) - Obfuscated credit card information from the logs.

### Added
- P1 official postback url.

## [1.0.8] - 2019-02-27
### Fixed
- Temporary solution to make the digest use the public key instead of the private one.

## [1.0.7] - 2019-02-27
### Changed
- The failed jobs cleanup command to be an endless process.

## [1.0.6] - 2019-02-27
### Added
- We can instruct NGLogger middleware to not log requests. (See Header ignore-log: true).

## [1.0.5] - 2019-02-27
- Initial release.

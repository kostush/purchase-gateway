# Common Fraud Service
## Testing configurarion
Url to access: https://fraud-testing.mg.services/admin/rule-management


| Rule             | Event           | Ip                        | Bin    | Email                                                                  |
|------------------|-----------------|---------------------------|--------|------------------------------------------------------------------------|
| Blacklist        | InitCustomer    | 0.0.1.0, 1.2.3.4          |        | blacklisted-1@test.mindgeek.com, blacklisted@test.mindgeek.com |
| Captcha_List     | InitCustomer    | 0.1.0.0, 1.1.0.0          |        | captcha-1@test.mindgeek.com                                     |
| 3DS_Trigger_List | InitCustomer    | 1.0.0.0, 1.1.0.0, 5.0.0.0 |        | 3ds-1@test.mindgeek.com                                         |
| Blacklist        | InitVisitor     | 0.0.2.0, 1.2.3.4          |        |                                                                        |
| Captcha_List     | InitVisitor     | 0.2.0.0, 2.2.0.0          |        |                                                                        |
| 3DS_Trigger_List | InitVisitor     | 2.0.0.0, 2.2.0.0, 5.0.0.0 |        |                                                                        |
| Blacklist        | ProcessCustomer | 0.0.3.0, 1.2.3.4          |        | blacklisted-3@test.mindgeek.com, blacklisted@test.mindgeek.com |
| Captcha_List     | ProcessCustomer | 0.3.0.0, 3.3.0.0          |        | captcha-3@test.mindgeek.com                                     |
| 3DS_Trigger_List | ProcessCustomer | 3.0.0.0, 3.3.0.0, 5.0.0.0 | 492182 | 3ds-3@test.mindgeek.com                                         |
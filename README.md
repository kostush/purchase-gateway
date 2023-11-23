### Purchase Gateway

https://wiki.mgcorp.co/display/PROBILLER/Purchase+Gateway

## Project dependencies
* [Billing Gateway](https://stash.mgcorp.co/projects/PROB/repos/billing-gateway/browse/Readme.md)
* [Logger](https://stash.mgcorp.co/projects/pbngbe/repos/logger/browse/README.md)
* [Fraud Service](https://stash.mgcorp.co/projects/pbngbe/repos/fraud-service/browse/README.md)
* [Bin Routing Service](https://stash.mgcorp.co/projects/pbngbe/repos/bin-routing-service/browse/README.md)
* [Biller Mapping Service](https://stash.mgcorp.co/projects/pbngbe/repos/biller-mapping-service/browse/README.md)
* [Transaction Service](https://stash.mgcorp.co/projects/pbngbe/repos/transaction-service/browse/README.md)

## Get started

### Prerequisites
* [Docker](https://stash.mgcorp.co/projects/PBNGBE/repos/init/browse/DOCKER-SETTINGS.md)
* CLI (Command-line interpreter) that supports shell script execution.

### Steps
1. Checkout [this project](ssh://git@stash.mgcorp.co:7999/pbngbe/purchase-gateway.git).
2. Execute `sh init.sh`

Check [here](https://wiki.mgcorp.co/display/PROBILLER/Developer) for more information regarding setting up your development environment and overall development practices check.

## FAQ

1. How to stop the service?

    *Execute `docker-compose stop`*

2. How to start the service?

    *Execute `docker-compose up -d`*

3. How to go inside the service container?

    *Execute `docker-compose exec web bash`*

4. How to test the service?

    *Install [Postman](https://www.getpostman.com)*, then use the collections available [here](postman-collections/).

5. How to run composer:

    *Execute `composer install` into the container*

6. How I can access the service?

    *You will be able to access using http://localhost:8008.*

7. How to seed the data for first use?

    *Execute `php lumen/artisan doctrine:seed` into the container* 

## Available Operations
- Purchase init
- Process purchase

## Operations
#### Purchase init
###### Request
```
{  
   "siteId":"8e34c94e-135f-4acb-9141-58b3a6e56c74",
   "bundleId":"5fd44440-2956-11e9-b210-d663bd873d93",
   "addonId":"670af402-2956-11e9-b210-d663bd873d93",
   "currency":"USD",
   "clientIp":"10.10.109.185",
   "paymentType":"cc",
   "clientCountryCode":"CA",
   "amount":29.99,
   "initialDays":7,
   "rebillDays":30,
   "rebillAmount":29.99,
   "atlasCode":"NDU1MDk1OjQ4OjE0Nw",
   "atlasData":"atlas data example",
   "isTrial": false,
   "tax":{  
      "initialAmount":{  
         "beforeTaxes":28.56,
         "taxes":1.43,
         "afterTaxes":29.99
      },
      "rebillAmount":{  
         "beforeTaxes":28.56,
         "taxes":1.43,
         "afterTaxes":29.99
      },
      "taxApplicationId":"60bf5bcb-ac64-496c-acc5-9c7cf54a1869",
      "taxName":"VAT",
      "taxRate":0.05
   },
   "crossSellOptions":[  
      {
         "bundleId":"4475820e-2956-11e9-b210-d663bd873d93",
         "addonId":"4e1b0d7e-2956-11e9-b210-d663bd873d93",
         "siteId":"4c22fba2-f883-11e8-8eb2-f2801f1b9fd1",
         "initialDays":3,
         "rebillDays":30,
         "amount":1.00,
         "rebillAmount":34.97,
         "isTrial": false,
         "tax":{  
            "initialAmount":{  
               "beforeTaxes":0.95,
               "taxes":0.05,
               "afterTaxes":1.00
            },
            "rebillAmount":{  
               "beforeTaxes":33.30,
               "taxes":1.67,
               "afterTaxes":34.97
            },
            "taxApplicationId":"60bf5bcb-ac64-496c-acc5-9c7cf54a1869",
            "taxName":"VAT",
            "taxRate": 0.05
         }
      }
   ]
}
```
###### Response
```
{
    "sessionId": "23e6d035-fe5d-4b10-999c-397be6f23628",
    "paymentProcessorType": "gateway",
    "fraudAdvice": {
        "captcha": false,
        "blacklist": false
    }
}
```
Also in the response header can be found the authentication token

#### Process purchase
###### Request
```
{   
	"siteId": "8e34c94e-135f-4acb-9141-58b3a6e56c74",
    "member": {
    	"email": "test@captcha.com",
		"username": "user123",
		"password": "secretPassword",
		"firstName": "Mister",
		"lastName": "Axe",
		"countryCode": "CA",
		"zipCode": "H0H0H0",
		"address1": "123 Random Street",
		"address2": "Hello Boulevard",
		"city": "Montreal",
		"phone": "514-000-0911"
    },
    "payment":  {
	    "ccNumber": "4532777777796550",
	    "cvv": 123,
	    "cardExpirationMonth": "5",
	    "cardExpirationYear": "2020"
	  },
    "selectedCrossSells": []
}
```
###### Response
```
{  
   "success":true,
   "purchaseId":"363b97ae-7a67-41bf-bb7a-b6e6a27b45b5",
   "memberId":"e3d2b250-f977-4d6a-b975-9000ebb2c5a5",
   "bundleId":"5fd44440-2956-11e9-b210-d663bd873d93",
   "addonId":"670af402-2956-11e9-b210-d663bd873d93",
   "subscriptionId":"3f771626-0025-491e-9ded-4adb00629665",
   "crossSells":[  
      {  
         "success":true,
         "bundleId":"4475820e-2956-11e9-b210-d663bd873d93",
         "addonId":"4e1b0d7e-2956-11e9-b210-d663bd873d93",
         "subscriptionId":"c2755d17-70ca-4cce-8bc8-3b116aaba75c"
      }
   ],
   "digest":"....."
}
```

## Logs
This section explains the logging structure expected and presents the related configuration.

#### Application Logs
Any information that needs to be captured or is of some interest in future needs to be logged. These type of logs constitute application logs.

Example: Request to the application, various steps performed during execution, response returned by the application.

To support this, we added Filebeat/Elasticsearch/Kibana modules to gather and visualize such logs. 

_Related docker services:_
- elasticsearch
- filebeat
- kibana

_Logs directory:_
- /lumen/storage/logs

_Log file format_
- \<file name>.log

To view logs in Kibana, go to `http://localhost:5601` and switch to `Logs` tab.

#### Business Intelligence Event Logs
Certain specific information needs to be returned to BI (related to our processing) as requested by them. As the consumption of these events would be in Kafka, we needed to pass such events to Kafka broker.

To support this, we added Filebeat/Kafka modules to gather and visualize such events.

_Related docker services:_
- filebeat
- kafka

_Event logs directory:_
- /lumen/storage/events

_Event log file format_
- \<file name>.log

To view event logs, go to `http://localhost:3030/kafka-topics-ui/` and check for _topic-\<year.month.day>_ topic.

#### Common Services 
##### 1.RabbitMq
See [documentation](https://stash.mgcorp.co/projects/PBNGBE/repos/common-serivices/browse) for usage
```
https://stash.mgcorp.co/scm/pbngbe/common-services.git
```

## Generate the client
### Requeriments
- Java JRE. https://java.com/en/download/manual.jsp;
- The `purchase-gateway` and `purchase-gateway-client` folders have to be on the same level.
### Windows
```
cd <workspace-folder>\purchase-gateway-client\docs
generate-client.cmd
```
It will download the openapi-generator-cli.jar, in case it is missing.

You should see something like:
```
openapi-generator-cli installed version:
3.3.4
Executing:
java -jar openapi-generator-cli.jar generate -c config.json -i openapi.yml -g php -o ../../purchase-gateway-client/
[main] INFO  o.o.c.languages.AbstractPhpCodegen - Environment variable PHP_POST_PROCESS_FILE not defined so the PHP code may not be properly formatted. To define it, try 'export PHP_POST_PROCESS_FILE="/usr/local/bin/prettier --write"' (Linux/Mac)
[main] INFO  o.o.c.languages.AbstractPhpCodegen - NOTE: To enable file post-processing, 'enablePostProcessFile' must be set to `true` (--enable-post-process-file for CLI).
...
```

Read more: https://wiki.mgcorp.co/display/PE/Generating+Client+Code+from+Open+API

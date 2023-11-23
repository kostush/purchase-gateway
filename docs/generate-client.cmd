@echo off

SET _service_name=purchase-gateway
SET _openapi_version=3.3.4

:: Download the openapi-generator-cli, if it doesn't exist
if not exist openapi-generator-cli.jar (
    ECHO openapi-generator-cli does not exist. Downloading...
    powershell -Command "Invoke-WebRequest -OutFile openapi-generator-cli.jar https://repo1.maven.org/maven2/org/openapitools/openapi-generator-cli/%_openapi_version%/openapi-generator-cli-%_openapi_version%.jar"
)
ECHO openapi-generator-cli installed version:
java -jar openapi-generator-cli.jar version

:: Setting paths
SET _config_path=config.json
SET _docs_path=openapi.yml
SET _client_generation_path=..\..\%_service_name%-client\

:: Generate the client
ECHO Executing:
ECHO java -jar openapi-generator-cli.jar generate -c %_config_path% -i %_docs_path% -g php -o %_client_generation_path%
java -jar openapi-generator-cli.jar generate -c %_config_path% -i %_docs_path% -g php -o %_client_generation_path%

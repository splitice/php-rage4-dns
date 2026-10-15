<?php

namespace Splitice\Rage4 {
    /**
     * Deterministic replacement for the cURL runtime used by Rage4ApiClient.
     */
    final class CurlRuntime
    {
        /** @var string|false */
        public static $result = '{}';

        /** @var int */
        public static $status = 200;

        /** @var string */
        public static $error = '';

        /** @var array<int, mixed> */
        public static $options = array();

        /**
         * Restore the fake transport to a successful empty response.
         *
         * @return void
         */
        public static function reset()
        {
            self::$result = '{}';
            self::$status = 200;
            self::$error = '';
            self::$options = array();
        }
    }

    /**
     * @return resource
     */
    function curl_init()
    {
        return fopen('php://memory', 'r');
    }

    /**
     * @param resource $handle
     * @param int $option
     * @param mixed $value
     * @return bool
     */
    function curl_setopt($handle, $option, $value)
    {
        CurlRuntime::$options[$option] = $value;
        return true;
    }

    /**
     * @param resource $handle
     * @return string|false
     */
    function curl_exec($handle)
    {
        return CurlRuntime::$result;
    }

    /**
     * @param resource $handle
     * @param int $option
     * @return int
     */
    function curl_getinfo($handle, $option)
    {
        return CurlRuntime::$status;
    }

    /**
     * @param resource $handle
     * @return string
     */
    function curl_error($handle)
    {
        return CurlRuntime::$error;
    }
}

namespace {
    use PHPUnit\Framework\TestCase;
    use Splitice\Rage4\CurlRuntime;
    use Splitice\Rage4\Rage4ApiClient;
    use Splitice\Rage4\Rage4Exception;

    final class Rage4ApiClientTest extends TestCase
    {
        /**
         * @return void
         */
        protected function setUp(): void
        {
            CurlRuntime::reset();
        }

        /**
         * @dataProvider invalidCredentialProvider
         *
         * @param string $username
         * @param string $password
         * @return void
         */
        public function testCredentialsAreRequired($username, $password)
        {
            $this->expectException(Rage4Exception::class);
            $this->expectExceptionMessage('Username and Password cannot be empty!');

            new Rage4ApiClient($username, $password);
        }

        /**
         * @return array<string, array<int, string>>
         */
        public static function invalidCredentialProvider()
        {
            return array(
                'missing username' => array('', 'secret'),
                'missing password' => array('user@example.com', ''),
            );
        }

        /**
         * @return void
         */
        public function testCredentialsConfigureAuthenticatedRequests()
        {
            new Rage4ApiClient('user@example.com', 'account-key');

            $this->assertSame(CURLAUTH_BASIC, CurlRuntime::$options[CURLOPT_HTTPAUTH]);
            $this->assertSame('user@example.com:account-key', CurlRuntime::$options[CURLOPT_USERPWD]);
            $this->assertTrue(CurlRuntime::$options[CURLOPT_RETURNTRANSFER]);
            $this->assertFalse(CurlRuntime::$options[CURLOPT_SSL_VERIFYPEER]);
        }

        /**
         * @dataProvider queryProvider
         *
         * @param array<string, mixed> $query
         * @return void
         */
        public function testQueryStringsRoundTripWithoutLosingValues(array $query)
        {
            $client = new Rage4ApiClient('user@example.com', 'account-key');

            parse_str($client->buildQueryString($query), $decoded);
            $expected = array_map(function ($value) {
                return $value === null ? '' : (string) $value;
            }, $query);

            $this->assertSame($expected, $decoded);
        }

        /**
         * @return array<string, array<int, array<string, mixed>>>
         */
        public static function queryProvider()
        {
            return array(
                'null and zero' => array(array('nullable' => null, 'zero' => 0)),
                'realistic DNS data' => array(array(
                    'name' => '_acme-challenge.example.com',
                    'content' => 'token with + / = symbols',
                    'ttl' => 300,
                )),
                'unicode' => array(array('label' => 'münchen', 'enabled' => true)),
            );
        }

        /**
         * @return void
         */
        public function testSuccessfulResponseIsDecodedAndRequestUrlIsEncoded()
        {
            CurlRuntime::$result = '{"status":true,"id":42}';
            $client = new Rage4ApiClient('user@example.com', 'account-key');

            $result = $client->executeApi('CreateRecord', array(
                'name' => 'www.example.com',
                'content' => 'value with spaces',
            ));

            $this->assertSame(array('status' => true, 'id' => 42), $result);
            $this->assertSame(
                'https://secure.rage4.com/rapi/CreateRecord/?name=www.example.com&content=value+with+spaces',
                CurlRuntime::$options[CURLOPT_URL]
            );
            $this->assertSame(
                array('Connection: keep-alive', 'Keep-Alive: 300'),
                CurlRuntime::$options[CURLOPT_HTTPHEADER]
            );
        }

        /**
         * @return void
         */
        public function testSuccessfulRequestWithoutParametersHasNoQueryDelimiter()
        {
            CurlRuntime::$result = '[]';
            $client = new Rage4ApiClient('user@example.com', 'account-key');

            $this->assertSame(array(), $client->executeApi('GetDomains'));
            $this->assertSame(
                'https://secure.rage4.com/rapi/GetDomains/',
                CurlRuntime::$options[CURLOPT_URL]
            );
        }

        /**
         * @return void
         */
        public function testTransportFailureIncludesTheCurlError()
        {
            CurlRuntime::$result = false;
            CurlRuntime::$error = 'connection timed out';
            $client = new Rage4ApiClient('user@example.com', 'account-key');

            $this->expectException(Rage4Exception::class);
            $this->expectExceptionMessage('Unable to communicate with Rage4 API: connection timed out');

            $client->executeApi('GetDomains');
        }

        /**
         * @return void
         */
        public function testBadRequestExposesStructuredApiError()
        {
            CurlRuntime::$status = 400;
            CurlRuntime::$result = '{"error":{"name":"is invalid"}}';
            $client = new Rage4ApiClient('user@example.com', 'account-key');

            try {
                $client->executeApi('CreateRegularDomain');
                $this->fail('Expected the bad request to throw.');
            } catch (Rage4Exception $exception) {
                $this->assertSame(400, $exception->getCode());
                $this->assertStringContainsString('is invalid', $exception->getMessage());
                $this->assertSame(array('error' => array('name' => 'is invalid')), $exception->getApidata());
            }
        }

        /**
         * @dataProvider httpErrorProvider
         *
         * @param int $status
         * @param string $body
         * @param mixed $apiData
         * @return void
         */
        public function testOtherHttpErrorsReportStatusAndAvailableData($status, $body, $apiData)
        {
            CurlRuntime::$status = $status;
            CurlRuntime::$result = $body;
            $client = new Rage4ApiClient('user@example.com', 'account-key');

            try {
                $client->executeApi('GetDomains');
                $this->fail('Expected the HTTP error to throw.');
            } catch (Rage4Exception $exception) {
                $this->assertSame($status, $exception->getCode());
                $this->assertSame($apiData, $exception->getApidata());
            }
        }

        /**
         * @return array<string, array<int, mixed>>
         */
        public static function httpErrorProvider()
        {
            return array(
                'bad request without error details' => array(400, '{}', array()),
                'service unavailable with JSON body' => array(503, '{"retry":true}', array('retry' => true)),
                'malformed error body' => array(502, '<html>bad gateway</html>', null),
            );
        }

        /**
         * @dataProvider invalidSuccessBodyProvider
         *
         * @param string $body
         * @return void
         */
        public function testSuccessfulStatusStillRequiresAJsonObjectOrArray($body)
        {
            CurlRuntime::$result = $body;
            $client = new Rage4ApiClient('user@example.com', 'account-key');

            $this->expectException(Rage4Exception::class);
            $this->expectExceptionMessage('Invalid response JSON from Rage4 API');

            $client->executeApi('GetDomains');
        }

        /**
         * @return array<string, array<int, string>>
         */
        public static function invalidSuccessBodyProvider()
        {
            return array(
                'malformed JSON' => array('{'),
                'JSON scalar' => array('true'),
            );
        }
    }
}

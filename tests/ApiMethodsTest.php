<?php
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Splitice\Rage4\IRage4ApiClient;
use Splitice\Rage4\Rage4Api;
use Splitice\Rage4\Rage4ApiClient;
use Splitice\Rage4\Rage4Exception;

class ApiMethodsTest extends TestCase
{
    public function testConstructorAcceptsAnInjectedClient()
    {
        $client = $this->createMock(IRage4ApiClient::class);
        $api = new Rage4Api($client);

        $this->assertSame($client, $this->readClient($api));
    }

    public function testConstructorCreatesTheDefaultClientFromCredentials()
    {
        $api = new Rage4Api('user@example.com', 'account-key');

        $this->assertInstanceOf(Rage4ApiClient::class, $this->readClient($api));
    }

    /**
     * @dataProvider successfulOperationProvider
     *
     * @param string $method
     * @param array<int, mixed> $arguments
     * @param string $endpoint
     * @param array<string, mixed> $query
     * @param array<int|string, mixed> $response
     * @param mixed $expected
     */
    public function testSuccessfulOperationsUseExpectedApiContract(
        $method,
        array $arguments,
        $endpoint,
        array $query,
        array $response,
        $expected
    ) {
        $client = $this->expectApiCall($endpoint, $query, $response);
        $api = new Rage4Api($client);

        $this->assertSame($expected, call_user_func_array(array($api, $method), $arguments));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function successfulOperationProvider()
    {
        $domains = array(array('id' => 1, 'name' => 'example.com'));
        $domain = array('id' => 1, 'name' => 'example.com');
        $record = array('id' => 9, 'name' => 'www.example.com');

        return array(
            'get domains' => array(
                'getDomains',
                array(),
                'GetDomains',
                array(),
                $domains,
                $domains,
            ),
            'create domain with custom nameserver prefix' => array(
                'createDomain',
                array('example.com', 'hostmaster@example.com', 'dns.example.net', 'edge'),
                'CreateRegularDomain',
                array(
                    'name' => 'example.com',
                    'email' => 'hostmaster@example.com',
                    'ns1' => 'edge1.dns.example.net',
                    'ns2' => 'edge2.dns.example.net',
                ),
                array('status' => true, 'id' => 1),
                array('status' => true, 'id' => 1),
            ),
            'create reverse IPv4 domain' => array(
                'createReverseDomain4',
                array('2.0.192.in-addr.arpa', 'hostmaster@example.com', 24),
                'createreversedomain4',
                array(
                    'name' => '2.0.192.in-addr.arpa',
                    'email' => 'hostmaster@example.com',
                    'subnet' => 24,
                ),
                array('status' => true, 'id' => 2),
                array('status' => true, 'id' => 2),
            ),
            'get domain by name' => array(
                'getDomainByName',
                array('example.com'),
                'GetDomainByName',
                array('name' => 'example.com'),
                $domain,
                $domain,
            ),
            'get domain by id with integer coercion' => array(
                'getDomain',
                array('42'),
                'GetDomain',
                array('id' => 42),
                $domain,
                $domain,
            ),
            'create reverse IPv6 domain' => array(
                'createReverseDomain6',
                array('8.b.d.0.1.0.0.2.ip6.arpa', 'hostmaster@example.com', 48),
                'createreversedomain6',
                array(
                    'name' => '8.b.d.0.1.0.0.2.ip6.arpa',
                    'email' => 'hostmaster@example.com',
                    'subnet' => 48,
                ),
                array('status' => true, 'id' => 3),
                array('status' => true, 'id' => 3),
            ),
            'delete domain coerces status to boolean' => array(
                'deleteDomain',
                array('42'),
                'DeleteDomain',
                array('id' => 42),
                array('status' => 1),
                true,
            ),
            'import domain coerces status to boolean' => array(
                'importDomain',
                array('example.com'),
                'ImportDomain',
                array('name' => 'example.com'),
                array('status' => 0),
                false,
            ),
            'get records with integer coercion' => array(
                'getRecords',
                array('42'),
                'GetRecords',
                array('id' => 42),
                array($record),
                array($record),
            ),
            'get geographical regions' => array(
                'getGeoRegions',
                array(),
                'ListGeoRegions',
                array(),
                array(array('key' => 'EU', 'value' => 'Europe')),
                array(array('key' => 'EU', 'value' => 'Europe')),
            ),
            'get record types maps keys to values' => array(
                'getRecordTypes',
                array(),
                'ListRecordTypes',
                array(),
                array(
                    array('key' => 'A', 'value' => 1),
                    array('key' => 'AAAA', 'value' => 2),
                ),
                array('A' => 1, 'AAAA' => 2),
            ),
            'create record with all supported options' => array(
                'createRecord',
                array(42, 'www.example.com', '192.0.2.1', 'A', '10', true, '192.0.2.2', 300, '-1', '51.5', '-0.12', false),
                'CreateRecord',
                array(
                    'name' => 'www.example.com',
                    'content' => '192.0.2.1',
                    'type' => 'A',
                    'failover' => 'true',
                    'failovercontent' => '192.0.2.2',
                    'ttl' => 300,
                    'geozone' => -1,
                    'priority' => 10,
                    'geolock' => 'false',
                    'geolat' => 51.5,
                    'geolong' => -0.12,
                    'id' => 42,
                ),
                array('status' => true, 'id' => 9),
                array('status' => true, 'id' => 9),
            ),
            'update record normalizes nonnumeric geozone' => array(
                'updateRecord',
                array(9, 'www.example.com', '192.0.2.1', '20', true, '192.0.2.2', 600, 'invalid', '51.5', '-0.12'),
                'UpdateRecord',
                array(
                    'name' => 'www.example.com',
                    'content' => '192.0.2.1',
                    'failover' => 'true',
                    'ttl' => 600,
                    'geozone' => 0,
                    'failovercontent' => '192.0.2.2',
                    'priority' => 20,
                    'geolat' => 51.5,
                    'geolong' => -0.12,
                    'id' => 9,
                ),
                array('status' => true, 'id' => 9),
                array('status' => true, 'id' => 9),
            ),
            'delete record returns status without coercion' => array(
                'deleteRecord',
                array('9'),
                'DeleteRecord',
                array('id' => 9),
                array('status' => 'queued'),
                'queued',
            ),
        );
    }

    /**
     * @dataProvider supportedOperationProvider
     *
     * @param string $method
     * @param array<int, mixed> $arguments
     */
    public function testEverySupportedOperationReturnsFirstNestedError($method, array $arguments)
    {
        $response = array(
            'errors' => array('name' => array('nested error', 'second error')),
            'error' => 'lower-priority error',
        );
        $client = $this->mockClientReturning($response);
        $api = new Rage4Api($client);

        $this->assertSame('nested error', call_user_func_array(array($api, $method), $arguments));
    }

    /**
     * @dataProvider supportedOperationProvider
     *
     * @param string $method
     * @param array<int, mixed> $arguments
     */
    public function testEverySupportedOperationReturnsSingularError($method, array $arguments)
    {
        $client = $this->mockClientReturning(array('errors' => array(), 'error' => 'singular error'));
        $api = new Rage4Api($client);

        $this->assertSame('singular error', call_user_func_array(array($api, $method), $arguments));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function supportedOperationProvider()
    {
        return array(
            'get domains' => array('getDomains', array()),
            'create domain' => array('createDomain', array('example.com', 'hostmaster@example.com')),
            'create reverse IPv4 domain' => array('createReverseDomain4', array('2.0.192.in-addr.arpa', 'hostmaster@example.com', 24)),
            'get domain by name' => array('getDomainByName', array('example.com')),
            'get domain by id' => array('getDomain', array(1)),
            'create reverse IPv6 domain' => array('createReverseDomain6', array('8.b.d.0.1.0.0.2.ip6.arpa', 'hostmaster@example.com', 48)),
            'delete domain' => array('deleteDomain', array(1)),
            'import domain' => array('importDomain', array('example.com')),
            'get records' => array('getRecords', array(1)),
            'get geographical regions' => array('getGeoRegions', array()),
            'get record types' => array('getRecordTypes', array()),
            'create record' => array('createRecord', array(1, 'www.example.com', '192.0.2.1')),
            'update record' => array('updateRecord', array(1, 'www.example.com', '192.0.2.1')),
            'delete record' => array('deleteRecord', array(1)),
        );
    }

    /**
     * @dataProvider invalidArgumentsProvider
     *
     * @param string $method
     * @param array<int, mixed> $arguments
     * @param string $message
     */
    public function testInvalidArgumentsAreRejectedBeforeCallingTheApi($method, array $arguments, $message)
    {
        $client = $this->createMock(IRage4ApiClient::class);
        $client->expects($this->never())->method('executeApi');
        $api = new Rage4Api($client);

        $this->expectException(Rage4Exception::class);
        $this->expectExceptionMessage($message);
        call_user_func_array(array($api, $method), $arguments);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function invalidArgumentsProvider()
    {
        return array(
            'create domain requires name' => array('createDomain', array('', 'hostmaster@example.com'), '(method: createDomain) Domain name and Email address is required'),
            'create domain requires email' => array('createDomain', array('example.com', ''), '(method: createDomain) Domain name and Email address is required'),
            'reverse IPv4 requires name' => array('createReverseDomain4', array('', 'hostmaster@example.com', 24), '(method: createReverseDomain4) Domain name, Email address and subnet is required'),
            'reverse IPv4 requires email' => array('createReverseDomain4', array('2.0.192.in-addr.arpa', '', 24), '(method: createReverseDomain4) Domain name, Email address and subnet is required'),
            'reverse IPv4 requires subnet' => array('createReverseDomain4', array('2.0.192.in-addr.arpa', 'hostmaster@example.com', 0), '(method: createReverseDomain4) Domain name, Email address and subnet is required'),
            'domain lookup requires name' => array('getDomainByName', array(''), '(method: getDomainByName) name is required'),
            'domain lookup requires numeric id' => array('getDomain', array('not-an-id'), '(method: getDomain) id is required'),
            'reverse IPv6 requires name' => array('createReverseDomain6', array('', 'hostmaster@example.com', 48), '(method: createReverseDomain6) Domain name, Email address and subnet is required'),
            'reverse IPv6 requires email' => array('createReverseDomain6', array('8.b.d.0.1.0.0.2.ip6.arpa', '', 48), '(method: createReverseDomain6) Domain name, Email address and subnet is required'),
            'reverse IPv6 requires subnet' => array('createReverseDomain6', array('8.b.d.0.1.0.0.2.ip6.arpa', 'hostmaster@example.com', 0), '(method: createReverseDomain6) Domain name, Email address and subnet is required'),
            'delete domain requires numeric id' => array('deleteDomain', array('not-an-id'), '(method: deleteDomain) Domain id must be a number'),
            'import domain requires name' => array('importDomain', array(''), '(method: importDomain) Domain must be a valid string'),
            'get records requires numeric id' => array('getRecords', array('not-an-id'), '(method: getRecords) Domain id must be a number'),
            'create record requires domain id' => array('createRecord', array(0, 'www.example.com', '192.0.2.1'), '(method: createRecord) Domain id must be a number'),
            'create record requires name' => array('createRecord', array(1, '', '192.0.2.1'), '(method: createRecord) Name cannot be empty'),
            'create record requires content' => array('createRecord', array(1, 'www.example.com', ''), '(method: createRecord) Content cannot be empty'),
            'update record requires record id' => array('updateRecord', array(0, 'www.example.com', '192.0.2.1'), '(method: updateRecord) Record id must be a number'),
            'update record requires name' => array('updateRecord', array(1, '', '192.0.2.1'), '(method: updateRecord) Name cannot be empty'),
            'update record requires content' => array('updateRecord', array(1, 'www.example.com', ''), '(method: updateRecord) Content cannot be empty'),
            'delete record requires numeric id' => array('deleteRecord', array('not-an-id'), '(method: deleteRecord) Record id must be a number'),
        );
    }

    public function testCreateDomainUsesDefaultNameserverPrefix()
    {
        $client = $this->expectApiCall(
            'CreateRegularDomain',
            array(
                'name' => 'example.com',
                'email' => 'hostmaster@example.com',
                'ns1' => 'ns1.dns.example.net',
                'ns2' => 'ns2.dns.example.net',
            ),
            array('status' => true)
        );

        $api = new Rage4Api($client);
        $this->assertSame(array('status' => true), $api->createDomain('example.com', 'hostmaster@example.com', 'dns.example.net'));
    }

    public function testCreateDomainSendsNullNameserversWhenNoneAreProvided()
    {
        $client = $this->expectApiCall(
            'CreateRegularDomain',
            array(
                'name' => 'example.com',
                'email' => 'hostmaster@example.com',
                'ns1' => null,
                'ns2' => null,
            ),
            array('status' => true)
        );

        $api = new Rage4Api($client);
        $this->assertSame(array('status' => true), $api->createDomain('example.com', 'hostmaster@example.com'));
    }

    public function testCreateRecordNormalizesDefaultOptionalValues()
    {
        $client = $this->expectApiCall(
            'CreateRecord',
            array(
                'name' => 'www.example.com',
                'content' => 'hello',
                'type' => 'TXT',
                'failover' => 'false',
                'failovercontent' => '',
                'ttl' => 3600,
                'geozone' => 0,
                'priority' => 0,
                'geolock' => 'true',
                'geolat' => null,
                'geolong' => null,
                'id' => 42,
            ),
            array('status' => true, 'id' => 9)
        );

        $api = new Rage4Api($client);
        $this->assertSame(
            array('status' => true, 'id' => 9),
            $api->createRecord(42, 'www.example.com', 'hello')
        );
    }

    public function testUpdateRecordOmitsEmptyOptionalValues()
    {
        $client = $this->expectApiCall(
            'UpdateRecord',
            array(
                'name' => 'www.example.com',
                'content' => '192.0.2.1',
                'failover' => 'false',
                'ttl' => 3600,
                'geozone' => -1,
                'priority' => null,
                'id' => 9,
            ),
            array('status' => true, 'id' => 9)
        );

        $api = new Rage4Api($client);
        $this->assertSame(
            array('status' => true, 'id' => 9),
            $api->updateRecord(9, 'www.example.com', '192.0.2.1', '', false, '', 3600, '-1', '', '')
        );
    }

    /**
     * @param string $endpoint
     * @param array<string, mixed> $query
     * @param array<int|string, mixed> $response
     * @return IRage4ApiClient&MockObject
     */
    private function expectApiCall($endpoint, array $query, array $response)
    {
        $client = $this->createMock(IRage4ApiClient::class);
        $client->expects($this->once())
            ->method('executeApi')
            ->with($this->equalTo($endpoint), $this->equalTo($query))
            ->willReturn($response);

        return $client;
    }

    /**
     * @param array<int|string, mixed> $response
     * @return IRage4ApiClient&MockObject
     */
    private function mockClientReturning(array $response)
    {
        $client = $this->createMock(IRage4ApiClient::class);
        $client->expects($this->once())->method('executeApi')->willReturn($response);

        return $client;
    }

    /**
     * @return IRage4ApiClient
     */
    private function readClient(Rage4Api $api)
    {
        $property = new ReflectionProperty(Rage4Api::class, 'client');
        $property->setAccessible(true);

        return $property->getValue($api);
    }
}

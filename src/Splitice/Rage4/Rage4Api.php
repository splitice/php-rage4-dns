<?php 
namespace Splitice\Rage4;

/**
 * Rage4 DNS PHP5 class
 * This is a PHP5 wrapper to easily integrate Rage4 DNS service (www.rage4.com) easily.
 *
 * @author SplitIce (www.x4b.net)
 * @author Asim Zeeshan (www.asim.pk)
 * @package Splitice\Rage4
 */
class Rage4Api {
    private $valid_record_types = null;

    /**
     * @var IRage4ApiClient
     */
    private $client;

    /**
     * Create an instance of the Rage4 API interface.
     *
     * @param string|IRage4ApiClient $username Rage4 account username (Email Address) or
     * @param string $password Rage4 account password (Account Key)
     */
    public function __construct($username, $password = null) {
        if(is_string($password) && is_string($username)){
            $this->client = new Rage4ApiClient($username,$password);
        }elseif($username instanceof IRage4ApiClient){
            $this->client = $username;
        }
    }

    private function encodeBool($value){
        return $value?'true':'false';
    }

    /**
     * Get all domain names in your Rage4.com account.
     *
     * @return string
     */
    public function getDomains() {
        $response = $this->client->executeApi("GetDomains");
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }

    /**
     * Create a new domain name (zone) in your Rage4.com account.
     *
     * @param string $domain_name
     * @param string $email
     * @param string|null $ns
     * @param string $nsprefix
     * @throws Rage4Exception
     * @return string
     */
    public function createDomain($domain_name, $email, $ns = null, $nsprefix = 'ns') {
        if (empty($domain_name) || empty($email)) {
            throw new Rage4Exception("(method: createDomain) Domain name and Email address is required");
        }

        //Create a ns1 & ns2 from the options given or use defaults
        $ns1 = $ns2 = null;
        if($ns != null){
            $ns1 = $nsprefix.'1.'.$ns;
            $ns2 = $nsprefix.'2.'.$ns;
        }

        $response = $this->client->executeApi('CreateRegularDomain',array('name'=>$domain_name,'email'=>$email,'ns1'=>$ns1,'ns2'=>$ns2));
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }


    /**
     * Create a reverse IPv4 domain name (zone) in your Rage4.com account.
     *
     * @param string $domain_name
     * @param string $email
     * @param integer $subnet
     * @throws Rage4Exception
     * @return mixed
     */
    public function createReverseDomain4($domain_name, $email, $subnet) {
        if (empty($domain_name) || empty($email) || empty($subnet)) {
            throw new Rage4Exception("(method: createReverseDomain4) Domain name, Email address and subnet is required");
        }

        $response = $this->client->executeApi('createreversedomain4',array('name'=>$domain_name,'email'=>$email,'subnet'=>$subnet));
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }

    /**
     * Get a domain name (zone) in your Rage4.com account by name.
     *
     * @param string $name
     * @throws Rage4Exception
     * @return string
     */
    function getDomainByName($name){
    	if (empty($name)) {
    		throw new Rage4Exception("(method: getDomainByName) name is required");
    	}
    	
    	$response = $this->client->executeApi("GetDomainByName",array('name'=>$name));

		if(isset($response['errors']) && count($response['errors'])){
			$type = array_keys($response['errors']);
			return $response['errors'][$type[0]][0];
		} else if (isset($response['error']) && $response['error']!="") {
    		return $response['error'];
    	} else {
    		return $response;
    	}
    }

    /**
     * Create a reverse IPv6 domain name (zone) in your Rage4.com account
     *
     * @param string $domain_name domain name (for reverse domains: ip6.arpa or in-addr.arpa)
     * @param string $email owner's email
     * @param int $subnet valid subnet mask
     * @throws Rage4Exception
     * @return string
     */
    public function createReverseDomain6($domain_name, $email, $subnet) {
        if (empty($domain_name) || empty($email) || empty($subnet)) {
            throw new Rage4Exception("(method: createReverseDomain6) Domain name, Email address and subnet is required");
        }

        $response = $this->client->executeApi('createreversedomain6',array('name'=>$domain_name,'email'=>$email, 'subnet'=>$subnet));
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }

    /**
     * Delete a new domain name using its unique identifier in the
     * system. To know the unqiue identifier, GetDomains() must be
     * called first
     *
     * @param int $domain_id
     * @throws Rage4Exception
     * @return bool
     */
    public function deleteDomain($domain_id) {
        // explicitly typecast into integer
        $domain_id = (int)$domain_id;
        
        if (empty($domain_id)) {
            throw new Rage4Exception("(method: deleteDomain) Domain id must be a number");
        }
        
        $response = $this->client->executeApi("DeleteDomain", array('id'=>$domain_id));
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return (bool)$response['status'];
        }
    }

    /**
     * Import a domain name including zone data into the system.
     *
     * Note! You need to allow AXFR transfers
     * Note! Only regular domains are supported
     *
     * @param string $domain the domain
     * @throws Rage4Exception
     * @return bool
     */
    public function importDomain($domain) {
        // explicitly typecast into string
        $domain = (string)$domain;
        
        if (empty($domain)) {
            throw new Rage4Exception("(method: importDomain) Domain must be a valid string");
        }
        
        $response = $this->client->executeApi("ImportDomain",array('name'=>$domain));
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return (bool)$response['status'];
        }
    }

    /**
     * Get all records (A, AAAA etc) of a particular domain name
     *
     * @param int $domain_id
     * @throws Rage4Exception
     * @return string|array error or array of records
     */
    public function getRecords($domain_id) {
        // explicitly typecast into integer
        $domain_id = (int)$domain_id;
        
        if (empty($domain_id)) {
            throw new Rage4Exception("(method: getRecords) Domain id must be a number");
        }
        
        $response = $this->client->executeApi("GetRecords", array('id'=>$domain_id));
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }

    /**
     * Get a list of valid Geographical regions
     *
     * @return string|array error or array of geo regions
     */
    public function getGeoRegions() {
    	$response = $this->client->executeApi("ListGeoRegions");
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
    		return $response['error'];
    	} else {
    		return $response;
    	}
    }

    /**
     * Get a list of valid record types and their numerical values
     *
     * @return string|array error or array of record types
     */
    public function getRecordTypes() {
        $response = $this->client->executeApi("ListRecordTypes");
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            $ret = array();
            foreach($response as $v){
                $ret[$v['key']] = $v['value'];
            }
            return $ret;
        }
    }

    /**
     * Create new record for a specific domain
     *
     * @param int $domain_id
     * @param string $name name of the record
     * @param string $content content of the record
     * @param string $type record type, should be one of: NS, A, AAAA, CNAME, MX, TXT, SRV, PTR
     * @param int|null|string $priority priority of the record being created (optional)
     * @param bool $failover Failure support enabled
     * @param string $failovercontent Failure IP / content
     * @param int $ttl TTL of record
     * @param int $geozone Geographical Zone ID (or -1 for closest first)
     * @param null|float $geolat Latitude override
     * @param null|float $geolong Longitude override
     * @param bool $geolock Lock Geographical coordinates
     * @throws Rage4Exception
     * @return string
     */
    public function createRecord($domain_id, $name, $content, $record_type="TXT", $priority="", $failover=false, $failovercontent="", $ttl = 3600, $geozone=0, $geolat=null, $geolong=null, $geolock=true) {
        // explicitly typecast into required types
        $domain_id          = (int)$domain_id;

        if (empty($domain_id)) {
            throw new Rage4Exception("(method: createRecord) Domain id must be a number");
        }
        if (empty($name)) {
            throw new Rage4Exception("(method: createRecord) Name cannot be empty");
        }
        if (empty($content)) {
            throw new Rage4Exception("(method: createRecord) Content cannot be empty");
        }

        //Build query (non-nullable fields)
        $query = array('name'=>$name,'content'=>$content,'type'=>$record_type,'failover'=>$this->encodeBool($failover), 'failovercontent'=>$failovercontent, 'ttl'=>$ttl, 'geozone'=>(int)$geozone);

        //Build query (nullable fields)
        $query['priority'] = ($priority===null||$priority==="")?0:(int)$priority;
        $query['geolock'] = $this->encodeBool($geolock);
        $query['geolat'] = ($geolat===null || $geolat === '')?null:(float)$geolat;
        $query['geolong'] = ($geolong===null || $geolong === '')?null:(float)$geolong;
        $query['id'] = $domain_id;
        
        $response = $this->client->executeApi("CreateRecord", $query);
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }

    /**
     * Update an existing record for a specific domain name (no need to mention domain name)
     *
     * @param int $record_id record id that you wish to update
     * @param string $name name of the record
     * @param string $content content of the record
     * @param int|null $priority priority of the record being created (optional)
     * @param bool $failover Failure support enabled
     * @param string $failovercontent Failure IP / content
     * @param int $ttl TTL of record
     * @param int $geozone Geographical Zone ID (or -1 for closest first)
     * @param null|float $geolat Latitude override
     * @param null|float $geolong Longitude override
     * @throws Rage4Exception
     * @return string
     */
    public function updateRecord($record_id, $name, $content, $priority=null, $failover=false, $failovercontent="", $ttl = 3600, $geozone = 0, $geolat = null, $geolong = null) {
        // explicitly typecast into required types
        $record_id          = (int)$record_id;

        //Handle null and similar zone values
        if(!is_numeric($geozone)){
            $geozone = 0;
        }

        //validate input
        if (empty($record_id)) {
            throw new Rage4Exception("(method: updateRecord) Record id must be a number");
        }
        if (empty($name)) {
            throw new Rage4Exception("(method: updateRecord) Name cannot be empty");
        }
        if (empty($content)) {
            throw new Rage4Exception("(method: updateRecord) Content cannot be empty");
        }

        //Build query (non-nullable fields)
        $query = array('name'=>$name,'content'=>$content,'failover'=>$this->encodeBool($failover), 'ttl'=>$ttl, 'geozone'=>(int)$geozone);
		if($failovercontent){
			$query['failovercontent'] = $failovercontent;
		}

        //Build query (nullable fields)
        $query['priority'] = ($priority===null||$priority==="")?null:(int)$priority;
        if($geolat !== null && $geolat !== '') $query['geolat'] = (float)$geolat;
	    if($geolong !== null && $geolong !== '') $query['geolong'] = (float)$geolong;
        $query['id'] = $record_id;
        
        $response = $this->client->executeApi("UpdateRecord", $query);
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }

    /**
     * Delete a record in an existing domain name (zone) in your account
     *
     * @param int $record_id record identifier
     * @throws Rage4Exception
     * @return mixed
     */
    public function deleteRecord($record_id) {
        // explicitly typecast into integer
        $record_id = (int)$record_id;
        
        if (empty($record_id)) {
            throw new Rage4Exception("(method: deleteRecord) Record id must be a number");
        }
        
        $response = $this->client->executeApi("DeleteRecord",array('id'=>$record_id));
	    if(isset($response['errors']) && count($response['errors'])){
		    $type = array_keys($response['errors']);
		    return $response['errors'][$type[0]][0];
	    } else if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response['status'];
        }
    }
}
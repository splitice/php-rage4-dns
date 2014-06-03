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
class API {
    private $valid_record_types = array(1 => "NS", 2 => "A", 3 => "AAAA", 4 => "CNAME", 5 => "MX", 6 => "TXT", 7 => "SRV", 8 => "PTR");
    private $ch;

    /**
     * Create an instance of the Rage4 API client.
     *
     * @param string $username Rage4 account username (Email Address)
     * @param string $password Rage4 account password (Account Key)
     */
    public function __construct($username, $password) {
        if (empty($user) || empty($pass)){
            throw new Rage4Exception("Username and Password cannot be empty!");
        }

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_USERPWD, $username.":".$password);
    }

    private function encodeBool($value){
        return $value?'true':'false';
    }

    /**
     * @param array $query Query string data
     * @return string an encoded query string
     */
    private function buildQueryString(array $query){
        //Null values should be an empty string
        //e.g ?nullable=&....
        foreach($query as $qk=>$qv){
            if($qv === null){
                $query[$qk] = '';
            }
        }
        return http_build_query($query);
    }

    /**
     * @param string $method
     * @param array $query_data
     * @return string
     */
    private function executeApi($method, array $query_data = array()) {
        //echo "Trying ... https://secure.rage4.com/rapi/$method <br />";
        //echo var_dump($method);

        //Build URL
        $url = "https://secure.rage4.com/rapi/".$method.'/';
        if($query_data) {
            $url .= '?'.$this->buildQueryString($query_data);
        }

        //Set curl options
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
        curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

        //Connection keepalive
        $header = array();
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);

        //Execute request
        $result = curl_exec($this->ch);

        //Format
        if($result === false){
            throw new Rage4Exception("Unable to communicate with Rage4 API: ".curl_error($this->ch));
        }

        //Check status code
        $status_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        if($status_code != 200){
            throw new Rage4Exception("Invalid HTTP status code in response from Rage4 API");
        }

        //JSON
        $json = @json_decode($result,true);
        if($json === false){
            throw new Rage4Exception("Invalid response JSON from Rage4 API");
        }

        return $json;
    }

    /**
     * Get all domain names in your Rage4.com account.
     *
     * @return string
     */
    public function getDomains() {
        $response = $this->executeApi("getdomains");

        if (isset($response['error']) && $response['error']!="") {
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
     * @return string
     */
    public function createDomain($domain_name, $email, $ns = null) {
        if (empty($domain_name) || empty($email)) {
            throw new Rage4Exception("(method: createDomain) Domain name and Email address is required");
        }

        $response = $this->executeApi('createregulardomainext',array('name'=>$domain_name,'email'=>$email,'ns'=>$ns));
        
        if (isset($response['error']) && $response['error']!="") {
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
     * @return mixed
     */
    public function createReverseDomain4($domain_name, $email, $subnet) {
        if (empty($domain_name) || empty($email) || empty($subnet)) {
            throw new Rage4Exception("(method: createReverseDomain4) Domain name, Email address and subnet is required");
        }

        $response = $this->executeApi('createreversedomain4',array('name'=>$domain_name,'email'=>$email,'subnet'=>$subnet));
        
        if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }

    /**
     * Get a domain name (zone) in your Rage4.com account by name.
     *
     * @param $name
     * @return string
     */
    function getDomainByName($name){
    	if (empty($name)) {
    		throw new Rage4Exception("(method: getDomainByName) name is required");
    	}
    	
    	$response = $this->executeApi("getdomainbyname",array('name'=>$name));
    	
    	if (isset($response['error']) && $response['error']!="") {
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
     * @return string
     */
    public function createReverseDomain6($domain_name, $email, $subnet) {
        if (empty($domain_name) || empty($email) || empty($subnet)) {
            throw new Rage4Exception("(method: createReverseDomain6) Domain name, Email address and subnet is required");
        }

        $response = $this->executeApi('createreversedomain6',array('name'=>$domain_name,'email'=>$email, 'subnet'=>$subnet));
        
        if (isset($response['error']) && $response['error']!="") {
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
     * @param $domain_id
     * @return bool
     */
    public function deleteDomain($domain_id) {
        // explicitly typecast into integer
        $domain_id = (int)$domain_id;
        
        if (empty($domain_id)) {
            throw new Rage4Exception("(method: deleteDomain) Domain id must be a number");
        }
        
        $response = $this->executeApi("deletedomain/$domain_id");
        
        if (isset($response['error']) && $response['error']!="") {
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
     * @return bool
     */
    public function importDomain($domain) {
        // explicitly typecast into string
        $domain = (string)$domain;
        
        if (empty($domain)) {
            throw new Rage4Exception("(method: importDomain) Domain must be a valid string");
        }
        
        $response = $this->executeApi("importdomain",array('name'=>$domain));
        
        if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return (bool)$response['status'];
        }
    }

    /**
     * Get all records (A, AAAA etc) of a particular domain name
     *
     * @param $domain_id
     * @return string domain id
     */
    public function getRecords($domain_id) {
        // explicitly typecast into integer
        $domain_id = (int)$domain_id;
        
        if (empty($domain_id)) {
            throw new Rage4Exception("(method: getRecords) Domain id must be a number");
        }
        
        $response = $this->executeApi("getrecords/$domain_id");
        
        if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }

    /**
     * Get a list of valid Geographical regions
     *
     * @return string
     */
    public function getGeoRegions() {
    	$response = $this->executeApi("listgeoregions");
    
    	if (isset($response['error']) && $response['error']!="") {
    		return $response['error'];
    	} else {
    		return $response;
    	}
    }

    /**
     * Create new record for a specific domain
     *
     * @param int $domain_id
     * @param string $name name of the record
     * @param string $content content of the record
     * @param string $type record type, should be one of: NS, A, AAAA, CNAME, MX, TXT, SRV, PTR
     * @param int|null $priority priority of the record being created (optional)
     * @param bool $failover Failure support enabled
     * @param string $failovercontent Failure IP / content
     * @param int $ttl TTL of record
     * @param int $geozone Geographical Zone ID (or -1 for closest first)
     * @param null|float $geolat Latitude override
     * @param null|float $geolong Longitude override
     * @param bool $geolock Lock Geographical coordinates
     * @return string
     */
    public function createRecord($domain_id, $name, $content, $type="TXT", $priority="", $failover=false, $failovercontent="", $ttl = 3600, $geozone=0, $geolat=null, $geolong=null, $geolock=true) {
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
        $query = array('name'=>$name,'content'=>$content,'failover'=>$this->encodeBool($failover), 'failovercontent'=>$failovercontent, 'ttl'=>$ttl, 'geozone'=>(int)$geozone);

        //Build query (nullable fields)
        $query['priority'] = ($priority===null||$priority==="")?null:(int)$priority;
        $query['geolock'] = $this->encodeBool($geolock);
        $query['geolat'] = ($geolat===null || $geolat === '')?null:(float)$geolat;
        $query['geolong'] = ($geolong===null || $geolong === '')?null:(float)$geolong;
        
        $response = $this->executeApi("createrecord/$domain_id", $query);
        
        if (isset($response['error']) && $response['error']!="") {
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
     * @param bool $geolock Lock Geographical coordinates
     * @return string
     */
    public function updateRecord($record_id, $name, $content, $priority=null, $failover=false, $failovercontent="", $ttl = 3600, $geozone = 0, $geolat = null, $geolong = null, $geolock=true) {
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
        $query = array('name'=>$name,'content'=>$content,'failover'=>$this->encodeBool($failover), 'failovercontent'=>$failovercontent, 'ttl'=>$ttl, 'geozone'=>(int)$geozone);

        //Build query (nullable fields)
        $query['priority'] = ($priority===null||$priority==="")?null:(int)$priority;
        $query['geolock'] = $this->encodeBool($geolock);
        $query['geolat'] = ($geolat===null || $geolat === '')?null:(float)$geolat;
        $query['geolong'] = ($geolong===null || $geolong === '')?null:(float)$geolong;
        
        $response = $this->executeApi("updaterecord/$record_id", $query);

        if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }

    /**
     * Delete a record in an existing domain name (zone) in your account
     *
     * @param $record_id record identifier
     * @return mixed
     */
    public function deleteRecord($record_id) {
        // explicitly typecast into integer
        $record_id = (int)$record_id;
        
        if (empty($record_id)) {
            throw new Rage4Exception("(method: deleteRecord) Record id must be a number");
        }
        
        $response = $this->executeApi("deleterecord/$record_id");
        
        if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response['status'];
        }
    }
}
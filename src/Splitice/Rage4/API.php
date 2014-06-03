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
    private $username           = "";
    private $password           = "";
    private $valid_record_types = array(1 => "NS", 2 => "A", 3 => "AAAA", 4 => "CNAME", 5 => "MX", 6 => "TXT", 7 => "SRV", 8 => "PTR");
    private $ch;

    /*
        THE CONSTRUCTOR
        
        All API calls uses BASIC authentication using user's 
         - email address as username
         - Account Key as password
        
        Note: Account Key is available in User Profile section of 
        Rage4 DNS control panel.
        ------------------------------------------------------------
        Parameters: $user and $pass
        
        */
    public function __construct($user, $pass) {
        if (empty($user) || empty($pass)){
            $this->throwError("Username and Password cannot be empty!");
        } else if (!empty($user) && !empty($pass)){
            $this->username = $this->cleanInput($user);
            $this->password = $this->cleanInput($pass);
        }

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_USERPWD, $this->username.":".$this->password);
    }
    
    // Internal method
    // TODO: Instead of printing, I need to "return" back the error messages
    private function throwError($err) {
        throw new Rage4Exception($err);
    }
    
    // Utility functions
    private function cleanInput($i) {
        return trim($i);
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
    
    // Internal method to debug code, I will leave it here for now
    private function dump($obj) {
        echo "<br /><pre>";
        print_r($obj);
        echo "</pre>";
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
    
    /*
        Core function that queries the API and renders results
        ------------------------------------------------------------
        Parameters: $method (it includes the method and/or querystring)
        
        */
    private function doQuery($method) {
        //echo "Trying ... https://secure.rage4.com/rapi/$method <br />";
        //echo var_dump($method);
        $url = "https://secure.rage4.com/rapi/".$method;
        //echo(var_dump($url));
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
        curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_USERPWD, $this->username.":".$this->password);
        
        $header = array();
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
        //echo $this->username.":".$this->password."<br />";
        //echo "HTTPCODE=".$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE)."<br />";
        $result = curl_exec($this->ch);
        //$this->dump($result);
        //exit;
        return $result;
    }
    
    private function json_decode($str){
    	if($str === false){
    		throw new Rage4Exception("HTTP Error: ". $str);
    	}
    	
    	$data = json_decode($str, true);
    	
    	if($data === null || $data === false){
    		throw new Rage4Exception("Invalid JSON Data: ". $str);
    	}
    	
    	return $data;
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
            $this->throwError("(method: createDomain) Domain name and Email address is required");
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
            $this->throwError("(method: createReverseDomain4) Domain name, Email address and subnet is required");
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
    		$this->throwError("(method: getDomainByName) name is required");
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
            $this->throwError("(method: createReverseDomain6) Domain name, Email address and subnet is required");
        }

        $response = $this->executeApi('createreversedomain6',array('name'=>$domain_name,'email'=>$email, 'subnet'=>$subnet));
        
        if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response;
        }
    }
    
    /*
        DELETE A DOMAIN NAME
        Delete a new domain name using its unique identifier in the
        system. To know the unqiue identifier, GetDomains() must be
        called first
        ------------------------------------------------------------
        Parameters: (all required)
        $domain_id (int) = domain id
        
        */
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
            $this->throwError("(method: deleteDomain) Domain id must be a number");
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
            $this->throwError("(method: importDomain) Domain must be a valid string");
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
            $this->throwError("(method: getRecords) Domain id must be a number");
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
    
    /*
        CREATE NEW RECORD
        Create new record for a specific domain name
        ------------------------------------------------------------
        Parameters: (all required except where mentioned)
        $domain_id (int)            = domain id
        $name (string)              = name of the record
        $content (string)           = content of the record
        $type (string)              = type, should be one of the following 
                                        1 = NS
                                        2 = A
                                        3 = AAAA
                                        4 = CNAME
                                        5 = MX
                                        6 = TXT
                                        7 = SRV
                                        8 = PTR
        $priority (int)             = priority of the record being created (optional)
        $failover (bool)            = Failure support? Yes/No
        $failovercontent (string)   = Failure IP / content
        
        */
    /**
     * Create new record for a specific domain
     *
     * @param int $domain_id
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
    public function createRecord($domain_id, $name, $content, $type="TXT", $priority="", $failover=false, $failovercontent="", $ttl = 3600, $geozone=0, $geolat=null, $geolong=null, $geolock=true) {
        // explicitly typecast into required types
        $domain_id          = (int)$domain_id;
        $name               = (string)$this->cleanInput($name);
        $content            = (string)$this->cleanInput($content);
        $type               = $this->cleanInput($type);
        $priority           = $this->cleanInput($priority);
        $failovercontent    = (string)$this->cleanInput($failovercontent);
        
        if (empty($domain_id)) {
            $this->throwError("(method: createRecord) Domain id must be a number");
        }
        if (empty($name)) {
            $this->throwError("(method: createRecord) Name cannot be empty");
        }
        if (empty($content)) {
            $this->throwError("(method: createRecord) Content cannot be empty");
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
        $name               = (string)$this->cleanInput($name);
        $content            = (string)$this->cleanInput($content);
        $priority           = $this->cleanInput($priority);
        $failovercontent    = (string)$this->cleanInput($failovercontent);

        //Handle null and similar zone values
        if(!is_numeric($geozone)){
            $geozone = 0;
        }

        //validate input
        if (empty($record_id)) {
            $this->throwError("(method: updateRecord) Record id must be a number");
        }
        if (empty($name)) {
            $this->throwError("(method: updateRecord) Name cannot be empty");
        }
        if (empty($content)) {
            $this->throwError("(method: updateRecord) Content cannot be empty");
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
            $this->throwError("(method: deleteRecord) Record id must be a number");
        }
        
        $response = $this->executeApi("deleterecord/$record_id");
        
        if (isset($response['error']) && $response['error']!="") {
            return $response['error'];
        } else {
            return $response['status'];
        }
    }
    
}
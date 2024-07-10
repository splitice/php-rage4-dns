<?php

namespace Splitice\Rage4;

/**
 * Client for the Rage4 API web service
 *
 * @package Splitice\Rage4
 *
 */
class Rage4ApiClient implements IRage4ApiClient
{
    protected $ch;

    /**
     * Create an instance of the Rage4 API client.
     *
     * @param string $username Rage4 account username (Email Address)
     * @param string $password Rage4 account password (Account Key)
     * @throws Rage4Exception
     */
    public function __construct($username, $password) {
        if (empty($username) || empty($password)){
            throw new Rage4Exception("Username and Password cannot be empty!");
        }

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_USERPWD, $username.":".$password);
    }

    /**
     * @param array $query Query string data
     * @return string an encoded query string
     */
    public function buildQueryString(array $query){
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
     * @throws Rage4Exception
     * @return string
     */
    public function executeApi($method, array $query_data = array()) {
        //echo "Trying ... https://secure.rage4.com/rapi/$method <br />";
        //echo var_dump($method);

        //Build URL
        $url = "https://secure.rage4.com/rapi/".$method.'/';
        if($query_data) {
            $url .= '?'.$this->buildQueryString($query_data);
        }
        //echo var_dump($url);

        //Set curl options
        curl_setopt($this->ch, CURLOPT_URL, $url);

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
            if($status_code == 400) {
                $json = @json_decode($result,true);
                if($json && !empty($json['error'])){
                    throw new Rage4Exception("Got a 400 error in response from Rage4 API with error: ".json_encode($json['error']));
                }
            }
            throw new Rage4Exception("Invalid HTTP status code ($status_code) in response from Rage4 API");
        }

        //JSON
        $json = @json_decode($result,true);
        if($json === false){
            throw new Rage4Exception("Invalid response JSON from Rage4 API");
        }

        return $json;
    }
} 
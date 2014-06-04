<?php
use Splitice\Rage4\Rage4ApiClient;

/**
 * Created by PhpStorm.
 * User: splitice
 * Date: 6/4/14
 * Time: 12:51 PM
 */

class ClientQueryStringFormatTest extends PHPUnit_Framework_TestCase {
    function testSimpleQueryString(){
        $client = new Rage4ApiClient("1","1");
        $this->assertEquals('q1=a&q2=b',$client->buildQueryString(array('q1'=>'a','q2'=>'b')));
    }
    function testQueryStringNullValues(){
        $client = new Rage4ApiClient("1","1");
        $this->assertEquals('q1=&q2=',$client->buildQueryString(array('q1'=>null,'q2'=>'')));
    }
    function testQueryStringNullAndZero(){
        $client = new Rage4ApiClient("1","1");
        $this->assertEquals('q1=&q2=0&q3=0',$client->buildQueryString(array('q1'=>null,'q2'=>0,'q3'=>'0')));
    }
} 
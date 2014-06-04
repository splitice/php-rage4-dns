<?php
use Splitice\Rage4\Rage4Api;

/**
 * Created by PhpStorm.
 * User: splitice
 * Date: 6/4/14
 * Time: 2:24 PM
 */

class ApiDomainCreationMethodsTest extends PHPUnit_Framework_TestCase {
    function testCreateDomain(){
        //Setup
        $domain = "test.com";
        $email = "email@test.com";
        $ns = "ns";

        //Assert
        $client = $this->getMock('IRage4ApiClient', array('executeApi'));
        $client->expects($this->once())->method('executeApi')->with($this->equalTo(array('name'=>$domain,'email'=>$email,'ns'=>$ns)));

        //Do
        $api = new Rage4Api($client);
        $api->createDomain($domain, $email, $ns);
    }

    function testCreateDomainNullNs(){
        //Setup
        $domain = "test.com";
        $email = "email@test.com";

        //Assert
        $client = $this->getMock('IRage4ApiClient', array('executeApi'));
        $client->expects($this->once())->method('executeApi')->with($this->equalTo(array('name'=>$domain,'email'=>$email,'ns'=>null)));

        //Do
        $api = new Rage4Api($client);
        $api->createDomain($domain, $email);
    }

    function testCreateReverseDomain4(){
        //Setup
        $domain = "test.com";
        $email = "email@test.com";
        $subnet = 24;

        //Assert
        $client = $this->getMock('IRage4ApiClient', array('executeApi'));
        $client->expects($this->once())->method('executeApi')->with($this->equalTo(array('name'=>$domain,'email'=>$email,'subnet'=>$subnet)));

        //Do
        $api = new Rage4Api($client);
        $api->createReverseDomain4($domain, $email, $subnet);
    }

    function testCreateReverseDomain6(){
        //Setup
        $domain = "test.com";
        $email = "email@test.com";
        $subnet = 24;

        //Assert
        $client = $this->getMock('IRage4ApiClient', array('executeApi'));
        $client->expects($this->once())->method('executeApi')->with($this->equalTo(array('name'=>$domain,'email'=>$email,'subnet'=>$subnet)));

        //Do
        $api = new Rage4Api($client);
        $api->createReverseDomain6($domain, $email, $subnet);
    }
} 
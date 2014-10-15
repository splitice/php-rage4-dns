<?php
use Splitice\Rage4\Rage4Api;

/**
 * Created by PhpStorm.
 * User: splitice
 * Date: 6/4/14
 * Time: 2:24 PM
 */

class ApiMethodsTest extends PHPUnit_Framework_TestCase {
    const API_CLIENT = '\\Splitice\\Rage4\\IRage4ApiClient';

    function testCreateDomain(){
        //Setup
        $domain = "test.com";
        $email = "email@test.com";
        $ns = "ns.com";

        //Assert
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with($this->equalTo('createregulardomain'),$this->equalTo(array('name'=>$domain,'email'=>$email,'ns1'=>'ns1.'.$ns,'ns2'=>'ns2.'.$ns)));

        //Do
        $api = new Rage4Api($client);
        $api->createDomain($domain, $email, $ns);
    }

    function testCreateDomainNullNs(){
        //Setup
        $domain = "test.com";
        $email = "email@test.com";

        //Assert
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with($this->equalTo('createregulardomain'),$this->equalTo(array('name'=>$domain,'email'=>$email,'ns1'=>null,'ns2'=>null)));

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
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with($this->equalTo('createreversedomain4'),$this->equalTo(array('name'=>$domain,'email'=>$email,'subnet'=>$subnet)));

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
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with($this->equalTo('createreversedomain6'),$this->equalTo(array('name'=>$domain,'email'=>$email,'subnet'=>$subnet)));

        //Do
        $api = new Rage4Api($client);
        $api->createReverseDomain6($domain, $email, $subnet);
    }

    function testGetDomainByName(){
        //Setup
        $domain = "test.com";

        //Assert
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with($this->equalTo('getdomainbyname'),$this->equalTo(array('name'=>$domain)));

        //Do
        $api = new Rage4Api($client);
        $api->getDomainByName($domain);
    }

    function testDeleteDomain(){
        //Setup
        $domain = 1;

        //Assert
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with($this->equalTo('deletedomain/'.$domain),$this->equalTo(array()));

        //Do
        $api = new Rage4Api($client);
        $api->deleteDomain($domain);
    }

    function testImportDomain(){
        //Setup
        $domain = "test.com";

        //Assert
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with($this->equalTo('importdomain'),$this->equalTo(array('name'=>$domain)));

        //Do
        $api = new Rage4Api($client);
        $api->importDomain($domain);
    }

    function testGetRecords(){
        //Setup
        $domain = 1;

        //Assert
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with($this->equalTo('getrecords/'.$domain),$this->equalTo(array()));

        //Do
        $api = new Rage4Api($client);
        $api->getRecords($domain);
    }

    function testGetGeoRegions(){
        //Setup

        //Assert
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with($this->equalTo('listgeoregions'),$this->equalTo(array()));

        //Do
        $api = new Rage4Api($client);
        $api->getGeoRegions();
    }

    function testCreateRecord(){
        //Setup
        $domain = 1;
        $name = 'test.com';
        $content = '1.1.1.1';
        $type = 2;
        $priority = 1;
        $failover = false;
        $failovercontent = "1.1.1.2";
        $ttl = 10;
        $geozone = -1;
        $geolat = 100;
        $geolong = "-20.1";

        //Assert
        $client = $this->getMock(self::API_CLIENT);

        $client->expects($this->once())->method('executeApi')->with(
            $this->equalTo('createrecord/'.$domain),
            $this->equalTo(array('name'=>$name,'content'=>$content,'type'=>$type,'priority'=>$priority,'failover'=>'false','failovercontent'=>$failovercontent,'ttl'=>10,'geozone'=>-1,'geolat'=>100.0,'geolong'=>-20.1,'geolock'=>'true')));

        //Do
        $api = new Rage4Api($client);
        $api->createRecord($domain,$name,$content,$type,$priority,$failover,$failovercontent,$ttl,$geozone,$geolat,$geolong);
    }

    function testUpdateRecord(){
        //Setup
        $record = 1;
        $name = 'test.com';
        $content = '1.1.1.1';
        $priority = 1;
        $failover = false;
        $failovercontent = "1.1.1.2";
        $ttl = 10;
        $geozone = -1;
        $geolat = 100;
        $geolong = "-20.1";

        //Assert
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with(
            $this->equalTo('updaterecord/'.$record),
            $this->equalTo(array('name'=>$name,'content'=>$content,'priority'=>$priority,'failover'=>'false','failovercontent'=>$failovercontent,'ttl'=>10,'geozone'=>-1,'geolat'=>100.0,'geolong'=>-20.1)));

        //Do
        $api = new Rage4Api($client);
        $api->updateRecord($record,$name,$content,$priority,$failover,$failovercontent,$ttl,$geozone,$geolat,$geolong);
    }

    function testUpdateRecordNullGeo(){
        //Setup
        $record = 1;
        $name = 'test.com';
        $content = '1.1.1.1';
        $priority = 1;
        $failover = false;
        $failovercontent = "1.1.1.2";
        $ttl = 10;
        $geozone = -1;
        $geolat = null;
        $geolong = null;

        //Assert
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with(
            $this->equalTo('updaterecord/'.$record),
            $this->equalTo(array('name'=>$name,'content'=>$content,'priority'=>$priority,'failover'=>'false','failovercontent'=>$failovercontent,'ttl'=>10,'geozone'=>-1,'geolat'=>'','geolong'=>'')));

        //Do
        $api = new Rage4Api($client);
        $api->updateRecord($record,$name,$content,$priority,$failover,$failovercontent,$ttl,$geozone,$geolat,$geolong);
    }

    function testDeleteRecord(){
        //Setup
        $record = 1;

        //Assert
        $client = $this->getMock(self::API_CLIENT);
        $client->expects($this->once())->method('executeApi')->with($this->equalTo('deleterecord/'.$record),$this->equalTo(array()));

        //Do
        $api = new Rage4Api($client);
        $api->deleteRecord($record);
    }
} 
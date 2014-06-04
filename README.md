Rage4 DNS PHP5 class
====================

[![Build Status](https://travis-ci.org/splitice/php-rage4-dns.svg)](https://travis-ci.org/splitice/php-rage4-dns)

This is a PHP5 wrapper to easily integrate Rage4 DNS service (www.rage4.com). There is no official PHP SDK currently.

Number of API calls is not limited at the moment hence no mechanism added to track/limit the same.

The methods introduced in this first release are
- getDomains()
- getDomainByName()
- createDomain()
- createReverseDomain4()
- createReverseDomain6()
- deleteDomain()
- importDomain()
- getRecords()
- getGeoRegions()
- createRecord()
- updateRecord()
- deleteRecord()

You can also consult the official documentation available at the following URL:
http://gbshouse.uservoice.com/knowledgebase/articles/109834-rage4-dns-developers-api

Official set of SDKs by Rage4: http://code.google.com/p/rage4-dns-sdk/ (only .NET available at the moment)

## Requirements
You need PHP 5.3.2+ compiled with the cURL extension.

## Install PHP Rage4 API
### Installing via Composer

The recommended way to install OVH SDK is through [Composer](http://getcomposer.org).

1. Add ``splitice/rage4-api`` as a dependency in your project's ``composer.json`` file:

        {
            "require": {
                "splitice/rage4-api": "dev-master"
            }
        }

2. Download and install Composer:

        curl -s http://getcomposer.org/installer | php

3. Install your dependencies:

        php composer.phar install

4. Require Composer's autoloader

    Composer also prepares an autoload file that's capable of autoloading all of the classes in any of the libraries that it downloads. To use it, just add the following line to your code's bootstrap process:

        require 'vendor/autoload.php';

You can find out more on how to install Composer, configure autoloading, and other best-practices for defining dependencies at [getcomposer.org](http://getcomposer.org).

## Examples

Here are some examples on how to do basic operations.

### Configure the API client
```
using Splitice\Rage4\Rage4Api;
$r4 = new Rage4Api("username","password");
```

### Get all domain names (zones)

	$response = $r4->getDomains();
	print_r($response);
    
### Create a new domain name (zone)

	// createDomain($domain_name, $email)
	$response = $r4->createDomain('my-domain-name-here.com', 'you@yourhost.com');
	print_r($response);

### Create Reverse IPv4 domain

	// createReverseDomain4($domain_name, $email, $subnet)
	$response = $r4->createReverseDomain4('155.39.97.in-addr.arpa', 'you@yourhost.com', '27');
	print_r($response);

### Create Reverse IPv6 domain

	// createReverseDomain6($domain_name, $email, $subnet)
	$response = $r4->createReverseDomain6('0.0.0.0.8.b.d.0.1.0.0.2.ip6.arpa', 'you@yourhost.com', '48');
	print_r($response);

### Delete a new domain name (zone)

In this example, 627 is the ID of the domain zone to be deleted. To know the IDs of the domain zones, do $r4->getDomains(); first

	// deleteDomain($domain_id)
	$response = $r4->deleteDomain(627);
	print_r($response);

### Import a new domain name (zone)

Note! You need to allow AXFR transfers
Note! Only regular domains are supported
        
	// importDomain($domain)
	$response = $r4->importDomain('my-domain-name-here.com');
	print_r($response);

### Get all records of a domain name (zone)

You need to mention the domain ID for which you need to get all records for. Again, if you are unsure please do a $r4->getDomains(); first

	// getRecords($domain_id);
	$response = $r4->getRecords(55);
	print_r($response);

### Create a new record for a particular domain name (zone)

	// createRecord($domain_id, $name, $content, $type="TXT", $priority="", $failover="", $failovercontent="", $ttl=3600)
	$response = $r4->createRecord(55, 'my-domain-name-here.com', 'ns1.4dns.com', "NS", 1500);
	print_r($response);

### Update an existing record

5555 is the record id that was returned from the function $r4->createRecord()

Note! No domain_name/domain_id is required while updating a record
Note! There is no way to update the record-type at the moment, so the easy way is to delete the record first and then recreate with new values (if record-type is changed) e.g. from CNAME to TXT etc

	// updateRecord($record_id, $name, $content, $priority="", $failover="", $failovercontent="", $ttl=3600)
	$response = $r4->updateRecord(5555, 'my-domain-name-here.com', 'ns1.4dns.com', 1500);
	print_r($response);

### Delete an existing record

	// deleteRecord($record_id)
	$response = $r4->deleteRecord(5555);
	print_r($response);

## TODO / Wish List

- Monolog support
 
## Credits

- [Asim Zeeshan](https://github.com/asimzeeshan): The original class
- Piotr Ginalski from gbshouse.com for being very useful :)
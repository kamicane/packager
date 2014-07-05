<?php

require_once __DIR__ . '/../Package.php';

class PackageTest extends PHPUnit_Framework_TestCase
{
	public function test_constructor()
	{
		$package = new Package();
		$package = new Package(__DIR__ . '/fixtures/package.yml');
	}
	
	public function test_parse_sources()
	{
		$package = new Package();
		$package->parse_sources(array(
			__DIR__ . '/fixtures/Source/Core.js',
			__DIR__ . '/fixtures/Source/Browser.js'
		));
		$sources = $package->get_sources();
		$this->assertEquals('Core', $sources[0]->get_name());
		$this->assertEquals('Browser', $sources[1]->get_name());
		
		$package = new Package(__DIR__ . '/fixtures/package.yml');
		$sources = $package->get_sources();
		$this->assertEquals('Core', $sources[0]->get_name());
		$this->assertEquals('Class', end($sources)->get_name());
		$this->assertEquals(4, count($sources));
	}
}

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
	}
}

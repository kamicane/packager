<?php

require_once dirname(__FILE__) . '/../packager.php';

class PackageTest extends PHPUnit_Framework_TestCase
{
	public function test_constructor()
	{
		$packager = new Packager(array());
		$package = new Package($packager, dirname(__FILE__) . '/fixtures/package.yml');
		$packager->add_package($package);
		return $package;
	}
	
	/**
	 * @depends test_constructor
	 */
	public function test_get_manifest($package)
	{
		$manifest = $package->get_manifest();
		$this->assertEquals(4, count($manifest));
		$this->assertEquals('Core', $manifest['name']);
	}
	
	/**
	 * @depends test_constructor
	 */
	public function test_get_source_with_component($package)
	{
		$descriptor = $package->get_source_with_component('Core');
		$this->assertEquals(0, count($descriptor['requires']));
		$this->assertEquals('Core/Core', $descriptor['package/name']);
		
		$descriptor = $package->get_source_with_component('Array');
		$this->assertEquals(1, count($descriptor['requires']));
		$this->assertEquals('Core/Type', $descriptor['requires'][0]);
		$this->assertEquals('Core/Array', $descriptor['package/name']);
	}
}

<?php

require_once dirname(__FILE__) . '/../packager.php';

class PackagerTest extends PHPUnit_Framework_TestCase
{
	public function testConstructor()
	{
		new Packager(array());
		return new Packager(dirname(__FILE__) . '/fixtures/package.yml');
	}
	
	/**
	 * @depends testConstructor
	 */
	public function test_get_all_files($packager)
	{
		$packages_files = $packager->get_all_files();
		$this->assertEquals(4, count($packages_files[0]), 'Should be 4 files');
	}
	
	/**
	 * @depends testConstructor
	 */
	public function test_get_packages($packager)
	{
		$packages = $packager->get_packages();
		$this->assertEquals('Core', $packages[0]);
	}
}

class PackageTest extends PHPUnit_Framework_TestCase
{
}


<?php

require_once dirname(__FILE__) . '/../packager.php';

class PackagerTest extends PHPUnit_Framework_TestCase
{
	public function test_constructor()
	{
		new Packager(array());
		return new Packager(dirname(__FILE__) . '/fixtures/package.yml');
	}
	
	/**
	 * @depends test_constructor
	 */
	public function test_get_all_files($packager)
	{
		$packages_files = $packager->get_all_files();
		$this->assertEquals(4, count($packages_files[0]), 'Should be 4 files');
	}
	
	/**
	 * @depends test_constructor
	 */
	public function test_get_packages($packager)
	{
		$packages = $packager->get_packages();
		$this->assertEquals('Core', $packages[0]);
	}
	
	/**
	 * @depends test_constructor
	 */
	public function test_resolve_files($packager)
	{
		$files = $packager->resolve_files();
		var_dump($files);
	}
	
	/**
	 * @depends test_constructor
	 */
	public function test_build($packager)
	{
		$build = $packager->build();
		var_dump($build);
	}
}


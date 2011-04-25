<?php

require_once dirname(__FILE__) . '/../packager.php';

class PackagerTest extends PHPUnit_Framework_TestCase
{
	protected static $packager;
	
	public function setUp()
	{
		self::$packager = new Packager(dirname(__FILE__) . '/fixtures/package.yml');
	}
	
	public function test_get_all_files()
	{
		$packages_files = self::$packager->get_all_files();
		$this->assertEquals(4, count($packages_files[0]), 'Should be 4 files');
	}
	
	public function test_get_packages()
	{
		$packages = self::$packager->get_packages();
		$this->assertEquals('Core', $packages[0]);
	}
	
	public function test_complete_file()
	{
		$files = self::$packager->complete_file('Class');
		$this->assertEquals(3, count($files));
		$this->assertEquals('Core/Core', $files[0]);
		$this->assertEquals('Core/Class', $files[2]);
	}
	
	public function test_build()
	{
		$build = self::$packager->build(array('Class'));
		$this->assertRegExp('/name: Core/', $build);
		$this->assertRegExp('/name: Array/', $build);
		$this->assertRegExp('/name: Class/', $build);
		$this->assertNotRegExp('/name: Browser/', $build);
	}
	
	public function test_resolve_files()
	{
		$files = self::$packager->resolve_files(array('Class'));
		$this->assertEquals(3, count($files));
		$this->assertEquals('Core/Core', $files[0]);
		$this->assertEquals('Core/Class', $files[2]);
	}
	
	public function test_complete_files()
	{
		$files = self::$packager->complete_files(array('Class'));
		$this->assertEquals(3, count($files));
		$this->assertEquals('Core/Core', $files[0]);
		$this->assertEquals('Core/Class', $files[2]);
	}
	
	public function test_get_file_source()
	{
		$source = file_get_contents(dirname(__FILE__).'/fixtures/Source/Class.js');
		$this->assertEquals($source, self::$packager->get_file_source('Class'));
	}
}


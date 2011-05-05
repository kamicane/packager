<?php

require_once __DIR__ . '/../Packager.php';

class PackagerTest extends PHPUnit_Framework_TestCase
{
	public function test_constructor()
	{
		$packager = new Packager();
		$packager = Packager::get_instance();
		$packager->add_package(__DIR__ . '/fixtures/package.yml');
		$sources = $packager->get_sources();
		$this->assertEquals(4, count($sources));
		$this->assertEquals('Core', $sources[0]->get_name());
	}
	
	public function test_add_component()
	{
		$packager = new Packager();
		$source = new Source('test');
		
		$this->assertEquals(-1, $packager->get_source_index($source));
		$index = $packager->add_component($source, 'test_add_component');
		$this->assertEquals($index, $packager->get_source_index($source));
	}
	
	public function test_get_required_for_source()
	{
		$packager = Packager::get_instance();
		$packager->add_package(__DIR__ . '/fixtures/package.yml');
		$sources = $packager->get_sources();

		$class = end($sources);

		$required = $packager->get_required_for_source($class);
		
		$this->assertEquals(2, count($required));
		$this->assertEquals('Array', $required[0]->get_name());
		$this->assertEquals('Core', $required[1]->get_name());
	}
	
	public function test_get_source_by_name()
	{
		$packager = Packager::get_instance();
		$source = $packager->get_source_by_name('Class');
		$this->assertEquals('Class', $source->get_name());
	}
	
	public function test_build()
	{
		$packager = Packager::get_instance();
		$source = $packager->get_source_by_name('Class');
		$build = array(
			file_get_contents(__DIR__ . '/fixtures/Source/Core.js'),
			file_get_contents(__DIR__ . '/fixtures/Source/Array.js'),
			file_get_contents(__DIR__ . '/fixtures/Source/Class.js')
		);
		$this->assertEquals(implode("\n", $build), $packager->build($source));
	}
}


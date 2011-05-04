<?php

require_once __DIR__ . '/../Packager.php';

class PackagerTest extends PHPUnit_Framework_TestCase
{
	public function test_constructor()
	{
		$packager = new Packager();
		$packager = new Packager(__DIR__ . '/fixtures/package.yml');
	}
	
	public function test_add_component()
	{
		$packager = new Packager();
		$source = new Source('test');
		
		$this->assertEquals(-1, $packager->get_source_index($source));
		$index = $packager->add_component($source, 'test_add_component');
		$this->assertEquals($index, $packager->get_source_index($source));
	}
	
	public function test_add_dependency()
	{
		$packager = new Packager();		
		$source = new Source('test');
		
		$packager->add_dependency($source, 'Core');
		$sources = $packager->get_sources();
		$this->assertEquals($source, $sources[0]['source'])	;
		$this->assertEquals('Core', $sources[0]['requires'][0]);
	}
}


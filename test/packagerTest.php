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
		$this->assertEquals('Core', $required[0]->get_name());
		$this->assertEquals('Array', $required[1]->get_name());
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
		
		$actual = $packager->build($source);
		$this->assertEquals(implode('', $build), $actual);
		
		return $actual;
	}
	
	/**
	 * @depends test_build
	 */
	public function test_strip_blocks($code)
	{
		$blocks = array('1.2compat');
		
		$code = Packager::strip_blocks($code, $blocks);

		foreach ($blocks as $block){
			$this->assertNotRegExp("/<$block>/", $code, "Should not find '$block' opening block.");
			$this->assertNotRegExp("/<\/$block>/", $code, "Should not find '$block' closing block.");
		}
	}
	
	public function test_remove_package()
	{
		$packager = Packager::get_instance();
		
		$package = new Package();
		$package->set_name('Test Package');
		
		$packager->add_package($package);
		
		$packages = $packager->get_packages();
		$this->assertEquals(2, count($packages));
		$this->assertEquals(end($packages), $package);
		
		$this->assertTrue($packager->remove_package($package));
		
		$packages = $packager->get_packages();
		$this->assertEquals(1, count($packages));
		
		$this->assertFalse($packager->remove_package('Random'));
	}
	
	public function test_remove_package_and_components()
	{
		$packager = Packager::get_instance();
		
		$this->assertTrue($packager->remove_package('Core'));
		
		$sources = $packager->get_sources();
		$this->assertEquals(0, count($sources));
		
		$this->assertFalse(!!$packager->get_source_by_name('Core'));
	}
}


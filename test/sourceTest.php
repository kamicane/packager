<?php

require_once __DIR__ . '/../Source.php';

class SourceTest extends PHPUnit_Framework_TestCase
{
	public function test_constructor()
	{
		$source = new Source('test');
		$source = new Source('test', __DIR__ . '/fixtures/Source/Core.js');
	}
	
	public function test_parse()
	{
		$source_path = __DIR__ . '/fixtures/Source/Class.js';
		$source = new Source('Core');
		
		$source->parse($source_path);
		
		$this->assertEquals('Class', $source->get_name());
		$this->assertEquals(file_get_contents($source_path), $source->get_code());
		$this->assertEquals(array('Class'), $source->get_provides());
		$this->assertEquals(array('Core/Array'), $source->get_requires());
	}
	
	public function test_no_deps_build()
	{
		$source = new Source('test');

		$code = '/* code */';
		$source->set_code($code);
		
		$this->assertEquals($code, $source->build());
	}
	
	public function test_deps_build()
	{
		# todo(ibolmo): Cannot overwrite packages once they're defined.
		$source = new Source('!Core');
		$source->provides('Type');
		$core_code = '/* Core */';
		$source->set_code($core_code);
		
		$source = new Source('Array');
		$source->requires('!Core/Type');
		$array_code = '/* Array */';
		$source->set_code($array_code);
		
		$this->assertEquals($core_code.$array_code, $source->build());
	}
	
	public function test_redundant_deps()
	{
		$source = new Source('Base');
		$base_code = '/* Base */';
		$source->provides('Base');
		$source->set_code($base_code);
		
		$source = new Source('Child');
		$source->provides('Child');
		$source->requires('Base');
		$child_code = '/* Child */';
		$source->set_code($child_code);
		
		$source = new Source('Sibling');
		$source->provides('Sibling');
		$source->requires('Base');
		$sibling_code = '/* Sibling */';
		$source->set_code($sibling_code);
		
		$source = new Source('Container');
		$source->requires(array('Child', 'Sibling'));
		$container_code = '/* Container */';
		$source->set_code($container_code);
		
		$this->assertEquals($base_code.$child_code.$sibling_code.$container_code, $source->build());
	}
}

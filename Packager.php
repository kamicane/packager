<?php

require dirname(__FILE__) . '/Package.php';

class Packager {

	static $instance;
	private static $graph = array();
	
	static function get_instance()
	{
		if (!self::$instance) self::$instance = new Packager();
		return self::$instance;
	}
	
	public function add_package($package)
	{
		if (!is_a($package, 'Package')) $package = new Package($package);
		$this->packages[] = $package;
	}
	
	public function add_component(Source $source, $component)
	{
		throw new Exception('TODO');
	}
	
	public function add_dependency(Source $source, $component)
	{
		throw new Exception('TODO');
	}
	
}

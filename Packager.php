<?php

require_once __DIR__ . '/Package.php';

class Packager {

	static $instance;

	protected $sources = array();
	protected $generators = array();
	protected $keys = array();

	private static $graph = array();
	
	public function __construct($package_paths = '')
	{
		$this->configure();
		if ($package_paths) foreach ((array) $package_paths as $package) $this->add_package($package);
	}
	
	public function configure()
	{
		$this->add_generator('component name', function(Source $source, $component){
			return $component;
		});
		
		$this->add_generator('source name', function(Source $source, $component){
			return $source->get_name();
		});
		
		$this->add_generator('package and source name', function(Source $source, $component){
			return sprintf('%s/%s', $source->get_package_name(), $source->get_name());
		});
	}
	
	public function get_source_index($source)
	{
		$key = $source->get_name();
		return isset($this->keys[$key]) ? $this->keys[$key] : -1;
	}
	
	static function get_instance()
	{
		if (!self::$instance) self::$instance = new Packager();
		return self::$instance;
	}
	
	public function add_component(Source $source, $component)
	{
		$index = $this->get_source_index($source);
		if ($index < 0) $index = array_push($this->sources, array('source' => $source, 'requires' => array())) - 1;

		foreach ($this->generators as $generator){
			$key = call_user_func($generator['callback'], $source, $component);
			$this->set_key($key, $index, $generator['name']);
		}
		
		return $index;
	}
	
	/*
		Add dependency between source and component.
	*/
	public function add_dependency(Source $source, $component)
	{
		$index = $this->get_source_index($source);
		if ($index < 0) throw new Exception("Could not find source '$source'.");
		$this->sources[$index]['requires'][] = $component;
	}
	
	public function add_generator($name, $callback)
	{
		$this->generators[] = array('name' => $name, 'callback' => $callback);
	}
	
	public function add_package($package)
	{
		if (!is_a($package, 'Package')) $package = new Package($package);
		$this->packages[] = $package;
	}
	
	protected function set_key($key, $index, $generator_name)
	{
		if (isset($this->keys[$key])) $this->warn("Generator '$generator_name' set component key '$key'.");
		$this->keys[$key] = $index;
	}
	
	protected function warn($message)
	{
		# todo(ibolmo): log mixin
	}
	
}

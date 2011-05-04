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
	
	static function get_instance()
	{
		if (!self::$instance) self::$instance = new Packager();
		return self::$instance;
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
	
	public function get_sources()
	{
		return $this->sources;
	}
	
	public function add_component(Source $source, $component)
	{
		$index = $this->get_source_index($source);
		if ($index < 0) $index = array_push($this->sources, array('source' => $source, 'requires' => array())) - 1;

		foreach ($this->generators as $name => $callback){
			$key = call_user_func($callback, $source, $component);
			$this->set_key($key, $index, $name);
		}
		
		return $index;
	}
	
	/*
		Add dependency between source and component.
	*/
	public function add_dependency(Source $source, $component)
	{
		$index = $this->get_source_index($source);
		if ($index < 0) $index = array_push($this->sources, array('source' => $source, 'requires' => array())) - 1;
		
		$this->sources[$index]['requires'][] = $component;
	}
	
	public function add_generator($name, $callback)
	{
		$this->generators[$name] = $callback;
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

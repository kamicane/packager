<?php

require dirname(__FILE__) . '/Package.php';

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
	
	public function get_component_index(Source $source, $component)
	{
		foreach ($this->generators as $generator){
			$key = call_user_func($generator['callback'], $source, $component);
			if (isset($this->keys[$key])) return $this->keys[$key];
		}
		return -1;
	}
	
	static function get_instance()
	{
		if (!self::$instance) self::$instance = new Packager();
		return self::$instance;
	}
	
	public function add_component(Source $source, $component)
	{
		$index = $this->get_component_index($source, $component);
		if ($index < 0){
			# todo(ibolmo): this smells because $component is not really useful since right now we're adding source files together, and not components.
			$index = array_push($this->sources, $source) - 1;
			foreach ($this->generators as $generator){
				list($callback, $name) = $generator;
				$this->set_key(call_user_func($callback, $source, $component), $index, $name);
			}
		}
		return $index;
	}
	
	public function add_dependency(Source $source, $component)
	{
		throw new Exception('TODO');
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
		if (isset($this->keys[$key])) throw new Exception("Generator, $generator_name, attempted to override component key, $key.");
		$this->keys[$key] = $index;
	}
	
}

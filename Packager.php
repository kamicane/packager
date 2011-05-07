<?php

require_once __DIR__ . '/Package.php';

class Packager {

	static $instance;

	protected $sources = array();
	protected $generators = array();
	protected $keys = array();
	
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
	
	static function strip_blocks($code, $blocks)
	{
		foreach ((array) $blocks as $block){
			$code = preg_replace_callback("%(/[/*])\s*<$block>(.*?)</$block>(?:\s*\*/)?%s", function($matches){
				return (strpos($matches[2], ($matches[1] == "//") ? "\n" : "*/") === false) ? $matches[2] : "";
			}, $code);
		}
		return $code;
	}
	
	public function add_component(Source $source, $component)
	{
		$index = $this->get_source_index($source);
		if ($index < 0) $index = $this->add_source($source);
		
		foreach ($this->generators as $name => $callback){
			$key = call_user_func($callback, $source, $component);
			$this->set_key($key, $index, $name);
		}
		
		return $index;
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
	
	public function add_source(Source $source)
	{
		$index = array_push($this->sources, $source) - 1;
		return $this->keys[$source->get_name()] = $index;
	}
	
 	public function build(Source $source)
	{
		$build = array($source->get_code());
		
		foreach ($this->get_required_for_source($source) as $required) array_unshift($build, $required->get_code());
		
		return implode('', $build);
	}
	
	public function configure()
	{
		$this->add_generator('component name', function(Source $source, $component){
			return $component;
		});
		
		$this->add_generator('package and source name', function(Source $source, $component){
			return sprintf('%s/%s', $source->get_package_name(), $source->get_name());
		});
		
		$this->add_generator('package and component name', function(Source $source, $component){
			return sprintf('%s/%s', $source->get_package_name(), $component);
		});
	}
	
	public function get_source_by_name($name)
	{
		return isset($this->keys[$name]) ? $this->sources[$this->keys[$name]] : null;
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
	
	public function get_required_for_source(Source $source, &$required = null)
	{
		$return = false;
		if (!$required){
			$return = true;
			$required = array();
		}
		
		foreach ($source->get_requires() as $component){
			if (!isset($this->keys[$component])) throw new Exception("Could not find '$component'.");
			$require = $this->sources[$this->keys[$component]];
			if (!in_array($require, $required)) $required[] = $require;
		}
		foreach ($source->get_requires() as $component){
			$require = $this->sources[$this->keys[$component]];
			if ($require->has_requires()) $this->get_required_for_source($require, $required);
		}
		
		if ($return) return $required;
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

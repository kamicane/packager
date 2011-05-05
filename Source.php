<?php

require_once __DIR__ . '/Packager.php';

class Source
{
	const DESCRIPTOR_REGEX = '/\/\*\s*^---(.*?)^(?:\.\.\.|---)\s*\*\//ms';
	
	protected $code = '';
	protected $provides = array();
	protected $requires = array(); 
	
	public function __construct($package_name, $source_path = '')
	{
		$this->package_name = $package_name;
		if ($source_path) $this->parse($source_path);
	}
	
	public function __toString()
	{
		return $this->get_name();
	}
	
	static function normalize_name($default, $name){
		$exploded = explode('/', $name);
		if (count($exploded) == 1) return array($default, $exploded[0]);
		if (empty($exploded[0])) return array($default, $exploded[1]);
		return array($exploded[0], $exploded[1]);
	}
	
	public function build()
	{
		return Packager::build($this);
	}
	
	public function get_code()
	{
		return $this->code;
	}
	
	public function get_name()
	{
		if (!$this->name) $this->name = basename($this->path, '.js');
		return $this->name;
	}
	
	public function get_package_name()
	{
		return $this->package_name;
	}
	
	public function get_provides()
	{
		return $this->provides;
	}
	
	public function get_requires()
	{
		return $this->requires;
	}
	
	public function has_requires()
	{
		return !empty($this->requires);
	}
	
	public function parse($source_path = '')
	{
		if ($source_path){
			$this->path = $source_path;
			$this->code = file_get_contents($source_path);
		}
		
		if (!$this->code) throw new RuntimeException('Missing the code to parse. Did you forget to supply the source_path or set_code?');
		
		preg_match(self::DESCRIPTOR_REGEX, $this->code, $matches);
		if (empty($matches)) throw new Exception("No yaml header present in $source_path");
		
		$header = YAML::decode($matches[0]);
		
		foreach($header as $key => $value){
			$method = 'parse_' . strtolower($key);
			if (is_callable(array($this, $method))) $this->$method($value);
		}
	}
	
	public function parse_name($name)
	{
		$this->name = $name;
	}
	
	public function parse_provides($provides)
	{
		$provides = (array) $provides;
		$this->provides($provides);
	}
	
	public function parse_requires($requires)
	{
		$requires = (array) $requires;
		foreach ($requires as $i => $require) $requires[$i] = implode('/', self::normalize_name($this->package_name, $require));
		$this->requires($requires);
	}
	
	public function provides($provides)
	{
		$packager = Packager::get_instance();
		foreach ($provides as $component){
			$packager->add_component($this, $component);
			$this->provides[] = $component;
		}
		return $this;
	}
	
	public function requires($requires)
	{
		$this->requires = $requires;
		return $this;
	}
	
	public function set_name($name)
	{
		$this->name = $name;
		return $this;
	}
	
	public function set_code($code)
	{
		$this->code = $code;
		return $this;
	}
}

<?php

require dirname(__FILE__) . '/helpers/yaml.php';

class Source
{
	const DESCRIPTOR_REGEX = '/\/\*\s*^---(.*?)^(?:\.\.\.|---)\s*\*\//ms';
	
	function __construct($package_name, $source_path = '')
	{
		$this->package_name = $package_name;
		
		if ($source_path){
			$this->path = $source_path;
			$this->parse($source_path);
		}
	}
	
	static function parse_name($default, $name){
		$exploded = explode('/', $name);
		if (count($exploded) == 1) return array($default, $exploded[0]);
		if (empty($exploded[0])) return array($default, $exploded[1]);
		return array($exploded[0], $exploded[1]);
	}
	
	public function get_name()
	{
		if (!$this->name) $this->name = basename($this->path, '.js');
		return $this->name;
	}
	
	public function parse($source_path)
	{
		$this->source = file_get_contents($source_path);
		preg_match(self::DESCRIPTOR_REGEX, $this->source, $matches);
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
		foreach ($requires as $i => $require) $require[$i] = implode('/', self::parse_name($this->package_name, $require));
		$this->requires($requires);
	}
	
	public function provides($provides)
	{
		throw new Exception('TODO');
	}
	
	public function requires($requires)
	{
		throw new Exception('TODO');
	}
}

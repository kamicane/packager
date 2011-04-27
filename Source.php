<?php

class Source
{
	const DESCRIPTOR_REGEX = '/\/\*\s*^---(.*?)^(?:\.\.\.|---)\s*\*\//ms';
	
	function __construct($package, $source_path)
	{
		$this->package = $package;
		$this->path = $source_path;
	}
	
	static function parse_name($default, $name){
		$exploded = explode('/', $name);
		if (count($exploded) == 1) return array($default, $exploded[0]);
		if (empty($exploded[0])) return array($default, $exploded[1]);
		return array($exploded[0], $exploded[1]);
	}
	
	public function get_descriptor($source = '')
	{
		if (!$source) $source = file_get_contents($this->path);
		
		preg_match(self::DESCRIPTOR_REGEX, $source, $matches);
		if (empty($matches)) return array();
		
		$descriptor = YAML::decode($matches[0]);

		if (!isset($descriptor['name'])) $descriptor['name'] = basename($this->path, '.js');
		if (!isset($descriptorp['license'])) $descriptor['license'] = array_get($this->manifest, 'license');
		$descriptor['source'] = $source;
		$descriptor['provides'] = (array) array_get($descriptor, 'provides');
		
		$requires = (array) array_get($descriptor, 'requires');
		$descriptor['requires'] = array_map(array($this, 'normalize_requires'), $requires);
		
		return array_merge($descriptor, array(
			'package' => $this->package->get_name(),
			'package/name' => sprintf('%s/%s', $this->package->get_name(), $descriptor['name'])
		));
	}
	
	protected function normalize_requires($require){
		return implode('/', self::parse_name($this->package->get_name(), $require));
	}
}

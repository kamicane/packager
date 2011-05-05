<?php

require_once __DIR__ . '/helpers/yaml.php';
require_once __DIR__ . '/Source.php';

class Package
{
	protected $sources = array();
	
	public function __construct($package_path = '')
	{
		if ($package_path){
			$this->path = $this->resolve_path($package_path);
			$this->root_dir = dirname($this->path);
			$this->parse($this->path);
		}
	}
	
	public function __toString()
	{
		return $this->get_name();
	}
	
	static function decode($path){
		return preg_match('/\.json$/', $path) ? json_decode(file_get_contents($path), true) : YAML::decode_file($path);
	}
	
	static function glob($path, $pattern = '*', $flags = 0, $depth = 0){
		$matches = array();
		$folders = array(rtrim($path, DIRECTORY_SEPARATOR));

		while ($folder = array_shift($folders)){
			$matches = array_merge($matches, glob($folder.DIRECTORY_SEPARATOR.$pattern, $flags));
			if ($depth != 0) {
				$moreFolders = glob($folder.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
				$depth = ($depth < -1) ? -1: $depth + count($moreFolders) - 2;
				$folders = array_merge($folders, $moreFolders);
			}
		}
		return $matches;
	}
	
	public function add_source($source_path = '')
	{
		if (!is_a($source_path, 'Source')) $source_path = new Source($this->get_name(), $source_path);
		$this->sources[] = $source_path;
	}
	
	public function get_name()
	{
		return $this->name;
	}
	
	public function get_sources()
	{
		return $this->sources;
	}
	
	public function parse($package_path)
	{
		$package = self::decode($package_path);
		
		foreach ($package as $key => $value){
			$method = 'parse_' . strtolower($key);
			if (is_callable(array($this, $method))) $this->$method($value);
		}
	}
	
	public function parse_name($name)
	{
		$this->set_name($name);
	}
	
	public function parse_sources($sources)
	{
		# todo(ibolmo): 5, should be a class option.
		if (is_string($sources)) $sources = self::glob($this->path, $sources, 0, 5);
		foreach ($sources as $source) $this->add_source($this->root_dir . '/' . $source);
	}
	
	public function set_name($name)
	{
		$this->name = $name;
		return $this;
	}
	
	public function resolve_path($path){
		if (!is_dir($path) && file_exists($path)) return $path;

		$pathinfo = pathinfo($path);
		$path = $pathinfo['dirname'] . '/' . $pathinfo['basename'] . '/';

		if (file_exists($path . 'package.yml')) return $path . 'package.yml';
		if (file_exists($path . 'package.yaml')) return $path . 'package.yaml';
		if (file_exists($path . 'package.json')) return $path . 'package.json';

		throw new Exception("package.(ya?ml|json) not found in $path.");
	}
}

<?php

class Package
{
	const DESCRIPTOR_REGEX = '/\/\*\s*^---(.*?)^(?:\.\.\.|---)\s*\*\//ms';
	protected $sources = array();
	
	public function __construct(Packager $manager, $path){	
		$this->manager = $manager;
		$this->path = $this->resolve_path($path);
		$this->root_dir = dirname($this->path);

		$this->manifest = self::decode($this->path);
		$this->normalize_manifest();

		$this->name = $this->manifest['name'];
		
		foreach ($this->manifest['sources'] as $i => $source_path) {
			$source_path = $this->root_dir . '/' . $source_path;
			$descriptor = $this->get_descriptor($source_path);
			$this->sources[$descriptor['name']] = array_merge($descriptor, array(
				'package' => $this->name,
				'path' => $source_path,
				'package/name' => sprintf('%s/%s', $this->name, $descriptor['name'])
			));
		}
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
	
	static function parse_name($default, $name){
		$exploded = explode('/', $name);
		$length = count($exploded);
		if ($length == 1) return array($default, $exploded[0]);
		if (empty($exploded[0])) return array($default, $exploded[1]);
		return array($exploded[0], $exploded[1]);
	}
	
	public function get_descriptor($source_path){
		$source = file_get_contents($source_path);
		
		preg_match(self::DESCRIPTOR_REGEX, $source, $matches);
		if (empty($matches)) return array();
		
		$descriptor = YAML::decode($matches[0]);

		if (!isset($descriptor['name'])) $descriptor['name'] = basename($source_path, '.js');
		if (!isset($descriptorp['license'])) $descriptor['license'] = array_get($this->manifest, 'license');
		$descriptor['source'] = $source;
		$descriptor['provides'] = (array) array_get($descriptor, 'provides');
		
		$requires = (array) array_get($descriptor, 'requires');
		$descriptor['requires'] = array_map(array($this, 'normalize_requires'), $requires);
		
		return $descriptor;
	}
	
	public function get_files(){
		$files = array();
		foreach ($this->sources as $descriptor) $files[] = $descriptor['package/name'];
		return $files;
	}
	
	public function get_manifest(){
		return $this->manifest;
	}
	
	public function get_name(){
		return $this->name;
	}
	
	public function get_source_with_component($component){
		foreach ($this->sources as $source){
			if (array_contains($source['provides'], $component)) return $source;
		}
		return null;
	}
	
	public function get_source_with_file($name){
		return array_get($this->sources, $name);
	}
	
	public function get_root_dir(){
		return $this->root_dir;
	}
	
	public function resolve_path($path){
		if (!is_dir($path) && file_exists($path)) return $path;
		
		$pathinfo = pathinfo($path);
		$path = $pathinfo['dirname'] . '/' . $pathinfo['basename'] . '/';
		
		if (file_exists($path . 'package.yml')) return $path . 'package.yml';
		if (file_exists($path . 'package.yaml')) return $path . 'package.yaml';
		if (file_exists($path . 'package.json')) return $path . 'package.json';
		
		throw new Exception("package.(yml|yaml|json) not found in $path.");
	}
	
	# todo(ibolmo): Consider passing a callback function `onError` rather than passing Packager instance in constructor.
	public function validate(){		
		foreach ($this->sources as $descriptor){
			$file_requires = $descriptor['requires'];
			foreach ($file_requires as $component){
				if (!$this->manager->component_exists($component)){
					# todo(ibolmo): Pass, or create, a logger in constructor rather than depend on Packager
					Packager::warn("WARNING: The component $component, required in the file " . $file['package/name'] . ", has not been provided.\n");
				}
			}
		}
	}
	
	protected function normalize_manifest(){
		$manifest = $this->manifest;
		if (!is_array($manifest['sources'])){
			# todo(ibolmo): 5, should be a class option
			$manifest['sources'] = self::glob($this->path, $manifest['sources'], 0, 5);
			foreach ($manifest['sources'] as $i => $source_path) $manifest['sources'][$i] = $this->root_dir . $source_path;
 		}
	}
	
	protected function normalize_requires($require){
		return implode('/', self::parse_name($this->name, $require));
	}
}

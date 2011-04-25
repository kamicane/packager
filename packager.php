<?php

require dirname(__FILE__) . "/helpers/yaml.php";
require dirname(__FILE__) . "/helpers/array.php";

class Package
{
	const DESCRIPTOR_REGEX = '/\/\*\s*^---(.*?)^(?:\.\.\.|---)\s*\*\//ms';
	protected $sources = array();
	
	public function __construct(Packager $manager, $path)
	{	
		$this->manager = $manager;
		$this->path = $this->resolve_path($path);
		$this->root_dir = dirname($this->path);

		$this->manifest = self::decode($this->path);
		$this->normalize_manifest();

		$this->name = $this->manifest['name'];
		
		foreach ($this->manifest['sources'] as $i => $source_path) {
			$descriptor = $this->get_descriptor($this->root_dir . '/' . $source_path);
			$this->sources[$descriptor['name']] = array_merge($descriptor, array(
				'package' => $this->name,
				'path' => $source_path,
				'package/name' => sprintf('%s/%s', $this->name, $descriptor['name'])
			));
		}
	}
	
	static function decode($path)
	{
		return preg_match('/\.json$/', $path) ? json_decode(file_get_contents($path), true) : YAML::decode_file($path);
	}
	
	static function glob($path, $pattern = '*', $flags = 0, $depth = 0)
	{
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
	
	static function parse_name($default, $name)
	{
		$exploded = explode('/', $name);
		$length = count($exploded);
		if ($length == 1) return array($default, $exploded[0]);
		if (empty($exploded[0])) return array($default, $exploded[1]);
		return array($exploded[0], $exploded[1]);
	}
	
	public function get_descriptor($source_path)
	{
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
	
	public function get_files()
	{
		$files = array();
		foreach ($this->sources as $descriptor) $files[] = $descriptor['package/name'];
		return $files;
	}
	
	public function get_manifest()
	{
		return $this->manifest;
	}
	
	public function get_name()
	{
		return $this->name;
	}
	
	public function get_source_with_component($component)
	{
		foreach ($this->sources as $source){
			if (array_contains($source['provides'], $component)) return $source;
		}
		return null;
	}
	
	public function get_source_with_file($name)
	{
		return array_get($this->sources, $name);
	}
	
	public function get_root_dir()
	{
		return $this->root_dir;
	}
	
	public function resolve_path($path)
	{
		if (!is_dir($path) && file_exists($path)) return $path;
		
		$pathinfo = pathinfo($path);
		$path = $pathinfo['dirname'] . '/' . $pathinfo['basename'] . '/';
		
		if (file_exists($path . 'package.yml')) return $path . 'package.yml';
		if (file_exists($path . 'package.yaml')) return $path . 'package.yaml';
		if (file_exists($path . 'package.json')) return $path . 'package.json';
		
		throw new Exception("package.(yml|yaml|json) not found in $path.");
	}
	
	# todo(ibolmo): Consider passing a callback function `onError` rather than passing Packager instance in constructor.
	public function validate()
	{		
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
	
	protected function normalize_manifest()
	{
		$manifest = $this->manifest;
		if (!is_array($manifest['sources'])){
			# todo(ibolmo): 5, should be a class option
			$manifest['sources'] = self::glob($this->path, $manifest['sources'], 0, 5);
			foreach ($manifest['sources'] as $i => $source_path) $manifest['sources'][$i] = $this->root_dir . $source_path;
 		}
	}
	
	protected function normalize_requires($require)
	{
		return implode('/', self::parse_name($this->name, $require));
	}
}


class Packager {
	
	public static function warn($message){
		$std_err = fopen('php://stderr', 'w');
		fwrite($std_err, $message);
		fclose($std_err);
	}

	public static function info($message){
		$std_out = fopen('php://stdout', 'w');
		fwrite($std_out, $message);
		fclose($std_out);
	}

	private $packages = array();
	private $manifests = array();
	private $root = null;
	private $files = array();

	public function __construct($package_paths){
		foreach ((array) $package_paths as $package_path) $this->add_package($package_path);
	}
	
	public function add_package($package_path){		
		$package = ($package_path instanceof Package) ? $package_path : new Package($this, $package_path);
		$name = $package->get_name();
		if (!$this->root) $this->root = $name;
		if (array_has($this->manifests, $name)) continue;
		$this->packages[$name] = $package;
		$this->manifests[$name] = $package->get_manifest();
	}
	
	public function remove_package($package_name){
		unset($this->packages[$package_name]);
		unset($this->manifests[$package_name]);
	}
	
	// # private HASHES
	
	private function component_to_hash($name){
		list($name, $component) = Package::parse_name($this->root, $name);
		$package = array_get($this->packages, $name);
		return !$package ? null : $package->get_source_with_component($component);
	}
	
	private function file_to_hash($name){
		list($name, $file) = Package::parse_name($this->root, $name);
		$package = array_get($this->packages, $name);
		return !$package ? null : $package->get_source_with_file($file);
	}
	
	public function file_exists($name){
		return $this->file_to_hash($name) ? true : false;
	}
	
	public function component_exists($name){
		return $this->component_to_hash($name) ? true : false;
	}
	
	public function package_exists($name){
		return array_contains($this->get_packages(), $name);
	}
	
	public function validate($more_files = array(), $more_components = array(), $more_packages = array()){

		foreach ($this->packages as $name => $package) $package->validate();
		
		foreach ($more_files as $file){
			if (!$this->file_exists($file)) self::warn("WARNING: The required file $file could not be found.\n");
		}
		
		foreach ($more_components as $component){
			if (!$this->component_exists($component)) self::warn("WARNING: The required component $component could not be found.\n");
		}
		
		foreach ($more_packages as $package){
			if (!$this->package_exists($package)) self::warn("WARNING: The required package $package could not be found.\n");
		}
	}

	public function resolve_files($files = array(), $components = array(), $packages = array(), $blocks = array(), $excluded = array()){
		if (!empty($components)){
			$more = $this->components_to_files($components);
			foreach ($more as $file) array_include($files, $file);
		}
		
		foreach ($packages as $package){
			$more = $this->get_all_files($package);
			foreach ($more as $file) array_include($files, $file);	
		}
		
		$files = $this->complete_files($files);
		
		if (!empty($excluded)){
			$less = array();
			foreach ($this->components_to_files($excluded) as $file) array_include($less, $file);
			$exclude = $this->complete_files($less);
			$files = array_diff($files, $exclude);
		}
		return $files;
	}

	// # public BUILD
	public function build($files = array(), $components = array(), $packages = array(), $blocks = array(), $excluded = array()){

		$files = $this->resolve_files($files, $components, $packages, $blocks, $excluded);
		
		if (empty($files)) return '';
		
		$included_sources = array();
		foreach ($files as $file) $included_sources[] = $this->get_file_source($file);
		
		$source = implode($included_sources, "\n\n");
		
		return $this->remove_blocks($source, $blocks) . "\n";
	}

	public function remove_blocks($source, $blocks){
		foreach ($blocks as $block){
			$source = preg_replace_callback("%(/[/*])\s*<$block>(.*?)</$block>(?:\s*\*/)?%s", array($this, "block_replacement"), $source);
		}
		return $source;
	}

	private function block_replacement($matches){
		return (strpos($matches[2], ($matches[1] == "//") ? "\n" : "*/") === false) ? $matches[2] : "";
	}
	
	public function build_from_files($files){
		return $this->build($files);
	}
	
	public function build_from_components($components, $excluded = null){
		return $this->build(array(), $components, array(), array(), $excluded);
	}

	public function write_from_files($file_name, $files = null){
		$full = $this->build_from_files($files);
		file_put_contents($file_name, $full);
	}

	public function write_from_components($file_name, $components = null, $exclude = null){
		$full = $this->build_from_components($components, $exclude);
		file_put_contents($file_name, $full);
	}
	
	// # public FILES

	public function get_all_files($of_package = null){
		$files = array();
		foreach ($this->packages as $name => $package){
			if ($of_package == null || $of_package == $name) $files[] = $package->get_files();
		}
		return $files;
	}

	public function get_file_dependancies($file){
		$this->files = array();
		$deps = $this->parse_file_dependancies($file);
		return $deps;
	}

	private function parse_file_dependancies($file){
		$deps = array();
		$hash = $this->file_to_hash($file);

		if (empty($hash)) return array();
		if (!in_array($file, $this->files)) {
			$this->files[] = $file;
			$files = $this->components_to_files($hash['requires']);
			$files = array_diff($files, $this->files);
			$deps = $this->complete_files($files);
		}
		return $deps;
	}

	public function complete_file($file){
		$files = $this->parse_file_dependancies($file);
		$hash = $this->file_to_hash($file);
		if (empty($hash)) return array();
		array_include($files, $hash['package/name']);
		return $files;
	}
	
	public function complete_files($files){
		$ordered_files = array();
		foreach ($files as $file){
			$all_files = $this->complete_file($file);
			foreach ($all_files as $one_file) array_include($ordered_files, $one_file);
		}
		return $ordered_files;
	}
	
	// # public COMPONENTS
	
	public function component_to_file($component){
		return array_get($this->component_to_hash($component), 'package/name');
	}
	
	public function components_to_files($components){
		$files = array();
		foreach ($components as $component){
			$file_name = $this->component_to_file($component);
			if (!empty($file_name) && !in_array($file_name, $files)) $files[] = $file_name;
		}
		return $files;
	}
	
	// # dynamic getter for PACKAGE properties and FILE properties
	
	public function __call($method, $arguments){
		if (strpos($method, 'get_file_') === 0){
			$file = array_get($arguments, 0);
			if (empty($file)) return null;
			$key = substr($method, 9);
			$hash = $this->file_to_hash($file);
			return array_get($hash, $key);
		}
		
		if (strpos($method, 'get_package_') === 0){
			$key = substr($method, 12);
			$package = array_get($arguments, 0);
			$package = array_get($this->manifests, (empty($package)) ? $this->root : $package);
			return array_get($package, $key);
		}
		
		return null;
	}

	public function get_packages(){
		return array_keys($this->packages);
	}
	
	// authors normalization
	
	public function get_package_authors($package = null){
		if (empty($package)) $package = $this->root;
		$package = array_get($this->manifests, $package);
		if (empty($package)) return array();
		return $this->normalize_authors(array_get($package, 'authors'), array_get($package, 'author'));
	}
	
	public function get_file_authors($file){
		$hash = $this->file_to_hash($file);
		if (empty($hash)) return array();
		return $this->normalize_authors(array_get($hash, 'authors'), array_get($hash, 'author'), $this->get_package_authors());
	}
	
	private function normalize_authors($authors = null, $author = null, $default = null){
		$use = empty($authors) ? $author : $authors;
		if (empty($use)) $use = $default;
		return (array) $use;
	}
	
}

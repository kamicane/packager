<?php

require dirname(__FILE__) . "/helpers/yaml.php";
require dirname(__FILE__) . "/helpers/array.php";
require dirname(__FILE__) . '/Package.php';

class Packager {

	private $files = array();
	private $manifests = array();
	private $packages = array();
	private $root = null;

	public function __construct($package_paths){
		foreach ((array) $package_paths as $package_path) $this->add_package($package_path);
	}

	static function info($message){
		$std_out = fopen('php://stdout', 'w');
		fwrite($std_out, $message);
		fclose($std_out);
	}
	
	static function warn($message){
		$std_err = fopen('php://stderr', 'w');
		fwrite($std_err, $message);
		fclose($std_err);
	}
	
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
	
	public function add_package($package_path){		
		$package = ($package_path instanceof Package) ? $package_path : new Package($this, $package_path);
		$name = $package->get_name();
		if (!$this->root) $this->root = $name;
		if (array_has($this->manifests, $name)) continue;
		$this->packages[$name] = $package;
		$this->manifests[$name] = $package->get_manifest();
	}

	public function build($files = array(), $components = array(), $packages = array(), $blocks = array(), $excluded = array()){

		$files = $this->resolve_files($files, $components, $packages, $blocks, $excluded);
		
		if (empty($files)) return '';
		
		$included_sources = array();
		foreach ($files as $file) $included_sources[] = $this->get_file_source($file);
		
		$source = implode($included_sources, "\n\n");
		
		return $this->remove_blocks($source, $blocks) . "\n";
	}
	
	public function build_from_components($components, $excluded = null){
		return $this->build(array(), $components, array(), array(), $excluded);
	}
	
	public function build_from_files($files){
		return $this->build($files);
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
	
	public function component_to_file($component){
		return array_get($this->component_to_hash($component), 'package/name');
	}
	
	public function component_exists($name){
		return $this->component_to_hash($name) ? true : false;
	}
	
	public function components_to_files($components){
		$files = array();
		foreach ($components as $component){
			$file_name = $this->component_to_file($component);
			if (!empty($file_name) && !in_array($file_name, $files)) $files[] = $file_name;
		}
		return $files;
	}
	
	public function file_exists($name){
		return $this->file_to_hash($name) ? true : false;
	}

	public function get_all_files($of_package = null){
		$files = array();
		foreach ($this->packages as $name => $package){
			if ($of_package == null || $of_package == $name) $files = array_merge($files, $package->get_files());
		}
		return $files;
	}
	
	public function get_file_authors($file){
		$hash = $this->file_to_hash($file);
		if (empty($hash)) return array();
		return $this->normalize_authors(array_get($hash, 'authors'), array_get($hash, 'author'), $this->get_package_authors());
	}

	public function get_file_dependancies($file){
		$this->files = array();
		$deps = $this->parse_file_dependancies($file);
		return $deps;
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
	
	public function package_exists($name){
		return array_contains($this->get_packages(), $name);
	}
	
	public function remove_blocks($source, $blocks){
		foreach ($blocks as $block){
			$source = preg_replace_callback("%(/[/*])\s*<$block>(.*?)</$block>(?:\s*\*/)?%s", array($this, "block_replacement"), $source);
		}
		return $source;
	}
	
	public function remove_package($package_name){
		unset($this->packages[$package_name]);
		unset($this->manifests[$package_name]);
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

	public function write_from_files($file_name, $files = null){
		$full = $this->build_from_files($files);
		file_put_contents($file_name, $full);
	}

	public function write_from_components($file_name, $components = null, $exclude = null){
		$full = $this->build_from_components($components, $exclude);
		file_put_contents($file_name, $full);
	}
	
	private function block_replacement($matches){
		return (strpos($matches[2], ($matches[1] == "//") ? "\n" : "*/") === false) ? $matches[2] : "";
	}
	
	private function component_to_hash($name){
		list($name, $component) = Source::parse_name($this->root, $name);
		$package = array_get($this->packages, $name);
		return !$package ? null : $package->get_source_with_component($component);
	}
	
	private function file_to_hash($name){
		list($name, $file) = Source::parse_name($this->root, $name);
		$package = array_get($this->packages, $name);
		return !$package ? null : $package->get_source_with_file($file);
	}
	
	private function normalize_authors($authors = null, $author = null, $default = null){
		$use = empty($authors) ? $author : $authors;
		if (empty($use)) $use = $default;
		return (array) $use;
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
}

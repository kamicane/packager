<?php

require dirname(__FILE__) . "/helpers/yaml.php";
require dirname(__FILE__) . "/helpers/array.php";

Class Packager {
	
	public static function warn($message){
		$std_err = fopen('php://stderr', 'w');
		fwrite($std_err, $message);
		fclose($std_err);
	}

	private $packages = array();
	private $manifests = array();
	private $root = null;
	
	public function __construct($package_paths){
		foreach ((array)$package_paths as $package_path) $this->parse_manifest($package_path);
	}
	
	private function parse_manifest($path){
		$pathinfo = pathinfo($path);
		
		if (is_dir($path)){
			
			$package_path = $pathinfo['dirname'] . '/' . $pathinfo['basename'] . '/';
			
			if (file_exists($package_path . 'package.yml')){
				$manifest_path = $package_path . 'package.yml';
				$manifest_format = 'yaml';
			} else if (file_exists($package_path . 'package.yaml')){
				$manifest_path = $package_path . 'package.yaml';
				$manifest_format = 'yaml';
			} else if (file_exists($package_path . 'package.json')){
				$manifest_path = $package_path . 'package.json';
				$manifest_format = 'json';
			}

		} else if (file_exists($path)){
			$package_path = $pathinfo['dirname'] . '/';
			$manifest_path = $package_path . $pathinfo['basename'];
			$manifest_format = $pathinfo['extension'];
		}
		
		if ($manifest_format == 'json') $manifest = json_decode(file_get_contents($manifest_path), true);
		else if ($manifest_format == 'yaml' || $manifest_format == 'yml') $manifest = YAML::decode_file($manifest_path);
		
		if (empty($manifest)) throw new Exception("manifest not found in $package_path, or unable to parse manifest.");

		$package_name = $manifest['name'];
		
		if ($this->root == null) $this->root = $package_name;

		if (array_has($this->manifests, $package_name)) return;

		$manifest['path'] = $package_path;
		$manifest['manifest'] = $manifest_path;
		
		$this->manifests[$package_name] = $manifest;
		
		foreach ($manifest['sources'] as $i => $path){
			
			$path = $package_path . $path;
			
			// this is where we "hook" for possible other replacers.
			$source = file_get_contents($path);

			$descriptor = array();

			// get contents of first comment
			preg_match('/\/\*\s*^---(.*?)^\.\.\.\s*\*\//ms', $source, $matches);

			if (!empty($matches)) $descriptor = YAML::decode($matches[0]);

			// populate / convert to array requires and provides
			$requires = (array)(!empty($descriptor['requires']) ? $descriptor['requires'] : array());
			$provides = (array)(!empty($descriptor['provides']) ? $descriptor['provides'] : array());
			$file_name = !empty($descriptor['name']) ? $descriptor['name'] : basename($path, '.js');

			// "normalization" for requires. Fills up the default package name from requires, if not present.
			foreach ($requires as $i => $require)
				$requires[$i] = implode('/', $this->parse_name($package_name, $require));
			
			$license = array_get($descriptor, 'license');
			
			$this->packages[$package_name][$file_name] = array_merge($descriptor, array(
				'package' => $package_name,
				'requires' => $requires,
				'provides' => $provides,
				'source' => $source,
				'path' => $path,
				'package/name' => $package_name . '/' . $file_name,
				'license' => empty($license) ? array_get($manifest, 'license') : $license
			));

		}
	}
	
	public function add_package($package_path){
		$this->parse_manifest($package_path);
	}
	
	public function remove_package($package_name){
		unset($this->packages[$package_name]);
		unset($this->manifests[$package_name]);
	}
	
	// # private UTILITIES
	
	private function parse_name($default, $name){
		$exploded = explode('/', $name);
		$length = count($exploded);
		if ($length == 1) return array($default, $exploded[0]);
		if (empty($exploded[0])) return array($default, $exploded[1]);
		return array($exploded[0], $exploded[1]);
	}
	
	// # private HASHES
	
	private function component_to_hash($name){
		$pair = $this->parse_name($this->root, $name);
		$package = array_get($this->packages, $pair[0]);

		if (!empty($package)){
			$component = $pair[1];

			foreach ($package as $file => $data){
				foreach ($data['provides'] as $c){
					if ($c == $component) return $data;
				}
			}
		}
		
		return null;
	}
	
	private function file_to_hash($name){
		$pair = $this->parse_name($this->root, $name);
		$package = array_get($this->packages, $pair[0]);

		if (!empty($package)){
			$file_name = $pair[1];

			foreach ($package as $file => $data){
				if ($file == $file_name) return $data;
			}
		}
		
		return null;
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

		foreach ($this->packages as $name => $files){
			foreach ($files as $file){
				$file_requires = $file['requires'];
				foreach ($file_requires as $component){
					if (!$this->component_exists($component)){
						self::warn("WARNING: The component $component, required in the file " . $file['package/name'] . ", has not been provided.\n");
					}
				}
			}
		}
		
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
	
	// # public BUILD
	
	public function build($files = array(), $components = array(), $packages = array(), $blocks = array()){

		if (!empty($components)){
			$more = $this->components_to_files($components);
			foreach ($more as $file) array_include($files, $file);
		}
		
		foreach ($packages as $package){
			$more = $this->get_all_files($package);
			foreach ($more as $file) array_include($files, $file);	
		}
		
		$files = $this->complete_files($files);
		
		if (empty($files)) return '';
		
		$included_sources = array();
		foreach ($files as $file) $included_sources[] = $this->get_file_source($file);
		
		$source = implode($included_sources, "\n\n");
		
		foreach ($blocks as $block){
			$source = preg_replace_callback("%(/[/*])\s*<$block>(.*?)</$block>(?:\s*\*/)?%s", array($this, "block_replacement"), $source);
		}
		
		return $source . "\n";
	}
	
	private function block_replacement($matches){
		return (strpos($matches[2], ($matches[1] == "//") ? "\n" : "*/") === false) ? $matches[2] : "";
	}
	
	public function build_from_files($files){
		return $this->build($files);
	}
	
	public function build_from_components($components){
		return $this->build(array(), $components);
	}

	public function write_from_files($file_name, $files = null){
		$full = $this->build_from_files($files);
		file_put_contents($file_name, $full);
	}

	public function write_from_components($file_name, $components = null){
		$full = $this->build_from_components($components);
		file_put_contents($file_name, $full);
	}
	
	// # public FILES

	public function get_all_files($of_package = null){
		$files = array();
		foreach ($this->packages as $name => $package){
			if ($of_package == null || $of_package == $name) foreach ($package as $file){
				$files[] = $file['package/name'];
			}
		}
		return $files;
	}
	
	public function get_file_dependancies($file){
		$hash = $this->file_to_hash($file);
		if (empty($hash)) return array();
		return $this->complete_files($this->components_to_files($hash['requires']));
	}
	
	public function complete_file($file){
		$files = $this->get_file_dependancies($file);
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
		if (empty($use) && !empty($default)) return $default;
		if (is_array($use)) return $use;
		if (empty($use)) return array();
		return array($use);
	}
	
}

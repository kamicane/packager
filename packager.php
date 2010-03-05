<?php

require __DIR__ . "/helpers/yaml.php";
require __DIR__ . "/helpers/array.php";

Class Packager {
	
	private $package_path;
	private $manifest;
	private $files;
	private $packages;
	
	public function __construct($package_path){

		$this->package_path = preg_replace('/\/$/', '', $package_path) . '/';
		
		$this->manifest = YAML::decode_file($this->package_path . 'package.yml');
		
		$this->files = array();

		foreach ($this->manifest['sources'] as $i => $path){
			
			$path = $this->package_path . $path;
	
			$file = file_get_contents($path);
	
			// yaml header
			preg_match("/\/\*\s*[-]{3}(.*)[.]{3}\s*\*\//s", $file, $matches); // this is a crappy regexp :)
			
			// hack to support unindented lists. hell might break loose. -- Taken from http://github.com/Guille/PluginsKit by Guillermo Rauch
			$rawYAML = preg_replace('/$([\s]+)-/m', '$1 -', trim($matches[1]));
			
			$descriptor = YAML::decode($rawYAML);
	
			// populate / convert to array requires and provides
			
			$requires = !empty($descriptor['requires']) ? $descriptor['requires'] : array();
			$provides = !empty($descriptor['provides']) ? $descriptor['provides'] : array();
			$name = !empty($descriptor['name']) ? $descriptor['name'] : basename($path, '.js');

			if (!is_array($requires)) $requires = array($requires);
			if (!is_array($provides)) $provides = array($provides);
			
			// Strip out beginning "/" to support `requires: [/Foo, /Bar]`
			foreach ($requires as $i => $require){
				$requires[$i] = preg_replace('/^\//', '', $require);
			}
			
			$this->files[$name] = array(
				'description' => $descriptor['description'],
				'requires' => $requires,
				'provides' => $provides,
				'source' => $file,
				'path' => $path
			);
	
		}
	}
	
	private function parse_manifest(){
		//...
	}
	
	public function build_from_files($files = null){
		$included_files = (is_array($files) && count($files)) ? $this->complete_files($files) : $this->get_all_files();
		
		$included_sources = array();
		foreach ($included_files as $file) $included_sources[] = $this->get_file_source($file);
		
		return $this->replace_build(implode($included_sources, "\n\n"));
	}
	
	public function build_from_components($components = null){
		return $this->build_from_files($this->raw_components_to_files($components));
	}
	
	public function write_from_files($file_name, $files = null){
		$full = $this->build_from_files($files);
		file_put_contents($file_name, $full);
	}
	
	public function write_from_components($file_name, $components = null){
		return $this->write_from_files($file_name, $this->raw_components_to_files($components));
	}
	
	// # public SCRIPTS
	
	public function get_all_files(){
		$files = array();
		foreach ($this->files as $file => $data) $files[] = $file;
		return $this->complete_files($files);
	}
	
	public function get_file_depends($file, &$buffer = array()){
		$requires = $this->raw_components_to_files($this->files[$file]['requires']);
		
		foreach ($requires as $s) $this->get_file_depends($s, $buffer);
		
		foreach ($requires as $s) array_include($buffer, $s);
		
		return $buffer;
	}
	
	public function get_file_path($file){
		return $this->files[$file]['path'];
	}
	
	public function get_file_source($file){
		return $this->files[$file]['source'];
	}
	
	public function get_file_description($file){
		return $this->files[$file]['description'];
	}
	
	public function get_file_provides($file){
		return $this->files[$file]['provides'];
	}
	
	public function complete_files($files){
		$ordered_files = array();
		
		foreach ($files as $file){
			$requires = $this->get_file_depends($file);
			
			foreach ($requires as $s) array_include($ordered_files, $s);
			
			array_include($ordered_files, $file);
		}
		
		return $ordered_files;
	}
	
	// # public COMPONENTS
	
	public function get_component_files($component){
		$result = array();
		
		foreach ($this->files as $file => $data){
			$provides = $data['provides'];
			
			foreach ($provides as $c){
				if ($c == $component) $result[] = $file;
			}
		}
		
		return $result;
	}
	
	public function components_to_files($components){
		return $this->complete_files($this->raw_components_to_files($components));
	}
	
	// # private COMPONENTS
	
	private function raw_components_to_files($components){
		$files = array();
		$included = array();

		foreach ($components as $component){
			$file_names = $this->get_component_files($component);
			foreach ($file_names as $file_name){
				if (!empty($included[$file_name])) continue;
				$included[$file_name] = true;
				$files[] = $file_name;
			}
		}
		
		return $files;
	}
	
	// # public MANIFEST getter
	
	public function get_key($key){
		return $this->manifest[$key];
	}
	
	// replaces the MooTools %build% dynamic var with the git commit hash
	
	private function replace_build($file){

		$ref = file_get_contents($this->package_path . '.git/HEAD');
		if ($ref){
			preg_match("@ref: ([\w/]+)@", $ref, $matches);
			$ref = file_get_contents($this->package_path . ".git/" . $matches[1]);
			preg_match("@(\w+)@", $ref, $matches);
			$file = str_replace("%build%", $matches[1], $file);
		}
		
		return $file;
		
	}
	
}

?>

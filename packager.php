<?php

require __DIR__ . "/helpers/yaml.php";
require __DIR__ . "/helpers/array.php";

Class Packager {
	
	private $package_path;
	private $manifest;
	private $scripts;
	
	public function __construct($manifest_path){
		
		$this->package_path = dirname($manifest_path) . '/';
		
		$manifest = YAML::decode_file($manifest_path);
		
		$this->manifest = $manifest;
		
		$this->scripts = array();

		foreach ($manifest['sources'] as $i => $path){
			
			$path = $this->package_path . $path;
	
			$script = file_get_contents($path);
	
			// yaml header
			preg_match("/\/\*\s*[-]{3}(.*)[.]{3}\s*\*\//s", $script, $matches); // this is a crappy regexp :)
			$descriptor = YAML::decode($matches[1]);
	
			// populate / convert to array requires and provides
	
			if (!empty($descriptor['requires'])){
				if (!is_array($descriptor['requires'])) $descriptor['requires'] = array($descriptor['requires']);
			} else {
				$descriptor['requires'] = array();
			}
	
			if (!empty($descriptor['provides'])){
				if (!is_array($descriptor['provides'])) $descriptor['provides'] = array($descriptor['provides']);
			} else {
				$descriptor['provides'] = array();
			}
	
			$this->scripts[$descriptor['name']] = array(
				'description' => $descriptor['description'],
				'requires' => $descriptor['requires'],
				'provides' => $descriptor['provides'],
				'source' => $script,
				'path' => $path
			);
	
		}
	}
	
	public function build_scripts($scripts = null){
		$included_scripts = (is_array($scripts) && count($scripts)) ? $this->complete_scripts($scripts) : $this->get_all_scripts();
		
		$included_sources = array();
		foreach ($included_scripts as $script) $included_sources[] = $this->get_script_source($script);
		
		return $this->replace_build(implode($included_sources, "\n\n"));
	}
	
	public function build_components($components = null){
		return $this->build_scripts($this->raw_components_to_scripts($components));
	}
	
	public function write_scripts($file_name, $scripts = null){
		$full = $this->build_scripts($scripts);
		file_put_contents($file_name, $full);
	}
	
	public function write_components($file_name, $components = null){
		return $this->write_scripts($file_name, $this->raw_components_to_scripts($components));
	}
	
	// # public SCRIPTS
	
	public function get_all_scripts(){
		$scripts = array();
		foreach ($this->scripts as $script => $data) $scripts[] = $script;
		return $this->complete_scripts($scripts);
	}
	
	public function get_script_depends($script, &$buffer = array()){
		$requires = $this->raw_components_to_scripts($this->scripts[$script]['requires']);
		
		foreach ($requires as $s) $this->get_script_depends($s, $buffer);
		
		foreach ($requires as $s) array_include($buffer, $s);
		
		return $buffer;
	}
	
	public function get_script_path($script){
		return $this->scripts[$script]['path'];
	}
	
	public function get_script_source($script){
		return $this->scripts[$script]['source'];
	}
	
	public function get_script_description($script){
		return $this->scripts[$script]['description'];
	}
	
	public function get_script_provides($script){
		return $this->scripts[$script]['provides'];
	}
	
	public function complete_scripts($scripts){
		$ordered_scripts = array();
		
		foreach ($scripts as $script){
			$requires = $this->get_script_depends($script);
			
			foreach ($requires as $s) array_include($ordered_scripts, $s);
			
			array_include($ordered_scripts, $script);
		}
		
		return $ordered_scripts;
	}
	
	// # public COMPONENTS
	
	public function get_component_script($component){
		foreach ($this->scripts as $script => $data){
			$provides = $data['provides'];
			
			foreach ($provides as $c){
				if ($c == $component) return $script;
			}
		}
		return null;
	}
	
	public function components_to_scripts($components){
		return $this->complete_scripts($this->raw_components_to_scripts($components));
	}
	
	// # private COMPONENTS
	
	private function raw_components_to_scripts($components){
		$scripts = array();
		$included = array();

		foreach ($components as $component){
			
			$script_name = $this->get_component_script($component);
			if (!empty($included[$script_name])) continue;
			$included[$script_name] = true;
			
			$scripts[] = $script_name;
		}
		
		return $scripts;
	}
	
	// replaces the MooTools %build% dynamic var with the git commit hash
	
	private function replace_build($script){

		$ref = file_get_contents($this->package_path . '.git/HEAD');
		if ($ref){
			preg_match("@ref: ([\w/]+)@", $ref, $matches);
			$ref = file_get_contents($this->package_path . ".git/" . $matches[1]);
			preg_match("@(\w+)@", $ref, $matches);
			$script = str_replace("%build%", $matches[1], $script);
		}
		
		return $script;
		
	}
	
}

?>

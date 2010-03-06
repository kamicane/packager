<?php

require __DIR__ . "/helpers/yaml.php";
require __DIR__ . "/helpers/array.php";

Class Packager {

	private $packages = array();
	private $manifests = array();
	private $root;
	
	public function __construct($package_paths){
		if (!is_array($package_paths)) $package_paths = array($package_paths);
		foreach ($package_paths as $i => $package_path) $this->parse_manifest($package_path, ($i == 0));
	}
	
	private function parse_manifest($package_path, $is_root = false){
		$package_path = preg_replace('/\/$/', '', $package_path) . '/';
		$manifest = YAML::decode_file($package_path . 'package.yml');

		$manifest_name = $manifest['name'];
		
		if ($is_root) $this->root = $manifest_name;
		
		if (!empty($this->manifests[$manifest_name])) return;
		
		$manifest_author = !empty($manifest['author']) ? $manifest['author'] : null;
		$manifest_authors = !empty($manifest['authors']) ? $manifest['authors'] : null;
		$manifest_description = !empty($manifest['description']) ? $manifest['description'] : null;

		$manifest_authors = $this->get_authors($manifest_authors, $manifest_author);
		$manifest_sources = $manifest['sources'];
		$manifest_license = $manifest['license'];
		
		$this->manifests[$manifest_name] = array(
			'authors' => $manifest_authors,
			'license' => $manifest_license,
			'description' => $manifest_description,
			'path' => $package_path
		);

		foreach ($manifest_sources as $i => $path){
			
			$path = $package_path . $path;
			
			// this is where we "hook" for possible other replacers.
			$source = $this->replace_build($package_path, file_get_contents($path));
	
			// yaml header
			preg_match("/\/\*\s*[-]{3}(.*)[.]{3}\s*\*\//s", $source, $matches); // this is a crappy regexp :)
			
			$descriptor = YAML::decode($matches[1]);
	
			// populate / convert to array requires and provides
			$requires = !empty($descriptor['requires']) ? $descriptor['requires'] : array();
			$provides = !empty($descriptor['provides']) ? $descriptor['provides'] : array();
			$file_name = !empty($descriptor['name']) ? $descriptor['name'] : basename($path, '.js');

			if (!is_array($requires)) $requires = array($requires);
			if (!is_array($provides)) $provides = array($provides);
			
			$descriptor_author = !empty($descriptor['author']) ? $descriptor['author'] : null;
			$descriptor_authors = !empty($descriptor['authors']) ? $descriptor['authors'] : null;
			
			//"normalization". Fills up the default package name from requires, if not present.
			foreach ($requires as $i => $require){
				$require = $this->parse_name($manifest_name, $require);
				$requires[$i] = implode('/', $require);
			}
			
			$this->packages[$manifest_name][$file_name] = array(
				'name' => $file_name,
				'full_name' => $manifest_name . '/' . $file_name,
				'package' => $manifest_name,
				'description' => $descriptor['description'],
				'authors' => $this->get_authors($descriptor_authors, $descriptor_author, $manifest_authors),
				'license' => empty($descriptor['license']) ? $manifest_license : $descriptor['license'],
				'requires' => $requires,
				'provides' => $provides,
				'source' => $source,
				'path' => $path
			);

		}
	}
	
	// # private UTILITIES
	
	private function get_authors($authors = null, $author = null, $default = null){
		$use = empty($authors) ? $author : $authors;
		if (empty($use) && !empty($default)) return $default;
		return is_array($use) ? $use : empty($use) ? array() : array($use);
	}
	
	private function parse_name($default, $name){
		$exploded = explode('/', $name);
		$length = count($exploded);
		if ($length == 1) return array($default, $exploded[0]);
		else if (empty($exploded[0])) return array($default, $exploded[1]);
		else return array($exploded[0], $exploded[1]);
	}
	
	private function replace_build($package_path, $file){
		$ref = file_get_contents($package_path . '.git/HEAD');
		if ($ref){
			preg_match("@ref: ([\w/]+)@", $ref, $matches);
			$ref = file_get_contents($package_path . ".git/" . $matches[1]);
			preg_match("@(\w+)@", $ref, $matches);
			$file = str_replace("%build%", $matches[1], $file);
		}
		return $file;
	}
	
	// # private HASHES
	
	private function component_to_hash($name){
		$pair = $this->parse_name($this->root, $name);
		if (!empty($this->packages[$pair[0]])) $package = $this->packages[$pair[0]];
		else return null;
		
		$component = $pair[1];
		
		foreach ($package as $file => $data){
			foreach ($data['provides'] as $c){
				if ($c == $component) return $data;
			}
		}
		
		return null;
	}
	
	private function file_to_hash($name){
		$pair = $this->parse_name($this->root, $name);
		if (!empty($this->packages[$pair[0]])) $package = $this->packages[$pair[0]];
		else return null;
		
		$file_name = $pair[1];
		
		foreach ($package as $file => $data){
			if ($file == $file_name) return $data;
		}
		
		return null;
	}
	
	// # public BUILD
	
	public function build_from_files($files = null){
		$included_files = (is_array($files) && count($files)) ? $this->complete_files($files) : $this->get_all_files();
		
		$included_sources = array();
		foreach ($included_files as $file) $included_sources[] = $this->get_file_source($file);
		
		return implode($included_sources, "\n\n");
	}
	
	public function build_from_components($components = null){
		return $this->build_from_files($this->components_to_files($components));
	}

	public function write_from_files($file_name, $files = null){
		$full = $this->build_from_files($files);
		file_put_contents($file_name, $full);
	}

	public function write_from_components($file_name, $components = null){
		return $this->write_from_files($file_name, $this->components_to_files($components));
	}
	
	// # public FILES

	public function get_all_files(){
		$files = array();
		foreach ($this->packages as $package){
			foreach ($package as $file) $files[] = $file['full_name'];
		}
		return $this->complete_files($files);
	}
	
	public function get_file_dependancies($file){
		$hash = $this->file_to_hash($file);
		if (empty($hash)) return array();
		return $this->components_to_files($hash['requires']);
	}
	
	public function complete_file($file){
		$files = $this->get_file_dependancies($file);
		$hash = $this->file_to_hash($file);
		if (empty($hash)) return array();
		array_include($files, $hash['full_name']);
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
	
	// # piblic FILE properties
	
	public function get_file_name($file){
		$hash = $this->file_to_hash($file);
		return (empty($hash)) ? null : $hash['name'];
	}
	
	public function get_file_path($file){
		$hash = $this->file_to_hash($file);
		return (empty($hash)) ? null : $hash['path'];
	}

	public function get_file_source($file){
		$hash = $this->file_to_hash($file);
		return (empty($hash)) ? null : $hash['source'];
	}

	public function get_file_description($file){
		$hash = $this->file_to_hash($file);
		return (empty($hash)) ? null : $hash['description'];
	}
	
	public function get_file_authors($file){
		$hash = $this->file_to_hash($file);
		return (empty($hash)) ? null : $hash['authors'];
	}

	public function get_file_provides($file){
		$hash = $this->file_to_hash($file);
		return (empty($hash)) ? null : $hash['provides'];
	}
	
	// # public COMPONENTS
	
	public function component_to_file($component){
		$hash = $this->component_to_hash($component);
		return (!empty($hash)) ? $hash['full_name'] : null;
	}
	
	public function components_to_files($components){
		$files = array();
		$included = array();
		foreach ($components as $component){
			$file_name = $this->component_to_file($component);
			if (empty($file_name) || !empty($included[$file_name])) continue;
			$included[$file_name] = true;
			$files[] = $file_name;
		}
		return $this->complete_files($files);
	}
	
	// # public PACKAGES
	
	public function get_package_name($package = null){
		if (empty($package)) $package = $this->root;
		return (!empty($this->manifests[$package])) ? $package : null;
	}
	
	public function get_package_authors($package = null){
		if (empty($package)) $package = $this->root;
		return $this->manifests[$package]['authors'];
	}
	
	public function get_package_path($package = null){
		if (empty($package)) $package = $this->root;
		return $this->manifests[$package]['path'];
	}
	
	public function get_package_license($package = null){
		if (empty($package)) $package = $this->root;
		return $this->manifests[$package]['license'];
	}
	
	public function get_package_description($package = null){
		if (empty($package)) $package = $this->root;
		return $this->manifests[$package]['description'];
	}
	
}

?>

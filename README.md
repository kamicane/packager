Packager
========

Packager is a PHP 5.3 library to concatenate libraries split in multiple files in a single file. It automatically calculates dependancies. Packager requires a yml header syntax in every file, and a `package.yml` manifest file, as seen on the MooTools project.

Packager API
============

Constructor
-----------

The constructor of this class accepts either a path to a package or a list of path to packages. `package.yml` must not be included in the path.

### Example
	
	$pkg = new Packager("/Users/kamicane/Sites/mootools-core/");
	$pkg = new Packager(array("/Users/kamicane/Sites/mootools-core/", "/Users/kamicane/Sites/mootools-more/"));

Adding a manifest
-----------------

* `parse_manifest` » adds a manifest to this instance


Working with files
------------------

### Getters

 * `get_all_files` » gets an ordered list of all files
 * `get_file_dependancies` » gets an ordered list of every file that this file depends on
 * `get_file_path` » gets the file path
 * `get_file_source` » gets the file source
 * `get_file_description` » gets the file description
 * `get_file_provides` » gets a list of the file provided components

### Converters

 * `complete_file` » converts a single file to an ordered list of files
 * `complete_files` » converts a list of files to an ordered list of files

### Generators

 * `build_from_files` » returns a string containing the source of the selected files and their dependancies
 * `write_from_files` » writes a file with the selected files and their dependancies


Working with components
-----------------------

### Converting to files

 * `component_to_file` » gets the name of the file that provides this component
 * `components_to_files` » converts a list of components to an ordered list of files

### Generators

 * `build_from_components` » returns a string containing the source of the selected components and their dependancies
 * `write_from_components` » writes a file with the selected components and their dependancies


Class usage
-----------

### Syntax

	$pkg = new Packager(`$path_to_manifest`);

### Example

	$pkg = new Packager("/Users/kamicane/Sites/mootools-core/");
	
	$pkg->write_from_components("/Users/kamicane/Sites/mootools.js", array('Type', 'Array'));

Command Line usage
------------------

### Syntax

	./build MANIFEST_PATH [COMPONENTS]

* `MANIFEST_PATH` is a filepath to the manifest file for a package *(required)*
* `COMPONENTS` can be one or more components provided by the package *(optional)*

### Example

	./build /Users/kamicane/Sites/mootools-core/ Fx Element Array > /Users/kamicane/Sites/mootools.js  # partial build
	
	./build /Users/kamicane/Sites/mootools-core/ > /Users/kamicane/Sites/mootools.js  # full build

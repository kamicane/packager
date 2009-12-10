Packager API
============

Files
-----

### Getters

 * `get_all_files` » get an ordered list of all files
 * `get_file_depends` » gets an ordered list of every file that this file depends on
 * `get_file_path` » gets the file path
 * `get_file_source` » gets the file source
 * `get_file_description` » gets the file description
 * `get_file_provides` » gets a list of the file provided components

### Converters

 * `complete_files` » converts a list of files to an ordered list of files

### Generators

 * `build_from_files` » returns a string containing the source of the selected files and their dependancies
 * `write_from_files` » writes a file with the selected files and their dependancies


Components
----------

### Getters

 * `get_component_file` » gets the file name that contains this component

### Converters

 * `components_to_files` » converts a list of components to an ordered files list

### Generators

 * `build_from_components` » returns a string containing the source of the selected components and their dependancies
 * `write_from_components` » writes a file with the selected components and their dependancies


Class usage
-----------

### Syntax

	$pkg = new Packager(`$path_to_manifest`);

### Example

	$pkg = new Packager("~/Sites/mootools-core/package.yml");
	
	$pkg->write_from_components("~/Sites/mootools.js", array('Type', 'Array'));

Command Line usage
------------------

### Syntax

	./build `$manifest_path` `$output_path` `$component` `$component` `$component` `$component` …

### Example

	./build ~/Sites/mootools-core/package.yml ~/Sites/mootools.js Fx Element Array //partial build
	
	./build ~/Sites/mootools-core/package.yml ~/Sites/mootools.js //full build

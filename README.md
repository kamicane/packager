Packager API
============

Scripts
-------

### Getters

 * `get_all_scripts` » get an ordered list of all scripts
 * `get_script_depends` » gets an ordered list of every script that this script depends on
 * `get_script_path` » gets the script file path
 * `get_script_source` » gets the script source
 * `get_script_description` » gets the script description
 * `get_script_provides` » gets a list of the script provided components

### Converters

 * `complete_scripts` » converts a list of scripts to an ordered list of scripts


Components
----------

### Getters

 * `get_component_script` » gets the script name that contains this component

### Converters

 * `components_to_scripts` » converts a list of components to an ordered scripts list

Command Line build
------------------

### Syntax

	./build `$manifest_path` `$output_path` `$component` `$component` `$component` `$component` …

### Example

	./build ~/Sites/mootools-core/package.yml ~/Sites/mootools.js Fx Element Array //partial build
	
	./build ~/Sites/mootools-core/package.yml ~/Sites/mootools.js //full build

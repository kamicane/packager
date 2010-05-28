Packager
========

Packager is a PHP 5.2+ library to concatenate libraries split in multiple files in a single file. It automatically calculates dependancies. Packager requires a yml header syntax in every file, and a `package.yml` manifest file, as seen on the MooTools project.

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

 * `get_all_files` » gets an list of all files
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
 * `components_to_files` » converts a list of components to a list of files

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

Packager Command Line script
----------------------------

The Packager command line script is your one-stop solution to build any of your packages at once. Works on unices.

### Syntax

	./packager COMMAND +option1 argument1 argument2 +option2 argument1 argument2

* `COMMAND` a packager command *(required)*
* `+option` options for commands *(optional)*

### Commands

* `register` registers a package. Creates a .packages.yml file in your home folder.
* `unregister` unregisters a package
* `list` list registered packages
* `build` builds a single file with the supplied packages / files / components

### Registering a Package
	
#### Example

	./packager register /Users/kamicane/mootools-core
	» the package Core has been registered

	
### Listing Packages

#### Example

	./packager list
	» Core: /Users/kamicane/mootools-core


### Unregistering a Package

#### Example

	./packager unregister Core
	» the package Core has been unregistered
	
### Building Packages

#### Examples

	./packager build Core/Type Core/Fx ART/ART.Element
	
Which is the same as...
	
	./packager build +components Core/Type Core/Fx ART/ART.Element
	
Which builds the passed in components (and their dependancies) using your registered packages.
	
	./packager build +files Core/Core Core/Fx ART/ART
	
This builds the passed in files (and their dependancies) using your registered packages.
	
	./packager build ART/*
	
Builds every component from ART, and their dependancies, using your registered packages.
	
	./packager build SomePackage/SomeComponent +packages /Users/kamicane/Sites/some-package
	
Builds the selected components using your registered packages and a temporary package that resides in /Users/kamicane/Sites/some-package, without having to register it first.

	./packager build SomePackage/SomeComponent -packages Core
	
Builds the selected components using your registered packages minus the package names you pass to -packages. This lets you build your components without dependancies.

	./packager build ART/SomeComponent +use-only ART
	
Builds the selected components using only ART of your registered packages. This lets you build your components without dependancies.

	./packager build SomePackage/SomeComponent +use-only +packages /Users/kamicane/Sites/some-package
	
Builds the selected components using none of your registered packages plus the passed in package, without registering it. This lets you build your components without dependancies.

	./packager build +components ART/ART +files ART/ART.Base
	
You can mix components and files

	./packager build Core/* > mootools.js
	
This is how you output to a file


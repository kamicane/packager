Packager
========

Packager is a PHP 5.3+ library to concatenate libraries split in multiple files in a single file. It automatically calculates dependancies. 

Packager <del>requires</del> a yml header syntax in every file, and a `package.yml` manifest file, as seen on the MooTools project.

2.0 Branch
----------
This is an experimental branch, dedicated to a more programmatic approach to packaging (building) your JavaScript files. 

With the previous Packager, you were **required** to define a `package.yml`. This is no longer the case. You're still able to work with your `package.json` or `package.yml` files, but you have the ability to define dependencies at runtime and build only the dependencies for that file.

Why?
----
I needed a more dynamic packager. On my web development, each web page varies between JavaScript uses. My home page, for example, might have a login area but the about us page does not. Why should I build a single `mootools.js` for the whole site? Even if I could keep track of what pages require what, it's not my job or the application's job to keep that in order.

Instead, I wanted to: `<?php require_js('homepage.js') ?>`. I wanted homepage.js to have a yml header, and for Packager to build a _specific build_ for this **specific page**. The next step would be to create agent specific builds, but that's for another branch.

API 2.0
=======
Remember, the emphasis has been to create an actual API. The following are classes and their purpose. See the linked wiki pages, for additional method signatures and documentation.

[Packager](#)
Packager is the "registry" of components, and the manager of building the dependencies.

[Package](#)
A Package is a container of many sources. Due to the emphasis on Source, Package is not as useful **yet**.

[Source](#)
A Source has many components (provides) and dependencies (requires). By defining a Source, you can add (provide) to the registry and build all dependencies for the source.

[Command Line Script](#)
This is currently broken, and low priority for me. Pull requests, greatly appreciated.

Since I'm using Packager 2.0 as an API I no longer need a `npm|gem|pear`-like library for my development.


Getting Stated
==============
banana banana banana

For now, take a look at [sfPackagerPlugin](https://github.com/ibolmo/sfPackagerPlugin) for a programmatic usage of the 2.0 branch. In particular take a look at: [PackagerHelper](https://github.com/ibolmo/sfPackagerPlugin/blob/master/lib/helper/PackagerHelper.php) which has the "useful" interface, I had been talking about.



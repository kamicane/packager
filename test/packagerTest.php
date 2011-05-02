<?php

require_once __DIR__ . '/../Packager.php';

class PackagerTest extends PHPUnit_Framework_TestCase
{
	public function test_constructor()
	{
		$packager = new Packager();
		$packager = new Packager(__DIR__ . '/fixtures/package.yml');
	}
}


<?php

require dirname(__FILE__) . "/../libs/spyc.php";

class YAML {
	
	public static function decode($input){
		return Spyc::YAMLLoadString($input);
	}
	
	public static function decode_file($file){
		return (file_exists($file)) ? self::decode(file_get_contents($file)) : null;
	}
	
	public static function encode($input){
		return Spyc::YAMLDump($input);
	}

}

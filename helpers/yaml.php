<?php

require __DIR__ . "/../libs/spyc.php";

class YAML {
	
	public static function decode($input){
		return spyc_load($input);
	}
	
	public static function decode_file($input){
		return (file_exists($input)) ? self::decode(file_get_contents($input)) : null;
	}
	
}

?>

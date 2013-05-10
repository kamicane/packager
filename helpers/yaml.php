<?php

class YAML
{
    /**
     * @param $input
     * @return array
     */
    public static function decode($input)
    {
		return Spyc::YAMLLoadString($input);
	}

    /**
     * @param $file
     * @return array|null
     */
    public static function decode_file($file)
    {
		return (file_exists($file)) ? self::decode(file_get_contents($file)) : null;
	}

    /**
     * @param $input
     * @return string
     */
    public static function encode($input)
    {
		return Spyc::YAMLDump($input);
	}

}

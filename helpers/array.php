<?php

function array_contains($array, $item){
	$index = array_search($item, $array);
	return $index !== false;
}

function array_include(&$array, $item){
	if (!array_contains($array, $item)) $array[] = $item;
	return $array;
}

function array_erase(&$array, $item){
	$index = array_search($item, $array);
	
	if ($index === false) return $array;
	
	array_splice($array, $index, 1);
	
	return $array;
}

function array_has($array, $key){
	return !empty($array) && array_key_exists($key, $array);
}

function array_get($array, $key){
	return (!empty($array) && array_key_exists($key, $array)) ? $array[$key] : null;
}

<?php

function array_contains($array, $item){
	return array_search($item, $array) !== false;
}

function array_include(&$array, $item){
	if (!array_contains($array, $item)) $array[] = $item;
	return $array;
}

function array_erase(&$array, $item){
	foreach ($array as $i => $v){
		if ($array[$i] === $item) array_splice($array, $i, 1);
	}
	return $array;
}

function array_has($array, $key){
	return !empty($array) && array_key_exists($key, $array);
}

function array_get($array, $key){
	return (!empty($array) && array_key_exists($key, $array)) ? $array[$key] : null;
}

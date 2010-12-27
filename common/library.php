<?php

function strip_magic_quotes($arr)
{
	foreach ($arr as $key => $value) {
		if (is_array($arr[$key])) {
			$arr[$key] = $arr[$key];
		} else {
			$arr[$key] = stripslashes($value);
		}
	}
	return $arr;
}

function make_sql_safe($arr)
{
	foreach ($arr as $key => $value) {
		if (is_array($arr[$key])) {
			$arr[$key] = make_sql_safe($arr[$key]);
		} else {
			$arr[$key] = mysql_escape_string(trim($value));
		}
	}
	return $arr;
}

function make_file_safe ($arr)
{
	foreach ($arr as $key => $value) {
		if (is_array($arr[$key])) {
			$arr[$key] = make_file_safe($arr[$key]);
		} else {
			$arr[$key] = preg_replace("/[^a-z0-9_\-\.]/i","",trim($value));
		}
	}
	return $arr;
}


function make_filename_safe($filename) { return preg_replace('/[^a-z0-9_\-\.]/i', '' ,trim($filename)); }

?>
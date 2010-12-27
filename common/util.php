<?php
	// this file contacts a bunch of helper utilities used by the other pages

	define ('SQL_ECHO',	0x01);
	define ('SQL_TEST',	0x02);
	define ('SQL_NOERROR',	0x04);
	
	function errorMessage($msg) { echo $msg; }

	function singleQueryField($query)
	{
		$rs = dbQuery($query);
		$row = mysql_fetch_row($rs);
		return $row[0];
	}

	function singleQuery($query)
	{
		$resultSet = dbQuery($query);
		return mysql_fetch_assoc($resultSet);
	}

	function displayPicklistCustomSQL($sql, $custom, $selectName, $default="null")
	{
		$str = "";
		$enumRS = dbQuery($sql);
		$str .= '<select name="' . $selectName . '" id="' . $selectName . '" onChange="picklistChange()" id="' . $selectName . '">';
		$str .= '<option value="null">--- choose value ---</option>';
		while ($rowEnum = mysql_fetch_assoc($enumRS))
		{
			$value = $rowEnum['id'];
			$name = $rowEnum[$custom];
			$selected = ($default == $value) ? "selected" : "";
			$str .= '<option value="' . $value . '" ' . $selected .'>' . $name . '</option>';
		}
		$str .= '</select>';
		return $str;
	}
	
	function rowCount($sql)
	{
		$rsNumRows = dbQuery($sql);
		return mysql_num_rows($rsNumRows);
	}

	function displayPicklistSQL($sql, $selectName, $default="null")
	{
		return displayPickListCustomSQL($sql, 'name', $selectName, $default);
	}


	function displayPicklist($table, $selectName, $default="null")
	{
		return displayPicklistSQL("select * from " . $table . " order by name", $selectName, $default);
	}

	function displayList($listName, $default) {
		$listId = singleQueryField("select id from lists where name='" . $listName . "'", "id");
		print '<select name="' . $listName . '">';
		print '<option value="null">--- choose value ---</option>';
		$enumRS = mysql_query("select * from listEntries where list=" . $listId) or die(mysql_error());
		while ($rowEnum = mysql_fetch_assoc($enumRS))
		{
			$value = $rowEnum['id'];
			$name = $rowEnum['entry'];
			$selected = ($default == $value) ? "selected" : "";
			print '<option value="' . $value . '" ' . $selected .'>' . $name . '</option>';
		}
		print '</select>';
	}

	function displayNumberedPicklist($table, $selectName, $default) {
		$sql = "select id, name from $table";
		$enumRS = dbQuery($sql);
		print '<select name="' . $selectName . '" id="' . $selectName . '" onChange="picklistChange()" id="' . $selectName . '">';
		print '<option value="null">--- choose value ---</option>';
		while ($rowEnum = mysql_fetch_assoc($enumRS))
		{
			$value = $rowEnum['id'];
			$name = $rowEnum['name'];
			$selected = ($default == $value) ? "selected" : "";
			print '<option value="' . $value . '" ' . $selected .'>' . "$value. $name" . '</option>';
		}
		print '</select>';
	}

	// validates the parameter is a number from 0-255
	function validateNumber255($num)
	{
		if (!ereg("^[0-9]+$", $num))
			return false;

		if ($num < 0 || $num > 255)
			return false;

		return true;
	}

	function validateFormString($str)
	{
		if (strlen($str) == 0)
			return false;

		return true;
	}

	function validateTimeStamp($str)
	{
		if (ereg("^[0-9]+-[0-9]+-[0-9][0-9] [0-9]+:[0-9][0-9] [AP][mM]", $str))
			return true;
		else
			errorMessage("Invalid date / time.");

		return false;
	}

	function validateTime($str)
	{
		if (ereg("^[0-9]+:[0-9][0-9] [AaPp][mM]", $str))
			return true;
		else if (strlen($str)>0)
			errorMessage("Invalid time.");
		else
			return false;
	}

	function validateDate($str)
	{
		if (ereg("^[0-9]+-[0-9]+-[0-9][0-9]", $str))
			return true;
		else if (strlen($str) > 0)
			errorMessage("Invalid date.");
		else
			return false;
	}

	function convertToMySQLDateTime($str)
	{
		if (validateTimeStamp($str)) {
			list($month, $day, $year, $hour, $minute, $ampm) = split('[-:\ ]', $str);
			$year += 2000;
			if ($ampm == "AM" && $hour == "12")
				$hour = 0;
			if ($ampm == "PM" && $hour != "12")
				$hour += 12;
			$newStr = $year . "-" . $month . "-" . $day . " " . $hour . ":" . $minute;
			return $newStr;
		} else return null;
	}

	function convertToMySQLTime($str)
	{
		if (validateTime($str)) {
			list($hour, $minute, $ampm) = split('[-:\ ]', $str);
			$ampm = strtoupper($ampm);
			if ($ampm == "AM" && $hour == "12")
				$hour = 0;
			if ($ampm == "PM" && $hour != "12")
				$hour += 12;
			$newStr = $hour . ":" . $minute;
			return $newStr;
		} else return null;
	}

	function convertToMySQLDate($str)
	{
		if (validateDate($str)) {
			list($month, $day, $year) = split('[-]', $str);
			if ($year > 50)
				$year += 1900;
			else
				$year += 2000;
			$newStr = $year . "-" . $month . "-" . $day;
			return $newStr;
		} else return null;
	}

	function MySQLDateOrNull($str)
	{
		if (strlen($str) > 0)
			return "'$str'";
		else return "null";
	}
	
	function MySQLDateToHuman($str)
	{
		list($year, $month, $day) = split('[-]', $str);
		if (is_numeric($year) && is_numeric($month) && is_numeric($day)) {
			$year %= 100;
			return sprintf("%02d-%02d-%02d", $month, $day, $year);
		}
		
		return '';
	}

	function formattedFilesize($filesize)
	{
		$metric = "";
		if ($filesize >= 1024) {
			$filesize /= 1024;
			$metric = " KB";
			if ($filesize >= 1024) {
				$filesize /= 1024;
				$metric = " MB";
			}
		}
		return sprintf("%1.2f %s", $filesize, $metric);
	}

	function dbQuery($sql, $flags=0)
	{
		if ($flags & SQL_ECHO) echo "<pre>$sql</pre><br>";

		$errorMessage = "<hr><h1>MySQL Error</h1>The site is attempting to execute a command that is generating an error in the ".
			"database.  This is most likely caused by either entering bad data or a due to a program bug. " .
			"If you continue to experience this problem please contact the developer." . 
			"<hr><h3>SQL Command</h3><div style='border: 1px solid #a0a0a0; background-color: #fffff0'>" . $sql .
			"</div><hr><h3>Error Message from database";

		if ($flags & SQL_TEST) return;

		$rs = mysql_query($sql) or (($flags&SQL_NOERROR)) ? die() : die($errorMessage . " (" . mysql_errno() . ")</h3>" . 
			"<div style='border: 1px solid #a0a0a0; background-color: #fffff0'>" . mysql_error() . "</div><hr>");
		return $rs;
	}

	function dbUpdate($sql, $flags=0) { return dbQuery($sql, $flags); }

	function dbRowCount($sql)
	{
		$rs = dbQuery($sql);
		return mysql_num_rows($rs);
	}

	function currency2String($amount)
	{
		return sprintf("%4.2f", $amount);
	}

	function displayTristate($name, $value="")
	{
		if ($value == 'u') $uSelected = "selected";
		else if ($value == 'y') $ySelected = "selected";
		else if ($value == 'n') $nSelected = "selected";
		print '<select name="' . $name . '" id="' . $name . '">';
		print '<option value="u" ' . $uSelected . '>unspecified</option>';
		print '<option value="y" ' . $ySelected . '>yes</option>';
		print '<option value="n" ' . $nSelected . '>no</option>';
	}

	function displayTristateRequired($name)
	{
		if ($name == "needsDriver") $bgColor = 'style="background-color: #fff0f0"';
		print '<select name="' . $name . '" id="' . $name . '"' . $bgColor . '>';
		print '<option value="null">-- choice required --</option>';
		print '<option value="u">unspecified</option>';
		print '<option value="y">yes</option>';
		print '<option value="n">no</option>';
	}

	function tristate($value)
	{
		if ($value == 'y') return "yes";
		else if ($value == 'n') return "no";
		else return "unspecified";
	}

	// returns the number or the string "null"
	function getSQLNum($val) { return (is_numeric($val)) ? $val : "null"; }
	function getSQLStr($val) { return mysql_real_escape_string($val); }
	function getSQLDate($str) { return (strlen($str) > 0) ? "'$str'" : "null"; }
	function getRequestNum($whichVar) { return getSQLNum($_REQUEST[$whichVar]); }
	function getRequestStr($whichVar) { return $_REQUEST[$whichVar]; }
	function getRequestBoolean($val) { return ($_REQUEST[$val])==1 ? 1 : 0; }
	function getRequestCheckBox($whichVar) { return ($_REQUEST[$whichVar]=='on') ? 1 : 0; }
	function stringExists($str) { return (strlen($str)>0); }
	function fileExtension($filename) { return strtolower(substr($filename, strrpos($filename, '.')+1)); }
	function requestDateToDB($str) { return getSQLDate(convertToMySQLDate($_REQUEST[$str])); }
	
	function CRUD_list($table_name, $controller_name, $headers, $arr)
	{
		$create_url = "/$controller_name/create";
		$create_link = '<a href="'.$create_url.'">add a record</a>';
		
		$numHeaders = sizeof($headers);
		
		echo '<table class="listTable">';
		echo '<tr>';
		echo '<th colspan="'.($numHeaders+1).'">'.$table_name.'</td>';
		echo '</tr>';
		echo '<tr>';
		foreach ($headers as $h) {
			echo "<td><b>$h</b></td>";
		}
		echo "<td><b>actions</b></td>";
		echo '</tr>';
		foreach ($arr as $model) {
			$id = $model['id'];
			$edit_url = "/$controller_name/edit/$id";
			$edit_link = '<a href="'.$edit_url.'">edit</a>';
			$delete_url = "/$controller_name/delete/$id";
			$delete_link = '<a href="'.$delete_url.'">delete</a>';
			echo '<tr>';
			foreach ($headers as $h) {
				$value = $model[$h];
				echo "<td>$value</td>";
			}
			echo '<td>'."$edit_link $delete_link".'</td>';
			echo '</tr>';
		}
		echo '<tr class="addRecord"><td colspan="2">'.$create_link.'</td></tr>';
		echo '</table>';
	}
	
	function set_flash($msg) { $_SESSION['flash'] = $msg; }
	function get_flash()
	{
		$msg = $_SESSION['flash'];
		$_SESSION['flash'] = '';
		return $msg;
	}
	
	function getSanitizedRequestParam($param)
	{
		if (isset($_REQUEST[$param])) {
			$p = $_REQUEST[$param];
			if (is_array($p)) {
				$tempArr = array();
				foreach ($p as $k=>$v) {
					$tempArr[$k] = mysql_real_escape_string($v);
				}
				return $tempArr;
			} else {
				return mysql_real_escape_string($p);
			}
		} else {
			return null;
		}
	}
	
	function get_params()
	{
		global $params;
		return $params;
	}
	
	function get_id()
	{
		$params = get_params();
		return $params['id'];
	}
	
	function is_post()
	{
		global $requestMethod;
		return ($requestMethod == "POST") ? true : false;
	}
	
	function is_checked($str)
	{
		return (strtolower($str) == "on") ? 1 : 0;
	}
	
	function render($view)
	{
		include("../../admin/views/$view.html.php");
	}
	
	function make_path($str)
	{
		$len = strlen($str);
		if ($len == 0) return $str;
		$last_slash = strrpos($str, '/');
		if ( $last_slash == ($len-1) )
			return $str;
		else
			return $str . "/";
	}
	
	function yesNo($input)
	{
		return $input ? '<span class="yes">YES</span>' : '<span class="no">no</span>';
	}

?>

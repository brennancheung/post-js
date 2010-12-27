<?php

// get a list of the different types of video types
// $videoTypes = array();
// $rs = dbQuery("SELECT videoTypeID, extension, description, bitRate FROM videoType");
// while ($row = mysql_fetch_assoc($rs)) {
// 	$videoTypeID = $row['videoTypeID'];
// 	$extension = $row['extension'];
// 	$bitRate = $row['bitRate'];
// 	$description = $row['description'];
// 	$str = "$extension - $bitRate - $description";
// 	$videoTypes[$videoTypeID] = $str;
// }

define ('FORM_VALIDATE',	0x01);
define ('FORM_MULTIPART',	0x02);

function has_image_extension($filename)
{
	global $image_extensions;
	
	$temp = pathinfo($filename);
	$extension = strtolower($temp['extension']);
	
	foreach ($image_extensions as $ext) {
		if ($ext == $extension)
			return true;
	}
	
	return false;
}

function has_video_extension($filename)
{
	global $video_extensions;
	
	$temp = pathinfo($filename);
	$extension = strtolower($temp['extension']);
	
	foreach($video_extensions as $ext) {
		if ($ext == $extension)
			return true;
	}
	
	return false;
}

function listTable($tableName, $header)
{
	$numHeaders = sizeof($header);
	echo '<table class="panel">';
	echo '<tr class="panelTitle"><td colspan="'.$numHeaders.'">'.$tableName.'</td><tr>';
	echo '<tr class="headerListing">';
	foreach ($header as $k=>$v) {
		// allow for parameters to be in the form of $headerName or $headerName=>$width
		if (is_numeric($v)) {
			$width = ' width="'.$v.'"';
			$headerName = $k;
		} else {
			$headerName = $v;
		}
		echo "<th$width>$headerName</td>";
	}
	echo '</tr>';

}

function listRow($rowID, $clickURL, $columns)
{
	// counter to determine if the coloring should be even or odd
	static $i;
	if (!is_numeric($i)) $i=0;
	$className = ((++$i%2)==0) ? 'even' : 'odd';

	echo '<tr id="'.$rowID.'" class="'.$className.'" ';
	echo "onMouseOver=\"rowHighlight('$rowID')\" ";
	echo "onMouseOut=\"rowUnhighlight('$rowID')\" ";
	if (strlen($clickURL)>0)
		echo "onClick=\"window.location='$clickURL'\"";
	echo '>';

	foreach($columns as $c) {
		echo "<td>$c</td>";
	}

	echo '</tr>';
}

function endTable() { echo '</table>'; }

// determines if the given user belongs to the specified site or site group
function doesUserBelongToSite($userID, $siteID, $siteGroupID)
{
	// does the user belong to the site group?
	return (dbRowCount("SELECT * FROM user_lnk_siteGroup WHERE userID=$userID AND siteGroupID=$siteGroupID") == 1) ? TRUE : FALSE;

	// does the user belong to the site
	return (dbRowCount("SELECT * FROM user_lnk_site WHERE userID=$userID AND siteID=$siteID") == 1) ? TRUE : FALSE;
}

function getSiteName($siteID)
{
	return singleQueryField('SELECT name FROM site WHERE siteID='.$_SESSION['siteID'], 'name');
}

function getSiteGroupName($siteGroupID)
{
	return singleQueryField('SELECT name FROM siteGroup WHERE siteGroupID='.$_SESSION['siteGroupID'], 'name');
}

function make_schema($str)
{
	$str = strtolower($str);
	$str = preg_replace('/ /', '_', $str);
	$str = preg_replace('/[^A-Za-z0-9_]/', '', $str);
	
	return $str;
}

// form helper
function form($url='', $flags=0)
{
	if (strlen($params)>0)
		$params = "&$params";
	$submit = ($flags & FORM_VALIDATE) ? ' onSubmit="return submitValidation();"' : '';
	$multipart = ($flags & FORM_MULTIPART) ? ' enctype="multipart/form-data"' : '';
	echo '<form action="'.$url.'" method="POST"'.$submit.$multipart.'>';
}

// panel helper
function editPanel($title)
{
	echo '<table class="formPanel">';
	echo '<tr class="panelTitle"><td colspan="2">'.$title.'</td></tr>';
}

function inputText($name, $value='', $size=110)
{
	return '<input type="text" name="'.$name.'" id="'.$name.'" value="'.$value.'" size="'.$size.'">';
}

function button_to($url, $text, $method="POST")
{
	
	echo '<form action="'.$url.'" method="'.$method.'"><input type="submit" value="'.$text.'"/></form>';
}

function inputPassword($name, $value='', $size=110)
{
	return '<input type="password" name="'.$name.'" id="'.$name.'" value="'.$value.'" size="'.$size.'">';
}

function inputTextArea($name, $value='', $cols=100, $rows=10)
{
	return '<textarea name="'.$name.'" id="'.$name.'" cols="'.$cols.'" rows="'.$rows.'" wrap="off">'.$value.'</textarea>';
}

function inputDatePicker($name, $value='')
{
	$color = (strlen($value)>0) ? "#f0fff0" : "#fff0f0";
	$txt = '<input type="text" name="'.$name.'" id="'.$name.'" size="10" onKeyUp="dateValidate(this);" value="'.$value.'" onChange="dateValidate(this);" style="background-color:'.$color.'">';
	$txt .= '<button id="trigger_'.$name.'">select date</button>';
	$txt .= '<script type="text/javascript">';
	$txt .= '	Calendar.setup(';
	$txt .= '		{';
	$txt .= '			inputField	: "'.$name.'",';
	$txt .= '			ifFormat	: "%m-%d-%y",';
	$txt .= '			button		: "trigger_'.$name.'",';
	$txt .= '			singleClick	: false';
	$txt .= '		}';
	$txt .= '		);';
	$txt .= '</script>';

	return $txt;
}

function inputPhotoPicker($name, $photo='', $photoPrefix='/images/')
{
	if (stringExists($photo))
		$imgLink = '<img src="'.$photoPrefix.$photo.'">';

	$txt = '<input type="file" id="'.$name.'" name="'.$name.'">' . $imgLink;
	return $txt;
}

function inputCheckBox($name, $value=false)
{
	$isChecked = $value ? 'CHECKED' : '';
	return '<input type="checkbox" name="'.$name.'" value="1" '.$isChecked.'>';
}

function inputHidden($name, $value)
{
	return '<input type="hidden" name="'.$name.'" value="'.$value.'">';
}

function formSubmit($name='submit', $value='submit')
{
	return '<input type="submit" name="'.$name.'" value="'.$value.'">';
}

function inputSubmit($name='submit', $value='submit')
{
	return '<input type="submit" name="'.$name.'" value="'.$value.'">';
}

function inputButton($name='submit')
{
	return formSubmit($name, $name);
}


function editRow($name, $contents, $id='')
{
	$id_text = strlen($id)>0 ? ' id="'.$id.'"' : '';
	echo '<tr'.$id_text.'><td class="panelLeft">'.$name.':</td>';
	echo '<td class="panelRight">'.$contents.'</td></tr>';
	echo "\n";
}

function editSpacerRow() { echo '<tr><td class="panelLeft">&nbsp;</td><td class="panelRight">&nbsp;</td></tr>'; }

function editNoteRow($note, $showArrows=TRUE) {
	$darr = $showArrows ? '&darr;' : '';
	echo '<tr><td class="panelLeft">&nbsp;</td><td class="panelRight"><span style="background-color: #eee; font-size: 0.85em;"><i>'.$darr.' '.$note.' '.$darr.'</i></span></td></tr>';
}

function editHeaderRow($header) {
	echo '<tr><td class="header" colspan="2">'.$header.'</td></tr>';
}

// makes it easy and readable to generate links inside the app
function clickTo($module, $action, $params='')
{
	if (strlen($params)>0) $params = "&$params";
	return "index.php?module=$module&action=$action$params";
}

function createLink($url, $newWindow=TRUE, $txt='null')
{
	if ($txt=='null') $txt = $url;
	$target = $newWindow ? 'target="newWindow"' : '';
	return '<a href="'.$url.'" '.$target.'>'.$txt.'</a>';
}

function endEditPanel($delete=FALSE)
{
	if ($delete) {
		$delStr = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$delStr .= ' confirm delete: <select name="confirm"><option value="no">no</option><option value="yes">yes</option></select>';
		$delStr .= '<input type="submit" value="Delete" name="delete" class="button">';
	}
	editSpacerRow();
	echo '<tr><td class="panelLeft">&nbsp;</td><td class="panelEnd"><input type="submit" value="Submit" class="button">'.$delStr.'</td></tr></table>';
}

function endForm() { echo '</form>'; }

function checkFor($name) { return ($_REQUEST[$name]=='true') ? TRUE : FALSE; }

function buttonClicked($name) { return ($_REQUEST[$name]==$name); }

function is_delete($deleteName='delete')
{
	return (isset($_REQUEST[$deleteName]) && $_REQUEST['confirm']=='yes');
}

function is_non_delete_post()
{
	return (is_post() && !isset($_REQUEST['delete']));
}

function displaySubMenu($arr)
{
	echo '<div class="subMenu">';
	foreach($arr as $k=>$v) {
		echo '<a href="'.$v.'">'.$k.'</a> &nbsp;';
	}
	echo '</div>';
}

function nameSQLPicklist($selectName, $table, $val)
{
	$rs = dbQuery("SELECT $selectName, name FROM $table ORDER BY name");

	$txt = '<select name="'.$selectName.'">';
	$txt .= '<option value="null">-- choose --</value>';
	while ($row = mysql_fetch_assoc($rs))
	{
		$vid = $row[$selectName];
		$selected = ($val==$vid) ? 'SELECTED' : '';
		$name = $row['name'];
		$txt .= '<option value="'.$vid.'" '.$selected.'>'.$name.'</option>';
	}
	$txt .= '</select>';

	return $txt;
}

function videoFormatPicklist($selectName, $value)
{
	global $videoTypes;

	$txt = '<select name="'.$selectName.'">';
	$txt .= '<option value="null">-- choose --</value>';
	foreach($videoTypes as $videoTypeID=>$str)
	{
		$selected = ($value==$videoTypeID) ? 'SELECTED' : '';
		$txt .= '<option value="'.$videoTypeID.'" '.$selected.'>'.$str.'</option>';
	}
	$txt .= '</select>';

	return $txt;
}

function yesNoPicklist($selectName, $state)
{
	$str = '<select name="'.$selectName.'">';
	$selected = ($state=='n') ? 'SELECTED' : '';
	$str .= '<option value="n" '.$selected.'>no</option>';
	$selected = ($state=='y') ? 'SELECTED' : '';
	$str .= '<option value="y" '.$selected.'>yes</option>';
	$str .= '</select>';
	return $str;
}

function displayYesNo($b) { echo ($b) ? 'yes' : 'no'; }
function checkOnYes($b, $mark='<b>x</b>') { if ($b) return $mark; }

function generateValidationJS($rules)
{
	echo '<script language="javascript">';
	echo 'function submitValidation()';
	echo '{';
	echo 'flag=true;';

	foreach ($rules as $field=>$func) {
		echo $field."Field = document.getElementById('$field');";
		echo "if (!$func($field"."Field)) flag=false;";
	}
	echo 'if (!flag) alert("Field validation failed!");';
	echo 'return flag;';
	echo '}';
	echo '</script>';
}

function generateDynamicValidationJS($rules)
{
	echo '<script language="javascript">';

	foreach ($rules as $field=>$func) {
		echo $field."Field = document.getElementById('$field');";
		echo $field."Field.addEventListener('keyup', ".$func.", false);";
		echo $field."Field.addEventListener('change', ".$func.", false);";
	}
	echo '</script>';
}

function statusMessage($msg)
{
	echo '<div class="status">'.$msg.'</div>';
}

function getSitePreference($pref)
{
	$sitePreferenceID = singleQueryField("SELECT sitePreferenceID FROM sitePreferenceInfo WHERE progName='$pref'", 'sitePreferenceID');
	return singleQueryField("SELECT value FROM sitePreference WHERE sitePreferenceID=$sitePreferenceID", 'value');
}

function makeLink($text, $link) { echo '<a href="'.$link.'">'.$text.'</a>'; }

function processAndSaveImage($sim, $dest, $destSize, $jpgQuality=90, $progressIndicator=FALSE)
{
	$sdx = imagesx($sim);
	$sdy = imagesy($sim);
	$longest = ($sdx>$sdy) ? $sdx : $sdy;

	if ($destSize>$longest) {
		copy($src, $dest);
		statusMessage("warning, original file not big enough to downsize to $destSize.  Keeping size of original file.");
		$destSize = $longest;
	}

	if ($sdx >= $sdy) {
		$ddx = $destSize;
		$ddy = $sdy * ($destSize / $sdx);
	} else {
		$ddx = $sdx * ($destSize / $sdy);
		$ddy = $destSize;
	}

	$ddx = intval($ddx);
	$ddy = intval($ddy);

	$dim = imagecreatetruecolor($ddx, $ddy);
	imagecopyresampled($dim, $sim, 0, 0, 0, 0, $ddx, $ddy, $sdx, $sdy);
	imagejpeg($dim, $dest, $jpgQuality);

	if ($progressIndicator) echo '.';

	// return the dimensions of the outputted image
	return array($ddx, $ddy);
}

function redirect_to($controller, $action='index', $id='', $remainder='')
{
	$link = link_to($controller, $action, $id, $remainder);
	echo '<script language="javascript">window.location="'.$link.'";</script>';
}

function matchAny($str, $matchTxt)
{
	$matches = split('\|', $matchTxt);

	foreach ($matches as $match) {
		if ($match == $str) return TRUE;
	}

	return FALSE;
}

function SQLDateToHumanReadable($str, $delimiter='/')
{
	$arr = split('-', $str);
	$year = $arr[0];
	$month = $arr[1];
	$day = $arr[2];

	return sprintf("%02d%s%02d%s%02d", $month, $delimiter, $day, $delimiter, $year-2000);
}

function link_to($controller, $action='index', $id='', $remainder='')
{
	if (is_numeric($id)) {
		if (stringExists($remainder))
			return APP_URL . "$controller/$action/$id/$remainder";
		else
			return APP_URL . "$controller/$action/$id";
	} else {
		return APP_URL . "$controller/$action";
	}
}

function get_params_id()
{
	global $params;
	return $params['id'];
}

function render_view($include_file)
{
	global $APPLICATION_HOME;
	
	ob_start();
	include($APPLICATION_HOME."views/$include_file.php");
	$str = ob_get_contents();
	ob_end_clean();
	echo $str;
}

function render_partial($include_file)
{
	render_view($include_file);
}

// creates a nicely sorted table of checkboxes from a join table
function displayHABTMCheckboxes($inputName, $table, $joinTable, $joinTableField1, $joinTableField2, $join_id, $numCols=1, $where="", $editMode=TRUE)
{
	$where = strlen($where)>0 ? " WHERE $where " : "";
	$rs = dbQuery("SELECT * FROM $table $where ORDER BY name");
	$elements = array();
	for ($i=0; $row = mysql_fetch_assoc($rs); $i++)
	{
		$id = $row['id'];
		$name = $row['name'];
		$elements[$i]['id'] = $id;
		$elements[$i]['name'] = $name;
		if ($editMode) {
			// echo "SELECT * FROM $joinTable WHERE $joinTableField1=$id AND $joinTableField2=$join_id<br/>";
			$elements[$i]['selected'] = (rowCount("SELECT * FROM $joinTable WHERE $joinTableField1=$id AND $joinTableField2=$join_id") == 1);
		}
			
	}
	
	$numElements = mysql_num_rows($rs);
	
	if ($i < 1) return;
	
	$numRows = floor(($numElements/$numCols)) + 1;
	$grid = array();
	
	$i = 0;
	for ($x=0; $x<($numCols); $x++) {
		for ($y=0; $y<($numRows); $y++) {
			$grid["$x,$y"] = ($i <= $numElements) ? $elements[$i] : "";
			$i++;
		}
	}
	
	$str = "";
	$str .= '<div class="HABTMCheckboxes">';
	$str .= '<table>';
	for ($row=0; $row<($numRows); $row++) {
		$str .= "\n<tr>";
		for ($col=0; $col<($numCols); $col++) {
			$str .= "<td>";
			$element = $grid["$col,$row"];
			if ($element == "") {
				$str .= "&nbsp;";
			} else {
				$checked = $element['selected'] ? 'CHECKED' : '';
				$str .= '<input type="checkbox" id="'.$inputName.'" name="'.$inputName.'" value="'.$element['id'].'" '.$checked.'/>'.$element['name'];
			}
			$str .= "</td> ";
		}
		$str .= "</tr>\n";
	}
	$str .= "</table>";
	$str .= "</div>";
	
	return $str;
}

function inputHeight($name, $height)
{
	$str = "";
	if (is_numeric($height) && $height > 0) {
		$feet = intval($height / 12);
		$inches = $height % 12;
	} else {
		$feet = '';
		$inches = '';
	}
	$str .= inputText($name.'_feet', $feet, 1) . " ' ";
	$str .= inputText($name.'_inches', $inches, 1) . ' "';
	
	return $str;
}

function height_inches_to_string($height)
{
	if (is_numeric($height) && $height > 0) {
		$feet = intval($height / 12);
		$inches = $height % 12;
		return "$feet' " . $inches . '"';
	}
	
	return '';
}

function displayCupSizePicklist($cup_size)
{
	$str = '';
	$str .= '<select name="cup_size">';
	$str .= '<option value="null">--- choose ---</option>';
	$rs = dbQuery("SELECT * FROM cup_sizes ORDER BY numeric_value ASC");
	while ($row = mysql_fetch_assoc($rs))
	{
		$numeric_value = $row['numeric_value'];
		$name = $row['name'];
		$selected = $cup_size == $numeric_value ? 'SELECTED' : '';
		$str .= '<option value="'.$numeric_value.'" '.$selected.'>'.$name.'</option>';
	}
	$str .= '</select>';
	return $str;
}

function make_list_from_result_set_by_column($rs, $column_name, &$has_rows)
{
	$temp_array = array();
	$has_rows = false;
	while ($row = mysql_fetch_assoc($rs))
	{
		$temp_array[] = $row[$column_name];
		$has_rows = true;
	}
	return join(', ', $temp_array);
}


?>

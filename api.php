<?php
exit;
require_once 'config.php';
require_once 'common/util.php';

$cmd = getRequestStr('cmd');

switch($cmd) {
	case 'save-word':
		$word = rawurldecode(getRequestStr('word'));
		$definition = rawurldecode(getRequestStr('definition'));
		$sql = "INSERT words SET word='$word', definition='$definition'";
		dbUpdate($sql);
		file_put_contents('log.txt', 'testing');
		break;
		
	case 'load-word':
		break;
		
	case 'load-all-words':
		$rs = dbQuery("SELECT * FROM words ORDER BY word");
		while ($row = mysql_fetch_assoc($rs))
		{
			extract($row);
			echo ": $word $definition ; \n";
		}
		break;
}

?>

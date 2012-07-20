<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Services_JSON.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/News.php');

// FIXME - add permission check

$id = get_numeric_param('id');

if ($id != NULL && $id > -1) {
	$ok = News::delete_with_id($id);
	echo $ok;
}

?>

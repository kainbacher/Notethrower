<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Services_JSON.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/News.php');

$id = get_numeric_param('id');
$html = get_param('html');
$headline = get_param('headline');

$n = null;

if ($id != NULL && $id > -1) {
	$n = News::fetch_for_id($id);
} else {
	$n = new News();
}

$n->html = $html;
$n->headline = $headline;

$ok = $n->save();

echo '{"success":';
if ($ok) {
	echo 'true,';
} else {
	echo 'false,';
}
echo '"msg":"News saved",';
echo '"newId":' . $n->id . '}';

?>

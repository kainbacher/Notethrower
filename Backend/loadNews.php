<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Services_JSON.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/News.php');

$asExtTreeNode = get_param('asExtTreeNode');
$id = get_numeric_param('id');

$jsonService = new Services_JSON();

$news = array();

if ($id != NULL && $id > -1) {
	$n = News::fetch_for_id($id);
	$news[0] = $n;
} else {
	$news = News::fetch_all();
}

if ($asExtTreeNode == 'true') {
	echo '[';
	$idx = 0;
	foreach ($news as $n) {
		if ($idx > 0) {
			echo ',';
		}
		echo '{"id": ' . $n->id . ',';
		echo '"text":' . $jsonService->encode($n->headline) . ', leaf: true}';
		$idx++;
	}
	echo ']';
}

else {
	$jsonService = new Services_JSON();
	echo $jsonService->encode($news);
}

?>
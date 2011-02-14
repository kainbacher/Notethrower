<?php

include_once('../Includes/Init.php');
include_once('../Includes/Paginator.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Artist.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/News.php');

$pageNum = get_numeric_param('page');

$newsCount = News::count_all();

$paginatorResp = paginator_get_start_and_end_item_for_page($newsCount, $GLOBALS['NEWS_PER_PAGE'], $pageNum);

$news = News::fetch_newest_from_to($paginatorResp['startItem'], $paginatorResp['endItem']);

$newsIdx = 0;
foreach ($news as $n) {
    if ($newsIdx > 0) {
        echo '<div class="newsSpacer"></div>';
    }
    echo '<div class="newsEntry">';
    echo '<h1>' . $n->headline . '</h1>';
    //echo '<br>';
    //echo '<small>' . $n->entry_date . '</small>'; // FIXME - timezone? 
    echo $n->html;
    echo '</div>';
    $newsIdx++;
}

?>
<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');

$pageHeader = processTpl('Common/pageHeader.html', array(
    '${PAGE_TITLE_SUFFIX}' => 'Start'
));

$leftPlayer = processTpl('Startpage/leftPlayer.html', array());
$rightPlayer = processTpl('Startpage/rightPlayer.html', array());

$pageFooter = processTpl('Common/pageFooter.html', array());

processAndPrintTpl('Startpage/index.html', array(
    '${Common/pageHeader}'     => $pageHeader,
    '${Startpage/leftPlayer}'  => $leftPlayer,
    '${Startpage/rightPlayer}' => $rightPlayer,
    '${Common/pageFooter}'     => $pageFooter
));
   
?>
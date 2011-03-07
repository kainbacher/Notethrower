<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/AudioTrack.php');

$leftTrack  = AudioTrack::fetchRandomPublicTrack();
$rightTrack = AudioTrack::fetchRandomPublicTrack($leftTrack->id); // ensure that we have different tracks

$pageHeader = processTpl('Common/pageHeader.html', array(
    '${pageTitleSuffix}' => ' - Start'
));

$leftPlayer = processTpl('Startpage/player.html', array(
    '${trackId}' => $leftTrack->id,
    '${mp3Url}'  => $leftTrack->getPreviewMp3Url(),
));

$rightPlayer = processTpl('Startpage/player.html', array(
    '${trackId}' => $rightTrack->id,
    '${mp3Url}'  => $rightTrack->getPreviewMp3Url(),
));

$pageFooter = processTpl('Common/pageFooter.html', array());

processAndPrintTpl('Startpage/index.html', array(
    '${Common/pageHeader}'      => $pageHeader,
    '${Startpage/player_left}'  => $leftPlayer,
    '${Startpage/player_right}' => $rightPlayer,
    '${Common/pageFooter}'      => $pageFooter
));

?>
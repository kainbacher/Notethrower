<?php

error_reporting (E_ALL ^ E_NOTICE);

include_once('../Includes/Init.php');

include_once('../Includes/DbConnect.php');
include_once('../Includes/DB/Artist.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/AudioTrackArtistVisibility.php');
include_once('../Includes/DB/AudioTrackAttribute.php');
include_once('../Includes/DB/AudioTrackAudioTrackAttribute.php');
include_once('../Includes/DB/AudioTrackFile.php');
include_once('../Includes/DB/Message.php');
include_once('../Includes/DB/News.php');
include_once('../Includes/DB/PayPalTx.php');
include_once('../Includes/DB/Stats.php');

header('Content-type: text/plain');

Artist::create_table();
AudioTrack::create_table();
AudioTrackArtistVisibility::create_table();
AudioTrackAttribute::createTable();
AudioTrackAudioTrackAttribute::createTable();
AudioTrackFile::create_table();
Message::create_table();
News::create_table();
PayPalTx::create_table();
Stats::create_table();

echo 'creation done.' . "\n";

AudioTrackAttribute::populateTable();

echo 'population done.' . "\n";

echo 'all done';

?>
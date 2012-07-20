<?php

error_reporting (E_ALL ^ E_NOTICE);

include_once('../Includes/Init.php');

include_once('../Includes/DbConnect.php');
include_once('../Includes/DB/Attribute.php');
include_once('../Includes/DB/EditorInfo.php');
include_once('../Includes/DB/Genre.php');
include_once('../Includes/DB/Invitation.php');
include_once('../Includes/DB/Message.php');
include_once('../Includes/DB/Mood.php');
include_once('../Includes/DB/News.php');
include_once('../Includes/DB/Nonce.php');
include_once('../Includes/DB/PayPalTx.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectAttribute.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/ProjectFileAttribute.php');
include_once('../Includes/DB/ProjectGenre.php');
include_once('../Includes/DB/ProjectMood.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/ReleaseContribution.php');
include_once('../Includes/DB/Stats.php');
include_once('../Includes/DB/Subscription.php');
include_once('../Includes/DB/Tool.php');
include_once('../Includes/DB/TranscodingJob.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/UserAttribute.php');
include_once('../Includes/DB/UserGenre.php');
include_once('../Includes/DB/UserTool.php');

header('Content-type: text/plain');

Attribute::createTable();
EditorInfo::createTable();
Genre::createTable();
Invitation::createTable();
Message::create_table();
Mood::createTable();
News::create_table();
Nonce::create_table();
PayPalTx::create_table();
Project::create_table();
ProjectAttribute::createTable();
ProjectFile::create_table();
ProjectFileAttribute::createTable();
ProjectGenre::createTable();
ProjectMood::createTable();
ProjectUserVisibility::create_table();
ReleaseContribution::createTable();
Stats::create_table();
Tool::createTable();
TranscodingJob::createTable();
User::create_table();
UserAttribute::createTable();
UserGenre::createTable();
UserTool::createTable();
Subscription::create_table();

echo 'creation done.' . "\n";

Attribute::populateTable();
Genre::populateTable();
Tool::populateTable();
Mood::populateTable();

echo 'population done.' . "\n";

echo 'all done';

?>

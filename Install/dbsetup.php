<?php

error_reporting (E_ALL ^ E_NOTICE);

include_once('../Includes/Init.php');

include_once('../Includes/DbConnect.php');
include_once('../Includes/DB/Attribute.php');
include_once('../Includes/DB/Genre.php');
include_once('../Includes/DB/Message.php');
include_once('../Includes/DB/News.php');
include_once('../Includes/DB/Nonce.php');
include_once('../Includes/DB/PayPalTx.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectAttribute.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/Stats.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/UserAttribute.php');

header('Content-type: text/plain');

Attribute::createTable();
Genre::createTable();
Message::create_table();
News::create_table();
Nonce::create_table();
PayPalTx::create_table();
Project::create_table();
ProjectAttribute::createTable();
ProjectFile::create_table();
ProjectUserVisibility::create_table();
Stats::create_table();
User::create_table();
UserAttribute::createTable();

echo 'creation done.' . "\n";

Attribute::populateTable();
Genre::populateTable();

echo 'population done.' . "\n";

echo 'all done';

?>
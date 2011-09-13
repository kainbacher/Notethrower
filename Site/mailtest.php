<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');

$GLOBALS['MAIL_FROM_NAME'] = 'noreply-oneloudr';
$GLOBALS['MAIL_FROM_ADDRESS'] = 'noreply@oneloudr.com';
$GLOBALS['MAIL_REPLY_TO_ADDRESS'] = 'noreply@oneloudr.com';
$GLOBALS['EMAIL_DELIVERY_MODE'] = 'active';
$GLOBALS['EMAIL_DELIVERY_OVERRIDE_ALLOWED_RECIPIENTS'] = array();
$GLOBALS['EMAIL_DELIVERY_OVERRIDE_ADDR'] = 'hjonas@gmx.at';
$GLOBALS['BCC_EMAIL_ADDRESS'] = '';
$GLOBALS['MAIL_SERVER_HOST'] = 'smtp.sendgrid.net';
$GLOBALS['MAIL_SERVER_PORT'] = 25;
$GLOBALS['MAIL_SERVER_AUTH_TYPE'] = 'basic';
$GLOBALS['MAIL_SERVER_AUTH_USER'] = 'oneloudr';
$GLOBALS['MAIL_SERVER_AUTH_PWD'] = 'oneloudr';

include_once('../Includes/MailUtil.php');

sendEmail(
    'hjonas@gmx.at',
    'subject',
    'textContent'
);

?>
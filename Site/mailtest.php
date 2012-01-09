<?php

include_once('../Includes/Init.php'); // must be included first

error_reporting(E_ALL | E_STRICT);

include_once('../Includes/Config.php');

// FIXME - make sure this is in the config!
$GLOBALS['MAIL_FROM_NAME'] = 'noreply-oneloudr';
$GLOBALS['MAIL_FROM_ADDRESS'] = 'noreply@oneloudr.com';
$GLOBALS['MAIL_REPLY_TO_ADDRESS'] = 'noreply@oneloudr.com';
$GLOBALS['EMAIL_DELIVERY_MODE'] = 'active';
$GLOBALS['EMAIL_DELIVERY_OVERRIDE_ALLOWED_RECIPIENTS'] = array();
$GLOBALS['EMAIL_DELIVERY_OVERRIDE_ADDR'] = 'hjonas@gmx.at';
$GLOBALS['BCC_EMAIL_ADDRESS'] = '';
$GLOBALS['MAIL_SERVER_HOST'] = 'smtp.sendgrid.net';
$GLOBALS['MAIL_SERVER_PORT'] = 587;
$GLOBALS['MAIL_SERVER_AUTH_TYPE'] = 'basic';
$GLOBALS['MAIL_SERVER_AUTH_USER'] = 'oneloudr';
$GLOBALS['MAIL_SERVER_AUTH_PWD'] = 'oneloudr';

include_once('../Includes/Mailer/MailUtil.php');

$msg = "hey, \n\njust a quick test if this whole email delivery thing really works\nin a reliable way ... cu,\n the test script.";

$recipients = array(
  'hjonas@gmx.at',
  'hanno@rastaduck.org',
  'hanno.jonas@gmail.com',
  'hanno@jonas-it.com',
  'hanno.jonas@vol.at'
);

sendEmailToRecipients(
    $recipients,
    'subject',
    $msg 
);

?>

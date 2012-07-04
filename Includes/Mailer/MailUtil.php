<?php

include_once($INCLUDE_PATH . 'Config.php');
include_once($INCLUDE_PATH . 'Mailer/lib/swift_required.php');

function sendEmail($email, $subject, $textContent, $htmlContent = null, $forceEmailDelivery = false) {
    return sendEmailToRecipients(array($email), $subject, $textContent, $htmlContent, $forceEmailDelivery);
}

function sendEmailToRecipients($emails, $subject, $textContent, $htmlContent = null, $forceEmailDelivery = false) {
    return sendEmailWithFromAndReplyToAddressToRecipients(
            $emails,
            $subject,
            $textContent,
            $htmlContent,
            $GLOBALS['MAIL_FROM_NAME'],
            $GLOBALS['MAIL_FROM_ADDRESS'],
            $GLOBALS['MAIL_REPLY_TO_ADDRESS'],
            $forceEmailDelivery
    );
}

function sendEmailWithFromAndReplyToAddress($email, $subject, $textContent, $htmlContent = null, $mailFromName,
        $mailFromAddress, $mailReplyToAddress, $forceEmailDelivery = false) {

    return sendEmailWithFromAndReplyToAddressToRecipients(
            array($email), 
            $subject, 
            $textContent, 
            $htmlContent, 
            $mailFromName,
            $mailFromAddress, 
            $mailReplyToAddress, 
            $forceEmailDelivery
    );
}

function sendEmailWithFromAndReplyToAddressToRecipients($emails, $subject, $textContent, $htmlContent = null, $mailFromName,
        $mailFromAddress, $mailReplyToAddress, $forceEmailDelivery = false) {

    global $logger;

    $logger->debug('mail to: ' . join(', ', $emails));
    $logger->debug('mail from: ' . $mailFromName . ' <' . $mailFromAddress . '>');
    $logger->debug('mail reply-to: ' . $mailReplyToAddress);

    if(!$forceEmailDelivery) {
        if ($GLOBALS['EMAIL_DELIVERY_MODE'] == 'override') {
            if (count($emails) == 1) { // we don't do a smart override when there's more than one recipient (because we're too lazy to implement the logic for this edge case)
                $logger->info('recipient: ' . $emails[0]);
                if (in_array($emails[0], $GLOBALS['EMAIL_DELIVERY_OVERRIDE_ALLOWED_RECIPIENTS'])) {
                    $logger->info('email override is active but email address is whitelisted');
                } else {
                    $emails = array($GLOBALS['EMAIL_DELIVERY_OVERRIDE_ADDR']);
                    $logger->info('email will be sent to override address: ' . $emails[0]);
                }

            } else {
                $logger->info('recipient(s): ' . join(', ', $emails));
                $emails = array($GLOBALS['EMAIL_DELIVERY_OVERRIDE_ADDR']);
                $logger->info('email will be sent to override address: ' . $emails[0]);
            }

        } else if ($GLOBALS['EMAIL_DELIVERY_MODE'] == 'inactive') {
            $logger->info('---- email delivery simulation ----');
            $logger->info('recipient(s): ' . join(', ', $emails));
            $logger->info('from        : ' . $mailFromName . ' <' . $mailFromAddress . '>');
            $logger->info('reply-to    : ' . $mailReplyToAddress);
            $logger->info('bcc         : ' . $GLOBALS['MAIL_BCC_ADDRESS']);
            $logger->info('subject     : ' . $subject);
            $logger->info('text        : ' . $textContent);
            $logger->info('html        : ' . $htmlContent);
            $logger->info('-----------------------------------');

            return true;
        }
    }

	$logger->info('sending mail to: ' . join(', ', $emails) . ($GLOBALS['MAIL_BCC_ADDRESS'] ? ' (BCC to: ' . $GLOBALS['MAIL_BCC_ADDRESS'] . ')' : ''));

    $from = array($mailFromAddress => $mailFromName);
    // this is how the recipients array originally looked, but it works without the names, too.
    // $emails = array(
      // 'hjonas@gmx.at'=>'Hanno Jonas',
      // 'hanno.jonas@vol.at'=>'Hanno Jonas'
    // );
     
    // Setup Swift mailer parameters
    $transport = Swift_SmtpTransport::newInstance($GLOBALS['MAIL_SERVER_HOST'], $GLOBALS['MAIL_SERVER_PORT']);
    $transport->setUsername($GLOBALS['MAIL_SERVER_AUTH_USER']);
    $transport->setPassword($GLOBALS['MAIL_SERVER_AUTH_PWD']);
    $swift = Swift_Mailer::newInstance($transport);
     
    // Create a message (subject)
    $message = new Swift_Message($subject);
     
    $message->setFrom($from);
    $message->setReplyTo($mailReplyToAddress);
    $message->setTo($emails);
    if ($GLOBALS['MAIL_BCC_ADDRESS']) $message->setBcc($GLOBALS['MAIL_BCC_ADDRESS']);
    
    // attach the body of the email
    if ($htmlContent) {
        $message->setBody($htmlContent, 'text/html');
        if ($textContent) $message->addPart($textContent, 'text/plain');
        
    } else {
        $message->setBody($textContent, 'text/plain');
    }
     
    // send message 
    if ($recipientCount = $swift->send($message, $failures)) {
        // This will let us know how many users received this message
        $logger->info('Message successfully sent to ' . $recipientCount . ' recipient(s)');
        return true;
        
    } else { // something went wrong =(
        $logger->error('Something went wrong: ' . print_r($failures, true));
        return false;
    }
}

?>

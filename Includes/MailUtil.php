<?php

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');

function sendEmail($email, $subject, $textContent, $htmlContent = null, $forceEmailDelivery = false) {
    return sendEmailToRecipients(array($email), $subject, $textContent, $htmlContent, $forceEmailDelivery);
}

function sendEmailToRecipients($emails, $subject, $textContent, $htmlContent = null, $forceEmailDelivery = false) {
    return sendEmailWithFromAddressToRecipients(
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

function sendEmailWithFromAddress($email, $subject, $textContent, $htmlContent = null, $mailFromName,
        $mailFromAddress, $mailReplyToAddress, $forceEmailDelivery = false) {

    return sendEmailWithFromAddressToRecipients(array($email), $subject, $textContent, $htmlContent, $mailFromName,
            $mailFromAddress, $mailReplyToAddress, $forceEmailDelivery);
}

function sendEmailWithFromAddressToRecipients($emails, $subject, $textContent, $htmlContent = null, $mailFromName,
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
            $logger->info('sender      : ' . $mailFromName . ' <' . $mailFromAddress . '>');
            $logger->info('reply-to    : ' . $mailReplyToAddress);
            $logger->info('subject     : ' . $subject);
            $logger->info('text        : ' . $textContent);
            $logger->info('html        : ' . $htmlContent);
            $logger->info('-----------------------------------');

            return true;
        }
    }

    require_once ('../Includes/Mail.php');
    require_once ('../Includes/Mail/mime.php');

    $crlf = "\r\n";

	$hdrs = array(
	    'From' 			=> enc8bit($mailFromName) . ' <' . $mailFromAddress . '>',
		'Subject' 		=> enc8bit($subject),
		'Reply-To' 		=> $mailReplyToAddress,
		'Return-Path' 	=> $mailFromAddress,
		'Date'      	=> date('r'),
		'To' 		    => join(',', $emails),
		'Bcc'           => $GLOBALS['BCC_EMAIL_ADDRESS']
	);

	$logger->info('mail headers: ' . print_r($hdrs, true));

    $params['host'] = $GLOBALS['MAIL_SERVER_HOST'];
    $params['port'] = $GLOBALS['MAIL_SERVER_PORT'];
    $params['auth'] = $GLOBALS['MAIL_SERVER_AUTH_TYPE'];
    $params['username'] = $GLOBALS['MAIL_SERVER_AUTH_USER'];
    $params['password'] = $GLOBALS['MAIL_SERVER_AUTH_PWD'];

    $mime = new Mail_mime($crlf);

    if ($htmlContent) {
        $mime->setHTMLBody(normalizeNewlines($htmlContent));
    }

    if ($textContent) {
        $mime->setTXTBody(normalizeNewlines($textContent));
	}

    $bodyParams = array(
        'html_charset'	=> 'UTF-8',
        'text_charset'	=> 'UTF-8',
	    'head_charset'	=> 'UTF-8'
	);

	$body = $mime->get($bodyParams);
	//$body = $mime->get(); // use this when utf-8 is not needed

    $hdrs = $mime->headers($hdrs);

	$mail = Mail::factory('smtp', $params);

	$logger->info('sending mail to: ' . join(', ', $emails) . ' (BCC to: ' . $GLOBALS['BCC_EMAIL_ADDRESS'] . ')');

    $emails[] = $GLOBALS['BCC_EMAIL_ADDRESS']; // add the bcc recipient to the "real" email recipient list (the bcc header above is used just for information purposes)

    $logger->debug('mail body: ' . $body);
	$status = $mail->send($emails, $hdrs, $body);
	$logger->info('sending status: ' . $status);

	return $status;
}

function enc8bit($string) {
	$ret = '=?UTF-8?Q?';
    $l = strlen($string);
	for($i = 0; $i < $l; $i++) {
		$tmp = dechex(ord($string[$i]));
		$ret .= '=' . $tmp;
	}

	return $ret . '?=';
}

?>

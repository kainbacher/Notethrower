<?php

set_time_limit(120); // 2 minutes hard limit

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/DbConnect.php');
include_once('../Includes/MailchimpClient.php');

header('Content-type: text/plain');

try {            
    echo 'synchronizing list members ...' . "\n";
    syncListMembers(); // ensures the email lists in oneloudr and MailChimp are the same
    echo 'done.' . "\n";
    
} catch (MailChimpException $e) {
    $logger->error('MailChimpException occured: ' . $e);
    
    echo 'failed to sync email lists with mailchimp! (' . getMailChimpErrorForExceptionCode($e->getCode()) . ')' . "\n";
}

?>

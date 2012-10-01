<?php

include_once($INCLUDE_PATH . 'Config.php');
include_once($INCLUDE_PATH . 'Snippets.php');
include_once($INCLUDE_PATH . 'DB/User.php');
require_once($INCLUDE_PATH . 'MCAPI/MCAPI.class.php');

class MailChimpException extends Exception {}

/** 
 * ensures that the members in the MailChimp list are exactly the same as the users in the portal. 
 */
function syncListMembers() {
    global $MC_API_KEY;
    global $MC_LIST_ID;
    global $logger;
    
    $logger->info('synchronizing oneloudr and MailChimp member lists');
    
    $api = new MCAPI($MC_API_KEY);

    $mcMembers = array();
    
    $logger->info('fetching MailChimp member list');
    
    $response = $api->listMembers($MC_LIST_ID);
    $logger->debug(print_r($response, true));

    if ($api->errorCode) {
        throw new MailChimpException('Failed to get list members! Code=' . $api->errorCode . ', Msg=' . $api->errorMessage, $api->errorCode);
        
    } else {
        $logger->info('MC member count: ' . sizeof($response['data']));
        foreach($response['data'] as $member){
            $mcMembers[] = $member['email'];
        }
    }
    
    //echo '<br>MC members:<br>'; print_r_pre($mcMembers);
    
    $logger->info('fetching oneloudr user/member list');
    
    $olMembers = User::getAllNewsletterRecipientEmails();
    $logger->info('oneloudr member count: ' . count($olMembers));
    
    //echo '<br>oneloudr members:<br>'; print_r_pre($olMembers);
    
    // delete MC members which are not on the oneloudr member list
    $emailsToDelete = array_diff($mcMembers, $olMembers);
    
    if (count($emailsToDelete) > 0) {
        //echo '<br>emails to delete:<br>'; print_r_pre($emailsToDelete);
        
        $logger->info('deleting MC members which are not in the oneloudr system');
        $logger->info('members to delete: ' . implode(', ', $emailsToDelete));
        
        $response = $api->listBatchUnsubscribe($MC_LIST_ID, $emailsToDelete, true, false, false); // list_id, emails, delete_member, send_goodbye, send_notify
        $logger->debug(print_r($response, true));
        
        if ($api->errorCode){
            throw new MailChimpException('Failed to delete MC list members which are not in the oneloudr system! Code=' . $api->errorCode . ', Msg=' . $api->errorMessage, $api->errorCode);
            
        } else {
            $logger->info('Success count: ' . $response['success_count']);
            $logger->info('Error count  : ' . $response['error_count']);
            foreach ($response['errors'] as $val) {
                logError($val['email'] . ' failed! Code: ' . $val['code'] . 'Msg: ' . $val['message']);
            }
        }
        
    } else {
        $logger->info('nothing to delete');
    }
    
    // add members which are on the oneloudr list but not on the MC list
    $emailsToAdd = array_diff($olMembers, $mcMembers);
        
    if (count($emailsToAdd) > 0) {
        //echo '<br>emails to add:<br>'; print_r_pre($emailsToAdd);
        
        $logger->info('adding members which are on the oneloudr member list but not in the MC system');
        $logger->info('members to add: ' . implode(', ', $emailsToAdd));
        
        $batch = array();
        foreach ($emailsToAdd as $email) {
            //$batch[] = array('EMAIL'=>$boss_man_email, 'FNAME'=>'Me', 'LNAME'=>'Chimp');
            $batch[] = array('EMAIL' => $email, 'EMAIL_TYPE' => 'html');
        }
        
        $response = $api->listBatchSubscribe($MC_LIST_ID, $batch, false, false, true); // double_optin, update_existing, replace_interests
        $logger->debug(print_r($response, true));
        
        if ($api->errorCode){
            throw new MailChimpException('Failed to add members which are on the oneloudr list but not in the MC system! Code=' . $api->errorCode . ', Msg=' . $api->errorMessage, $api->errorCode);
            
        } else {
            $logger->info('Add count   : ' . $response['add_count']);
            $logger->info('Update count: ' . $response['update_count']);
            $logger->info('Error count : ' . $response['error_count']);
            foreach ($response['errors'] as $val) {
                $e = isset($val['email']) ? $val['email'] : $val['email_address']; // MC documentation is unclear about the field name
                logError($e . ' failed! Code: ' . $val['code'] . 'Msg: ' . $val['message']);
            }
        }
        
    } else {
        $logger->info('nothing to add');
    }
}

function getMailChimpErrorForExceptionCode($code) {
    if ($code === null) return 'General error';
    
    switch($code) {
        case -98: return 'Request timed out';
        case -50: return 'Too many connections';
        case   0: return 'Parse exception';
        case 104: return 'Invalid API key';
        case 200: return 'Unknown list ID';
        case 311: return 'Invalid campaign content';
        case 315: return 'Invalid segment';
        case 502: return 'Invalid email';
        case 506: return 'Invalid options';
        case 555: return 'Max size reached';
        default : return 'General error'; 
    }
}

?>
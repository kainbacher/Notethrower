<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Subscription.php');


if(get_param('referrer') == 'batcholdartists'){
    /*
	Invitation sent on 20.09.2011 - 15:34. DONT REPEAT!!!
	$oldartists = Subscription::fetch_notethrower_artists();
    
    for($i=0; $i < count($oldartists); $i++){
        $artist = $oldartists[$i];
        $referral_url = 'http://oneloudr.com/invite/'.$artist['rand_str'];
        $emailtext = processTpl('Subscription/emailtextoldartists.html', array(
                '${username}'                                => $artist['username'],
                '${referralUrl}'                             => $referral_url
            ));
        $email_sent = send_email($artist['email_address'], 'Your ONELOUDR Invitation', $emailtext);
        if($email_sent){
            echo 'Invitation for "'.$artist['username'].'" successfully sent<br />';
        }  
        //echo $email_sent;
        //echo 'name: '.$artist['username'].'<br />';
    }
	*/
    exit;
}

$referrer = Subscription::fetch_for_rand_str(get_param('referrer'));
if(!$referrer->id){
    //if no referral -> just die 
    //uncomment when we open the subscription for everyone
    displayStartpage();
    exit;
}
if(get_param('action')=='success' && $referrer->id){
    displaySuccess($referrer);
    exit;
}


//$error = processTpl('Subscription/err_email_wrongformat.html');
if(get_param('username')){
    $username = get_param('username');
    
    if(get_param('email')){
        $email = get_param('email');
        if(Subscription::fetch_for_email(get_param('email'))){
            $error = processTpl('Subscription/err_email_exists.html');
            displayStepTwo($referrer, $username, $email, $error);
            exit;    
        } elseif(email_syntax_ok($email) == false){
            $error = processTpl('Subscription/err_email_wrongformat.html');
            displayStepTwo($referrer, $username, $email, $error);
            exit;
        } else {
            $subscription = new Subscription();
            processParams($subscription, $referrer->id);
            
            $subscription->insert();
            $referal_url = 'http://oneloudr.com/invite/'.$subscription->rand_str;
            $emailtext = processTpl('Subscription/emailtext.html', array(
                '${username}'                                => $username,
                '${referralUrl}'                             => $referal_url
            ));
            $email_sent = send_email($subscription->email_address, 'Your ONELOUDR Invitation', $emailtext);
            
            
            //prevent reload
            header('Location: /invite/'.$subscription->rand_str.'/success');
            //header('Location: subscription.php?action=success&code='.$subscription->rand_str);
            exit;
          
        }
    }
    
    
    //error checks
    if(strlen($username) < 3){
        $error = processTpl('Subscription/err_username_tooshort.html');
        displayStepOne($referrer, $username, $error);
        exit;
    } elseif(strlen($username) > 50){
        $error = processTpl('Subscription/err_username_toolong.html');
        displayStepOne($referrer, $username, $error);
        exit;
    } elseif(Subscription::fetch_for_username($username)){
        $error = processTpl('Subscription/err_username_exists.html');
        displayStepOne($referrer, $username, $error);
        exit;
    }
    displayStepTwo($referrer, $username);

} else {
    displayStepOne($referrer);
}




function buildSubscriptionHeader($title){
    return processTpl('Subscription/pageHeader.html', array(
        '${pageTitle}'                                => escape($title)
    ));
}

function buildSubscriptionFooter(){
    return processTpl('Subscription/pageFooter.html');
}

function displayStartpage(){
    processAndPrintTpl('Subscription/index.html', array(
        '${Common/pageHeader}'                     => buildSubscriptionHeader('Welcome'),
        '${Common/pageFooter}'                     => buildSubscriptionFooter()
    ));
}

function displayStepOne($referrer, $username = null, $error = null){
    
    processAndPrintTpl('Subscription/step_one.html', array(
        '${Common/pageHeader}'                     => buildSubscriptionHeader('Signup'),
        '${Optional/username}'                     => $username,
        '${Optional/error}'                        => $error,
        '${formAction}'                            => '/invite/'.$referrer->rand_str,
        '${Common/pageFooter}'                     => buildSubscriptionFooter()
    ));
}


function displayStepTwo($referrer, $username, $email = null, $error = null){
    processAndPrintTpl('Subscription/step_two.html', array(
        '${Common/pageHeader}'                     => buildSubscriptionHeader('Signup - Step 2'),
        '${Optional/error}'                        => $error,
        '${Optional/email}'                        => $email,
        '${username}'                              => $username,
        '${formAction}'                            => '/invite/'.$referrer->rand_str,
        '${userName}'                              => get_param('username'),
        '${Common/pageFooter}'                     => buildSubscriptionFooter()
    ));
}

function displaySuccess($referrer){
    
    processAndPrintTpl('Subscription/step_three.html', array(
        '${Common/pageHeader}'                     => buildSubscriptionHeader('Signup - Success'),
        '${referalUrl}'                            => 'http://oneloudr.com/invite/'.$referrer->rand_str,
        '${Common/pageFooter}'                     => buildSubscriptionFooter()
    ));
}


function processParams($subscription, $referrerid = 0){
    $subscription->username = get_param('username');
    $subscription->email_address = get_param('email');
    $subscription->rand_str = rand_char();
    $subscription->referrer_id = $referrerid;
}









/*
 * 
 *
if(get_param('action') == 'success'){
    $subscription = Subscription::fetch_for_rand_str(get_param('code'));
    
    $referal_url = $_SERVER['HTTP_REFERER'].'?referrer='.$subscription->rand_str;
    
    processAndPrintTpl('Subscription/step_three.html', array(
        '${Common/pageHeader}'                     => buildPageHeader('Subscription Title - Success'),
        '${referalUrl}'                            => $referal_url,
        '${Common/pageFooter}'                     => buildPageFooter()
    ));
    exit;
}

if(get_param('username') && get_param('email') && email_syntax_ok(get_param('email'))){
    
    $emailcheck = Subscription::fetch_for_email(get_param('email'));
    if($emailcheck){
        header('Location: subscription.php?action=error&type=email');
        exit;
    }else {
        $subscription = new Subscription();
        processParams($subscription);
    
        $subscription->insert();
        $referal_url = $_SERVER['HTTP_REFERER'].'?referrer='.$subscription->rand_str;
        $email_sent = send_email($subscription->email_address, 'Your ONELOUDR Subscription',
                'Hey ' . $subscription->username . "\n" . 
                'Your referral-url: '. $referal_url . "\n");
                
        //prevent reload
        header('Location: subscription.php?action=success&code='.$subscription->rand_str);
        exit;
    }

} elseif(get_param('username') && (strlen(get_param('username')) >= 3)){
    $error = false;
    
    if(get_param('email') && email_syntax_ok(get_param('email')) == false){
        $error = processTpl('Subscription/err_email_wrongformat.html');
    }
    
    //check if user already exists:
    $namecheck = Subscription::fetch_for_username(get_param('username'));
    if($namecheck){
        header('Location: subscription.php?action=error&type=name');
        exit;
    }
        
    processAndPrintTpl('Subscription/step_two.html', array(
        '${Common/pageHeader}'                     => buildPageHeader('Subscription Title - Step 2'),
        '${Optional/referrerId}'                   => get_param('referrerid'),
        '${Optional/error}'                        => $error,
        '${phpSelf}'                               => $_SERVER['PHP_SELF'],
        '${userName}'                              => get_param('username'),
        '${Common/pageFooter}'                     => buildPageFooter()
    ));
} else{
    $error = false;
    
    if(get_param('username')){
        if(strlen(get_param('username')) <= 3){
            $error = processTpl('Subscription/err_username_tooshort.html');
        } elseif(strlen(get_param('username')) > 50){
            $error = processTpl('Subscription/err_username_toolong.html');
        }
    }
    if(get_param('action') == 'error'){
        if(get_param('type') == 'name'){
            $error = processTpl('Subscription/err_username_exists.html');
        } elseif (get_param('type') == 'email'){
            $error = processTpl('Subscription/err_email_exists.html');
        }
        
    }
    
    $referrer = false;
    $referrer_name = false;
    if(get_param('referrer')){
        $referrer = Subscription::fetch_for_rand_str(get_param('referrer'));
        $referrer_name = processTpl('Subscription/msg_referredby.html', array(
            '${referrer_name}' => $referrer->username
        ));
    }

    processAndPrintTpl('Subscription/step_one.html', array(
        '${Common/pageHeader}'                     => buildPageHeader('Subscription Title'),
        '${Optional/referrerName}'                 => $referrer_name,
        '${Optional/referrerId}'                   => $referrer->id,
        '${Optional/error}'                        => $error,
        '${phpSelf}'                               => '/invite/',
        '${Common/pageFooter}'                     => buildPageFooter()
    ));
}



*/

?>
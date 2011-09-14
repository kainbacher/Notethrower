<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Subscription.php');

if(get_param('action') == 'success'){
    $subscription = Subscription::fetch_for_rand_str(get_param('code'));
    
    $referal_url = $_SERVER['HTTP_REFERER'].'?referrer='.$subscription->rand_str;
    $email_sent = send_email($subscription->email_address, 'Your ONELOUDR Subscription',
                'Hey ' . $subscription->username . "\n" . 
                'Your referral-url: '. $referal_url . "\n");
    
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
        '${phpSelf}'                               => $_SERVER['PHP_SELF'],
        '${Common/pageFooter}'                     => buildPageFooter()
    ));
}


function processParams($subscription){
    $subscription->username = get_param('username');
    $subscription->email_address = get_param('email');
    $subscription->rand_str = rand_char();
    $subscription->referrer_id = get_param('referrerid');
}

?>
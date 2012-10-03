<?php

include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/EditorInfo.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/Mailer/MailUtil.php');

$loggedInUser = User::new_from_cookie();

$problemOccured = false;
$errorFields = Array();

$trackId = get_numeric_param('tid');

$track  = ProjectFile::fetch_for_id($trackId);
$project = Project::fetch_for_id($track->project_id);
$user   = User::fetch_for_id($project->user_id);

$offer->email = '';
$offer->usage = '';
$offer->price = '';
$offer->accepted = false;
$offer->emailArtistOffer = '';
$offer->usageArtistOffer = '';

$offerSent = false;

if (get_param('action') == 'send') {
    if (!inputDataOk($errorFields, $offer)) {
        $problemOccured = true;
    } else {
        // send offer as email
        $text = $offer->email . ' made an offer for your song "' . $track->release_title . '" on oneloudr.com.' . "\n" .
                'Usage for your song: ' . $offer->usage . "\n" .
                'Category: ' . $offer->usageCategory . "\n" .
                'Offer in $: ' . $offer->price . "\n" . "\n" .
                'Your oneloudr team';
        sendEmail($user->email_address, 'New offer for song on oneloudr.com', $text);
        sendEmail($GLOBALS['SELLER_EMAIL'], 'New offer for song on oneloudr.com', $text);
        $offerSent = true;
    }
} else if (get_param('action') == 'checkout') {

    if (!inputDataOkForArtistOffer($errorFields, $offer)) {
        $problemOccured = true;
    } else {
        // send an email with the checkout info
        $text = 'Someone (email: ' . $offer->emailArtistOffer . ') has accepted your offer for track "' . $track->release_title . '" on oneloudr.com.' . "\n" .
                'Usage for your song: ' . $offer->usageArtistOffer . "\n" .
                'Category: ' . $offer->usageCategoryArtistOffer . "\n" .
                'Once the user finishes the paypal transaction, you should receive from paypal a message about the purchase' . "\n\n" .
                'Your oneloudr team';
        sendEmail($user->email_address, 'Song checkout on oneloudr.com', $text);
        sendEmail($GLOBALS['SELLER_EMAIL'], 'Song checkout on oneloudr.com', $text);
        //email sent, redirect to paypal
        $paypalUrl = $GLOBALS['PAYPAL_BASE_URL'] . '&business=' . $GLOBALS['SELLER_EMAIL'];
        $paypalUrl .= '&item_name=' . urlencode($user->name . ' - ' . $track->release_title);
        $paypalUrl .= '&cbt=' . urlencode(substr('Download: ' . $user->name . ' - ' . $track->release_title, 0, 60));
        $paypalUrl .= '&item_number=PPI' . $track->id;
        $paypalUrl .= '&amount=' . $track->price; // FIXME - this field is not there yet
        $paypalUrl .= '&currency_code=' . $project->currency; 
        $paypalUrl .= '&return=' . urlencode($GLOBALS['RETURN_URL'] . '?tid=' . $track->id . '&mode=purchase');
        $paypalUrl .= '&cancel_return=' . urlencode($GLOBALS['BASE_URL']);
        $paypalUrl .= $GLOBALS['PAYPAL_FIX_PARAMS'];
        header( 'Location: ' . $paypalUrl ) ;
    }
}

// optional form
$form = '';
$offerSentMsg = '';
if ($offerSent) {
    $offerSentMsg = 'Thank you for making an offer. We will get back to you as soon as possible!<p>&nbsp;</p>';
    
} else {
    if ($track->price > 0) { // FIXME - this field is not there yet
        $emailArtistOffer = '<input class="' . (isset($errorFields['emailArtistOffer']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" type="text" name="emailArtistOffer" maxlength="255" size="40"';
        $emailArtistOffer .= ' value="';
        $value = $offer->emailArtistOffer;
        $emailArtistOffer .= ($preconfiguredValue && !$value ? escape($preconfiguredValue) : escape($value));
        $emailArtistOffer .= '">' . "\n";
        
        $usageArtistOffer = '<select class="' . (isset($errorFields['usageArtistOffer']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" name="usageCategoryArtistOffer" style="width: 255px">';
        $selectedValue = $offer->usageCategoryArtistOffer;
        $selectOptions = array(-1 => '- Please choose -') + $GLOBALS['OFFER_CATEGORIES'];
        foreach (array_values($selectOptions) as $optVal) {
            $selected = ((string) $selectedValue === (string) $optVal) ? ' selected' : '';
            $usageArtistOffer .= '<option value="' . $optVal . '"' . $selected . '>' . escape($optVal) . '</option>' . "\n";
        }
        $usageArtistOffer .= '</select>' . "\n";

        $usageArtistOffer .= '<textarea class="' . (isset($errorFields['usageArtistOffer']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" name="usageArtistOffer" rows="6" cols="40">';
        $value = $offer->usageArtistOffer;
        $usageArtistOffer .= escape($value);
        $usageArtistOffer .= '</textarea>' . "\n";

    
        $form = processTpl('MakeAnOffer/form.html', array(
            '${trackId}' => $track->id,
            '${userName}' => escape($user->name),
            '${trackTitle}' => escape($track->release_title),
            '${trackPrice}' => '$' . $track->price, // FIXME
            '${emailArtistOffer}' => $emailArtistOffer,
            '${usageArtistOffer}' => $usageArtistOffer
        ));
    
    } // if ($track->price > 0)
}

$email = '<input class="' . (isset($errorFields['email']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" type="text" name="email" maxlength="255" size="40"';
$email .= ' value="';
$value = $offer->email;
$email .= ($preconfiguredValue && !$value ? escape($preconfiguredValue) : escape($value));
$email .= '">' . "\n";

$usageCategory = '<select class="' . (isset($errorFields['usage']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" name="usageCategory" style="width: 255px">';
$selectedValue = $offer->usageCategory;
$selectOptions = array(-1 => '- Please choose -') + $GLOBALS['OFFER_CATEGORIES'];
foreach (array_values($selectOptions) as $optVal) {
    $selected = ((string) $selectedValue === (string) $optVal) ? ' selected' : '';
    $usageCategory .= '<option value="' . $optVal . '"' . $selected . '>' . escape($optVal) . '</option>' . "\n";
}
$usageCategory .= '</select>' . "\n";

$usageCategory .= '<textarea class="' . (isset($errorFields['usage']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" name="usage" rows="6" cols="40">';
$value = $offer->usage;
$usageCategory .= escape($value);
$usageCategory .= '</textarea>' . "\n";

$price = '<input class="' . (isset($errorFields['price']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" type="text" name="price" maxlength="255" size="40"';
$price .= ' value="';
$value = $offer->price;
$price .= (escape($value));
$price .= '">' . "\n";

$accepted = '<input class="' . (isset($errorFields['accepted']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" type="checkbox" name="accepted" value="yes">';

if (isset($errorFields['accepted'])) {
    $accepted .= '<p class="problemMessage">' .$errorFields['accepted'] . '</p>';
}

$usageInfoHtml = null;
$editorInfo = EditorInfo::fetchForId($EDITOR_INFO_ID_MAKE_OFFER_USAGE_INFO);
if (!$editorInfo) $usageInfoHtml = $MISSING_EDITOR_INFO_TEXT . ($user && $user->is_editor ? ' <a href="' . $GLOBALS['BASE_URL'] . 'Backend/editInfo.php">Enter the text for this section now!</a>' : '');
else              $usageInfoHtml = $editorInfo->html;

$aboutLicensingTermsHtml = null;
$editorInfo = EditorInfo::fetchForId($EDITOR_INFO_ID_MAKE_OFFER_ABOUT_LICENSING_TERMS);
if (!$editorInfo) $aboutLicensingTermsHtml = $MISSING_EDITOR_INFO_TEXT . ($user && $user->is_editor ? ' <a href="' . $GLOBALS['BASE_URL'] . 'Backend/editInfo.php">Enter the text for this section now!</a>' : '');
else              $aboutLicensingTermsHtml = $editorInfo->html;

$licensingTermsHtml = null;
$editorInfo = EditorInfo::fetchForId($EDITOR_INFO_ID_MAKE_OFFER_LICENSING_TERMS);
if (!$editorInfo) $licensingTermsHtml = $MISSING_EDITOR_INFO_TEXT . ($user && $user->is_editor ? ' <a href="' . $GLOBALS['BASE_URL'] . 'Backend/editInfo.php">Enter the text for this section now!</a>' : '');
else              $licensingTermsHtml = $editorInfo->html;

processAndPrintTpl('MakeAnOffer/index.html', array(
    '${Common/pageHeader}'                      => buildPageHeader('Make an offer'),
    '${Common/bodyHeader}'                      => buildBodyHeader($loggedInUser),
    '${MakeAnOffer/artistOfferForm_optional}'   => $form,
    '${trackId}'                                => $track->id,
    '${headline}'                               => 'Make ' . ($track->price > 0 ? 'a different ' : 'an ') . 'Offer', // FIXME
    '${trackTitle}'                             => escape($track->release_title),
    '${email}'                                  => $email,
    '${usageCategory}'                          => $usageCategory,
    '${price}'                                  => $price,
    '${accepted}'                               => $accepted,
    '${offerSent_optional}'                     => $offerSentMsg,
    '${usageInfo}'                              => $usageInfoHtml,
    '${aboutLicensingTerms}'                    => $aboutLicensingTermsHtml,
    '${licensingTerms}'                         => $licensingTermsHtml,
    '${Common/bodyFooter}'                      => buildBodyFooter(),
    '${Common/pageFooter}'                      => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------
function inputDataOk(&$errorFields, &$offer) {
    global $logger;
    $result = true;

    $offer->usage = get_param('usage');
    $offer->price = get_param('price');
    $offer->accepted = get_param('accepted');
    $offer->email = get_param('email');
    $offer->usageCategory = get_param('usageCategory');

    if (!$offer->email) {
        $result = false;
        $errorFields['email'] = 'Please specify your email address!';
    }
    if (!$offer->usage || $offer->usageCategory === '- Please choose -') {
        $result = false;
        $errorFields['usage'] = 'Please describe the use you wish to make of the song and select an usage from the list!';
    }
    if (!$offer->price) {
        $result = false;
        $errorFields['price'] = 'Please provide a price!';
    }
    if (!$offer->accepted) {
        $result = false;
        $errorFields['accepted'] = 'Please accept the terms!';
    }

    return $result;

}

function inputDataOkForArtistOffer(&$errorFields, &$offer) {
    global $logger;
    $result = true;

    $offer->usageArtistOffer = get_param('usageArtistOffer');
    $offer->emailArtistOffer = get_param('emailArtistOffer');
    $offer->usageCategoryArtistOffer = get_param('usageCategoryArtistOffer');

    if (!$offer->emailArtistOffer) {
        $result = false;
        $errorFields['emailArtistOffer'] = 'Please specify your email address!';
    }
    if (!$offer->usageArtistOffer || $offer->usageCategoryArtistOffer === '- Please choose -') {
        $result = false;
        $errorFields['usageArtistOffer'] = 'Please describe the use you wish to make of the song and select an usage from the list!';
    }

    return $result;

}

?>

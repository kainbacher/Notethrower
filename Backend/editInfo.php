<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/FormUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/EditorInfo.php');
include_once('../Includes/DB/User.php');

$user = User::new_from_cookie();
if ($user && ($user->is_editor || $user->is_admin)) {
    $logger->info('editor/admin user is logged in');
    
} else {
    $logger->info('user is not logged in or not an editor/admin');
    show_fatal_error_and_exit('access denied');
}

$obj = null;
$unpersistedObj = null;
$msgsAndErrorsList = '';
$errorFields = array();
$problemOccured = false;
$objIsAboutToBeCreated = false;    
        
if (get_param('action') == 'save') {
    $id = get_param('textId');
    if (!$id) show_fatal_error_and_exit('missing id parameter');

    $logger->info('attempting to save editor info data ...');
    if (inputDataOk($errorFields)) {
        $obj = EditorInfo::fetchForId($id);
        if (!$obj) {
            $obj = EditorInfo::createDefaultObj();
            $objIsAboutToBeCreated = true;
        }

        processParams($obj);        
        $objIsAboutToBeCreated ? $obj->insert() : $obj->update();
        $objIsAboutToBeCreated = false; 
    
        $msgsAndErrorsList .= processTpl('Common/message_success.html', array(
            '${msg}' => escape('Saved')
        ));
        
    } else {
        $logger->info('input data was invalid: ' . join(', ', $errorFields));
        
        $unpersistedObj = EditorInfo::createDefaultObj();
        processParams($unpersistedObj);
        
        $msgsAndErrorsList .= processTpl('Common/message_error.html', array(
            '${msg}' => escape('Please correct the highlighted problems')
        ));
        $problemOccured = true;
    }
    
} else {
    $id = get_param('textId');
    if (!$id) $id = current(array_keys($EDITOR_INFO_ID_LIST));
    
    $obj = EditorInfo::fetchForId($id);
    if (!$obj) {
        $unpersistedObj = EditorInfo::createDefaultObj();
        $unpersistedObj->textId = $id;
        $objIsAboutToBeCreated = true;
    }
}

$formElements = '';

$formElements .= getFormFieldForParams(array(
    'inputType'              => 'select',
    'propName'               => 'textId',
    'label'                  => 'Info text ID',
    'mandatory'              => true,
    'onChangeCallback'       => 'idWasChanged()',
    'obj'                    => $obj,
    'unpersistedObj'         => $unpersistedObj,
    'selectOptions'          => $EDITOR_INFO_ID_LIST,
    'errorFields'            => $errorFields,
    'workWithUnpersistedObj' => $problemOccured || $objIsAboutToBeCreated
));

$formElements .= getFormFieldForParams(array(
    'inputType'              => 'textarea', // will be turned into a TinyMCE editor
    'propName'               => 'html',
    'label'                  => 'Content',
    'mandatory'              => false,
    'obj'                    => $obj,
    'unpersistedObj'         => $unpersistedObj,
    'errorFields'            => $errorFields,
    'workWithUnpersistedObj' => $problemOccured || $objIsAboutToBeCreated
));

processAndPrintTpl('EditInfo/index.html', array(
    '${Common/pageHeader}'              => buildPageHeader('Edit info', false, false, false, false, false, false, true), // $title, $includeJPlayerStuff, $includeAjaxPagination, $includeChosenStuff, $includeGooglemapStuff, $useMobileVersion, $ogTags, $includeTinyMCE)
    '${Common/bodyHeader}'              => buildBodyHeader($user),
    '${Common/message_choice_list}'     => $msgsAndErrorsList,
    '${formAction}'                     => basename($_SERVER['PHP_SELF']),
    '${Common/formElement_list}'        => $formElements,
    '${Common/bodyFooter}'              => buildBodyFooter(),
    '${Common/pageFooter}'              => buildPageFooter()
));

// END

// functions
function inputDataOk(&$errorFields) {
    global $EDITOR_INFO_ID_LIST;
    
    // check id
    if (!in_array(get_param('textId'), array_keys($EDITOR_INFO_ID_LIST))) {
        $errorFields['textId'] = 'Invalid text ID!'; // can only happen when someone messes around with POST params
    }
    
    return (count($errorFields) == 0);
}

function processParams(&$obj) {
    $obj->textId = get_param('textId');
    $obj->html   = get_param('html');
}
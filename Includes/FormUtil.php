<?php

include_once('../Includes/Config.php');
include_once('../Includes/recaptchalib.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');

function showFormFieldForParams($params) {
    echo getFormFieldForParams($params);
}

function getFormFieldForParams($params) {
    global $logger;

    // mandatory params:
    $propName                 = $params['propName'];
    $mandatory                = $params['mandatory'];
    $obj                      = $params['obj'];
    $unpersistedObj           = $params['unpersistedObj'];
    $errorFields              = $params['errorFields'];
    $workWithUnpersistedObj   = $params['workWithUnpersistedObj'];

    // optional params
    $label                     = array_key_exists('label', $params)                     ? $params['label']                     : str($propName);
    $inputType                 = array_key_exists('inputType', $params)                 ? $params['inputType']                 : 'text';
    $inputFieldGroupPrefix     = array_key_exists('inputFieldGroupPrefix', $params)     ? $params['inputFieldGroupPrefix']     : null;
    $inputFieldPrefix          = array_key_exists('inputFieldPrefix', $params)          ? $params['inputFieldPrefix']          : null;
    $inputFieldSuffix          = array_key_exists('inputFieldSuffix', $params)          ? $params['inputFieldSuffix']          : null;
    $inputFieldGroupSuffixHtml = array_key_exists('inputFieldGroupSuffixHtml', $params) ? $params['inputFieldGroupSuffixHtml'] : null;
    $maxlength                 = array_key_exists('maxlength', $params)                 ? $params['maxlength']                 : 0;
    $size                      = array_key_exists('size', $params)                      ? $params['size']                      : 0;
    $selectOptions             = array_key_exists('selectOptions', $params)             ? $params['selectOptions']             : array();
    $objValue                  = array_key_exists('objValue', $params)                  ? $params['objValue']                  : null;
    $objValues                 = array_key_exists('objValues', $params)                 ? $params['objValues']                 : null;
    $infoText                  = array_key_exists('infoText', $params)                  ? $params['infoText']                  : null;
    $infoHtml                  = array_key_exists('infoHtml', $params)                  ? $params['infoHtml']                  : null;
    $onChangeCallback          = array_key_exists('onChangeCallback', $params)          ? $params['onChangeCallback']          : null;
    $customStyleForInputField  = array_key_exists('customStyleForInputField', $params)  ? $params['customStyleForInputField']  : null;
    $hide                      = array_key_exists('hide', $params)                      ? $params['hide']                      : false;
    $rows                      = array_key_exists('rows', $params)                      ? $params['rows']                      : 3;
    $cols                      = array_key_exists('cols', $params)                      ? $params['cols']                      : 30;
    $disabled                  = array_key_exists('disabled', $params)                  ? $params['disabled']                  : false;
    $readonly                  = array_key_exists('readonly', $params)                  ? $params['readonly']                  : false;
    $recaptchaPublicKey        = array_key_exists('recaptchaPublicKey', $params)        ? $params['recaptchaPublicKey']        : '';
    $objValueOverride          = array_key_exists('objValueOverride', $params)          ? $params['objValueOverride']          : null;
    $objValuesOverride         = array_key_exists('objValuesOverride', $params)         ? $params['objValuesOverride']         : null;
    $cssClassSuffix            = array_key_exists('cssClassSuffix', $params)            ? $params['cssClassSuffix']            : null;
    $maxFileSizeForUpload      = array_key_exists('maxFileSizeForUpload', $params)      ? $params['maxFileSizeForUpload']      : null;

    // label
    $label = processTpl('Common/formElementLabel_' . ($mandatory ? 'mandatory' : 'optional') . '.html', array(
        '${label}' => ($label ? escape($label) . ':' : '')
    ));

    // input field
    $inputField = '';
    if (
        $inputType == 'select' ||
        $inputType == 'select2' // when the object values should not be read from the object (ie. when they are not persisted as a comma-separated list of values but in a different table)
    ) {
        if ($inputType == 'select') {
            $val            = array();
            $unpersistedVal = array();
            eval('if ($obj) $val = $obj->' . $propName . ';');
            eval('if ($unpersistedObj) $unpersistedVal = $unpersistedObj->' . $propName . ';');
            $objValue = $workWithUnpersistedObj ? $unpersistedVal : $val;
        }

        $options = '';
        foreach (array_keys($selectOptions) as $optVal) {
            $selected = ((string) $objValue === (string) $optVal) ? ' selected' : '';
            $options .= processTpl('Common/formElementInputField_select_option.html', array(
                '${value}'             => $optVal,
                '${selected_optional}' => $selected,
                '${label}'             => escape($selectOptions[$optVal])
            ));
        }

        $inputField = processTpl('Common/formElementInputField_select.html', array(
            '${id}'                                              => $propName,
            '${name}'                                            => $propName,
            '${prefix}'                                          => ($inputFieldPrefix ? escape($inputFieldPrefix) . '&nbsp;' : ''),
            '${suffix}'                                          => ($inputFieldSuffix ? '&nbsp;' . escape($inputFieldSuffix) : ''),
            '${size_optional}'                                   => '',
            '${multiple_optional}'                               => '',
            '${disabled_optional}'                               => ($disabled ? ' disabled="disabled"' : ''),
            '${onChangeCallback_optional}'                       => ($onChangeCallback ? ' onChange="' . $onChangeCallback . '"' : ''),
            '${Common/formElementInputField_select_option_list}' => $options,
            '${cssClassSuffix}'                                  => ($cssClassSuffix ? ' ' . $cssClassSuffix : '')
        ));

    } else if (
        $inputType == 'multiselect' ||
        $inputType == 'multiselect2' // when the object values should not be read from the object (ie. when they are not persisted as a comma-separated list of values but in a different table)
    ) {
        if ($inputType == 'multiselect') {
            $values            = array();
            $unpersistedValues = array();
            eval('if ($obj) $values = $obj->' . $propName . ';');
            eval('if ($unpersistedObj) $unpersistedValues = $unpersistedObj->' . $propName . ';');
            $objValues = $workWithUnpersistedObj ? $unpersistedValues : $values;
        }

        $options = '';

        if (count($selectOptions) == 0) {
            $disabled = true;
            $options .= processTpl('Common/formElementInputField_select_option.html', array(
                '${value}'             => '',
                '${selected_optional}' => '',
                '${label}'             => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
            ));
        }

        foreach (array_keys($selectOptions) as $optVal) {
            $selected = $objValues && in_array($optVal, $objValues) ? $selected = ' selected' : ''; // in_array() with strict flag is making problems with numeric values
            $options .= processTpl('Common/formElementInputField_select_option.html', array(
                '${value}'             => $optVal,
                '${selected_optional}' => $selected,
                '${label}'             => escape($selectOptions[$optVal])
            ));
        }

        $inputField = processTpl('Common/formElementInputField_select.html', array(
            '${id}'                                              => $propName,
            '${name}'                                            => $propName,
            '${prefix}'                                          => ($inputFieldPrefix ? escape($inputFieldPrefix) . '&nbsp;' : ''),
            '${suffix}'                                          => ($inputFieldSuffix ? '&nbsp;' . escape($inputFieldSuffix) : ''),
            '${size_optional}'                                   => ($size ? ' size="' . $size . '"' : ''),
            '${multiple_optional}'                               => ' multiple',
            '${disabled_optional}'                               => ($disabled ? ' disabled' : ''),
            '${onChangeCallback_optional}'                       => ($onChangeCallback ? ' onChange="' . $onChangeCallback . '"' : ''),
            '${Common/formElementInputField_select_option_list}' => $options,
            '${cssClassSuffix}'                                  => ($cssClassSuffix ? ' ' . $cssClassSuffix : '')
        ));

    } else if ($inputType == 'checkbox') {
        $value            = null;
        $unpersistedValue = null;
        eval('if ($obj) $value = $obj->' . $propName . ';');
        eval('if ($unpersistedObj) $unpersistedValue = $unpersistedObj->' . $propName . ';');
        $objValue = $workWithUnpersistedObj ? $unpersistedValue : $value;
        if ($objValueOverride) $objValue = $objValueOverride;

        $inputField = processTpl('Common/formElementInputField_checkbox.html', array(
            '${id}'                        => $propName,
            '${name}'                      => $propName,
            '${value}'                     => 1, // should be sufficient for single checkbox
            '${label}'                     => '', // no label for single checkbox
            '${prefix}'                    => ($inputFieldPrefix ? escape($inputFieldPrefix) . '&nbsp;' : ''),
            '${suffix}'                    => ($inputFieldSuffix ? '&nbsp;' . escape($inputFieldSuffix) : ''),
            '${checked_optional}'          => ($objValue ? ' checked="checked"' : ''),
            '${disabled_optional}'         => ($disabled ? ' disabled="disabled"' : ''),
            '${onChangeCallback_optional}' => ($onChangeCallback ? ' onClick="' . $onChangeCallback . '"' : '')
        ));

    } else if ($inputType == 'checkboxes') {
        if ($objValuesOverride) {
            $objValues = $objValuesOverride;
            
        } else {
            $values            = array();
            $unpersistedValues = array();
            eval('if ($obj) $values = $obj->' . $propName . ';');
            eval('if ($unpersistedObj) $unpersistedValues = $unpersistedObj->' . $propName . ';');
            $objValues = $workWithUnpersistedObj ? $unpersistedValues : $values;
        }

        $i = 0;
        foreach (array_keys($selectOptions) as $optVal) {
            $inputField .= processTpl('Common/formElementInputField_checkbox.html', array(
                '${id}'                        => $propName . '_' . $i,
                '${name}'                      => $propName,
                '${value}'                     => $optVal,
                '${label}'                     => escape($selectOptions[$optVal]),
                '${prefix}'                    => ($inputFieldPrefix ? escape($inputFieldPrefix) . '&nbsp;' : ''),
                '${suffix}'                    => ($inputFieldSuffix ? '&nbsp;' . escape($inputFieldSuffix) : ''),
                '${checked_optional}'          => ($objValues && in_array($optVal, $objValues) ? ' checked' : ''),
                '${disabled_optional}'         => ($disabled ? ' disabled="disabled"' : ''),
                '${onChangeCallback_optional}' => ($onChangeCallback ? ' onClick="' . $onChangeCallback . '"' : '')
            )) . '<br />'; // FIXME - linebreak should be part of template somehow

            $i++;
        }

    } else if ($inputType == 'radio') {
        $val            = null;
        $unpersistedVal = null;
        eval('if ($obj) $val = $obj->' . $propName . ';');
        eval('if ($unpersistedObj) $unpersistedVal = $unpersistedObj->' . $propName . ';');
        $selectedValue = $workWithUnpersistedObj ? $unpersistedVal : $val;

        $i = 0;
        foreach (array_keys($selectOptions) as $optVal) {
            $inputField .= processTpl('Common/formElementInputField_radio.html', array(
                '${id}'                        => $propName . '_' . $i,
                '${name}'                      => $propName,
                '${value}'                     => $optVal,
                '${label}'                     => escape($selectOptions[$optVal]),
                '${prefix}'                    => ($inputFieldPrefix ? escape($inputFieldPrefix) . '&nbsp;' : ''),
                '${suffix}'                    => ($inputFieldSuffix ? '&nbsp;' . escape($inputFieldSuffix) : ''),
                '${checked_optional}'          => ((string) $selectedValue === (string) $optVal ? ' checked' : ''),
                '${disabled_optional}'         => ($disabled ? ' disabled="disabled"' : ''),
                '${onChangeCallback_optional}' => ($onChangeCallback ? ' onClick="' . $onChangeCallback . '"' : '')
            ));

            $i++;
        }

    } else if ($inputType == 'textarea') {
        $val            = null;
        $unpersistedVal = null;
        eval('if ($obj) $val = $obj->' . $propName . ';');
        eval('if ($unpersistedObj) $unpersistedVal = $unpersistedObj->' . $propName . ';');
        $text = $workWithUnpersistedObj ? $unpersistedVal : $val;

        $inputField = processTpl('Common/formElementInputField_textArea.html', array(
            '${id}'                        => $propName,
            '${name}'                      => $propName,
            '${rows}'                      => $rows,
            '${cols}'                      => $cols,
            '${maxlength_optional}'        => ($maxlength ? ' maxlength="' . $maxlength . '"' : ''),
            '${text}'                      => escape($text),
            '${prefix}'                    => ($inputFieldPrefix ? escape($inputFieldPrefix) . '&nbsp;' : ''),
            '${suffix}'                    => ($inputFieldSuffix ? '&nbsp;' . escape($inputFieldSuffix) : ''),
            '${disabled_optional}'         => ($disabled ? ' disabled="disabled"' : ''),
            '${readonly_optional}'         => ($readonly ? ' readonly="readonly"' : ''),
            '${onChangeCallback_optional}' => ($onChangeCallback ? ' onChange="' . $onChangeCallback . '"' : ''),
            '${customStyle}'               => ($customStyleForInputField ? ' style="' . $customStyleForInputField . '"' : ''),
            '${charsRemaining_optional}'   => ($maxlength ? processTpl('Common/formElementInputField_textArea_charsRemaining.html', array()) : '')
        ));

//    } else if ($inputType == 'dateSelection') { // ATTENTION - this requires some jquery stuff to be loaded (jquery lib, jquery-ui lib, jquery-ui stylesheet+images, a calendar icon)
//        $html .= '<input class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" ' .
//                 ($customStyleForInputField ? 'style="' . $customStyleForInputField . '" ' : '') .
//                 'type="' . $inputType . '" ' .
//                 'name="' . $propName . '" ' .
//                 'id="' . $propName . '_textfield"';
//
//        $html .= ' maxlength="10"'; // eg. 12.12.2012
//
//        $html .= ' size="' . $size . '"';
//
//        $html .= ' value="';
//        $val            = null;
//        $unpersistedVal = null;
//        eval('if ($obj) $val = $obj->' . $propName . ';');
//        eval('if ($unpersistedObj) $unpersistedVal = $unpersistedObj->' . $propName . ';');
//        $finalVal = $workWithUnpersistedObj ? $unpersistedVal : $val;
//        if ($finalVal) {
//            if (isValidYYYYMMDDDateStr($finalVal)) {
//                $finalVal = substr($finalVal, 8, 2) . '.' . substr($finalVal, 5, 2) . '.' . substr($finalVal, 0, 4);
//            } else {
//                // noop. take the unmodified (and wrongly formatted or simply invalid value)
//            }
//        }
//        $html .= escape($finalVal);
//        $html .= '"';
//
//        $uniqueId = $propName . rand(0, 9999); // the propName makes it unique enough but we add a random number to make sure
//
//        $html .= ' onChange="update_' . $uniqueId . '_date();"';
//
//        $html .= '>';
//
//        //done $html .= ($inputFieldSuffix ? '&nbsp;' . $inputFieldSuffix : '');
//
//        $html .= '&nbsp;<img class="cursorHand" id="' . $uniqueId . '_btn" src="../Images/Buttons/calendar_icon_20x20.png" style="vertical-align:bottom">';
//
//        $html .= '<div id="' . $uniqueId . '_div" type="text" style="display:none"></div>';
//
//        $html .= '<script type="text/javascript">' . "\n" .
//                 '  $(document).ready(function() {' . "\n" .
//                 "    $('#" . $uniqueId . "_div').datepicker({\n" .
//                 '      showWeek: true,' . "\n" .
//                 '      firstDay: 1,' . "\n" .
//                 "      dateFormat: 'dd.mm.yy',\n" .
//                 //"      defaultDate: '" . $startDate . "',\n" . // FIXME
//                 '      onSelect: function(dateText, inst) {' . "\n" .
//                 "        $('#" . $propName . "_textfield').val(dateText);\n" .
//                 '      }' . "\n" .
//                 '    });' . "\n\n" .
//
//                 "    $('#" . $uniqueId . "_btn').click(function() {\n" .
//                 "      $('#" . $uniqueId . "_div').slideToggle('fast');\n" .
//                 '      return false;' . "\n" .
//                 '    });' . "\n\n" .
//
//                 '    update_' . $uniqueId . '_date();' . "\n" . // do an initial update of the datepicker with the value from the textfield
//                 '  });' . "\n\n" .
//
//                 '  function update_' . $uniqueId . '_date() {' . "\n" .
//                 "    $('#" . $uniqueId . "_div').datepicker('setDate', $('#" . $propName . "_textfield').val());\n" .
//                 '  }' . "\n" .
//                 '</script>' . "\n";
//
    } else if ($inputType == 'text') {
        $val            = null;
        $unpersistedVal = null;
        eval('if ($obj) $val = $obj->' . $propName . ';');
        eval('if ($unpersistedObj) $unpersistedVal = $unpersistedObj->' . $propName . ';');
        $value = $workWithUnpersistedObj ? $unpersistedVal : $val;

        $inputField = processTpl('Common/formElementInputField_textField.html', array(
            '${id}'                        => $propName,
            '${name}'                      => $propName,
            '${size_optional}'             => ($size > 0 ? ' size="' . $size . '"' : ''),
            '${maxlength_optional}'        => ($maxlength > 0 ? ' maxlength="' . $maxlength . '"' : ''),
            '${value}'                     => escape($value),
            '${prefix}'                    => ($inputFieldPrefix ? escape($inputFieldPrefix) . '&nbsp;' : ''),
            '${suffix}'                    => ($inputFieldSuffix ? '&nbsp;' . escape($inputFieldSuffix) : ''),
            '${disabled_optional}'         => ($disabled ? ' disabled="disabled"' : ''),
            '${readonly_optional}'         => ($readonly ? ' readonly="readonly"' : ''),
            '${onChangeCallback_optional}' => ($onChangeCallback ? ' onChange="' . $onChangeCallback . '"' : '')
        ));

    } else if ($inputType == 'password') {
        $inputField = processTpl('Common/formElementInputField_password.html', array(
            '${id}'                        => $propName,
            '${name}'                      => $propName,
            '${size_optional}'             => ($size > 0 ? ' size="' . $size . '"' : ''),
            '${maxlength_optional}'        => ($maxlength > 0 ? ' maxlength="' . $maxlength . '"' : ''),
            '${prefix}'                    => ($inputFieldPrefix ? escape($inputFieldPrefix) . '&nbsp;' : ''),
            '${suffix}'                    => ($inputFieldSuffix ? '&nbsp;' . escape($inputFieldSuffix) : ''),
            '${disabled_optional}'         => ($disabled ? ' disabled="disabled"' : ''),
            '${readonly_optional}'         => ($readonly ? ' readonly="readonly"' : ''),
            '${onChangeCallback_optional}' => ($onChangeCallback ? ' onChange="' . $onChangeCallback . '"' : '')
        ));

    } else if ($inputType == 'file') {
        $maxFileSizeAddon = '';
        if ($maxFileSizeForUpload) {
            $maxFileSizeAddon = '<input type="hidden" name="MAX_FILE_SIZE" value="' . $maxFileSizeForUpload . '">';
        }

        $inputField = processTpl('Common/formElementInputField_file.html', array(
            '${id}'                        => $propName,
            '${name}'                      => $propName,
            '${size_optional}'             => ($size > 0 ? ' size="' . $size . '"' : ''),
            '${maxlength_optional}'        => ($maxlength > 0 ? ' maxlength="' . $maxlength . '"' : ''),
            '${prefix}'                    => ($inputFieldPrefix ? escape($inputFieldPrefix) . '&nbsp;' : ''),
            '${suffix}'                    => ($inputFieldSuffix ? '&nbsp;' . escape($inputFieldSuffix) : ''),
            '${disabled_optional}'         => ($disabled ? ' disabled="disabled"' : ''),
            '${readonly_optional}'         => ($readonly ? ' readonly="readonly"' : ''),
            '${onChangeCallback_optional}' => ($onChangeCallback ? ' onChange="' . $onChangeCallback . '"' : ''),
            '${maxFileSizeAddon}'          => $maxFileSizeAddon
        ));

    } else if ($inputType == 'recaptcha') {
        $inputField = processTpl('Common/formElementInputField_recaptcha.html', array(
            '${prefix}'    => ($inputFieldPrefix ? escape($inputFieldPrefix) . '&nbsp;' : ''),
            '${recaptcha}' => recaptcha_get_html($recaptchaPublicKey),
            '${suffix}'    => ($inputFieldSuffix ? '&nbsp;' . escape($inputFieldSuffix) : '')
        ));
    }

    // info text
    $help = '';
    if ($infoText || $infoHtml) {
        $help = processTpl('Common/formElementHelp.html', array(
            '${text}' => $infoHtml ? $infoHtml : escape($infoText)
        ));
    }

    // optional message
    $errorMsg = '';
    if (isset($errorFields[$propName])) {
        $errorMsg = processTpl('Common/formElementError.html', array(
            '${text}' => escape($errorFields[$propName])
        ));
    }

    // construct the final form element
    return processTpl('Common/formElement.html', array(
        '${id}'                                  => 'formElt_' . $propName,
        '${classAddon}'                          => (isset($errorFields[$propName]) ? ' formEltWithProblem' : ''),
        '${hidden_optional}'                     => ($hide ? ' style="display:none"' : ''),
        '${Common/formElementLabel_choice}'      => $label,
        '${prefix}'                              => ($inputFieldGroupPrefix ? escape($inputFieldGroupPrefix) . '&nbsp;' : ''),
        '${Common/formElementInputField_choice}' => $inputField,
        '${suffix}'                              => ($inputFieldGroupSuffixHtml ? '&nbsp;' . $inputFieldGroupSuffixHtml : ''),
        '${Common/formElementHelp_optional}'     => $help,
        '${Common/formElementError_optional}'    => $errorMsg
    ));
}

?>
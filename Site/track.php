<?php

include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/AudioTrackUserVisibility.php');
include_once('../Includes/DB/AudioTrackFile.php');
include_once('../Includes/DB/AudioTrackAttribute.php');
include_once('../Includes/DB/AudioTrackAudioTrackAttribute.php');
include_once('../Uploader/SolmetraUploader.php');

$user = User::new_from_cookie();
ensureUserIsLoggedIn($user);

$track = null;
$unpersistedTrack = null;
$message = null;
$problemOccured = false;
$errorFields = Array();

$trackId = get_numeric_param('tid'); // this is only set in an update scenario

$uploaderInstance = get_param('solmetraUploaderInstance');
$uploaderParams = null;
if ($uploaderInstance) {
    $logger->debug('$uploaderInstance: ' . $uploaderInstance);
    $uploaderParams = $_POST['solmetraUploaderData'][$uploaderInstance];
}
$logger->debug('$uploaderParams: ' . $uploaderParams);

if (get_param('action') == 'create') {
    $track = new AudioTrack();
    $track->user_id                 = $user->id;
    $track->title                     = 'New audio track';
    $track->type                      = 'original';
    $track->is_full_song              = false;
    $track->originating_user_id     = null;
    $track->parent_track_id           = null;
    $track->price                     = 0;
    $track->currency                  = 'USD'; // TODO - take from config - check other occurences as well
    $track->genres                    = '';
    $track->visibility                = 'public';
    $track->playback_count            = 0;
    $track->download_count            = 0;
    $track->originator_notified       = 0;
    $track->status                    = 'newborn';
    $track->sorting                   = 0;
    $track->rating_count              = 0;
    $track->rating_value              = 0;
    $track->preview_mp3_filename      = '';
    $track->orig_preview_mp3_filename = '';
    $track->save();

    // create a visibility record for this user
    $atav = new AudioTrackUserVisibility();
    $atav->user_id = $user->id;
    $atav->track_id  = $track->id;
    $atav->save();

} else if (get_param('action') == 'edit') {
    if (!$trackId) {
        show_fatal_error_and_exit('cannot save without a track id!');
    }

    $track = AudioTrack::fetch_for_id($trackId);
    ensureTrackBelongsToUserId($track, $user->id);

} else if (get_param('action') == 'save') {
    $logger->info('attempting to save track data ...');
    if (!$trackId) {
        show_fatal_error_and_exit('cannot save without a track id!');
    }

    $track = AudioTrack::fetch_for_id($trackId);
    ensureTrackBelongsToUserId($track, $user->id);

    if (inputDataOk($errorFields, $track, $uploaderParams)) {
        processParams($track, $user, $uploaderParams);

        if ($track->status == 'newborn') {
            $track->status = 'active';
        }

        $track->save();

        // if the track is private, make sure that the owner can see it
//        if ($track->visibility == 'private') {
//            $atav = AudioTrackUserVisibility::fetch_for_user_id_track_id($user->id, $track->id);
//            if (!$atav) {
//                $atav = new AudioTrackUserVisibility();
//                $atav->user_id = $user->id;
//                $atav->track_id = $track->id;
//                $atav->save();
//            }
//        }

        // if the track is a full song, the associated tracks inherit the visibility setting
        if ($track->is_full_song) {
            $logger->info('copying audio track user visibility associations from the parent song to it\'s children');
            $logger->debug('fetching child tracks');
            $children = AudioTrack::fetchAllChildTracksOfFullSong($track->id, true, true, $user->id);
            foreach ($children as $childTrack) {
                $logger->debug('processing child track ' . $childTrack->id);
                if ($childTrack->visibility != $track->visibility) {
                    $logger->debug('visibility has changed, copying it');
                    // copy the information if it's private or public
                    $childTrack->visibility = $track->visibility;
                    $childTrack->save();

                    // clean up the old associations of the child track
                    $logger->debug('cleaning up the child\' old associations');
                    AudioTrackUserVisibility::delete_all_with_track_id($childTrack->id);

                    // copy the information who may see the private track
                    if ($track->visibility == 'private') {
                        $logger->debug('visibility has changed to private');
                        // get the parent's associations to copy them
                        $logger->debug('fetching parent\'s track/user visibility associations');
                        $parentAssoc = AudioTrackUserVisibility::fetch_all_for_track_id($track->id);
                        foreach ($parentAssoc as $assoc) {
                            $logger->debug('creating new association for user/track ' . $assoc->user_id . '/' . $childTrack->id);
                            $atav = new AudioTrackUserVisibility();
                            $atav->user_id = $assoc->user_id;
                            $atav->track_id  = $childTrack->id;
                            $atav->save();
                        }
                    }

                } else {
                    $logger->debug('visibility hasn\'t changed');
                }
            }
        }

        $message = 'Successfully saved track data.';

        $mp3Count = AudioTrackFile::count_all_hqmp3_files_for_track_id($track->id, false);
        if ($mp3Count > 0) {
            $track->status = 'active';
            $track->save();

        } else {
            $track->status = 'inactive';
            $track->save();
            $message .= ' WARNING: No MP3 track file was uploaded! The widget can only play the track when an MP3 file was uploaded. Please make sure it is of high quality, at least 128kbps at 44.1kHz';
        }

    } else {
        $logger->info('input data was invalid: ' . print_r($errorFields, true));
        $unpersistedTrack = new AudioTrack();
        $unpersistedTrack->id = $trackId;
        processParams($unpersistedTrack, $user, $uploaderParams);
        $message = 'Please correct the highlighted problems!';
        $problemOccured = true;
    }

} else if (get_param('action') == 'delete') {
    if (!$trackId) {
        show_fatal_error_and_exit('cannot delete without a track id!');
    }

    $track = AudioTrack::fetch_for_id($trackId);
    ensureTrackBelongsToUserId($track, $user->id);

    AudioTrack::delete_with_id($trackId);
    AudioTrackAudioTrackAttribute::deleteForTrackId($trackId);

    header('Location: trackList.php');
    exit;

} else if (get_param('action') == 'toggleTrackState') { // not used currently - tracks are always active as long as at least the mp3 version was uploaded, otherwise they are inactive until this is done
    $logger->info('changing track state ...');
    if (!$trackId) {
        show_fatal_error_and_exit('cannot modify track state without a track id!');
    }

    $msg = '';

    $track = AudioTrack::fetch_for_id($trackId);
    ensureTrackBelongsToUserId($track, $user->id);

    if ($track->status == 'active') {
        $track->status = 'inactive';

    } else {
        $mp3Count = AudioTrackFile::count_all_hqmp3_files_for_track_id($track->id, false);
        if ($mp3Count > 0) {
            $track->status = 'active';
        } else {
            $msg = 'The track status cannot be set to \'Active\' because no MP3 track file was uploaded! The widget can only play the track when an MP3 file was uploaded. Please make sure it is of high quality, at least 128kbps at 44.1kHz';
        }
    }

    $track->save();

    header('Location: trackList.php?msg=' . urlencode($msg));
    exit;

} else if (get_param('action') == 'toggleFileState') { // not used currently - track files are always active
    $logger->info('changing track file state ...');
    if (!$trackId) {
        show_fatal_error_and_exit('cannot modify file state without a track id!');
    }

    $track = AudioTrack::fetch_for_id($trackId);
    ensureTrackBelongsToUserId($track, $user->id);

    $file = AudioTrackFile::fetch_for_id(get_numeric_param('fid'));
    if (!$file) {
        show_fatal_error_and_exit('track file not found!');
    }

    if ($file->status == 'active') $file->status = 'inactive';
    else $file->status = 'active';

    $file->save();

} else if (get_param('action') == 'deleteTrackFile') {
    $logger->info('deleting track file ...');
    if (!$trackId) {
        show_fatal_error_and_exit('cannot delete track file without a track id!');
    }

    $track = AudioTrack::fetch_for_id($trackId);
    ensureTrackBelongsToUserId($track, $user->id);

    $file = AudioTrackFile::fetch_for_id(get_numeric_param('fid'));
    if (!$file) {
        show_fatal_error_and_exit('track file not found!');
    }

    AudioTrackFile::delete_with_id(get_numeric_param('fid'));

    $mp3Count = AudioTrackFile::count_all_hqmp3_files_for_track_id($track->id, false);
    if ($mp3Count > 0) {
        $track->status = 'active';
        $track->save();

    } else {
        $track->status = 'inactive';
        $track->save();
        $message .= ' WARNING: No MP3 track file was uploaded! The widget can only play the track when an MP3 file was uploaded. Please make sure it is of high quality, at least 128kbps at 44.1kHz';
    }
}

function inputDataOk(&$errorFields, &$track, $uploaderParams) {
    global $logger;

    $result = true;

    if (strlen(get_param('title')) < 1) {
        $errorFields['title'] = 'Title is missing!';
        $result = false;
    }

//    if (isset($_FILES['preview_mp3_filename']['name']) && $_FILES['preview_mp3_filename']['name']) {
//        if (!preg_match('/mp3$/i', $_FILES['preview_mp3_filename']['name'])) {
//            $errorFields['preview_mp3_filename'] = 'File must be in MP3 format!';
//            $result = false;
//        }
//
//    } else { // no upload was started // TODO - show name of current file next to upload field
//        if (!$track->preview_mp3_filename) {
//            $errorFields['preview_mp3_filename'] = 'Preview MP3 file is missing!';
//            $result = false;
//        }
//    }

    if (get_param('type') != 'original' && get_param('type') != 'remix') { // should never happen unless the user is trying to manipulate the form submission
        $errorFields['type'] = 'Track type is invalid!';
        $result = false;
    }

    if (get_param('type') == 'remix') {
        if (!get_numeric_param('originating_user_id')) {
            $errorFields['originating_user_id'] = 'Originating user is missing!';
            $result = false;

        } else {
            $checkUser = User::fetch_for_id(get_numeric_param('originating_user_id'));
            if (!$checkUser) {
                $errorFields['originating_user_id'] = 'Originating user is invalid!';
                $result = false;
            }
        }
    }

    if (!get_numeric_param('is_full_song')) {
        if (get_numeric_param('parent_track_id')) {
            $parentTrack = AudioTrack::fetch_for_id(get_numeric_param('parent_track_id'));
            if (!$parentTrack) {
                $errorFields['parent_track_id'] = 'Song is invalid!';
                $result = false;
            }
        }
    }

    if ($price = get_numeric_param('price')) {
        if ($price < 0) {
            $errorFields['price'] = 'Price is invalid!';
            $result = false;
        }
    }

    if (get_param('currency') != 'USD') { // should never happen unless the user is trying to manipulate the form submission
        $errorFields['currency'] = 'Currency is invalid!';
        $result = false;
    }

    if (strlen(get_param('genres')) < 1) {
        $errorFields['genres'] = 'Genre is missing!';
        $result = false;
    }

    // FIXME - check genres for validity

    if (strlen(get_param('visibility')) < 1) {
        $errorFields['visibility'] = 'Visibility is missing!';
        $result = false;

    } else if (get_param('visibility') != 'private' && get_param('visibility') != 'public') {
        $errorFields['visibility'] = 'Visibility is invalid!';
        $result = false;
    }

    // newfilename/newfiletype
    //if (isset($_FILES['newfilename']['name']) && $_FILES['newfilename']['name']) {
    if ($uploaderParams && $uploaderParams != 'newfilename') { // if there's more than just the string 'newfilename' then a file upload has happened
        if (!get_param('newfiletype')) {
            $errorFields['newfiletype'] = 'Please choose a file type!';
            $result = false;
        }

        // FIXME - check if file extension matches file type?
    }

    return $result;
}

function processParams(&$track, &$user, $uploaderParams) {
    global $logger;

    $track->user_id               = $user->id;
    $track->title                   = get_param('title');
    $track->type                    = get_param('type');
    $track->is_full_song            = get_numeric_param('is_full_song');
    $track->originating_user_id   = get_numeric_param('originating_user_id');

    if (!$track->is_full_song) {
        $track->parent_track_id     = get_numeric_param('parent_track_id');
    }

    $track->price                   = get_numeric_param('price');
    $track->currency                = get_param('currency');
    $track->genres                  = get_param('genres');
    $track->visibility              = get_param('visibility');
    //$track->status                  = 'active';
    //$track->sorting                 = get_numeric_param('sorting');
    //$track->rating_count            = 0; // read-only field on this page
    //$track->rating_value            = 0; // read-only field on this page
    $track->additionalInfo          = get_param('additionalInfo');

    handleNewFileUpload($track, $user, $uploaderParams);

    if ($track->type == 'remix' && $track->originating_user_id && !$track->originator_notified) {
        $logger->info('new remix detected, sending notification mail to originator');

        $originator = User::fetch_for_id($track->originating_user_id);
        if ($originator) {
            // send notification mail to originator
            $email_sent = send_email($originator->email_address, $user->name . ' has created a remix using one of your tracks',
                    'Hey ' . $originator->name . ',' . "\n\n" .
                    $user->name . ' has just created a new remix using one of your tracks.' . "\n\n" .
                    'You may want to check out the "Remixed by others" section in your Notethrower Widget or on your public user page: ' .
                    $GLOBALS['BASE_URL'] . 'Site/userInfo.php?aid=' . $track->originating_user_id . "\n\n" .
                    'Please note that you might not see the new track until the remixer puts it online.');

            if (!$email_sent) {
                $logger->error('Failed to send "new remix" notification email to originator!');

            } else {
                $logger->info('marking track as "originator was modified"');
                $track->originator_notified = true;
            }

        } else {
            $logger->error('no originator found for user id: ' . $track->originating_user_id);
        }
    }

    //handle track attributes
    $containsAttributeIds = get_array_param('containsAttributIds');
    $needsAttributeIds = get_array_param('needsAttributIds');

    if (!is_null($track->id)) {
        AudioTrackAudioTrackAttribute::deleteForTrackId($track->id);
        AudioTrackAudioTrackAttribute::addAll($containsAttributeIds, $track->id, 'contains');
        AudioTrackAudioTrackAttribute::addAll($needsAttributeIds, $track->id, 'needs');
        $track->containsOthers = get_param('containsOthers');
        $track->needsOthers = get_param('needsOthers');
    }
}

function handleNewFileUpload(&$track, &$user, $uploaderParams) {
    global $logger;

//    // handle preview mp3 upload
//    if (isset($_FILES['preview_mp3_filename']['name']) && $_FILES['preview_mp3_filename']['name']) {
//        $logger->info('processing preview mp3 file upload: ' . $_FILES['preview_mp3_filename']['name']);
//
//        $track->orig_preview_mp3_filename = $_FILES['preview_mp3_filename']['name'];
//
//        $userSubdir = null;
//        if (ini_get('safe_mode')) {
//            $userSubdir = ''; // in safe mode we're not allowed to create directories
//        } else {
//            $userSubdir = md5('Wuizi' . $user->id);
//        }
//        $upload_dir = $GLOBALS['CONTENT_BASE_PATH'] . $userSubdir;
//
//        // upload to tmp file
//        $upload_filename = $track->id . '_preview.mp3';
//        do_upload($upload_dir, 'preview_mp3_filename', $upload_filename);
//
//        $track->preview_mp3_filename = $userSubdir . '/' . $upload_filename;
//    }

    // handle new file upload
    //if (isset($_FILES['newfilename']['name']) && $_FILES['newfilename']['name']) {
    $logger->debug('$uploaderParams: ' . $uploaderParams);
    if ($uploaderParams != 'newfilename') { // if there's more than just the string 'newfilename' then an upload has happened
        $uploaderInfo = explode('|', $uploaderParams);
        $fieldName    = $uploaderInfo[0];
        $filename     = $uploaderInfo[1];
        $origFilename = $uploaderInfo[2];
        //$logger->info('processing new track file upload: ' . $_FILES['newfilename']['name']);
        $logger->info('processing new track file upload: ' . $filename . ' (orig filename: ' . $origFilename . ')');

        $userSubdir = null;
        if (ini_get('safe_mode')) {
            $userSubdir = ''; // in safe mode we're not allowed to create directories
        } else {
            $userSubdir = md5('Wuizi' . $user->id);
        }
        $upload_dir = $GLOBALS['CONTENT_BASE_PATH'] . $userSubdir;

        $newFileType = get_param('newfiletype');

        // upload to tmp file
        $upload_filename = $track->id . '_' . $newFileType . '.mp3';
        //do_upload($upload_dir, 'newfilename', $upload_filename);
        do_solmetra_upload($GLOBALS['TMP_UPLOAD_PATH'] . $filename, $upload_dir, $upload_filename, true);

        $newTrackFile = AudioTrackFile::fetch_for_track_id_and_type($track->id, $newFileType);
        if (!$newTrackFile) {
            $newTrackFile = new AudioTrackFile();
        }

        $newTrackFile->track_id = $track->id;
        $newTrackFile->filename = $userSubdir . '/' . $upload_filename;
        //$newTrackFile->orig_filename = $_FILES['newfilename']['name'];
        $newTrackFile->orig_filename = $origFilename;
        $newTrackFile->type = $newFileType;
        $newTrackFile->status = 'active';
        $newTrackFile->save();
    }
}

$solmetraUploader = new SolmetraUploader(
    '../Uploader/',
    'upload.php',
    '../Uploader/config.php'
);

writePageDoctype();

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php writePageMetaTags(); ?>
    <title><?php writePageTitle(); ?></title>
    <link rel="stylesheet" href="../Styles/main.css" type="text/css">

    <script type="text/javascript" src="../Javascripts/jquery-1.3.2.min.js"></script>
    <script type="text/javascript" src="../Javascripts/jquery.main.js"></script>

    <script type="text/javascript" src="../Javascripts/Snippets.js"></script>
    <script type="text/javascript" src="../Uploader/SolmetraUploader.js"></script>
    <script type="text/javascript">

function typeHasChanged(elt) {
    if (document.getElementById('originating_user_id_row')) {
        var trackType = elt.options[elt.selectedIndex].value;
        if (trackType == 'remix') {
            document.getElementById('originating_user_id_row').style.display = '';
        } else {
            document.getElementById('originating_user_id_row').style.display = 'none';
        }
    }
}

function trackVsSongHasChanged(elt) {
    if (document.getElementById('parent_track_id_row')) {
        var sel = elt.options[elt.selectedIndex].value;
        if (sel == 0) {
            document.getElementById('parent_track_id_row').style.display = '';
        } else {
            document.getElementById('parent_track_id_row').style.display = 'none';
        }
    }
}

function visibilityHasChanged(elt) {
    if (document.getElementById('associated_users_row')) {
        var sel = elt.options[elt.selectedIndex].value;
        if (sel == 'private') {
            document.getElementById('associated_users_row').style.display = '';
        } else {
            document.getElementById('associated_users_row').style.display = 'none';
        }
    }
}

function newFileTypeHasChanged(elt) {
    if (document.getElementById('uploader_row')) {
        var sel = elt.options[elt.selectedIndex].value;
        if (sel != '') {
            document.getElementById('uploader_row').style.display = '';
        } else {
            document.getElementById('uploader_row').style.display = 'none';
        }
    }
}

function showSelectFriendsPopup() {
    window.open('selectFriends.php?tid=<?php echo $track->id; ?>', 'NT_SELECT_FRIENDS', 'scrollbars=yes,resizable=yes,status=0,width=400,height=600');
}

    </script>
  </head>
  <body>
    <div id="bodyWrapper">

        <? include ("pageHeader.php"); ?>
        <? include ("mainMenu.php"); ?>

    <div id="pageMainContent">


        <div class="horizontalMenu">
            <ul>
                <li><a href="index.php">Startpage</a></li>
                <li><a href="trackList.php">Track List</a></li>
            </ul>
        </div>

            <div id="trackFormDivStart"></div>
            <div id="trackFormDiv"><div id="container">

        <br/>
        <h1>Track information:</h1><br>

        <div id="trackFormPlusImage">

          <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="tid" value="<?php echo $track->id; ?>">
            <table class="trackFormTable">
              <tr>
                <td colspan="3" align="right">
<?php

if ($message) {
    if ($problemOccured) {
        echo '<span class="problemMessage">' . $message . '</span>';
    } else {
        echo '<span class="noticeMessage">'  . $message . '</span>';
    }

    echo '<tr><td colspan="3">&nbsp;</td></tr>' . "\n";
}

$userSelectionArray = array();
$userSelectionArray[0] = '- Please choose -';
$users = User::fetch_all_from_to(0, 999999999, false, false);
foreach ($users as $a) {
    if ($a->name != $user->name) {
        $userSelectionArray[$a->id] = $a->name;
    }
}

$songSelectionArray = array();
$songSelectionArray[0] = '- Please choose -';
$songs = AudioTrack::fetchAllFullSongsOfUser($user->id);
foreach ($songs as $s) {
    if ($s->id != $track->id) {
        $songSelectionArray[$s->id] = $s->title;
    }
}

// handle track attributes
$containsTrackAttributes = AudioTrackAttribute::fetchShownFor('contains');
$needsTrackAttributes = AudioTrackAttribute::fetchShownFor('needs');
$trackContainsAttributeIds = AudioTrackAudioTrackAttribute::fetchAttributeIdsForTrackIdAndState($track->id, 'contains');
$trackNeedsAttributeIds = AudioTrackAudioTrackAttribute::fetchAttributeIdsForTrackIdAndState($track->id, 'needs');

showFormField('Track title',                  'text',   'title',                 '', true,  255, $track, $unpersistedTrack, $problemOccured, $errorFields, null, null);
showTrackVsSongField('Audio track or full song', 'select', 'is_full_song',       '<b>Please SELECT FULL SONG if:</b> You are uploading a mixed down Mp3 version of your track, or a mixed down Mp3 version of another member\'s track with your musical contributions included.<br><p><br><b>Please SELECT AUDIO TRACK if:</b> You are uploading separate stems or bounced files of individual tracks in a song. For example, just a guitar or vocal track that you want to associate with a song you already uploaded.</br></p>', true,  0,   $track, $unpersistedTrack, $problemOccured, $errorFields, array('0' => 'Audio track', '1' => 'Full song'), null);

$hidden = true;
if ($errorFields['is_full_song'] || ($track && !$track->is_full_song) || ($unpersistedTrack && !$unpersistedTrack->is_full_song)) $hidden = false;
showFormField('Associated with song',         'select', 'parent_track_id',       'Here you can define which song your audio track stem should be associated with. Attach individual bounced track stems like you guitar track, drum track, vocals, etc., to the song they belong to. They will show up as an optional download for artists who want more creative control over remixing and contributing to your music.<br><br><p><b>NOTE:</b> You may also upload all of your track stems as bounced individual audio files in .wav or high quality .mp3\'s in a .zip file format. Midi files can also be included. You can let user\'s know what is in your .zip file by writing the contents in the provided additional track information text box below.</br></p>', false, 0,   $track, $unpersistedTrack, $problemOccured, $errorFields, $songSelectionArray, null, $hidden);

showTypeField('Type',                         'select', 'type',                  '<b>Please SELECT ORIGINAL if:</b> You are uploading a track that you have full copyright ownership of or have cleared the rights to upload.<br><p><br><b>Please SELECT REMIX if:</b> You have made a musical contribution to another Notethrower Artist\'s track, and are uploading the full result of your contributions mixed in with their original track</br></p> <p><br><b>NOTE:</b> You may not upload a remix of a remix. Under Notethrower\'s terms and conditions, you may only make musical contributions to another artist\'s ORIGINAL track, and not a track that has already been adapted by another Notethrower artist. However; you can make multiple versions of the same track by another artist.  This is done so both the original artist and the remixer split the profits from the sale of their collaboration 50/50. Make sense? Great!', true,  0,   $track, $unpersistedTrack, $problemOccured, $errorFields, array('original' => 'Original', 'remix' => 'Remix'), null);

$hidden = true;
if ($errorFields['originating_user_id'] || ($track && $track->type == 'remix') || ($unpersistedTrack && $unpersistedTrack->type == 'remix')) $hidden = false;
showFormField('Originating artist',           'select', 'originating_user_id', '', false, 0,   $track, $unpersistedTrack, $problemOccured, $errorFields, $userSelectionArray, null, $hidden);

showFormField('Price for commercial license', 'text',   'price',                 'Please enter the price you want others to pay to license your work.  Notethrower will take a 10% fee from the sale at this price.  If you are uploading a remix of another Notethrower artist\'s track, you will split the profit with that artist 50/50, minus the 10% fee.', false, 255, $track, $unpersistedTrack, $problemOccured, $errorFields, null, null);
showFormField('Currency',                     'select', 'currency',              '', false, 0,   $track, $unpersistedTrack, $problemOccured, $errorFields, array('USD' => 'USD'), null);
showFormField('Genre',                        'select', 'genres',                '', true,  0,   $track, $unpersistedTrack, $problemOccured, $errorFields, array_merge(array('' => '- Please choose -'), $GLOBALS['GENRES']), null);
showVisibilityField('Visibility',             'select', 'visibility',            'If you only want certain Notethrower artist\'s to have access to your track choose PRIVATE.  If you want to make music with the world, choose; you guessed it, PUBLIC.  Your choice. You can change this at any time.', true,  0,   $track, $unpersistedTrack, $problemOccured, $errorFields, array('public' => 'Public', 'private' => 'Private'), null);

$hidden = true;
if ($errorFields['visibility'] || ($track && $track->visibility == 'private') || ($unpersistedTrack && $unpersistedTrack->visibility == 'private')) $hidden = false;
echo '<tr id="associated_users_row"' . ($hidden ? ' style="display:none";' : '') . '>' . "\n";
//echo '<td>Artists who have access to this track:</td>' . "\n";
echo '<td>&nbsp;</td>' . "\n";

//$usersWithAccessListStr = '';
//$usersWithAccessList = AudioTrackUserVisibility::fetch_all_for_track_id($track->id);
//$ac = count($usersWithAccessList);
//if ($ac > 20) {
//    for ($ai = 0; $ai < 20; $ai++) {
//        if ($usersWithAccessList[$ai]->user_id != $user->id) {
//            $usersWithAccessListStr .= '<a href="userInfo.php?aid=' . $usersWithAccessList[$ai]->user_id . '" target="_blank">' .
//                    escape($usersWithAccessList[$ai]->user_name) . '</a>, ';
//        }
//    }
//    $usersWithAccessListStr .= 'and some more ...';
//
//} else if ($ac > 1) {
//    foreach ($usersWithAccessList as $a) {
//        if ($a->user_id != $user->id) {
//            $usersWithAccessListStr .= '<a href="userInfo.php?aid=' . $a->user_id . '" target="_blank">' .
//                    escape($a->user_name) . '</a>, ';
//        }
//    }
//    $usersWithAccessListStr = substr($usersWithAccessListStr, 0, -2);
//
//} else {
//    $usersWithAccessListStr = '(none)';
//}
//echo '<td>' . $usersWithAccessListStr . '<br><a href="javascript:showSelectFriendsPopup();">Select artists</a></td>' . "\n";
echo '<td><a href="javascript:showSelectFriendsPopup();">Select the artists you want to have access to this track</a></td>' . "\n";
echo '<td>&nbsp;</td>' . "\n";
echo '</tr>' . "\n";

showAttributesList('This track contains', 'containsAttributIds[]', $containsTrackAttributes, $trackContainsAttributeIds, $track->containsOthers, 'containsOthers', 'Please check any box that applies to your track.  This helps artists, fans, and music supervisors find what they are looking for faster.');
showAttributesList('This track needs', 'needsAttributIds[]', $needsTrackAttributes, $trackNeedsAttributeIds, $track->needsOthers, 'needsOthers', 'You can sing like Pavarotti, but can\'t play a lick of guitar.  No problem!  Let others know what you would like added to your track, and hear how your new song develops.');

//showFormField('Preview MP3 file',             'file',   'preview_mp3_filename',  '', true,  255, $track, $unpersistedTrack, $problemOccured, $errorFields, null, $track->orig_preview_mp3_filename);
//showFormField('Sort index',                   'text',   'sorting',               '', false, 3,   $track, $unpersistedTrack, $problemOccured, $errorFields, null, null);

showFormField('Additional info',              'textarea', 'additionalInfo',      'IMPORTANT: Please include any notes about the track/song you want to add.  If you upload a .zip file of the bounced stems, please specify here what tracks are included. For example, if you included midi files, bass track, vocals, etc. You may also want to include info. on how you recorded it, what equipment, software was used, or any other important information.', false, 0,   $track, $unpersistedTrack, $problemOccured, $errorFields, null, null);

?>
              <tr>
                <td colspan="3">&nbsp;</td>
              </tr>
              <tr>
                <td colspan="3"><br><h1>Track files:</h1><br><br></td>
              </tr>
              <tr>
                <td colspan="3">
<?php

$existingFiles = AudioTrackFile::fetch_all_for_track_id($track->id, true);
echo '<table class="trackFilesTable">' . "\n";
echo '<tr>' . "\n";
echo '<td class="tableHeading"><b>Filename</b></td>' .
     '<td class="tableHeading"><b>Type</b></td>' .
     //'<td class="tableHeading"><b>Status</b></td>' .
     '<td class="tableHeading"><b>Action</b></td>' . "\n";
echo '</tr>' . "\n";

$i = 0;
foreach ($existingFiles as $file) {
    $i++;
    echo '<tr class="standardRow' . ($i % 2 + 1) . '" onmouseover="this.className=\'highlightedRow\';" onmouseout="this.className=\'standardRow' . ($i % 2 + 1) . '\';">' . "\n";
    echo '<td><b>' . escape($file->orig_filename) . '</b></td>' .
         '<td>' . $file->type . '</td>' .
         //'<td><a href="track.php?tid=' . $track->id . '&action=toggleFileState&fid=' . $file->id . '">' . ($file->status == 'active' ? 'Active' : 'Inactive') . '</a></td>' .
         '<td><a href="javascript:askForConfirmationAndRedirect(\'Do you really want to delete this track file?\', \'' .
            escape_and_rewrite_single_quotes($file->orig_filename) . '\', \'track.php?tid=' . $track->id . '&action=deleteTrackFile&fid=' . $file->id . '\');">Delete</a></td>' . "\n";
    echo '</tr>' . "\n";
}

if (count($existingFiles) == 0) {
    echo '<tr>' . "\n";
    echo '<td>No files uploaded yet. You need to upload at least an MP3 version of your track.</td>' . "\n";
    echo '</tr>' . "\n";
}

echo '</table>' . "\n";

?>              </td>
              </tr>
              <tr>
                <td colspan="3">&nbsp;</td>
              </tr>
              <tr>
                <td colspan="3"><br><h1>Upload new track file:</h1><br><br></td>
              </tr>
<?php

showNewFileTypeField('Type', 'select', 'newfiletype', '', false, 0, $track, $unpersistedTrack, $problemOccured, $errorFields, array('' => '- Please choose -', 'HQMP3' => '.mp3 (128kbps at 44.1kHz or better)', 'WAV' => '.wav', 'ZIP' => '.zip'), null);
//showFormField('File', 'file',   'newfilename', '', false, 0, $track, $unpersistedTrack, $problemOccured, $errorFields, null, null);

echo '<tr id="uploader_row" style="display:none" class="standardRow1" onmouseover="this.className=\'highlightedRow\';" onmouseout="this.className=\'standardRow1\';">' .
     '<td>File:</td>' .
     '<td>' . $solmetraUploader->getInstance('newfilename') . '</td>' .
     '<td>Please upload at least a high quality 128kbps at 44.1kHz Mp3 file. You may then add entire bounced stems from your recording sessions of uncompressed .wav or quality .mp3 files included in a .zip file.<strong>Click save to start the upload process.</strong>Please be patient when uploading large files.</td>' .
     '</tr>';

?>
              <tr>
                <td colspan="3">&nbsp;</td>
              </tr>
              <tr>
                <td colspan="3" align="right">
<?php

if ($message) {
    if ($problemOccured) {
        echo '<span class="problemMessage">' . $message . '</span>';
    } else {
        echo '<span class="noticeMessage">'  . $message . '</span>';
    }
    echo '&nbsp;';
}

?>
                <input class="submitButton buttonSave" type="submit" value="">

                </td>
              </tr>
            </table>
          </form>

        </div>

        <br/>
        <br/>

      </div></div>
      <div id="trackFormDivEnd"></div>


    </div> <!-- pageMainContent -->

    <? include ("footer.php"); ?>

    </div> <!-- bodyWrapper -->

    <?php writeGoogleAnalyticsStuff(); ?>
  </body>
</html>

<?php

function showTypeField($label, $inputType, $propName, $helpTextHtml, $mandatory, $maxlength, &$track, &$unpersistedTrack, $problemOccured, &$errorFields, $selectOptions, $currentUploadFilename) {
    global $logger;

    $classSuffix = isset($errorFields[$propName]) ? ' formFieldWithProblem' : '';

    echo '<tr class="standardRow1' . $classSuffix . '"' .
         ' onmouseover="this.className=\'highlightedRow' . $classSuffix . '\';" onmouseout="this.className=\'standardRow1' . $classSuffix . '\';"' .
         '>' . "\n";

    echo '<td class="formLeftCol">' . ($mandatory ? '<b>' : '') . $label . ':' . ($mandatory ? '</b>' : '') . '</td>' . "\n";

    echo '<td class="formMiddleCol ' . ($mandatory ? 'mandatoryFormField' : 'optionalFormField') . '">';

    $normalClass  = 'inputTextField';
    $problemClass = 'inputTextFieldWithProblem';
    $size = 40;

    $trackVal            = null;
    $unpersistedTrackVal = null;
    eval('if ($track) $trackVal = $track->' . $propName . ';');
    eval('if ($unpersistedTrack) $unpersistedTrackVal = $unpersistedTrack->' . $propName . ';');
    //$logger->debug($unpersistedTrackVal  . ' / ' . $trackVal);
    $selectedValue = $unpersistedTrack ? $unpersistedTrackVal : $trackVal;

    echo '<select onChange="typeHasChanged(this);" class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" name="' . $propName . '">' . "\n";
    foreach (array_keys($selectOptions) as $optVal) {
        //$logger->debug($selectedValue  . ' === ' . $optVal);
        $selected = ((string) $selectedValue === (string) $optVal) ? ' selected' : '';
        echo '<option value="' . $optVal . '"' . $selected . '>' . escape($selectOptions[$optVal]) . '</option>' . "\n";
    }
    echo '</select>' . "\n";

    echo '</td>' . "\n";

    echo '<td style="vertical-align:top">';
//    if (isset($errorFields[$propName])) {
//        echo '&nbsp;' . $errorFields[$propName];
//    } else {
        if ($helpTextHtml) {
            echo '<span style="font-size:11px">' . $helpTextHtml . '</span>';
        } else {
            echo '&nbsp;';
        }
//    }
    echo '</td>';

    if (isset($errorFields[$propName])) {
        echo '<tr class="formFieldWithProblem"><td colspan="3">';
        echo $errorFields[$propName];
        echo '<br><br></td></tr>';
    }

    echo '</tr>' . "\n";
}

function showTrackVsSongField($label, $inputType, $propName, $helpTextHtml, $mandatory, $maxlength, &$track, &$unpersistedTrack, $problemOccured, &$errorFields, $selectOptions, $currentUploadFilename) {
    global $logger;

    $classSuffix = isset($errorFields[$propName]) ? ' formFieldWithProblem' : '';

    echo '<tr class="standardRow1' . $classSuffix . '"' .
         ' onmouseover="this.className=\'highlightedRow' . $classSuffix . '\';" onmouseout="this.className=\'standardRow1' . $classSuffix . '\';"' .
         '>' . "\n";

    echo '<td class="formLeftCol">' . ($mandatory ? '<b>' : '') . $label . ':' . ($mandatory ? '</b>' : '') . '</td>' . "\n";

    echo '<td class="formMiddleCol ' . ($mandatory ? 'mandatoryFormField' : 'optionalFormField') . '">';

    $normalClass  = 'inputTextField';
    $problemClass = 'inputTextFieldWithProblem';
    $size = 40;

    $trackVal            = null;
    $unpersistedTrackVal = null;
    eval('if ($track) $trackVal = $track->' . $propName . ';');
    eval('if ($unpersistedTrack) $unpersistedTrackVal = $unpersistedTrack->' . $propName . ';');
    //$logger->debug($unpersistedTrackVal  . ' / ' . $trackVal);
    $selectedValue = $unpersistedTrack ? $unpersistedTrackVal : $trackVal;

    echo '<select onChange="trackVsSongHasChanged(this);" class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" name="' . $propName . '">' . "\n";
    foreach (array_keys($selectOptions) as $optVal) {
        //$logger->debug($selectedValue  . ' === ' . $optVal);
        $selected = ((string) $selectedValue === (string) $optVal) ? ' selected' : '';
        echo '<option value="' . $optVal . '"' . $selected . '>' . escape($selectOptions[$optVal]) . '</option>' . "\n";
    }
    echo '</select>' . "\n";

    echo '</td>' . "\n";

    echo '<td style="vertical-align:top">';
//    if (isset($errorFields[$propName])) {
//        echo '&nbsp;' . $errorFields[$propName];
//    } else {
        if ($helpTextHtml) {
            echo '<span style="font-size:11px">' . $helpTextHtml . '</span>';
        } else {
            echo '&nbsp;';
        }
//    }
    echo '</td>';

    if (isset($errorFields[$propName])) {
        echo '<tr class="formFieldWithProblem"><td colspan="3">';
        echo $errorFields[$propName];
        echo '<br><br></td></tr>';
    }

    echo '</tr>' . "\n";
}

function showVisibilityField($label, $inputType, $propName, $helpTextHtml, $mandatory, $maxlength, &$track, &$unpersistedTrack, $problemOccured, &$errorFields, $selectOptions, $currentUploadFilename) {
    global $logger;

    $classSuffix = isset($errorFields[$propName]) ? ' formFieldWithProblem' : '';

    echo '<tr class="standardRow1' . $classSuffix . '"' .
         ' onmouseover="this.className=\'highlightedRow' . $classSuffix . '\';" onmouseout="this.className=\'standardRow1' . $classSuffix . '\';"' .
         '>' . "\n";

    echo '<td class="formLeftCol">' . ($mandatory ? '<b>' : '') . $label . ':' . ($mandatory ? '</b>' : '') . '</td>' . "\n";

    echo '<td class="formMiddleCol ' . ($mandatory ? 'mandatoryFormField' : 'optionalFormField') . '">';

    $normalClass  = 'inputTextField';
    $problemClass = 'inputTextFieldWithProblem';
    $size = 40;

    $trackVal            = null;
    $unpersistedTrackVal = null;
    eval('if ($track) $trackVal = $track->' . $propName . ';');
    eval('if ($unpersistedTrack) $unpersistedTrackVal = $unpersistedTrack->' . $propName . ';');
    //$logger->debug($unpersistedTrackVal  . ' / ' . $trackVal);
    $selectedValue = $unpersistedTrack ? $unpersistedTrackVal : $trackVal;

    echo '<select onChange="visibilityHasChanged(this);" class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" name="' . $propName . '">' . "\n";
    foreach (array_keys($selectOptions) as $optVal) {
        //$logger->debug('#### ' . $selectedValue  . ' === ' . $optVal);
        $selected = ((string) $selectedValue === (string) $optVal) ? ' selected' : '';
        echo '<option value="' . $optVal . '"' . $selected . '>' . escape($selectOptions[$optVal]) . '</option>' . "\n";
    }
    echo '</select>' . "\n";

    echo '</td>' . "\n";

    echo '<td style="vertical-align:top">';
//    if (isset($errorFields[$propName])) {
//        echo '&nbsp;' . $errorFields[$propName];
//    } else {
        if ($helpTextHtml) {
            echo '<span style="font-size:11px">' . $helpTextHtml . '</span>';
        } else {
            echo '&nbsp;';
        }
//    }
    echo '</td>';

    if (isset($errorFields[$propName])) {
        echo '<tr class="formFieldWithProblem"><td colspan="3">';
        echo $errorFields[$propName];
        echo '<br><br></td></tr>';
    }

    echo '</tr>' . "\n";
}

function showAttributesList($label, $fieldName, &$trackAttributes, &$checkedTrackAttributeIds,  $othersValue, $othersFieldName, $helpTextHtml) {
    global $logger;

    echo '<tr class="standardRow1"' .
         ' onmouseover="this.className=\'highlightedRow\';" onmouseout="this.className=\'standardRow1\';"' .
         '>' . "\n";

    echo '<td class="formLeftCol" valign="top">' . $label . ':</td>' . "\n";

    echo '<td class="formMiddleCol optionalFormField">';

    echo '<table><tr>';
    for($i = 1; $i <= sizeof($trackAttributes); $i++) {
        echo '<td><input type="checkbox" name="' . $fieldName . '" value="' . $trackAttributes[$i-1]->id . '"';

        // checked or not?
        if (in_array($trackAttributes[$i-1]->id, $checkedTrackAttributeIds)) {
            echo ' checked="checked"';
        }
        echo '> ' . $trackAttributes[$i-1]->name . '</td>';

        // end of the row?
        if ((($i % 3) == 0) && ($i > 0)) {
            echo '</tr>';
        }
        // do we have to make a new row?
        if ((($i % 3) == 0) && ($i < sizeof($trackAttributes))) {
            echo '<tr>';
        }
    }

    // handle the case where we have to add emty cells to complete the last row
    if (((sizeof($trackAttributes)) % 3) != 0) {
        $rest = 3 - ((sizeof($trackAttributes)) % 3);
        for ($i = 0; $i < $rest; ++$i) {
            echo '<td>&nbsp;</td>';
        }
        echo '</tr>';
    }

    // display the "others" field in a own row, with only one td
    echo '<tr><td colspan="3">Others: ';
    echo '<input type="text" name="' . $othersFieldName . '" value="' . $othersValue . '"></td></tr>';

    echo '</table>';

    echo '</td>';

    // help text cell
    echo '<td style="vertical-align:top">';
    echo '<span style="font-size:11px">' . $helpTextHtml . '</span>';
    echo '</td>';

    echo '</tr>';
}

function showNewFileTypeField($label, $inputType, $propName, $helpTextHtml, $mandatory, $maxlength, &$track, &$unpersistedTrack, $problemOccured, &$errorFields, $selectOptions, $currentUploadFilename, $hidden = false) {
    global $logger;

    $hideStmt = '';
    if ($hidden) {
        $hideStmt = ' style="display:none";';
    }

    $classSuffix = isset($errorFields[$propName]) ? ' formFieldWithProblem' : '';

    echo '<tr id="' . $propName . '_row"' . $hideStmt . ' class="standardRow1' . $classSuffix . '"' .
         ' onmouseover="this.className=\'highlightedRow' . $classSuffix . '\';" onmouseout="this.className=\'standardRow1' . $classSuffix . '\';"' .
         '>' . "\n";

    echo '<td class="formLeftCol">' . ($mandatory ? '<b>' : '') . $label . ':' . ($mandatory ? '</b>' : '') . '</td>' . "\n";

    echo '<td class="formMiddleCol ' . ($mandatory ? 'mandatoryFormField' : 'optionalFormField') . '">';

    $normalClass  = 'inputTextField';
    $problemClass = 'inputTextFieldWithProblem';
    $size = 40;

    $trackVal            = null;
    $unpersistedTrackVal = null;
    eval('if ($track) $trackVal = $track->' . $propName . ';');
    eval('if ($unpersistedTrack) $unpersistedTrackVal = $unpersistedTrack->' . $propName . ';');
    //$logger->debug($unpersistedTrackVal  . ' / ' . $trackVal);
    $selectedValue = $unpersistedTrack ? $unpersistedTrackVal : $trackVal;

    echo '<select onChange="newFileTypeHasChanged(this);" class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" name="' . $propName . '">' . "\n";
    foreach (array_keys($selectOptions) as $optVal) {
        //$logger->debug($selectedValue  . ' === ' . $optVal);
        $selected = ((string) $selectedValue === (string) $optVal) ? ' selected' : '';
        echo '<option value="' . $optVal . '"' . $selected . '>' . escape($selectOptions[$optVal]) . '</option>' . "\n";
    }
    echo '</select>' . "\n";

    echo '</td>' . "\n";

    echo '<td style="vertical-align:top">';
//    if (isset($errorFields[$propName])) {
//        echo '&nbsp;' . $errorFields[$propName];
//    } else {
        if ($helpTextHtml) {
            echo '<span style="font-size:11px">' . $helpTextHtml . '</span>';
        } else {
            echo '&nbsp;';
        }
//    }
    echo '</td>';

    if (isset($errorFields[$propName])) {
        echo '<tr class="formFieldWithProblem"><td colspan="3">';
        echo $errorFields[$propName];
        echo '<br><br></td></tr>';
    }

    echo '</tr>' . "\n";
}

function showFormField($label, $inputType, $propName, $helpTextHtml, $mandatory, $maxlength, &$track, &$unpersistedTrack, $problemOccured, &$errorFields, $selectOptions, $currentUploadFilename, $hidden = false) {
    global $logger;

    $hideStmt = '';
    if ($hidden) {
        $hideStmt = ' style="display:none";';
    }

    $classSuffix = isset($errorFields[$propName]) ? ' formFieldWithProblem' : '';

    echo '<tr id="' . $propName . '_row"' . $hideStmt . ' class="standardRow1' . $classSuffix . '"' .
         ' onmouseover="this.className=\'highlightedRow' . $classSuffix . '\';" onmouseout="this.className=\'standardRow1' . $classSuffix . '\';"' .
         '>' . "\n";

    echo '<td class="formLeftCol">' . ($mandatory ? '<b>' : '') . $label . ':' . ($mandatory ? '</b>' : '') . '</td>' . "\n";

    echo '<td class="formMiddleCol ' . ($mandatory ? 'mandatoryFormField' : 'optionalFormField') . '">';

    $normalClass  = 'inputTextField';
    $problemClass = 'inputTextFieldWithProblem';
    $size = 40;
    if ($inputType == 'file') {
        $normalClass  = 'inputFileField';
        $problemClass = 'inputFileFieldWithProblem';
        $size = 16;
    }

    if ($inputType == 'textarea') {
        echo '<textarea class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" name="' . $propName . '" rows="6" cols="40">';
        $trackVal            = null;
        $unpersistedTrackVal = null;
        eval('if ($track) $trackVal = $track->' . $propName . ';');
        eval('if ($unpersistedTrack) $unpersistedTrackVal = $unpersistedTrack->' . $propName . ';');
        echo !$problemOccured ? escape($trackVal) : ($unpersistedTrack ? escape($unpersistedTrackVal) : '');
        echo '</textarea>' . "\n";

    } else if ($inputType == 'select') {
        $trackVal            = null;
        $unpersistedTrackVal = null;
        eval('if ($track) $trackVal = $track->' . $propName . ';');
        eval('if ($unpersistedTrack) $unpersistedTrackVal = $unpersistedTrack->' . $propName . ';');
        //$logger->debug($unpersistedTrackVal  . ' / ' . $trackVal);
        $selectedValue = $unpersistedTrack ? $unpersistedTrackVal : $trackVal;

        echo '<select class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" name="' . $propName . '">' . "\n";
        foreach (array_keys($selectOptions) as $optVal) {
            //$logger->debug($selectedValue  . ' === ' . $optVal);
            $selected = ((string) $selectedValue === (string) $optVal) ? ' selected' : '';
            echo '<option value="' . $optVal . '"' . $selected . '>' . escape($selectOptions[$optVal]) . '</option>' . "\n";
        }
        echo '</select>' . "\n";

    } else {
        echo '<input class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" type="' . $inputType . '" name="' . $propName . '" maxlength="' . $maxlength . '" size="' . $size . '"';

        if ($inputType != 'password' && $inputType != 'file') {
            echo ' value="';
            $trackVal            = null;
            $unpersistedTrackVal = null;
            eval('if ($track) $trackVal = $track->' . $propName . ';');
            eval('if ($unpersistedTrack) $unpersistedTrackVal = $unpersistedTrack->' . $propName . ';');
            $logger->debug($propName . ': persisted: ' . $trackVal . ' - unpersisted: ' . $unpersistedTrackVal . ' - track? ' . ($track ? 'yes' : 'no'));
            echo !$problemOccured ? escape($trackVal) : ($unpersistedTrack ? escape($unpersistedTrackVal) : '');
            echo '"';
        }
        echo '>';

        if ($inputType == 'file' && $currentUploadFilename) {
            echo '<br>Current file:&nbsp;<b>';
            echo escape($currentUploadFilename);
            echo '</b>';
        }
    }

    echo '</td>' . "\n";

    echo '<td style="vertical-align:top">';
//    if (isset($errorFields[$propName])) {
//        echo '&nbsp;' . $errorFields[$propName];
//    } else {
        if ($helpTextHtml) {
            echo '<span style="font-size:11px">' . $helpTextHtml . '</span>';
        } else {
            echo '&nbsp;';
        }
//    }
    echo '</td>';

    if (isset($errorFields[$propName])) {
        echo '<tr class="formFieldWithProblem"><td colspan="3">';
        echo $errorFields[$propName];
        echo '<br><br></td></tr>';
    }

    echo '</tr>' . "\n";
}

?>

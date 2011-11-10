<?php

set_time_limit(120); // 2 minutes hard limit

include_once('../Includes/Init.php');
include_once('../Includes/Config.php');

include_once('../Includes/DbConnect.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/TranscodingJob.php');

$startTime = time();

//$logger->set_debug_level();

// check if this script is currently running
$processingFile = '../Tmp/processing';

exitIfValidProcessingFileFound($processingFile);

$logger->debug('touching processing file');
touch($processingFile);
chmod($processingFile, 0666);

doTheWork($startTime);

$logger->debug('removing processing file');
unlink($processingFile);

// END

// functions
function doTheWork($startTime) {
    global $logger;

    // php script startet und schreibt ein processing file
    // dann macht es seine arbeit
    // entweder ists vor einer minute fertig, dann sleept es und sucht nach neuen jobs, bis die minute fertig ist
    // oder es läuft länger als eine minute, dann hörts nach dem gerade aktuellen render prozess auf
    // beim verlassen wird das processing file gelöscht

    // FIXME + optimieren: wenn ein worker etwas länger als eine minute arbeitet und dann aufhört, dann kann
    // eine wartezeit von fast einer ganzen minute entstehen.

    while (true) {
        if (time() - $startTime > 58) {
            $logger->debug('it\'s time to quit now so that cron can start this script again');
            return;
        }

        // get the next TranscodingJob record with status PENDING
        $pjob = fetchAndLockNextPendingJob();

        // process it
        if ($pjob) {
            transcode($pjob);
        }

        // sleep
        usleep(500000); // 0.5 seconds
    }
}


function transcode(&$pjob) {
    global $logger;

    $logger->info('transcoding file ' . $pjob->filename . ' (jobId = ' . $pjob->id . ')');

    try {
        $projectFile = ProjectFile::fetch_for_id($pjob->projectFileId);

        $sourceFile = $GLOBALS['CONTENT_BASE_PATH'] . $pjob->filename;
        $mp3Filename = getFilenameWithoutExt($pjob->filename) . '.mp3';
        $destFile = $GLOBALS['CONTENT_BASE_PATH'] . $mp3Filename;

        $options = $GLOBALS['TRANSCODER_OPTIONS'][$projectFile->type];
        $command = $GLOBALS['TRANSCODER_COMMAND'] . ' ' . $options . ' ' . $sourceFile . ' ' . $destFile;

        // execute the command
        $returnVar = 1;
        $output = array();
        $logger->info('executing command: ' . $command);
        $ret = exec($command, $output, $returnVar);

        if ($returnVar != 0) {
            throw new Exception('Failed to transcode file, command returned: ' . $ret);
        }

        // chmod the new created mp3 file
        $done = chmod($destFile, 0644);
        if (!$done) {
            throw new Exception('Failed chmod file ' . $destFile);
        }

        $logger->info('conversion done. cloning DB record ...');

        // clone the existing project file
        $newProjectFile = ProjectFile::fetch_for_id($pjob->projectFileId);
        // edge case, when the projectFile does not exist the fetch_for_id returns
        // and empty ProjectFile instance
        if ($newProjectFile->id == $pjob->projectFileId) {
            $newProjectFile->filename = $mp3Filename;
            $newProjectFile->orig_filename = getFilenameWithoutExt($newProjectFile->orig_filename) . '.mp3';
            $newProjectFile->autocreated_from = $newProjectFile->id;
            $newProjectFile->id = null; // remove id to produce a new insert
            $success = $newProjectFile->save();
            if (!success) {
                throw new Exception('Failed to create new ProjectFile db entry!');
            }
        } else {
            throw new Exception('No ProjectFile found (id: ' . $pjob->projectFileId . ')!');
        }

        $logger->info('all done.');

        $pjob->status = 'SUCCESS';
        $pjob->update();

    } catch (Exception $e) {
        $logger->error('Exception occured: ' . $e->getMessage());
        $pjob->info = $e->getMessage();
        $pjob->status = 'FAILED';
        $pjob->update();
    }
}

function fetchAndLockNextPendingJob() {
    global $logger;

    //$pjob = PageRenderingJob::fetchOldestPendingJob();
    $pjob = TranscodingJob::fetchRandomPendingJob();

    if ($pjob) {
        // attempt to lock the job by updating the status
        $pjob->status = 'PROCESSING';
        $resp = $pjob->updateStatusBasedOnOldStatus('PENDING');

        if ($resp['ok']) {
            if ($resp['affectedRows'] > 0) {
                return $pjob;
            } else {
                $logger->info('job could not be locked because another worker grabbed it before i could lock it -> trying again');
                return fetchAndLockNextPendingJob();
            }

        } else {
            $logger->error('failed to lock job!');
            return null;
        }

    } else {
        $logger->debug('no pending jobs found');
        return null;
    }
}

function exitIfValidProcessingFileFound($processingFile) {
    global $logger;

    if (file_exists($processingFile)) { // there's a file, the script seems to be running right now
        if (time() - filemtime($processingFile) < 60) { // we exit if the file is younger than a minute, otherwise we assume that the previous process died
            $logger->debug('valid processing file found, exit ...');
            exit;
        }
    }
}

function getFilenameWithoutExt($filename) {
    return dirname($filename) . $GLOBALS['PATH_SEPARATOR'] . pathinfo($filename, PATHINFO_FILENAME);
}

?>
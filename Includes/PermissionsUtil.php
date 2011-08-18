<?php

include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');

function ensureUserIsLoggedIn($user) {
    global $logger;

    if ($user) {
        $logger->info('user is logged in');

    } else {
        $logger->info('user is NOT logged in');
        header('Location: pleaseLogin.php');
        exit;
    }
}

function ensureProjectIdBelongsToUserId($projectId, $userId) {
    if (!$projectId) {
        show_fatal_error_and_exit('Project ID not specified!');
    }

    $project = Project::fetch_for_id($projectId);

    if (!$project || !$project->id) {
        show_fatal_error_and_exit('Project with ID ' . $projectId . ' not found!');
    }

    ensureProjectBelongsToUserId($project, $userId);
}

function ensureProjectBelongsToUserId(&$project, $userId) {
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('Project object not specified!');
    }

    if (!$userId) {
        show_fatal_error_and_exit('User ID not specified!');
    }

    if ($project->user_id != $userId) {
        show_fatal_error_and_exit('Project with ID ' . $project->id . ' does not belong to user with ID ' .
                $userId . ' (owner user ID: ' . $project->user_id . ')!');
    }
}

function ensureMessageIdBelongsToUser($mid, $user) {
    if (!$mid) {
        show_fatal_error_and_exit('Msg ID not specified!');
    }

    if (!$user || !$user->id) {
        show_fatal_error_and_exit('User not specified!');
    }

    $msg = Message::fetch_for_id($mid);

    if (!$msg || !$msg->id) {
        show_fatal_error_and_exit('Message with ID ' . $mid . ' not found!');
    }

    ensureMessageBelongsToUser($msg, $user);
}

function ensureMessageBelongsToUser($msg, $user) {
    if (!$msg || !$msg->id) {
        show_fatal_error_and_exit('Msg not specified!');
    }

    if (!$user || !$user->id) {
        show_fatal_error_and_exit('User not specified!');
    }

    if ($msg->recipient_user_id != $user->id) {
        show_fatal_error_and_exit('Msg with ID ' . $msg->id . ' does not belong to user with ID ' .
                $user->id . ' (owner user ID: ' . $msg->recipient_user_id . ')!');
    }
}

?>
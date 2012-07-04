<?php

include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectUserVisibility.php');

function ensureUserIsLoggedIn(&$user) {
    global $logger;
    if (!userIsLoggedIn($user)) {
        header('Location: /pleaseLogin');
        exit;
    }
}

function userIsLoggedIn(&$user) {
    global $logger;

    if ($user) {
        $logger->info('user is logged in');
        return true;

    } else {
        $logger->info('user is NOT logged in');
        return false;
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

function ensureProjectFileBelongsToProjectId(&$projectFile, $projectId) {
    if (!$projectFile || !$projectFile->id) {
        show_fatal_error_and_exit('ProjectFile object not specified!');
    }

    if (!$projectId) {
        show_fatal_error_and_exit('Project ID not specified!');
    }

    if ($projectFile->project_id != $projectId) {
        show_fatal_error_and_exit('ProjectFile with ID ' . $projectFile->id . ' does not belong to project with ID ' .
                $projectId . ' (project ID: ' . $projectFile->project_id . ')!');
    }
}

function ensureProjectIdIsAssociatedWithUserId($projectId, $userId) {
    if (!projectIdIsAssociatedWithUserId($projectId, $userId)) {
        show_fatal_error_and_exit('Project with ID ' . $projectId . ' cannot be access by user with ID ' . $userId);
    }
}

function projectIdIsAssociatedWithUserId($projectId, $userId) {
    if (!$projectId) {
        show_fatal_error_and_exit('Project ID not specified!');
    }

    if (!$userId) {
        show_fatal_error_and_exit('User ID not specified!');
    }

    $puv = ProjectUserVisibility::fetch_for_user_id_project_id($userId, $projectId);
    if (!$puv || !$puv->project_id) {
        return false;
    }

    return true;
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

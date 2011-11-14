<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_release_contributor table
// it stores which contributor (user) has partial ownership of a release (project_file)
class ReleaseContributor {
    var $project_file_id;
    var $user_id;

    // fields from referenced tables
    var $user_name;

    // constructors
    // ------------
    function ReleaseContributor() {
    }

    function fetchAllForProjectFileId($projectFileId) {
        $objs = array();

        $result = _mysql_query(
            'select rc.*, u.name as user_name ' .
            'from pp_release_contributor rc, pp_user u ' .
            'where rc.project_file_id = ' . n($projectFileId) . ' ' .
            'and rc.user_id = u.id'
        );

        while ($row = mysql_fetch_array($result)) {
            $rc = new ReleaseContributor();
            ReleaseContributor::_read_row($rc, $row);

            $objs[] = $rc;
        }

        mysql_free_result($result);

        return $objs;
    }

    function addAll($userIds, $projectFileId) {
        foreach ($userIds as $id) {
            if ($id) {
                $rc = new ReleaseContributor();
                $rc->project_file_id = $projectFileId;
                $rc->user_id         = $id;
                $rc->insert();
            }
        }
    }

    function _read_row(&$rc, $row) {
        $rc->project_file_id = $row['project_file_id'];
        $rc->user_id         = $row['user_id'];

        // fields from referenced tables
        $rc->user_name = $row['user_name'];

        return $rc;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_release_contributor ' .
            '(' .
            'project_file_id               int(10) not null, ' .
            'user_id                       int(10) not null, ' .
            'primary key (project_file_id, user_id), ' .
            'index (project_file_id), ' .
            'index (user_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function getUserIdsForProjectFileId($projectFileId) {
        $ids = array();

        $result = _mysql_query(
            'select user_id ' .
            'from pp_release_contributor ' .
            'where project_file_id = ' . n($projectFileId)
        );

        while ($row = mysql_fetch_array($result)) {
            $ids[] = $row['user_id'];
        }

        mysql_free_result($result);

        return $ids;
    }

    function getUserNamesForProjectFileId($projectFileId) {
        $names = array();

        $result = _mysql_query(
            'select u.name as user_name ' .
            'from pp_release_contributor rc, pp_user u ' .
            'where rc.project_file_id = ' . n($projectFileId) . ' ' .
            'and rc.user_id = u.id ' .
            'order by user_name asc'
        );

        while ($row = mysql_fetch_array($result)) {
            $names[] = $row['user_name'];
        }

        mysql_free_result($result);

        return $names;
    }

    function deleteForProjectFileId($projectFileId) {
        return _mysql_query(
            'delete from pp_release_contributor ' .
            'where project_file_id = ' . n($projectFileId)
        );
    }

    // object methods
    // --------------
    function insert() {
        $ok = _mysql_query(
            'insert into pp_release_contributor ' .
            '(project_file_id, user_id) ' .
            'values (' .
            n($this->project_file_id) . ', ' .
            n($this->user_id)         .
            ')'
        );

        if (!$ok) {
            return false;
        }

        return $ok;
    }

}

?>
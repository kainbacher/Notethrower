<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_release_contribution table
// it stores which contribution (project_file) is part of a release (project_file)
class ReleaseContribution {
    var $mix_project_file_id;
    var $contrib_project_file_id;

    // fields from referenced tables

    // constructors
    // ------------
    function ReleaseContribution() {
    }

    function fetchAllForMixProjectFileId($projectFileId) {
        $objs = array();

        $result = _mysql_query(
            'select rc.* ' .
            'from pp_release_contribution rc ' .
            'where rc.mix_project_file_id = ' . n($projectFileId)
        );

        while ($row = mysql_fetch_array($result)) {
            $rc = new ReleaseContribution();
            ReleaseContribution::_readRow($rc, $row);

            $objs[] = $rc;
        }

        mysql_free_result($result);

        return $objs;
    }

    function addAll($contribProjectFileIds, $mixProjectFileId) {
        foreach ($contribProjectFileIds as $id) {
            if ($id) {
                $rc = new ReleaseContribution();
                $rc->mix_project_file_id     = $mixProjectFileId;
                $rc->contrib_project_file_id = $id;
                $rc->insert();
            }
        }
    }

    function _readRow(&$rc, $row) {
        $rc->mix_project_file_id     = $row['mix_project_file_id'];
        $rc->contrib_project_file_id = $row['contrib_project_file_id'];

        // fields from referenced tables

        return $rc;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_release_contribution ' .
            '(' .
            'mix_project_file_id     int(10) not null, ' .
            'contrib_project_file_id int(10) not null, ' .
            'primary key (mix_project_file_id, contrib_project_file_id), ' .
            'index (mix_project_file_id), ' .
            'index (contrib_project_file_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function getContribProjectFileIdsForMixProjectFileId($mixProjectFileId) {
        $ids = array();

        $result = _mysql_query(
            'select contrib_project_file_id ' .
            'from pp_release_contribution ' .
            'where mix_project_file_id = ' . n($mixProjectFileId)
        );

        while ($row = mysql_fetch_array($result)) {
            $ids[] = $row['contrib_project_file_id'];
        }

        mysql_free_result($result);

        return $ids;
    }

    function deleteForMixProjectFileId($projectFileId) {
        return _mysql_query(
            'delete from pp_release_contribution ' .
            'where mix_project_file_id = ' . n($projectFileId)
        );
    }

    // object methods
    // --------------
    function insert() {
        return _mysql_query(
            'insert into pp_release_contribution ' .
            '(mix_project_file_id, contrib_project_file_id) ' .
            'values (' .
            n($this->mix_project_file_id)     . ', ' .
            n($this->contrib_project_file_id) .
            ')'
        );
    }

}

?>
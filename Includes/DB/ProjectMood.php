<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_project_mood table
class ProjectMood {
    var $project_id;
    var $mood_id;

    // non-table fields
    var $mood_name;

    // constructors
    // ------------
    function ProjectMood() {
    }

    function addAll($moodIds, $projectId) {
        foreach ($moodIds as $id) {
            if ($id) {
                $pm = new ProjectMood();
                $pm->project_id = $projectId;
                $pm->mood_id    = $id;
                $pm->insert();
            }
        }
    }

    function _read_row(&$pm, $row) {
        $pm->project_id = $row['project_id'];
        $pm->mood_id    = $row['mood_id'];

        // non-table fields
        $pm->mood_name = $row['mood_name'];

        return $pm;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_project_mood ' .
            '(' .
            'project_id int(10) not null, ' .
            'mood_id    int(10) not null, ' .
            'primary key (project_id, mood_id), ' .
            'index (project_id), ' .
            'index (mood_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function getMoodIdsForProjectId($project_id) {
        $ids = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_project_mood ' .
            'where project_id = ' . n($project_id)
        );

        while ($row = mysql_fetch_array($result)) {
            $ids[] = $row['mood_id'];
        }

        mysql_free_result($result);

        return $ids;
    }

    function getMoodNamesForProjectId($project_id) {
        $names = array();

        $result = _mysql_query(
            'select g.name as mood_name ' .
            'from pp_project_mood pg, pp_mood g ' .
            'where pg.project_id = ' . n($project_id) . ' ' .
            'and pg.mood_id = g.id ' .
            'order by mood_name asc'
        );

        while ($row = mysql_fetch_array($result)) {
            $names[] = $row['mood_name'];
        }

        mysql_free_result($result);

        return $names;
    }

    function deleteForProjectId($projectId) {
        return _mysql_query(
            'delete from pp_project_mood ' .
            'where project_id = ' . n($projectId)
        );
    }

    // object methods
    // --------------
    function insert() {
        $ok = _mysql_query(
            'insert into pp_project_mood ' .
            '(project_id, mood_id) ' .
            'values (' .
            n($this->project_id)    . ', ' .
            n($this->mood_id)       .
            ')'
        );

        if (!$ok) {
            return false;
        }

        return $ok;
    }
}

?>
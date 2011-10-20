<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_project_user_visibility table
// this table stores which user can access which project (collaboration artists)
class ProjectUserVisibility {
    var $project_id;
    var $user_id;
    var $is_request; // this is true(1) when some requests to join a project but hasn't been accepted yet, or null/false when the association is active.

    // non-table fields
    var $user_name;
    var $collaborating_user_id;
    var $user_image_filename;

    // constructors
    // ------------
    function ProjectUserVisibility() {
    }

    function fetch_all_for_user_id($aid) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_project_user_visibility ' .
            'where user_id = ' . n($aid) . ' ' .
            'and is_request != 1'
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new ProjectUserVisibility();
            $a = ProjectUserVisibility::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_all_requests_for_projects_of_user_id($aid) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_project_user_visibility ' .
            'where user_id = ' . n($aid) . ' ' .
            'and is_request = 1'
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new ProjectUserVisibility();
            $a = ProjectUserVisibility::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_all_for_project_id($tid) {
        $objs = array();

        $result = _mysql_query(
            'select atav.*, a.name as user_name ' .
            'from pp_project_user_visibility atav, pp_user a ' .
            'where atav.project_id = ' . n($tid) . ' ' .
            'and atav.user_id = a.id ' .
            'and atav.is_request != 1'
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new ProjectUserVisibility();
            $a = ProjectUserVisibility::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_all_requests_for_project_id($tid) {
        $objs = array();

        $result = _mysql_query(
            'select atav.*, a.name as user_name ' .
            'from pp_project_user_visibility atav, pp_user a ' .
            'where atav.project_id = ' . n($tid) . ' ' .
            'and atav.user_id = a.id ' .
            'and atav.is_request = 1'
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new ProjectUserVisibility();
            $a = ProjectUserVisibility::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_for_user_id_project_id($aid, $tid) {
        $result = _mysql_query(
            'select * ' .
            'from pp_project_user_visibility ' .
            'where user_id = ' . n($aid) . ' ' .
            'and project_id = ' . n($tid) . ' ' .
            'and is_request != 1'
        );

        $a = new ProjectUserVisibility();

        if ($row = mysql_fetch_array($result)) {
            $a = ProjectUserVisibility::_read_row($a, $row);
        }

        mysql_free_result($result);

        return $a;
    }

    function fetch_all_collaboration_users_of_user_id($aid, $limit = 0) {
        $objs = array();

        $limitClause = '';
        if ($limit) $limitClause = 'limit ' . $limit;

        $result = _mysql_query(
            'select distinct a.id as collaborating_user_id, a.name as user_name, a.image_filename as user_image_filename ' .
            'from pp_project t, pp_project_user_visibility atav, pp_user a ' .
            'where t.user_id = ' . n($aid) . ' ' .
            'and t.id = atav.project_id ' .
            'and atav.user_id = a.id ' .
            'and atav.is_request != 1 ' .
            'and a.id != ' . n($aid) . ' ' .
            'order by user_name asc ' .
            $limitClause
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new ProjectUserVisibility();
            $a = ProjectUserVisibility::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function _read_row($a, $row) {
        $a->project_id = $row['project_id'];
        $a->user_id    = $row['user_id'];
        $a->is_request = $row['is_request'];

        // non-table fields
        $a->user_name             = $row['user_name'];
        $a->collaborating_user_id = $row['collaborating_user_id'];
        $a->user_image_filename   = $row['user_image_filename'];

        return $a;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_project_user_visibility ' .
            '(' .
            'user_id                   int(10)      not null, ' .
            'project_id                int(10)      not null, ' .
            'is_request                tinyint(1), ' .
            'primary key (user_id, project_id), ' .
            'key user_id (user_id), ' .
            'index project_id (project_id), ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function delete_all_with_project_id($tid) {
        global $logger;

        if (!$tid) return;

        $logger->info('deleting all project_user_visibility records with project id: ' . $tid);

        return _mysql_query(
            'delete from pp_project_user_visibility ' .
            'where project_id = ' . n($tid)
        );
    }

    function delete_all_with_project_id_and_user_id_list($tid, $aids) {
        global $logger;

        if (!$tid) return;
        if (!$aids) return;

        $logger->info('deleting all project_user_visibility records with project id ' . $tid . ' and user id list ' . implode(',', $aids));

        return _mysql_query(
            'delete from pp_project_user_visibility ' .
            'where project_id = ' . n($tid) . ' ' .
            'and user_id in (' . implode(',', $aids) . ')'
        );
    }

    // object methods
    // --------------
    function save() {
        if (isset($this->id)) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    function insert() {
        $ok = _mysql_query(
            'insert into pp_project_user_visibility ' .
            '(user_id, project_id, is_request) ' .
            'values (' .
            n($this->user_id)                  . ', ' .
            n($this->project_id)               . ', ' .
            b($this->is_request)               .
            ')'
        );

        if (!$ok) {
            return false;
        }

        $this->id = mysql_insert_id();

        return $ok;
    }
}

?>
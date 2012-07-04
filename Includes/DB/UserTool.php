<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_user_tool table
class UserTool {
    var $user_id;
    var $tool_id;

    // non-table fields
    var $tool_name; // currently not read/not used

    // constructors
    // ------------
    function UserTool() {
    }

    function fetchAllForToolId($tool_id) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_user_tool ' .
            'where tool_id = ' . n($tool_id)
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new UserTool();
            UserTool::_read_row($f, $row);

            $objs[] = $f;
        }

        mysql_free_result($result);

        return $objs;
    }

    function addAll($toolIds, $userId) {
        foreach ($toolIds as $id) {
            if ($id) {
                $ug = new UserTool();
                $ug->user_id = $userId;
                $ug->tool_id = $id;
                $ug->insert();
            }
        }
    }

    function _read_row(&$a, $row) {
        $a->user_id = $row['user_id'];
        $a->tool_id = $row['tool_id'];

        // non-table fields
        $a->tool_name = $row['tool_name'];

        return $a;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_user_tool ' .
            '(' .
            'user_id  int(10) not null, ' .
            'tool_id int(10) not null, ' .
            'primary key (user_id, tool_id), ' .
            'index (user_id), ' .
            'index (tool_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function getToolIdsForUserId($user_id) {
        $ids = array();

        $result = _mysql_query(
            'select tool_id ' .
            'from pp_user_tool ' .
            'where user_id = ' . n($user_id)
        );

        while ($row = mysql_fetch_array($result)) {
            $ids[] = $row['tool_id'];
        }

        mysql_free_result($result);

        return $ids;
    }

    function getToolNamesForUserId($user_id) {
        $names = array();

        $result = _mysql_query(
            'select g.name as tool_name ' .
            'from pp_user_tool ug, pp_tool g ' .
            'where ug.user_id = ' . n($user_id) . ' ' .
            'and ug.tool_id = g.id'
        );

        while ($row = mysql_fetch_array($result)) {
            $names[] = $row['tool_name'];
        }

        mysql_free_result($result);

        return $names;
    }

    function deleteForUserId($userId) {
        return _mysql_query(
            'delete from pp_user_tool ' .
            'where user_id = ' . n($userId)
        );
    }

    // object methods
    // --------------
    function insert() {
        $ok = _mysql_query(
            'insert into pp_user_tool ' .
            '(user_id, tool_id) ' .
            'values (' .
            n($this->user_id)  . ', ' .
            n($this->tool_id) .
            ')'
        );

        if (!$ok) {
            return false;
        }

        return $ok;
    }
}

?>

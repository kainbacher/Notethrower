<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_tool table
class Tool {
    var $id;
    var $name;

    // constructors
    // ------------
    function Tool() {
    }

    function fetchAll() {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_tool ' .
            'order by name asc'
        );

        while ($row = mysql_fetch_array($result)) {
            $g = new Tool();
            Tool::_read_row($g, $row);

            $objs[$g->id] = $g;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchForId($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_tool ' .
            'where id = ' . n($id)
        );

        $g = null;

        if ($row = mysql_fetch_array($result)) {
            $g = new Tool();
            Tool::_read_row($g, $row);
        }

        mysql_free_result($result);

        return $g;
    }

    function fetchForName($name) {
        $result = _mysql_query(
            'select * ' .
            'from pp_tool ' .
            'where name = ' . qq($name)
        );

        $g = null;

        if ($row = mysql_fetch_array($result)) {
            $g = new Tool();
            Tool::_read_row($g, $row);
        }

        mysql_free_result($result);

        return $g;
    }

    function _read_row(&$g, $row) {
        $g->id   = $row['id'];
        $g->name = $row['name'];
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_tool ' .
            '(' .
            'id                        int(10)      not null auto_increment, ' .
            'name                      varchar(255) not null, ' .
            'primary key (id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function populateTable() {
        $existingTools = Tool::fetchAll();
        if (count($existingTools) == 0) {
            $initialTools = array(
                'Fender Stratocaster',
                'Gibson Les Paul'
            );

            foreach ($initialTools as $it) {
                if ($it) {
                    $t = new Tool();
                    $t->name = $it;
                    $t->insert();
                }
            }
        }
    }

    function countAll() {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_tool'
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function isValidTool($tool) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_tool ' .
            'where name = ' . qq($tool)
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return ($count == 1);
    }

    function getSelectorOptionsArray($includeEmptyOption = false) {
        $tools = array();

        if ($includeEmptyOption) {
            $tools[''] = '';
        }

        $all = Tool::fetchAll();
        foreach ($all as $t) {
            $tools[$t->id] = $t->name;
        }
        return $tools;
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
            'insert into pp_tool ' .
            '(name) ' .
            'values (' .
            qq($this->name) .
            ')'
        );

        if (!$ok) {
            return false;
        }

        $this->id = mysql_insert_id();

        return $ok;
    }

    function update() {
        $ok = _mysql_query(
            'update pp_tool ' .
            'set name = ' . qq($this->name) . ' ' .
            'where id = ' . n($this->id)
        );

        return $ok;
    }
}

?>
<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_project_attribute table
class ProjectAttribute {
    var $project_id;
    var $attribute_id;
    var $status; // valid values are "contains" and "needs"

    // non-table fields
    var $attribute_name;

    // constructors
    // ------------
    function ProjectAttribute() {
    }

    function fetchForAttributeIdAndStatus($attribute_id, $status) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_project_attribute ' .
            'where attribute_id = ' . n($attribute_id) . ' and ' .
            'status = ' . qq($status)
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new ProjectAttribute();
            ProjectAttribute::_read_row($f, $row);

            $objs[] = $f;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchAllWithStatusOfProjectsOfUser($user_id, $status) {
        $objs = array();

        $result = _mysql_query(
            'select pa.* ' .
            'from pp_project_attribute pa, pp_project p ' .
            'where p.user_id = ' . n($user_id) . ' ' .
            'and p.status != "finished" ' .
            'and p.id = pa.project_id ' .
            'and pa.status = ' . qq($status)
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new ProjectAttribute();
            ProjectAttribute::_read_row($f, $row);

            $objs[] = $f;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchAllWithStatusOfProject($project_id, $status) {
        $objs = array();

        $result = _mysql_query(
            'select pa.* ' .
            'from pp_project_attribute pa ' .
            'where pa.project_id = ' . n($project_id) . ' ' .
            'and pa.status = ' . qq($status)
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new ProjectAttribute();
            ProjectAttribute::_read_row($f, $row);

            $objs[] = $f;
        }

        mysql_free_result($result);

        return $objs;
    }

    function addAll($attributeIds, $projectId, $status) {
        foreach ($attributeIds as $id) {
            if ($id) {
                $pa = new ProjectAttribute();
                $pa->project_id   = $projectId;
                $pa->attribute_id = $id;
                $pa->status       = $status;
                $pa->insert();
            }
        }
    }

    function _read_row(&$a, $row) {
        $a->project_id       = $row['project_id'];
        $a->attribute_id     = $row['attribute_id'];
        $a->status           = $row['status'];

        // non-table fields
        $a->attribute_name = $row['attribute_name'];

        return $a;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_project_attribute ' .
            '(' .
            'project_id                    int(10) not null, ' .
            'attribute_id                  int(10) not null, ' .
            'status                        varchar(30) not null, ' .
            'primary key (project_id, attribute_id), ' .
            'index (project_id), ' .
            'index (attribute_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function getAttributeIdsForProjectIdAndState($project_id, $status) {
        $ids = array();

        $result = _mysql_query(
            'select attribute_id ' .
            'from pp_project_attribute ' .
            'where project_id = ' . n($project_id) . ' ' .
            'and status = ' . qq($status)
        );

        while ($row = mysql_fetch_array($result)) {
            $ids[] = $row['attribute_id'];
        }

        mysql_free_result($result);

        return $ids;
    }

    function getAttributeNamesForProjectIdAndState($project_id, $status) {
        $names = array();

        $result = _mysql_query(
            'select a.name as attribute_name ' .
            'from pp_project_attribute pa, pp_attribute a ' .
            'where pa.project_id = ' . n($project_id) . ' ' .
            'and pa.status = ' . qq($status) . ' ' .
            'and pa.attribute_id = a.id ' .
            'order by attribute_name asc'
        );

        while ($row = mysql_fetch_array($result)) {
            $names[] = $row['attribute_name'];
        }

        mysql_free_result($result);

        return $names;
    }

    function deleteForProjectId($projectId) {
        return _mysql_query(
            'delete from pp_project_attribute ' .
            'where project_id = ' . n($projectId)
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
            'insert into pp_project_attribute ' .
            '(project_id, attribute_id, status) ' .
            'values (' .
            n($this->project_id)    . ', ' .
            n($this->attribute_id)  . ', ' .
            qq($this->status)       .
            ')'
        );

        if (!$ok) {
            return false;
        }

        return $ok;
    }

    function update() {
        $ok = _mysql_query(
            'update pp_project_attribute ' .
            'set status = '       . qq($this->status)      . ' ' .
            'where project_id = ' . n($this->project_id)   . ' ' .
            'and attribute_id = ' . n($this->attribute_id)
        );

        return $ok;
    }

}

?>
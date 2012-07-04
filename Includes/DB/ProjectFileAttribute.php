<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_project_file_attribute table
// it stores the attributes of a project file (eg. "bass stem", "guitar and drums added", etc.)
class ProjectFileAttribute {
    var $project_file_id;
    var $attribute_id;

    // non-table fields
    var $attribute_name;

    // constructors
    // ------------
    function ProjectFileAttribute() {
    }

    // this function is used to get all the project files of a project with a given attribute
    function fetchAllForProjectIdAndAttributeId($project_id, $attribute_id) {
        $objs = array();

        $result = _mysql_query(
            'select pfa.* ' .
            'from pp_project_file_attribute pfa, pp_project_file pf, pp_project p ' .
            'where pfa.attribute_id = ' . n($attribute_id) . ' ' .
            'and pfa.project_file_id = pf.id ' .
            'and pf.project_id = p.id'
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new ProjectFileAttribute();
            ProjectFileAttribute::_read_row($f, $row);

            $objs[] = $f;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchAllForProjectFileId($project_file_id) {
        $objs = array();

        $result = _mysql_query(
            'select pfa.* ' .
            'from pp_project_file_attribute pfa ' .
            'where pfa.project_file_id = ' . n($project_file_id)
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new ProjectFileAttribute();
            ProjectFileAttribute::_read_row($f, $row);

            $objs[] = $f;
        }

        mysql_free_result($result);

        return $objs;
    }

    function addAll($attributeIds, $projectFileId) {
        foreach ($attributeIds as $id) {
            if ($id) {
                $pfa = new ProjectFileAttribute();
                $pfa->project_file_id = $projectFileId;
                $pfa->attribute_id    = $id;
                $pfa->insert();
            }
        }
    }

    function _read_row(&$a, $row) {
        $a->project_file_id = $row['project_file_id'];
        $a->attribute_id    = $row['attribute_id'];

        // non-table fields
        $a->attribute_name = $row['attribute_name'];

        return $a;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_project_file_attribute ' .
            '(' .
            'project_file_id               int(10) not null, ' .
            'attribute_id                  int(10) not null, ' .
            'primary key (project_file_id, attribute_id), ' .
            'index (project_file_id), ' .
            'index (attribute_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function getAttributeIdsForProjectFileId($project_file_id) {
        $ids = array();

        $result = _mysql_query(
            'select attribute_id ' .
            'from pp_project_file_attribute ' .
            'where project_file_id = ' . n($project_file_id)
        );

        while ($row = mysql_fetch_array($result)) {
            $ids[] = $row['attribute_id'];
        }

        mysql_free_result($result);

        return $ids;
    }

    function getAttributeNamesForProjectFileId($project_file_id) {
        $names = array();

        $result = _mysql_query(
            'select a.name as attribute_name ' .
            'from pp_project_file_attribute pfa, pp_attribute a ' .
            'where pfa.project_file_id = ' . n($project_file_id) . ' ' .
            'and pfa.attribute_id = a.id ' .
            'order by attribute_name asc'
        );

        while ($row = mysql_fetch_array($result)) {
            $names[] = $row['attribute_name'];
        }

        mysql_free_result($result);

        return $names;
    }

    function deleteForProjectFileId($projectFileId) {
        return _mysql_query(
            'delete from pp_project_file_attribute ' .
            'where project_file_id = ' . n($projectFileId)
        );
    }

    // object methods
    // --------------
    function insert() {
        $ok = _mysql_query(
            'insert into pp_project_file_attribute ' .
            '(project_file_id, attribute_id) ' .
            'values (' .
            n($this->project_file_id) . ', ' .
            n($this->attribute_id)    .
            ')'
        );

        if (!$ok) {
            return false;
        }

        return $ok;
    }

}

?>

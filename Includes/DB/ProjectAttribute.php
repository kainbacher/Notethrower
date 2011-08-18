<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_project_attribute table
class ProjectAttribute {
    var $track_id;
    var $attribute_id;
    var $status;

    // non-table fields
    var $attribute_name;

    // constructors
    // ------------
    function ProjectAttribute() {
    }

    function fetchAttributeIdsForTrackIdAndState($track_id, $status) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_project_attribute ' .
            'where track_id = ' . n($track_id) . ' and ' .
            'status = ' . qq($status)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $f = new ProjectAttribute();
            $f = ProjectAttribute::_read_row($f, $row);

            $objs[$ind] = $f->attribute_id;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchAttributeNamesForTrackIdAndState($track_id, $status) {
        $objs = array();

        $result = _mysql_query(
            'select atata.*, ata.name as attribute_name ' .
            'from pp_project_attribute atata, pp_attribute ata ' .
            'where atata.track_id = ' . n($track_id) . ' and ' .
            'atata.status = ' . qq($status) . ' and ' .
            'atata.attribute_id = ata.id'
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $f = new ProjectAttribute();
            $f = ProjectAttribute::_read_row($f, $row);

            $objs[$ind] = $f->attribute_name;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchForAttributeIdAndStatus($attribute_id, $status) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_project_attribute ' .
            'where attribute_id = ' . n($attribute_id) . ' and ' .
            'status = ' . qq($status)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $f = new ProjectAttribute();
            $f = ProjectAttribute::_read_row($f, $row);

            $objs[$ind] = $f;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function addAll($attributeIds, $trackId, $status) {
        foreach ($attributeIds as $id) {
            $f = new ProjectAttribute();
            $f->attribute_id = $id;
            $f->track_id = $trackId;
            $f->status = $status;
            $f->save();
        }
    }

    function _read_row($a, $row) {
        $a->attribute_id     = $row['attribute_id'];
        $a->track_id         = $row['track_id'];
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
            'attribute_id                  int(10) not null, ' .
            'track_id                      int(10) not null, ' .
            'status                        varchar(30) not null, ' .
            'index (track_id), ' .
            'index (attribute_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function deleteForProjectId($trackId) {
        return _mysql_query(
            'delete from pp_project_attribute ' .
            'where track_id = ' . n($trackId)
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
            '(attribute_id, track_id, status) ' .
            'values (' .
            n($this->attribute_id)  . ', ' .
            n($this->track_id)      . ', ' .
            qq($this->status)       .
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
            'update pp_project_attribute ' .
            'set attrubite_id = ' . n($this->attribute_id) . ', ' .
            'track_id = ' . n($this->track_id) . ', ' .
            'status = ' . qq($this->status) . ' ' .
            'where id = ' . n($this->id)
        );

        return $ok;
    }

}

?>
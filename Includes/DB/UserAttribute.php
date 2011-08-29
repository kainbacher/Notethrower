<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_user_attribute table
class UserAttribute {
    var $user_id;
    var $attribute_id;
    var $status; // valid values are "offers" and "needs"

    // non-table fields
    var $attribute_name;

    // constructors
    // ------------
    function UserAttribute() {
    }

    function fetchAttributeIdsForUserIdAndState($user_id, $status) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_user_attribute ' .
            'where user_id = ' . n($user_id) . ' and ' .
            'status = ' . qq($status)
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new UserAttribute();
            $f = UserAttribute::_read_row($f, $row);

            $objs[] = $f->attribute_id;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchAttributeNamesForUserIdAndState($user_id, $status) {
        $objs = array();

        $result = _mysql_query(
            'select ua.*, a.name as attribute_name ' .
            'from pp_user_attribute ua, pp_attribute a ' .
            'where ua.user_id = ' . n($user_id) . ' and ' .
            'ua.status = ' . qq($status) . ' and ' .
            'ua.attribute_id = a.id'
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new UserAttribute();
            $f = UserAttribute::_read_row($f, $row);

            $objs[] = $f->attribute_name;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchForAttributeIdAndStatus($attribute_id, $status) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_user_attribute ' .
            'where attribute_id = ' . n($attribute_id) . ' and ' .
            'status = ' . qq($status)
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new UserAttribute();
            $f = UserAttribute::_read_row($f, $row);

            $objs[] = $f;
        }

        mysql_free_result($result);

        return $objs;
    }

    function addAll($attributeIds, $userId, $status) {
        foreach ($attributeIds as $id) {
            $f = new UserAttribute();
            $f->user_id      = $userId;
            $f->attribute_id = $id;
            $f->status       = $status;
            $f->save();
        }
    }

    function _read_row($a, $row) {
        $a->user_id       = $row['user_id'];
        $a->attribute_id  = $row['attribute_id'];
        $a->status        = $row['status'];

        // non-table fields
        $a->attribute_name = $row['attribute_name'];

        return $a;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_user_attribute ' .
            '(' .
            'user_id                    int(10) not null, ' .
            'attribute_id               int(10) not null, ' .
            'status                     varchar(6) not null, ' .
            'primary key (user_id, attribute_id), ' .
            'index (user_id), ' .
            'index (attribute_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function deleteForUserId($userId) {
        return _mysql_query(
            'delete from pp_user_attribute ' .
            'where user_id = ' . n($userId)
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
            'insert into pp_user_attribute ' .
            '(user_id, attribute_id, status) ' .
            'values (' .
            n($this->user_id)       . ', ' .
            n($this->attribute_id)  . ', ' .
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
            'update pp_user_attribute ' .
            'set user_id = '      . n($this->user_id)      . ', ' .
            'attribute_id = '     . n($this->attribute_id) . ', ' .
            'status = '           . qq($this->status)      . ' ' .
            'where id = '         . n($this->id)
        );

        return $ok;
    }

}

?>
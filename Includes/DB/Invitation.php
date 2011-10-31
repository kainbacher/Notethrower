<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_invitation table
class Invitation {
    var $id;
    var $sender_user_id;
    var $recipient_email_address;
    var $project_id; // the project to which the recipient is invited
    var $creation_date;

    // constructors
    // ------------
    function Invitation() {
    }

    function fetchForId($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_invitation ' .
            'where id = ' . n($id)
        );

        $i = null;

        if ($row = mysql_fetch_array($result)) {
            $i = new Invitation();
            Invitation::_read_row($i, $row);
        }

        mysql_free_result($result);

        return $i;
    }

    function fetchAllForRecipientEmailAddress($email) {
        $result = _mysql_query(
            'select * ' .
            'from pp_invitation ' .
            'where recipient_email_address = ' . qq($email)
        );

        $objs = array();

        while ($row = mysql_fetch_array($result)) {
            $i = new Invitation();
            Invitation::_read_row($i, $row);
            $objs[] = $i;
        }

        mysql_free_result($result);

        return $objs;
    }

    function _read_row(&$i, $row) {
        $i->id                      = $row['id'];
        $i->sender_user_id          = $row['sender_user_id'];
        $i->recipient_email_address = $row['recipient_email_address'];
        $i->project_id              = $row['project_id'];
        $i->creation_date           = $row['creation_date'];
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_invitation ' .
            '(' .
            'id                       int(10)      not null auto_increment, ' .
            'sender_user_id           int(10)      not null, ' .
            'recipient_email_address  varchar(255) not null, ' .
            'project_id               int(10)      not null, ' .
            'creation_date            datetime     not null, ' .
            'primary key (id), ' .
            'index recipient_email (recipient_email_address) ' .
            ') default charset=utf8'
        );

        return $ok;
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
            'insert into pp_invitation ' .
            '(sender_user_id, recipient_email_address, project_id, creation_date) ' .
            'values (' .
            n($this->sender_user_id)           . ', ' .
            qq($this->recipient_email_address) . ', ' .
            n($this->project_id)               . ', ' .
            qq($this->creation_date)           .
            ')'
        );

        if (!$ok) {
            return false;
        }

        $this->id = mysql_insert_id();

        return $ok;
    }

    function update() {
        return _mysql_query(
            'update pp_invitation ' .
            'set sender_user_id = '      . n($this->sender_user_id)           . ', ' .
            'recipient_email_address = ' . qq($this->recipient_email_address) . ', ' .
            'project_id = '              . n($this->project_id)               . ' ' .
            // creation_date intentionally not set here
            'where id = '                . n($this->id)
        );
    }
}

?>
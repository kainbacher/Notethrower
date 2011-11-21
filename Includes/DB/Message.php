<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_message table
class Message {
    var $id;
    var $sender_user_id; // can be empty in case the system has sent a message
    var $recipient_user_id;
    var $subject;
    var $text;
    var $type; // possible values: null (same as 'message'), 'message', 'invitation'
    var $deleted;
    var $marked_as_read;
    var $entry_date;

    // non-table fields
    var $sender_user_name; // can be empty - see above
    var $sender_image_filename; // can be empty - see above

    // constructors
    // ------------
    function Message() {
    }

    function fetch_all_for_recipient_user_id($raid, $limit = null) {
        $objs = array();

        $result = _mysql_query(
            'select m.*, a.name as sender_user_name, a.image_filename as sender_image_filename ' .
            'from pp_message m ' .
            'left join pp_user a on m.sender_user_id = a.id ' .
            'where m.recipient_user_id = ' . n($raid) . ' ' .
            'and m.deleted = 0 ' .
            'order by m.entry_date desc ' .
            ($limit ? 'limit ' . $limit : '')
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $m = new Message();
            $m = Message::_read_row($m, $row);

            $objs[$ind] = $m;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_for_id($id) {
        $result = _mysql_query(
            'select m.*, a.name as sender_user_name, a.image_filename as sender_image_filename ' .
            'from pp_message m ' .
            'left join pp_user a on m.sender_user_id = a.id ' .
            'where m.id = ' . n($id)
        );

        if ($row = mysql_fetch_array($result)) {
            $m = new Message();
            $m = Message::_read_row($m, $row);
            mysql_free_result($result);
            return $m;

        } else {
            mysql_free_result($result);
            return null;
        }
    }

    function _read_row($m, $row) {
        $m->id                  = $row['id'];
        $m->sender_user_id      = $row['sender_user_id'];
        $m->recipient_user_id   = $row['recipient_user_id'];
        $m->subject             = $row['subject'];
        $m->text                = $row['text'];
        $m->type                = $row['type'];
        $m->deleted             = $row['deleted'];
        $m->marked_as_read      = $row['marked_as_read'];
        $m->entry_date          = $row['entry_date'];

        // non-table fields
        $m->sender_user_name      = $row['sender_user_name'];
        $m->sender_image_filename = $row['sender_image_filename'];

        return $m;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_message ' .
            '(' .
            'id                  int(10)      not null auto_increment, ' .
            'sender_user_id      int(10), ' .
            'recipient_user_id   int(10)      not null, ' .
            'subject             varchar(255), ' .
            'text                text         not null, ' .
            'type                varchar(10), ' .
            'deleted             tinyint(1)   not null, ' .
            'marked_as_read      tinyint(1)   not null, ' .
            'entry_date          datetime     not null default "1970-01-01 00:00:00", ' .
            'primary key (id), ' .
            'key aid_del (recipient_user_id, deleted) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function count_all_unread_msgs_for_recipient_user_id($raid) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_message ' .
            'where recipient_user_id = ' . n($raid) . ' ' .
            'and marked_as_read = 0'
        );

        $count = 0;
        if ($row = mysql_fetch_array($result)) {
            $count = $row['cnt'];
        }

        mysql_free_result($result);

        return $count;
    }

    function delete_with_id($id) {
        if (!$id) return;

        return _mysql_query(
            'delete from pp_message ' .
            'where id = ' . n($id)
        );
    }

    function mark_all_as_read_for_recipient_user_id($raid) {
        if (!$raid) return;

        return _mysql_query(
            'update pp_message ' .
            'set marked_as_read = 1 ' .
            'where recipient_user_id = ' . n($raid)
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
            'insert into pp_message ' .
            '(sender_user_id, recipient_user_id, subject, text, type, deleted, marked_as_read, entry_date) ' .
            'values (' .
            n($this->sender_user_id)       . ', ' .
            n($this->recipient_user_id)    . ', ' .
            qq($this->subject)             . ', ' .
            qq($this->text)                . ', ' .
            qq($this->type)                . ', ' .
            b($this->deleted)              . ', ' .
            b($this->marked_as_read)       . ', ' .
            'now()'                        .
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
            'update pp_message ' .
            'set sender_user_id = '   . n($this->sender_user_id)      . ', ' .
            'recipient_user_id = '    . n($this->recipient_user_id)   . ', ' .
            'subject = '              . qq($this->subject)            . ', ' .
            'text = '                 . qq($this->text)               . ', ' .
            'type = '                 . qq($this->type)               . ', ' .
            'deleted = '              . b($this->deleted)             . ', ' .
            'marked_as_read = '       . b($this->marked_as_read)      . ' ' .
            // entry_date intentionally not set here
            'where id = '             . n($this->id)
        );

        return $ok;
    }
}

?>
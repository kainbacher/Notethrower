<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_artist table
class Artist {
    var $id; // -1 means unknown
    var $username;
    var $password_md5;
    var $email_address;
    var $name;
    var $artist_info;
    var $additional_info;
    var $image_filename;
    var $webpage_url;
    var $paypal_account;
    var $status; // active, inactive (account created but not confirmed), banned
    var $entry_date;

    var $loggedIn;

    // constructors
    // ------------
    function Artist() {
    }

    function new_from_cookie($refreshLastActivityTimestamp = true) {
        global $logger;

        if (isset($_COOKIE['notethrower'])) {
            $val = $_COOKIE['notethrower'];
            //$separator_pos    = strpos($val, '#');
            //$password_md5     = substr($val, 0, $separator_pos);
            //$id               = substr($val, $separator_pos + 1);
            $parts = explode('#', $val);
            $password_md5     = $parts[0];
            $id               = $parts[1];
            $lastActivityTime = $parts[2];

            if (time() - $lastActivityTime > $GLOBALS['SESSION_LIFETIME_SECONDS']) {
                $logger->info('session has expired!');
                return null;
            }

            $result = _mysql_query(
                'select * ' .
                'from pp_artist ' .
                'where id = ' . n($id) . ' ' .
                'and password_md5 = ' . qq($password_md5) . ' ' .
                'and status = "active"'
            );

            if ($row = mysql_fetch_array($result)) {
                $a = new Artist();
                $a = Artist::_read_row($a, $row);
                $a->loggedIn = true;
                if ($refreshLastActivityTimestamp) $a->refreshLastActivityTimestamp();
                mysql_free_result($result);
                return $a;

            } else {
                mysql_free_result($result);
                return null;
            }

        } else {
            return null;
        }
    }

    function fetch_all_from_to($from, $to, $show_inactive_items, $include_unknown_artist) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_artist ' .
            ($show_inactive_items ? 'where 1 = 1 ' : 'where status = "active" ') .
            ($include_unknown_artist ? '' : 'and id >= 0 ') .
            'order by name ' .
            'limit ' . $from . ', ' . ($to - $from + 1)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new Artist();
            $a = Artist::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_for_id($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_artist ' .
            'where id = ' . n($id)
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new Artist();
            $a = Artist::_read_row($a, $row);
            mysql_free_result($result);
            return $a;

        } else {
            mysql_free_result($result);
            return null;
        }
    }

    function fetch_for_username($username) {
        $result = _mysql_query(
            'select * ' .
            'from pp_artist ' .
            'where username = ' . qq($username)
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new Artist();
            $a = Artist::_read_row($a, $row);
            mysql_free_result($result);
            return $a;

        } else {
            mysql_free_result($result);
            return null;
        }
    }


    function fetch_all_for_name_like($search_string, $limit) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_artist ' .
            'where upper(name) like ' . qq('%' . strtoupper($search_string) . '%') . ' ' .
            'order by name ' .
            'limit ' . ($limit)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new Artist();
            $a = Artist::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }


    function fetch_for_username_password($username, $password) {
        $result = _mysql_query(
            'select * ' .
            'from pp_artist ' .
            'where username = ' . qq($username) . ' ' .
            'and password_md5 = ' . qq(md5($password))
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new Artist();
            $a = Artist::_read_row($a, $row);
            mysql_free_result($result);
            return $a;

        } else {
            mysql_free_result($result);
            return null;
        }
    }

    function fetch_for_email_address($email) {
        $result = _mysql_query(
            'select * ' .
            'from pp_artist ' .
            'where email_address = ' . qq($email)
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new Artist();
            $a = Artist::_read_row($a, $row);
            mysql_free_result($result);
            return $a;

        } else {
            mysql_free_result($result);
            return null;
        }
    }

    function fetch_for_name($name) {
        $result = _mysql_query(
            'select * ' .
            'from pp_artist ' .
            'where name = ' . qq($name)
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new Artist();
            $a = Artist::_read_row($a, $row);
            mysql_free_result($result);
            return $a;

        } else {
            mysql_free_result($result);
            return null;
        }
    }

    function _read_row($a, $row) {
        $a->id              = $row['id'];
        $a->username        = $row['username'];
        $a->password_md5    = $row['password_md5'];
        $a->email_address   = $row['email_address'];
        $a->name            = $row['name'];
        $a->artist_info     = $row['artist_info'];
        $a->additional_info = $row['additional_info'];
        $a->image_filename  = $row['image_filename'];
        $a->webpage_url     = $row['webpage_url'];
        $a->paypal_account  = $row['paypal_account'];
        $a->status          = $row['status'];
        $a->entry_date      = reformat_sql_date($row['entry_date']);

        return $a;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_artist ' .
            '(' .
            'id              int(10)      not null auto_increment, ' .
            'username        varchar(50)  not null, ' .
            'password_md5    varchar(50)  not null, ' .
            'email_address   varchar(255) not null, ' .
            'name            varchar(50)  not null, ' .
            'artist_info     text, ' .
            'additional_info text, ' .
            'image_filename  varchar(255), ' .
            'webpage_url     varchar(255), ' .
            'paypal_account  varchar(255), ' .
            'status          varchar(20)  not null, ' .
            'entry_date      datetime     not null default "1970-01-01 00:00:00", ' .
            'primary key (id), ' .
            'key name (name), ' .
            'key username (username), ' .
            'key id_pwd (id, password_md5), ' .
            'key username_pwd (username, password_md5), ' .
            'key entry_date (entry_date) ' .
            ')'
        );

        if ($ok) {
            $test_record = Artist::fetch_for_id(-1);
            if (!$test_record || !$test_record->id) {
                $ok = _mysql_query(
                    'insert into pp_artist (id, username, password_md5, email_address, name, artist_info, additional_info, ' .
                    'image_filename, webpage_url, paypal_account, status, entry_date) ' .
                    'values (-1, "_unknown_artist", "' . md5('dummyPwd') . '", "", "Unknown Artist", "", "", "", "", "", "active", now())'
                );
            }
        }

        return $ok;
    }

    function count_all($count_inactive_items, $include_unknown_artist) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_artist ' .
            ($count_inactive_items ? 'where 1 = 1 ' : 'where status = "active" ') .
            ($include_unknown_artist ? '' : 'and id >= 0')
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function delete_with_id($id) {
        if (!$id) return;

        return _mysql_query(
            'delete from pp_artist ' .
            'where id = ' . n($id)
        );
    }

    // object methods
    // --------------
    function refreshLastActivityTimestamp() {
        global $logger;
        $logger->info('refreshing activity timestamp by setting cookie with value: ' . $this->password_md5 . '#' . $this->id . '#' . time());
        setcookie($GLOBALS['COOKIE_NAME'], $this->passwordMd5 . '#' . $this->id . '#' . time(), 0, '/'); // TODO - make this more secure - a brute force attack could be used to break md5 encryption of short passwords
    }

    function doLogin() {
        global $logger;
        $logger->info('setting cookie with value: ' . $this->password_md5 . '#' . $this->id . '#' . time());
        setcookie('notethrower', $this->password_md5 . '#' . $this->id . '#' . time(), null, '/' . $GLOBALS['WEBAPP_BASE']);
    }

    function doLogout() {
        global $logger;
        $logger->info('setting cookie with no value and time: ' . (time() - 3600));
        setcookie('notethrower', '', time() - 3600, '/' . $GLOBALS['WEBAPP_BASE']);
    }

    function save() {
        if (isset($this->id)) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    function insert() {
        $ok = _mysql_query(
            'insert into pp_artist ' .
            '(username, password_md5, email_address, name, artist_info, additional_info, image_filename, ' .
            'webpage_url, paypal_account, status, entry_date) ' .
            'values (' .
            qq($this->username)        . ', ' .
            qq($this->password_md5)    . ', ' .
            qq($this->email_address)   . ', ' .
            qq($this->name)            . ', ' .
            qq($this->artist_info)     . ', ' .
            qq($this->additional_info) . ', ' .
            qq($this->image_filename)  . ', ' .
            qq($this->webpage_url)     . ', ' .
            qq($this->paypal_account)  . ', ' .
            qq($this->status)          . ', ' .
            'now()'                    .
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
            'update pp_artist ' .
            'set username = '      . qq($this->username)        . ', ' .
            'password_md5 = '      . qq($this->password_md5)    . ', ' .
            'email_address = '     . qq($this->email_address)   . ', ' .
            'name = '              . qq($this->name)            . ', ' .
            'artist_info = '       . qq($this->artist_info)     . ', ' .
            'additional_info = '   . qq($this->additional_info) . ', ' .
            'image_filename = '    . qq($this->image_filename)  . ', ' .
            'webpage_url = '       . qq($this->webpage_url)     . ', ' .
            'paypal_account = '    . qq($this->paypal_account)  . ', ' .
            'status = '            . qq($this->status)          . ' ' .
            // entry_date intentionally not set here
            'where id = '          . n($this->id)
        );

        return $ok;
    }
}

?>
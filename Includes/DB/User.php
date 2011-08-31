<?php

include_once('../Includes/Config.php');
include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/ProjectAttribute.php');

// dao for pp_user table
class User {
    var $id; // -1 means unknown
    var $username;
    var $password_md5;
    var $email_address;
    var $name;
    var $artist_info;
    var $additional_info;
    var $influences;
    var $image_filename;
    var $webpage_url;
    var $paypal_account;
    var $activity_points;
    var $is_artist; // if true, the user is both a fan and an artist, if false the user is only a fan
    var $is_pro; // a pro user pays for some extra services and has more features available than a regular user
    var $status; // active, inactive (account created but not confirmed), banned
    var $entry_date;

    // non-table fields
    var $loggedIn;
    var $offersAttributeIdsList;
    var $offersAttributeNamesList;

    // constructors
    // ------------
    function User() {
    }

    function new_from_cookie($refreshLastActivityTimestamp = true) {
        global $logger;

        if (isset($_COOKIE[$GLOBALS['COOKIE_NAME_AUTHENTICATION']])) {
            $logger->info('auth cookie found');
            $val = $_COOKIE[$GLOBALS['COOKIE_NAME_AUTHENTICATION']];
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
                'from pp_user ' .
                'where id = ' . n($id) . ' ' .
                'and password_md5 = ' . qq($password_md5) . ' ' .
                'and status = "active"'
            );

            if ($row = mysql_fetch_array($result)) {
                $u = new User();
                $u = User::_read_row($u, $row);
                $u->loggedIn = true;
                if ($refreshLastActivityTimestamp) $u->refreshLastActivityTimestamp();
                mysql_free_result($result);
                return $u;

            } else {
                mysql_free_result($result);
                return null;
            }

        } else {
            return null;
        }
    }

    function fetch_all_from_to($from, $to, $show_inactive_items, $include_unknown_artist, $include_fans = false,
            $orderByClause = 'order by u.name asc') {

        $objs = array();

        $constraints = array();
        if (!$show_inactive_items) {
            $constraints[] = 'u.status = "active"';
        }
        if (!$include_unknown_artist) {
            $constraints[] = 'u.id >= 0';
        }
        if (!$include_fans) {
            $constraints[] = 'u.is_artist = 1';
        }

        $whereClause = join(' and ', $constraints);
        if ($whereClause) $whereClause = 'where ' . $whereClause;

        $result = _mysql_query(
            'select u.* ' .
            'from pp_user u ' .
            $whereClause . ' ' .
            $orderByClause . ' ' .
            'limit ' . $from . ', ' . ($to - $from + 1)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new User();
            $a = User::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_most_listened_artists_from_to($from, $to) {
        $objs = array();

        $result = _mysql_query(
            'select u.*, sum(t.playback_count) as pb_count ' .
            'from pp_user u, pp_project t ' .
            'where u.id = t.user_id ' .
            'group by u.id ' .
            'order by pb_count desc ' .
            'limit ' . $from . ', ' . ($to - $from + 1)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new User();
            $a = User::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_for_id($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_user ' .
            'where id = ' . n($id)
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new User();
            $a = User::_read_row($a, $row);
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
            'from pp_user ' .
            'where username = ' . qq($username)
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new User();
            $a = User::_read_row($a, $row);
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
            'from pp_user ' .
            'where upper(name) like ' . qq('%' . strtoupper($search_string) . '%') . ' ' .
            'order by name ' .
            'limit ' . ($limit)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new User();
            $a = User::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_for_username_password($username, $password) {
        $result = _mysql_query(
            'select * ' .
            'from pp_user ' .
            'where username = ' . qq($username) . ' ' .
            'and password_md5 = ' . qq(md5($password))
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new User();
            $a = User::_read_row($a, $row);
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
            'from pp_user ' .
            'where email_address = ' . qq($email)
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new User();
            $a = User::_read_row($a, $row);
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
            'from pp_user ' .
            'where name = ' . qq($name)
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new User();
            $a = User::_read_row($a, $row);
            mysql_free_result($result);
            return $a;

        } else {
            mysql_free_result($result);
            return null;
        }
    }

    function fetchAllThatOfferSkillsForUser(&$user) {
        $attribute_id_list = array();

        // fetch all attributes of all projects of the user
        $paList = ProjectAttribute::fetchAllWithStatusOfProjectsOfUser($user->id, 'needs');
        foreach ($paList as $pa) {
            $attribute_id_list[$pa->attribute_id] = $pa->attribute_id; // make distinct list of attributes
        }

        $objs = array();

        if (count($attribute_id_list) > 0) {
            $result = _mysql_query(
                'select u.*, a.id as attribute_id, a.name as attribute_name ' .
                'from pp_user u, pp_user_attribute ua, pp_attribute a ' .
                'where ua.attribute_id in (' . implode(',', $attribute_id_list) . ') ' .
                'and ua.status = "offers" ' .
                'and ua.user_id = u.id ' .
                'and u.status = "active" ' .
                'and ua.attribute_id = a.id ' .
                // FIXME - limit/paging?
                'order by u.name asc' // FIXME - sort by rating as soon as we have one?
            );

            $previousUid = null;
            $u = null;
            while ($row = mysql_fetch_array($result)) {
                if ($previousUid != $row['id']) {
                    $u = new User();
                    $u = User::_read_row($u, $row);
                    $u->offersAttributeIdsList   = array();
                    $u->offersAttributeNamesList = array();
                    $objs[] = $u;
                }

                $u->offersAttributeIdsList[]   = $row['attribute_id'];
                $u->offersAttributeNamesList[] = $row['attribute_name'];

                $previousUid = $row['id'];
            }

            mysql_free_result($result);
        }

        return $objs;
    }

    function _read_row($a, $row) {
        $a->id              = $row['id'];
        $a->username        = $row['username'];
        $a->password_md5    = $row['password_md5'];
        $a->email_address   = $row['email_address'];
        $a->name            = $row['name'];
        $a->artist_info     = $row['artist_info'];
        $a->additional_info = $row['additional_info'];
        $a->influences      = $row['influences'];
        $a->image_filename  = $row['image_filename'];
        $a->webpage_url     = $row['webpage_url'];
        $a->paypal_account  = $row['paypal_account'];
        $a->activity_points = $row['activity_points'];
        $a->is_artist       = $row['is_artist'];
        $a->is_pro          = $row['is_pro'];
        $a->status          = $row['status'];
        $a->entry_date      = reformat_sql_date($row['entry_date']);

        return $a;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_user ' .
            '(' .
            'id              int(10)      not null auto_increment, ' .
            'username        varchar(50)  not null, ' .
            'password_md5    varchar(50)  not null, ' .
            'email_address   varchar(255) not null, ' .
            'name            varchar(50)  not null, ' .
            'artist_info     text, ' .
            'additional_info text, ' .
            'influences      text, ' .
            'image_filename  varchar(255), ' .
            'webpage_url     varchar(255), ' .
            'paypal_account  varchar(255), ' .
            'activity_points int(10), ' .
            'is_artist       tinyint(1)   not null, ' .
            'is_pro          tinyint(1)   not null, ' .
            'status          varchar(20)  not null, ' .
            'entry_date      datetime     not null default "1970-01-01 00:00:00", ' .
            'primary key (id), ' .
            'key name (name), ' .
            'key username (username), ' .
            'key id_pwd (id, password_md5), ' .
            'key username_pwd (username, password_md5), ' .
            'key entry_date (entry_date) ' .
            ') default charset=utf8'
        );

        if ($ok) {
            $test_record = User::fetch_for_id(-1);
            if (!$test_record || !$test_record->id) {
                $ok = _mysql_query(
                    'insert into pp_user (id, username, password_md5, email_address, name, artist_info, additional_info, influences,' .
                    'image_filename, webpage_url, paypal_account, activity_points, is_artist, is_pro, status, entry_date) ' .
                    'values (-1, "_unknown_artist", "' . md5('dummyPwd') . '", "", "Unknown Artist", "", "", "", "", "", "", 0, 1, 0, "active", now())'
                );
            }
        }

        return $ok;
    }

    function count_all($count_inactive_items, $include_unknown_artist) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_user ' .
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
            'delete from pp_user ' .
            'where id = ' . n($id)
        );
    }

    // object methods
    // --------------
    function refreshLastActivityTimestamp() {
        global $logger;
        $logger->info('refreshing activity timestamp by setting cookie with value: ' . $this->password_md5 . '#' . $this->id . '#' . time());
        setcookie($GLOBALS['COOKIE_NAME_AUTHENTICATION'], $this->password_md5 . '#' . $this->id . '#' . time(), 0, $GLOBALS['WEBAPP_BASE']); // TODO - make this more secure - a brute force attack could be used to break md5 encryption of short passwords
    }

    function doLogin() {
        global $logger;
        $logger->info('setting "' . $GLOBALS['COOKIE_NAME_AUTHENTICATION'] . '" cookie with value: ' . $this->password_md5 . '#' . $this->id . '#' . time());
        setcookie($GLOBALS['COOKIE_NAME_AUTHENTICATION'], $this->password_md5 . '#' . $this->id . '#' . time(), 0, $GLOBALS['WEBAPP_BASE']);
    }

    function doLogout() {
        global $logger;
        $logger->info('setting cookie with no value and time: ' . (time() - 3600));
        setcookie($GLOBALS['COOKIE_NAME_AUTHENTICATION'], '', time() - 3600, $GLOBALS['WEBAPP_BASE']);
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
            'insert into pp_user ' .
            '(username, password_md5, email_address, name, artist_info, additional_info, influences, image_filename, ' .
            'webpage_url, paypal_account, activity_points, is_artist, is_pro, status, entry_date) ' .
            'values (' .
            qq($this->username)        . ', ' .
            qq($this->password_md5)    . ', ' .
            qq($this->email_address)   . ', ' .
            qq($this->name)            . ', ' .
            qq($this->artist_info)     . ', ' .
            qq($this->additional_info) . ', ' .
            qq($this->influences)      . ', ' .
            qq($this->image_filename)  . ', ' .
            qq($this->webpage_url)     . ', ' .
            qq($this->paypal_account)  . ', ' .
            n($this->activity_points)  . ', ' .
            b($this->is_artist)        . ', ' .
            b($this->is_pro)           . ', ' .
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
            'update pp_user ' .
            'set username = '      . qq($this->username)        . ', ' .
            'password_md5 = '      . qq($this->password_md5)    . ', ' .
            'email_address = '     . qq($this->email_address)   . ', ' .
            'name = '              . qq($this->name)            . ', ' .
            'artist_info = '       . qq($this->artist_info)     . ', ' .
            'additional_info = '   . qq($this->additional_info) . ', ' .
            'influences = '        . qq($this->influences)      . ', ' .
            'image_filename = '    . qq($this->image_filename)  . ', ' .
            'webpage_url = '       . qq($this->webpage_url)     . ', ' .
            'paypal_account = '    . qq($this->paypal_account)  . ', ' .
            'activity_points = '   . n($this->activity_points)  . ', ' .
            'is_artist = '         . b($this->is_artist)        . ', ' .
            'is_pro = '            . b($this->is_pro)           . ', ' .
            'status = '            . qq($this->status)          . ' ' .
            // entry_date intentionally not set here
            'where id = '          . n($this->id)
        );

        return $ok;
    }
}

?>
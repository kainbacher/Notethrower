<?php

include_once('../Includes/Config.php');
include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/ProjectAttribute.php');
include_once('../Includes/DB/ProjectGenre.php');

// dao for pp_user table
class User {
    var $id; // -1 means unknown
    var $username;
    var $password_md5;
    var $email_address;
    var $name;
    var $artist_info;
    var $latitude;
    var $longitude;
    var $additional_info; // currently hidden
    var $video_url;
    var $influences;
    var $image_filename;
    var $webpage_url;
    var $facebook_url;
    var $twitter_username;
    var $paypal_account;
    var $activity_points;
    var $is_artist; // if true, the user is both a fan and an artist, if false the user is only a fan
    var $is_pro; // a pro user pays for some extra services and has more features available than a free user
    var $is_proudloudr; // a proudloudr user has additional rights compared to a regular pro user
    var $is_editor; // editors can change certain texts on the page
    var $is_admin; // admins can change certain parameters, block/delete users, etc.
    var $wants_newsletter; // if true, the user wants to receive oneloudr newsletters
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
            $logger->info('auth cookie not found');
            return null;
        }
    }

    function fetch_all_from_to($from, $to, $show_inactive_items, $include_unknown_artist, $include_fans = false,
            $orderByClause = 'order by u.name asc') {

        $objs = array();

        $constraints = array();
        if ($show_inactive_items) {
            $constraints[] = 'u.status in ("active", "inactive")';
        } else {
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

    // attention: all changes here need to be done in getResultsCountForSearch(), too!
    function fetchForSearch($from, $length, $name, $bio, $attributeId, $genreId) {
        $objs = array();

        $result = _mysql_query(
                'select u.* ' .
                'from pp_user u ' .
                ($genreId ? 'join pp_user_genre ug on ug.user_id = u.id ' : '') .
                ($attributeId ? 'join pp_user_attribute ua on ua.user_id = u.id ' : '') .
                'where u.status = "active" ' .
                ($name ? 'and u.name like ' . qqLike($name) . ' ' : '') .
                ($bio ? 'and (u.artist_info like ' . qqLike($bio) . ' or u.influences like ' . qqLike($bio) . ') ' : '') .
                ($genreId ? 'and ug.genre_id = ' . n($genreId) . ' ' : '') .
                ($attributeId ? 'and ua.attribute_id = ' . n($attributeId) . ' ' : '') .
                'order by entry_date desc ' .
                ($length ? 'limit ' . n($from) . ', ' . n($length) : '')
        );

        while ($row = mysql_fetch_array($result)) {
            $u = new User();
            $u = User::_read_row($u, $row);
            $objs[] = $u;
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
            'and u.status = "active" ' .
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
            'and status = "active" ' .
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

    function fetch_for_email_address_and_password($email, $password) {
        $result = _mysql_query(
            'select * ' .
            'from pp_user ' .
            'where email_address = ' . qq($email) . ' ' .
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

    function fetchAllThatOfferSkillsForUsersProjects(&$user) {
        // fetch all attributes of all projects of the user
        $attribute_id_list = array();
        $paList = ProjectAttribute::fetchAllWithStatusOfProjectsOfUser($user->id, 'needs');
        foreach ($paList as $pa) {
            if (!isset($attribute_id_list[$pa->project_id])) $attribute_id_list[$pa->project_id] = array();
            $attribute_id_list[$pa->project_id][] = $pa->attribute_id;
        }

        // fetch all genres of all projects of the user
        $projects_genre_id_list = array();
        $pgList = ProjectGenre::fetchAllOfProjectsOfUser($user->id);
        foreach ($pgList as $pg) {
            if (!isset($projects_genre_id_list[$pg->project_id])) $projects_genre_id_list[$pg->project_id] = array();
            $projects_genre_id_list[$pg->project_id][] = $pg->genre_id;
        }

        // create a map of attributes and genres per project
        $projects = Project::fetch_all_unfinished_projects_of_user($user->id);
        $clauses = array();
        foreach ($projects as $proj) {
            if (isset($attribute_id_list[$proj->id]) && isset($projects_genre_id_list[$proj->id])) {
                $clauses[] = '(' .
                             'ua.attribute_id in (' . implode(',', $attribute_id_list[$proj->id]) . ') ' .
                             'and (ug.genre_id is null or ug.genre_id in (' . implode(',', $projects_genre_id_list[$proj->id]) . '))' .
                             ')';
            }
        }

        $objs = array();

        if (count($clauses) > 0) {
            $result = _mysql_query(
                'select u.*, a.id as attribute_id, a.name as attribute_name ' .
                'from pp_user u ' .
                'join pp_user_attribute ua on ua.user_id = u.id ' .
                'join pp_attribute a on a.id = ua.attribute_id ' .
                'left join pp_user_genre ug on ug.user_id = u.id ' .
                'where ' .
                '(' . implode("\n" . ' or ', $clauses) . ') ' .
                'and ua.status = "offers" ' .
                'and u.status = "active" ' .
                'and u.id != ' . n($user->id) . ' ' .
                // FIXME - limit/paging?
                'group by u.id ' .
                'order by u.name asc' // FIXME - sort by rating as soon as we have one?
            );

            $previousUid = null;
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

    function fetchAllThatOfferSkillsForProjectId($ownerUserId, $projectId, $additionalAttributes = null) {
        // fetch all attributes of project
        $attribute_id_list = array();
        $paList = ProjectAttribute::fetchAllWithStatusOfProject($projectId, 'needs');

        foreach ($paList as $pa) {
            if (!isset($attribute_id_list[$pa->project_id])) $attribute_id_list[$pa->project_id] = array();
            $attribute_id_list[$pa->project_id][] = $pa->attribute_id;
        }

        if($additionalAttributes){
            $attribute_id_list[$projectId] = array_merge($attribute_id_list[$projectId], $additionalAttributes);
        }

        // fetch all genres of project
        $projects_genre_id_list = array();
        $pgList = ProjectGenre::fetchAllOfProject($projectId);
        foreach ($pgList as $pg) {
            if (!isset($projects_genre_id_list[$pg->project_id])) $projects_genre_id_list[$pg->project_id] = array();
            $projects_genre_id_list[$pg->project_id][] = $pg->genre_id;
        }

        // fetch all collaborators of this project
        $collaborators = ProjectUserVisibility::getAllUserIdsForProjectId($projectId);

        $objs = array();

        // TODO - all this can most likely be done cleaner with a single select

        if (count($attribute_id_list) > 0 && count($projects_genre_id_list) > 0) {
            $result = _mysql_query(
                'select u.*, a.id as attribute_id, a.name as attribute_name ' .
                'from pp_user u ' .
                'join pp_user_attribute ua on ua.user_id = u.id ' .
                'join pp_attribute a on a.id = ua.attribute_id ' .
                'left join pp_user_genre ug on ug.user_id = u.id ' .
                'where ' .
                'ua.attribute_id in (' . implode(',', $attribute_id_list[$projectId]) . ') ' .
                'and (ug.genre_id is null or ug.genre_id in (' . implode(',', $projects_genre_id_list[$projectId]) . ')) ' .
                'and ua.status = "offers" ' .
                'and u.status = "active" ' .
                'and u.id != ' . n($ownerUserId) . ' ' .
                // FIXME - limit/paging?
                'group by u.id ' .
                'order by u.name asc' // FIXME - sort by rating as soon as we have one?
            );

            $previousUid = null;
            while ($row = mysql_fetch_array($result)) {
                if (!in_array($row['id'], $collaborators)) { // ignore users which already collaborate
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
            }

            mysql_free_result($result);
        }

        return $objs;
    }

    function _read_row($a, $row) {
        $a->id               = $row['id'];
        $a->username         = $row['username'];
        $a->password_md5     = $row['password_md5'];
        $a->email_address    = $row['email_address'];
        $a->name             = $row['name'];
        $a->artist_info      = $row['artist_info'];
        $a->latitude         = $row['latitude'];
        $a->longitude        = $row['longitude'];
        $a->additional_info  = $row['additional_info'];
        $a->video_url        = $row['video_url'];
        $a->influences       = $row['influences'];
        $a->image_filename   = $row['image_filename'];
        $a->webpage_url      = $row['webpage_url'];
        $a->facebook_url     = $row['facebook_url'];
        $a->twitter_username = $row['twitter_username'];
        $a->paypal_account   = $row['paypal_account'];
        $a->activity_points  = $row['activity_points'];
        $a->is_artist        = $row['is_artist'];
        $a->is_pro           = $row['is_pro'];
        $a->is_proudloudr    = $row['is_proudloudr'];
        $a->is_editor        = $row['is_editor'];
        $a->is_admin         = $row['is_admin'];
        $a->wants_newsletter = $row['wants_newsletter'];
        $a->status           = $row['status'];
        $a->entry_date       = reformat_sql_date($row['entry_date']);

        return $a;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_user ' .
            '(' .
            'id               int(10)      not null auto_increment, ' .
            'username         varchar(50)  not null, ' .
            'password_md5     varchar(50)  not null, ' .
            'email_address    varchar(255) not null, ' .
            'name             varchar(50)  not null, ' .
            'artist_info      text, ' .
            'latitude         double, ' .
            'longitude        double, ' .
            'additional_info  text, ' .
            'video_url        varchar(255), ' .
            'influences       text, ' .
            'image_filename   varchar(255), ' .
            'webpage_url      varchar(255), ' .
            'facebook_url     varchar(255), ' .
            'twitter_username varchar(255), ' .
            'paypal_account   varchar(255), ' .
            'activity_points  int(10), ' .
            'is_artist        tinyint(1)   not null, ' .
            'is_pro           tinyint(1)   not null, ' .
            'is_proudloudr    tinyint(1)   not null default 0, ' .
            'is_editor        tinyint(1)   not null default 0, ' .
            'is_admin         tinyint(1)   not null default 0, ' .
            'wants_newsletter tinyint(1)   not null default 0, ' .
            'status           varchar(20)  not null, ' .
            'entry_date       datetime     not null default "1970-01-01 00:00:00", ' .
            'primary key (id), ' .
            'key name (name), ' .
            'unique key username (username), ' .
            'unique key email (email_address), ' .
            'key id_pwd (id, password_md5), ' .
            'unique key username_pwd (username, password_md5), ' .
            'unique key email_pwd (email_address, password_md5), ' .
            'key entry_date (entry_date) ' .
            ') default charset=utf8'
        );

        if ($ok) {
            $test_record = User::fetch_for_id(-1);
            if (!$test_record || !$test_record->id) {
                $ok = _mysql_query(
                    'insert into pp_user (id, username, password_md5, email_address, name, artist_info, ' .
                    'latitude, longitude, additional_info, video_url, influences,' .
                    'image_filename, webpage_url, facebook_url, twitter_username, paypal_account, activity_points, ' .
                    'is_artist, is_pro, is_proudloudr, is_editor, is_admin, wants_newsletter, status, entry_date) ' .
                    'values (-1, "_unknown_artist", "' . md5('dummyPwd') . '", "", "Unknown Artist", "", null, null, ' .
                    '"", "", "", "", "", "", "", "", 0, 1, 0, 0, 0, 0, 0, "inactive", now())'
                );
            }
        }

        return $ok;
    }

    // FIXME - needed?
    // attention: all changes here need to be done in fetchForSearch(), too!
    function getResultsCountForSearch($name, $bio, $attributeId, $genreId) {
        $result = _mysql_query(
                'select count(*) as cnt ' .
                'from pp_user u ' .
                ($genreId ? 'join pp_user_genre ug on ug.user_id = u.id ' : '') .
                ($attributeId ? 'join pp_user_attribute ua on ua.user_id = u.id ' : '') .
                'where u.status = "active" ' .
                ($name ? 'and u.name like ' . qqLike($name) . ' ' : '') .
                ($bio ? 'and (u.artist_info like ' . qqLike($bio) . ' or u.influences like ' . qqLike($bio) . ') ' : '') .
                ($genreId ? 'and ug.genre_id = ' . n($genreId) . ' ' : '') .
                ($attributeId ? 'and ua.attribute_id = ' . n($attributeId) . ' ' : '')
        );

        $count = 0;
        if ($row = mysql_fetch_array($result)) {
            $count = $row['cnt'];
        }

        mysql_free_result($result);

        return $count;
    }

    function count_all($count_inactive_items, $include_unknown_artist) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_user ' .
            ($count_inactive_items ? 'where status in ("active", "inactive") ' : 'where status = "active" ') .
            ($include_unknown_artist ? '' : 'and id >= 0')
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function getAllNewsletterRecipientEmails() {
        $result = _mysql_query(
            'select email_address ' .
            'from pp_user ' .
            'where status = "active" ' .
            'and email_address is not null ' .
            'and email_address != "" ' .
            'and wants_newsletter = 1'
        );

        $emails = array();
        
        while ($row = mysql_fetch_array($result)) {
            $emails[] = $row['email_address'];
        }
        
        mysql_free_result($result);

        return $emails;
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
            '(username, password_md5, email_address, name, artist_info, latitude, longitude, additional_info, ' .
            'video_url, influences, image_filename, webpage_url, facebook_url, twitter_username, paypal_account, ' .
            'activity_points, is_artist, is_pro, is_proudloudr, is_editor, is_admin, wants_newsletter, status, entry_date) ' .
            'values (' .
            qq($this->username)         . ', ' .
            qq($this->password_md5)     . ', ' .
            qq($this->email_address)    . ', ' .
            qq($this->name)             . ', ' .
            qq($this->artist_info)      . ', ' .
            n($this->latitude)          . ', ' .
            n($this->longitude)         . ', ' .
            qq($this->additional_info)  . ', ' .
            qq($this->video_url)        . ', ' .
            qq($this->influences)       . ', ' .
            qq($this->image_filename)   . ', ' .
            qq($this->webpage_url)      . ', ' .
            qq($this->facebook_url)     . ', ' .
            qq($this->twitter_username) . ', ' .
            qq($this->paypal_account)   . ', ' .
            n($this->activity_points)   . ', ' .
            b($this->is_artist)         . ', ' .
            b($this->is_pro)            . ', ' .
            b($this->is_proudloudr)     . ', ' .
            b($this->is_editor)         . ', ' .
            b($this->is_admin)          . ', ' .
            b($this->wants_newsletter)  . ', ' .
            qq($this->status)           . ', ' .
            qq(formatMysqlDatetime())   .
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
            'set username = '      . qq($this->username)         . ', ' .
            'password_md5 = '      . qq($this->password_md5)     . ', ' .
            'email_address = '     . qq($this->email_address)    . ', ' .
            'name = '              . qq($this->name)             . ', ' .
            'artist_info = '       . qq($this->artist_info)      . ', ' .
            'latitude = '          . n($this->latitude)          . ', ' .
            'longitude = '         . n($this->longitude)         . ', ' .
            'additional_info = '   . qq($this->additional_info)  . ', ' .
            'video_url = '         . qq($this->video_url)        . ', ' .
            'influences = '        . qq($this->influences)       . ', ' .
            'image_filename = '    . qq($this->image_filename)   . ', ' .
            'webpage_url = '       . qq($this->webpage_url)      . ', ' .
            'facebook_url = '      . qq($this->facebook_url)     . ', ' .
            'twitter_username = '  . qq($this->twitter_username) . ', ' .
            'paypal_account = '    . qq($this->paypal_account)   . ', ' .
            'activity_points = '   . n($this->activity_points)   . ', ' .
            'is_artist = '         . b($this->is_artist)         . ', ' .
            'is_pro = '            . b($this->is_pro)            . ', ' .
            'is_proudloudr = '     . b($this->is_proudloudr)     . ', ' .
            'is_editor = '         . b($this->is_editor)         . ', ' .
            'is_admin = '          . b($this->is_admin)          . ', ' .
            'wants_newsletter = '  . b($this->wants_newsletter)  . ', ' .
            'status = '            . qq($this->status)           . ' ' .
            // entry_date intentionally not set here
            'where id = '          . n($this->id)
        );

        return $ok;
    }
}

?>

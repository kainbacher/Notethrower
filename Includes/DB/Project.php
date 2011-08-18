<?php

include_once('../Includes/Config.php');
include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/ProjectAttribute.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/ProjectUserVisibility.php');

// dao for pp_project table
class Project {
    var $id;
    var $user_id;
    var $title; // the project name
    var $sorting; // not used at the moment but maybe useful in the future
    var $type; // old: original or remix, new: drop this! but only after the old live data has been successfully converted into the new projects concept!
    var $originating_user_id; // new: drop this! but only after the old live data has been successfully converted into the new projects concept!
    var $price;
    var $currency;
    var $rating_count;
    var $rating_value;
    var $competition_points; // when two songs are compared and one is chosen as the better song, its comp. points are incremented by 1
    var $genres; // new: replace with association table entry to genre table
    var $visibility; // new: drop this? maybe useful in the future (pro feature - private projects)
    var $playback_count;
    var $download_count;
    var $status; // old: newborn, active and inactive (mp3 file missing) - new: newborn, active, inactive (mp3 file missing) and finished (watch out to change all $show_inactive_items stuff!)
    var $entry_date;
    var $containsOthers; // check this if we need it here
    var $needsOthers; // check this if we need it here
    var $additionalInfo;

    // non-table fields
    var $user_name;
    var $user_img_filename;
    var $mp3_filename;

    // constructors
    // ------------
    function Project() {
    }

    function fetch_newest_from_to($from, $to, $show_inactive_items, $ignore_visibility, $visitorUserId) {
        $objs = array();

        $result = null;

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select distinct t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_project t, pp_user a, pp_project_user_visibility puv ' .
                ($show_inactive_items ? 'where t.status in ("finished", "active", "inactive") ' : 'where t.status in ("finished", "active") ') .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = puv.project_id and puv.user_id = ' . n($visitorUserId) . ') ') .
                'and t.user_id = a.id ' .
                'and t.id = puv.project_id ' .
                'order by t.entry_date desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );

        } else {
            $result = _mysql_query(
                'select t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_project t, pp_user a ' .
                ($show_inactive_items ? 'where t.status in ("finished", "active", "inactive") ' : 'where t.status in ("finished", "active") ') .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                'and t.user_id = a.id ' .
                'order by t.entry_date desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new Project();
            $a = Project::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_most_downloaded_from_to($from, $to, $show_inactive_items, $ignore_visibility, $visitorUserId) {
        $objs = array();

        $result = null;

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select distinct t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_project t, pp_user a, pp_project_user_visibility puv ' .
                ($show_inactive_items ? 'where t.status in ("finished", "active", "inactive") ' : 'where t.status in ("finished", "active") ') .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = puv.project_id and puv.user_id = ' . n($visitorUserId) . ') ') .
                'and t.user_id = a.id ' .
                'and t.id = puv.project_id ' .
                'order by t.download_count desc, t.entry_date desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );

        } else {
            $result = _mysql_query(
                'select t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_project t, pp_user a ' .
                ($show_inactive_items ? 'where t.status in ("finished", "active", "inactive") ' : 'where t.status in ("finished", "active") ') .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                'and t.user_id = a.id ' .
                'order by t.download_count desc, t.entry_date desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new Project();
            $a = Project::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_all_originals_of_user_id_from_to($aid, $from, $to, $show_inactive_items, $ignore_visibility, $visitorUserId) {
        $objs = array();

        $result = null;

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select distinct t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_project t, pp_user a, pp_project_user_visibility puv ' .
                'where t.user_id = ' . n($aid) . ' ' .
                'and t.type = "original" ' .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = puv.project_id and puv.user_id = ' . n($visitorUserId) . ') ') .
                ($show_inactive_items ? 'and t.status in ("finished", "active", "inactive") ' : 'and t.status in ("finished", "active") ') .
                'and t.user_id = a.id ' .
                'and t.id = puv.project_id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );

        } else {
            $result = _mysql_query(
                'select t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_project t, pp_user a ' .
                'where t.user_id = ' . n($aid) . ' ' .
                'and t.type = "original" ' .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                ($show_inactive_items ? 'and t.status in ("finished", "active", "inactive") ' : 'and t.status in ("finished", "active") ') .
                'and t.user_id = a.id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new Project();
            $a = Project::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_all_remixes_of_user_id_from_to($aid, $from, $to, $show_inactive_items, $ignore_visibility, $visitorUserId) {
        $objs = array();

        $result = null;

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select distinct t.* ' .
                'from pp_project t, pp_user a, pp_project_user_visibility puv ' .
                'where t.user_id = ' . n($aid) . ' ' .
                'and t.type = "remix" ' .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = puv.project_id and puv.user_id = ' . n($visitorUserId) . ') ') .
                ($show_inactive_items ? 'and t.status in ("finished", "active", "inactive") ' : 'and t.status in ("finished", "active") ') .
                'and t.originating_user_id = a.id ' .
                'and t.id = puv.project_id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );

        } else {
            $result = _mysql_query(
                'select t.* ' .
                'from pp_project t, pp_user a ' .
                'where t.user_id = ' . n($aid) . ' ' .
                'and t.type = "remix" ' .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                ($show_inactive_items ? 'and t.status in ("finished", "active", "inactive") ' : 'and t.status in ("finished", "active") ') .
                'and t.originating_user_id = a.id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new Project();
            $a = Project::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_all_remixes_for_originating_user_id_from_to($oaid, $from, $to, $show_inactive_items, $ignore_visibility, $visitorUserId) {
        $objs = array();

        $result = null;

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select distinct t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_project t, pp_user a, pp_project_user_visibility puv ' .
                'where t.originating_user_id = ' . n($oaid) . ' ' .
                'and t.type = "remix" ' .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = puv.project_id and puv.user_id = ' . n($visitorUserId) . ') ') .
                ($show_inactive_items ? 'and t.status in ("finished", "active", "inactive") ' : 'and t.status in ("finished", "active") ') .
                'and t.user_id = a.id ' .
                'and t.id = puv.project_id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );

        } else {
            $result = _mysql_query(
                'select t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_project t, pp_user a ' .
                'where t.originating_user_id = ' . n($oaid) . ' ' .
                'and t.type = "remix" ' .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                ($show_inactive_items ? 'and t.status in ("finished", "active", "inactive") ' : 'and t.status in ("finished", "active") ') .
                'and t.user_id = a.id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new Project();
            $a = Project::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_project_details($tid, $visitorUserId) {
        $result = null;

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select distinct t.* ' .
                'from pp_project t, pp_project_user_visibility puv ' .
                'where t.id = ' . n($tid) . ' ' .
                'and (t.visibility = "public" or t.visibility = "private" and t.id = puv.project_id and puv.user_id = ' . n($visitorUserId) . ') ' .
                'and t.status in ("finished", "active") ' .
                'and t.id = puv.project_id'
            );

        } else {
            $result = _mysql_query(
                'select t.* ' .
                'from pp_project t ' .
                'where t.id = ' . n($tid) . ' ' .
                'and t.visibility = "public" ' .
                'and t.status in ("finished", "active")'
            );
        }

        $a = null;

        if ($row = mysql_fetch_array($result)) {
            $a = new Project();
            $a = Project::_read_row($a, $row);
        }

        mysql_free_result($result);

        return $a;
    }

    function fetch_for_id($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_project ' .
            'where id = ' . n($id)
        );

        $a = new Project();

        if ($row = mysql_fetch_array($result)) {
            $a = Project::_read_row($a, $row);
        }

        mysql_free_result($result);

        return $a;
    }

    function fetchForSearch($from, $length, $userOrTitle, $needsAttributeIds, $containsAttributeIds, $needsOthers, $containsOthers, $genres, $ignore_visibility, $show_inactive_items, $visitorUserId) {
        $objs = array();
        $result = _mysql_query(
                'select distinct t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_project t join pp_user a on t.user_id = a.id join pp_project_user_visibility puv on puv.project_id=t.id where 1=1 ' .
                ($userOrTitle == '' ? '' : 'and (a.name like ' . qqLike($userOrTitle) . ' or t.title like ' . qqLike($userOrTitle) . ') ') .
                ($needsOthers == '' ? '' : 'and t.needs_others like ' . qqLike($needsOthers) . ' ') .
                ($containsOthers == '' ? '' : 'and t.contains_others like ' . qqLike($containsOthers) . ' ') .
                ($show_inactive_items ? 'and t.status in ("finished", "active", "inactive") ' : 'and t.status in ("finished", "active") ') .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = puv.project_id and puv.user_id = ' . n($visitorUserId) . ') ') .
                ($needsAttributeIds == '' ? '' : ' and t.id=(select max(attr.project_id) from pp_project_attribute attr where attr.project_id = t.id and attr.attribute_id in (' .
                nList($needsAttributeIds) . ') and attr.status="needs") ') .
                ($containsAttributeIds == '' ? '' : ' and t.id=(select max(attr.project_id) from pp_project_attribute attr where attr.project_id = t.id and attr.attribute_id in (' .
                nList($containsAttributeIds) . ') and attr.status="contains") ') .
                (count($genres) == 0 ? '' : ' and t.genres in (' .
                qqList($genres) . ') ') .
                ' order by entry_date desc ' .
                'limit ' . n($from) . ', ' . n($length)
            );


        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new Project();
            $a = Project::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchCountForSearch($userOrTitle, $needsAttributeIds, $containsAttributeIds, $needsOthers, $containsOthers, $genres, $ignore_visibility, $show_inactive_items, $visitorUserId) {
        $objs = array();
        $result = _mysql_query(
                'select count(distinct t.id) as cnt ' .
                'from pp_project t join pp_user a on t.user_id = a.id join pp_project_user_visibility puv on puv.project_id=t.id where 1=1 ' .
                ($userOrTitle == '' ? '' : 'and (a.name like ' . qqLike($userOrTitle) . ' or t.title like ' . qqLike($userOrTitle) . ') ') .
                ($needsOthers == '' ? '' : 'and t.needs_others like ' . qqLike($needsOthers) . ' ') .
                ($containsOthers == '' ? '' : 'and t.contains_others like ' . qqLike($containsOthers) . ' ') .
                ($show_inactive_items ? 'and t.status in ("finished", "active", "inactive") ' : 'and t.status in ("finished", "active") ') .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = puv.project_id and puv.user_id = ' . n($visitorUserId) . ') ') .
                ($needsAttributeIds == '' ? '' : ' and t.id=(select max(attr.project_id) from pp_project_attribute attr where attr.project_id = t.id and attr.attribute_id in (' .
                nList($needsAttributeIds) . ') and attr.status="needs") ') .
                ($containsAttributeIds == '' ? '' : ' and t.id=(select max(attr.project_id) from pp_project_attribute attr where attr.project_id = t.id and attr.attribute_id in (' .
                nList($containsAttributeIds) . ') and attr.status="contains") ') .
                (count($genres) == 0 ? '' : ' and t.genres in (' .
                qqList($genres) . ') ') .
                ' order by t.entry_date desc '
            );


        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function fetch_all_private_projects_the_user_can_access($from, $to, $aid) {
        $objs = array();

        $result = _mysql_query(
            'select t.*, a.name as user_name, a.image_filename as user_img_filename ' .
            'from pp_project t, pp_user a, pp_project_user_visibility puv ' .
            'where puv.user_id = ' . n($aid) . ' ' .
            'and puv.project_id = t.id ' .
            'and t.status in ("finished", "active") ' .
            'and t.visibility = "private" ' .
            'and t.user_id != ' . n($aid) . ' ' .
            'and t.user_id = a.id ' .
            'order by t.entry_date desc ' .
            'limit ' . $from . ', ' . ($to - $from + 1)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new Project();
            $a = Project::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchAllNewbornProjectIdsForUserId($aid) {
        if (!$aid) return array();

        $result = _mysql_query(
            'select id ' .
            'from pp_project ' .
            'where user_id = ' . n($aid) . ' ' .
            'and status = "newborn"'
        );

        $idList = array();

        if ($row = mysql_fetch_array($result)) {
            $idList[] = $row['id'];
        }

        mysql_free_result($result);

        return $idList;
    }

    function fetchRandomPublicFinishedProject($genre = null, $excludeProjectId = null) {
        $whereClauseAddon = '';
        if (!is_null($excludeProjectId)) {
            $whereClauseAddon .= 'and t.id != ' . n($excludeProjectId) . ' ';
        }
        if ($genre) {
            $whereClauseAddon .= 'and t.genres like ' . qqLike($genre) . ' ';
        }

        $result = _mysql_query(
            'select t.*, u.name as user_name, u.image_filename as user_img_filename, f.filename as mp3_filename ' .
            'from pp_project t, pp_project_file f, pp_user u ' .
            'where t.status = "finished" ' .
            'and t.visibility = "public" ' .
            'and t.user_id = u.id ' .
            'and t.id = f.project_id ' .
            'and f.orig_filename like "%.mp3" ' .
            $whereClauseAddon .
            'order by rand() ' .
            'limit 1'
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new Project();
            $a = Project::_read_row($a, $row);
            mysql_free_result($result);
            return $a;
        }

        mysql_free_result($result);
        return null;
    }

    function _read_row($a, $row) {
        $a->id                        = $row['id'];
        $a->user_id                   = $row['user_id'];
        $a->title                     = $row['title'];
        $a->price                     = $row['price'];
        $a->currency                  = $row['currency'];
        $a->sorting                   = $row['sorting'];
        $a->type                      = $row['type'];
        $a->originating_user_id       = $row['originating_user_id'];
        $a->rating_count              = $row['rating_count'];
        $a->rating_value              = $row['rating_value'];
        $a->competition_points        = $row['competition_points'];
        $a->genres                    = $row['genres'];
        $a->visibility                = $row['visibility'];
        $a->playback_count            = $row['playback_count'];
        $a->download_count            = $row['download_count'];
        $a->status                    = $row['status'];
        $a->containsOthers            = $row['contains_others'];
        $a->needsOthers               = $row['needs_others'];
        $a->additionalInfo            = $row['additional_info'];
        $a->entry_date                = reformat_sql_date($row['entry_date']);

        if (isset($row['user_name']))             $a->user_name             = $row['user_name'];
        if (isset($row['user_img_filename']))     $a->user_img_filename     = $row['user_img_filename'];
        if (isset($row['mp3_filename']))          $a->mp3_filename          = $row['mp3_filename'];

        return $a;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_project ' .
            '(' .
            'id                        int(10)      not null auto_increment, ' .
            'user_id                   int(10)      not null, ' .
            'title                     varchar(255) not null, ' .
            'price                     float        not null, ' .
            'currency                  varchar(3)   not null, ' .
            'sorting                   int(5), ' .
            'type                      varchar(10)  not null, ' .
            'originating_user_id       int(10), ' .
            'rating_count              int(10)      not null, ' .
            'rating_value              float        not null, ' .
            'competition_points        int(10)      not null, ' .
            'genres                    varchar(255), ' .
            'visibility                varchar(10)  not null, ' .
            'playback_count            int(10)      not null, ' .
            'download_count            int(10)      not null, ' .
            'status                    varchar(20)  not null, ' .
            'contains_others           varchar(255), ' .
            'needs_others              varchar(255), ' .
            'additional_info           text, ' .
            'entry_date                datetime     not null default "1970-01-01 00:00:00", ' .
            'primary key (id), ' .
            'key user_id (user_id), ' .
            'key type (type), ' .
            'key rating_value (rating_value), ' .
            'key entry_date (entry_date) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function count_all($count_inactive_items, $ignore_visibility, $visitorUserId) {
        $result = null;

//        $result = _mysql_query(
//            'select count(*) as cnt ' .
//            'from pp_project t, pp_project_user_visibility puv ' .
//            'where t.id = puv.project_id ' .
//            ($ignore_visibility ? '' : 'and (t.visibility = "public" or (t.visibility = "private" and t.id = puv.project_id and puv.user_id = ' . n($visitorUserId) . ')) ') .
//            ($count_inactive_items ? 'and t.status in ("finished", "active", "inactive")' : 'and t.status in ("finished", "active")')
//        );

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select count(distinct t.id) as cnt ' .
                'from pp_project t, pp_project_user_visibility puv ' .
                'where t.id = puv.project_id ' .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = puv.project_id and puv.user_id = ' . n($visitorUserId) . ') ') .
                ($count_inactive_items ? 'and t.status in ("finished", "active", "inactive")' : 'and t.status in ("finished", "active")')
            );

        } else {
            $result = _mysql_query(
                'select count(*) as cnt ' .
                'from pp_project t ' .
                'where 1=1 ' .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                ($count_inactive_items ? 'and t.status in ("finished", "active", "inactive")' : 'and t.status in ("finished", "active")')
            );
        }

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function count_all_private_projects_the_user_can_access($aid) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_project t, pp_project_user_visibility puv ' .
            'where puv.user_id = ' . n($aid) . ' ' .
            'and puv.project_id = t.id ' .
            'and t.user_id != ' . n($aid) . ' ' .
            'and t.status in ("finished", "active") ' .
            'and t.visibility = "private"'
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function delete_with_id($id) {
        global $logger;

        if (!$id) return;

        ProjectFile::delete_all_with_project_id($id);
        ProjectUserVisibility::delete_all_with_project_id($id);
        ProjectAttribute::deleteForProjectId($id);

        $logger->info('deleting project record with id: ' . $id);

        return _mysql_query(
            'delete from pp_project ' .
            'where id = ' . n($id)
        );
    }

    // object methods
    // --------------
    function getPreviewMp3Url() {
        if (!$this->mp3_filename) {
            show_fatal_error_and_exit('$this->mp3_filename is not set!');
        }

        $userSubdir = null;
        if (ini_get('safe_mode')) {
            $userSubdir = ''; // in safe mode we're not allowed to create directories
        } else {
            $userSubdir = md5('Wuizi' . $this->user_id);
        }

        return $GLOBALS['BASE_URL'] . 'Backend/preview.php?song=' . $this->mp3_filename;
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
            'insert into pp_project ' .
            '(user_id, title, ' .
            'price, currency, sorting, type, originating_user_id, rating_count, ' .
            'rating_value, competition_points, genres, visibility, playback_count, download_count, ' .
            'status, contains_others, needs_others, additional_info, entry_date) ' .
            'values (' .
            n($this->user_id)                    . ', ' .
            qq($this->title)                     . ', ' .
            qq($this->price)                     . ', ' .
            qq($this->currency)                  . ', ' .
            n($this->sorting)                    . ', ' .
            qq($this->type)                      . ', ' .
            n($this->originating_user_id)        . ', ' .
            n($this->rating_count)               . ', ' .
            n($this->rating_value)               . ', ' .
            n($this->competition_points)         . ', ' .
            qq($this->genres)                    . ', ' .
            qq($this->visibility)                . ', ' .
            n($this->playback_count)             . ', ' .
            n($this->download_count)             . ', ' .
            qq($this->status)                    . ', ' .
            qq($this->containsOthers)            . ', ' .
            qq($this->needsOthers)               . ', ' .
            qq($this->additionalInfo)            . ', ' .
            'now()'                              .
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
            'update pp_project ' .
            'set user_id = '               . n($this->user_id)                    . ', ' .
            'title = '                     . qq($this->title)                     . ', ' .
            'price = '                     . qq($this->price)                     . ', ' .
            'currency = '                  . qq($this->currency)                  . ', ' .
            'sorting = '                   . n($this->sorting)                    . ', ' .
            'type = '                      . qq($this->type)                      . ', ' .
            'originating_user_id = '       . n($this->originating_user_id)        . ', ' .
            'rating_count = '              . n($this->rating_count)               . ', ' .
            'rating_value = '              . n($this->rating_value)               . ', ' .
            'competition_points = '        . n($this->competition_points)         . ', ' .
            'genres = '                    . qq($this->genres)                    . ', ' .
            'visibility = '                . qq($this->visibility)                . ', ' .
            'playback_count = '            . n($this->playback_count)             . ', ' .
            'download_count = '            . n($this->download_count)             . ', ' .
            'status = '                    . qq($this->status)                    . ', ' .
            'contains_others = '           . qq($this->containsOthers)            . ', ' .
            'needs_others = '              . qq($this->needsOthers)               . ', ' .
            'additional_info = '           . qq($this->additionalInfo)            . ' ' .
            // entry_date intentionally not set here
            'where id = '                  . n($this->id)
        );

        return $ok;
    }
}

?>
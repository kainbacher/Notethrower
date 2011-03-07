<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/AudioTrackFile.php');
include_once('../Includes/DB/AudioTrackUserVisibility.php');
include_once('../Includes/DB/AudioTrackAudioTrackAttribute.php');

// dao for pp_audio_track table
class AudioTrack {
    var $id;
    var $user_id;
    var $user_name; // not a field in this table
    var $user_img_filename; // not a field in this table
    var $title;
    var $preview_mp3_filename;
    var $orig_preview_mp3_filename;
    var $sorting;
    var $type; // original or remix
    var $is_full_song;
    var $originating_user_id;
    var $originating_user_name; // not a field in this table
    var $parent_track_id;
    var $price;
    var $currency;
    var $rating_count;
    var $rating_value;
    var $competition_points; // when two songs are compared and one is chosen as the better song, its comp. points are incremented by 1
    var $genres;
    var $visibility;
    var $playback_count;
    var $download_count;
    var $originator_notified;
    var $status; // newborn, active, inactive
    var $entry_date;
    var $containsOthers;
    var $needsOthers;
    var $additionalInfo;

    // constructors
    // ------------
    function AudioTrack() {
    }

    function fetch_newest_from_to($from, $to, $show_inactive_items, $ignore_visibility, $visitorUserId) {
        $objs = array();

        $result = null;

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select distinct t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_audio_track t, pp_user a, pp_audio_track_user_visibility atav ' .
                ($show_inactive_items ? 'where t.status in ("active", "inactive") ' : 'where t.status = "active" ') .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ') ') .
                'and t.user_id = a.id ' .
                'and t.id = atav.track_id ' .
                'order by t.entry_date desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );

        } else {
            $result = _mysql_query(
                'select t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_audio_track t, pp_user a ' .
                ($show_inactive_items ? 'where t.status in ("active", "inactive") ' : 'where t.status = "active" ') .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                'and t.user_id = a.id ' .
                'order by t.entry_date desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrack();
            $a = AudioTrack::_read_row($a, $row);

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
                'from pp_audio_track t, pp_user a, pp_audio_track_user_visibility atav ' .
                ($show_inactive_items ? 'where t.status in ("active", "inactive") ' : 'where t.status = "active" ') .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ') ') .
                'and t.user_id = a.id ' .
                'and t.id = atav.track_id ' .
                'order by t.download_count desc, t.entry_date desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );

        } else {
            $result = _mysql_query(
                'select t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_audio_track t, pp_user a ' .
                ($show_inactive_items ? 'where t.status in ("active", "inactive") ' : 'where t.status = "active" ') .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                'and t.user_id = a.id ' .
                'order by t.download_count desc, t.entry_date desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrack();
            $a = AudioTrack::_read_row($a, $row);

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
                'from pp_audio_track t, pp_user a, pp_audio_track_user_visibility atav ' .
                'where t.user_id = ' . n($aid) . ' ' .
                'and t.type = "original" ' .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ') ') .
                ($show_inactive_items ? 'and t.status in ("active", "inactive") ' : 'and t.status = "active" ') .
                'and t.user_id = a.id ' .
                'and t.id = atav.track_id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );

        } else {
            $result = _mysql_query(
                'select t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_audio_track t, pp_user a ' .
                'where t.user_id = ' . n($aid) . ' ' .
                'and t.type = "original" ' .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                ($show_inactive_items ? 'and t.status in ("active", "inactive") ' : 'and t.status = "active" ') .
                'and t.user_id = a.id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrack();
            $a = AudioTrack::_read_row($a, $row);

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
                'select distinct t.*, a.name as originating_user_name ' .
                'from pp_audio_track t, pp_user a, pp_audio_track_user_visibility atav ' .
                'where t.user_id = ' . n($aid) . ' ' .
                'and t.type = "remix" ' .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ') ') .
                ($show_inactive_items ? 'and t.status in ("active", "inactive") ' : 'and t.status = "active" ') .
                'and t.originating_user_id = a.id ' .
                'and t.id = atav.track_id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );

        } else {
            $result = _mysql_query(
                'select t.*, a.name as originating_user_name ' .
                'from pp_audio_track t, pp_user a ' .
                'where t.user_id = ' . n($aid) . ' ' .
                'and t.type = "remix" ' .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                ($show_inactive_items ? 'and t.status in ("active", "inactive") ' : 'and t.status = "active" ') .
                'and t.originating_user_id = a.id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrack();
            $a = AudioTrack::_read_row($a, $row);

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
                'from pp_audio_track t, pp_user a, pp_audio_track_user_visibility atav ' .
                'where t.originating_user_id = ' . n($oaid) . ' ' .
                'and t.type = "remix" ' .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ') ') .
                ($show_inactive_items ? 'and t.status in ("active", "inactive") ' : 'and t.status = "active" ') .
                'and t.user_id = a.id ' .
                'and t.id = atav.track_id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );

        } else {
            $result = _mysql_query(
                'select t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_audio_track t, pp_user a ' .
                'where t.originating_user_id = ' . n($oaid) . ' ' .
                'and t.type = "remix" ' .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                ($show_inactive_items ? 'and t.status in ("active", "inactive") ' : 'and t.status = "active" ') .
                'and t.user_id = a.id ' .
                'order by t.playback_count desc ' .
                'limit ' . $from . ', ' . ($to - $from + 1)
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrack();
            $a = AudioTrack::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_track_details($tid, $visitorUserId) {
        $result = null;

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select distinct t.* ' .
                'from pp_audio_track t, pp_audio_track_user_visibility atav ' .
                'where t.id = ' . n($tid) . ' ' .
                'and (t.visibility = "public" or t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ') ' .
                'and t.status = "active" ' .
                'and t.id = atav.track_id'
            );

        } else {
            $result = _mysql_query(
                'select t.* ' .
                'from pp_audio_track t ' .
                'where t.id = ' . n($tid) . ' ' .
                'and t.visibility = "public" ' .
                'and t.status = "active"'
            );
        }

        $a = null;

        if ($row = mysql_fetch_array($result)) {
            $a = new AudioTrack();
            $a = AudioTrack::_read_row($a, $row);
        }

        mysql_free_result($result);

        return $a;
    }

    function fetchAllFullSongsOfUser($aid) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_audio_track ' .
            'where user_id = ' . n($aid) . ' ' .
            'and is_full_song = 1 ' .
            'and status in ("active", "inactive") ' .
            'order by title asc'
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrack();
            $a = AudioTrack::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchAllChildTracksOfFullSong($tid, $show_inactive_items, $ignore_visibility, $visitorUserId) {
        $objs = array();

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select distinct t.* ' .
                'from pp_audio_track t, pp_audio_track_user_visibility atav ' .
                'where t.parent_track_id = ' . n($tid) . ' ' .
                'and t.is_full_song = 0 ' .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ') ') .
                ($show_inactive_items ? 'and t.status in ("active", "inactive") ' : 'and t.status = "active" ') .
                'and t.id = atav.track_id ' .
                'order by t.title asc'
            );

        } else {
            $result = _mysql_query(
                'select t.* ' .
                'from pp_audio_track t ' .
                'where t.parent_track_id = ' . n($tid) . ' ' .
                'and t.is_full_song = 0 ' .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                ($show_inactive_items ? 'and t.status in ("active", "inactive") ' : 'and t.status = "active" ') .
                'order by t.title asc'
            );
        }

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrack();
            $a = AudioTrack::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_for_id($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_audio_track ' .
            'where id = ' . n($id)
        );

        $a = new AudioTrack();

        if ($row = mysql_fetch_array($result)) {
            $a = AudioTrack::_read_row($a, $row);
        }

        mysql_free_result($result);

        return $a;
    }

    function fetchForSearch($from, $length, $userOrTitle, $needsAttributeIds, $containsAttributeIds, $needsOthers, $containsOthers, $genres, $ignore_visibility, $show_inactive_items, $visitorUserId) {
        $objs = array();
        $result = _mysql_query(
                'select distinct t.*, a.name as user_name, a.image_filename as user_img_filename ' .
                'from pp_audio_track t join pp_user a on t.user_id = a.id join pp_audio_track_user_visibility atav on atav.track_id=t.id where 1=1 ' .
                ($userOrTitle == '' ? '' : 'and (a.name like ' . qqLike($userOrTitle) . ' or t.title like ' . qqLike($userOrTitle) . ') ') .
                ($needsOthers == '' ? '' : 'and t.needs_others like ' . qqLike($needsOthers) . ' ') .
                ($containsOthers == '' ? '' : 'and t.contains_others like ' . qqLike($containsOthers) . ' ') .
                ($show_inactive_items ? 'and t.status in ("active", "inactive") ' : 'and t.status = "active" ') .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ') ') .
                ($needsAttributeIds == '' ? '' : ' and t.id=(select max(attr.track_id) from pp_audio_track_audio_track_attribute attr where attr.track_id = t.id and attr.attribute_id in (' .
                nList($needsAttributeIds) . ') and attr.status="needs") ') .
                ($containsAttributeIds == '' ? '' : ' and t.id=(select max(attr.track_id) from pp_audio_track_audio_track_attribute attr where attr.track_id = t.id and attr.attribute_id in (' .
                nList($containsAttributeIds) . ') and attr.status="contains") ') .
                (count($genres) == 0 ? '' : ' and t.genres in (' .
                qqList($genres) . ') ') .
                ' order by entry_date desc ' .
                'limit ' . n($from) . ', ' . n($length)
            );


        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrack();
            $a = AudioTrack::_read_row($a, $row);

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
                'from pp_audio_track t join pp_user a on t.user_id = a.id join pp_audio_track_user_visibility atav on atav.track_id=t.id where 1=1 ' .
                ($userOrTitle == '' ? '' : 'and (a.name like ' . qqLike($userOrTitle) . ' or t.title like ' . qqLike($userOrTitle) . ') ') .
                ($needsOthers == '' ? '' : 'and t.needs_others like ' . qqLike($needsOthers) . ' ') .
                ($containsOthers == '' ? '' : 'and t.contains_others like ' . qqLike($containsOthers) . ' ') .
                ($show_inactive_items ? 'and t.status in ("active", "inactive") ' : 'and t.status = "active" ') .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ') ') .
                ($needsAttributeIds == '' ? '' : ' and t.id=(select max(attr.track_id) from pp_audio_track_audio_track_attribute attr where attr.track_id = t.id and attr.attribute_id in (' .
                nList($needsAttributeIds) . ') and attr.status="needs") ') .
                ($containsAttributeIds == '' ? '' : ' and t.id=(select max(attr.track_id) from pp_audio_track_audio_track_attribute attr where attr.track_id = t.id and attr.attribute_id in (' .
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

    function fetch_all_private_tracks_the_user_can_access($from, $to, $aid) {
        $objs = array();

        $result = _mysql_query(
            'select t.*, a.name as user_name, a.image_filename as user_img_filename ' .
            'from pp_audio_track t, pp_user a, pp_audio_track_user_visibility atav ' .
            'where atav.user_id = ' . n($aid) . ' ' .
            'and atav.track_id = t.id ' .
            'and t.status = "active" ' .
            'and t.visibility = "private" ' .
            'and t.user_id != ' . n($aid) . ' ' .
            'and t.user_id = a.id ' .
            'order by t.entry_date desc ' .
            'limit ' . $from . ', ' . ($to - $from + 1)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrack();
            $a = AudioTrack::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchAllNewbornTrackIdsForUserId($aid) {
        if (!$aid) return array();

        $result = _mysql_query(
            'select id ' .
            'from pp_audio_track ' .
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

    function fetchRandomTrack($excludeTrackId = null) {
        $whereClause = '';
        if (!is_null($excludeTrackId)) {
            $whereClause = 'where id != ' . n($excludeTrackId) . ' ';
        }

        $result = _mysql_query(
            'select * ' .
            'from pp_audio_track ' .
            $whereClause .
            'order by rand() ' .
            'limit 1'
        );

        $a = new AudioTrack();

        if ($row = mysql_fetch_array($result)) {
            $a = AudioTrack::_read_row($a, $row);
        }

        mysql_free_result($result);

        return $a;
    }

    function _read_row($a, $row) {
        $a->id                        = $row['id'];
        $a->user_id                   = $row['user_id'];
        $a->title                     = $row['title'];
        $a->preview_mp3_filename      = $row['preview_mp3_filename'];
        $a->orig_preview_mp3_filename = $row['orig_preview_mp3_filename'];
        $a->price                     = $row['price'];
        $a->currency                  = $row['currency'];
        $a->sorting                   = $row['sorting'];
        $a->type                      = $row['type'];
        $a->is_full_song              = $row['is_full_song'];
        $a->originating_user_id       = $row['originating_user_id'];
        $a->parent_track_id           = $row['parent_track_id'];
        $a->rating_count              = $row['rating_count'];
        $a->rating_value              = $row['rating_value'];
        $a->competition_points        = $row['competition_points'];
        $a->genres                    = $row['genres'];
        $a->visibility                = $row['visibility'];
        $a->playback_count            = $row['playback_count'];
        $a->download_count            = $row['download_count'];
        $a->originator_notified       = $row['originator_notified'];
        $a->status                    = $row['status'];
        $a->containsOthers            = $row['contains_others'];
        $a->needsOthers               = $row['needs_others'];
        $a->additionalInfo            = $row['additional_info'];
        $a->entry_date                = reformat_sql_date($row['entry_date']);

        if (isset($row['user_name']))             $a->user_name             = $row['user_name'];
        if (isset($row['user_img_filename']))     $a->user_img_filename     = $row['user_img_filename'];
        if (isset($row['originating_user_name'])) $a->originating_user_name = $row['originating_user_name'];

        return $a;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_audio_track ' .
            '(' .
            'id                        int(10)      not null auto_increment, ' .
            'user_id                 int(10)      not null, ' .
            'title                     varchar(255) not null, ' .
            'preview_mp3_filename      varchar(255) not null, ' .
            'orig_preview_mp3_filename varchar(255) not null, ' .
            'price                     float        not null, ' .
            'currency                  varchar(3)   not null, ' .
            'sorting                   int(5), ' .
            'type                      varchar(10)  not null, ' .
            'is_full_song              tinyint(1)   not null, ' .
            'originating_user_id     int(10), ' .
            'parent_track_id           int(10), ' .
            'rating_count              int(10)      not null, ' .
            'rating_value              float        not null, ' .
            'competition_points        int(10)      not null, ' .
            'genres                    varchar(255), ' .
            'visibility                varchar(10)  not null, ' .
            'playback_count            int(10)      not null, ' .
            'download_count            int(10)      not null, ' .
            'originator_notified       tinyint(1)   not null, ' .
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
            ')'
        );

        return $ok;
    }

    function count_all($count_inactive_items, $ignore_visibility, $visitorUserId) {
        $result = null;

//        $result = _mysql_query(
//            'select count(*) as cnt ' .
//            'from pp_audio_track t, pp_audio_track_user_visibility atav ' .
//            'where t.id = atav.track_id ' .
//            ($ignore_visibility ? '' : 'and (t.visibility = "public" or (t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ')) ') .
//            ($count_inactive_items ? 'and t.status in ("active", "inactive")' : 'and t.status = "active"')
//        );

        if ($visitorUserId >= 0) {
            $result = _mysql_query(
                'select count(distinct t.id) as cnt ' .
                'from pp_audio_track t, pp_audio_track_user_visibility atav ' .
                'where t.id = atav.track_id ' .
                ($ignore_visibility ? '' : 'and (t.visibility = "public" or t.visibility = "private" and t.id = atav.track_id and atav.user_id = ' . n($visitorUserId) . ') ') .
                ($count_inactive_items ? 'and t.status in ("active", "inactive")' : 'and t.status = "active"')
            );

        } else {
            $result = _mysql_query(
                'select count(*) as cnt ' .
                'from pp_audio_track t ' .
                'where 1=1 ' .
                ($ignore_visibility ? '' : 'and t.visibility = "public" ') .
                ($count_inactive_items ? 'and t.status in ("active", "inactive")' : 'and t.status = "active"')
            );
        }

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function count_all_private_tracks_the_user_can_access($aid) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_audio_track t, pp_audio_track_user_visibility atav ' .
            'where atav.user_id = ' . n($aid) . ' ' .
            'and atav.track_id = t.id ' .
            'and t.user_id != ' . n($aid) . ' ' .
            'and t.status = "active" ' .
            'and t.visibility = "private"'
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function reset_song_associations_to_parent_track_id($tid) {
        _mysql_query(
            'update pp_audio_track ' .
            'set parent_track_id = null ' .
            'where parent_track_id = ' . n($tid)
        );
    }

    function delete_with_id($id) {
        global $logger;

        if (!$id) return;

        AudioTrack::reset_song_associations_to_parent_track_id($id);
        AudioTrackFile::delete_all_with_track_id($id);
        AudioTrackUserVisibility::delete_all_with_track_id($id);
        AudioTrackAudioTrackAttribute::deleteForTrackId($id);

        $logger->info('deleting track file record with id: ' . $id);

        return _mysql_query(
            'delete from pp_audio_track ' .
            'where id = ' . n($id)
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
            'insert into pp_audio_track ' .
            '(user_id, title, preview_mp3_filename, orig_preview_mp3_filename, ' .
            'price, currency, sorting, type, is_full_song, originating_user_id, parent_track_id, rating_count, ' .
            'rating_value, competition_points, genres, visibility, playback_count, download_count, originator_notified, ' .
            'status, contains_others, needs_others, additional_info, entry_date) ' .
            'values (' .
            n($this->user_id)                    . ', ' .
            qq($this->title)                     . ', ' .
            qq($this->preview_mp3_filename)      . ', ' .
            qq($this->orig_preview_mp3_filename) . ', ' .
            qq($this->price)                     . ', ' .
            qq($this->currency)                  . ', ' .
            n($this->sorting)                    . ', ' .
            qq($this->type)                      . ', ' .
            b($this->is_full_song)               . ', ' .
            n($this->originating_user_id)        . ', ' .
            n($this->parent_track_id)            . ', ' .
            n($this->rating_count)               . ', ' .
            n($this->rating_value)               . ', ' .
            n($this->competition_points)         . ', ' .
            qq($this->genres)                    . ', ' .
            qq($this->visibility)                . ', ' .
            n($this->playback_count)             . ', ' .
            n($this->download_count)             . ', ' .
            b($this->originator_notified)        . ', ' .
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
            'update pp_audio_track ' .
            'set user_id = '               . n($this->user_id)                    . ', ' .
            'title = '                     . qq($this->title)                     . ', ' .
            'preview_mp3_filename = '      . qq($this->preview_mp3_filename)      . ', ' .
            'orig_preview_mp3_filename = ' . qq($this->orig_preview_mp3_filename) . ', ' .
            'price = '                     . qq($this->price)                     . ', ' .
            'currency = '                  . qq($this->currency)                  . ', ' .
            'sorting = '                   . n($this->sorting)                    . ', ' .
            'type = '                      . qq($this->type)                      . ', ' .
            'is_full_song = '              . b($this->is_full_song)               . ', ' .
            'originating_user_id = '       . n($this->originating_user_id)        . ', ' .
            'parent_track_id = '           . n($this->parent_track_id)            . ', ' .
            'rating_count = '              . n($this->rating_count)               . ', ' .
            'rating_value = '              . n($this->rating_value)               . ', ' .
            'competition_points = '        . n($this->competition_points)         . ', ' .
            'genres = '                    . qq($this->genres)                    . ', ' .
            'visibility = '                . qq($this->visibility)                . ', ' .
            'playback_count = '            . n($this->playback_count)             . ', ' .
            'download_count = '            . n($this->download_count)             . ', ' .
            'originator_notified = '       . b($this->originator_notified)        . ', ' .
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
<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_audio_track_artist_visibility table
class AudioTrackArtistVisibility {
    var $track_id;
    var $artist_id;

    // non-table fields
    var $artist_name;
    var $collaborating_artist_id;
    var $artist_image_filename;

    // constructors
    // ------------
    function AudioTrackArtistVisibility() {
    }

    function fetch_all_for_artist_id($aid) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_audio_track_artist_visibility ' .
            'where artist_id = ' . n($aid)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrackArtistVisibility();
            $a = AudioTrackArtistVisibility::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_all_for_track_id($tid) {
        $objs = array();

        $result = _mysql_query(
            'select atav.*, a.name as artist_name ' .
            'from pp_audio_track_artist_visibility atav, pp_artist a ' .
            'where atav.track_id = ' . n($tid) . ' ' .
            'and atav.artist_id = a.id'
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrackArtistVisibility();
            $a = AudioTrackArtistVisibility::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_for_artist_id_track_id($aid, $tid) {
        $result = _mysql_query(
            'select * ' .
            'from pp_audio_track_artist_visibility ' .
            'where artist_id = ' . n($aid) . ' ' .
            'and track_id = ' . n($tid)
        );

        $a = new AudioTrackArtistVisibility();

        if ($row = mysql_fetch_array($result)) {
            $a = AudioTrackArtistVisibility::_read_row($a, $row);
        }

        mysql_free_result($result);

        return $a;
    }

    function fetch_all_collaboration_artists_of_artist_id($aid, $limit = 0) {
        $objs = array();

        $limitClause = '';
        if ($limit) $limitClause = 'limit ' . $limit;

        $result = _mysql_query(
            'select distinct a.id as collaborating_artist_id, a.name as artist_name, a.image_filename as artist_image_filename ' .
            'from pp_audio_track t, pp_audio_track_artist_visibility atav, pp_artist a ' .
            'where t.artist_id = ' . n($aid) . ' ' .
            'and t.id = atav.track_id ' .
            'and atav.artist_id = a.id ' .
            'and a.id != ' . n($aid) . ' ' .
            'order by artist_name asc ' .
            $limitClause
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new AudioTrackArtistVisibility();
            $a = AudioTrackArtistVisibility::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function _read_row($a, $row) {
        $a->track_id    = $row['track_id'];
        $a->artist_id   = $row['artist_id'];

        // non-table fields
        $a->artist_name             = $row['artist_name'];
        $a->collaborating_artist_id = $row['collaborating_artist_id'];
        $a->artist_image_filename   = $row['artist_image_filename'];

        return $a;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_audio_track_artist_visibility ' .
            '(' .
            'artist_id                 int(10)      not null, ' .
            'track_id                  int(10)      not null, ' .
            'primary key (artist_id, track_id), ' .
            'key artist_id (artist_id)' .
            ')'
        );

        return $ok;
    }

    function delete_all_with_track_id($tid) {
        global $logger;

        if (!$tid) return;

        $logger->info('deleting all track artist visibility records with track id: ' . $tid);

        return _mysql_query(
            'delete from pp_audio_track_artist_visibility ' .
            'where track_id = ' . n($tid)
        );
    }

    function delete_all_with_track_id_and_artist_id_list($tid, $aids) {
        global $logger;

        if (!$tid) return;
        if (!$aids) return;

        $logger->info('deleting all track artist visibility records with track id ' . $tid . ' and artist id list ' . implode(',', $aids));

        return _mysql_query(
            'delete from pp_audio_track_artist_visibility ' .
            'where track_id = ' . n($tid) . ' ' .
            'and artist_id in (' . implode(',', $aids) . ')'
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
            'insert into pp_audio_track_artist_visibility ' .
            '(artist_id, track_id) ' .
            'values (' .
            n($this->artist_id)                  . ', ' .
            n($this->track_id)                   .
            ')'
        );

        if (!$ok) {
            return false;
        }

        $this->id = mysql_insert_id();

        return $ok;
    }
}

?>
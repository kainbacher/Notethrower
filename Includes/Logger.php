<?php

include_once('../Includes/Config.php');
// Achtung: Schnipsel.php darf hier nicht inkludiert werden, weil das zu einer circular reference fuehrt

$LEVEL_DEBUG = 0;
$LEVEL_INFO  = 1;
$LEVEL_WARN  = 2;
$LEVEL_ERROR = 3;
$LEVEL_FATAL = 4;
$LEVEL_OFF   = 9;

class Logger {
    var $fp;
    var $file;
    var $level;
    var $request_id;
    var $start_line_logged;

    // constructors
    // ------------
    function Logger($name) {
        $this->request_id = rand(10000000, 99999999); // generate random 8-digit requestId

        $this->level = $GLOBALS['LEVEL_INFO'];

        if (!file_exists($GLOBALS['LOGFILE_BASE_PATH'])) {
            Logger::_create_directory($GLOBALS['LOGFILE_BASE_PATH']);
        }

        $datum    = strftime('%Y%m%d');
        $filename = $datum . '-' . $name . '.log';

        $this->file = $GLOBALS['LOGFILE_BASE_PATH'] . $filename;

        umask(0777); // most probably ignored on windows systems
        $this->fp = fopen($this->file, 'a+');
        chmod($this->file, 0777);

        $this->start_line_logged = false;
    }

    // class functions
    // ---------------
    function debug($msg) {
        if ($GLOBALS['LEVEL_DEBUG'] >= $this->level) Logger::_log('DEBUG', $msg);
    }
    function info($msg) {
        if ($GLOBALS['LEVEL_INFO'] >= $this->level) Logger::_log('INFO', $msg);
    }
    function warn($msg) {
        if ($GLOBALS['LEVEL_WARN'] >= $this->level) Logger::_log('WARN', $msg);
    }
    function error($msg) {
        if ($GLOBALS['LEVEL_ERROR'] >= $this->level) Logger::_log('ERROR', $msg);
    }
    function fatal($msg) {
        if ($GLOBALS['LEVEL_FATAL'] >= $this->level) Logger::_log('FATAL', $msg);
    }

    function _log($level, $msg) {
        $zeit    = strftime('%Y-%m-%d %H:%M:%S');
        $log_str = $zeit . ' - ' . $this->request_id . ' - ' . $level . ': ' . $msg . "\r\n";
        if ($this->fp) {
            if (!$this->start_line_logged) {
                fwrite($this->fp, $zeit . ' - ' . $this->request_id . ' - INFO: --------' . "\r\n");
                $this->start_line_logged = true;
            }

            fwrite($this->fp, $log_str);

        } else {
            echo 'ERROR: cannot write to logfile: ' . $this->file . "\r\n";
            echo $log_str;
        }
    }

    function _create_directory($dir) {
        $ok = true;

        if (!is_dir($dir)) {
            // create dir with full access rights
            umask(0777); // most probably ignored on windows systems
            $ok = mkdir($dir, 0777); // ATTENTION: works only for one directory level
        }

        if (!$ok) {
            Logger::_show_fatal_error_and_exit(
                'Pfad kann nicht erstellt werden: ' . $dir
            );
        }
    }

    function _show_fatal_error_and_exit($errortext) {
        echo '<br><font color="#FF0000">FEHLER: ' . htmlentities($errortext) . '</font><br>';
        trigger_error($errortext, E_USER_ERROR);
        exit;
    }

    // object methods
    // --------------
    function set_debug_level() {
        $this->level = $GLOBALS['LEVEL_DEBUG'];
    }
    function set_info_level() {
        $this->level = $GLOBALS['LEVEL_INFO'];
    }
    function set_warn_level() {
        $this->level = $GLOBALS['LEVEL_WARN'];
    }
    function set_error_level() {
        $this->level = $GLOBALS['LEVEL_ERROR'];
    }
    function set_fatal_level() {
        $this->level = $GLOBALS['LEVEL_FATAL'];
    }
    function turn_off() {
        $this->level = $GLOBALS['LEVEL_OFF'];
    }

    function close() {
        fclose($this->fp);
        umask(0777); // most probably ignored on windows systems
        chmod($this->file, 0777); // not sure if this should be done here or after opening the file
    }

    function __destruct() {
        $this->close();
    }

    function DESTROY() {
        $this->close();
    }
}

?>

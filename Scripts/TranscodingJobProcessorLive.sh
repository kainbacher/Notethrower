#!/bin/bash

# This script does start the TranscodingJobProcessor.php and pipes the output
# into the Log file (LOG_FILE) if logging is enabled (LOG_ENABLED="true")

# Config Section
TRANSCODING_DIR="/home/benso/oneloudr.com/OL/Transcoding/"
LOG_ENABLED="true"
LOG_FILE="/home/benso/oneloudr.com/OL/Log/cron.log"
##################################################################

cd $TRANSCODING_DIR
if [ $LOG_ENABLED = "true" ]; then
    /usr/local/php5/bin/php TranscodingJobProcessor.php >> $LOG_FILE 2>&1
else
    /usr/local/php5/bin/php TranscodingJobProcessor.php > /dev/null 2>&1
fi
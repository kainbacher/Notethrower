#!/bin/bash

# This script does start the TranscodingJobProcessor.php and pipes the output
# into the Log file (LOG_FILE) if logging is enabled (LOG_ENABLED="true")

# Config Section
TRANSCODING_DIR="/home/benso/oneloudr.com/OLTest/Transcoding/"
LOG_ENABLED="false"
LOG_FILE="/home/benso/oneloudr.com/OLTest/Log"
##################################################################

cd $TRANSCODING_DIR
if [ $LOG_ENABLED = "true" ]; then
    php TranscodingJobProcessor.php >> $LOG_FILE 2>&1
else
    php TranscodingJobProcessor.php > /dev/null 2>&1
fi
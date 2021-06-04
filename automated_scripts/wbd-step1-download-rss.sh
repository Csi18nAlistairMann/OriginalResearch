#!/bin/bash
#
# wbd-step1-download-rss.sh
#
# Geared to What Bitcoin Did website
#

source defines.sh

# Set up and obtain the RSS data
CWD=$(pwd)
mkdir -p $WBD_DIR
cd $WBD_DIR
DATE=$(date +%Y%m%d)

if [ ! -s podcast.rss.$DATE ]; then
    # Only download RSS once a day. At this point the file is not further
    # processed.
    rm -f podcast.rss

    wget https://www.whatbitcoindid.com/podcast?format=RSS -O podcast.rss
    cp podcast.rss podcast.rss.$DATE
fi

if [ ! -s transcriptions.html.$DATE ]; then
    # Only download transcriptions page once a day. This page IS processed
    # being fed through STDIN to
    # ./automated_scripts/wbd-transcriptions-scrape.php
    rm -f transcriptions.html

    wget https://www.whatbitcoindid.com/transcriptions -O transcriptions.html
    cp transcriptions.html transcriptions.html.$DATE
fi

cd $CWD

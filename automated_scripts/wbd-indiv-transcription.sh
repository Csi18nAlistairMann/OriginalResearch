#!/bin/bash
#
# wbd-indiv-transcriptions.sh
#
# Given a particular episode number, see if it needs processing and if so, do
# it.

source defines.sh

# Set up
INDEX=WBD$1
CWD=$(pwd)
echo $WBD_DIR$INDEX
if [ -d $WBD_DIR$INDEX ]; then
    cd $WBD_DIR$INDEX
    DATE=$(date +%Y%m%d)
    if [ ! -f transcription.original.$DATE ]; then
	# Only download transcription once a day
	wget -i transcription.url -O transcription.original
	cp transcription.original transcription.original.$DATE
    fi

    # Take raw html of transcription and clean it up
    cat transcription.original | \
	php "$INSTALL_DIR"automated_scripts/wbd-clean-transcript.php \
	>transcript.cleaned

    # Take location of mp3 and download if we don't already have it
    if [ ! -f $INDEX.mp3 ]; then
	wget -i mp3-from-rss.url -O $INDEX.mp3
    fi

else
    echo "'"$1"' doesn't appear to be a valid episode"
fi

# Return control
cd $CWD

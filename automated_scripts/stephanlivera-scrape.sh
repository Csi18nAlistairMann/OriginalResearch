#!/bin/bash
#
# stephanlivera-scrape.sh
#
# Scraper for Stephan Livera Podcast at https://stephanlivera.com/
#
# Individual podcasts see a direct download to the mp3 and higher quality
# transcripts (added later?) to each page.
#
# To contrast, youtube-dl downloads more data but results in a smaller
# audio file as it downloads video from which audio is extracted. Youtube also
# provides a lower quality transcript - AIUI SL reviews his own transcriptions.

source ./defines.sh

# Set up
CWD=$(pwd)
DATE=$(date +%Y%m%d)
PROJNAME=$1
EPNO=$2

if [ "$#" -ne "2" ]; then
    echo "Bad arguments"
    exit
fi

ERR_NO_TRANSCRIPT=0
CLEANED_TITLE_FN="title.cleaned"
CLEANED_TRANSCRIPT="transcript.cleaned"
ORIGINAL_FILE=index.html
TRANSCRIPTION_URL=transcription.url

EPISODEDIR="SLP"$EPNO"/"
URL=https://stephanlivera.com/episode/$EPNO/
MP3URLMATCH="https://stephanlivera.com/episode-player"

SLP_TRANSCRIPT_CLEANER="$INSTALL_DIR"automated_scripts/helpa-slp-transcript-scrape.php
SLP_TITLE_CLEANER="$INSTALL_DIR"automated_scripts/helpa-slp-title-scrape.php
OR_GET_TAG="$INSTALL_DIR"api/get_tag_for_thing.php
OR_ADD_THING="$INSTALL_DIR"automated_scripts/add-thing-nuance-tags.php

OR_SLP_TAG=$(php $OR_GET_TAG "$PROJNAME" 'THINGONLY' "Stephan Livera Podcast")
if [ "$OR_SLP_TAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG "$PROJNAME" 'THINGONLY' "Stephan Livera Podcast"
    ERR_NO_TRANSCRIPT=1
    exit
fi

# (Re)reate local storage for what's coming
mkdir -p $SLP_DIR$EPISODEDIR
cd $INSTALL_DIR

# Keep URL around for later use and add to OR
echo "$URL" >$SLP_DIR$EPISODEDIR$TRANSCRIPTION_URL
php $OR_ADD_THING $PROJNAME $URL '' $EPTAG

# #####
# YDATE=$(date -d "yesterday 13:00" '+%Y%m%d')
# mv $ORIGINAL_FILE.$YDATE $ORIGINAL_FILE.$DATE
# ln -sf $ORIGINAL_FILE.$DATE $ORIGINAL_FILE
# #####

# Download index page and mp3 once a day
if [ ! -e $SLP_DIR$EPISODEDIR$ORIGINAL_FILE.$DATE ]; then
    # Get and copy the index page
    echo $SLP_DIR
    echo $EPISODEDIR
    echo $ORIGINAL_FILE.$DATE
    echo $SLP_DIR$EPISODEDIR$ORIGINAL_FILE.$DATE
    wget $URL -O $SLP_DIR$EPISODEDIR$ORIGINAL_FILE.$DATE
    ln -sf $SLP_DIR$EPISODEDIR$ORIGINAL_FILE.$DATE \
	$SLP_DIR$EPISODEDIR$ORIGINAL_FILE

    # Download mp3
    MP3URL=$(grep mp3\" $SLP_DIR$EPISODEDIR$ORIGINAL_FILE | \
	sed 's#.*href=\"\('$MP3URLMATCH'.*\)#\1#' | sed 's#\">.*##g')
    if [ ! -e $SLP_DIR$EPISODEDIR$ORIGINAL_FILE.mp3 ]; then
	wget $MP3URL -O $SLP_DIR$EPISODEDIR$ORIGINAL_FILE.mp3
    fi
fi

# Retrieve the title and transcript
cat $SLP_DIR$EPISODEDIR$ORIGINAL_FILE | \
    php $SLP_TRANSCRIPT_CLEANER \
    >$SLP_DIR$EPISODEDIR$CLEANED_TRANSCRIPT
cat $SLP_DIR$EPISODEDIR$ORIGINAL_FILE | php $SLP_TITLE_CLEANER \
    >$SLP_DIR$EPISODEDIR$CLEANED_TITLE_FN
CLEANED_TITLE=$(cat $SLP_DIR$EPISODEDIR$CLEANED_TITLE_FN)
if [ ! -s $SLP_DIR$EPISODEDIR$CLEANED_TRANSCRIPT ]; then
    ERR_NO_TRANSCRIPT=1
fi

# Add SLPxxx to OR
EPTAG=$(php $OR_GET_TAG $PROJNAME THINGONLY "$SLP_DIR$EPISODEDIR")
RV=$?
if [ "$EPTAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG $PROJNAME THINGONLY "$SLP_DIR$EPISODEDIR"
    ERR_NO_TRANSCRIPT=1
    exit

elif [ "$RV" -eq "1" -a "$EPTAG" = 'Too few matches' ]; then
    php $OR_ADD_THING $PROJNAME $SLP_DIR$EPISODEDIR \
	"$CLEANED_TITLE" $OR_SLP_TAG
    EPTAG=$(php $OR_GET_TAG $PROJNAME THINGONLY "$SLP_DIR$EPISODEDIR")
    if [ "$EPTAG" = "$ERR_BAD_ARGS_T" ]; then
	echo $0":"$LINENO": Bad arguments among"
	echo php $OR_GET_TAG $PROJNAME THINGONLY "$SLP_DIR$EPISODEDIR"
	ERR_NO_TRANSCRIPT=1
	exit
    fi
fi

# Report to the user
if [ $ERR_NO_TRANSCRIPT -eq 1 ]; then
    echo 'No transcript detected';
fi

# Return control
cd $CWD

exit $ERR_NO_TRANSCRIPT;

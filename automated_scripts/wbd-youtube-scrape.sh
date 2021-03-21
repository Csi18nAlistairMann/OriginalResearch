#!/bin/bash
#
# wbd-youtube-scrape.sh <database> "<html file>"
# Geared to What Bitcoin Did -
#  https://www.youtube.com/c/WhatBitcoinDidPodcast/videos
#
# For details please read the notes in common-youtube-scrape.sh
#
# This script takes a youtube scrape and makes such calls as will result in
# the various videos being entered into the OR database.
#

source defines.sh
source ./automated_scripts/common-youtube-scrape.sh

#
# Set up. TEMPFILE is used to create a succession of tmpfiles with
# progressively fewer unprocessed items. Start by collecting all possible
# items
if [ ! -e "$2" ]; then
    echo "No source file on command line - aborting"
    exit
fi
DATABASE=$1
HTMLFILE=$2
TEMPFILE=$(mktemp)

OR_GET_TAG="$INSTALL_DIR"api/get_tag_for_thing.php
OR_ADD_THING="$INSTALL_DIR"automated_scripts/add-thing-nuance-tags.php

SITES_TXT='Sites'
YOUTUBE_TXT='Youtube'
WBD_TXT="What Bitcoin Did"
WBDBG_TXT="What Bitcoin Did Beginner Guides"
WBDBW_TXT="What Bitcoin Did Bitcoin World"
CLEANED_TITLE=''
OR_SLP_TAG='?'

# Add in the parents we'll be using
php $OR_ADD_THING $DATABASE "$SITES_TXT" '' '?'
SITESTAG=$(php $OR_GET_TAG $DATABASE THINGONLY "$SITES_TXT")
if [ "$SITESTAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG $DATABASE THINGONLY "$SITES_TXT"
    exit
fi

php $OR_ADD_THING $DATABASE "$YOUTUBE_TXT" '' "$SITESTAG"
YTTAG=$(php $OR_GET_TAG $DATABASE THINGONLY "$YOUTUBE_TXT")
if [ "$SITESTAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG $DATABASE THINGONLY "$YOUTUBE_TXT"
    exit
fi

php $OR_ADD_THING $DATABASE "$WBD_TXT" '' '?'
WBDTAG=$(php $OR_GET_TAG $DATABASE THINGONLY "$WBD_TXT")
if [ "$SITESTAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG $DATABASE THINGONLY "$WBD_TXT"
    exit
fi

php $OR_ADD_THING $DATABASE "$WBDBG_TXT" '' "$WBDTAG"
WBDBGTAG=$(php $OR_GET_TAG $DATABASE THINGONLY "$WBDBG_TXT")
if [ "$WBDBGTAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG $DATABASE THINGONLY "$WBDBG_TXT"
    exit
fi

php $OR_ADD_THING $DATABASE "$WBDBW_TXT" '' "$WBDTAG"
WBDBWTAG=$(php $OR_GET_TAG $DATABASE THINGONLY "$WBDBW_TXT")
if [ "$WBDBWTAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG $DATABASE THINGONLY "$WBDBW_TXT"
    exit
fi

# Here we repeatedly process the URLs and titles in a manner specific to the
# particular stream.
# Each iteration provides something that will match something in the URL or
# title, and a space-seperated list of tags. The match will cause the URL to
# be inserted as a Thing in the given database, with the title as its Nuance.
# The final two arguments are used to name the file containing the items to be
# processed and the file unprocessed items will be left in ready for the next
# pass.

# Grep out the youtube links to the .all file
scrape_to_simple "$HTMLFILE" "$TEMPFILE.all"

# Now scan through the URLs linking them as specified
# iteration <episode-tag> <subdir> <match> <tags> <from> <to>
iteration "WBDBG" "what-bitcoin-did/beginnerguides" "Beginner’s Guide" "$WBDBGTAG" "all" 1
iteration "WBDBW" "what-bitcoin-did/bitcoinworld" "Bitcoin World" "$WBDBWTAG" 1 2
iteration "WBD" "what-bitcoin-did/episodes" "*" "$WBDTAG" 2 3

#
# If the final temporary file is of non-zero size the original scrape has one
# or more videos that do not match anything above. Let the user know, then
# clean up.
cleanup $TEMPFILE 3

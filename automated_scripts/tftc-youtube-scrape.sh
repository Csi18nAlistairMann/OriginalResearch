#!/bin/bash
#
# tftc-youtube-scrape.sh <database> "<html file>"
# Geared to Tales From The Crypt - https://www.youtube.com/c/TFTC21/videos
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
TFTC_TXT="Tales From The Crypt"
TFTCG_TXT='TFTC Guides'
RHR_TXT='Rabbit Hole Recap'
CD_TXT='Citadel Dispatch'
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

php $OR_ADD_THING $DATABASE "$TFTC_TXT" '' '?'
TFTCTAG=$(php $OR_GET_TAG $DATABASE THINGONLY "$TFTC_TXT")
if [ "$SITESTAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG $DATABASE THINGONLY "$TFTC_TXT"
    exit
fi

php $OR_ADD_THING $DATABASE "$TFTCG_TXT" '' "$TFTCTAG"
TFTCGTAG=$(php $OR_GET_TAG $DATABASE THINGONLY "$TFTCG_TXT")
if [ "$SITESTAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG $DATABASE THINGONLY "$TFTCG_TXT"
    exit
fi

php $OR_ADD_THING $DATABASE "$RHR_TXT" '' "$TFTCTAG"
RHRTAG=$(php $OR_GET_TAG $DATABASE THINGONLY "$RHR_TXT")
if [ "$SITESTAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG $DATABASE THINGONLY "$RHR_TXT"
    exit
fi

php $OR_ADD_THING $DATABASE "$CD_TXT" '' "$TFTCTAG"
CDTAG=$(php $OR_GET_TAG $DATABASE THINGONLY "$CD_TXT")
if [ "$SITESTAG" = "$ERR_BAD_ARGS_T" ]; then
    echo $0":"$LINENO": Bad arguments among"
    echo php $OR_GET_TAG $DATABASE THINGONLY "$CD_TXT"
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
# Also see anchor-scrape.php
iteration "TFTC" "tftc/citadel" "Citadel" "$CDTAG" "all" 1
iteration "TFTC" "tftc/rhr" "Rabbit" "$RHRTAG" 1 2
iteration "TFTC" "tftc/rhr" "RHR Week" "$RHRTAG" 2 3
iteration "TFTC" "tftc/guides" "Guide" "$TFTCGTAG" 3 4
iteration "TFTC" "tftc/episodes" "Tales from the" "$TFTCTAG" 4 5
iteration "TFTC" "tftc/episodes" ">#[0-9]+:" "$TFTCTAG" 5 6
iteration "TFTC" "tftc/episodes" "Ep[0-9]+" "$TFTCTAG" 6 7
iteration "TFTC" "tftc/episodes" "TFTC [0-9]+" "$TFTCTAG" 7 8
# These items do not match the above so have been dealt with manually
iteration "TFTC" "tftc/other" "3NQ_d3kr6u0" "$TFTCTAG" 8 9
iteration "TFTC" "tftc/other" "6ZmYx3_-Frc" "$TFTCTAG" 9 10
iteration "TFTC" "tftc/other" "hISvHhQI_ck" "$TFTCTAG" 10 11
iteration "TFTC" "tftc/citadel" "aylDowaSdzU" "$CDTAG" 11 12

#
# If the final temporary file is of non-zero size the original scrape has one
# or more videos that do not match anything above. Let the user know, then
# clean up.
cleanup $TEMPFILE 12

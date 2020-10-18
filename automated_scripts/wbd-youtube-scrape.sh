#!/bin/bash
#
# wbd-youtube-scrape.sh <database> "<html file>"
# Geared to What Bitcoin Did -
#  https://www.youtube.com/channel/UCzrWKkFIRS0kjZf7x24GdGg/videos
#
# For details please read the notes in common-youtube-scrape.sh
#
# This script takes a youtube scrape and makes such calls as will result in
# the various videos being entered into the OR database.
#

source ./automated_scripts/common-youtube-scrape.sh

#
# Set up. TEMPFILE is used to create a succession of tmpfiles with
# progressively fewer unprocessed items. Start by collecting all possible
# items
DATABASE=$1
TEMPFILE=$(mktemp)
scrape_to_simple "$2" "$TEMPFILE.all"

#
# Here we repeatedly process the URLs and titles in a manner specific to the
# particular stream.
# Each iteration provides something that will match something in the URL or
# title, and a space-seperated list of tags. The match will cause the URL to
# be inserted as a Thing in the given database, with the title as its Nuance.
# The final two arguments are used to name the file containing the items to be
# processed and the file unprocessed items will be left in ready for the next
# pass.
iteration "Beginner.*Guide" 'You51d WBD1187' 'all' 1
iteration "Bitcoin World" 'You51d WBD1188' 1 2
iteration '*' 'You51d Wha995' 2 3

#
# If the final temporary file is of non-zero size the original scrape has one
# or more videos that do not match anything above. Let the user know, then
# clean up.
cleanup $TEMPFILE 3

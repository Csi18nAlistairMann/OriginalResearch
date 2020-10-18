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
iteration 'Rabbit' 'You51d Rab74d' 'all' 1
iteration 'RHR Week' 'You51d Rab74d' 1 2
iteration 'Guide' 'You51d TFT1051' 2 3
iteration 'Tales from the' 'You51d 60' 3 4
iteration '>#[0-9]+:' 'You51d 60' 4 5
# These items do not match the above so have been dealt with manually
iteration '3NQ_d3kr6u0' 'You51d 60' 5 6
iteration '6ZmYx3_-Frc' 'You51d 60' 6 7
iteration 'hISvHhQI_ck' 'You51d 60' 7 8

#
# If the final temporary file is of non-zero size the original scrape has one
# or more videos that do not match anything above. Let the user know, then
# clean up.
cleanup $TEMPFILE 8

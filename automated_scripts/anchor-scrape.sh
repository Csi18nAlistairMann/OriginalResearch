#!/bin/bash
#
# anchor-scrape.sh <database> "<URL>"
#
# Obtain and scrape an anchor.fm channel

source ./defines.sh

DATABASE=$1

if [ "$#" -ne "2" ]; then
    echo "Bad arguments"
    exit
fi

# Set up
CWD=$(pwd)
DATE=$(date +%Y%m%d)
PROJNAME=$1
URL=$2
ORIGINAL_FILE=index.html

mkdir -p "$SCRAPES_DIR$URL"

# Download index page and mp3 once a day
if [ ! -e $SCRAPES_DIR$URL$ORIGINAL_FILE.$DATE ]; then
    # Get and copy the index page
    echo $SCRAPES_DIR$URL$ORIGINAL_FILE.$DATE
    wget $URL -O $SCRAPES_DIR$URL$ORIGINAL_FILE.$DATE
    ln -sf $SCRAPES_DIR$URL$ORIGINAL_FILE.$DATE \
	$SCRAPES_DIR$URL$ORIGINAL_FILE

    cat $SCRAPES_DIR$URL$ORIGINAL_FILE | \
	php "$INSTALL_DIR"automated_scripts/anchor-scrape.php
fi

cd $CWD

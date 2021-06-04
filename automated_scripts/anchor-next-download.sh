#!/bin/bash
#
# anchor-next-download.sh
#
# Search through the various directories looking for those with an anchor.fm
# URL within

source ./defines.sh

if [ "$#" -ne "0" ]; then
    echo "Bad arguments"
    exit
fi

# Set up
pushd $(pwd) >/dev/null  # Change to current dir (save curpos)
DATE=$(date +%Y%m%d)
ORIGINAL_FILE=index.html

cd $ARCHIVE_DIR

# Search through the various directories looking for those with an anchor.fm
# URL within
# https://stackoverflow.com/questions/10610955/shell-script-to-traverse-directories
find . -type d | while read -r dir
do
    FOUND=0
    pushd "$dir" >/dev/null  # Change to directory we're traversing
    if [ -e "anchor.url" ]; then
	# This is a dir storing anchor.fm data
	if [[ ! -e "anchor.html" || ! -s "anchor.html" ]]; then
	    # This anchor.fm data hasn't downloaded html yet OR the data that
	    # downloaded was empty (AWS outage 25 nov 2020)
	    echo "Entering $dir"
	    FOUND=1

	    # Establish where in the FS we found
	    LOCALDIR=$(echo "$dir"/ | cut -b 3-)
	    # Obtain the episode html
	    wget -i "$ARCHIVE_DIR$LOCALDIR"anchor.url -O \
		"$ARCHIVE_DIR$LOCALDIR"anchor.html

	    if [ -s "$ARCHIVE_DIR$LOCALDIR""anchor.html" ]; then
		# Parse the html for the media file, record it
		pushd "$INSTALL_DIR" >/dev/null  # Change to scripts dir
		URL=$(cat "$ARCHIVE_DIR$LOCALDIR"anchor.html | \
		    php "$INSTALL_DIR"automated_scripts/anchor-audioUrl.php)
		echo "$URL" >"$ARCHIVE_DIR$LOCALDIR"anchor.media
		popd >/dev/null  # Return to episode dir

		# Obtain the media too
		wget -i "$ARCHIVE_DIR$LOCALDIR"anchor.media -P "$ARCHIVE_DIR$LOCALDIR"
	    fi
	fi
    fi

    popd >/dev/null  # return to the directory we're traversing
    if [ "$FOUND" -eq "1" ]; then
	# We only want to do one call per run. If we've done it, stop now
	break
    fi
done

# C
popd >/dev/null  # Return to current dir

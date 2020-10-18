#!/bin/bash
#
# common-youtube-scrape.sh <database> "<html file>"
#
# These functions assist scripts elsewhere dedicated to matching particular
# youtube channels.
#
# Setup: your browser needs an extension that will save the page's html as
# adjusted by Javascript. Known to work:
# Chromium - SingleFile (Chrome store: https://tinyurl.com/adqg8dt)
#
# 'database' is the OR database into which to insert the data
# 'html file' is a scrape of a youtube page. To obtain:
#  a. Visit https://www.youtube.com/c/<channel>/videos
#  b. Page down if needing to capture all available videos - javascript doesn't
#     load them in until requested. Skip if only needing most recent. Having
#     browser on full-screen maximises initial population
#  c. Click SingleFile extension. If reload required, do so, with page down
#     repeated if used above, and click SingleFile extension again
#  d. It may takes some seconds for a larger page but the html should be
#     downloaded to your default download location
#  e. Call this script as above
#
# Keep an eye on the output. Should a video be seen that doesn't seem to fit
# the matches it'll be listed afterwards. I suggest matching on the youtube ID
# if nothing else seems reasonable. Or match on '*' if you're sure you want to
# add all remaining videos regardless.

#
# scrape_to_simple
#
# Create a file with each line holding a video URL and its accompanying title.
# A scrape currently has the form:
# <a id=video-title
#    class="yt-simple-endpoint style-scope ytd-grid-video-renderer"
#    aria-label="Rabbit Hole Recap: Week of 2020.09.14 by TFTC 2 days ago 1 \
#                hour, 21 minutes 244 views"
#    title="Rabbit Hole Recap: Week of 2020.09.14"
#    href="https://www.youtube.com/watch?v=pz6lM2XVLxQ">
# Rabbit Hole Recap: Week of 2020.09.14
# </a>
# This routine takes the final section above to form:
# https://www.youtube.com/watch?v=pz6lM2XVLxQ">Rabbit Hole Recap: Week of \
# 2020.09.14
# Note that the title is taken from inside the <a></a>, not the title provided
# as an attribute.
# The "> terminating the <a tag is kept in place to divide the URL from the
# title - it is relied on later.
# Matching lines will also contain a second URL we will want to ignore as both
# duplicative and lacking the title. The correct URL has a quote mark just
# before its href
function scrape_to_simple {
    cat "$1" | grep 'watch?v=' | grep '</a>' | sed \
	"s#.*['\"] href=\"\(https://www.youtube.com/watch?v=.*\)#\1#" | \
	sed 's\</a>.*\\g' | uniq | LC_ALL=C sort >"$2"
}

#
# iteration constructs and handles particular matches against the scraped html
function iteration {
    MATCH=$1
    TAGS=$2
    FROM=$3
    TO=$4

    # Construct data file with tags on the first line and matches from the
    # original file on subsequent lines
    echo $TAGS >$TEMPFILE.data
    cat $TEMPFILE.$FROM | egrep -i "$MATCH" >>$TEMPFILE.data

    # Have a helper script do the insertions
    cat $TEMPFILE.data | php \
	./automated_scripts/convert-insert-youtube-scrape.php $DATABASE

    # Clean up, then extract non-matching items ready for the next run
    rm $TEMPFILE.data
    cat $TEMPFILE.$FROM | egrep -vi "$MATCH" >$TEMPFILE.$TO
}

#
# cleanup
#
# Alert the user if there are items that haven't been processed before
# cleaning up
function cleanup {
    # Detect if filesize non-zero
    if [ -s $1.$2 ]; then
	echo "Following didn't get matched - please resolve and rerun:"
	cat $1.$2
    fi
    rm $1.$2
    rm $1
}

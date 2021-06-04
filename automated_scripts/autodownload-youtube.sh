#!/bin/bash
#
# autodownload-youtube.sh
#
# This script to take a youtube.com or youtu.be link and download the video,
# audio, and 'auto-sub' which seems to be the transcript

source defines.sh

# Setup
DIR=$1
CWD=$(pwd)
if [ ! -d "$DIR" ]; then
    echo "Episode directory doesn't exist - aborting"
    exit
fi
cd "$DIR"
URL=$(cat 'media.url')
URLLEN=$(echo -n "$URL" | wc -c)
URLENTRY='s:'$URLLEN':"'$URL'"'

# Establish what the ID is
LONG=`echo $URL | grep -c 'https://www.youtube.com/'`
SHORT=`echo $URL | grep -c 'https://youtu.be/'`
if [ $LONG -eq 1 ]; then
    ID=$(echo $URL | sed 's|https://www.youtube.com/watch?v=||')
elif [ $SHORT -eq 1 ]; then
    ID=$(echo $URL | sed 's|https://youtu.be/||')
else
    echo "N;"
    exit
fi

# Download the files
# --write-info-json Download and keep metadata
# -k keep video even though we're asking for audio only
# -x keep audio only
# --write-auto-sub download automatic subtitles. I think this is transcript
OUTPUT1=$(youtube-dl --write-info-json -k -x --write-auto-sub $URL 2>/dev/null)
TITLE=$(ls -t | grep info.json | head -1 | sed 's/.info.json//')
TITLELEN=$(echo -n "$TITLE" | wc -c)
TITLEENTRY='s:'$TITLELEN':"'$TITLE'"'

# Establish what files likely got downloaded, establish the length of each, and
# finally construct a PHP serialize() string to represent it
VIDFILE=$TITLE.mp4
AUDFILE=$TITLE.m4a
TXTFILE=$TITLE.en.vtt
TXTCFILE=$TITLE.en.vtt.cleaned
JSONFILE=$TITLE.info.json
ORFILE=$TITLE.or
VIDLEN=0
AUDLEN=0
TXTLEN=0
TXTCLEN=0
if [ -e "$VIDFILE" ]; then
    VIDLEN=$(echo -n "$VIDFILE" | wc -c)
    VIDENTRY='s:'$VIDLEN':"'$VIDFILE'"'
else
    VIDENTRY='N'
fi
if [ -e "$AUDFILE" ]; then
    AUDLEN=$(echo -n "$AUDFILE" | wc -c)
    AUDENTRY='s:'$AUDLEN':"'$AUDFILE'"'
else
    AUDENTRY='N'
fi
if [ -e "$TXTFILE" ]; then
    TXTLEN=$(echo -n "$TXTFILE" | wc -c)
    TXTENTRY='s:'$TXTLEN':"'$TXTFILE'"'
    cd "$INSTALL_DIR"
    cat "$DIR/$TXTFILE" | \
	php "$INSTALL_DIR"automated_scripts/clean-youtube-vtt.php \
	>"$DIR/$TXTCFILE"
    cd "$DIR"
    TXTCLEN=$(echo -n "$TXTCFILE" | wc -c)
    TXTCENTRY='s:'$TXTCLEN':"'$TXTCFILE'"'
else
    TXTENTRY='N'
    TXTCENTRY='N'
fi

# Construct a PHP serialize() string representing all results, and print it.
# The idea is that this string can be provided to update the database with
# the result of this script.
SERIALLEN=6
RV='a:'$SERIALLEN':{s:3:"url";'$URLENTRY';s:3:"vid";'$VIDENTRY';s:3:"aud";'\
$AUDENTRY';s:3:"txt";'$TXTENTRY';s:4:"txtc";'$TXTCENTRY';s:3:"tit";'\
$TITLEENTRY';}'
echo "$RV" >"$ORFILE"
echo "$ORFILE" >>/tmp/orfileprob

# Return whence we came
cd $CWD

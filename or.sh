#!/bin/bash
#
# or.sh <project name>
#

PROJNAME=$1
TEMP_DIR="/tmp/effort02"
WERE_LOOKING_AT=$TEMP_DIR"/were_looking_at"
WAT_DO_NEXT=$TEMP_DIR"/what_to_do_next"
BREAK_LINK_FROM=$TEMP_DIR"/break_link_from"
BREAK_LINK_TO=$TEMP_DIR"/break_link_to"
DATA_DIR='./data'
PREDICATE_LINKS=0
PREDICATE_AKA_OF=1

#
# Initialising
if [ "$PROJNAME" = '' ]; then
    echo 'No project name found - exiting'
    exit
elif ! test -d "$DATA_DIR/$PROJNAME"; then
    echo 'New project - creating'
    mkdir -p "$DATA_DIR/$PROJNAME/db"
else
    echo "Existing project - clearing temporary state"
    rm -f "$WERE_LOOKING_AT" "$WAT_DO_NEXT" "$BREAK_LINK_FROM" "$BREAK_LINK_TO"
fi

#
# Main loop
ARG='?'
while [ 1 -eq 1 ]; do
    # Flip between Things to show until a menu item
    # is requested
    while [ "$ARG" != '/' ] && [ "$ARG" != '.' ] && [ "$ARG" != '>' ] && \
	[ "$ARG" != 'RV' ] && [ "$ARG" != 'RA' ] && [ "$ARG" != 'RM' ] && \
	[ "$ARG" != 'S' ] && [ "$ARG" != '[' ] && [ "$ARG" != ']' ] && \
	[ "$ARG" != '{' ] && [ "$ARG" != '-' ]; do
	php show-thing.php "$PROJNAME" $ARG
	ARG=$(cat $WAT_DO_NEXT)
    done;

    # Handle menu items then get back to showing
    # Things
    ONSHOW=$(cat $WERE_LOOKING_AT);
    if [ "$ARG" = '/' ]; then
	php add-link.php "$PROJNAME" $ONSHOW $PREDICATE_LINKS

    elif [ "$ARG" = '{' ]; then
	php add-link.php "$PROJNAME" $ONSHOW $PREDICATE_AKA_OF

    elif [ "$ARG" = '.' ]; then
	php edit-thing.php "$PROJNAME" $ONSHOW

    elif [ "$ARG" = '>' ]; then
	FROM=$(cat $BREAK_LINK_FROM);
	TO=$(cat $BREAK_LINK_TO);
	php break-link.php "$PROJNAME" $FROM $TO

    elif [ "$ARG" = 'RV' ]; then
	php show-reviews.php "$PROJNAME" $ONSHOW

    elif [ "$ARG" = 'RA' ]; then
	TFILE=$(mktemp $TEMP_DIR/foo.XXXXXX)
	touch $TFILE
	php add-review.php "$PROJNAME" $TFILE $ONSHOW
	rm -f $TFILE

    elif [ "$ARG" = 'RM' ]; then
	TFILE=$(mktemp $TEMP_DIR/foo.XXXXXX)
	touch $TFILE
	php mark-reviewed.php "$PROJNAME" $TFILE $ONSHOW
	rm -f $TFILE

    elif [ "$ARG" = 'S' ]; then
	php search.php "$PROJNAME" $ONSHOW

    elif [ "$ARG" = '[' ]; then
	php edit-nuance.php "$PROJNAME" $ONSHOW

    elif [ "$ARG" = ']' ]; then
	SUBJECT=$(cat $BREAK_LINK_TO);
	OBJECT=$(cat $BREAK_LINK_FROM);
	php connect-as-aka.php "$PROJNAME" $SUBJECT $OBJECT

    elif [ "$ARG" = '-' ]; then
	php delete-thing.php "$PROJNAME" $ONSHOW
    fi

    ARG=$(cat $WAT_DO_NEXT)
done;

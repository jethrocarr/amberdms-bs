#!/bin/bash

# A simple wrapper script for wkhtmltopdf
# James Cole: intangi@gmail.com
# Tweaked slightly by Jethro Carr for suitability purposes.

#What this script does:
#1. Allows wkhtmltopdf to be used without an X server creating
#   display numbers randomly to not conflict with other instances
#   Also doesn't listen on tcp and uses XAUTHORITY files for security
#2. Optionally accepts html from STDIN
#3. Dumps to STDOUT if no output file is specified
#4. All messages are printed via STDERR now so as to not conflict
#   with piping data

#Depends on Xvfb and wkhtmltopdf

#Make sure wkhtmltopdf is either installed in the path,
#or it is in the same directory as where you're running this script from.

######################################

#We have to put .html on the end because wkhtmltopdf won't render without the extension on the input file
STDIN=$(tempfile -s .html);
while read -t1 data</dev/stdin; do
	echo $data>>$STDIN
done

INFILE=false;
USE_STDIN=false;
if [ -s $STDIN ]; then
	INFILE=$STDIN;
	USE_STDIN=true;
elif [ $# != 0 ]; then
	INFILE=$1;
fi;

OUTFILE=false;
USE_STDOUT=false;
if [ $USE_STDIN == true ]; then
	if [ $# == 0 ]; then
		OUTFILE=$(tempfile);
		USE_STDOUT=true;
	else
		OUTFILE=$1
	fi;
else
	if [ $# == 1 ]; then
		OUTFILE=$(tempfile);
		USE_STDOUT=true;
	else
		OUTFILE=$2
	fi;
fi;

if [ $INFILE == false ] || [ $OUTFILE == false ]; then
	echo "$0 Expects at least the input argument. Accepts STDIN automatically:" >&2
	echo "$0 [file.html|http://example.com] [output.pdf|]">&2;
	exit 1;
fi;

WKHTMLTOPDF='';
if [ -f wkhtmltopdf ]; then
	WKHTMLTOPDF=./wkhtmltopdf;
elif which wkhtmltopdf; then
	WKHTMLTOPDF=$(which wkhtmltopdf);
else
	echo Unable to find wkhtmltopdf. >&2;
	exit 1;
fi;

DISP=$RANDOM let "DISP %= 500";
while [ -f /tmp/.X${DISP}-lock ]; do 
	DISP=$RANDOM let "DISP %= 500";
done;

XAUTHORITY=$(tempfile);
Xvfb -screen 0 800x600x24 -dpi 96 -terminate -auth $XAUTHORITY -nolisten tcp :$DISP >/dev/null 2>&1 &

ERRLOG=$(tempfile);

ERR=false;

#echo $INFILE $OUTFILE
#exit;

DISPLAY=:$DISP $WKHTMLTOPDF -s A4 -B 5mm -L 5mm -R 5mm -T 5mm $INFILE $OUTFILE >&2 2>$ERRLOG;

if [ $? != 0 ]; then
	echo Looks like there was an error: >&2;
	cat $ERRLOG >&2
	ERR=true;
elif [ $USE_STDOUT == true ]; then
	cat $OUTFILE;
fi;
rm $ERRLOG;
rm $XAUTHORITY;

kill $! 2>/dev/null;

if [ $USE_STDIN == true ]; then
	rm $INFILE;
fi;
if [ $USE_STDOUT == true ]; then
	rm $OUTFILE;
fi;

if [ $ERR == true ]; then
	exit 1;
fi;


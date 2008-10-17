#!/bin/bash
# $Id$

# Script to test a solution using the standard environment.
# Written by Jaap Eldering, April 2004
#
# Run this script in a problem-directory. Expects testdata
# input in 'testdata.in' and output in 'testdata.out'.
#
# Usage: $0 <filename>
#
# $Id$
#
# Extended pattern matching:
shopt -s extglob

NLANG=5
LANG=('c' 'cpp'        'java' 'pascal'   'haskell')
EXTS=('c' 'cpp cc c++' 'java' 'pas pp p' 'hs'     )

TESTSOL=$HOME/system/judge/test_solution.sh

TESTIN=testdata.in
TESTOUT=testdata.out
SAMPLEIN=testsample.in
SAMPLEOUT=testsample.out

TMPDIR="test.$$.tmp"
TIMELIMIT=10

if [ $# -ne 1 ]; then
	echo "Wrong number of arguments!"
	exit 1
fi

FILE="$1"

if [ "$VERBOSE" ]; then
	[[ "$VERBOSE" = [2-7] ]] || VERBOSE=7 # loglevel LOG_DEBUG
	export VERBOSE
fi

if [ "$DEBUG" ]; then
	export DEBUG
fi

test_sol ()
{
	local lang base="" file="$1" in="$2" out="$3"
	# First determine language:
	for ((i=0; i<NLANG; i++)); do
		for ext in ${EXTS[$i]}; do
			if [[ "$file" = *.$ext ]]; then
				base="${file%.$ext}"
				lang="$i"
				break 2
			fi
		done
	done
	if [ -z "$base" ]; then
		echo "Could not determine language!?"
		exit 1
	fi

	mkdir "$TMPDIR/$file"
	$TESTSOL "$file" "${LANG[$lang]}" "$in" "$out" "$TIMELIMIT" "$TMPDIR/$file"
	exitcode=$?
	if [ $exitcode -ne 0 ]; then
		printf "Error: "
		case $exitcode in
		1) echo "compile";;
		2) echo "timelimit";;
		3) echo "runtime error";;
		4) echo "no output";;
		5) echo "wrong answer";;
		*) echo "script error $exitcode";;
		esac
	else
		printf "Correct, runtime: %s\n" `cat $TMPDIR/$file/program.time`
	fi

}

[ -r "$TESTIN"    ] || { echo "No input testdata found."; exit 1; }
[ -r "$TESTOUT"   ] || { echo "No output testdata found."; exit 1; }
[ -r "$SAMPLEIN"  ] || { echo "No input sample testdata found."; exit 1; }
[ -r "$SAMPLEOUT" ] || { echo "No output sample testdata found."; exit 1; }

mkdir $TMPDIR
test_sol "$FILE" "$SAMPLEIN" "$SAMPLEOUT"

[ "$DEBUG" ] || rm -rf "$TMPDIR"

exit 0
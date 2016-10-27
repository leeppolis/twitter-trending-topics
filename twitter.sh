#!/bin/sh
if ps -ef | grep -v grep | grep "index.php twitter" ; then
	echo "Already running";
	exit 0;
else
	echo "Starting";
	# Replace this with the actual path to your document root
	# Usually /var/www/html
	# /home/user/public_html
	cd THE_PATH_TO_YOUR_SCRIPT_HERE;
	nohup php ./index.php twitter > /dev/null 2>&1;
fi
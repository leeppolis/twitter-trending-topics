#!/bin/sh
if ps -ef | grep -v grep | grep "index.php twitter" ; then
	echo "Twitter import running";
	exit 0;
else
	echo "Starting scraper";
	# Replace this with the actual path to your document root
	# Usually /var/www/html
	# /home/user/public_html
	cd THE_PATH_TO_YOUR_SCRIPT_HERE;
	nohup php ./index.php scraper > /dev/null 2>&1;
	sleep 5s;
	nohup php ./index.php scraper > /dev/null 2>&1;
fi
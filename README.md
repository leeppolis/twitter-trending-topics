# Twitter Trending Topics
This repository contains a set of scripts that automatically generate a custom web magazine based on Twitter's Trending Topics.
If this description is obscure to you, you can have a look at the running example at: [https://trending.simonelippolis.com](https://trending.simonelippolis.com)

This set of scripts is the result of an experiment of rapid prototyping. Feel free to download and use it, but consider that this code was not intended to run on production servers and might need some review.

Setting up this application requires basic PHP and mySql skills, and some *nix scripting and configuration knowledge. HTML, JavaScript, and CSS knowledge are required to customize the default theme or to create a new one.

Apache, lighthttpd, or nginx are required to run the frontend (or see the "magazine" in a web browser), while the bot that consumes the Twitter APIs and the web page scraper are run from command line by cron.

This set of script has been tested on an Ubuntu box, with 2 cores and 2Gb of RAM. The same server runs a dozen of other websites.

This application is based on a modified version of [PHP Boilerplate](https://github.com/leeppolis/php-boilerplate) and uses 3rd party components available via [Composer](https://getcomposer.org).

For any comment or question you can get in touch with me on [my website](https://simonelippolis.com) or by tweeting to [@simonelippolis](https://twitter.com/simonelippolis).

## Showcase
Should you use this app for one of your projects, please [drop me a line](https://twitter.com/simonelippolis), I'll add your link in this section.

- [Demo site](https://trending.simonelippolis.com)

## How to install

### Prerequisites
- A web server (tested only on Apache)
- PHP 5.x
- MySql 5.x
- Access to cron

The application requires all the web traffic to be redirected to the main `index.php` file using `mod_rewrite` (or equivalent). The ability to define rewrite rules either in the virtual host configuration or in a custom `.htaccess` file is a requirement. 

Both `TwitterOAuth` and `Goutte` use cURL for consuming Twitter APIs and scraping destination links, be sure that `mod_curl` is installed on your server.


### Third party components
Third party libraries are defined in the `composer.json` file.

- `Twig` ver. `1.26.1`
- `Goutte` ver. `3.1.2`
- `TwitterOAuth` ver. `0.6.6`

### Step-by-step

1. Clone this repository to your webserver's public folder
2. Use [Composer](https://getcomposer.org) to get all the required dependencies. From your terminal, type `composer update` (this command depends on your configuration, check [Composer](https://getcomposer.org)'s docs for more options)
3. Create a new Database on your mySql server, and create the required tables importing the `database.sql` file
4. Check if the folder './core/cache' is writable by your webserver. If not, `chmod 777 ./core/cache`. This folder will contain all the cached template files and the twitter bot and scraper logs.
5. Open `settings.php` with your favourite editor

### Settings
The `settings.php` file contains all the configuration for the application. You'l need to fill that with your database configuration, name and description of your website, your timezone, etc. It also contains the routing configuration. Each configuration variable should be self-explanatory, but look at the inline comments to know more about each property. 

### Routing
As already stated above, the application needs `mod_rewrite` enabled on your server. A copy of a working `.htaccess` file is included in the repository. If you have access to the virtual host configuration, my advice is to move the rules defined in the `.htaccess` file directly to the apache config file: this will make everything faster.

Routing is defined by the `$routes` hash in `settings.php`. The `key` of the hash contains a regex defining the rules to be matched for the file in `value` to be executed.

In this way, the homepage of the application is defined as 

    '/^(\/)?$/' => 'home.php'
    
and the "browse" page (the script that returns the JSON with all the articles related to a certain #hashtag)

	'/^browse(\/)?([a-zA-Z0-9_%#\+\-.]*)?$/' => 'browse.php'

Source files are located in `/core/sources`.
Check [PHP Boilerplate](https://github.com/leeppolis/php-boilerplate) README file for more info about routing and file and directory structure.

> #### Note
> Files executed from the command line (like `twitter.php` and `scraper.php`) doesn't need to have a defined route.

### Twitter API
In order to be able to connect to and to consume the Twitter API you need to have a valid key/secret pair, and configure the relative variables in `settings.php`. To get a valid API key, and for more information on how Twitter API works, please visit [Twitter's developer portal](https://dev.twitter.com).

## How to start indexing
Once you configured everything, you should be able to start indexing.
My advice is to try to start everything manually before configuring your cron job.
To grab the current Twitter trending topic, move to the directory where you installed the application and type

	# php index.php twitter
	
This command will start the first few steps required to fill the database:

1. Will connect to the Twitter API to grub the current trending topics, and save them in the `topics` table in your database
2. For each topic, will then search Twitter's API to get tweets related to that topic
3. Will analyze the received tweets, and save the links contained in each of them into the temporary `links` table in your database.

This operation should finish within 5 minutes.

Once finished, it's time to start scraping.
Type

	# php index.php scraper
	
in your terminal to start the scraper. This operation will read all the links contained in the temporary table, check them against some rule (defined in `\SL\Utilities\Linkify::validURL()` in `./core/classes/Utilities/Linkify`), start a cURL request to grab title, description and image. Only valid links will then be moved to the `articles` table in the database, and relations will be created.
This script handles 100 links for each time it is run, start it manually until you see the `links` table becoming empty.

> #### Consuming Twitter APIs
> The application uses [TwitterOAuth](https://github.com/abraham/twitteroauth) to consume Twitter's APIs. Have a look both at its documentation, and [Twitter API docs](https://dev.twitter.com/overview/documentation) to find wonderful ways to extend this application's functionalities.

Once finshed, pointing your browser to `http://youdomain` should show you the homepage of the application, showing a maximum of 7 different trending topics among the ones that have been grabbed the last time the script has been run. The topics shown are ordered in descending order based on the number of valid links found for each topic. Only topics with more than 1 valid link are shown.

> #### Blacklisting URLs
> open `./core/classes/Utilities/Blacklist` you'll see a `validLink()` method. The `if` statement returns true if the current link does not point to the listed domains. By default, any link that points to twitter.com or t.co should be considered invaid (you'll finish indexing retweets and reply to tweets related to trending topics instead of 3rd party articles about those topics). I added a filter to domains that I recognize as SPAM, other aggregators (i.e. paper.li, news.google.com), or shortened links that for some reason the scraper has not been able to follow (i.e. goo.gl, sh.st, etc.). Add or remove controls and conditions depending on your preferences.

While these two scripts run, you should be able to see a log describing what they are doing. Once you tested it running for the first time, if everything works, you'll be able to add these commands to your cron in order to execute them automatically.

> #### Note
> The debug text shown on your terminal are also saved in 2 different files: `./core/cache/_import_log.txt` and `./core/cache/scraper_log.txt`: this will help you debugging once these script will be configured to run via cron.

The repository contains 2 shell scripts that you might find useful. Open each of them with your favourite text editor, and adjust file paths according to your server configuration.
On the test server `twitter.sh` is executed every 30 minutes, while `scraper.sh` is configured to be executed every minute. In this way, being my box a 2 cores, I've been able to partially parallelize scraping tasks without running the risk to go out of memory. Remember that the frequency of updates must take into consideration your server hardware, other software running on the same machine, and Twitter API limits.

#### The twitter.sh file
This script first checks if the twitter bot is already running, if not moves the current directory to the one where you installed the application, and starts the bot.

#### The scraper.sh script
This script first checks if the twitter bot is running; if not, moves the current directory to the one where you instaled the application, then starts 2 instances of the scraper with a 5 seconds delay. I do this to optimize resources on the server. If your box has more than 2 cores, you can start more parallel instances of the scraper.
Each scraper instance will end in more or less one minute; handling 100 links each time, it is able to scrape more or less 200 links every minute. It will be easy for you to compute how much time is needed to completely finish each scraping session, starting from the first Twitter API call.
On my machine, it takes 5 to 9 minute depending on the number of valid links found. 

## Theming
HTML, CSS, images, and javascript are in the `themes/2016` folder.
If you want to create a custom theme, just duplicate with another name, customize all the assets, and then change the `$theme_name` variable in `settings.php`.
While developing, you can tell the website to load an alternative theme by adding 'theme=YOUR_THEME_NAME` as a querystring parameter.

The default theme, called _2016_, shows every available variable available for the theme. As you'll notice, the list of filtered links is returned as a JSON object, and then rendered via Javascript using [Mustache](https://github.com/janl/mustache.js/). Changing this behavior will be easy if you know a little PHP: edit `core/sources/browse.php` and comment out

	$format = 'json';
	
Then create a `browse.html` template in your template directory and edit the `home.html` template to be sure that every link points to the correct resource (at the moment there is a Javascript that checks an 'hashchange` event).

> #### Article's images
> The application also makes available for the theme the `articles.image` variable. While usually it contains a URL to an image or `null` my advice is to proxy it through the `image.php` script.
> A lot of websites enforce hotline preventions policies, and this might result in a lot of broken images on your site. Proving images using `image.php` should avoid this problem, by serving a predefined placeholder in case the scraped image URL is not available.
>  To leverage the possibilities offered by the `image.php` script, replace the `src` tag of the image in your template:
>  
> `<img src="{{ article.image }}"/>`
>
> with
> 
> `<img src="{{ output.properties.base_url }}/image?i={{ article.image }}&r={{ article.link }}" />` 

The templating engine is [Twig](http://twig.sensiolabs.org), please check the [docs](http://twig.sensiolabs.org/documentation) to know more.

## License
MIT License

Copyright (c) 2016 Simone Lippolis

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.


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
- A web server
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

#### Note
Files executed from the command line (like `twitter.php` and `scraper.php`) doesn't need to have a defined route.


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


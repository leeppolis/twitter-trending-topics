<?php
	// Default timezone
	date_default_timezone_set('Europe/Rome');
	
	// DNC Do not change, initializes the M_CLI constant to true if the script
	// is being run via command line.
	define( 'M_CLI', (PHP_SAPI == 'cli') ? true : false );
	// End DNC
	
	// Define DB information
	define( 'M_DB_HOST', '' );
	define( 'M_DB_CATALOG', '' );
	define( 'M_DB_USER', '' );
	define( 'M_DB_PASSWORD', '' );
	
	// Define PATHS
	// DNC - Do not change if you keep the default dir structure
	define( 'M_BASE_PATH', dirname(__FILE__) );
	define( 'M_SOURCES', M_BASE_PATH . '/core/sources' );
	define( 'M_CACHE', M_BASE_PATH . '/core/cache' );
	define( 'M_TEMPLATES_CACHE', M_CACHE . '/templates' );
	define( 'M_CLASSES', M_BASE_PATH . '/core/classes' );
	define( 'M_UPLOADS', M_BASE_PATH . '/gallery/uploads' );
	define( 'M_COMPOSER', M_BASE_PATH . '/vendor/autoload.php' );
	// End DNC
	
	// CLI - If the script is called by a web server, the following constants
	// and variables are required to work properly.
	if ( !M_CLI ) {
		// Define the protocol, either http or https
		// Usually the script is able to auto-detect this feature, but if you
		// use external services like Cloudflare's "Flexible" SSL please set it
		// to "https" manually
		$protocol =  "http";
		if (
			(
				isset($_SERVER['HTTP_X_FORWARDED_PORT'])
				&& $_SERVER['HTTP_X_FORWARDED_PORT'] == "443")
				|| (
					isset($_SERVER['HTTPS'])
					&& $_SERVER['HTTPS'] !== null
				)
				|| (
					isset($_SERVER["SERVER_PORT"])
					&& $_SERVER["SERVER_PORT"] == "443"
				)
			) {
			$protocol = "https";
		}
		define( 'M_DOMAIN', $_SERVER['HTTP_HOST'] );
		define( 'M_BASE_URL', $protocol . '://' . M_DOMAIN );
	
		// Define themes settings
		// theme_name is the name of the folder under M_THEME_DIR where you put
		// your Twig's templates.
		// It can be overridden by a "theme" querystring variable, useful when
		// you're developing a new theme and want to test it before going live.
		$theme_name = "2016";
		$theme_name = ( isset($_GET['theme']) && $_GET['theme']) ? $_GET['theme'] : $theme_name;
		define( 'M_THEME', $theme_name );
		define( 'M_THEME_DIR', M_BASE_PATH . '/themes/' . M_THEME );
		define( 'M_THEME_URL', M_BASE_URL . '/themes/' . M_THEME );
		// M_IMAGE_PLACEHOLDER is the URL of the default placeholder image
		// returned to the frontend in the case the article's image is not
		// accessible (i.e. the destination server enforces an hotlink
		// prevention rule
		define( 'M_IMAGE_PLACEHOLDER', M_THEME_URL . '/img/placeholder.png' );
	}
	// End CLI 
	
	// The name of your website, shown in the default HTML <title> tag. Each
	// page can override this by changing the $output->head->title variable 
	define( 'M_NAME', 'Trending topics' );
	// The claim of your website, shown in HTML <title>. Each page can override
	// this by changing the $output->head->title variable
	define( 'M_CLAIM', 'Find the best resources about the hottest topics.' );
	// The description of your website, shown in HTML <meta name="description">
	// tag. Each page can override this by changing the
	// $output->head->description variable
	define( 'M_DESCRIPTION', 'Browse the resources that generated trending topics on Twitter' );
	// The keywords of your website, shown in HTML <meta name="keywords"> tag.
	// Each page can override this by changing the $output->head->keywords
	// variable
	define( 'M_KEYWORDS', '' );
	// The default separator used in the HTML <title> tag to separate title,
	// claim, and other texts
	define( 'M_SEPARATOR', '|' );
	
	// Toggle debug & caching
	// The M_DEBUG constant refers to the template engine debugging options.
	// Set it to true to enable Twig or Smarty's debug mode.
	// To enable PHP debug, create an empty .debug file in the root of your
	// web server
	define( 'M_DEBUG', false );
	define( 'M_ENABLE_TEMPLATE_CACHE', true );
	// If set to true, any tentative to render a page as JSON object will fail
	// with an error message
	define( 'M_ALLOW_JSON', true );
	
	// Define routes
	// Routes are defined in an hash array in the following format:
	// 
	// RegexExp => FileName
	// 
	// The application checks the regex against the requeste URL, once it finds
	// one matching, executes the code in M_SOURCES/FileName
	// 
	// Example:
	// 
	// | RequestUrl				| Matches										| Executes		|
	// ==========================================================================================
	// | /browse/#!trendingtopic	| /^browse(\/)?([a-zA-Z0-9_%#\+\-.]*)?$/	| browse.php	|
	// | /browse/#!i+should+match	| /^browse(\/)?([a-zA-Z0-9_%#\+\-.]*)?$/	| browse.php	|
	// ------------------------------------------------------------------------------------------
	// | /test						| /^test(\/)?$/								| test.php		|
	// ------------------------------------------------------------------------------------------
	// | /browse/news/1				| /^browse\/news\/([0-9]*)(\/)?$/			| news.php		|
	// | /browse/news/15			| /^browse\/news\/([0-9]*)(\/)?$/			| news.php		|
	// | /browse/news/152			| /^browse\/news\/([0-9]*)(\/)?$/			| news.php		|
	// ------------------------------------------------------------------------------------------
	$routes = array(
		'/^(\/)?$/' => 'home.php',
		'/^browse(\/)?([a-zA-Z0-9_%#\+\-.]*)?$/' => 'browse.php',
		'/^image(\/)?$/' => 'image.php' 
	);
	
	// Twitter's Configuration
	// Populate these fields with you Twitter API keys you can get from
	// https://dev.twitter.com
	// You'll need to register a new app, and then you'll be able to generate 
	// your personale keys.
	define( 'TW_CONSUMER_KEY', '');
	define( 'TW_CONSUMER_SECRET', '');
	define( 'TW_ACCESS_TOKEN', '');
	define( 'TW_ACCESS_SECRET', '');
	
	// These config are related to Twitter APIs options for trending topics and 
	// for Twitter search API.
	// 
	// TW_GEOPOSITION_ID 
	// Trending topics can be global or local. Localization is performed using
	// WOEIDs, a standard defined by Yahoo. Unfortunately, Yahoo's API to get
	// WOEIDs are no longer available, even if the documentation pages are still 
	// online. 
	// At the URL: 
	// http://woeid.rosselliot.co.nz/lookup/italy
	// You can find the WOEID for your country. Here are a copule examples of
	// valid WOEIDs: 
	//
	// Global = 1
	// Italy = 23424853
	// USA = 23424977
	//
	// TW_LANG
	// ISO code for a language. Used in the Twitter search results to filter 
	// tweets written in a language different than the one defined.
	//
	// TW_MAX_TWEETS
	// The number of tweets the API should return as a result of our search. 
	// Check Twitter search API limits for more info.
	//
	// For more info about the Twitter API please visit
	// https://dev.twitter.com
	//
	define( 'TW_GEOPOSITION_ID', 23424977 );
	define( 'TW_LANG', 'en' );
	define( 'TW_MAX_TWEETS', 100);
?>
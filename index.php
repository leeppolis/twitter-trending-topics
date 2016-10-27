<?php
	$show_error = 0;
	if ( file_exists("./.debug") ) {
		$show_error = E_ALL;
	}
	error_reporting( $show_error );	
	$settings = './settings.php';
	$page = '505.php';
	
	$output = new stdClass();
	$output->error = "missing config file";
	
	if ( file_exists( $settings ) ) {
		require_once( $settings );
		if ( !M_CLI ) session_start([ "cookie_domain" => M_DOMAIN ]);
		
		// Setup
		// Include Classes
		if (file_exists( M_CLASSES ) ):
			if ( $handle = opendir( M_CLASSES ) ):
				while ( false !== ( $entry = readdir($handle) ) ):
					if ( $entry != "." && $entry != ".." && ( is_dir( M_CLASSES . '/' . $entry ) ) ):
						if ( $handle2 = opendir( M_CLASSES . '/' . $entry ) ):
							while ( false !== ( $entry2 = readdir( $handle2 ) ) ):
								if ($entry2 != "." && $entry2 != ".." && (strstr($entry2, '.class.php') !== false)):
									require_once( M_CLASSES . '/' . $entry . '/' . $entry2 );
								endif;
							endwhile;
						endif;
					endif;
				endwhile;
			endif;
		endif;
		
		// Include composer dependencies
		if (file_exists(M_COMPOSER)) require_once( M_COMPOSER );
		
		if ( M_CLI ) {
			$page = $argv[1] . ".php";
		} else {
			// Routing //
			$page = '404.php';	
			$output->error = "Invalid route.";	
			$parts = null;
			$self = substr( $_SERVER['REQUEST_URI'] , 1 , strlen ( $_SERVER['REQUEST_URI'] ) );
			$format = (isset($_REQUEST['format'])) ? $_REQUEST['format'] : 'html';	
			$request_uri = explode('?', $self);
			
			foreach( $routes as $key => $value ) {
				if ( preg_match( $key , $request_uri[0] ) ) {
					$page = $value;
					break;
				}
			}
		}
		
		$output->head = new stdClass();
		$output->head->name = M_NAME;
		$output->head->title = M_NAME . ' ' . M_SEPARATOR . ' ' . M_CLAIM;
		$output->head->description = M_DESCRIPTION;
		$output->head->keywords = M_KEYWORDS;
		
		$output->properties = new stdClass();
		if ( !M_CLI ) {
			$output->properties->base_url = M_BASE_URL;
			$output->properties->theme_url = M_THEME_URL;
			$output->properties->my_url = $self;
			$output->properties->my_url_clean = $request_uri[0];
			$output->properties->referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null;
		}
		$output->properties->time = time();
		$output->properties->UTCDateTime = gmdate('c',$output->properties->time);
		$output->properties->debug = $show_error;
		
		$output->page = new stdClass();
			
		if ($page != '404.php' && $page != '505.php' && file_exists( M_SOURCES )) {
			if ( !file_exists( M_SOURCES .'/' . $page ) ) {
				$page = '404.php';
				$output->error = "Source file not found (" . M_SOURCES . '/' . $page . ")";
			} else {
				$output->error = null;
			}
		}
	}
		
	// Init Templating engine //
	if ( !M_CLI ) {
		if ( class_exists( "Twig_Autoloader") ) {
			Twig_Autoloader::register();
			$twig_loader = new Twig_Loader_Filesystem( M_THEME_DIR );
			$twig = new Twig_Environment($twig_loader, array(
			    'cache' => M_CACHE,
			    'debug' => M_DEBUG
			));
			$renderer = new \SL\Utilities\Renderer( $twig );
		} else if ( class_exists( "Smarty") ) {
			$smarty = new Smarty();
			$smarty->setTemplateDir( M_THEME_DIR );
			$smarty->setCompileDir( M_TEMPLATES_CACHE );
			$smarty->setCacheDir( M_CACHE );
			$smarty->setCaching( M_ENABLE_TEMPLATE_CACHE );
			$smarty->debugging = M_DEBUG;
			$renderer = new \SL\Utilities\Renderer( $smarty );
		}	
	}
	// Test DB Connection
	try {
    	$DBH = new PDO("mysql:dbname=" . M_DB_CATALOG . ";charset=UTF8;host=" . M_DB_HOST, M_DB_USER, M_DB_PASSWORD);
    	$DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    	$DBH->exec("set names utf8");
	} catch (PDOException $e) {
    	$page = "505.php";
    	$output->error = "unable to connect to the DB: " . $e->getMessage();
	}
		
	include( M_SOURCES .'/' . $page );
	die();
?>	
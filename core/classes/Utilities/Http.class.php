<?php
/**
 * Http.class.php
 * A collection of static method to handle simple http-related tasks
 *
 * @author Simone Lippolis <simone@simonelippolis.com>
 * @version 1.0
 * @package SL
 * @subpackage Utilities
 */
	namespace SL\Utilities;
	
/**
 * @method void httpCode( [ int $code ] )
 * @method void contentType( [ string $type ] )
 * @method mixed redirect( string $url [, int $code, bool $die] )
 */	
	Class Http {

/**
 * Given an HTTP code, sends the appropriate HTTP Header to the client
 *
 * @param int $code
 * @return void 
 *
 */	
		
		public static function httpCode( $code = 301 ) {
			
			switch( $code ) {
				case "401":
					$message = "HTTP/1.0 401 Unauthorized";
				case "404":
					$message = "HTTP/1.0 404 Not found";
					break;
				case "505":
					$message = "HTTP/1.0 505 Internal Server Error";
					break;
				case "301":
					$message = "HTTP/1.0 301 Moved Permanently";
					break;
				default:
					$message = "HTTP/1.0 302 Moved Temporarly";
					break;
			}
			
			header($message);
		}

/**
 * Given an content type string, sends the appropriate HTTP Header to the client
 *
 * Accepts either:
 * - download
 * - text
 * - html
 * - json
 *
 * Defaults to json
 *
 * @param string $type
 * @return void 
 *
 */			
		public static function contentType( $type = "json" ) {
			
			switch( strtolower($type) ) {
				case "download":
					$message = "Content-type:application/force-download";
					break;
				case "text":
					$message = "Content-type:text/plain";
					break;
				case "html":
					$message = "Content-type:text/html";
					break;
				default:
					$message = "Content-type:application/json";
					break;
			}
			
			header($message);
		}

/**
 * Given a url and an HTTP code, sends the Redirect Header to the client.
 *
 * @param string $url The URL of the destination page
 * @param int $code The HTTP code (usually 302 or 301), defaults to 302
 * @param bool $die A boolean indicating if the current execution should end soon after the redirect, defaults to true
 * @return void 
 *
 */	
		
		public static function redirect( $url, $code = 302, $die = true ) {
			if ( !$url ) return false;
			
			\SL\Utilities\Http::httpCode( $code );
			
			header('Location:' . $url);
			
			if ($die) die();
				else return true;
		}
	}
?>
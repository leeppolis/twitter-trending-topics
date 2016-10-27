<?php
	
/**
 * Linkify.class.php
 * A collection of static method to handle strings containing links
 *
 * @author Simone Lippolis <simone@simonelippolis.com>
 * @version 1.0
 * @package SL
 * @subpackage Utilities
 */
	namespace SL\Utilities;
	
/**
 * @method string twitterLinks( string $text )
 * @method bool hasLinks( string $text )
 * @method mixed returnLinks( string $text )
 */
	Class Linkify {		
		
/**
 * Given a the body of a tweet (or any other string), returns a string with all the entity converted to the appropriate HTML link.
 *
 * :// strings are converted to links to external pages
 * @mentions are converted to links to the appropriate Twitter profile
 * #tags are converted to links to the Twitter search page
 *
 * @param string $text
 * @return string 
 *
 */		
 
		public static function twitterLinks($text) {
			//Convert urls to <a> links
			$text = preg_replace("/([\w]+\:\/\/[\w-?&;#~=\.\/\@_]+[\w\/])/", "<a target=\"_blank\" href=\"$1\">$1</a>", $tweet);
			
			//Convert hashtags to twitter searches in <a> links
			$text = preg_replace("/#([A-Za-z0-9\/\._]*)/", "<a target=\"_blank\" href=\"http://twitter.com/search?q=$1\">#$1</a>", $tweet);
			
			//Convert attags to twitter profiles in <a> links
			$text = preg_replace("/@([A-Za-z0-9\/\._]*)/", "<a href=\"http://www.twitter.com/$1\" target=\"_blank\">@$1</a>", $tweet);
			
			return $text;
		}		
		
/**
 * Given a string, returns wether it contains an URL or not.
 *
 * @param string $text
 * @return bool 
 *
 */			
		
		public static function hasLinks($text) {
			$output = false;
			preg_match_all("/([\w]+\:\/\/[\w-?&;#~=\.\/\@_]+[\w\/])/i", $text, $matches);
			$links = \SL\Utilities\Linkify::returnLinks($text);
			if ( $links ) $output = true;
			return $output;
		}	
		
/**
 * Given a string, returns an array with all the URLs found in the string, or false if the string doesn't contain any URL.
 *
 * @param string $text
 * @return mixed 
 *
 */			
		
		public static function returnLinks($text) {
			$output = false;
			preg_match_all("/([\w]+\:\/\/[\w-?&;#~=\.\/\@_]+[\w\/])/i", $text, $matches);
			if ( is_array( $matches ) && count( $matches[0] ) > 0 ) {
				$output = $matches[0];
			}
			return $output;
		}		
	}
?>
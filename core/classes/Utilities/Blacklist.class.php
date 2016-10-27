<?php
/**
 * Blacklist.class.php
 * A collection of static methods useful to choose if a link should be considered valid or not
 *
 * @author Simone Lippolis <simone@simonelippolis.com>
 * @version 1.0
 * @package SL
 * @subpackage Utilities
 */
	namespace SL\Utilities;
	
/**
 * @method void validLink( string $real_link )
 * @method void validItem( string $title, string $description )
 */	
	namespace SL\Utilities;
	
	Class Blacklist {	
		
/**
 * Given a URL checks it against a collection of rules to decide wether it should be scraper or not.
 *
 * @param string $real_link
 * @return bool
 *
 */	
			
		public static function validLink( $real_link ) {
			$output = false;
			
			if (	strpos( $real_link, '://twitter.com' ) === false &&
					strpos( $real_link, '://www.twitter.com' ) === false &&
					strpos( $real_link, '://instagram.com' ) === false &&
					strpos( $real_link, '://www.instagram.com' ) === false &&
					strpos( $real_link, '://twitpic.com' ) === false &&
					strpos( $real_link, '://www.twitpic.com' ) === false &&
					strpos( $real_link, '://t.co' ) === false &&
					strpos( $real_link, '://linkis.com' ) === false &&
					strpos( $real_link, '://www.linkis.com' ) === false &&
					strpos( $real_link, '://bit.ly' ) === false &&
					strpos( $real_link, '://goo.gl' ) === false &&
					strpos( $real_link, '://twibbon.com' ) === false &&
					strpos( $real_link, '://sh.st' ) === false &&
					strpos( $real_link, '://xtistore.es' ) === false &&
					strpos( $real_link, '://www.xtistore.es' ) === false &&
					strpos( $real_link, '://thr.cm' ) === false &&
					strpos( $real_link, '://www.thr.cm' ) === false &&
					strpos( $real_link, '://ouo.press' ) === false &&
					strpos( $real_link, '://www.ouo.press' ) === false &&
					strpos( $real_link, '://gigst.rs' ) === false &&
					strpos( $real_link, '://www.gigst.rs' ) === false &&
					strpos( $real_link, '://cur.lv' ) === false &&
					strpos( $real_link, '://www.cur.lv' ) === false &&
					strpos( $real_link, '://aggbot.com' ) === false &&
					strpos( $real_link, '://www.aggbot.com' ) === false &&
					strpos( $real_link, '://imgurl.com' ) === false &&
					strpos( $real_link, '://www.imgurl.com' ) === false &&
					strpos( $real_link, '://imgur.com' ) === false &&
					strpos( $real_link, '://www.imgur.com' ) === false &&
					strpos( $real_link, '://facebook.com' ) === false &&
					strpos( $real_link, '://www.facebook.com' ) === false &&
					strpos( $real_link, '://paper.li' ) === false &&
					strpos( $real_link, '://www.paper.li' ) === false &&
					strpos( $real_link, '://news.google.com' ) === false &&
					strlen( $real_link ) > 11
			) {
				$output = true;
			}
			
			return $output;
		}
		
		
/**
 * Given title and description of a web page checks them against a collection of rules to decide wether it should be saved or not.
 *
 * @param string $title, string $description
 * @return bool
 *
 */	
		
		public static function validItem( $title, $description ) {
			$output = false;			
			if (
				$title != ""
				&& strtolower($title) != "redirecting"
				&& strtolower($title) != "redirect"
				&& strtolower($title) != "redirecting..."
				&& strtolower($title) != "redirect..."
				&& strtolower($title) != "robot check"
				&& ( strpos($title," ") !== false && $description != ""  )
				&& ( trim($title) != trim($description) )
			) {
				$output = true;
			}		
			return $output;
		}
	}
?>
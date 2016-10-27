<?php
/**
 * Log.class.php
 * Manage debug logging for twitter bot and scraper
 *
 * @author Simone Lippolis <simone@simonelippolis.com>
 * @version 1.0
 * @package SL
 * @subpackage Utilities
 */
	namespace SL\Utilities;
	
/**
 * @method void clean( string $file )
 * @method void logIt( string $file, string $message )
 */
	Class Log {

/**
 * Check the size of the $file log  file, if it's bigger that 1MB empties it.
 *
 * @param string $file The full path to the log file
 * @return void
 *
 */	

		public static function clean( $file ) {
			if ( file_exists($file ) ) {
				if ( filesize( $file ) > (1024 * 1024 * 1) ) {
					unlink( $file );
					touch( $file );
				}
			}
		}

/**
 * Echos $message to console and appends it to the log file $file.
 *
 * @param string $file The full path to the log file
 * @param string $message The debug message you want to be logged
 * @return void
 *
 */	
		
		public static function logIt( $file, $message ) {
			if ( strpos($message, "\n***") !== false ) {
				echo str_replace("\n***", "\n\033[31m***", $message );
			} else {
				echo "\033[34m" . $message;
			}
			$f = fopen( $file, "a" );
			fwrite( $f, $message );
			fclose($f);
		}
	}
?>
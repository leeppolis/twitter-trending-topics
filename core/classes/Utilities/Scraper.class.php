<?php
	namespace SL\Utilities;
	
	Class Scraper {
		
		public static function getRealURL( $url ) {
			$output = $url;
			if ( $headers = get_headers($url, 1) ) {
				$output = (isset($headers['location'])) ? ($headers['location']) : ((isset($headers['Location'])) ? ($headers['Location']) : $url);
			}
			return $output;
		}
		
		public static function getImageURI( $page_uri, $image_uri ) {
			$output = $image_uri;
			
			$image_parts = parse_url($image_uri);
			$page_parts = parse_url($page_uri);
			if (!isset($image_parts['scheme'])) {
				if (strpos($image_parts['path'], '/') === 0) {
					$output = $page_parts['scheme'] . '://' . $page_parts['host'] . $image_parts['path'];
				} else {
					$last_char = substr($page_parts['path'], -1);
					if ($last_char == '/') {
						$output = $page_parts['scheme'] . '://' . $page_parts['host'] . $page_parts['path'] . $image_parts['path'];
					} else {
						$path_parts = array_filter( explode('/', $page_parts['path']), 'strlen');
						$path_parts = array_values($path_parts);
						if (strpos($path_parts[ count($path_parts)-1 ],".") !== false) {
							$output = $output = $page_parts['scheme'] . '://' . $page_parts['host'] . str_replace($path_parts[ count($path_parts)-1 ], "", $page_parts['path']) . $image_parts['path'];
						} else {
							$output = $page_parts['scheme'] . '://' . $page_parts['host'] . $page_parts['path'] . $image_parts['path'];
						}
					}
				}
			}
			return $output;
		}
	}
?>
<?php
	$image = (isset($_GET['i'])) ? $_GET['i'] : null;
	$referrer = (isset($_GET['r'])) ? $_GET['r'] : $output->properties->base_url;
	
	$url = M_IMAGE_PLACEHOLDER;
	$content_type = 'image/png';
	
	if ( $image ) {
		$ch = curl_init($image);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_REFERER,$referrer);
		curl_setopt($ch, CURLOPT_TIMEOUT,10);
		$output = curl_exec($ch);
		$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$type = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );		
		curl_close($ch);
		if ($httpcode == 200) {
			$url = $image;
			$content_type = $type;
		}
	} 
	\Sl\Utilities\Http::contentType('custom', $content_type);
	echo file_get_contents( $url );
?>
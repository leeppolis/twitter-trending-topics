<?php
	/*
	 *
	 * HTTP 404 Error page.
	 * You should not need to edit this code, just check that your theme has a 404.html file and customize that.
	 *
	 */
	 
	header("HTTP/1.0 404 Not Found");
	$template = '404.html';
	$output->head->title = "404 Not found " . M_SEPARATOR . " " . $output->head->title;
	$renderer->render( $template, $output, $format );
?>
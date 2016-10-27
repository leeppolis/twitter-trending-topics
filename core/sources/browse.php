<?php
	$template = "browse.html";
	$format = 'json';
	$browse = $topic_query = false;
	if ( strpos($request_uri[0], '/') !== 0) {
		list( $browse, $topic_query) = explode('/', $request_uri[0] );
	}
	
	if ( !$topic_query ) SL\Utilities\Http::redirect( M_BASE_URL );
	
	$items = new \Trending\Items( $DBH );
	if ( $run = $items->getLatestValidRun() ) {
		if ( $topics = $items->getTopicsFromRunID( $run['id'], 0, 10) ) {
			$output->topics = $topics;
		}
	}
	if ( $topic = $items->getTopicByQuery( $topic_query ) ) {
		$output->head->title = "#" . str_replace( "#", "", $topic['keyword']) . "  " . M_SEPARATOR . " " . $output->head->title;
		$output->page->title = $topic['keyword'];
		$output->articles = $items->getArticlesForPages( $topic['id'], 0, 500, ' date_published DESC' );
	}
	unset( $items );
	// Do not change this line
	$renderer->render( $template, $output, $format );
?>
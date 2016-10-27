<?php
	$template = "home.html";
	$output->topics = null;
	
	$items = new \Trending\Items( $DBH );
	if ( $run = $items->getLatestValidRun() ) {
		$output->run_id = $run['id'];
		if ( $topics = $items->getTopicsForPages( $run['id'], 0, 10) ) {
			$output->topics = $topics;
		}
	}
	$output->popular = $items->getPopularTopics();
	unset( $items );
	
	// Do not change this line
	$renderer->render( $template, $output, $format );
?>
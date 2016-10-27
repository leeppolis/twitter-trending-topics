<?php		
	// If you're trying to run this script from a browser, I'll redirect you to
	// the homepage.
	// This will vener happen since this script isn't in the available routes.
	if ( !M_CLI ) { // Prevent executing from web
		SL\Utilities\Http::redirect( M_BASE_URL );
	}
	
	$start = time();
	// Do not create a new run instance in the DB more often than every $frequency minutes
	$frequency = 29;
	$delay = .5;
	$file =  M_CACHE . "/_import_log.txt";
	$items = new \Trending\Items( $DBH );
	
	\SL\Utilities\Log::clean( $file );
	$run_id = date("YmdH") . ( round( date("i") / $frequency ) * $frequency );
	
	\SL\Utilities\Log::logIt( $file, "\n\n============================================================\n\n" );
	\SL\Utilities\Log::logIt( $file, "\n\n---------------\nStarting at: " . date('r') . "\n----\n" );
	\SL\Utilities\Log::logIt( $file, "RunID: \033[33m" . $run_id . "\n\033[34m--\n" );
	
	if ( $connection = new \Abraham\TwitterOAuth\TwitterOAuth(TW_CONSUMER_KEY, TW_CONSUMER_SECRET, TW_ACCESS_TOKEN, TW_ACCESS_SECRET) ) {
// 1. Create entry on the DB
		if ( $run_db_id = $items->createRun( $run_id ) ) {
			\SL\Utilities\Log::logIt( $file,  "Entry with ID " . $run_id . "(" . $run_db_id . ") has been added to the DB (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
// 2. Get trending topics
			$loop = 1;
			$done = false;
			while (!$done) {
				try {
					\SL\Utilities\Log::logIt( $file,  "Connecting to Twitter trend API, try # " . $loop . " - " . $run_id . "(" . $run_db_id . ") has been added to the DB (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
					$trends = $connection-> get("trends/place", [ "id" => TW_GEOPOSITION_ID]);
					$done = true;
				} catch(Exception $e) {
					\SL\Utilities\Log::logIt( $file,  "\n*** Failed Twitter API trend call " . $e->getMessage() . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n");
					if ($loop > 3) {
						\SL\Utilities\Log::logIt( $file,  "\n*** Failed 3 retries, I quit (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n");
						$done = true;
					}
				}
				$loop++;
			}
			if (isset($trends->errors) && is_array($trends->errors)) {
				\SL\Utilities\Log::logIt( $file,  "\n*** " . $trends->errors[0]->message . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n");
				echo $trends->errors[0]->message;
			} else {
// 3. Add topics to the DB
				set_time_limit(600);
				\SL\Utilities\Log::logIt( $file,  "Trends, received, updating DB\n");						
				foreach ($trends[0]->trends as $trend) {
					if ( $items->createTopic( $trend, $run_db_id ) ) {
						\SL\Utilities\Log::logIt( $file,  "- Added topic \"" . $trend->name . "\" (\"" . $trend->query . "\")\n");
					} else {
						\SL\Utilities\Log::logIt( $file,  "\n*** Error creating topic " . $trend->name . " (" . $trend->query . ")\n\n");
					}
				}
// 4. Go to next step
				\SL\Utilities\Log::logIt( $file,  "Querying the DB (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n" );
// 5. Get the topics
				if ( $topics = $items->getTopics( $run_id, 0, 100 ) ) {
// 6. For each one, get the tweets
					foreach ($topics as $topic) {
						\SL\Utilities\Log::logIt( $file,  "- Getting tweets for \"" . $topic['keyword'] . "\"/\"" . $topic['query'] . "\" (" . date('r') . " - elapsed " . (time() - $start) . ")\n" );
						set_time_limit(600);

							try {
								$tweets = $connection->get("search/tweets", ["q" => $topic['query'], "include_entities" => true, "count" => TW_MAX_TWEETS, "lang" => TW_LANG ]);
							} catch (Exception $e) {
								\SL\Utilities\Log::logIt($file, "\n*** Failed Twitter API tweet call " . $e->getMessage() . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n");
							}
							if (isset($tweets->errors) && is_array($tweets->errors)) {
								\SL\Utilities\Log::logIt($file, "*** " . $tweets->errors[0]->message . "\n\n");
								echo $tweets->errors[0]->message;
							} else {
								if (isset($tweets->statuses)) {
									foreach ($tweets->statuses as $tweet) {
										// 7. For each tweet, check if it contains at least 1 URL
										set_time_limit(600);
										\SL\Utilities\Log::logIt($file, "-- Checking \"" . $tweet->text . "\" (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
										$urls = array();
										if (isset($tweet->entities->urls) && is_array($tweet->entities->urls)) {
											\SL\Utilities\Log::logIt($file, "---- Valid, contains entity URLs (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
											foreach ($tweet->entities->urls as $url) {
												array_push($urls, $url->expanded_url);
											}
										}
										if ($links = \SL\Utilities\Linkify::returnLinks($tweet->text)) {
											\SL\Utilities\Log::logIt($file, "---- Valid, contains at least 1 link (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
											$urls = array_merge($urls, $links);
										}
										$added = date( 'Y-m-d H:i:s', strtotime( $tweet->created_at ) );
										// 8. Save this tweet in the temporary table

										if (count($urls) > 0) {
											foreach ($urls as $url) {
												if ($items->createLink($url, $added, $topic['id'])) {
													\SL\Utilities\Log::logIt($file, "------ Saved " . $url . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
												} else {
													\SL\Utilities\Log::logIt($file, "*** ------ Failed to save " . $url . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
												}
											}
										} else {
											\SL\Utilities\Log::logIt($file, "---- No links, discarded (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
										}
										set_time_limit(600);
										echo "\nSleeping " . $delay . " seconds (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n";
										sleep($delay);
									}
								}
							}
							set_time_limit(600);
							echo "\nSleeping " . $delay . " seconds (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n";
							sleep($delay);
					}
					set_time_limit(600);
					echo "\nSleeping " . $delay . " seconds (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n";
					sleep($delay);
					if ( !$items->enableScrapingOnRun( $run_id ) ) {
						\SL\Utilities\Log::logIt( $file,  "\n*** Unable to set run " . $run_id . " ready for scraping\n\n");
					}
				} else {
					\SL\Utilities\Log::logIt( $file,  "\n*** Unable to get Topics for run " . $run_id . "\n\n");
				}
			}
		} else {
			\SL\Utilities\Log::logIt( $file,  "\n*** Unable to create new RUN instance\n\n");
		}
	}
	
	\SL\Utilities\Log::logIt( $file,  "Optimizing and compressing DB tables (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
	set_time_limit(600);
	$items->optimize();
	\SL\Utilities\Log::logIt( $file,  "---- Done (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
		
	\SL\Utilities\Log::logIt( $file,  "\n\n---------------\n\033[33m" . $run_id . " - Ended on: " . date('r') . " - elapsed " . (time() - $start) . "\n\033[34m---------------\n\n");
	unset( $items );
	die(0);
?>
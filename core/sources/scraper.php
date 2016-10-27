<?php
	error_reporting(E_ALL);
	
	if ( !M_CLI ) { // Prevent executing from web
		SL\Utilities\Http::redirect( M_BASE_URL );
	}
	
	$start = time();
	$frequency = 30;
	$delay = .5;
	$file =  M_CACHE . "/_scrape_log.txt";
	$items = new \Trending\Items( $DBH );
	
	\SL\Utilities\Log::clean( $file );
	\SL\Utilities\Log::logIt( $file, "\n\n============================================================\n\n" );
	\SL\Utilities\Log::logIt( $file, "\n\n---------------\nStarting at: " . date('r') . "\n----\n" );
	
	if ( $run = $items->getLatestRun() ) {
		
		\SL\Utilities\Log::logIt( $file,  "\n\nFound run with code " . $run['run'] . " and id " . $run['id'] . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n\033[34m---------------\n\n");
	
// STEP 2 HERE!
// 10. Get all the links
		\SL\Utilities\Log::logIt( $file,  "Querying links (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
		$links = $items->getAndRemoveLinkFromRunID( $run['run'] );
		if ( is_array($links) ) {
// 11. Check each one of them
			if ( count( $links ) > 0 ) {
				foreach( $links as $link ) {
					$real_link = null;
					\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " -- Checking " . $link['link_id'] . " - " . $link['link'] . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
					\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ---- Starting scraping " .  $link['link_id'] . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
					$title = "";
					$description = "";
					$image = "";
					try {
						set_time_limit(600);
						$scraper_client = new \Goutte\Client();
						$scraper_crawler = $scraper_client->request('GET', $link['link']);
						$response = $scraper_client->getResponse();
						\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ------ Getting real URL (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
						$real_link = $scraper_client->getHistory()->current()->getUri();
						\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ------ Got " . $real_link . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
						
						if ( \SL\Utilities\Blacklist::validLink( $real_link ) && ( $response->getStatus() == 200 ) ) {						
							// Title
							if ($scraper_crawler->filterXPath('//meta[@property="og:title"]')->count() == 1) {
								$title = $scraper_crawler->filterXPath('//meta[@property="og:title"]')->attr("content");
							} else if ($scraper_crawler->filter('head > title')->count() > 0) {
								$scraper_crawler->filter('head > title')->each(function ($node) {
									global $title;
									$title = $node->text();
								});
							}
							// Description
							if ($scraper_crawler->filterXPath('//meta[@property="og:description"]')->count() == 1) {
								$description = $scraper_crawler->filterXPath('//meta[@property="og:description"]')->attr("content");
							} else if ($scraper_crawler->filterXPath('//meta[@name="description"]')->count() == 1) {
								$description = $scraper_crawler->filterXPath('//meta[@name="description"]')->attr("content");
							}
							// Image
							if ($scraper_crawler->filterXPath('//meta[@property="og:image"]')->count() == 1) {
								$image = \SL\Utilities\Scraper::getImageURI( $real_link, $scraper_crawler->filterXPath('//meta[@property="og:image"]')->attr("content"));
							}
					
							\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ------ Got this info:\n");
							\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ---------- @Title: " . $title . "\n");
							\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ---------- @Description: " . $description . "\n");
							\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ---------- @Image: " . $image . "\n");
							\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ------ (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
						} else {
							\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ------ Useless link, skipping (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
						}
						unset( $scraper_client );
					} catch(Exception $e) {
						set_time_limit(600);
						\SL\Utilities\Log::logIt( $file,  "\n*** Scraper error, skipping " . $e . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n");
						$title = "";
					}
					
					if ( \SL\Utilities\Blacklist::validItem( $title, $description) ) {
// 12. Save to the DB
						echo ( $link['added'] );
						\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ------ Saving new article to DB (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
						if ( $article_id = $items->createArticle( $title, $link['added'], $description, $image, $real_link, $link['topic_id']  ) ) {
							\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ------ Saved with id " . $article_id . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
						}
					} else {
						\SL\Utilities\Log::logIt( $file,  $link['link_id'] . " ------ Invalid title, skipping this link (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
					}
					set_time_limit(600);
					echo "\nSleeping " . $delay . " seconds (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n";
					sleep($delay);
				}
// 13. Compute topic's popularity
				\SL\Utilities\Log::logIt( $file,  "Setting topics popularity for " . $run['run'] . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
				set_time_limit(600);
				if ( $items->setTopicsPopularity( $run['run'] ) ) {
					\SL\Utilities\Log::logIt( $file,  "-- Done (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
				} else {
					\SL\Utilities\Log::logIt($file, "\n*** Something Failed!\n\n");
				}
			} else {
				\SL\Utilities\Log::logIt( $file,  "\n\No more links, let's activate current run (" . date('r') . " - elapsed " . (time() - $start) . ")\n\033[34m---------------\n\n");
// 14. Compute topic's popularity
				\SL\Utilities\Log::logIt( $file,  "Setting topics popularity for " . $run['run'] . " (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
				set_time_limit(600);
				if ( $items->setTopicsPopularity( $run['run'] ) ) {
					\SL\Utilities\Log::logIt( $file,  "-- Done (" . date('r') . " - elapsed " . (time() - $start) . ")\n");	
				} else {
					\SL\Utilities\Log::logIt( $file,  "\n*** Something Failed!\n\n");
				}
// 15. Run finished,mark it as complete
				\SL\Utilities\Log::logIt( $file,  "Set " . $run['run'] . " as active (" . date('r') . " - elapsed " . (time() - $start) . ")\n");
				set_time_limit(600);
				if ( $items->activateRun( $run['run'] ) ) {
					\SL\Utilities\Log::logIt( $file,  "\n\nUpdating topics' popularity (" . date('r') . " - elapsed " . (time() - $start) . ")\n\033[34m---------------\n\n");
					$items->setTopicsPopularity( $run['run'] );
					\SL\Utilities\Log::logIt( $file,  "-- Done\n");
				} else {
					\SL\Utilities\Log::logIt( $file,  "\n*** Failed! Unable to activate " . $run['run'] . "\n\n");
				}
			}
		} else {
			\SL\Utilities\Log::logIt( $file,  "\n*** Unable to get links (" . date('r') . " - elapsed " . (time() - $start) . ")\n\n");
		}

	} else {
		\SL\Utilities\Log::logIt( $file,  "\n\nNothing to do (" . date('r') . " - elapsed " . (time() - $start) . ")\n\033[34m---------------\n\n");
	}
	\SL\Utilities\Log::logIt( $file,  "\n\n---------------\n\033[33mEnded on: " . date('r') . " - elapsed " . (time() - $start) . "\n\033[34m---------------\n\n");
	unset( $items );
	die(0);
?>
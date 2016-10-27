<?php
/**
 * Items.class.php
 * A collection of method to handle topics, links and articles, their relations, and any activity to be performed on them
 * This class repreents the direct link between the application and the mySql database.
 *
 * DB Entities:
 * 
 * - run A "run" is a collection of topics. A new run is created every time the twitter bot searches for new trending topics
 * - tun_to_topics The tables that creates relationships between "runs" and the trending topics. Each topic can be related to more than one "run"
 * - links The temporary table that stores links before the scraper starts to check their validity.
 * - links_to_topics Each link can be related to more than one topic
 * - articles Valid links get scraped, and info about them (title, description, image)  are saved in this table
 * - topics_to_articles Each article can be related to more than one topic
 *
 * run.status meaning:
 * 
 * - status = 1 This "run" is active and valid, can be shown
 * - status = 0 This "run" has ben created, but links have not been scraped yet
 * - status = -1 This "run" has been created, but related topics and links might not be ready for further operations yet
 *
 * topic.volume contains the number of articles related to that topic.
 *
 * articles.image contains a reference to the article's image. It is not used in the default theme, but it is scraped and populated anyway when that data is available.
 *
 * @author Simone Lippolis <simone@simonelippolis.com>
 * @version 1.0
 * @package Trending
 */
	namespace Trending;
	
/**
 * @method mixed createRun( string $run_id ] )
 * @method bool enableScrapingOnRun( string $run_id )
 * @method bool activateRun( string $run_id )
 * @method mixed getRunDBID( string $run_id )
 * @method mixed getLatestValidRun()
 * @method mixed getLatestRun()
 * @method mixed createTopic( object $trend, string $run_id  )
 * @method mixed getTopics( string $run_id [ , int $offset = 0, int $page_size = 15 ] )
 * @method mixed getTopicsFromRunID( string $run_id [ , int $offset = 0, int $page_size = 15, string $where = '' ] )
 * @method mixed getTopicsForPages( string $run_id [ , int $offset = 0, int $page_size = 15, string $where = '' ] )
 * @method mixed getPopularTopics()
 * @method mixed getTopicByQuery( string $query )
 * @method bool setTopicsPopularity( string $run_id )
 * @method bool topicExists( string $topic )
 * @method bool createLink( string $link, string $added, int $topic_id )
 * @method bool removeLink( int $link_id )
 * @method mixed getLinksFromTopicID( int $topic_id )
 * @method mixed getLinksFromRunID( string $run_id )
 * @method mixed getAndRemoveLinkFromRunID( string $run_id )
 * @method mixed createArticle( string $title, string $date_published, string $description, string $image, string $url, int $topic_id )
 * @method mixed getArticlesByTopicID( int $topic_id [ , int $offset = 0, int $page_size = 75, string $order = "A.id DESC", string $where = "" ] )
 * @method mixed getArticlesForPages( nt $topic_id [ , int $offset = 0, int $page_size = 75, string $order = "A.id DESC" ] )
 * @method void optimize()
 */	
	
	class Items {
		private $DBH;
		
		function __construct( $DBH ) {
			$this->DBH = $DBH;
		}
		
		public function createRun( $run_id ) {
			$output = false;
			if ( !$this->getRunDBID( $run_id ) ) {
				$query_params = array(
					':run_id' => $run_id,
					':status' => -1,
					':cover' => null
				);
				$sql = "INSERT INTO runs (run, status, cover) VALUES ( :run_id, :status, :cover )";
				if ( $STH = $this->DBH->prepare( $sql ) ) {
					if ( $STH->execute( $query_params ) ) {
						$output = $this->DBH->lastInsertId();
					}
				}
			}
			return $output;
		}

		public function enableScrapingOnRun( $run_id ) {
			$output = false;
			$query_params = array(
				':run_id' => $this->getRunDBID( $run_id )
			);
			$sql = "UPDATE runs SET status = 0 WHERE id = :run_id";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$output = true;
				}
			}
			return $output;
		}
		
		public function activateRun( $run_id ) {
			$output = false;
			$query_params = array(
				':run_id' => $this->getRunDBID( $run_id )
			);
			$sql = "UPDATE runs SET status = 1 WHERE id = :run_id";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$output = true;
				}
			}
			return $output;
		}
		
		public function getRunDBID( $run_id ) {
			$output = false;
			$query_params = array(
				':run' => $run_id
			);
			$sql = "SELECT id FROM runs WHERE run = :run LIMIT 0,1";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$result = $STH->fetch( \PDO::FETCH_ASSOC );
					$output = $result['id'];
				}
			}
			return $output;
		}
		
		public function getLatestValidRun() {
			$output = false;
			$sql = "SELECT id, run FROM runs WHERE status = 1 ORDER BY run DESC LIMIT 0,1";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute() ) {
					$result = $STH->fetch( \PDO::FETCH_ASSOC );
					$output = $result;
				}
			}
			return $output;
		}
		
		public function getLatestRun() {
			$output = false;
			$sql = "SELECT id, run FROM runs WHERE status = 0 ORDER BY run DESC LIMIT 0,1";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute() ) {
					$result = $STH->fetch( \PDO::FETCH_ASSOC );
					$output = $result;
				}
			}
			return $output;
		}
		
		public function createTopic( $trend, $run_id ) {
			$output = false;
			$query_params = array(
				':topic' => $trend->name,
				':query' => $trend->query,
				':volume' => 0
			);
			$sql = "INSERT INTO topics (keyword, query, volume) VALUES (:topic, :query, :volume) ON DUPLICATE KEY UPDATE volume = :volume";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					if ( !$output = $this->DBH->lastInsertId() ) {
						$sql = "SELECT id FROM topics WHERE keyword = :topic AND query = :query AND volume = :volume LIMIT 0,1";
						if ( $STH = $this->DBH->prepare( $sql ) ) {
							if ( $STH->execute( $query_params ) ) {
								$result = $STH->fetch( \PDO::FETCH_ASSOC );
								$output = $result['id'];
							}
						}
					}
				}
			}
			if ($output) {
				$query_params = array(
					':topic_id' => $output,
					':run_id' => $run_id
				);
				$sql = "INSERT IGNORE INTO runs_to_topics (run_id, topic_id) VALUES (:run_id, :topic_id)";
				if ( $STH = $this->DBH->prepare( $sql ) ) {
					if ( !$STH->execute( $query_params ) ) {
						$output = false;
					}
				}
			}
			return $output;
		}
		
		public function getTopics( $run_id, $offset = 0, $page_size = 15) {
			$output = false;
			$run_db_id = $this->getRunDBID( $run_id );
			return $this->getTopicsFromRunID( $run_db_id, $offset, $page_size );
		}
		
		public function getTopicsFromRunID( $run_id, $offset = 0, $page_size = 15, $where = '' ) {
			$output = false;
			if ($where != '') {
				$where = 'AND ' . $where;
			}
			$query_params = array(
				':run_id' => $run_id
			);
			$sql = "SELECT T.id, T.keyword, T.query, T.volume FROM topics AS T INNER JOIN runs_to_topics AS RTT ON RTT.topic_id = T.id WHERE RTT.run_id = :run_id " . $where . " ORDER BY T.volume DESC LIMIT " . $offset . "," . $page_size;
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$output = $STH->fetchAll( \PDO::FETCH_ASSOC );
				}
			}
			return $output;
		}
		
		public function getTopicsForPages( $run_id, $offset = 0, $page_size = 15, $where = '' ) {
			return  $this->getTopicsFromRunID( $run_id, $offset, $page_size, 'T.volume > 1' );
		}

		public function getPopularTopics() {
			$output = false;

			$sql = "SELECT keyword, query, volume FROM topics ORDER BY volume DESC LIMIT 0,20";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute() ) {
					$output = $STH->fetchAll( \PDO::FETCH_ASSOC );
				}
			}
			return $output;
		}
		
		public function getTopicByQuery( $query ) {
			$output = false;
			$query_params = array(
				':query' => $query
			);
			$sql = "SELECT * FROM topics WHERE query = :query LIMIT 0,1";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$output = $STH->fetch( \PDO::FETCH_ASSOC );
				}
			}
			return $output;
		}
		
		public function setTopicsPopularity( $run_id ) {
			$output = false;
			$run_db_id = $this->getRunDBID( $run_id );
			if ( $topics = $this->getTopicsFromRunID( $run_db_id, 0, 1500 ) ) {
				foreach ( $topics as $topic ) {
					$query_params = array(
						':topic_id' => $topic['id']
					);
					$sql = "UPDATE topics SET volume = ( SELECT COUNT(topic_id) FROM topics_to_articles WHERE topic_id = :topic_id ) WHERE id = :topic_id";
					if ( $STH = $this->DBH->prepare( $sql ) ) {
						if ( $STH->execute( $query_params ) ) {
							$output = true;
						}
					}
				}
			}
			return $output;
		}
		
		public function topicExists( $topic ) {
			$output = false;
			$query_params = array(
				':topic' => $topic
			);
			$sql = "SELECT id FROM topics WHERE keyword = :topic LIMIT 0,1";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$result = $STH->fetch( \PDO::FETCH_ASSOC );
					$output = $result['id'];
				}
			}
			return $output;
		}
		
		public function createLink( $link, $added, $topic_id ) {
			$output = false;
			$query_params = array(
				':topic_id' => $topic_id,
				':added' => $added,
				':link' => $link
			);
			$sql = "INSERT IGNORE INTO links (link, added, topic_id) VALUES (:link, :added, :topic_id)";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$output = true;
				}
			}
			return $output;
		}
		
		public function removeLink( $link_id ) {
			$output = false;
			$query_params = array(
				':link_id' => $link_id
			);
			$sql = "DELETE FROM links WHERE id = :link_id";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$output = true;
				}
			}
			return $output;
		}
		
		public function getLinksFromTopicID( $topic_id ) {
			$output = false;
			$query_params = array(
				':topic_id' => $topic_id
			);
			$sql = "SELECT id, link FROM links WHERE topic_id = :topic_id";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$output = $STH->fetchAll( \PDO::FETCH_ASSOC );
				}
			}
			return $output;
		}
		
		public function getLinksFromRunID( $run_id ) {
			$output = false;
			$query_params = array(
				':run_id' => $run_id
			);
			$sql = "SELECT L.id AS link_id, L.link, T.id AS topic_id FROM links AS L INNER JOIN topics AS T ON L.topic_id = T.id INNER JOIN runs_to_topics AS RTP ON T.id = RTP.topic_id INNER JOIN runs AS R ON RTP.run_id = R.id WHERE R.run = :run_id ";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$output = $STH->fetchAll( \PDO::FETCH_ASSOC );
				}
			}
			return $output;
		}
		
		public function getAndRemoveLinkFromRunID( $run_id ) {
			$output = false;
			$query_params = array(
				':run_id' => $run_id
			);
			$sql = "SELECT L.id AS link_id, L.link, L.added , T.id AS topic_id FROM links AS L INNER JOIN topics AS T ON L.topic_id = T.id INNER JOIN runs_to_topics AS RTP ON T.id = RTP.topic_id INNER JOIN runs AS R ON RTP.run_id = R.id WHERE R.run = :run_id LIMIT 0,100";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$output = $STH->fetchAll( \PDO::FETCH_ASSOC );
				}
			}
			foreach( $output as $link ) {
				$this->removeLink( $link['link_id'] );
			}
			return $output;
		}
		
		public function createArticle( $title, $date_published, $description, $image, $url, $topic_id ) {
			$output = false;
			$query_params = array(
				':url' => $url,
				':image' => $image,
				':description' => $description,
				':source' => parse_url( $url, PHP_URL_HOST ),
				':date_published' => $date_published,
				':title' => $title
			);
			$sql = "INSERT IGNORE INTO articles (title, date_published, source, description, image, url) VALUES (:title, :date_published, :source, :description, :image, :url)";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					if ( !$output = $this->DBH->lastInsertId() ) {
						$sql = "SELECT id FROM articles WHERE title = :title AND date_published = :date_published AND source = :source AND description = :description AND image = :image AND url = :url LIMIT 0,1";
						if ( $STH = $this->DBH->prepare( $sql ) ) {
							if ( $STH->execute( $query_params ) ) {
								$result = $STH->fetch( \PDO::FETCH_ASSOC );
								$output = $result['id'];
							}
						}
					}
				}
			}
			if ( $output ) {
				$query_params = array(
					':topic_id' => $topic_id,
					':article_id' => $output
				);
				$sql = "INSERT IGNORE INTO topics_to_articles (topic_id, article_id) VALUES (:topic_id, :article_id)";
				if ( $STH = $this->DBH->prepare( $sql ) ) {
					if ( !$STH->execute( $query_params ) ) {
						$output = false;
					}
				}
			}
			return $output;
		}
		
		public function getArticlesByTopicID( $topic_id, $offset = 0, $page_size = 75, $order = "A.id DESC", $where = "" ) {
			$output = false;
			if ( $order != "" ) {
				$order = "ORDER BY " . $order;
			}
			$query_params = array(
				':topic_id' => $topic_id
			);
			$sql = "SELECT A.*, DATE_FORMAT(A.date_published, '%a %b %D, %H:%i') AS short_published, TTA.topic_id AS topic_id FROM articles AS A INNER JOIN topics_to_articles AS TTA ON A.id = TTA.article_id WHERE topic_id = :topic_id " . $where . " " . $order . " LIMIT " . $offset . "," . $page_size;
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				if ( $STH->execute( $query_params ) ) {
					$output = $STH->fetchAll( \PDO::FETCH_ASSOC );
				}
			}
			return $output;
		}
		
		public function getArticlesForPages( $topic_id, $offset = 0, $page_size = 75, $order = " A.id DESC" ) {
			//$output = new \stdClass();
			//$output->mosaic = $this->getArticlesByTopicID( $topic_id, $offset, $page_size, $order, " AND image <> '' " );
			//$output->list = $this->getArticlesByTopicID( $topic_id, $offset, $page_size, $order, " AND image = '' OR image IS NULL " );
			return $this->getArticlesByTopicID( $topic_id, $offset, $page_size, $order );
		}
		
		public function optimize() {
			$sql = "OPTIMIZE TABLE articles; OPTIMIZE TABLE links; OPTIMIZE TABLE runs; OPTIMIZE TABLE runs_to_topics; OPTIMIZE TABLE topics; OPTIMIZE TABLE topics_to_articles;";
			if ( $STH = $this->DBH->prepare( $sql ) ) {
				$STH->execute( );
			}
		}
	}
?>
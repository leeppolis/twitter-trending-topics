<?php
/**
 * Renderer.class.php
 * Class used to send the output to the browser, either in HTML or JSON format.
 * Supports both Twig and Smarty templating engines.
 *
 * @author Simone Lippolis <simone@simonelippolis.com>
 * @version 1.0
 * @package SL
 * @subpackage Utilities
 */
	namespace SL\Utilities;
/**
 * @method void render( string $template, object $output, string $format )
 * @method string fetch( string $template, object $output, string $format )
 * @method bool isJSONRequestValid( )
 */		
	Class Renderer {
		private $obj;
/**
 * The constructor initializs the internal variable containing the template engine class.
 *
 * @param objet #obj
 * @return void
 *
 */		
		function __construct( $obj ) {
			$this->obj = $obj;
		}
/**
 * Outputs the result of the compilation of a template
 *
 * @param string $template The filename of the template file, located in M_THEME_DIR
 * @param object $output The object that is passed to the template engine, containing all the variables useful for rendering
 * @param string $format The format of the output. It can be either 'html' or 'json', defaults to 'html'. If M_ALLOW_JSON is seto to false, requesting a JSON format will return back an error message
 * @return void
 *
 */
		public function render( $template, $output, $format = 'html' ) {
			echo $this->fetch( $template, $output, $format );
		}

/**
 * Returns the result of the compilation of a template
 *
 * @param string $template The filename of the template file, located in M_THEME_DIR
 * @param object $output The object that is passed to the template engine, containing all the variables useful for rendering
 * @param string $format The format of the output. It can be either 'html' or 'json', defaults to 'html'. If M_ALLOW_JSON is seto to false, requesting a JSON format will return back an error message
 * @return string
 *
 */		
		public function fetch( $template, $output, $format = 'html' ) {
			if ( strtolower( $format ) == 'json' ) {				
				if ( $this->isJSONRequestValid( $output ) ) {
					unset( $output->head );
					$output->message = ( $template == '404.html' ) ? 'File not found' : 'OK';
				} else {
					unset( $output );
					$output = new \stdClass();
					$output->message = 'Wrong request';
				}
				return json_encode( $output );				
			} else {
				if ( file_exists( M_THEME_DIR . "/" . $template ) ) {
					
					if ( class_exists( "Twig_Autoloader") ) {
						return $this->obj->render( $template, array( "output" => $output ) );
					} else if ( class_exists( "Smarty" ) ) {
						$this->obj->assign( 'output', $output );
						return $this->obj->fetch( $template );
					}
					
				} else {
					trigger_error ( "Template not found (\"". M_THEME_DIR . "/" . $template . "\")" );
				}
			}
		}

/**
 * Checks if the current installation is configured to allow JSON responses to the client
 *
 * @return bool
 *
 */
		private function isJSONRequestValid() {
			return M_ALLOW_JSON;
		}
	}
?>
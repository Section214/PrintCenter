<?php
/**
 * Helper functions
 *
 * @package     PrintCenter\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Fetch an array of available shirt types
 *
 * @since       1.0.0
 * @return      array $shirts The available shirt types
 */
function printcenter_get_shirts() {
	$shirts = array();

	$posts = get_posts( array(
		'posts_per_page' => 999999,
		'post_type'      => 'ssi_product',
		'post_status'    => 'publish'
	) );

	if( count( $posts ) > 0 ) {
		foreach( $posts as $post ) {
			$sku          = get_post_meta( $post->ID, '_ssi_sku', true );
			$shirts[$sku] = $post->post_title;
		}
	}

	return $shirts;
}


/**
 * Prettifies an XML string
 *
 * @since       1.0.0
 * @param       string $xml The XML as a string
 * @param       boolean $html_output True if the output should be escaped (for use in HTML)
 * @return      string The prettified XML
 */
function printcenter_prettify_xml( $xml, $html_output = false ) {
	$xml_obj = new SimpleXMLElement($xml);
	$level   = 4;
	$indent  = 0; // current indentation level
	$pretty  = array();

	// Get an array containing each XML element
	$xml = explode( "\n", preg_replace( '/>\s*</', ">\n<", $xml_obj->asXML() ) );

	// Shift off opening XML tag if present
	if( count( $xml ) && preg_match( '/^<\?\s*xml/', $xml[0] ) ) {
		$pretty[] = array_shift($xml);
	}

	foreach( $xml as $el ) {
		if( preg_match( '/^<([\w])+[^>\/]*>$/U', $el ) ) {
			// opening tag, increase indent
			$pretty[] = str_repeat( ' ', $indent ) . $el;
			$indent += $level;
		} else {
			if( preg_match( '/^<\/.+>$/', $el ) ) {
				$indent -= $level;  // closing tag, decrease indent
			}

			if( $indent < 0 ) {
				$indent += $level;
			}

			$pretty[] = str_repeat( ' ', $indent ) . $el;
		}
	}

	$xml = implode( "\n", $pretty );

	return ( $html_output ) ? htmlentities( $xml ) : $xml;
}
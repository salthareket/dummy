<?php
/**
 * The template for displaying Author Archive pages
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

namespace App;

use Timber\Timber;

$context = Timber::context();
if ( isset( $context['author'] ) ) {
	$context['title'] = sprintf( __( 'Archive of %s', 'timber-starter' ), $context['author']->name() );
}
Timber::render( array( 'author.twig', 'archive.twig' ), $context );
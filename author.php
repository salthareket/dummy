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
global $wp_query;

$context = Timber::context();

if ( isset( $wp_query->query_vars['author'] ) ) {
	$author = Timber::get_user( $wp_query->query_vars['author']);

	$cell_terms = Timber::get_terms("hucre-tipi");
    $cell_types = sort_terms_hierarchicaly($cell_terms);
    $context['cell_types'] = $cell_types;

    global $wpdb;
	$query = $wpdb->prepare(
	    "SELECT tt.term_id
	    FROM {$wpdb->term_relationships} AS tr
	    INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
	    INNER JOIN {$wpdb->posts} AS p ON tr.object_id = p.ID
	    WHERE p.post_type = %s
	    AND p.post_author = %d",
	    "hucreler",
	    $author->ID
	);
    $cell_type_selected = array_unique($wpdb->get_col($query));

    $author->cell_type_count = count($cell_type_selected);

    $context['title'] = 'Author Archives: ' . $author->name();
    $context['cell_type_selected'] = $cell_type_selected;
    $context['author'] = $author;
}
Timber::render( array( 'author.twig', 'archive.twig' ), $context );
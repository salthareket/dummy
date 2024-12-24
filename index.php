<?php
/**
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

//global $wp_query;

$context          = Timber::context();
//$context["query_vars"] = ($wp_query->query_vars);
$context['posts']= Timber::get_posts();
$templates        = array( 'post/archive.twig' );
$context['categories'] = Timber::get_terms([
    'taxonomy' =>  'category',
    'hide_empty' => false,
]);
$context["tags"] = wp_tag_cloud(array(
   'number' => 20,
   'post_type' => 'post',
   'echo' => false
));

Timber::render( $templates, $context );
<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * To generate specific templates for your pages you can use:
 * /mytheme/views/page-mypage.twig
 * (which will still route through this PHP file)
 * OR
 * /mytheme/page-mypage.php
 * (in which case you'll want to duplicate this file and save to the above path)
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

// Context: _salt_block_context zaten add_to_context'te hazırlandı, tekrar Timber::context() çağırmaya gerek yok
if ( ! empty( $GLOBALS['_salt_block_context'] ) ) {
    $context = $GLOBALS['_salt_block_context'];
} else {
    $context = Timber::context();
}

\SaltProfiler::mark('page.php: context ready');

// post zaten add_to_context içinde Timber::get_post() ile set edildi
// Burada tekrar çağırmaya gerek yok

\SaltProfiler::mark('page.php: before render');


/*global $wp_query;
if($wp_query->posts){
	$context['posts'] = Timber::get_posts();
}*/

if($post->post_parent > 0){
	$parent_top = get_parent_top($post);
	$template = get_page_template_slug( $parent_top );
	if($template == "template-page-merged.php"){
		$redirect_url = make_hash_url($post->link, true);
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: ".$redirect_url);
	}	
}
Timber::render( array( 'page-' . $post->post_name . '.twig', 'page.twig' ), $context );
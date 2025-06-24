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
/*
Template Name: My Account
*/
//if (!is_user_logged_in() && WC()->query->get_current_endpoint() != "" ) {

/*if (!is_user_logged_in() && get_current_endpoint("my-account") != "" && get_current_endpoint("my-account") != "my-account") {
    wp_safe_redirect(get_page_url('musteriler'));
    die;
}*/

$context = Timber::context();
$post = Timber::get_post();
$context['post'] = $post;
$context['type'] = get_current_endpoint() ?? getUrlEndpoint();
Timber::render( array( 'my-account/index.twig' ), $context );
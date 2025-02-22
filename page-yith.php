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

$context = Timber::context();
$post = new Timber\Post();
$context['post'] = $post;

$args = array(
   "post_type" => "ywdpd_discount"
);
$posts = Timber::get_posts($args);

$discounts = new YITH_WC_Dynamic_Discounts();
$discount_list = $discounts->get_discount_rules();

Timber::render( array( 'page-' . $post->post_name . '.twig', 'page.twig' ), $context );
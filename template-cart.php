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
Template Name: Cart
*/

$context = function_exists('salt_get_context') ? salt_get_context() : Timber::context();
$post = Timber::get_post();
$context['post'] = $post;

// ── Empty cart: "New in store" tease listesi ─────────────────────────────────
$pl_cfg  = (array) get_option( 'options_product_lists', [] );
$nis_cfg = (array) ( $pl_cfg['new_in_store'] ?? [] );
$nis_count = max( 1, (int) ( $nis_cfg['count'] ?? 8 ) );

$nis_query = new WP_Query( [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => $nis_count,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'tax_query'      => [ [
        'taxonomy' => 'product_visibility',
        'field'    => 'name',
        'terms'    => 'exclude-from-catalog',
        'operator' => 'NOT IN',
    ] ],
] );

// enabled yoksa zorla aç (empty cart'ta her zaman göster)
if ( empty( $nis_cfg['enabled'] ) ) {
    $nis_cfg['enabled']     = true;
    $pl_cfg['new_in_store'] = $nis_cfg;
}

$context['pl_cfg']                = $pl_cfg;
$context['new_in_store_products'] = $nis_query->have_posts()
    ? Timber::get_posts( $nis_query )
    : [];

$template = ["woo/pages/page-cart-custom.twig", "woo/pages/page-cart.twig", "page.twig"];
Timber::render( $template, $context );
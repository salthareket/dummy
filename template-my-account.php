<?php
/**
 * My Account Page Template
 *
 * WC aktif veya değil — her durumda index.twig render eder.
 * WC aktifken post.content içindeki [woocommerce_my_account] shortcode'u
 * çift render yapar — bunu engellemek için shortcode devre dışı bırakılır,
 * endpoint content doğrudan index.twig içinden do_action ile çağrılır.
 *
 * Template Name: My Account
 *
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// ── WC aktifken [woocommerce_my_account] shortcode'u devre dışı bırak.
// index.twig endpoint content'i woocommerce_account_content action ile render eder.
// Böylece çift form / çift sidebar sorunu ortadan kalkar.
if ( defined( 'ENABLE_ECOMMERCE' ) && ENABLE_ECOMMERCE ) {
    remove_shortcode( 'woocommerce_my_account' );
    // WC navigation'ı da kapat — index.twig kendi sidebar'ını render ediyor.
    add_filter( 'woocommerce_account_navigation', '__return_false' );
}

$context         = function_exists( 'salt_get_context' ) ? salt_get_context() : Timber\Timber::context();
$post            = Timber\Timber::get_post();
$context['post'] = $post;
$context['type'] = function_exists( 'get_current_endpoint' ) ? get_current_endpoint() : ( function_exists( 'getUrlEndpoint' ) ? getUrlEndpoint() : '' );

Timber\Timber::render( [ 'my-account/index.twig' ], $context );
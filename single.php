<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

namespace App;

use Timber\Timber;
use SaltHareket\Reviews\Reviews;

$context = function_exists('salt_get_context') ? salt_get_context() : Timber::context();

if ( post_password_required( $post->ID ) ) {
	Timber::render( 'single-password.twig', $context );
} else {

    // Reviews context
    if ( class_exists( 'Reviews' ) ) {
        $reviews_obj = new Reviews();
        $post_id     = $context['post']->ID ?? get_the_ID();
        $post_type   = get_post_type( $post_id ) ?: 'post';

        // Type config — bu post_type için review yapılabilir mi?
        $type_config  = \SaltHareket\Reviews\ReviewsSettings::getTypeConfig( 'post', $post_type );
        $can_review_type = \SaltHareket\Reviews\ReviewsSettings::canReviewType( 'post', $post_type );

        $context['review_type_config'] = $type_config;
        $context['can_review_type']    = $can_review_type;
        $context['review_modes']       = $type_config['modes'] ?? [ 'rate', 'comment' ];

        $reviews_result          = $reviews_obj->getForPost( $post_id, [ 'per_page' => 10 ] );
        $context['reviews']      = $reviews_result['reviews'];
        $context['reviews_data'] = $reviews_result['data'];
        $context['reviews_stats']= Reviews::stats( $post_id, 'post' );
        $context['has_reviewed'] = is_user_logged_in() && \SaltHareket\Reviews\ReviewsSettings::get( 'general.one_review_per_user' )
            ? $reviews_obj->has_reviewed( get_current_user_id(), $post_id, 'post' )
            : false;
        $context['can_review']   = $can_review_type && is_user_logged_in()
            ? $reviews_obj->can_review( get_current_user_id(), $post_id, 'post' )
            : false;
        $context['reviews_settings']  = \SaltHareket\Reviews\ReviewsSettings::all();
        $context['reviews_criteria']  = \SaltHareket\Reviews\ReviewsSettings::getCriteria( $post_type );
        $context['reviews_post_type'] = $post_type;
    }

	Timber::render( array( $post->post_type . '/single.twig', 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}
<?php
/**
 * The template for displaying Author Archive pages (Public Profile)
 */

namespace App;

use Timber\Timber;

$context = function_exists('salt_get_context') ? salt_get_context() : Timber::context();

if ( isset( $context['author'] ) ) {
    $context['title'] = $context['author']->name();

    // Reviews config — user + role bazlı
    if ( class_exists( '\SaltHareket\Reviews\ReviewsSettings' ) ) {
        $author       = $context['author'];
        $author_id    = is_object( $author ) ? $author->ID : 0;
        $author_user  = $author_id ? get_userdata( $author_id ) : null;
        $role         = $author_user && ! empty( $author_user->roles ) ? reset( $author_user->roles ) : '';

        $type_config  = \SaltHareket\Reviews\ReviewsSettings::getTypeConfig( 'user', $role );
        $context['review_type_config'] = $type_config;
        $context['can_review_user']    = \SaltHareket\Reviews\ReviewsSettings::canReviewType( 'user', $role );
        $context['reviews_settings']   = \SaltHareket\Reviews\ReviewsSettings::all();
        $context['review_modes']       = $type_config['modes'] ?? [];

        // Criteria — role bazlı
        $context['reviews_criteria'] = \SaltHareket\Reviews\ReviewsSettings::getCriteria( $role, 'user' );

        // Rating type
        $context['rating_type'] = $context['reviews_settings']['rating']['type'] ?? 'stars';
    }
}

Timber::render( array( 'author.twig', 'archive.twig' ), $context );
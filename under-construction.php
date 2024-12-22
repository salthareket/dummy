<?php

/*
Template Name: Under Construction
*/

$context = Timber::context();
$post = Timber::get_post_by("slug", "under-construction");
$context['post'] = $post;

Timber::render( array( 'page-under-construction.twig' ), $context );
<?php
/**
 * The template for the 404 page
 */

namespace App;

use Timber\Timber;

$context = function_exists('salt_get_context') ? salt_get_context() : Timber::context();
$templates = array('404.twig');
$query_var = get_query_var("post_type");

Timber::render($templates , $context );

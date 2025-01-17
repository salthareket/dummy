<?php
/**
 * The template for the 404 page
 */

namespace App;

use Timber\Timber;

$context = Timber::context();
$templates = array('404.twig');
$query_var = get_query_var("post_type");

Timber::render($templates , $context );

<?php
/**
 * The template for displaying 404 pages (Not Found)
 *
 * Methods for TimberHelper can be found in the /functions sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */
$context = Timber::context();
$templates = array('404.twig');
$query_var = get_query_var("post_type");

switch($query_var){
	case "session":
	    $icon = "<i class='fa-light fa-camera-web-slash fa-5x opacity-25 mb-3'></i>";
		$title = "Session Not Found!";
		$message = "Session you wanted to access is not exist.";
		$context["icon"] = $icon;
		$context["title"] = $title;
		$context["message"] = $message;
	    $context["wp_title"] = $title;
        //array_unshift( $templates, 'destinations/single.twig'); 
	break;

	default :
	    $icon = "<i class='fa-light fa-unlink fa-4-5x opacity-25 mb-3'></i>";
		$title = "Page not found!";
		$message = "Please contact us from <a href='#'>here</a> if you think this page link is broken.";
		$context["icon"] = $icon;
		$context["title"] = $title;
		$context["message"] = $message;
		$context["wp_title"] = $title;
		//array_unshift( $templates, 'no-authorization.twig' );
	break;
}

Timber::render($templates , $context );

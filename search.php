<?php
/**
 * Search results page
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */
//$query_vars = $wp_query->query_vars;

/*if(is_user_logged_in() && !empty(get_query_var("s"))){
	$user = wp_get_current_user();
	userSearchTermSave($user, get_query_var("s"));
}*/
set_query_var('s', get_query_var('search_term'));

if(class_exists("SearchHistory")){
    $search_history_obj = new SearchHistory();
    $search_history_obj->set_term(get_query_var("s"));  
}


/*
$templates = array('search-result/archive.twig', 'index.twig' );
$context = Timber::context();

//$context['archives'] = new TimberArchives();

$context['title'] = get_search_query();
$context['is_search'] = true;
$context['posts'] = Timber::get_posts();

Timber::render( $templates, $context );
*/

$context            = Timber::context();
//$context['sidebar'] = Timber::get_widgets('shop-sidebar');

$is_brand_page = is_tax( 'product_brand' );
$product_page_type = "search";
$context['product_page_type'] = $product_page_type;

$context['is_search'] = is_search();
$context['keyword'] = implode(" ", get_query_var("search_terms"));//$wp_query->query["s"];

if(function_exists("get_free_shipping_amount")){
    $free_shipping_min_amount = get_free_shipping_amount();
    $context['free_shipping_min_amount']  = $free_shipping_min_amount;  
}


//$context['recently_viewed_products']  = salt_recently_viewed_products();

if($context["keyword"]){
   $context['title'] = sprintf( __('%s için arama sonuçları', 'jaguar'), '<strong>"'.$context['keyword'].'"</strong>' ); //get_post_type_object( "product" )->labels->name;
}else{
   $context['title'] = trans("Tüm sonuçlar");
}
$context["post_types"] = get_post_types_with_taxonomies();

$context['posts'] = Timber::get_posts();
global $wp_query;
$context["found_posts"] = $wp_query->found_posts;
Timber::render(array('search/search.twig'), $context);
//Timber::render(array('templates/woo/'.$template.'.twig'), $context);
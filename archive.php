<?php
/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.2
 */


namespace App;

use Timber\Timber;


global $wp_query;
$args;
$templates = array( 'post/archive.twig', 'index.twig' );

$page_type = "post";


$data = Timber::context();

$data['title'] = 'Archive';

$query_vars = $wp_query->query_vars;
$taxonomy='';
if(array_key_exists("taxonomy", $query_vars)){
   $taxonomy = $query_vars['taxonomy'];
}
$post_type='';
if(array_key_exists("post_type", $query_vars)){
   $post_type=$query_vars['post_type'];
}

if ( is_day() ) {
	
	$data['title'] = 'Archive: '.get_the_date( 'D M Y' );
	$page_type = "day";
	$data['post_type'] = $post_type;

	
} else if ( is_month() ) {
	
	$data['title'] = 'Archive: '.get_the_date( 'M Y' );
	$page_type = "month";
	$data['post_type'] = $post_type;
	
} else if ( is_year() ) {
	
	$data['title'] = 'Archive: '.get_the_date( 'Y' );
	$page_type = "year";
	$data['post_type'] = $post_type;
	
} else if ( is_tag() ) {

	$term = Timber::get_terms("tag", array('slug' => get_query_var( 'tag' )));//get_term_by( 'slug', $tax, $taxonomy);
	$data["tax"] = $term;
   $data['post_type'] = $post_type;
	$data['title'] = single_tag_title( '', false );
	$page_type = "tag";


} else if ( is_category() ) {

   $post_type = get_post_type();
	$post_type = $post_type == '' ? 'post' : $post_type;
	$term = Timber::get_terms("category", array('term_id' => get_query_var( 'cat' )));//get_term_by( 'slug', $tax, $taxonomy);
	$data["tax"] = $term;
	$data['title'] = single_cat_title( '', false );
	$data['post_type'] = $post_type;
	array_unshift( $templates, $post_type.'/archive.twig');
	array_unshift( $templates, 'archive-' . get_query_var( 'cat' ) . '.twig' );
	$page_type = "category";

} else if ( is_post_type_archive()) {

   $data['post_type'] = $post_type;
	$data['title'] = post_type_archive_title( '', false );
	array_unshift( $templates, $post_type.'/archive.twig');
	array_unshift( $templates, 'archive-' . $post_type . '.twig' );
	$page_type = "post";
	
} elseif (!empty($taxonomy)) {

	  //$taxonomy = $wp_query->query_vars['taxonomy'];
	  //$tax = $wp_query->query_vars['term'];
	
	  if($wp_query->query_vars["taxonomy"] == $taxonomy){
         $tax = $wp_query->query_vars["term"];
         $data[str_replace("-", "_", $taxonomy)] = $tax;
	  }

	  //$tax = $wp_query->query_vars[$taxonomy];
	  //$page = Timber::get_terms(array("taxonomy" => $taxonomy, 'slug' => $tax, "hide_empty" => false));//get_term_by( 'slug', $tax, $taxonomy);

	  $term = get_term_by('slug', $tax, $taxonomy);
	  $children = array();
     if ($term) {
        $term =  Timber::get_term($term->term_id);
        $children = Timber::get_terms(array("taxonomy" => $taxonomy, 'parent' => $term->id, "hide_empty" => false));
     }

	  $data["tax"] = $term;
	  /*check taxonomy's posttype*/
	  if ($taxonomy) {
		  $taxObject = get_taxonomy($taxonomy);
		  $postType = $taxObject->object_type[0];
		  if($postType){
			  array_unshift( $templates, $postType.'/archive.twig');
			  array_unshift( $templates, 'archive-'.$postType.'.twig');
		  }
	  }
	  //if (isset($page[0]->name)) {
		//  $data['title'] = $page[0]->name;
	  //} else {
		  $data['title'] = $term->name;
		  $data['children'] = $children;
	  //}
	  array_unshift( $templates, $taxonomy.'/archive.twig', 'archive.twig');  
	  $page_type = "category";

}elseif( is_tax() ){
	echo "tax page";
	$page_type = "category";
}

//get date based post archive urls
//$data['archives'] = new TimberArchives();
//$posts = Timber::get_posts();//new Timber\PostQuery();
//$data['posts'] = $posts;//->to_array();
//$data['pagination'] = Timber::get_pagination();
$data['page_type'] = $page_type;

Timber::render( $templates, $data );
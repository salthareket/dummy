<?php

if (!class_exists('Timber')) {
    echo 'Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
    return;
}


$context = Timber::context();
$context['sidebar'] = Timber::get_widgets('shop-sidebar');


if (is_singular('product')) {

    $context['post'] = Timber::get_post();
    $product = wc_get_product($context['post']->ID);
    $context['product'] = $product;

    // Get related products
    $related_products = array();
    $related_limit = wc_get_loop_prop('columns');
    $related_ids = wc_get_related_products($context['post']->id, $related_limit);
    if($related_ids){
        $related_products = Timber::get_posts($related_ids);
    }
    $context['related_products'] = $related_products;

    wp_reset_postdata();

    Timber::render('product/single-product.twig', $context);

} else {

    /*global $wp_query;
    $paged = intval($wp_query->query_vars["paged"]);
    $paged = $paged<1?1:$paged;
    $context['paged'] = $paged;*/

    //$posts_per_page = intval($wp_query->query_vars["posts_per_page"]);

    $woocommerce_shop_page_display = get_option("woocommerce_shop_page_display");// empty, subcategories, both
    $woocommerce_category_archive_display = get_option("woocommerce_category_archive_display"); //empty, subcategories, both

    $woocommerce_catalog_columns = get_option("woocommerce_catalog_columns");
    $woocommerce_catalog_rows = get_option("woocommerce_catalog_rows");
    $posts_per_page = intval($woocommerce_catalog_columns * $woocommerce_catalog_rows);

    /*global $wp;
    $current_url = current_url();
    $query_string = add_query_arg( array(), $wp->request );
    if($query_string){
        $current_url = str_replace($query_string."/", "", $current_url);
    }*/

    if (is_product_category()) {
        $queried_object = get_queried_object();
        $term_id = $queried_object->term_id;
        //$category = Timber::get_term($term_id, 'product_cat');
        $category = get_term($term_id, 'product_cat');
        $context['category'] = Timber::get_term($term_id, 'product_cat');
        $context['title'] = single_term_title('', false);
        if($woocommerce_shop_page_display == "subcategories" && !$context['category']->children()){
           $woocommerce_shop_page_display = "";   
        }
    }else{
        $context['title'] = get_post_type_object( "product" )->labels->name;
    }

    switch($woocommerce_shop_page_display){
        case "" :
           // $posts = get_posts();
        break;

        case "subcategories" :
            $parentid = get_queried_object_id();
            $parentid = empty($parentid)?0:$parentid;   
            $args = array(
                'taxonomy' => 'product_cat',
                'parent' => $parentid,
                'number' => $posts_per_page,
                'offset' => ( $paged * $posts_per_page ) - $posts_per_page
            );
            $posts = Timber::get_terms( $args );
            $posts_total = wp_count_terms($args);
            
            $pagination_terms = array(
                "page" => $paged,
                "total" => $posts_total,
                "posts_per_page" => $posts_per_page,
                "total_page" => ceil($posts_total / $posts_per_page),
                "url" => $current_url
            );
            $context['pagination_terms'] = $pagination_terms;

        break;

        case "both" :

        break;
    }
    //$context['current_url'] = $current_url;
    $context['posts'] = Timber::get_posts();

    //global $wp_query;
    //print_r($wp_query);

    //unset($_SESSION['query_pagination_vars']);
    //unset($_SESSION['query_pagination_request']); 



        /*$query_vars = query_vars_for_pagination($wp_query->query_vars);
        $_SESSION['query_pagination_vars'] = $query_vars;

        if(isset($_GET["yith_wcan"]) || isset($_GET['orderby'])){
            $_SESSION['query_pagination_request'] = $wp_query->request;
            print_r($_SESSION['query_pagination_request']);
        }*/


    

    Timber::render('product/archive.twig', $context);
    //unset($_SESSION["paged_ajax"]);
}
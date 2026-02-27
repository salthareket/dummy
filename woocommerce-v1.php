<?php

if (!class_exists('Timber')){
    echo 'Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
    return;
}

if(ENABLE_WOO_API){
    //use Automattic\WooCommerce\Client;
    //use Automattic\WooCommerce\HttpClient\HttpClientException;

    $local_client = get_option("options_woo_api_local_client");
    $local_client_secret = get_option("options_woo_api_local_client_secret");
    $live_client = get_option("options_woo_api_live_client");
    $live_client_secret =  get_option("options_woo_api_live_client_secret");

    if(isLocalhost()){
       if($local_client && $local_client_secret){
            $client = $local_client;
            $client_secret = $local_client_secret; 
       }
    }else{
        if($live_client && $local_client_secret){
            $client = $live_client;
            $client_secret = $live_client_secret;
        }
    }
    if($client && $client_secret){
        $GLOBALS['woo_api'] = new Client(
                get_site_url(),
                $client, 
                $client_secret,
                [
                    'wp_api' => true,
                    'version' => 'wc/v3',
                    'query_string_auth' => true
                ]
        );        
    }
}


$context            = Timber::context();
$context['sidebar'] = Timber::get_widgets('shop-sidebar');
$is_brand_page = is_tax( 'product_brand' );
$is_search = is_search();
$context['is_search'] = $is_search;


$free_shipping_min_amount = 0;
if(function_exists("get_free_shipping_amount")){
   $free_shipping_min_amount = get_free_shipping_amount();
}
$context['free_shipping_min_amount']  = $free_shipping_min_amount;

$site_config = $GLOBALS["site_config"];
$ajax = $site_config["ajax"];
$pagination_type = $site_config["pagination_type"];
$context["ajax"] = $ajax;
$context["pagination_type"] = $pagination_type;

$product_page_type = "";
switch(true){

    case is_shop():
         $product_page_type = "shop";
         $context['title'] = get_the_title( get_option( 'woocommerce_shop_page_id' ) );
    break;
    case is_singular('product'):
         $product_page_type = "product";
    break;
    case is_product_category():
         $product_page_type = "product_cat";
    break;
    case is_checkout():
         $product_page_type = "checkout";
         break;
    case is_page_template('favorites'):
         $product_page_type = "favorites";
         break;
    case is_tax("product_brand"):
         $product_page_type = "product_brand";
         break;
    case is_tax():
         $product_page_type = "product_tax";
         break;
    default:
         $product_page_type = "shop";
    break;
}

$context['product_page_type'] = $product_page_type;


switch($product_page_type){

    case "shop" :
         $shop_view = "yith-filters";//default, categories, default_filters
         if(ENABLE_FILTERS){
              if(defined( 'YITH_WCAN' )){
                $shop_view = "yith-filters";
              }else{
                $shop_view = "default-filters";
              }
          }
          //$shop_view = "default-filters";

         switch($shop_view){

             case "categories":
                 $template = 'templates/woo/shop-categories';
                 $context['categories'] = Timber::get_terms("product_cat", array(
                    //'exclude' => get_option( 'default_product_cat' ),
                    'parent' => 0
                 ));
             break;


             case "default-filters":
                  /*if($is_search){
                      $context['title'] = get_search_query();
                      if(empty($context['title'])){
                         $context['title'] = get_post_type_object( "product" )->labels->name;
                      }
                  }else{
                      $context['title'] = get_post_type_object( "product" )->labels->name;
                  }*/
                  $template = 'templates/woo/shop-filters';
                  $posts = Timber::get_posts();
                  $queried_object = get_queried_object();
                  //$term_id = $queried_object->term_id;
                  //$thumbnail_id = get_term_meta( $term_id, 'thumbnail_id', true ); // Get Category Thumbnail
                  //$context['category'] = Timber::get_term( $term_id, 'product_cat' );
                  $context['title'] = get_post_type_object( "product" )->labels->name;
                  //$context['image'] = wp_get_attachment_url( $thumbnail_id );

                  $context['query_vars'] = woo_sidebar_filter_vars();


                  $siralama = get_query_var('siralama');
                  if(!empty($siralama)){
                      $context['query_vars_sorting'] = array(
                         "name"  => "Sıralama",
                         "slug"  => "siralama",
                         "term" => $siralama
                      );
                  }

                  $cat_args = array(
                      'orderby'    => "menu_order",
                      'order'      => "asc",
                      'hide_empty' => true,
                  );
                  $product_categories_active = explode(",", $kategori);
                  $product_categories = get_terms( 'product_cat', $cat_args );
                  if($product_categories_active){
                      foreach($product_categories as $key=>$product_category){
                          $product_categories[$key]->active = in_array($product_category->slug, $product_categories_active)?"1":"0";
                      }
                  }
                  $context["product_categories"] = $product_categories;

                  /*$queryType = "category";
                  if(get_query_var('fiyat') || get_query_var('beden') || get_query_var('tas') || get_query_var('materyal') || get_query_var('color') || get_query_var('cinsiyet')){
                     $queryType = "main";
                  }*/

                  //get all attributes on category
                  $query_args = array(
                      'status'    => 'publish',
                      'limit'     => -1,
                      //'category'  => array($context['category']->slug)
                  );
                  
                  //if($queryType == "main"){
                  //    $category_products = $posts;
                  //}else{
                      $category_products = wc_get_products($query_args);
                  //}
                  
                  $context['category_attributes'] = woo_filter_category_attributes($category_products);


                  $price_range = get_filtered_price();
                  $min = $price_range["min"];
                  $max = $price_range["max"];
                  $category_price_range = array(
                                       "min" => $min,//wc_price( $min ), 
                                       "max" => $max//wc_price( $max )
                                  );
                  $context['category_price_range'] = $category_price_range;
                  //echo '<pre>'; print_r($category_price_range); echo '</pre>';


                  if($min != $max){
                      $category_price_set = array();
                      $price_item_step = $max/5;//100;
                      $price_item_start = 0;
                      for($price_item=0; $price_item_start<$max; $price_item++){
                          $category_price_set_item = array(
                              "min" => $price_item_start,
                              "max" => ($price_item_start + $price_item_step)
                          );
                          $query_vars = explode(",", get_query_var("fiyat"));
                          $category_price_set_item["active"] = in_array( $price_item_start."|".($price_item_start + $price_item_step), $query_vars)?"1":"0";
                          array_push($category_price_set, $category_price_set_item);
                          $price_item_start += $price_item_step;
                      }
                      $context['category_price_set'] = $category_price_set;
                      //print_r( $category_price_set);
                  }

                     
                  $query_vars = explode(",", get_query_var("durum"));
                  $category_choices = array(
                      array(
                          "name"   => "Çok Satanlar",
                          "slug"   => "cok-satanlar",
                          "active" =>  in_array( "cok-satanlar", $query_vars)?"1":"0"
                      ),
                      array(
                          "name" => "İndirimli Ürünler",
                          "slug" => "indirimli-urunler",
                          "active" =>  in_array( "indirimli-urunler", $query_vars)?"1":"0"
                      ),
                      array(
                          "name" => "Yeni Ürünler",
                          "slug" => "yeni-urunler",
                          "active" =>  in_array( "yeni-urunler", $query_vars)?"1":"0"
                      ),
                      array(
                          "name" => "Tükenmek Üzere",
                          "slug" => "tukenmek-uzere",
                          "active" =>  in_array( "tukenmek-uzere", $query_vars)?"1":"0"
                      )
                  );
                  if($free_shipping_min_amount>0){
                      $category_choices[] = array(
                          "name"   => "Ücretsiz kargo",
                          "slug"   => "ucretsiz-kargo",
                          "active" =>  in_array( "ucretsiz-kargo", $query_vars)?"1":"0"
                      );
                  }

                  $context['category_choices'] = $category_choices;

                  $context['pagination'] = Timber::get_pagination();

                  $context['posts'] = $posts;
             break;

             case "yith-filters" :
                $template = 'templates/woo/shop-filters-yith';
                $post = get_post();
                $posts = Timber::get_posts();
                $context['post'] = $post;
                $context['posts'] = $posts;
                $context['pagination'] = $posts->pagination();
                
             break;

             case "default" :
                $template = 'templates/woo/shop';
                $posts = Timber::get_posts();//Timber::get_posts();
                $context['pagination'] = $posts->pagination();
                $context['posts'] = $posts;
             break;
         }
    break;


    /*
    case "search" :
           $template = 'templates/woo/search';
           $query_vars = $wp_query->query_vars;
           $context['title'] = get_search_query();
           $context['posts'] = Timber::get_posts($query_vars->post_type);
    break;
    */

    case "product_cat":
    case "product_tax":

        $template = 'templates/woo/archive';
        $has_child = false;
        $object = get_queried_object();

        //get_terms_first_post_image($taxonomy, $term_id);
        
        if($product_page_type == "product_cat"){
            $tax_root = get_term_root( $object->term_id, "product_cat");
            $children = get_terms( $object->taxonomy, array(
                'parent'    => $object->term_id,
                'hide_empty' => false
            ));
            if($children) {
               if(!get_field("filter_children", "product_cat_".$tax_root->term_id)){
                  $has_child = true;
                  $template = 'templates/woo/archive-category';
               }
            }            
        }



          

          if($product_page_type == "product_tax"){
              $taxonomy = str_replace("pa_","",$object->taxonomy);
              $term = $object->slug;
              $shop_url = wc_get_page_permalink( 'shop' );
              wp_redirect( $shop_url.'?'.$taxonomy.'='. $term );
              exit;
          }

          /*if(!$ajax){
            $bum=category_queries_ajax(array(), $query_vars);
            print_r($bum);
            $posts = Timber::get_posts();
            global $wp_query;
            $context['found_posts'] = $wp_query->found_posts;
          }*/
          
          if(!$has_child){
              if(ENABLE_FILTERS){
                  $query_vars = woo_sidebar_filter_vars();//url'deki tum attribute'leri getirir

                  $query = category_queries_ajax();
                  if(!$ajax){
                      $posts = Timber::get_posts($query["query"]);
                      //global $wp_query;
                      $context['found_posts'] = $posts->to_array();//$wp_query->found_posts;
                      $context['query_vars'] = $query["query_vars"];
                      $context = woo_sidebar_filters($context, $product_page_type, $free_shipping_min_amount, $query_vars, array());
                  }else{
                    $GLOBALS["query_vars"] = $query["query_vars"];
                  }
              }else{
                  if(!$ajax){
                      $posts = Timber::get_posts();
                      //global $wp_query;
                      $context['found_posts'] =  $posts->to_array();//Timber::get_posts();//$wp_query->found_posts;
                  }
              }            
          }else{
              $posts = Timber::get_terms( $object->taxonomy, array(
                'parent'    => $object->term_id,
                'hide_empty' => false
              ));
              global $wp_query;
              $context['found_posts'] = Timber::get_posts();//$wp_query->found_posts);
          }

        if($posts){
            //$context['pagination'] = $posts->to_array()->get_pagination();
        }
          
          $context['posts'] = $context['found_posts'];
          $category = get_terms("product_cat", array('term_id' => $wp_query->get_queried_object()->term_id));//get_term_by( 'slug', $tax, $taxonomy);
          if($category){
            print_r($category);
             $category = $category[0];
          }else{
             $category = array();
          }
          $context["tax"] = $category;
          $context["tax_root"]=$tax_root;
          wp_reset_postdata();
          wp_reset_query();

            /*$posts = Timber::get_posts();
            $context['products'] = $posts;

            if ( is_product_category() ) {
                $queried_object = get_queried_object();
                $term_id = $queried_object->term_id;
                $context['category'] = get_term( $term_id, 'product_cat' );
                $context['title'] = single_term_title( '', false );
            }*/
    break;


    case "product_brand":

          $shop_url = wc_get_page_permalink( 'shop' );
          wp_redirect( $shop_url."?marka=".get_queried_object()->slug );
          exit;


                $template = 'templates/woo/archive';
        
                $posts = Timber::get_posts();
                $context['products'] = $posts;
                $context["title"] = get_queried_object()->name;
                $queried_object = get_queried_object();
                if ( ! empty( $queried_object ) ) {
                    if(array_key_exists("taxonomy",$queried_object)){
                        if($queried_object->taxonomy == "product_brand"){
                            $term_id = $queried_object->term_id;
                            $brand = Timber::get_terms("product_brand",array('term_id'=>$term_id));
                            if(count($brand)>0){
                               $brand=$brand[0]; 
                            }
                            if ( $brand ) {
                                $thumb_id = get_term_meta( $brand->term_id, 'thumbnail_id', true );
                                $brand->title =  $brand->name;
                                $brand->description =  term_description( $brand->term_id, 'product_brand' );
                                $brand->image = wp_get_attachment_url(  $thumb_id );
                                $brand->url = get_term_link( $brand->term_id, 'product_brand' );
                                $brand->bg =  get_field('brand_bg', 'product_brand_' . $brand->term_id);

                                //get brand terms
                                $term_items = array();
                                $brand_categories = array();
                                $category_data = array();
                                $category_data["title"] = trans("Tüm Ürünler");
                                $category_data["url"] = $brand->url;
                                $category_data["active"] = 1;
                                $category_data["class"] = "";
                                $brand_categories[] =  $category_data;
                                /*foreach ( $posts as $post ) {
                                    $args = array('orderby' => 'menu_order', 'order' => 'ASC', 'fields' => 'all');
                                    $terms =  wp_get_post_terms( $post->ID, 'product_cat', $args );
                                    if($terms){
                                        foreach ( $terms as $term ) {
                                            if(!in_array($term->term_id,$term_items)){
                                                $category_data = array();
                                                $category_data["title"] = $term->name;
                                                $category_data["url"] = get_term_link( $term ).'?product_brand='.$brand->slug;
                                                $category_data["active"] = 0;
                                                $category_data["class"] = "";
                                                $brand_categories[] =  $category_data;
                                                $term_items[]=$term->term_id;
                                            }
                                        }
                                    }   
                                }*/

                                foreach ( $GLOBALS['brand_categories_temp'][$brand->slug] as $category ) {
                                    $category_data = array();
                                    $category_data["title"] = $category->name;
                                    $category_data["url"] = get_term_link( $category ).'?product_brand='.$brand->slug;
                                    $category_data["active"] =  $category->term_id==$term_id?1:0;
                                    $category_data["class"] = "";
                                    $brand_categories[] =  $category_data;
                                }

                                //add other brands
                                $category_data = array();
                                $category_data["title"] = trans('Diğer Markalar');
                                $category_data["url"] = get_permalink( wc_get_page_id( 'shop' ) );
                                $category_data["active"] =  0;
                                $category_data["class"] = "brands-other";
                                $brand_categories[] =  $category_data;
                                //add categries to brand
                                $brand->categories = $brand_categories;
                            }
                            $context['brand'] = $brand;
                            //print_r($brand);
                        } 
                    } 
                }  
    break;


    case "product": 

                $template = 'templates/woo/single-product';
                $context['post']    = Timber::get_post();
                $product_id         = $context['post']->ID;
                $product            = wc_get_product( $product_id );
                $context['product'] = $product;
                $context['product_type'] = $product->get_type();

                //$terms = wc_get_product_terms( $product->get_id(), 'product_cat', array('orderby' => "id", 'order' => "DESC", 'fields' => 'ids') );
                $product_brand = wc_get_product_terms( $product->get_id(), 'yith_product_brand', array('fields' => 'ids') );

                // related products
                if($product_brand){
                   $args = array( 
                        'post_type' => "product",  
                        //'fields'          => 'ids', // Only get post IDs
                        'numberposts'  => -1, 
                        'post__not_in' => array($product->get_id()), 
                        'relation'      => 'AND',         
                        'tax_query' => array(
                            array(
                                'taxonomy' => "yith_product_brand",
                                'field' => 'term_id',
                                'terms' => $product_brand,
                                'operator' => 'IN'
                            )
                        )
                    );
                    $related_ids = Timber::get_posts($args);
                    if($related_ids){
                       $context['related_products']   = $related_ids;
                    }
                }else{
                    $related_limit                   = wc_get_loop_prop( 'columns' );
                    $related_ids                     = wc_get_related_products( $product->get_id(), $related_limit );
                    if($related_ids){
                        $context['related_products']   = Timber::get_posts( $related_ids );
                    }
                }
                
                // up-sell products
                $upsell_ids                      = $product->get_upsell_ids();
                if($upsell_ids){
                   $context["upsell_products"]   = Timber::get_posts($upsell_ids);
                }
                // cross-sell products
                $crosssell_ids                   = get_post_meta($product_id, '_crosssell_ids', true);//$product->get_cross_sell_ids();
                if($crosssell_ids){
                   $context["crosssell_products"]= Timber::get_posts($crosssell_ids);
                }
                /*$last_viewed_products = array();
                if($user->logged){
                   $last_viewed_products = Timber::get_posts( salt_recently_viewed_products());
                }
                $context["last_viewed_products"] = $last_viewed_products;*/

                wp_reset_postdata();
    break;

    case "favorites" :
        $template = 'templates/woo/favorites';
    break;

    case  "checkout":
        $template = 'templates/woo/shop';
        echo "test";
    break;

}
global $wp_query;
$context["found_posts"] = $wp_query->found_posts;
Timber::render(array($template.'.twig'), $context);

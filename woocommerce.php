<?php
if (!class_exists('Timber')) {
    echo 'Timber not activated.';
    return;
}

$context = Timber::context();
$context['sidebar'] = Timber::get_widgets('shop-sidebar');

if (is_singular('product')) {

    $context['post'] = Timber::get_post();

    // related products
    $related_limit = wc_get_loop_prop('columns');
    $related_ids   = wc_get_related_products($context['post']->id, $related_limit);
    $context['related_products'] = $related_ids ? Timber::get_posts($related_ids) : [];

    wp_reset_postdata();

    Timber::render('woo/single-product.twig', $context);

} else {

    global $wp_query;

    $paged       = max(1, get_query_var('paged'));
    $current_url = get_pagenum_link(1);

    $woocommerce_shop_page_display     = get_option('woocommerce_shop_page_display');     // empty, subcategories, both
    $woocommerce_category_archive_display = get_option('woocommerce_category_archive_display');

    $woocommerce_catalog_columns = get_option('woocommerce_catalog_columns');
    $woocommerce_catalog_rows    = get_option('woocommerce_catalog_rows');
    $posts_per_page              = intval($woocommerce_catalog_columns * $woocommerce_catalog_rows);

    if (is_product_category()) {
        $queried_object = get_queried_object();
        $term_id = $queried_object->term_id;

        $context['category'] = Timber::get_term($term_id, 'product_cat');
        $context['title']    = single_term_title('', false);

        // Alt kategorisi yoksa subcategories modunu kapat
        if ($woocommerce_shop_page_display == 'subcategories' && !$context['category']->children()) {
            $woocommerce_shop_page_display = '';
        }
    } else {
        $context['title'] = get_post_type_object('product')->labels->name;
    }

    switch ($woocommerce_shop_page_display) {

        case '':
            // normal ürün loop — woocommerce_content() halleder
        break;

        case 'subcategories':
            $parentid = get_queried_object_id() ?: 0;
            $args = [
                'taxonomy' => 'product_cat',
                'parent'   => $parentid,
                'number'   => $posts_per_page,
                'offset'   => ($paged * $posts_per_page) - $posts_per_page,
            ];
            $posts_total = wp_count_terms($args);

            $context['posts']            = Timber::get_terms($args);
            $context['pagination_terms'] = [
                'page'           => $paged,
                'total'          => $posts_total,
                'posts_per_page' => $posts_per_page,
                'total_page'     => ceil($posts_total / $posts_per_page),
                'url'            => $current_url,
            ];
        break;

        case 'both':
            // hem kategori hem ürün — gerekirse eklenecek
        break;
    }

    $context['posts'] = Timber::get_posts();

    Timber::render('woo/archive.twig', $context);
}
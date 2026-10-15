<?php

if (!class_exists('Timber')) {
    echo 'Timber not activated.';
    return;
}

$context = function_exists('salt_get_context') ? salt_get_context() : Timber::context();
$context['sidebar'] = Timber::get_widgets('shop-sidebar');

if (is_singular('product')) {

    $context['post'] = Timber::get_post();

    // Product Lists config
    $pl = (array) get_option( 'options_product_lists', [] );
    $context['pl_cfg'] = $pl;

    // Related products
    $related_cfg   = (array) ( $pl['related'] ?? [] );
    $related_count = (int) ( $related_cfg['count'] ?? 4 );
    $related_ids   = wc_get_related_products( $context['post']->id, max( $related_count, 4 ) );
    $context['related_products'] = $related_ids ? Timber::get_posts( $related_ids ) : [];
    $context['related_cfg']      = $related_cfg;

    // Upsell products
    $upsell_cfg = (array) ( $pl['upsell'] ?? [] );
    if ( ! empty( $upsell_cfg['enabled'] ) ) {
        $_product   = wc_get_product( $context['post']->id );
        $upsell_ids = $_product ? array_slice( $_product->get_upsell_ids(), 0, (int)($upsell_cfg['count'] ?? 4) ) : [];
        $context['upsell_products'] = $upsell_ids ? Timber::get_posts( $upsell_ids ) : [];
    }
    $context['upsell_cfg'] = $upsell_cfg;

    // Cross-sell products
    $crosssell_cfg = (array) ( $pl['crosssell'] ?? [] );
    if ( ! empty( $crosssell_cfg['enabled'] ) ) {
        $_product       = $_product ?? wc_get_product( $context['post']->id );
        $crosssell_ids  = $_product ? array_slice( $_product->get_cross_sell_ids(), 0, (int)($crosssell_cfg['count'] ?? 4) ) : [];
        $context['crosssell_products'] = $crosssell_ids ? Timber::get_posts( $crosssell_ids ) : [];
    }
    $context['crosssell_cfg'] = $crosssell_cfg;

    // Recently Viewed — WC cookie'den oku (woocommerce_recently_viewed)
    $rv_cfg = (array) ( $pl['recently_viewed'] ?? [] );
    $context['rv_cfg'] = $rv_cfg;

    // Cookie tracking — mevcut ürünü cookie'ye yaz (WC track_product_view yerine)
    // Timber/custom render'da WC'nin orijinal tracker'ı çalışmayabileceğinden burada set ediyoruz.
    $current_product_id = (int) $context['post']->id;
    $viewed_cookie = ! empty( $_COOKIE['woocommerce_recently_viewed'] )
        ? array_filter( array_map( 'absint', explode( '|', wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ) ) ) )
        : [];
    $viewed_cookie = array_values( array_diff( $viewed_cookie, [ $current_product_id ] ) );
    array_unshift( $viewed_cookie, $current_product_id );
    $viewed_cookie = array_slice( $viewed_cookie, 0, 15 );

    if ( ! headers_sent() ) {
        wc_setcookie( 'woocommerce_recently_viewed', implode( '|', $viewed_cookie ) );
    }

    // Product Tabs config
    $context['product_tabs_cfg'] = (array) get_option( 'options_product_tabs', [] );
    if ( ! empty( $rv_cfg['enabled'] ) ) {
        // $viewed_cookie zaten güncel (mevcut ürün dahil, başta)
        // Mevcut ürünü listeden çıkar
        $viewed_raw = ! empty( $rv_cfg['exclude_current'] )
            ? array_values( array_diff( $viewed_cookie, [ $current_product_id ] ) )
            : $viewed_cookie;

        $rv_count   = max( 1, (int) ( $rv_cfg['count'] ?? 6 ) );
        $viewed_raw = array_slice( $viewed_raw, 0, $rv_count );

        $context['recently_viewed_products'] = ! empty( $viewed_raw )
            ? Timber::get_posts( [
                'post_type'           => 'product',
                'post__in'            => $viewed_raw,
                'orderby'             => 'post__in',
                'posts_per_page'      => $rv_count,
                'post_status'         => 'publish',
                'ignore_sticky_posts' => true,
            ] )
            : [];
    }

    // Sticky ATC config
    $context['sticky_atc_cfg'] = (array) get_option( 'options_sticky_atc', [] );

    // ProductStats — görüntülenme sayacı (session cookie ile spam koruması)
    if ( class_exists( 'SaltHareket\\ProductStats' ) ) {
        \SaltHareket\ProductStats::incrementView( $context['post']->id );
    }

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

    // ACF post_pagination ayarı varsa onu kullan
    $product_pagination = function_exists('get_post_type_pagination') ? get_post_type_pagination('product') : [];
    if (!empty($product_pagination['paged']) && !empty($product_pagination['posts_per_page'])) {
        $posts_per_page = intval($product_pagination['posts_per_page']);
    }

    // $wp_query'de posts_per_page'i şimdi set et — WooCommerce'in product_query hook'undan önce
    if ($posts_per_page > 0) {
        $wp_query->set('posts_per_page', $posts_per_page);
    }

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
            // Subcategory term'leri hepsi göster (pagination yok)
            // Ürünler woocommerce_content() ile ppp'ye göre paginate edilir
            $parentid = get_queried_object_id() ?: 0;
            $subcats = Timber::get_terms([
                'taxonomy' => 'product_cat',
                'parent'   => $parentid,
                'number'   => 0, // hepsi
                'hide_empty' => true,
            ]);
            if (!empty($subcats)) {
                $context['subcategories'] = $subcats;
            }
        break;
    }

    $context['posts'] = Timber::get_posts();

    // Timber pagination için max_num_pages'i düzelt
    if ($posts_per_page > 0 && $wp_query->found_posts > 0) {
        $wp_query->max_num_pages = ceil($wp_query->found_posts / $posts_per_page);
    }

    // ── Shop urun loop bulk prefetch ─────────────────────────────────────────
    // woo/archive.twig her urunu render ederken (fiyat, stok, thumbnail, meta)
    // ayri sorgu atiyor (~12 urun = ~146q). Render'dan ONCE urunleri TEK seferde
    // WC + WP object cache'e doldururuz; twig icindeki cagrilar 0q ile okur.
    if ( ! empty( $context['posts'] ) ) {
        $__shop_ids = [];
        foreach ( $context['posts'] as $__p ) {
            $__pid = is_object( $__p ) ? (int) ( $__p->ID ?? 0 ) : (int) $__p;
            if ( $__pid > 0 ) $__shop_ids[] = $__pid;
        }
        if ( $__shop_ids ) {
            if ( function_exists( '_prime_post_caches' ) ) {
                _prime_post_caches( $__shop_ids, true, true );
            }
            if ( function_exists( 'wc_get_products' ) ) {
                wc_get_products( [ 'include' => $__shop_ids, 'limit' => count( $__shop_ids ), 'return' => 'objects' ] );
            }
            // Reaction bulk prefetch — woo/tease.twig her urun icin 3-4 reaction
            // butonu (clap/like/favorite) render ediyor. Prefetch olmadan her biri
            // ayri count/has sorgusu atar. Tek seferde bulk cache'e al.
            if ( defined( 'ENABLE_REACTIONS' ) && ENABLE_REACTIONS
                && class_exists( '\\SaltHareket\\Reactions\\Admin\\ReactionsAjax' ) ) {
                \SaltHareket\Reactions\Admin\ReactionsAjax::prefetch( $__shop_ids, 'post' );
            }
            $__thumb_ids = [];
            foreach ( $__shop_ids as $__pid ) {
                $__tid = (int) get_post_thumbnail_id( $__pid );
                if ( $__tid > 0 ) $__thumb_ids[] = $__tid;
            }
            $__thumb_ids = array_values( array_unique( $__thumb_ids ) );
            if ( $__thumb_ids && function_exists( '_prime_post_caches' ) ) {
                _prime_post_caches( $__thumb_ids, true, true );
            }
        }
    }

    Timber::render('woo/archive.twig', $context);
}
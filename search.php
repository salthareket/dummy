<?php
/**
 * Search Results Page
 *
 * FiboSearch "See All Products" ve native WP search sonuçlarını render eder.
 * XT WooVAS / Iconic WSSV aktifse product_variation'ları da dahil eder.
 *
 * @package    SaltHareket\Theme
 * @version    1.1.0
 * @since      1.0.0
 *
 * CHANGELOG:
 * 1.1.0 - 2026-05-03
 *   - Add: FiboSearch "See All" — ?dgwt_wcas=1 geldiğinde product_variation'ları da dahil et
 *   - Add: product_variation exclude_from_search bypass (geçici override)
 *   - Fix: keyword boş geliyordu — $_GET['s'] fallback eklendi
 *   - Fix: set_query_var('s') $_GET['s'] fallback ile güncellendi
 *
 * 1.0.0 - İlk versiyon
 *   - Timber tabanlı search results sayfası
 *
 * HOW TO USE:
 *   WP search template — otomatik çalışır.
 *   FiboSearch "See All Products" linki ?s=...&post_type=product&dgwt_wcas=1 gönderir.
 *   XT_WOOVAS veya Iconic_WSSV aktifse variation'lar da sonuçlara dahil edilir.
 *
 * @example Normal search:
 *   /?s=wonder → search.php çalışır, Timber::get_posts() ile sonuçlar gelir
 *
 * @example FiboSearch See All:
 *   /?s=wonder&post_type=product&dgwt_wcas=1 → variation'lar da dahil edilir
 */
//$query_vars = $wp_query->query_vars;

/*if(is_user_logged_in() && !empty(get_query_var("s"))){
	$user = wp_get_current_user();
	userSearchTermSave($user, get_query_var("s"));
}*/
set_query_var('s', get_query_var('search_term') ?: ($_GET['s'] ?? ''));

if(class_exists("SearchHistory")){
    // SearchHistory constructor'ı hook'ları otomatik bağlar.
    // auto_track_search (the_posts filter) arama sonuçlarını otomatik kaydeder.
    // set_term() burada çağrılmıyor — double tracking'i önlemek için.
    new SearchHistory();
}

// Search tracking için found_posts'u shutdown'da geç
// the_posts filter'ında $query->found_posts henüz set edilmemiş olabilir
// Bu yüzden shutdown'da $wp_query->found_posts'u kullanıyoruz
if ( class_exists( 'SearchHistory' ) && is_search() ) {
    add_action( 'shutdown', function() {
        global $wp_query;
        $term = get_query_var( 's' ) ?: ( $_GET['s'] ?? '' );
        $term = trim( mb_strtolower( $term ) );
        if ( empty( $term ) ) return;

        $found = (int) $wp_query->found_posts;
        $sh = new SearchHistory();
        // Direkt upsert — no_results'ı $wp_query->found_posts ile güncelle
        global $wpdb;
        $table = $sh->get_table_name();
        $lang  = substr( get_locale(), 0, 2 );
        $no_results = $found > 0 ? 0 : 1;
        // Mevcut kaydı güncelle — sadece no_results kolonunu düzelt
        $wpdb->query( $wpdb->prepare(
            "UPDATE `{$table}` SET no_results = %d WHERE name = %s AND no_results != %d",
            $no_results, $term, $no_results
        ) );
    }, 20 );
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
$context['keyword'] = is_array(get_query_var("search_terms")) 
    ? implode(" ", get_query_var("search_terms")) 
    : (string) (get_query_var("s") ?: ($_GET['s'] ?? ''));

if(function_exists("get_free_shipping_amount")){
    $free_shipping_min_amount = get_free_shipping_amount();
    $context['free_shipping_min_amount']  = $free_shipping_min_amount;  
}

global $wp_query;

// ── FiboSearch "See All" — product_variation'ları da dahil et ────────────────
$is_fibo_search = !empty($_GET['dgwt_wcas']);
$variation_plugin_active = class_exists('XT_WOOVAS') || class_exists('Iconic_WSSV');
$post_type_param = $_GET['post_type'] ?? '';

if ($is_fibo_search && $variation_plugin_active && $post_type_param === 'product') {
    // FiboSearch "See All Products" — product + product_variation
    $search_term = get_query_var('s') ?: ($_GET['s'] ?? '');
    $paged       = get_query_var('paged') ?: 1;

    global $wp_post_types;
    $prev_exclude = $wp_post_types['product_variation']->exclude_from_search ?? true;
    if (isset($wp_post_types['product_variation'])) {
        $wp_post_types['product_variation']->exclude_from_search = false;
    }

    $combined_query = new WP_Query([
        's'                => $search_term,
        'post_type'        => ['product', 'product_variation'],
        'post_status'      => 'publish',
        'paged'            => $paged,
        'posts_per_page'   => get_option('posts_per_page', 10),
        'suppress_filters' => false,
    ]);

    if (isset($wp_post_types['product_variation'])) {
        $wp_post_types['product_variation']->exclude_from_search = $prev_exclude;
    }

    $wp_query = $combined_query;
    $context['posts'] = Timber::get_posts($combined_query);

} elseif (!empty($post_type_param) && $post_type_param === 'product' && !$is_fibo_search) {
    // post_type=product ama FiboSearch değil — sadece product ara
    $context['posts'] = Timber::get_posts();

} else {
    // Normal search (enter / search icon) — tüm public post type'lar
    // WP'nin default search query'si zaten tüm public post type'lara bakar
    $context['posts'] = Timber::get_posts();
}


if($context["keyword"]){
   $context['title'] = sprintf( translate('%s için arama sonuçları'), '<strong>"'.$context['keyword'].'"</strong>' );
}
$context["post_types"] = get_post_types_with_taxonomies();

$found_posts = $wp_query->found_posts;
$context["found_posts"] = $found_posts;
if($found_posts){

}else{
   $context['description'] = translate("İçerik bulunamadı");
}
Timber::render(array('search/search.twig'), $context);
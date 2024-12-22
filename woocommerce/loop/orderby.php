<?php

/**
 * Show options for ordering
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/orderby.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @package 	WooCommerce/Templates
 * @version     3.6.1
 */

if (!defined('ABSPATH')) {
  exit;
}

?>



<form class="woocommerce-ordering m-0" method="get" onsubmit="woocommerceOrderingSubmit()">
    <select name="orderby" class="orderby form-select" aria-label="<?php esc_attr_e('Shop order', 'jaguar'); ?>">
      <?php foreach ($catalog_orderby_options as $id => $name) : ?>
        <option value="<?php echo esc_attr($id); ?>" <?php selected($orderby, $id); ?>><?php echo esc_html($name); ?></option>
      <?php endforeach; ?>
    </select>
    <?php //<input type="hidden" name="paged" value="1" /> ?>
    <?php wc_query_string_form_fields(null, array('orderby', 'submit', 'paged', 'product-page')); ?>
</form>
<script>
    function woocommerceOrderingSubmit(){
        document.body.classList.add("loading-process");
        var currentUrl = window.location.href;
        var updatedUrl = currentUrl.replace(/\/page\/\d+\//, '/');
        history.replaceState(null, null, updatedUrl);
    }
</script>

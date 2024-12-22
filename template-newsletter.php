<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * To generate specific templates for your pages you can use:
 * /mytheme/views/page-mypage.twig
 * (which will still route through this PHP file)
 * OR
 * /mytheme/page-mypage.php
 * (in which case you'll want to duplicate this file and save to the above path)
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */
/*
Template Name: Newsletter
*/

$context = Timber::context();
$post = Timber::get_post();
$context['post'] = $post;



$context['reservation_review_id'] = get_query_var("reservation_review_id");

$page_type = get_query_var('nm');//reactivated , profile
$context['page_type'] = $page_type;

//get subscriber id
$subscriber_id = explode("-",get_query_var('nk'));
$subscriber_id=$subscriber_id[0];
//$wp_user_id = $wpdb->get_var("SELECT wp_user_id FROM wp_newsletter where id=".$subscriber_id);
$subscriber=$wpdb->get_row("SELECT id, token, wp_user_id, email, name, surname, profile_1 as phone, profile_2 as coupons FROM wp_newsletter where id=".$subscriber_id, ARRAY_A);
$context['subscriber'] = $subscriber;

$newsletter_profile_page_id = get_option( 'newsletter_page' );
if($newsletter_profile_page_id){
   $newsletter_profile_page_url = get_permalink($newsletter_profile_page_id)."?nm=profile&nk=".$subscriber["id"]."-".$subscriber["token"];
}
$context['profile_url'] = $newsletter_profile_page_url;



//cancel reservation if id provided
$reservation_id = get_query_var("reservation_id");
if($reservation_id){
   $rezervasyon_durumu = get_post_meta($reservation_id, "rezervasyon_durumu", true);
   if($rezervasyon_durumu != "tamamlandi"){
   	   update_field('rezervasyon_durumu', 'iptal', $reservation_id);
	   $coupon_id = get_post_meta($reservation_id, "rezervasyon_kupon", true);
	   if($coupon_id > 0){
	   	   $kupon_kodu = get_post_meta($coupon_id, "kupon_kodu", true);
	   	   $coupon_code = $kupon_kodu."-".substr($subscriber["token"], 0, 5)."-".$coupon_id;
	   	   update_field('rezervasyon_kupon', 0, $reservation_id);
	   	   $user_coupons = explode(",",$subscriber["coupons"]);
		   if(count($user_coupons)>0){
			    if(in_array($coupon_code, $user_coupons)){
	                $user_coupons = remove_element($user_coupons, $coupon_code);
	                $user_coupons = join(",",$user_coupons);
				    $wpdb->update(
						'wp_newsletter',
					    array(
							'profile_2' => $user_coupons
						),
						array( 'id' => $subscriber["id"] )
					);
					$subscriber=$wpdb->get_row("SELECT id, token, wp_user_id, email, name, surname, profile_1 as phone, profile_2 as coupons FROM wp_newsletter where id=".$subscriber_id, ARRAY_A);
	                $context['subscriber'] = $subscriber;
			    }
			}
	   }
	   reservation_notification($reservation_id, "iptal");
	   $context['alert_message'] = "Rezervasyonunuz iptal edildi.";
   }else{
   	   $context['alert_message'] = "Tamamlanmış rezervasyonunuzu iptal edemezsiniz.";
   }
   
}



$reservations = array();
$reservations_past = array();
//get reservations
if($subscriber["wp_user_id"] > 0){
	$args = array(
		'post_type'      => 'rezervasyon',
		'post_status'    =>  'publish',
	    'author'         =>  $subscriber["wp_user_id"],
	    'meta_key'	     => 'tarih',
	    'meta_type'      => 'DATETIME',
	    'orderby'		 => 'meta_value',
	    'order'			 => 'desc',
	    'posts_per_page' => -1,
	    'meta_query'     => array(
	    	array(
	    		'key'     => 'tarih',
	    		'value'   => date("Y-m-d H:i:s"),
				'type'    => 'DATETIME',
	    		'compare' => '>='
	    	)
	    )
	);
	$reservations = Timber::get_posts($args);

	$args = array(
		'post_type'      => 'rezervasyon',
	    'author'         =>  $subscriber["wp_user_id"],
	    'meta_key'	     => 'tarih',
	    'meta_type'      => 'DATETIME',
	    'orderby'		 => 'meta_value',
	    'order'			 => 'desc',
	    'posts_per_page' => -1,
	    'meta_query'     => array(
	    	array(
	    		'key'     => 'tarih',
	    		'value'   => date("Y-m-d H:i:s"),
				'type'    => 'DATETIME',
	    		'compare' => '<'
	    	)
	    )
	);
	$reservations_past = Timber::get_posts($args);		
}
$context['reservations'] = $reservations;
$context['reservations_past'] = $reservations_past;



$kupon = get_active_coupon();
if($kupon){
   $kupon = $kupon[0];
   $kupon->kupon_kodu = $kupon->kupon_kodu."-". substr($subscriber["token"], 0, 5)."-".$kupon->ID;
   //check is used bcompiler_write_exe_footer( 
   $user_coupons = $subscriber["coupons"];
   if($user_coupons){
   	  $user_coupons = explode(",",$subscriber["coupons"]);
	  if(!in_array($kupon->kupon_kodu, $user_coupons)){
	     $kupon = Timber::get_post($kupon);
	  }else{
	  	 $kupon = array();
	  }
	}else{
	   $kupon = Timber::get_post($kupon);	
	}
}
$context['kupon'] = $kupon;

Timber::render( array( 'page-newsletter.twig' ), $context );
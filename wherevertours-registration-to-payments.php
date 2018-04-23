<?php
/*
Plugin Name: Wherever Tours Registration to Payments
Plugin URI:
Description: Take registration information and process it for WooCommerce to handle payments
Version: 1.0
Author: Kevin J. McMahon Jr.
Author URI:
License:GPLv2
*/
?>
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'gform_after_submission_1', 'process_tour_payment', 10, 2 );
function process_tour_payment( $entry, $form) {
	global $woocommerce;
	$post = get_post( $entry['post_id']);
	$product_id = 2906;
	$wt_tour_registration_name = rgar( $entry, '1.2' ) . rgar( $entry, '1.3' ) . rgar( $entry, '1.4' ) . rgar( $entry, '1.6' ) . rgar( $entry, '1.8' );
	$tour_registration_title =  rgar( $entry, '14' ) . ' - ' . $wt_tour_registration_name . ' - ' . date("h:i:sa");
	$deposit = get_field('required_deposit_usd', $post);
	
	if (rgar( $entry, '10' ) == 'deposit'){
		$post_id = wp_insert_post(
			array(
				'post_title'	=> $tour_registration_title,
				'post_content'	=> 'Deposit: ' . $deposit . '<br>' . 'Name: ' . $wt_tour_registration_name,
				'post_type'		=> 'tour_registration'
			)
		);
		set_transient( 'tour_deposit', $deposit, 60);
		$woocommerce->cart->add_to_cart($product_id);
	}
}

function calculate_tour_payment($cart_item_data, $product_id, $variation_id){
	$calculated_price = get_transient('tour_deposit');
	if (!($tour_deposit === false)){
		$post_id = wp_insert_post(
			array(
				'post_title'	=> $tour_registration_title,
				'post_content'	=> $deposit,
				'post_type'		=> 'tour_registration'
			)
		);
			$product = wc_get_product( $product_id );
			$product_price = $product->get_price();
			//delete_transient( 'tour_deposit' );
			$cart_item_data['deposit'] = $product_price + $calculated_price;
			return $cart_item_data;
		}
    }
add_filter ('woocommere_add_cart_item_data', 'calculate_tour_payment', 10, 3 );

function update_wc_cart_totals($cart_obj) {
	foreach( $cart_obj->get_cart() as $key=>$value ) {
		if (isset ($value['deposit'])) {
			$price = $value['deposit'];
			$value['data']->set_price( ($price) );
		}
	}
}
add_action( 'woocommerce_before_calculate_totals', 'update_wc_cart_totals', 10, 1 );

?>
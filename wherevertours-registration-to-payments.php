<?php
/*
Plugin Name: Wherever Tours Registration to Payments
Plugin URI:
Description: Take registration information and process it for WooCommerce to handle payments
Version: 1.1
Author: Kevin J. McMahon Jr.
Author URI:
License:GPLv2
*/
?>
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'gform_after_submission_8', 'process_tour_payment', 10, 2 );
function process_tour_payment( $entry, $form ) {
	// Variables for Woocommerce
	global $woocommerce;
	$product_id = 2906;
	// Variables from Form Data
	$post = get_post( $entry['post_id']);
	$gf_nested_entry_ids = explode( ',', $entry[1] );
	$tour_registration_title =  rgar( $entry, '22' );
	$payment_option = rgar( $entry, '17');
	
	// Create Registration Post Function
	function wt_create_registration_entry($tour_registration_title, $wt_tour_registration_name, $deposit){
		wp_insert_post(
			array(
				'post_title'	=> $tour_registration_title . ' - ' . $wt_tour_registration_name . ' - ' . date("h:i:sa"),
				'post_content'	=> 'Deposit: ' . $deposit . '<br>' . 'Name: ' . $wt_tour_registration_name,
				'post_type'		=> 'tour_registration'
			)
		);
	}
	
	/*function wt_set_session_data_for_tour($deposit, $generated_tour_cart_title){
		$_POST['wt_deposit_amount'] = $deposit;
		$_POST['wt_tour_cart_title'] = $generated_tour_cart_title;
	}*/
	
	// Loop Through Nested Entries
	foreach ( $gf_nested_entry_ids as $gf_current_nested_entry_id ){
		// Get The Current Nested Entry's Form Data
		$current_nested_entry = GFAPI::get_entry($gf_current_nested_entry_id);
		// Get The Name
		$wt_tour_registration_name = rgar( $current_nested_entry, '1.2' ) . rgar( $current_nested_entry, '1.3' ) . rgar( $current_nested_entry, '1.4' ) . rgar( $current_nested_entry, '1.6' ) . rgar( $current_nested_entry, '1.8' );
		// Create the Product Title for Cart and Checkout
		$generated_tour_cart_title = 'Tour Deposit For: ' . $tour_registration_title . ' - ' . $wt_tour_registration_name;
		
		if ($payment_option == 'deposit'){
			$deposit = get_field('required_deposit_usd', $post);
			if(function_exists(wt_create_registration_entry)){
				wt_create_registration_entry($tour_registration_title, $wt_tour_registration_name, $deposit);
			}
			/*if(function_exists(wt_set_session_data_for_tour)){
				wt_set_session_data_for_tour($deposit, $generated_tour_cart_title);
			}*/
			$cart_item_data = array(
				'deposit'		=> $deposit,
				'cart_title'	=> $generated_tour_cart_title
			);
			$woocommerce->cart->add_to_cart($product_id, $cart_item_data);
		}
		
		elseif ($payment_option == 'custom_deposit'){
			$deposit = rgar( $entry, '26');
			if(function_exists(wt_create_registration_entry)){
				wt_create_registration_entry($tour_registration_title, $wt_tour_registration_name, $deposit);
			}
			if(function_exists(wt_set_session_data_for_tour)){
				wt_set_session_data_for_tour($deposit, $generated_tour_cart_title);
			}
			$cart_item_data = array(
				'deposit'		=> $deposit,
				'cart_title'	=> $generated_tour_cart_title
			);
			$woocommerce->cart->add_to_cart($product_id, $cart_item_data);
		}
	}
	
	//$wt_tour_registration_name = rgar( $entry, '1.2' ) . rgar( $entry, '1.3' ) . rgar( $entry, '1.4' ) . rgar( $entry, '1.6' ) . rgar( $entry, '1.8' );
	//$tour_registration_title =  rgar( $entry, '14' ) . ' - ' . $wt_tour_registration_name . ' - ' . date("h:i:sa");
	//$tour_registration_title =  rgar( $entry, '22' ) . ' - ' . date("h:i:sa");
	
}

/*function calculate_tour_payment($cart_item_data, $product_id, $variation_id){
	if(isset($_POST['wt_deposit_amount'])){
		$calculated_price = $_POST['wt_deposit_amount'];
		$product = wc_get_product( $product_id );
		$product_price = $product->get_price();
		$cart_item_data['deposit'] = $product_price + $calculated_price;
	}
	
	if(isset($_POST['wt_tour_cart_title'])){
		$cart_item_data['tour_cart_title'] = $_POST['wt_tour_cart_title'];
	}

	return $cart_item_data;
}
add_filter ('woocommerce_add_cart_item_data', 'calculate_tour_payment', 10, 3 );*/

// Gives WooCommerce Item A Unique Key
function namespace_force_individual_cart_items( $cart_item_data, $product_id ) {
	$unique_cart_item_key = md5( microtime() . rand() );
	$cart_item_data['unique_key'] = $unique_cart_item_key;
	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'namespace_force_individual_cart_items', 10, 2 );

// Update WooCommerce Cart
function update_wc_cart_totals($cart_obj) {
	foreach( $cart_obj->get_cart() as $key=>$value ) {
		$wc_product_data = $cart_obj['data'];
		if (isset ($value['deposit'])) {
			$price = $value['deposit'];
			$value['data']->set_price( $price );
		}
		if (isset ($value['cart_title'])) {
			if (method_exists( $wc_product_data, 'set_name' ) ) {
				$tour_cart_name = $value['cart_name'];
				$wc_product->set_name( $tour_cart_title );
			}
		}
	}
}
add_action( 'woocommerce_before_calculate_totals', 'update_wc_cart_totals', 10, 1 );

// Gets Tour Information and Populates Available Dates Into Gravity Form Fields
function populate_tour_dates( $form ){
	// Checks each form field
	foreach( $form['fields'] as &$field ) {
		// Only proceeds if Form Field has .tour-date CSS class
		if ( $field->type != 'select' || strpos( $field->cssClass, 'tour-date' ) === false ) {
			continue;
		}
			
		global $post;
		$id = $post->ID;
		$tour_dates = array();
		// Get ID of current post
		
		if( have_rows('available_tour_dates')):
		// Checks for Date Repeater Field
			while( have_rows('available_tour_dates') ): the_row();
				$tour_dates[] = array( 'text' => get_sub_field('tour_start_date'), 'value' => get_sub_field('tour_start_date') );
			endwhile;
		endif;
		$field->placeholder = "Select A Tour Date";
		$field->choices = $tour_dates;
	}
	return $form;
}
add_filter( 'gform_pre_render_8', 'populate_tour_dates' );
add_filter( 'gform_pre_validation_8', 'populate_tour_dates' );
add_filter( 'gform_pre_submission_filter_8', 'populate_tour_dates' );
//add_filter( 'gform_admin_pre_render_8', 'populate_tour_dates' );

// Gets Tour Information and Populates Available Room Numbers Into Gravity Form Fields
function populate_available_room_numbers( $form ) {
	foreach( $form['fields'] as &$field ) {
		if ( $field->type != 'select' || strpos( $field->cssClass, 'tour-room-number' ) === false ) {
			continue;
		}
				
		global $post;
		$id = $post->ID;
		$tour_available_room_numbers = array();
		// Get ID of current post
		
		if( have_rows('room_information')):
		// Checks for Room Repeater Field
			while( have_rows('room_information') ): the_row();
				if (get_sub_field('room_status') != 'Available'){
					continue;
				}
				$tour_available_room_numbers[] = array( 'text' => get_sub_field('room_number'), 'value' => get_sub_field('room_number') );
			endwhile;
		endif;
		$field->placeholder = "Select A Room Number";
		$field->choices = $tour_available_room_numbers;
	}
	return $form;
}
add_filter( 'gform_pre_render_8', 'populate_available_room_numbers' );
add_filter( 'gform_pre_validation_8', 'populate_available_room_numbers' );
add_filter( 'gform_pre_submission_filter_8', 'populate_available_room_numbers' );
//add_filter( 'gform_admin_pre_render_8', 'populate_available_room_numbers' );

// Add Shortcode
function gfapi_vardump_entry( $atts ) {

	// Attributes
	$atts = shortcode_atts(
		array(
			'entry' => '',
		),
		$atts
	);
	$entry_id = $atts['entry'];
	$entry = GFAPI::get_entry( $entry_id );
	echo '<pre>', var_dump ($entry), '</pre>';

}
add_shortcode( 'gfvardump', 'gfapi_vardump_entry' );
?>
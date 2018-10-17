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
	
	// Add Each Tour Registrant to WooCommerce Cart
	function wt_add_tour_to_cart($product_id, $cart_item_data){
		// Get Woocommerce Global Variable
		global $woocommerce;
		// Add a Unique Key To Product
		$unique_cart_item_key = md5( microtime() . rand() );
		$cart_item_data['unique_key'] = $unique_cart_item_key;
		// Add Product To Cart
		$woocommerce->cart->add_to_cart($product_id, '1', '', '', $cart_item_data);
	}
	
	// Loop Through Nested Entries
	foreach ( $gf_nested_entry_ids as $gf_current_nested_entry_id ){
		
		// Get The Current Nested Entry's Form Data
		$current_nested_entry = GFAPI::get_entry($gf_current_nested_entry_id);
		
		// Get The Name
		$wt_tour_registration_name = rgar( $current_nested_entry, '1.2' ) . ' ' . rgar( $current_nested_entry, '1.3' ) . ' ' . rgar( $current_nested_entry, '1.4' ) . ' ' . rgar( $current_nested_entry, '1.6' ) . ' ' . rgar( $current_nested_entry, '1.8' );
		$wt_tour_registration_name = trim($wt_tour_registration_name);
		
		// Create the Product Title for Cart and Checkout
		$generated_tour_cart_title = 'Tour Deposit For: ' . $tour_registration_title . ' - ' . $wt_tour_registration_name;
		
		if ($payment_option == 'deposit'){
			$deposit = get_field('required_deposit_usd', $post);
			if(function_exists('wt_create_registration_entry')){
				wt_create_registration_entry($tour_registration_title, $wt_tour_registration_name, $deposit);
			}
			
			$cart_item_data = array(
				'tour_deposit'		=> $deposit,
				'tour_cart_title'	=> $generated_tour_cart_title
			);
			
			if(function_exists('wt_add_tour_to_cart')){
				wt_add_tour_to_cart($product_id, $cart_item_data);
			}
		}
		
		elseif ($payment_option == 'custom_deposit'){
			$deposit = rgar( $entry, '26');
			if(function_exists('wt_create_registration_entry')){
				wt_create_registration_entry($tour_registration_title, $wt_tour_registration_name, $deposit);
			}
			$cart_item_data = array(
				'deposit'		=> $deposit,
				'tour_cart_title'	=> $generated_tour_cart_title
			);
			if(function_exists('wt_add_tour_to_cart')){
				wt_add_tour_to_cart($product_id, $cart_item_data);
			}
		}
	}
}

function update_wc_cart_totals($cart_obj){
	foreach( $cart_obj->get_cart() as $key=>$value ) {
		if (isset ($value['tour_deposit'])) {
			$price = $value['tour_deposit'];
			$value['data']->set_price( $price );
		}
	}
}
add_action( 'woocommerce_before_calculate_totals', 'update_wc_cart_totals', 10, 1 );

function update_wc_cart_item_name($cart_object){
	foreach ( $cart_object->get_cart() as $cart_item ) {
		if (isset($cart_item['tour_cart_title'])){
			// Get The New Name
			$new_tour_name = $cart_item['tour_cart_title'];
			// Set the new name (WooCommerce versions 2.5.x to 3+)
			if( method_exists( $cart_item['data'], 'set_name' ) )
				$cart_item['data']->set_name( $new_tour_name );
			else
				$cart_item['data']->post->post_title = $new_tour_name;
		}
    }
}
add_filter( 'woocommerce_before_calculate_totals', 'update_wc_cart_item_name', 10, 1 );

function dump_woocommerce_cart($cart_object) {
    foreach ($cart_object->get_cart() as $cart_item){
		echo '<h1>Start Cart Item</h1>' . '<pre>', var_dump($cart_item), '</pre>';
		$wc_product = $cart_item['data'];
		echo '<h1>Start Data</h1>' . '<pre>', var_dump($wc_product), '</pre>';
	}
}
//add_action('woocommerce_before_calculate_totals', 'dump_woocommerce_cart');

// Remove Links To Products In Cart
add_filter('woocommerce_cart_item_permalink','__return_false');

// Remove Quantity Field From Cart
function wc_remove_quantity_field_from_cart( $return, $product ) {
	if ( is_cart() ) return true;
}
add_filter( 'woocommerce_is_sold_individually', 'wc_remove_quantity_field_from_cart', 10, 2 );

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
		// Add Dropdown place holder text
		$field->placeholder = "Select A Room Number";
		
		// Add manual room option
		$tour_available_room_numbers[] = array( 'text' => 'Reserved Room', 'value' => 'manual_review' );
		
		// Set the field choices to the room number array
		$field->choices = $tour_available_room_numbers;
	}
	return $form;
}
add_filter( 'gform_pre_render_8', 'populate_available_room_numbers' );
add_filter( 'gform_pre_validation_8', 'populate_available_room_numbers' );
add_filter( 'gform_pre_submission_filter_8', 'populate_available_room_numbers' );
//add_filter( 'gform_admin_pre_render_8', 'populate_available_room_numbers' );

// Check is Room Map is enable and pre-populates field in form with result
function populate_room_map_in_form( $value ) {
	$tour_room_map_status = get_field('attach_room_map');
	if ($tour_room_map_status){
		$value = $tour_room_map_status;
	}
	return $value;
}
add_filter( 'gform_field_value_tour_room_map_status', 'populate_room_map_in_form' );

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

// Disable Scrolling on Gravity Forms Confirmations and Page Updates
add_filter( 'gform_confirmation_anchor', '__return_false' );
?>

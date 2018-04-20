<?php
add_action( 'gform_after_submission', 'process_tour_payment', 10, 2 );
function process_tour_payment( $entry, $form) {
    //Check Payment
    function tour_payment_type
        //Registration Only
        //Deposit
        //Installment
        //Full Payment
    $woocommerce->cart->add_to_cart($product_id);
    function calculate_tour_payment($cart_item_data, $product_id){
        $product = wc_get_product( $product_id );
        $price - $product->get_price();
        $cart_item_data['tour_price'] = $price;
        return $price;
    }
    add_filter ('woocommere_add_cart_item_data', 'calculate_tour_payment', 10, 3 );
    function update_wc_cart_totals($cart_obj) {
        foreach( $cart_obj->get_cart() as $key=>$value ) {
            if () {
                $price = $value[];
                $value['data']->set_price( ($price) );
            }
        }
    }
    add_action( 'woocommerce_before_calculate_totals', 'update_wc_cart_totals' 10, 1 );
}
?>
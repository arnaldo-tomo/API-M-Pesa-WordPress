<?php
/*
 * Plugin Name: WooCommerce Mpesa Mz Payment Gateway
 * Plugin URI: http://www.xindiri.com/woocommerce/mpesa-mz-wp-gateway-plugin.html
 * Description: Take mobile payment on your store.
 * Author: Xindiri
 * Author URI: http://www.xindiri.com
 * Version: 1.0.0
 *
 * 
 * /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */

add_filter( 'woocommerce_payment_gateways', 'mpesaMz_add_gateway_class' );
function mpesaMz_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_mpesaMz_Gateway'; // class name
	return $gateways;
}


/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'mpesaMz_init_gateway_class' );
function mpesaMz_init_gateway_class() {
 
	class WC_mpesaMz_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
			$this->id = 'mpesamz'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom number phone form
            $this->method_title = 'mpesa MZ';
            $this->method_description = 'Description of mpesa MZ payment gateway'; // will be displayed on the options page
         
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );
         
            // Method with all the options fields
            $this->init_form_fields();
         
            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->api_key = $this->get_option( 'api_key' );
            $this->public_key = $this->get_option( 'public_key' );
         
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
         
            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
         }
         public function init_form_fields(){
			$this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable mpesaMz Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'mobile number',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay with your mobile number via our super-cool payment gateway.',
                ),
                'public_key' => array(
                    'title'       => 'Public Key',
                    'type'        => 'textarea'
                ),
                'api_key' => array(
                    'title'       => 'Api Key',
                    'type'        => 'text'
                )
            );
    
	
	 	}
 
		/**
		 * You will need it if you want your custom mobile number form, Step 4 is about it
		 */
	public function payment_fields() {
 
	 // ok, let's display some description before the payment form
	 if ( $this->description ) {
		// you can instructions for test mode, I mean test phone numbers etc.
		if ( $this->testmode ) {
			$this->description .= ' TEST MODE ENABLED. <a href="#" target="_blank" rel="noopener noreferrer">documentation</a>.';
			$this->description  = trim( $this->description );
		}
		// display the description with <p> tags etc.
		echo wpautop( wp_kses_post( $this->description ) );
	}
 
	// I will echo() the form, but you can close PHP tags and print it directly in HTML
	echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
 
	// Add this action hook if you want your custom payment gateway to support it
	do_action( 'woocommerce_credit_card_form_start', $this->id );
 
	// I recommend to use inique IDs, because other gateways could already use phone number
	echo '<div class="form-row form-row-wide"><label>Phone Number <span class="required">*</span></label>
		 <input id="mpesaMz" name="mpesaMz" type="text" autocomplete="off" placeholder="84 xxx xxxx">
		</div>

		<div class="clear"></div>';
 
	do_action( 'woocommerce_credit_card_form_end', $this->id );
 
	echo '<div class="clear"></div></fieldset>';
 
 
		}
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom mobile number form
		 */
	 	public function payment_scripts() {
 
		
 
	 	}
 
		/*
 		 * Fields validation, 
		 */
		public function validate_fields() {
 
	      
            if( empty( $_POST[ 'mpesaMz' ]) ) {
                wc_add_notice(  'mobile number is required!', 'error' );
                return false;
            }
            return true;
		}
 
		/*
		 * We're processing the payments here,
		 */
		public function process_payment( $order_id ) {
        
            global $woocommerce;
 
            // we need it to get any order detailes
            $order = wc_get_order( $order_id );
            $order_data = $order->get_data(); // The Order data
            /*
              * Array with parameters for API interaction
             */
          
            $args = array(
         
                "contact" => $_POST['mpesaMz'],
                "amount" =>  $order->order_total,
                "reference"=> "t0258sd",
                "api_key"=>  $this->api_key,
                "public_key"=> $this->public_key,
                
 
         
            );
    



           
            # Create a connection
            $url = 'https://xpayy.herokuapp.com/payment/';
            $ch = curl_init($url);
            # Form data string
            $postString = http_build_query($args, '', '&');
            # Setting our options
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            # Get the response
            $response = curl_exec($ch);
            
          
            curl_close($ch);
            $body = json_decode( $response, true );
          
            echo $body['data']['status_code'];

            // // echo "------------";
            // echo  $response;
            
          


            if( !is_wp_error( $response ) ) {
 
        
                // it could be different depending on your payment processor
                if ( $body['data']['status_code'] == '200' || $body['data']['status_code'] == '201') {
        
                   // we received the payment
                   $order->payment_complete();
                   $order->reduce_order_stock();
        
                   // some notes to customer (replace true with false to make it private)
                   $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
        
                   // Empty cart
                   $woocommerce->cart->empty_cart();
        
                   // Redirect to the thank you page
                   return array(
                       'result' => 'success',
                       'redirect' => $this->get_return_url( $order )
                   );
        
                } else {

                    wc_add_notice(  $body['data']['body']['output_ResponseDesc'], 'error' );
                    return;
               }
        
           } else {
               wc_add_notice(  'Connection error.', 'error' );
               return;
           }



        
 
	 	}
 
		/*
		 * In case you need a webhook
		 */
		public function webhook() {
 
		
	 	}
     }
     

    //  api_context.api_key = '9njrbcqty9ew3cyx4s6k7jvtab134rr6'
    //  api_context.public_key = 'MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAmptSWqV7cGUUJJhUBxsMLonux24u+FoTlrb+4Kgc6092JIszmI1QUoMohaDDXSVueXx6IXwYGsjjWY32HGXj1iQhkALXfObJ4DqXn5h6E8y5/xQYNAyd5bpN5Z8r892B6toGzZQVB7qtebH4apDjmvTi5FGZVjVYxalyyQkj4uQbbRQjgCkubSi45Xl4CGtLqZztsKssWz3mcKncgTnq3DHGYYEYiKq0xIj100LGbnvNz20Sgqmw/cH+Bua4GJsWYLEqf/h/yiMgiBbxFxsnwZl0im5vXDlwKPw+QnO2fscDhxZFAwV06bgG0oEoWm9FnjMsfvwm0rUNYFlZ+TOtCEhmhtFp+Tsx9jPCuOd5h2emGdSKD8A6jtwhNa7oQ8RtLEEqwAn44orENa1ibOkxMiiiFpmmJkwgZPOG/zMCjXIrrhDWTDUOZaPx/lEQoInJoE2i43VN/HTGCCw8dKQAwg0jsEXau5ixD0GUothqvuX3B9taoeoFAIvUPEq35YulprMM7ThdKodSHvhnwKG82dCsodRwY428kg2xM/UjiTENog4B6zzZfPhMxFlOSFX4MnrqkAS+8Jamhy1GgoHkEMrsT5+/ofjCx0HjKbT5NuA2V/lmzgJLl3jIERadLzuTYnKGWxVJcGLkWXlEPYLbiaKzbJb2sYxt+Kt5OxQqC1MCAwEAAQ=='
}
 
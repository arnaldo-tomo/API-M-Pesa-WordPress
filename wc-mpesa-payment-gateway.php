<?php
/*
Plugin Name: Payment Gateway - Mpesa for WooCommerce
Plugin URI: https://wordpress.org/plugins/wc-m-pesa-payment-gateway/
Description: Receive payments directly to your store through the Vodacom Mozambique M-Pesa.
Version: 1.3.5
WC requires at least: 4.0.0
WC tested up to: 6.6.1
Author: TurboHost <suporte@turbohost.co.mz>
Author URI: http://turbohost.co.mz

    Copyright: © 2022 TurboHost <suporte@turbohost.co.mz>.
    License: GNU General Public License v3.0
    License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/
require 'vendor/autoload.php';

use Karson\MpesaPhpSdk\Mpesa;

$wc_mpesa_db_version = "1.3.4";
add_action('plugins_loaded', 'wc_mpesa_init', 0);
add_action('plugins_loaded', 'wc_mpesa_update_check');
register_activation_hook(__FILE__, 'wc_mpesa_install');
/**
 * TODO: Remove custom table functions
 *
 * @return void
 */
function wc_mpesa_install()
{
    global $wc_mpesa_db_version;
    global $wpdb;
    $table_name = $wpdb->prefix . "wc_mpesa_transactions";

    if (!get_option('wc_mpesa_version', $wc_mpesa_db_version)) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        update_option('wc_mpesa_version', $wc_mpesa_db_version);
    }
}



function wc_mpesa_update_check()
{
    global $wc_mpesa_db_version;
    if ($wc_mpesa_db_version != get_option('wc_mpesa_version')) {
        wc_mpesa_install();
    }
}





function wc_mpesa_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    /**
     * Localisation
     */
    load_plugin_textdomain('wc-mpesa-payment-gateway', false, dirname(plugin_basename(__FILE__)) . '/languages');


    /**
     * Gateway class
     */
    class WC_Gateway_MPESA extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id                 = 'wc-mpesa-payment-gateway';
            $this->icon               = apply_filters('wc-mpesa_icon', plugins_url('assets/img/m-pesa-logo.png', __FILE__));
            $this->has_fields         = false;
            $this->method_title       = __('Mpesa for WooCommerce', 'wc-mpesa-payment-gateway');
            $this->method_description = __('Accept Mpesa Payments for WooCommerce', 'wc-mpesa-payment-gateway');

            // Load the settings.
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();

            // Define user set variables
            $this->title         = $this->get_option('title');
            $this->description   = $this->get_option('description');
            $this->api_key = $this->get_option('api_key');
            $this->public_key = $this->get_option('public_key');
            $this->service_provider = $this->get_option('service_provider');
            $this->test = $this->get_option('test');

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'payment_form_html'));
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
            add_action('woocommerce_api_process_action', array($this, 'process_action'));

            /**
             * Set a minimum order amount for checkout
             */
            add_action('woocommerce_checkout_process', 'wc_minimum_order_amount');
            add_action('woocommerce_before_cart', 'wc_minimum_order_amount');
        }





        /**
         * Create form fields for the payment gateway
         *
         * @return void
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-mpesa-payment-gateway'),
                    'type' => 'checkbox',
                    'label' => __('Enable Mpesa payment gateway', 'wc-mpesa-payment-gateway'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Title', 'wc-mpesa-payment-gateway'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout', 'wc-mpesa-payment-gateway'),
                    'default' => __('Mpesa for WooCommerce', 'wc-mpesa-payment-gateway'),
                    'desc_tip'      => true,
                ),
                'description' => array(
                    'title' => __('Customer Message', 'wc-mpesa-payment-gateway'),
                    'type' => 'textarea',
                    'default' => __('Pay via mpesa', 'wc-mpesa-payment-gateway')
                ),
                'api_key' => array(
                    'title' => __('API Key', 'wc-mpesa-payment-gateway'),
                    'type' => 'text',
                    'default' => __('', 'wc-mpesa-payment-gateway')
                ),
                'public_key' => array(
                    'title' => __('Public Key', 'wc-mpesa-payment-gateway'),
                    'type' => 'textarea',
                    'default' => __('', 'wc-mpesa-payment-gateway')
                ),
                'service_provider' => array(
                    'title' => __('Service Provider Code', 'wc-mpesa-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Use 171717 for testing', 'wc-mpesa-payment-gateway'),
                    'default' => 171717
                ),
                'test' => array(
                    'title' => __('Test Mode', 'wc-mpesa-payment-gateway'),
                    'type' => 'checkbox',
                    'label' => __('Enable Test Environment', 'wc-mpesa-payment-gateway'),
                    'default' => 'yes',
                ),

            );
        }





        public function payment_fields()
        {
            session_start();
            // ok, let's display some description before the payment form
            if ($this->description) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ('yes' == $this->test) {
                    $this->description .= __('<br/> TEST MODE ENABLED.', 'wc-mpesa-payment-gateway');
                    $this->description  = trim($this->description);
                }
                // display the description with <p> tags etc.
                echo wpautop(wp_kses_post($this->description));
            }

            if (isset($_SESSION['wc_mpesa_number'])) {
                $number = $this->wc_mpesa_validate_number($_SESSION['wc_mpesa_number']);
            } else {
                $number = '';
            }
            

            // I will echo() the form, but you can close PHP tags and print it directly in HTML
            echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';


            //Use unique IDs, because other gateways could already use
            echo '<div class="form-row form-row-wide"><label>' . esc_html__('Mpesa number', 'wc-mpesa-payment-gateway') . '<span class="required">*</span></label>
                <input name="wc_mpesa_number" type="tel" value="' . esc_attr($number) . '" placeholder="' . esc_attr__('ex: 84 123 4567', 'wc-mpesa-payment-gateway') . '">
                </div>';

            echo '<div class="clear"></div></fieldset>';
        }


        public function validate_fields()
        {
            //validate currency
            if ('MZN' != get_woocommerce_currency()) {
                wc_add_notice(__('Currency not supported!', 'wc-mpesa-payment-gateway'), 'error');
                return false;
            }
            //validate  phone
            $number = $this->wc_mpesa_validate_number($_POST['wc_mpesa_number']);
            if (!$number) {
                wc_add_notice(__('Phone number is required!', 'wc-mpesa-payment-gateway'), 'error');
                return false;
            }

            //save phone to use on payment screen
            session_start();
            $_SESSION['wc_mpesa_number'] = $number;

            return true;
        }

        public function wc_mpesa_validate_number($number)
        {
            $number = filter_var($number, FILTER_VALIDATE_INT);
            //validade mpesa numbers to only accept 84 and 85 prefix ex: 84 8283607
            if (!isset($number) || strlen($number) != 9 || !preg_match('/^8[4|5][0-9]{7}$/', $number)) {
                wc_add_notice(__('Phone number is incorrect!', 'wc-mpesa-payment-gateway'), 'error');
                return false;
            }
            return $number;
        }
        



        public function payment_scripts()
        {
            if (!is_checkout_pay_page()) {
                return;
            }
            if ('no' == $this->enabled) {
                return;
            }
            // Load only on specified pages

            wp_enqueue_script('payment', plugin_dir_url(__FILE__) . '/assets/js/main.js', array(), false, true);

            wp_localize_script('payment', 'payment_text', [
                'status' => [
                    'intro'  => [
                        'title' => __('Payment Information', 'wc-mpesa-payment-gateway'),
                        'description'  => __('<ul><li>Check your details before pressing the button below.</li><li>Your phone number MUST be registered with MPesa (and Active) for this to work.</li><li>You will receive a pop-up on the phone requesting payment confirmation.</li><li>Enter your service PIN (MPesa) to continue.</li><li>You will receive a confirmation message shortly thereafter</li></ul>', 'wc-mpesa-payment-gateway'),
                    ],
                    'requested' => [
                        'title' => __('Payment request sent!', 'wc-mpesa-payment-gateway'),
                        'description' => __('Check your mobile phone and enter your PIN code to confirm payment ...', 'wc-mpesa-payment-gateway')
                    ],
                    'received' => [
                        'title' => __('Payment received!', 'wc-mpesa-payment-gateway'),
                        'description' => __('Your payment has been received and your order will be processed soon.', 'wc-mpesa-payment-gateway')
                    ],
                    'timeout' => [
                        'title' => __('Payment timeout exceeded!', 'wc-mpesa-payment-gateway'),
                        'description' => __('Use your browser\'s back button and try again.', 'wc-mpesa-payment-gateway')
                    ],
                    'failed' => [
                        'title' => __('Payment failed!', 'wc-mpesa-payment-gateway'),
                        'description' => __('Try again or use your browser\'s back button to change the number.', 'wc-mpesa-payment-gateway')
                    ],
                    
                ],
                'buttons' => [
                    'pay' => __('Pay', 'wc-mpesa-payment-gateway'),
                ]
            ]);
            wp_enqueue_style('style', plugin_dir_url(__FILE__) . '/assets/css/style.css', false, false, 'all');
        }

        public function payment_form_html($order_id)
        {
            // modify post object here
            $order = new WC_Order($order_id);
            $return_url = $this->get_return_url($order);
            $data = json_encode(['order_id' => $order_id, 'return_url'=> $return_url]);
            $html_output = "<div class='payment-container' id='app'>
            <div>
              <h4 class='payment-title' v-cloak>{{status.title}}</h4>
              <div v-if='error' class='payment-error' role='error'>{{error}}</div>
              <div class='payment-description' role='alert' v-html='status.description'></div>
            </div>
            <button class='payment-btn' v-bind='{ btnDisabled }' v-on:click='pay($data)'>".__('Pay', 'wc-mpesa-payment-gateway')."</button></div>";
            echo $html_output;
        }






        /**
         * Process the order payment status
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {
            $order = new WC_Order($order_id);

            $checkout_url = $order->get_checkout_payment_url(true);

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $checkout_url
            );
        }




        public function process_action()
        {
            session_start();
            if (isset($_SESSION['wc_mpesa_number'])) {
                $number = $this->wc_mpesa_validate_number($_SESSION['wc_mpesa_number']);
            } else {
                $number = false;
            }

            $response = [];
            
            $mpesa = new Mpesa();

            $mpesa->setPublicKey($this->public_key);
            $mpesa->setApiKey($this->api_key);
            $mpesa->setServiceProviderCode($this->service_provider);
            
            if ($this->test != 'yes') {
                $mpesa->setEnv('live');
            }
            

   
            
            $order = new WC_Order(filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT));
            $order_id = $order->get_id();
            if ($order_id && $number != false) {
                $amount = $order->get_total();
                $reference_id = $this->generate_reference_id($order_id);
                $number = "258${number}";

                $result =  $mpesa->c2b($order_id, $number, $amount, $reference_id);
                $result = $result->response;

                $response['status'] = 'failed';
                $response['message'] = "Ocorreu um erro";
                if ($result->output_ResponseCode == 'INS-0') {
                    // Mark as paid
                    $order->payment_complete();

                    // some notes to customer (replace true with false to make it private)
                    $order->add_order_note('Your order is paid! Thank you!', true);
                    // Remove cart
                    WC()->cart->empty_cart();
                    $response['status'] = 'success';
                } else {
                    // Mark as Failed
                    $response['status'] = 'failed';
                    $message = $this->getResponseMessage($result->output_ResponseCode);
                    if (isset($result->output_error)) {
                        $message = $result->output_error;
                    }
                    $response['status_code'] = $result->output_ResponseCode;
                    $response['message'] = $message;
                    $order->update_status('failed', __('Payment failed', 'wc-mpesa-payment-gateway'));
                }
            }
            $response['debug'] = $this->test;
            wp_send_json($response);
        }

        public function getResponseMessage($error_code)
        {
            switch ($error_code) {
                //show detailed error message
  
                case 'INS-0':
                    $error_message  = 'Requisição executada com sucesso';
                    break;
                case 'INS-1':
                    $error_message  = 'Erro interno';
                    break;
                case 'INS-2':
                    $error_message  = 'Chave da API inválida';
                    break;
                case 'INS-4':
                    $error_message  = 'Usuário não está ativo';
                    break;
                case 'INS-5':
                    $error_message  = 'Transação cancelada pelo cliente';
                    break;
                case 'INS-6':
                    $error_message  = 'Falha na transação';
                    break;
                case 'INS-9':
                    $error_message  = 'Tempo de solicitação esgotado';
                    break;
                case 'INS-10':
                    $error_message  = 'Transação duplicada';
                    break;
                case 'INS-13':
                    $error_message  = 'Short Code inválido';
                    break;
                case 'INS-14':
                    $error_message  = 'Referência inválida';
                    break;
                case 'INS-15':
                    $error_message  = 'Quantidade inválida';
                    break;
                case 'INS-16':
                    $error_message  = 'Incapaz de lidar com a solicitação devido a uma sobrecarga temporária';
                    break;
                case 'INS-17':
                    $error_message  = 'Referência de transação inválida. O comprimento deve estar entre 1 e 20.';
                    break;
                case 'INS-18':
                    $error_message  = 'ID da transação  inválido';
                    break;
                case 'INS-19':
                    $error_message  = 'Referência inválida';
                    break;
                case 'INS-20':
                    $error_message  = 'Nem todos os parâmetros foram  fornecidos. Por favor, tente novamente.';
                    break;
                case 'INS-21':
                    $error_message  = 'As validações de parâmetros falharam. Por favor, tente novamente.';
                    break;
                case 'INS-22':
                    $error_message  = 'Tipo de operação inválido';
                    break;
                case 'INS-23':
                    $error_message  = 'Status desconhecido. Entre em contato com o suporte M-Pesa';
                    break;
                case 'INS-24':
                    $error_message  = 'Iniciador inválido';
                    break;
                case 'INS-25':
                    $error_message  = 'SecurityCredential inválida';
                    break;
                case 'INS-26':
                    $error_message  = 'Não autorizado';
                    break;
                case 'INS-993':
                    $error_message  = 'Débito direto ausente';
                    break;
                case 'INS-994':
                    $error_message  = 'Débito direto já existe';
                    break;
                case 'INS-995':
                    $error_message  = 'O perfil do cliente tem problemas';
                    break;
                case 'INS-996':
                    $error_message  = 'Conta do cliente não ativa';
                    break;
                case 'INS-997':
                    $error_message  = 'Linking Transaction não encontrado';
                    break;
                case 'INS-998':
                    $error_message  = 'Mercado inválido';
                    break;
                case 'INS-2001':
                    $error_message  = 'Erro de autenticação do iniciador';
                    break;
                case 'INS-2002':
                    $error_message  = 'Receptor inválido';
                    break;
                case 'INS-2006':
                    $error_message  = 'Saldo insuficiente';
                    break;
                case 'INS-2051':
                    $error_message  = 'Número Mpesa inválido.';
                    break;
                case 'INS-2057':
                    $error_message  = 'Código do idioma inválido.';
                    break;
                default:
                    $error_message  = 'Erro no pagamento!';
                    break;
        }

            return $error_message;
        }


        public function generate_reference_id($order_id)
        {
            //generate uniq reference_id
            return substr($order_id . bin2hex(random_bytes(5)), 0, 10);
        }

        public function wc_minimum_order_amount()
        {
            // Set this variable to specify a minimum order value
            $minimum = 1;

            if (WC()->cart->total < $minimum) {
                if (is_cart()) {
                    wc_print_notice(
                        sprintf(
                            'Your current order total is %s — you must have an order with a minimum of %s to place your order ',
                            wc_price(WC()->cart->total),
                            wc_price($minimum)
                        ),
                        'error'
                    );
                } else {
                    wc_add_notice(
                        sprintf(
                            'Your current order total is %s — you must have an order with a minimum of %s to place your order',
                            wc_price(WC()->cart->total),
                            wc_price($minimum)
                        ),
                        'error'
                    );
                }
            }
        }
    } //END  WC_Gateway_MPESA


    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_gateway_mpesa_gateway($methods)
    {
        $methods[] = 'WC_Gateway_MPESA';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_mpesa_gateway');
}

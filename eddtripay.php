<?php
/**
 * Plugin Name: EDD - TriPay
 * Plugin URI: https://wordpress.org/plugins/edd-tripay
 * Description: Plugin Payment Gateway TriPay Untuk Easy Digital Download
 * Version: 1.0.0
 * Author: Gudang Wordpress
 * Author URI: https://gudangwordpress.com
 */

// exit if opened directly
if (! defined('ABSPATH')) {
    exit;
}

include( plugin_dir_path( __FILE__ ) . 'includes/checkout-form.php');
include( plugin_dir_path( __FILE__ ) . 'includes/settings.php');

// enqueue scripts and styles.
add_action('wp_enqueue_scripts', 'tripay_enqueue_script');
function tripay_enqueue_script()
{
    // Load Style.
    wp_register_style('edd-tripay', plugins_url('/assets/css/style.css', __FILE__));
    wp_enqueue_style('edd-tripay');
}

// registers currency IDR
function tripay_edd_currencies($currencies)
{
    $currencies['IDR'] = __('Indonesia Rupiah', 'edd-tripay');
    return $currencies;
}
add_filter('edd_currencies', 'tripay_edd_currencies');

// processes the payment
function tripay_edd_process_payment($purchase_data)
{
    global $edd_options;

    // Check whether test or production
    if (edd_is_test_mode()) {
        $url = 'https://payment.tripay.co.id/api-sandbox/transaction/create';
        $merchant_code = edd_get_option('tripay_test_merchant_code');
        $apikey = edd_get_option('tripay_test_api_key');
        $privatekey = edd_get_option('tripay_test_private_key');
    } else {
        $url = 'https://payment.tripay.co.id/api/transaction/create';
        $merchant_code = edd_get_option('tripay_live_merchant_code');
        $apikey = edd_get_option('tripay_live_api_key');
        $privatekey = edd_get_option('tripay_live_private_key');
    }

    // check for any stored errors
    $errors = edd_get_errors();
    if (!$errors) {

        $purchase_summary = edd_get_purchase_summary($purchase_data);

        $price_product = $purchase_data['price'];

        $payment = array(
            'price'  => $price_product,
            'date'  => $purchase_data['date'],
            'user_email'  => $purchase_data['user_email'],
            'purchase_key'  => $purchase_data['purchase_key'],
            'currency'  => $edd_options['currency'],
            'downloads'  => $purchase_data['downloads'],
            'cart_details'  => $purchase_data['cart_details'],
            'user_info'  => $purchase_data['user_info'],
            'status'  => 'pending'
        );

        // record the pending payment
        $payment_id = edd_insert_payment($payment);

        $return_url = add_query_arg( array(
    				'payment-confirmation' => 'tripay',
    				'payment-id' => $payment_id
    			), get_permalink( edd_get_option( 'success_page', false ) ) );

        $expire = edd_get_option('tripay_expire');

        $expiretime = @intval($expire) <= 0 ? 1 : @intval($expire);

        $signature = hash_hmac("sha256", $merchant_code.$payment_id.$price_product, $privatekey);

        $expired_time = (time()+(24*60*60*$expiretime));

        $item_details = [];
        if (is_array($purchase_data['cart_details']) && !empty($purchase_data['cart_details'])) {
            foreach ($purchase_data['cart_details'] as $item) {
                $item_details[] = array(
                    'name' => $item['name'],
                    'price' => intval($item['price'])/intval($item['quantity']),
                    'quantity' => $item['quantity']
                  );
            }
        }

        $params = array(
                    'amount' => $price_product,
                    'method' => $purchase_data['post_data']['tripay-method'],
                    'merchant_ref' => $payment_id,
                    'customer_name' => $purchase_data['user_info']['first_name'],
                    'customer_email' => $purchase_data['user_email'],
                    'customer_phone' => $purchase_data['post_data']['edd_phone'],
                    'expired_time' => $expired_time,
                    'callback_url' => home_url('index.php?edd-listener=tripay'),
                    'return_url' => $return_url,
                    'order_items' => $item_details,
                    'signature' => $signature,
                );

        $headers = array(
                    'Authorization' => "Bearer ".$apikey
                );

        $response = wp_remote_post($url, array(
                    'method' => 'POST',
                    'body' => $params,
                    'timeout' => 90,
                    'headers' => $headers,
                ));

        $body = json_decode(wp_remote_retrieve_body($response));

        if(200 !== wp_remote_retrieve_response_code($response) && 201 !==          wp_remote_retrieve_response_code($response)) {
          edd_debug_log('Tripay : '.$body->message);
          edd_set_error('tripay_error_checkout', $body->message);
          edd_send_back_to_checkout('?payment-mode=tripay');
        }

        if (is_wp_error($response)) {
            edd_set_error($response->get_error_code(), $response->get_error_message());
            edd_send_back_to_checkout('?payment-mode=tripay');
        }

        if ($payment_id) {
            edd_set_payment_transaction_id($payment_id, $body->data->reference);
            edd_insert_payment_note($payment_id, sprintf(__('TriPay Menunggu Pembayaran. Kode Transaksi %s', 'edd-tripay'), $body->data->reference));
            wp_redirect($body->data->checkout_url);
            exit;
        }
    } else {
        edd_send_back_to_checkout('?payment-mode=tripay');
    }
}
add_action('edd_gateway_tripay', 'tripay_edd_process_payment');

// Process TriPay Callback
function edd_listen_for_tripay_notification()
{
    global $edd_options;

    if (edd_is_test_mode()) {
        $privatekey = edd_get_option('tripay_test_private_key');
    } else {
        $privatekey = edd_get_option('tripay_live_private_key');
    }

    // check if payment url http://site.com/?edd-listener=tripay
    if ($_GET['edd-listener'] == 'tripay') {
        // ambil data JSON
        $json = file_get_contents("php://input");

        // get a callback signature
        $callbackSignature = isset($_SERVER['HTTP_X_CALLBACK_SIGNATURE']) ? $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] : '';

        // generate signature to match with X-Callback-Signature
        $signature = hash_hmac('sha256', $json, $privatekey);

        // signature validation
        if ($callbackSignature !== $signature) {
            edd_debug_log('TriPay : Invalid Signature');
            die('TriPay Invalid Signature');
        }

        $data = json_decode($json);
        $event = $_SERVER['HTTP_X_CALLBACK_EVENT'];
        $order_id = $data->merchant_ref;
        if ($event == 'payment_status') {
            if ($data->status == 'PAID') {
                edd_insert_payment_note( $order_id, sprintf(__('Pembayaran TriPay Berhasil. Kode Transaksi %s', 'edd-tripay'), $order_id) );
                edd_update_payment_status($order_id, 'publish');
            }
        }
    }
}
add_action('init', 'edd_listen_for_tripay_notification');

// Process TriPay Redirect
function edd_tripay_success_page_content( $content ) {

	if ( ! isset( $_GET['payment-id'] ) && ! edd_get_purchase_session() ) {
		return $content;
	}

	$payment_id = isset( $_GET['payment-id'] ) ? absint( $_GET['payment-id'] ) : false;

	if ( ! $payment_id ) {
		$session    = edd_get_purchase_session();
		$payment_id = edd_get_purchase_id_by_key( $session['purchase_key'] );
	}

	$payment = new EDD_Payment( $payment_id );

  if( $payment->ID > 0 && 'publish' == $payment->status ){

  edd_empty_cart();

  }

	if ( $payment->ID > 0 && 'pending' == $payment->status  ) {

		ob_start();

		edd_get_template_part( 'payment', 'processing' );

		$content = ob_get_clean();

	}

	return $content;

}
add_filter( 'edd_payment_confirm_tripay', 'edd_tripay_success_page_content' );

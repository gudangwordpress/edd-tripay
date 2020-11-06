<?php

// exit if opened directly
if (! defined('ABSPATH')) {
    exit;
}

// registers the gateway
function edd_register_gateway_tripay($gateways)
{
    $gateways['tripay'] = array('admin_label' => 'TriPay', 'checkout_label' => __('TriPay', 'edd-tripay'));
    return $gateways;
}
add_filter('edd_payment_gateways', 'edd_register_gateway_tripay');

// registers the gateway section
function edd_register_tripay_gateway_section($gateway_sections)
{
    $gateway_sections['tripay'] = __('TriPay', 'edd-tripay');

    return $gateway_sections;
}
add_filter('edd_settings_sections_gateways', 'edd_register_tripay_gateway_section', 1, 1);

// adds the settings to the Payment Gateways section
function tripay_edd_add_settings($gateway_settings)
{
    $sample_gateway_settings = array(
        array(
            'id'  => 'tripay_gateway_settings',
            'name'  => '<strong>' . __('TriPay Settings', 'edd-tripay') . '</strong>',
            'desc'  => __('Configure the gateway settings', 'edd-tripay'),
            'type'  => 'header'
        ),
        array(
            'id'	 => 'tripay_instruction',
            'type'	 => 'descriptive_text',
            'name'	 => __('Callback URL', 'edd-tripay'),
            'desc'	 =>
            '<p>' . sprintf(__('Untuk mode <b>Test</b> lihat <a href="%s" traget="_blank">di sini</a><br>Untuk mode <b>Live</b> lihat <a href="%s" traget="_blank">di sini</a>', 'edd-tripay'), 'https://payment.tripay.co.id/simulator/merchant', 'https://payment.tripay.co.id/member/merchant') . '</p>'
        ),
        array(
            'id'  => 'tripay_live_merchant_code',
            'name'  => __('Live Merchant Code', 'edd-tripay'),
            'desc'  => __('Masukkan Live Merchant Code TriPay Anda', 'edd-tripay'),
            'type'  => 'text',
            'size'  => 'regular'
        ),
        array(
            'id'  => 'tripay_live_api_key',
            'name'  => __('Live API Key', 'edd-tripay'),
            'desc'  => __('Masukkan Live API Key TriPay Anda', 'edd-tripay'),
            'type'  => 'text',
            'size'  => 'regular'
        ),
        array(
            'id'  => 'tripay_live_private_key',
            'name'  => __('Live Private Key', 'edd-tripay'),
            'desc'  => __('Masukkan Live Private Key TriPay Anda', 'edd-tripay'),
            'type'  => 'text',
            'size'  => 'regular'
        ),
        array(
           'name' => __('Masa Berlaku Kode Bayar','edd-tripay'),
           'id'  => 'tripay_expire',
           'type' => 'select',
           'desc' => __('','wc-tripay'),
           'default' => '1',
           'options' => array(
             '1' => __('1 Hari', 'edd-tripay'),
             '2' => __('2 Hari', 'edd-tripay'),
             '3' => __('3 Hari', 'edd-tripay'),
             '4' => __('4 Hari', 'edd-tripay'),
             '5' => __('5 Hari', 'edd-tripay'),
             '6' => __('6 Hari', 'edd-tripay'),
             '7' => __('7 Hari', 'edd-tripay'),
           )
         ),
       array(
           'id'	 => 'tripay_callback_url',
           'type'	 => 'descriptive_text',
           'name'	 => __('Callback URL', 'edd-tripay'),
           'desc'	 =>
           '<p><strong>' . sprintf(__('Callback URL: <code>%s</code>', 'edds'), home_url('index.php?edd-listener=tripay')) . '</strong></p>' .
           '<p>' . sprintf(__('Masukan link diatas ke kolom URL Callback di <a href="%s">di sini</a> lalu klik tombol <b>edit</b> sesuai merchant anda.', 'edd-tripay'), 'https://payment.tripay.co.id/member/merchant') . '</p>'
         ),
        array(
            'id'  => 'tripay_test_merchant_code',
            'name'  => __('Test Merchant Code', 'edd-tripay'),
            'desc'  => __('Enter your test API key, found in your Stripe Account Settins', 'edd-tripay'),
            'type'  => 'text',
            'size'  => 'regular'
        ),
        array(
            'id'  => 'tripay_test_api_key',
            'name'  => __('Test API Key', 'edd-tripay'),
            'desc'  => __('Masukkan Test API Key TriPay Anda', 'edd-tripay'),
            'type'  => 'text',
            'size'  => 'regular'
        ),
        array(
            'id'  => 'tripay_test_private_key',
            'name'  => __('Test Private Key', 'edd-tripay'),
            'desc'  => __('Masukkan Test Private Key TriPay Anda', 'edd-tripay'),
            'type'  => 'text',
            'size'  => 'regular'
        ),
        array(
            'id'  => 'tripay_gateway_settings_payment',
            'name'  => '<strong>' . __('Metode Pembayaran', 'edd-tripay') . '</strong>',
            'type'  => 'header'
        ),
        array(
            'id'  => 'tripay_maybank',
            'name'  => __('Maybank Virtual Account', 'edd-tripay'),
            'desc'  => __('Pembayaran melalui Maybank Virtual Account', 'edd-tripay'),
            'type'  => 'checkbox',
        ),
        array(
            'id'  => 'tripay_permata',
            'name'  => __('Permata Virtual Account', 'edd-tripay'),
            'desc'  => __('Pembayaran melalui Permata Virtual Account', 'edd-tripay'),
            'type'  => 'checkbox',
        ),
        array(
            'id'  => 'tripay_bni',
            'name'  => __('BNI Virtual Account', 'edd-tripay'),
            'desc'  => __('Pembayaran melalui BNI Virtual Account', 'edd-tripay'),
            'type'  => 'checkbox',
        ),
        array(
            'id'  => 'tripay_bri',
            'name'  => __('BRI Virtual Account', 'edd-tripay'),
            'desc'  => __('Pembayaran melalui BRI Virtual Account', 'edd-tripay'),
            'type'  => 'checkbox',
        ),
        array(
            'id'  => 'tripay_mandiri',
            'name'  => __('Mandiri Virtual Account', 'edd-tripay'),
            'desc'  => __('Pembayaran melalui Mandiri Virtual Account', 'edd-tripay'),
            'type'  => 'checkbox',
        ),
        array(
            'id'  => 'tripay_qris',
            'name'  => __('QRIS', 'edd-tripay'),
            'desc'  => __('Pembayaran melalui QRIS', 'edd-tripay'),
            'type'  => 'checkbox',
        ),
        array(
            'id'  => 'tripay_indomaret',
            'name'  => __('Indomaret', 'edd-tripay'),
            'desc'  => __('Pembayaran melalui Indomaret', 'edd-tripay'),
            'type'  => 'checkbox',
        ),
        array(
            'id'  => 'tripay_alfamart',
            'name'  => __('Alfamart', 'edd-tripay'),
            'desc'  => __('Pembayaran melalui Alfamart', 'edd-tripay'),
            'type'  => 'checkbox',
        ),
        array(
            'id'  => 'tripay_alfamidi',
            'name'  => __('Alfamidi', 'edd-tripay'),
            'desc'  => __('Pembayaran melalui Alfamidi', 'edd-tripay'),
            'type'  => 'checkbox',
        ),
    );

    $gateway_settings['tripay'] = $sample_gateway_settings;

    return $gateway_settings;
}
add_filter('edd_settings_gateways', 'tripay_edd_add_settings');

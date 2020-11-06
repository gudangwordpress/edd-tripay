<?php

// exit if opened directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Display payment method field at checkout
 * Add more here if you need to
 */
function edd_tripay_payment_cc_form()
{
  $maybank = edd_get_option('tripay_maybank');
  $permata = edd_get_option('tripay_permata');
  $bni = edd_get_option('tripay_bni');
  $bri = edd_get_option('tripay_bri');
  $mandiri = edd_get_option('tripay_mandiri');
  $qris = edd_get_option('tripay_qris');
  $indomaret = edd_get_option('tripay_indomaret');
  $alfamart = edd_get_option('tripay_alfamart');
  $alfamidi = edd_get_option('tripay_alfamidi');
    ?>
	<ul class="tripay">
    <h3>Metode Pembayaran</h3>
    <?php if($maybank == 1){ ?>
    <li>
      <input type="radio" id="tripaycheck1" name="tripay-method" value="MYBVA" checked/>
      <label for="tripaycheck1"><img src="<?php echo plugin_dir_url(__DIR__); ?>assets/images/maybank-va.png" /></label>
    </li>
  <?php } ?>
  <?php if($permata == 1){ ?>
    <li>
      <input type="radio" id="tripaycheck2" name="tripay-method" value="PERMATAVA" />
      <label for="tripaycheck2"><img src="<?php echo plugin_dir_url(__DIR__); ?>assets/images/permata-va.png" /></label>
    </li>
  <?php } ?>
  <?php if($bni == 1){ ?>
    <li>
      <input type="radio" id="tripaycheck3" name="tripay-method" value="BNIVA" />
      <label for="tripaycheck3"><img src="<?php echo plugin_dir_url(__DIR__); ?>assets/images/bni-va.png" /></label>
    </li>
  <?php } ?>
  <?php if($bri == 1){ ?>
    <li>
      <input type="radio" id="tripaycheck4" name="tripay-method" value="BRIVA" />
      <label for="tripaycheck4"><img src="<?php echo plugin_dir_url(__DIR__); ?>assets/images/bri-va.png" /></label>
    </li>
  <?php } ?>
  <?php if($mandiri == 1){ ?>
    <li>
      <input type="radio" id="tripaycheck5" name="tripay-method" value="MANDIRIVA" />
      <label for="tripaycheck5"><img src="<?php echo plugin_dir_url(__DIR__); ?>assets/images/mandiri-va.png" /></label>
    </li>
  <?php } ?>
  <?php if($indomaret == 1){ ?>
    <li>
      <input type="radio" id="tripaycheck6" name="tripay-method" value="INDOMARET" />
      <label for="tripaycheck6"><img src="<?php echo plugin_dir_url(__DIR__); ?>assets/images/indomaret.png" /></label>
    </li>
  <?php } ?>
  <?php if($qris == 1){ ?>
  <li>
    <input type="radio" id="tripaycheck7" name="tripay-method" value="QRIS" />
    <label for="tripaycheck7"><img src="<?php echo plugin_dir_url(__DIR__); ?>assets/images/qris.png" /></label>
  </li>
<?php } ?>
<?php if($alfamart == 1){ ?>
  <li>
    <input type="radio" id="tripaycheck8" name="tripay-method" value="ALFAMART" />
    <label for="tripaycheck8"><img src="<?php echo plugin_dir_url(__DIR__); ?>assets/images/alfamart.png" /></label>
  </li>
<?php } ?>
<?php if($alfamidi == 1){ ?>
  <li>
    <input type="radio" id="tripaycheck9" name="tripay-method" value="ALFAMIDI" />
    <label for="tripaycheck9"><img src="<?php echo plugin_dir_url(__DIR__); ?>assets/images/alfamidi.png" /></label>
  </li>
<?php } ?>
</ul>
	<?php
}
add_action('edd_tripay_cc_form', 'edd_tripay_payment_cc_form');

/**
 * Display phone number field at checkout
 * Add more here if you need to
 */
function tripay_edd_display_checkout_phonenumber_fields() {
?>
    <p id="edd-phone-wrap">
        <label class="edd-label" for="edd-phone">Nomor Telepon</label>
        <?php /*
        <span class="edd-description">
        	Enter your phone number so we can get in touch with you.
        </span> */ ?>
        <input class="edd-input" type="text" name="edd_phone" id="edd-phone" placeholder="Nomor Telepon" />
    </p>
    <?php
}
add_action( 'edd_purchase_form_user_info_fields', 'tripay_edd_display_checkout_phonenumber_fields' );

/**
 * Make phone number required
 * Add more required fields here if you need to
 */
function tripay_edd_required_checkout_fields( $required_fields ) {
    $required_fields['edd_phone'] = array(
        'error_id' => 'invalid_phone',
        'error_message' => 'Please enter a valid Phone number'
    );

    return $required_fields;
}
add_filter( 'edd_purchase_form_required_fields', 'tripay_edd_required_checkout_fields' );

/**
 * Set error if phone number field is empty
 * You can do additional error checking here if required
 */
function tripay_edd_validate_checkout_fields( $valid_data, $data ) {
    if ( empty( $data['edd_phone'] ) ) {
        edd_set_error( 'invalid_phone', 'Please enter your phone number.' );
    }
}
add_action( 'edd_checkout_error_checks', 'tripay_edd_validate_checkout_fields', 10, 2 );

/**
 * Store the custom field data into EDD's payment meta
 */
function tripay_edd_store_custom_fields( $payment_meta ) {

	if ( 0 !== did_action('edd_pre_process_purchase') ) {
		$payment_meta['phone'] = isset( $_POST['edd_phone'] ) ? sanitize_text_field( $_POST['edd_phone'] ) : '';
	}

	return $payment_meta;
}
add_filter( 'edd_payment_meta', 'tripay_edd_store_custom_fields');


/**
 * Add the phone number to the "View Order Details" page
 */
function tripay_edd_view_order_details( $payment_meta, $user_info ) {
	$phone = isset( $payment_meta['phone'] ) ? $payment_meta['phone'] : 'none';
?>
    <div class="column-container">
    	<div class="column">
    		<strong>Phone: </strong>
    		 <?php echo $phone; ?>
    	</div>
    </div>
<?php
}
add_action( 'edd_payment_personal_details_list', 'tripay_edd_view_order_details', 10, 2 );

/**
 * Add a {phone} tag for use in either the purchase receipt email or admin notification emails
 */
function tripay_edd_add_email_tag() {

	edd_add_email_tag( 'phone', 'Customer\'s phone number', 'sumobi_edd_email_tag_phone' );
}
add_action( 'edd_add_email_tags', 'tripay_edd_add_email_tag' );

/**
 * The {phone} email tag
 */
function tripay_edd_email_tag_phone( $payment_id ) {
	$payment_data = edd_get_payment_meta( $payment_id );
	return $payment_data['phone'];
}

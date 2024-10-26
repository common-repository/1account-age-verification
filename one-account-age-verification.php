<?php
/**
 * @package 1account Age Verification
 * @version 1.1.0
 */
/**
 * Plugin Name: 1account Age Verification
 * Plugin URI: http://wordpress.org/plugins/1account-age-verification/
 * Description: 1account offers real-time validation of your customers’ data via its connection to multiple third party data sources.
 * Version: 1.1.0
 * Author: 1account
 * Author URI: https://1account.net/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oneaccount
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


add_filter( 'redis_object_cache_redis_status', function( $redis_status ) {

    global $wp_query;

    if ( isset( $wp_query ) && is_preview() ) {
    	// This disables the Redis connection usage
    	// when WP query is set and we are on a preview request.
    	// This still enables some basic loading to be done
    	// to Redis before the query is set like option loading.
        return false;
    }

    return $redis_status;
} );


// Check if WooCommerce is active.
$plugin = 'woocommerce/woocommerce.php';
if ( ! in_array( $plugin,
		apply_filters( 'active_plugins',
			get_option( 'active_plugins', [] ) ) ) &&
     ! ( is_multisite() && array_key_exists( $plugin,
		     get_site_option( 'active_sitewide_plugins', [] ) ) )
) {
	return;
}

// Autoload files with composer.
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

use Oneacc\Api\UserController;
use Oneacc\Api\OrderController;

if ( class_exists( 'Oneacc\\Init' ) ) {
	Oneacc\Init::register_services();
}

// Add fake error to prevent order submition vithout validation
function add_fake_error($posted) {
	$user_ctrl = new UserController();
	$user_verified = $user_ctrl->is_user_verified();
	$confirm_flag = intval($_POST['confirm-order-flag']);
	if ($confirm_flag == "1" && !$user_verified ) {
		wc_add_notice( __( "Validation Error Status", 'fake_error' ), 'error');
	}
	$status = sanitize_text_field($_POST['user-status']);
	if ($status && ($status == 'av_success' || $status == 'av_failed')) {
		$user_id = $user_ctrl->get_authorized_user();
		$status = sanitize_text_field($_POST['user-status']);
		$user_ctrl->update_user_status($user_id, $status);
	}
}

add_action('woocommerce_after_checkout_validation', 'add_fake_error');

// Update user and order status if validation popup placed on thank you page
function thankyou_status_update() {
	$status = sanitize_text_field($_POST['user_status']);
	// echo "<prE>"; print_R($_POST);exit;
	$order_id = intval($_POST['order_id']);
	if ($status && ($status == 'av_success')) {
		$user_ctrl = new UserController();
		$user_id = $user_ctrl->get_authorized_user();
		$user_ctrl->update_user_status($user_id, $status);
		echo 'User status updated';
	}
	else
	{
		$user_ctrl = new UserController();
		$user_id = $user_ctrl->get_authorized_user();
		$user_ctrl->update_user_status($user_id, '');
		 echo 'User status updated';
	}
	if ($order_id) {
		$order_ctrl = new OrderController();
		$order_ctrl->update_order_status($order_id);
		echo 'Order status updated';
	}


	$order = wc_get_order( $order_id );
	$user_id = $order->get_user_id();

	$serialized_option = get_option('wc_oneacc_order_status_settings', true);

	if (isset($serialized_option['oneacc_status']) && isset($serialized_option['one_acc_user_status']) && is_array($serialized_option )) {
		$order_statuses = $serialized_option['oneacc_status'];
		$user_statuses = $serialized_option['one_acc_user_status'];

		foreach ($user_statuses as $key => $user_status) {
			$custom_order_status = strtolower(str_replace(' ', '_', $order_statuses[$key]));
			$custom_user_status = strtolower(str_replace(' ', '_', $user_status));

			if ($custom_user_status == $status) {
				echo $custom_order_status;
				echo $custom_user_status;
				$order->update_status($custom_order_status);
			}
		}
	}


	if (empty($user_id)) {

		$order_status =	$order->get_status();
		$serialized_option = get_option('wc_oneacc_order_status_settings', true);

		if (isset($serialized_option['oneacc_status']) && isset($serialized_option['one_acc_user_status']) && is_array($serialized_option )) {
			$order_statuses = $serialized_option['oneacc_status'];
			$user_statuses = $serialized_option['one_acc_user_status'];
			$hasEchoed = false;

			foreach ($user_statuses as $key => $user_status ) {
				$custom_order_status = strtolower(str_replace(' ', '_', $order_statuses[$key]));
				$custom_user_status = strtolower(str_replace(' ', '_', $user_status));  					
				if ($custom_order_status == $order_status ) {
					//if($custom_user_status == 'av_failed'){
						$order->update_status($custom_order_status);
					//}
				}

			}
		}


	}


	wp_die();
}

add_action('wp_ajax_nopriv_thankyou_status_update', 'thankyou_status_update');
add_action('wp_ajax_thankyou_status_update', 'thankyou_status_update');



function check_pending_status() {

	if($_POST['orderId'])
	{
	$orderId  = $_POST['orderId'];
	$status  = $_POST['status'];

	if($status == 'av_failed'){
		$status = '';
	}

	$platformId = $_POST['platformId'];

	$order = wc_get_order($orderId);
	$order_key = wc_get_order($orderId)->get_order_key();

	if($status == 'av_success'){
		$ageverify = 'verify';
	}else{
		$ageverify = 'unverify';
	}

		// Generate the order received page URL
		$order_received_url = get_permalink(get_option('woocommerce_checkout_page_id')) . 'order-received/' . $orderId . '/?key=' . $order_key.'&ageverify='.$ageverify;
		if ($order) {
				$site_uurl = site_url();
				// Get order data
			    $order_data = $order->get_data();
				$order_array = array(
				"userEmail" => $order->get_billing_email(),
				"userPhone" => $order->get_billing_phone(),
				"orderId" => $order_data['id'],
				"items" => array(),
				"avUrl" => $order_received_url,
				"platformId" =>$platformId,
				//"status" =>$status,
				"customerName" => $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'],
				"storeUrl" => $site_uurl.'/shop/' // Assuming this is the URL of your WooCommerce store
				);

			// Get order items
			foreach ($order->get_items() as $item_id => $item) {
				$product = $item->get_product();
				$item_array = array(
					"itemName" => $product->get_name(),
					"itemDescription" => $product->get_short_description()
				);
				$order_array["items"][] = $item_array;
			}

			// Convert to JSON format
			$order_json = json_encode($order_array, JSON_PRETTY_PRINT);
			$curl = curl_init();
			
			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'https://api.1account.net/platforms/woomagento/incompleteAV/create',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			//   CURLOPT_POSTFIELDS =>$order_json,
			CURLOPT_POSTFIELDS => $order_json ,
			  CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			  ),
			));
			
			$response = curl_exec($curl);
			curl_close($curl);
			echo $response;

			wp_die();

		}else{

		return false;
		wp_die();

		}
	

	}else{

	return false;
	wp_die();

	}
}
add_action('wp_ajax_nopriv_check_pending_status', 'check_pending_status');
add_action('wp_ajax_check_pending_status', 'check_pending_status');




function check_update_status() {

	if($_POST['orderId'])
	{
	$orderId  = $_POST['orderId'];
	$status  = $_POST['status'];
	$platformId = $_POST['platformId'];
	$order = wc_get_order($orderId);

	
	// Generate the order received page URL
	
	if ($order) {
		$order_key = wc_get_order($orderId)->get_order_key();
		$order_received_url = get_permalink(get_option('woocommerce_checkout_page_id')) . 'order-received/' . $orderId . '/?key=' . $order_key;
		
		$arr =array(
			"orderId"=> $orderId,
			"status" => $status,
			"platformId" => $platformId );

		$order_json = json_encode($arr, JSON_PRETTY_PRINT);

		$curl = curl_init();
		

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://api.1account.net/platforms/woomagento/incompleteAV/update',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_POSTFIELDS =>$order_json,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
		));

		$response = curl_exec($curl);
		echo   $response;  

		wp_die();
		

	}
	else
	{
	return false;
	wp_die();
	}
		
		// 
	}
	else
	{
	return false;
	wp_die();
	}
	}
	add_action('wp_ajax_nopriv_check_update_status', 'check_update_status');
	add_action('wp_ajax_check_update_status', 'check_update_status');


	//  After 72 Hours Update User AV Status Faild Or What ?
	function schedule_custom_event() {
		if ( ! wp_next_scheduled( 'custom_event_hook' ) ) {
			// Calculate the timestamp for the next occurrence (1 minute from now)
			$now = current_time( 'timestamp' );
			$next_event_time = $now + 60; // 60 seconds is 1 minute
	
			// Schedule the event at the calculated timestamp
			wp_schedule_single_event( $next_event_time, 'custom_event_hook' );
		}
	}
	add_action( 'wp', 'schedule_custom_event' );
	
	// Hook into this action to perform the task
	add_action( 'custom_event_hook', 'update_user_meta_after_72_hours' );
	function update_user_meta_after_72_hours() {
		if (is_user_logged_in()) {
			$user = wp_get_current_user();
			$last_order = wc_get_customer_last_order($user->ID);
	
			if ($last_order) {
				$last_order_date = strtotime($last_order->get_date_created()->date('Y-m-d H:i:s'));
				  $new_time = $last_order_date + (72 * 3600); // Adding 72 hours in seconds
				//  $new_time = $last_order_date + (5 * 60);
	
				if (current_time('timestamp') >= $new_time) {
					// Perform your desired action here
					// For example, update user meta
					// update_user_meta($user->ID, 'last_order_plus_72', date('Y-m-d H:i:s', $new_time));
					$st = 'av_failed';

					$av_status_check  = get_user_meta($user->ID, 'one_acc_woo_av_status', true);
					if($av_status_check  != 'av_success')
					{
						update_user_meta($user->ID,'one_acc_woo_av_status',$st);
					}
				}
			}
		}
	}

	function add_custom_meta_field_to_order($order){

		$value = get_post_meta($order->get_id(), 'av_order_tag_key', true);
	
		echo '<p class="form-field form-field-wide wc-order-status"><strong>AV Order Status:</strong><br>';
		// echo '<input type="text" name="av_order_tag_key" value="' . esc_attr($value) . '">';

		echo '<select name="av_order_tag_key">';
		echo '<option value="">AV_Pending</option>';
		echo '<option value="AV_Pass" ' . selected( $value, 'AV_Pass', false ) . '>AV_Pass</option>';
		echo '<option value="AV_Fail" ' . selected( $value, 'AV_Fail', false ) . '>AV_Fail</option>';
		echo '<option value="NO_UK_USER" ' . selected( $value, 'NO_UK_USER', false ) . '>NO_UK_USER</option>';
		// Add more options as needed
		echo '</select></p>';


		echo '</p>';

	}
	add_action('woocommerce_admin_order_data_after_order_details', 'add_custom_meta_field_to_order', 10, 1);


function save_custom_meta_field_data($order_id){

	$custom_meta_value = isset($_POST['av_order_tag_key']) ? sanitize_text_field($_POST['av_order_tag_key']) : '';
	
	update_post_meta($order_id, 'av_order_tag_key', $custom_meta_value);
}
add_action('woocommerce_process_shop_order_meta', 'save_custom_meta_field_data', 10, 1);
	





add_action( 'woocommerce_thankyou', 'account_age_verify_checkout_fields_after_order' );
 
function account_age_verify_checkout_fields_after_order( $order_id ) {

   $order = wc_get_order( $order_id );
   $billing_country = $order->get_billing_country();
	$user_id = $order->get_user_id();
	$user_order_status = get_user_meta($user_id, 'one_acc_woo_av_status', true);

	if ( $billing_country != 'GB' ) {

   		update_post_meta($order_id, 'av_order_tag_key', 'NO_UK_USER');
   		update_user_meta($user_id,'one_acc_woo_av_status', 'no_uk_user');



   		if(empty($user_id)){

   			$order_status =	$order->get_status();
			$serialized_option = get_option('wc_oneacc_order_status_settings', true);

			if (isset($serialized_option['oneacc_status']) && isset($serialized_option['one_acc_user_status']) && is_array($serialized_option )) {
				$order_statuses = $serialized_option['oneacc_status'];
				$user_statuses = $serialized_option['one_acc_user_status'];
				$hasEchoed = false;

				foreach ($user_statuses as $key => $user_status ) {
					$custom_order_status = strtolower(str_replace(' ', '_', $order_statuses[$key]));
					$custom_user_status = strtolower(str_replace(' ', '_', $user_status));  
		
					if ($custom_user_status == 'no_uk_user' ) {
							$order->update_status($custom_order_status);
					}

				}
			}

   		}

   }

   	if($user_id){

	   	$updated_user_order_status = get_user_meta($user_id, 'one_acc_woo_av_status', true);
	   	$serialized_option = get_option('wc_oneacc_order_status_settings', true);

			if (isset($serialized_option['oneacc_status']) && isset($serialized_option['one_acc_user_status']) && is_array($serialized_option )) {
			    $order_statuses = $serialized_option['oneacc_status'];
			    $user_statuses = $serialized_option['one_acc_user_status'];

			    foreach ($user_statuses as $key => $user_status) {
			        $custom_order_status = strtolower(str_replace(' ', '_', $order_statuses[$key]));
			        $custom_user_status = strtolower(str_replace(' ', '_', $user_status));

			        // echo $custom_user_status;
			        // echo $updated_user_order_status;

			        if ($custom_user_status == $updated_user_order_status) {
			            $order->update_status($custom_order_status);
			        }
			    }
			}
	}

		update_post_meta($order_id, 'av_popups', 'open');

   
}


function register_wait_call_order_status() {

	$serialized_option = get_option('wc_oneacc_order_status_settings', true);

	foreach ($serialized_option as $key => $value) {
		$counter=0;
		foreach($value as $data){

			if($key == 'oneacc_status'){

				$wc_slug = strtolower(str_replace(' ', '_', $serialized_option['oneacc_status'][$counter]));
				$wc_slug = 'wc-'.$wc_slug;

				register_post_status( $wc_slug, array(
				'label'                     => 'AV Pass',
				'public'                    => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				'exclude_from_search'       => false,
				'label_count'               => _n_noop( $serialized_option['oneacc_status'][$counter] . ' (%s)', $serialized_option['oneacc_status'][$counter] . ' (%s)' )
				) );
			}

		$counter++;	

		}

	}


}


function add_wait_call_to_order_statuses( $order_statuses ) {

	$new_order_statuses = array();
		foreach ( $order_statuses as $key => $status ) {

			$new_order_statuses[ $key ] = $status;

			$serialized_option = get_option('wc_oneacc_order_status_settings', true);

			if ( 'wc-on-hold' === $key  && is_array($serialized_option)) {

				foreach ($serialized_option as $keys => $value) {
				$counter=0;
				foreach($value as $data){

					if($keys == 'oneacc_status'){

						$wc_slug = strtolower(str_replace(' ', '_', $serialized_option['oneacc_status'][$counter]));
						$wc_slug = 'wc-'.$wc_slug;
						$new_order_statuses[$wc_slug] = $serialized_option['oneacc_status'][$counter];
					}

				$counter++;	

				}

			}

			}

}
return $new_order_statuses;
}

add_action( 'init', 'register_wait_call_order_status' );
add_filter( 'wc_order_statuses', 'add_wait_call_to_order_statuses' );


function add_custom_attributes_to_script($tag, $handle) {
    // Check if the script handle matches 'one-account-push-api'
    if ($handle === 'one-account-push-api') {
        // Add custom attributes to the script tag
        $tag = str_replace('<script', '<script data-ecommerce="1"', $tag);
    }
    return $tag;
}

// Hook the filter function to the 'script_loader_tag' filter
add_filter('script_loader_tag', 'add_custom_attributes_to_script', 10, 2);
 
add_action('woocommerce_review_order_before_submit', 'add_hidden_input_to_checkout');

function add_hidden_input_to_checkout() {

	$user_id = get_current_user_id();

	if($user_id){
		$current_status = get_user_meta($user_id, 'one_acc_woo_av_status', true);
		echo '<input type="hidden" name="current_status" id="current_status" value="' . esc_attr($current_status) . '" />';
	}
  
}


function get_last_order_id_on_checkouts() {
    global $wpdb;
    $last_order_id = $wpdb->get_var("SELECT MAX(ID) FROM {$wpdb->prefix}posts");
    $last_order_id = $last_order_id + 1;
    echo '<input type="hidden" name="last_order_id" class="last_order_id" id="last_order_id" value="'.$last_order_id.'" >';
}

add_action('woocommerce_before_checkout_form', 'get_last_order_id_on_checkouts');


add_action( 'profile_update', 'account_age_user_profile_update', 10, 2 );
function account_age_user_profile_update( $user_id ) {
      	
	if($user_id){

		$orders = wc_get_orders(array(
			'customer' => $user_id,
			'limit' => 1,
			'orderby' => 'date',
			'order' => 'DESC',
		));

		foreach ($orders as $order) {

			$updated_user_order_status = get_user_meta($user_id, 'one_acc_woo_av_status', true);
			$serialized_option = get_option('wc_oneacc_order_status_settings', true);

			if (isset($serialized_option['oneacc_status']) && isset($serialized_option['one_acc_user_status']) && is_array($serialized_option )) {
				$order_statuses = $serialized_option['oneacc_status'];
				$user_statuses = $serialized_option['one_acc_user_status'];

				foreach ($user_statuses as $key => $user_status) {
					$custom_order_status = strtolower(str_replace(' ', '_', $order_statuses[$key]));
					$custom_user_status = strtolower(str_replace(' ', '_', $user_status));

					if ($custom_user_status == $updated_user_order_status) {
						$order->update_status($custom_order_status);
					}
				}
			}
		}
	}

}
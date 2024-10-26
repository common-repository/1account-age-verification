<?php
/**
 * @package 1account for WooCommerce
 */

namespace Oneacc\Api;


use Oneacc\Base\BaseVarsController;
use Oneacc\Api\UserController;

class OrderController extends BaseVarsController {

	public function register() {
		add_action( 'woocommerce_thankyou', [ $this, 'check_validation_place' ] );
	}

	// Depending on popup_placement field create pass order data or update order status
	public function check_validation_place($order_id) {
		$order = wc_get_order($order_id);
		$validation = new UserController();
		$user_verified = $validation->is_user_verified();

		// if (!$user_verified) {
			?>
			<script type="text/javascript">
              const order = {
                forename: '<?php echo $order->get_billing_first_name() ?>',
                surname: '<?php echo $order->get_billing_last_name() ?>',
                email: '<?php echo $order->get_billing_email() ?>',
                msisdn: '<?php echo $order->get_billing_phone() ?>',
                building: '<?php echo $order->get_billing_address_2() ?>',
                street: '<?php echo $order->get_billing_address_1() ?>',
                city: '<?php echo $order->get_billing_city() ?>',
                postCode: '<?php echo $order->get_billing_postcode() ?>',
                country: '<?php echo $order->get_billing_country() ?>',
                id: '<?php echo $order_id ?>'
              };
			</script>
			<?php
		// } 
		if($user_verified){
			$this->update_order_status($order_id);
		}

		$av_popups = get_post_meta($order_id, 'av_popups', true);
	  echo '<input type="hidden" name="thank_popup" class="thank_popup" id="thank_popup" value="'.$av_popups.'" > ';
	  echo '<input type="hidden" name="order_id" class="order_id" id="order_id" value="'.$order_id.'" > ';
	  echo '<input type="hidden" name="order_id" class="order_id" id="order_id" value="'.$order_id.'" > ';

	  $user_id = $order->get_user_id();
	  
	  if($user_id){
			$current_status = get_user_meta($user_id, 'one_acc_woo_av_status', true);
			echo '<input type="hidden" name="current_status" id="current_status" value="' . esc_attr($current_status) . '" />';
		}

	  if($_GET['ageverify'] == 'unverify'){

	  		$user_id = $order->get_user_id();
	  		$order_key = wc_get_order($order_id)->get_order_key();

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

	  						echo 	$custom_user_status;

	  						if($custom_user_status == 'av_success'){
			  						$order_received_url = get_permalink(get_option('woocommerce_checkout_page_id')) . 'order-received/' . $order_id . '/?key=' . $order_key.'&ageverify=verify';
										wp_redirect( $order_received_url );
								}

	  					}
	  					// $hasEchoed = true;

	  				}
	  			}


	  		}


				$user_status = get_user_meta(get_current_user_id(), 'one_acc_woo_av_status', true);

				if($user_status == 'av_success'){
						$order_key = wc_get_order($order_id)->get_order_key();
						$order_received_url = get_permalink(get_option('woocommerce_checkout_page_id')) . 'order-received/' . $order_id . '/?key=' . $order_key.'&ageverify=verify';
						wp_redirect( $order_received_url );

				}
	  }



	}

	// Update and save order status meta
    public function update_order_status($order_id) {
	    $order = wc_get_order($order_id);
	    $validation = new UserController();
	    $user_verified = $validation->is_user_verified();
	    //  $order->add_meta_data($this->av_status_key, $user_verified ? $this->status_success : $this->status_failed);

	    $user_id = $order->get_user_id();
	    $billing_country = $order->get_billing_country();

	    if ($billing_country == 'GB' ) {

				if($user_verified)
				{
						update_post_meta($order_id, 'av_order_tag_key', 'AV_Pass');
				}
				else
				{
					update_post_meta($order_id, 'av_order_tag_key', 'AV_Fail');
				}

			}else{
				
				update_post_meta($order_id, 'av_order_tag_key', 'NO_UK_USER');
				//update_user_meta($user_id,'one_acc_woo_av_status', 'NO_UK_USER');

			}




	     $order->save_meta_data();
    }
}

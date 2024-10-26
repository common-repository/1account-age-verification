<?php
/**
 * @package 1account for WooCommerce
 */

namespace Oneacc\Base;


class Enqueue extends BaseVarsController {

	public function register() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue' ] );
		add_filter('script_loader_tag', [ $this, 'custom_script_loader_tag'], 10, 2);	

		//  add_action('wp_enqueue_scripts', [ $this, 'custom_enqueue_scripts'] );
	}

	function enqueue() {

		 if (is_checkout() || is_order_received_page()) {

			// Enqueue all scripts
			wp_enqueue_script( 'one-account-push-api',
				$this->push_api_script_url,
				[],
				false,
				true );
		  wp_script_add_data('one-account-push-api', 'attributes', array('data-ecommerce' => '1'));
		 
			wp_enqueue_script( 'oneacc-script',
				"{$this->plugin_url}assets/one-account.js",
				[ 'jquery' ],
				filemtime( "{$this->plugin_path}assets/one-account.js" ),
				true );
				wp_localize_script('oneacc-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
			wp_localize_script( 'oneacc-script',
				'one_acc_woo_params',
				[
					'ajax_url'                 => admin_url( 'admin-ajax.php' ),
					'update_user_status_nonce' => wp_create_nonce( 'update_user_status' ),
					'check_user_status_nonce'  => wp_create_nonce( 'check_user_status' ),
					'update_order_meta_nonce'  => wp_create_nonce( 'update_order_meta' ),
					'is_user_logged_in'        => is_user_logged_in() ? 'true' : 'false',
					'current_page'             => is_order_received_page() ? 'order_received' : ( is_checkout() ? 'checkout' : null ),
					'auth_code'                => wp_create_nonce( 'auth_code' ),
					'client_id'                => $this->client_id,
					'client_secret'            => $this->client_secret,
					'scope'                    => $this->scope,
					'popup_placement'          => $this->popup_placement,
				] );
		}


	}




	function admin_enqueue () {

		wp_enqueue_style( 'oneacc-scriptss', "{$this->plugin_url}assets/one-account.css", filemtime( "{$this->plugin_path}assets/one-account.css" ), true );
		wp_enqueue_script( 'oneacc-admins', "{$this->plugin_url}assets/one-admin-account.js", [ 'jquery' ],	filemtime( "{$this->plugin_path}assets/one-admin-account.js" ), true );
		wp_localize_script('oneacc-admins', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
		
	}

	// Replace ID oneacc-script-js removed -js text
	function custom_script_loader_tag($tag, $handle) {
	 	return str_replace("oneacc-script-js", "oneacc-script", $tag);
	}

}

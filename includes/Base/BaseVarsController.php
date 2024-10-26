<?php
/**
 * @package 1account for WooCommerce
 */

namespace Oneacc\Base;

class BaseVarsController {

	public $plugin_path;

	public $plugin_url;

	public $plugin;

	public $plugin_text_domain;

	public $push_api_script_url;

	public $av_status_key;

	public $status_success;

	public $status_failed;

	public $client_id;

	public $client_secret;

	public $scope;

	public $popup_placement;

	public function __construct() {
		$this->plugin_path         = plugin_dir_path( dirname( __FILE__, 2 ) );
		$this->plugin_url          = plugin_dir_url( dirname( __FILE__, 2 ) );
		$this->plugin              = plugin_basename( dirname( __FILE__,
				3 ) ) . '/one-account-age-verification.php';
		$this->plugin_text_domain  = 'oneacc-woo';
		$this->push_api_script_url = 'https://www.1account.net/pushApi/index.js';
		$this->status_success      = 'av_success';
		$this->status_failed       = 'av_failed';
		$this->av_status_key       = 'one_acc_woo_av_status';
		$this->client_id           = get_option( 'wc_oneacc_settings_client_id' );
		$this->client_secret       = get_option( 'wc_oneacc_settings_client_secret' );
		$this->scope               = get_option( 'wc_oneacc_settings_av_level' );
		$this->popup_placement     = get_option( 'wc_oneacc_settings_popup_placement' );
	}
}

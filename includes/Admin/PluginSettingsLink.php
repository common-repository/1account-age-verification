<?php
/**
 * @package 1account for WooCommerce
 */

namespace Oneacc\Admin;


use Oneacc\Base\BaseVarsController;

class PluginSettingsLink extends BaseVarsController {

	public function register() {
		add_filter( "plugin_action_links_$this->plugin",
			[ $this, 'settings_links' ] );
	}

	public function settings_links( $links ) {
		$settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=oneacc_settings') . '">' . __( 'Settings', $this->plugin_text_domain ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}


}
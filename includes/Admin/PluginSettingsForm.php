<?php
/**
 * @package 1account for WooCommerce
 */

namespace Oneacc\Admin;


use Oneacc\Base\BaseVarsController;

class PluginSettingsForm extends BaseVarsController {

	public function register() {
		add_filter( 'woocommerce_settings_tabs_array',
			[ $this, 'create_settings_tab' ],
			50 );

		add_filter( 'woocommerce_settings_tabs_array',
			[ $this, 'order_status_tab' ],
			50 );

		add_action( 'woocommerce_settings_tabs_oneacc_settings',
			[ $this, 'init_settings_form' ] );
		add_action( 'woocommerce_update_options_oneacc_settings',
			[ $this, 'update_settings_form' ] );

		add_action( 'woocommerce_settings_tabs_oneacc_order_status_settings',
			[ $this, 'order_status_settings_form' ] );

		add_action('wp_ajax_save_order_status_settings', [ $this, 'save_order_status_settings'] );
		add_action('wp_ajax_nopriv_save_order_status_settings', [ $this, 'save_order_status_settings'] );
	}

	// Create plugin settings tab under WooCommerce settings page.
	public function create_settings_tab( $settings_tabs ) {
		$settings_tabs['oneacc_settings'] = __( '1account Age Validation',
			$this->plugin_text_domain );

		return $settings_tabs;
	}

	public function order_status_tab( $order_status ) {
		$order_status['oneacc_order_status_settings'] = __( '1account Age Validation Order Status',
			$this->plugin_text_domain );

		return $order_status;
	}


	public function order_status_settings_form() {

		echo '<h2> Order Status settings </h2>';	
		$serialized_option = get_option('wc_oneacc_order_status_settings', true);

		$html = '';
		$html .= '<span class="title_field1" > Order Status </span>';
		$serialized_option = get_option('wc_oneacc_order_status_settings', true);

			if(isset($serialized_option) && is_array($serialized_option))
			{
				foreach ($serialized_option as $key => $value) {

				$counter=0;	
				foreach($value as $data){
					if($key == 'oneacc_status'){
				
						$html .='<div id="inputFormInput">';
						$html .='<div class="form-group input-group">
						<input name="oneacc_status[]" id="oneacc_status" type="text" value="'.$serialized_option['oneacc_status'][$counter].'" class="field1 oneacc_status" placeholder="" autocomplete="off">  
						<select name="one_acc_user_status[]" id="one_acc_user_status" class="one_acc_user_status">
							<option value=""></option>';

							$html .='<option value="av_success" ' . selected( $serialized_option['one_acc_user_status'][$counter], 'av_success', false ) . ' >AV Success </option>';
							$html .='<option value="av_failed" ' . selected( $serialized_option['one_acc_user_status'][$counter], 'av_failed', false ) . '>AV Failed</option>';
							$html .='<option value="no_uk_user" ' . selected( $serialized_option['one_acc_user_status'][$counter], 'no_uk_user', false ) . '>NO_UK_USER</option>';

							$html .='</select>                             
						<button id="removeInput" type="button" class="btn btn-danger btn-sm">Remove</button>
						</div>';
						$html .='</div>';
					}
					$counter++;
				}

				}
			}
	    
        $html .= '<div id="newInput"></div>
            <button id="addInput" type="button" class="btn btn-info btn-sm mb-4">Add Row</button> ';
        $html .= '<a class="button-primary quiz_bb_members_fields_settings"  >Save Settings</a>';



        echo $html;

            ?>	

            <script type="text/javascript">
            	
            jQuery(document).on('click', '#addInput', function () {
	            var html = '';
	            html += '<div id="inputFormInput">';
	            html += '<div class="form-group input-group">';
	            html += '<input name="oneacc_status[]" id="oneacc_status" type="text" style="" value="" class="field1 oneacc_status" placeholder="" autocomplete="off">';
	            html += '<select name="one_acc_user_status[]" id="one_acc_user_status" class="one_acc_user_status">';
					html += '<option value=""></option>';
					html += '<option value="av_success">AV Success </option>';
					html += '<option value="av_failed" >AV Failed</option>';
					html += '<option value="no_uk_user">NO_UK_USER </option>';
				html += '</select>'
	            html += '<button id="removeInput" type="button" class="btn btn-danger btn-sm">Remove</button>';
	            html += '</div>';
	            html += '</div>';
            	jQuery('#newInput').append(html);
        	});

            // remove row
	        jQuery(document).on('click', '#removeInput', function () {
	            jQuery(this).closest('#inputFormInput').remove();
	        });


			var avajaxs = {
		            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>'
		    };

		    jQuery(document).on('click', '.quiz_bb_members_fields_settings', function () {

		    	var field1Values = [];
		    	jQuery('input[name="oneacc_status[]"]').each(function() {
			            field1Values.push(jQuery(this).val()); // Push the value into the array
			        });

		    	var field2Values = [];
		    	jQuery('.one_acc_user_status').find('option:selected').each(function(){
		    		field2Values.push(jQuery(this).val());
		    	});

		    	var data = {
		    		action: 'save_order_status_settings',
		    		oneacc_status: field1Values,
		    		one_acc_user_status: field2Values,
		    	};

		    	jQuery.ajax({
		    		type: 'POST',
		    		url: avajaxs.ajaxurl,
		    		data: data,

		    		success: function(response) {
		    			location.reload(true);
		    			console.log(response);	               
		    		},
		    		error: function() {

		    		}
		    	});
		    });

            </script>

            <style type="text/css">
            	button.button-primary.woocommerce-save-button {  display: none; }
            </style>
            
            <?php	
	}


	public function save_order_status_settings() {
	        
	    	$field1_value = isset($_POST['oneacc_status']) ? array_map('sanitize_text_field', $_POST['oneacc_status']) : array();
	    	$field2_value = isset($_POST['one_acc_user_status']) ? array_map('sanitize_text_field', $_POST['one_acc_user_status']) : array();

		     $data = array(
		        'oneacc_status' => $field1_value,
		        'one_acc_user_status' => $field2_value,
		    );

		    update_option('wc_oneacc_order_status_settings', $data);
		    wp_send_json_success($data);

	    wp_die();
	}


	// Init the form for the plugin settings.
	public function init_settings_form() {
		woocommerce_admin_fields( $this->get_plugin_settings() );
	}

	// Save the form values for the plugin settings.
	public function update_settings_form() {
		woocommerce_update_options( $this->get_plugin_settings() );
	}

	// Create the form for the plugin settings.
	public function get_plugin_settings() {

		$serialized_option = get_option('wc_oneacc_order_status_settings', true);
		if (isset($serialized_option['oneacc_status']) && isset($serialized_option['one_acc_user_status'])) {
			    $option_values = $serialized_option['oneacc_status'];
			}

		$settings = [
			'api_section_title' => [
				'name' => __( 'API credentials', $this->plugin_text_domain ),
				'type' => 'title',
				'desc' => __( 'Enter your 1account API credentials to validate customers age via 1Account. Login to your <a href="https://www.1account.net" target="_blank" title="1account">1account</a> business user account and navigate to the Developer Portal to access your project\'s ID and Secret.',
					$this->plugin_text_domain ),
				'id'   => 'wc_oneacc_settings_section_title',
			],
			'client_id'         => [
				'name'     => __( 'Project ID', $this->plugin_text_domain ),
				'type'     => 'text',
				'desc'     => __( '' ),
				'desc_tip' => __( 'Get your API credentials from 1Account.',
					$this->plugin_text_domain ),
				'id'       => 'wc_oneacc_settings_client_id',
			],
			'client_secret'     => [
				'name'     => __( 'Project Secret', $this->plugin_text_domain ),
				'type'     => 'text',
				'desc'     => __( '' ),
				'desc_tip' => __( 'Get your API credentials from 1Account.',
					$this->plugin_text_domain ),
				'id'       => 'wc_oneacc_settings_client_secret',
			],
			[
				'type' => 'sectionend',
				'id'   => 'wc_oneacc_settings_section_title',
			],

			'plugin_settings_title' => [
				'name' => __( 'Plugin settings', $this->plugin_text_domain ),
				'type' => 'title',
				'desc' => __( 'Set up the plugin\'s behaviour so it meets your needs.',
					$this->plugin_text_domain ),
				'id'   => 'wc_oneacc_settings_plugin_settings',
			],
			[
				'name'     => __( 'Popup placement',
					$this->plugin_text_domain ),
				'type'     => 'select',
				'id'       => 'wc_oneacc_settings_popup_placement',
				'options'  => [
					'on_place_order'    => __( "On 'Place Order' event (Checkout page)",
						$this->plugin_text_domain ),
					'after_place_order' => __( "After 'Place Order' event (Thank you page)",
						$this->plugin_text_domain ),
				],
				'class'    => 'wc-enhanced-select',
				'desc_tip' => __( "'Checkout page' selected by default.",
					$this->plugin_text_domain ),
				'default'  => 'on_place_order',
			],
			
			'scope'                 => [
				'name' => __( 'AV Level', $this->plugin_text_domain ),
				'type' => 'text',
				'desc' => __( '' ),
				'id'   => 'wc_oneacc_settings_av_level',
			],
			// [
			// 	'name'     => __( 'Custom Order Status',
			// 		$this->plugin_text_domain ),
			// 	'type'     => 'select',
			// 	'id'       => 'wc_oneacc_settings_custom_order_status',
			// 	'options'  => [
			// 		// 'AV_success'    => __( "AV Succes",
			// 		// 	$this->plugin_text_domain ),
			// 		'AV Failed' => __( "AV Failed",
			// 			$this->plugin_text_domain ),
			// 		'AV Pending' => __( "AV Pending",
			// 			$this->plugin_text_domain ),	
			// 	],
			// 	'class'    => 'wc-enhanced-select',
			// 	'desc_tip' => __( "Selected by default.",
			// 		$this->plugin_text_domain ),
			// 	'default'  => 'AV_pending',
			// ],
			[
				'type' => 'sectionend',
				'id'   => 'wc_oneacc_settings_plugin_settings',
			],
		];


		
		return apply_filters( 'wc_oneacc_settings_settings', $settings );

	}

	public static function get_form_data() {

		return [
			'client_id'       => get_option( 'wc_oneacc_settings_client_id' ),
			'client_secret'   => get_option( 'wc_oneacc_settings_client_secret' ),
			'scope'           => get_option( 'wc_oneacc_settings_av_level' ),
			'popup_placement' => get_option( 'wc_oneacc_settings_popup_placement' ),
		];
	}
}

<?php
/**
 * @package 1account for WooCommerce
 */

namespace Oneacc\User;


use Oneacc\Base\BaseVarsController;

class UserSettings extends BaseVarsController {

	public function register() {
		add_action( 'show_user_profile', [ $this, 'show_user_profile_field' ] );
		add_action( 'edit_user_profile', [ $this, 'show_user_profile_field' ] );
		add_action( 'personal_options_update', [ $this, 'save_custom_profile_field' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_custom_profile_field' ] );
		// add_action('send_email_on_pending',  [ $this, 'send_user_mail' ]);
	}

	// Add user status field to user settings
	public function show_user_profile_field( $user ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		} ?>
        <h2><?php _e( '1account Age Verification Options', $this->plugin_text_domain ); ?></h2>
        <table class="form-table">
            <tr>
                <th>
                    <label for="<?php echo esc_attr($this->av_status_key) ?>"><?php _e( 'Age verification status', $this->plugin_text_domain ) ?></label>
                </th>
                <td>
                    <select name="<?php echo esc_attr($this->av_status_key) ?>"
                            id="<?php $this->av_status_key ?>" >
                        <option value=""></option>
                        <option value="<?php echo esc_attr($this->status_success) ?>" <?php selected( $this->status_success,
							get_user_meta( $user->ID,
								$this->av_status_key,
								true ) ); ?>>
							<?php _e( 'AV Success', $this->plugin_text_domain ) ?>
                        </option>
                        <option value="<?php echo esc_attr($this->status_failed) ?>" <?php selected( $this->status_failed,
							get_user_meta( $user->ID,
								$this->av_status_key,
								true ) ); ?>>
							<?php _e( 'AV Failed', $this->plugin_text_domain ) ?>
                        </option>
                        <option value="<?php echo esc_attr('no_uk_user') ?>" <?php selected( 'no_uk_user',
							get_user_meta( $user->ID,
								$this->av_status_key,
								true ) ); ?>>
							<?php _e( 'NO_UK_USER', $this->plugin_text_domain ) ?>
                        </option>
                    </select>

                   
                </td>
            </tr>
        </table>
	<?php }

	// Save user status field
	public function save_custom_profile_field( $user_id ) {
		if ( !current_user_can( 'edit_user', $user_id ) )
			return false;

		update_user_meta( $user_id, $this->av_status_key, $_POST[$this->av_status_key] );
	}



}
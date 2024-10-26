<?php
/**
 * @package 1account for WooCommerce
 */

namespace Oneacc\Api;


use Oneacc\Base\BaseVarsController;

class UserController extends BaseVarsController {

	public function get_authorized_user() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return get_current_user_id();
	}

	// Check if user is verified
	public function is_user_verified() {
		$user_id = $this->get_authorized_user();
		if ($user_id) {
			$user_status = get_user_meta( $user_id, $this->av_status_key, true );
			if ( $user_status === $this->status_success ) {
				return true;
			}
		} else if ( isset ($_COOKIE[$this->av_status_key])) {
			return $_COOKIE[$this->av_status_key] === $this->status_success;
		}

		return false;
	}

	

	// Update user status meta
	public function update_user_status($user_id, $status) {
		if ($user_id) {
			// $status = $status == $this->status_success ? $this->status_success : $this->status_failed;
			// $st ="";


			if( $status == $this->status_success)
			{
				$st = $this->status_success;

			}

			else if( $status == $this->status_failed)
			{
				$st = $this->status_failed;

			}
			else

			{
				$st = "";
			}

			update_user_meta($user_id, $this->av_status_key, $st);
		}
	}
}

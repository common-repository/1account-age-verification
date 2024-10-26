<?php
/**
 * @package 1account for WooCommerce
 */

namespace Oneacc;


final class Init {

	/**
	 * Store all the classes inside an array.
	 *
	 * @return array Full list of classes
	 */
	public static function get_services() {
		return [
			Base\Enqueue::class,
			Base\BaseVarsController::class,
			Admin\OrdersTable::class,
			Admin\PluginSettingsLink::class,
			Admin\PluginSettingsForm::class,
			Api\OrderController::class,
			Api\UserController::class,
			User\UserSettings::class,
		];
	}

	/**
	 * Loop through the classes, initialize and call the register() method if
	 * exists.
	 */
	public static function register_services() {
		foreach ( self::get_services() as $class ) {
			$service = self::instantiate( $class );

			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}

	/**
	 * Initialize the class
	 *
	 * @param $class
	 *
	 * @return mixed
	 */
	private static function instantiate( $class ) {
		return new $class();
	}
}

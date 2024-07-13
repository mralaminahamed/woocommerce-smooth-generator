<?php
/**
 * Plugin Name: WooCommerce Smooth Generator
 * Plugin URI: https://woocommerce.com
 * Description: A smooth product, order, customer, and coupon generator for WooCommerce.
 * Version: 1.2.0
 * Author: Automattic
 * Author URI: https://woocommerce.com
 *
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 5.0.0
 * WC tested up to: 9.1.0
 * Woo: 000000:0a0a0a0a0a0a0a0a0a0a0a0a0a0a0a0
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

// autoloader.
if ( ! class_exists( \WC\SmoothGenerator\Plugin::class ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

/**
 * Fetch instance of plugin.
 *
 * @return \WC\SmoothGenerator\Plugin
 */
function wc_smooth_generator() {
	static $instance;

	if ( is_null( $instance ) ) {
		$instance = new \WC\SmoothGenerator\Plugin( __FILE__ );
	}

	return $instance;
}

/**
 * Init plugin when WordPress loads.
 */
function load_wc_smooth_generator() {
	wc_smooth_generator();
}

if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
	add_action( 'plugins_loaded', 'load_wc_smooth_generator', 20 );
}

/**
 * Declare HPOS compatibility.
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

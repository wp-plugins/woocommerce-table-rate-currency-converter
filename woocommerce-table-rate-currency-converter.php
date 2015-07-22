<?php
/*
Plugin Name: WooCommerce Table Rate Currency Converter
Plugin URI: http://omniwp.com.br/plugins
Description: Allows the use of a local currency for table rate shippings. This does not affect the currency in which you take payment. Conversions are estimated based on data from the Open Source Exchange Rates API with no guarantee whatsoever of accuracy.
Version: 2.0
Author: omniWP
Author URI: http://omniwp.com.br
Requires at least: 3.5.1
Tested up to: 4.2.3

	Copyright: © 2013-2015 omniWP.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
/**
 * Required functions
 */
if ( ! function_exists( 'is_woocommerce_active' ) )
	require_once( 'woo-includes/woo-functions-custom.php' );

/**
 * Localisation
 */
load_plugin_textdomain( 'wc_table_rate_currency_converter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

/**
 * Plugin page links
 */
function wc_table_rate_currency_converter_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=wc_table_rate_currency_converter' ) . '">' . __( 'Settings' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_table_rate_currency_converter_plugin_links' );


/**
 * Check if WooCommerce is active
 */
if ( is_woocommerce_active() ) {

	/**
	 * wc_table_rate_currency_converter_init function.
	 *
	 * @access public
	 * @return void
	 */
	function wc_table_rate_currency_converter_init() {
		include_once( 'classes/class-wc-table-rate-currency-converter.php' );
	}

	add_action( 'woocommerce_shipping_init', 'wc_table_rate_currency_converter_init' );

	/**
	 * wc_table_rate_currency_converter_add_method function.
	 *
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	function wc_table_rate_currency_converter_add_method( $methods ) {
		$methods[] = 'wc_table_rate_currency_converter';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'wc_table_rate_currency_converter_add_method' );
}
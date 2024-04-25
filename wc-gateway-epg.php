<?php

/**
 * Plugin Name: Epg
 * Plugin URI: 
 * Description:  Ecom Payment Gateway
 * Author: Quantv
 * Author URI: 
 * Text Domain: epg
 * Domain Path: /languages
 * Version: 0.0.3
 * License: GNU General Public License v3.0
 */


defined('ABSPATH') or exit;
define( 'WC_GATEWAY_EPG_VERSION', '0.0.1' ); 
define( 'WC_GATEWAY_EPG_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_GATEWAY_EPG_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

//load plugin code
add_action('plugins_loaded', 'epg_gateway_init', 11);

function epg_gateway_init()
{
    require_once(plugin_basename('classes/class-wc-gateway-epg.php'));
}

//register epg gateway 
add_filter('woocommerce_payment_gateways', 'epg_add_gateways');
add_action('plugins_loaded', 'epg_load_plugin_textdomain');
function epg_add_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_Epg';
    return $gateways;
}

add_action( 'woocommerce_blocks_loaded', 'woocommerce_epg_woocommerce_blocks_support' );

function woocommerce_epg_woocommerce_blocks_support() {
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		require_once dirname( __FILE__ ) . '/classes/class-wc-gateway-epg-blocks-support.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new WC_Epg_Blocks_Support );
			}
		);
	}
}

add_action( 'init', 'epg_add_settting');

function epg_add_settting(){
    if ( class_exists( 'WooCommerce' ) ) {
        // Add "Settings" link when the plugin is active
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ),'epg_add_settings_link');
    }
}
function epg_add_settings_link( $links ) {
    $settings = array( '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=epg' ) . '">' . __( 'Thiết lập', 'woocommerce' ) . '</a>' );
    $links    = array_reverse( array_merge( $links, $settings ) );

    return $links;
}
function epg_load_plugin_textdomain()
{
    //tam thoi bo i18n
    //load_plugin_textdomain('epg', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}
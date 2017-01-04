<?php
/*
Plugin Name: WooCommerce - Instamojo
Plugin URI: http://www.instamojo.com
Description: Instamojo Payment Gateway for WooCommerce. Instamojo lets you collect payments instantly.
Version: 1.0.3
Author: instamojo
Email: support@instamojo.com
Author URI: http://www.instamojo.com/
License: MIT
License URI: https://opensource.org/licenses/MIT
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Add settings link on plugin page
function your_plugin_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=instamojo">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'your_plugin_settings_link' );

function woocommerce_required_admin_notice() {
  echo   '<div class="updated error notice"><p>';
      echo    _e( '<b>Instamojo</b> Plugin requires WooCommerce to be Installed First!', 'my-text-domain' ); 
   echo  '</p></div>';
}

#check if woocommerce installed.
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
add_action( 'admin_notices', 'woocommerce_required_admin_notice' );
//exit;
}else
{
	function insta_log($message){
		$log = new WC_Logger();
		$log->add( 'instamojo', $message );
		
	}
	# register our GET variables
	function add_query_vars_filter( $vars ){
	  $vars[] = "payment_id";
	  $vars[] = "id";
	  return $vars;
	}
	add_filter( 'query_vars', 'add_query_vars_filter' );

	# initialize your Gateway Class
	add_action( 'plugins_loaded', 'init_instamojo_payment_gateway' );
	function init_instamojo_payment_gateway()
	{
		include_once "payment_gateway.php";
	}

	# look for redirect from instamojo.
	add_action( 'template_redirect', 'init_instamojo_payment_gateway1' );
	function init_instamojo_payment_gateway1(){
		if(get_query_var("payment_id") and get_query_var("id")){
			$payment_id = get_query_var("payment_id");
			$payment_request_id = get_query_var("id");
			include_once "payment_confirm.php";
		}
	}

	# add paymetnt method to payment gateway list
	add_filter("woocommerce_payment_gateways","add_instamojo");
	function add_instamojo($methods){
		$methods[] = 'WP_Gateway_Instamojo';
		return $methods;
	}
}


?>

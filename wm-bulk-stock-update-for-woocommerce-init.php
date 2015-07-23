<?php
/**
 * Plugin Name: Bulk Stock Update for WooCommerce
 * Plugin URI: http://plugins.web-mumbai.com/
 * Description: "Bulk Stock Update for WooCommerce" for update all products stock on one page.
 * Version: 1.0
 * Author: Web Mumbai
 * Author URI: http://plugins.web-mumbai.com/
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Last Update Date: 30 May, 2015
 */ 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if(is_admin()){	
	require_once("wm-bulk-stock-update-for-woocommerce.php");
	new WM_WooCommerce_Update_Stock_Lite(__FILE__);
}//End Admin Check
<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://ad-unblock.com
 * @since      1.0.3
 *
 * @package    Ad_Unblock
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('ad_unblock_verification_code');
delete_option('ad_unblock_page_rules');

// Delete transients
delete_transient('ad_unblock_script_sources'); 
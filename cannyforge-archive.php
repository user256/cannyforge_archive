<?php
/**
 * Plugin Name: CannyForge Archive Generator
 * Description: A combined HTML sitemap + JS-powered archive, and a crawl-budget-friendly replacement for default WordPress taxonomy pagination.
 * Version: 0.1.1
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cannyforge-archive
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CANNYFORGE_ARCHIVE_VERSION' ) ) {
	define( 'CANNYFORGE_ARCHIVE_VERSION', '0.1.1' );
}

if ( ! defined( 'CANNYFORGE_ARCHIVE_FILE' ) ) {
	define( 'CANNYFORGE_ARCHIVE_FILE', __FILE__ );
}

require __DIR__ . '/autoload.php';

// Initialize the plugin.
add_action(
	'plugins_loaded',
	function () {
		$plugin = new \CannyForge\Archive\Bootstrap\Plugin();
		$plugin->init();
	}
);

// Flush rewrite rules on activation.
register_activation_hook(
	__FILE__,
	function () {
		$plugin = new \CannyForge\Archive\Bootstrap\Plugin();
		$plugin->init();
		flush_rewrite_rules();
	}
);

// Flush rewrite rules on deactivation.
register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);

// Add Settings link to the Plugins page.
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		$settings_link = '<a href="admin.php?page=' . \CannyForge\Archive\Admin\SettingsPage::PAGE_SLUG . '">' . __( 'Settings', 'cannyforge-archive' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
);

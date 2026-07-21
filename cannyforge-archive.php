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
//
// By the time an activation hook runs, WordPress's own `init` action has
// already fired for this request (activation happens deep inside a normal
// wp-admin/WP-CLI request, well after bootstrap) — so the `add_action( 'init',
// ... )` registration inside `$plugin->init()` never executes this request.
// Register the endpoint directly, synchronously, so the flush below actually
// captures it (ticket 201/603: verified against real WordPress by the
// integration suite, which caught this exact gap).
register_activation_hook(
	__FILE__,
	function () {
		$plugin = new \CannyForge\Archive\Bootstrap\Plugin();
		$plugin->init();

		add_rewrite_endpoint(
			\CannyForge\Archive\Frontend\ArchivePage::DEFAULT_SLUG,
			EP_ROOT,
			\CannyForge\Archive\Frontend\ArchivePage::QUERY_VAR
		);

		flush_rewrite_rules();
	}
);

// Flush rewrite rules on deactivation.
//
// The archive endpoint is still registered on `$wp_rewrite` for this request
// (it was added when `init` fired earlier, while the plugin was still
// active), so a naive flush here would regenerate the rules with the
// endpoint still present — a residue that survives deactivation. Strip it
// from the in-memory endpoint list before flushing so deactivation actually
// removes the rule (ticket 201/603).
register_deactivation_hook(
	__FILE__,
	function () {
		global $wp_rewrite;

		if ( $wp_rewrite instanceof WP_Rewrite ) {
			$wp_rewrite->endpoints = array_values(
				array_filter(
					(array) $wp_rewrite->endpoints,
					static function ( $endpoint ) {
						return ! is_array( $endpoint )
							|| ( $endpoint[2] ?? '' ) !== \CannyForge\Archive\Frontend\ArchivePage::QUERY_VAR;
					}
				)
			);
		}

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

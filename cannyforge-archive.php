<?php
/**
 * Plugin Name: CannyForge Archive
 * Plugin URI: https://cannyforge.com/archive
 * Description: A combined HTML sitemap + JS-powered archive, and a crawl-budget-friendly replacement for default WordPress taxonomy pagination.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: CannyForge
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
	define( 'CANNYFORGE_ARCHIVE_VERSION', '0.1.0' );
}

if ( ! defined( 'CANNYFORGE_ARCHIVE_FILE' ) ) {
	define( 'CANNYFORGE_ARCHIVE_FILE', __FILE__ );
}

$cannyforge_archive_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $cannyforge_archive_autoload ) ) {
	require $cannyforge_archive_autoload;
}

// Initialize the plugin.
add_action(
	'plugins_loaded',
	function () {
		$plugin = new \CannyForge\Archive\Bootstrap\Plugin();
		$plugin->init();
	}
);

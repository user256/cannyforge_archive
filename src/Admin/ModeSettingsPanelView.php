<?php
/**
 * Renders the mode-specific settings panels.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;

/**
 * Presentation-only renderer for the News/Blog mode panels.
 *
 * The guided Google setup is a stepped full-page wizard
 * ({@see GoogleWizardPage}); this class owns the mode panels and the
 * condensed Google summary strip with the wizard launcher link.
 */
final class ModeSettingsPanelView {
	/**
	 * Shared field renderer.
	 *
	 * @var FormFieldView
	 */
	private FormFieldView $fields;

	/**
	 * Cached Search Console page source.
	 *
	 * @var SearchConsoleCacheStore
	 */
	private SearchConsoleCacheStore $search_console_cache;

	/**
	 * GA4 page diagnostics view.
	 *
	 * @var Ga4DiagnosticsView
	 */
	private Ga4DiagnosticsView $ga4_diagnostics;

	/**
	 * WordPress post-title resolver.
	 *
	 * @var callable
	 */
	private $post_title;

	/**
	 * WordPress permalink resolver.
	 *
	 * @var callable
	 */
	private $permalink;

	/**
	 * Construct the mode-panel renderer.
	 *
	 * @param FormFieldView|null           $fields               Shared field renderer.
	 * @param SearchConsoleCacheStore|null $search_console_cache Cached top-page source.
	 * @param callable|null                $post_title           Post-title resolver.
	 * @param callable|null                $permalink             Permalink resolver.
	 * @param Ga4CacheStore|null           $ga4_cache            Cached GA4 page source.
	 */
	public function __construct(
		?FormFieldView $fields = null,
		?SearchConsoleCacheStore $search_console_cache = null,
		?callable $post_title = null,
		?callable $permalink = null,
		?Ga4CacheStore $ga4_cache = null
	) {
		$this->fields               = $fields ?? new FormFieldView();
		$this->search_console_cache = $search_console_cache ?? new SearchConsoleCacheStore();
		$this->post_title           = $post_title ?? static function ( int $post_id ): string {
			return function_exists( 'get_the_title' ) ? trim( (string) get_the_title( $post_id ) ) : '';
		};
		$this->permalink            = $permalink ?? static function ( int $post_id ): string {
			return function_exists( 'get_permalink' ) ? (string) get_permalink( $post_id ) : '';
		};
		$this->ga4_diagnostics      = new Ga4DiagnosticsView(
			$ga4_cache ?? new Ga4CacheStore(),
			$this->post_title,
			$this->permalink
		);
	}

	/**
	 * Render the mode-dependent settings panels.
	 *
	 * @param Settings       $settings           Current settings.
	 * @param GoogleSettings $google_settings    Current Google settings.
	 * @param string         $google_status      Current Google connection status.
	 * @param string         $google_notice      One-shot Google notice text.
	 * @param string         $google_notice_type One-shot Google notice type.
	 * @return void
	 */
	public function render(
		Settings $settings,
		GoogleSettings $google_settings,
		string $google_status,
		string $google_notice,
		string $google_notice_type
	): void {
		$this->render_news_panel( $settings );
		$this->render_blog_panel( $settings, $google_settings, $google_status, $google_notice, $google_notice_type );
	}

	/**
	 * Render the News mode controls.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_news_panel( Settings $settings ): void {
		echo '<div class="cf-panel-news" style="margin-top: 1rem; border-top: 1px solid var(--cf-border); padding-top: 1rem;">';
		echo '<h2>' . esc_html__( 'News Cycle Settings', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'Show posts published within the configured recent window, falling back to the latest posts when that window is empty.', 'cannyforge-archive' );
		echo '</p>';
		echo '<p><label>' . esc_html__( 'Include content published in the last (hours)', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="number" min="1" step="1" name="news_window_hours" value="%d"></label></p>',
			absint( $settings->news_window_hours() )
		);
		echo '<p><label>' . esc_html__( 'When that window is empty, show the latest (posts)', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="number" min="1" max="500" step="1" name="news_fallback_count" value="%d"></label></p>',
			absint( $settings->news_fallback_count() )
		);
		echo '<p class="description">';
		echo esc_html__( 'Fallback so the archive is never blank when no post falls inside the recent window.', 'cannyforge-archive' );
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Render the Blog mode controls and Google panel.
	 *
	 * @param Settings       $settings           Current settings.
	 * @param GoogleSettings $google_settings    Current Google settings.
	 * @param string         $google_status      Current Google connection status.
	 * @param string         $google_notice      One-shot Google notice text.
	 * @param string         $google_notice_type One-shot Google notice type.
	 * @return void
	 */
	private function render_blog_panel(
		Settings $settings,
		GoogleSettings $google_settings,
		string $google_status,
		string $google_notice,
		string $google_notice_type
	): void {
		echo '<div class="cf-panel-blog" style="margin-top: 1rem; border-top: 1px solid var(--cf-border); padding-top: 1rem;">';
		echo '<h2>' . esc_html__( 'Top Articles', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'A list of top articles manually set.', 'cannyforge-archive' );
		echo '</p>';
		echo '<p><label>' . esc_html__( 'Maximum curated URLs', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="number" min="1" name="blog_max_urls" value="%d"></label></p>',
			absint( $settings->blog_max_urls() )
		);
		echo '<p><textarea id="cf-blog-urls" name="blog_urls" rows="8" cols="50">';
		echo esc_textarea( implode( "\n", $settings->blog_urls() ) );
		echo '</textarea></p>';
		$this->render_search_console_curator();
		$this->render_ga4_diagnostics();

		echo '<p><label>' . esc_html__( 'Import URLs from CSV', 'cannyforge-archive' ) . '<br>';
		echo '<input type="file" name="blog_urls_csv" accept=".csv,text/csv"></label></p>';
		$this->fields->checkbox(
			'blog_urls_csv_replace',
			__( 'Replace the list with the CSV (otherwise merge)', 'cannyforge-archive' ),
			false
		);
		echo '<p class="description">';
		echo esc_html__( 'The first URL-like value in each CSV row is imported.', 'cannyforge-archive' );
		echo '</p>';
		$this->render_google_panel( $google_settings, $google_status, $google_notice, $google_notice_type );
		echo '</div>';
	}

	/**
	 * Render the human-readable Search Console results used to curate Blog URLs.
	 *
	 * The cache deliberately stores post IDs for the archive runtime. This
	 * presentation layer resolves those IDs back to WordPress titles and links,
	 * so the editor never has to curate an opaque list of numbers.
	 *
	 * @return void
	 */
	private function render_search_console_curator(): void {
		$post_ids    = $this->search_console_cache->get_post_ids();
		$source_urls = $this->search_console_cache->get_source_urls();

		echo '<section class="cf-search-console-curator" aria-labelledby="cf-search-console-curator-title">';
		echo '<h3 id="cf-search-console-curator-title">' . esc_html__( 'Search Console top pages', 'cannyforge-archive' ) . '</h3>';
		echo '<p class="description">';
		echo esc_html__( 'Select published WordPress posts from the latest Search Console results to add them to your curated URL list.', 'cannyforge-archive' );
		echo '</p>';

		$rows = array();
		foreach ( $post_ids as $post_id ) {
			$title = trim( (string) ( $this->post_title )( $post_id ) );
			$url   = (string) ( $this->permalink )( $post_id );

			if ( '' === $url ) {
				continue;
			}

			$rows[] = array(
				'id'    => $post_id,
				'title' => '' !== $title ? $title : __( '(Untitled post)', 'cannyforge-archive' ),
				'url'   => $url,
			);
		}

		if ( empty( $rows ) ) {
			$this->render_empty_search_console_state( $source_urls );
			echo '</section>';
			return;
		}

		echo '<ol class="cf-search-console-curator__list">';
		foreach ( $rows as $index => $row ) {
			printf(
				'<li class="cf-search-console-curator__item"><label><input type="checkbox" data-cf-search-console-page data-url="%1$s"> <span class="cf-search-console-curator__rank">%2$d</span> <span class="cf-search-console-curator__title">%3$s</span></label> <a class="cf-search-console-curator__link" href="%1$s" target="_blank" rel="noopener noreferrer">%4$s</a></li>',
				esc_attr( $row['url'] ),
				absint( $index + 1 ),
				esc_html( $row['title'] ),
				esc_html__( 'View', 'cannyforge-archive' )
			);
		}
		echo '</ol>';
		echo '<p class="cf-search-console-curator__actions">';
		echo '<button type="button" class="cf-btn cf-btn-outline" data-cf-add-search-console-pages data-target="#cf-blog-urls">' . esc_html__( 'Add selected to curated URLs', 'cannyforge-archive' ) . '</button> ';
		echo '<span class="description" data-cf-search-console-status role="status" aria-live="polite"></span>';
		echo '</p>';
		echo '</section>';
	}

	/**
	 * Render the empty state, including raw remote URLs when available.
	 *
	 * @param string[] $source_urls Raw Search Console URLs.
	 * @return void
	 */
	private function render_empty_search_console_state( array $source_urls ): void {
		echo '<p class="cf-search-console-curator__empty">';
		if ( empty( $source_urls ) ) {
			echo esc_html__( 'No cached Search Console pages are available yet. Refresh Search Console in the Google setup wizard to load them here.', 'cannyforge-archive' );
			echo '</p>';
			return;
		}

		printf(
			/* translators: %d: number of URLs returned by Search Console. */
			esc_html__( 'Search Console returned %d URLs, but none matched published posts on this WordPress install. This is expected when testing production data on local or staging content.', 'cannyforge-archive' ),
			count( $source_urls )
		);
		echo '</p>';
		$this->render_source_urls( $source_urls );
	}

	/**
	 * Render raw URLs returned by Google for local/staging diagnostics.
	 *
	 * @param string[] $source_urls Raw Search Console URLs.
	 * @return void
	 */
	private function render_source_urls( array $source_urls ): void {
		echo '<ol class="cf-search-console-curator__list">';
		foreach ( $source_urls as $url ) {
			printf(
				'<li class="cf-search-console-curator__item"><a class="cf-search-console-curator__link" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></li>',
				esc_url( $url ),
				esc_html( $url )
			);
		}
		echo '</ol>';
	}

	/**
	 * Render the GA4 page-path diagnostics.
	 *
	 * @return void
	 */
	private function render_ga4_diagnostics(): void {
		$this->ga4_diagnostics->render();
	}

	/**
	 * Render the Google summary strip and setup-wizard launcher for Blog mode.
	 *
	 * @param GoogleSettings $settings    Current Google settings.
	 * @param string         $status      Connection status.
	 * @param string         $notice      One-shot notice text.
	 * @param string         $notice_type Notice type.
	 * @return void
	 */
	private function render_google_panel(
		GoogleSettings $settings,
		string $status,
		string $notice,
		string $notice_type
	): void {
		echo '<div class="cannyforge-google-wizard">';
		echo '<h3 class="cannyforge-google-wizard__title">' . esc_html__( 'Google Top Content', 'cannyforge-archive' ) . '</h3>';
		echo '<p class="description">';
		echo esc_html__( 'Use the guided setup to connect Google, choose your content signal, and refresh the archive cache without needing to understand the raw API fields first.', 'cannyforge-archive' );
		echo '</p>';
		$this->render_google_notice( $notice, $notice_type );
		$this->render_google_summary( $settings, $status );
		echo '<p class="cannyforge-google-wizard__launcher">';
		printf(
			'<a class="button button-primary" href="%s">%s</a>',
			esc_url( GoogleWizardPage::url() ),
			esc_html__( 'Open Google setup wizard', 'cannyforge-archive' )
		);
		echo ' ';
		echo '<span class="description">' . esc_html__( 'You can reopen this any time to update credentials, reconnect, or refresh the cache.', 'cannyforge-archive' ) . '</span>';
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Render a one-shot Google panel notice.
	 *
	 * @param string $notice      Notice text.
	 * @param string $notice_type Notice type.
	 * @return void
	 */
	private function render_google_notice( string $notice, string $notice_type ): void {
		if ( '' === $notice ) {
			return;
		}

		$class = GoogleConnectionController::NOTICE_SUCCESS === $notice_type ? 'notice-success' : 'notice-error';
		printf(
			'<div class="notice %1$s inline"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $notice )
		);
	}

	/**
	 * Render the condensed Google setup summary shown on the main settings page.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @param string         $status   Connection status.
	 * @return void
	 */
	private function render_google_summary( GoogleSettings $settings, string $status ): void {
		$has_credentials    = '' !== $settings->client_id();
		$has_search_console = '' !== $settings->search_console_site_url();
		$has_ga4            = '' !== $settings->ga4_property_id();

		echo '<div class="cannyforge-google-wizard__summary">';
		$this->render_google_summary_item(
			__( 'Credentials', 'cannyforge-archive' ),
			$has_credentials ? __( 'Saved', 'cannyforge-archive' ) : __( 'Needed', 'cannyforge-archive' ),
			$has_credentials
		);
		$this->render_google_summary_item(
			__( 'Google account', 'cannyforge-archive' ),
			GoogleTokenStore::status_label( $status ),
			GoogleTokenStore::STATUS_CONNECTED === $status
		);
		$this->render_google_summary_item(
			__( 'Search Console property', 'cannyforge-archive' ),
			$has_search_console ? __( 'Ready', 'cannyforge-archive' ) : __( 'Needed', 'cannyforge-archive' ),
			$has_search_console
		);
		$this->render_google_summary_item(
			__( 'GA4 fallback', 'cannyforge-archive' ),
			$has_ga4 ? __( 'Enabled', 'cannyforge-archive' ) : __( 'Off', 'cannyforge-archive' ),
			$has_ga4
		);
		echo '</div>';
	}

	/**
	 * Render one status pill inside the Google summary strip.
	 *
	 * @param string $label   Summary item label.
	 * @param string $value   Summary item value.
	 * @param bool   $is_good Whether the item is in a ready/healthy state.
	 * @return void
	 */
	private function render_google_summary_item( string $label, string $value, bool $is_good ): void {
		printf(
			'<div class="cannyforge-google-wizard__summary-item"><span class="cannyforge-google-wizard__summary-label">%1$s</span><span class="cannyforge-google-wizard__summary-value %2$s">%3$s</span></div>',
			esc_html( $label ),
			$is_good ? 'is-good' : 'is-pending',
			esc_html( $value )
		);
	}
}

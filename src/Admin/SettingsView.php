<?php
/**
 * Renders the settings page form markup.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Contracts\Settings\Theme;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;

/**
 * Renders the "HTML Sitemap Generator Settings" form from a Settings snapshot.
 *
 * Pure presentation: it echoes escaped markup and owns no persistence. The
 * mode-dependent panel and all controls follow the brief's mock-up
 * (see docs/PLAN.md).
 */
final class SettingsView {
	/**
	 * The form field name carrying the nonce.
	 */
	public const NONCE_FIELD = 'cannyforge_archive_settings_nonce';

	/**
	 * The nonce action.
	 */
	public const NONCE_ACTION = 'cannyforge_archive_save_settings';

	/**
	 * Mode-panel renderer.
	 *
	 * @var ModeSettingsPanelView
	 */
	private ModeSettingsPanelView $mode_panel;

	/**
	 * Shared field renderer.
	 *
	 * @var FormFieldView
	 */
	private FormFieldView $fields;

	/**
	 * Construct the settings view.
	 *
	 * @param ModeSettingsPanelView|null $mode_panel Mode-panel renderer.
	 * @param FormFieldView|null         $fields     Shared field renderer.
	 */
	public function __construct( ?ModeSettingsPanelView $mode_panel = null, ?FormFieldView $fields = null ) {
		$this->mode_panel = $mode_panel ?? new ModeSettingsPanelView();
		$this->fields     = $fields ?? new FormFieldView();
	}

	/**
	 * Render the whole settings page.
	 *
	 * @param Settings            $settings              Current settings to populate the form with.
	 * @param string              $action_url            The form post target.
	 * @param string              $preview_url           The live archive URL for the "Preview" link.
	 * @param GoogleSettings|null $google_settings       Current Google settings.
	 * @param string              $google_status         Current Google connection status.
	 * @param bool                $google_secret_saved   Whether a client secret is already stored.
	 * @param string              $google_connect_url    Connect action URL.
	 * @param string              $google_disconnect_url Disconnect action URL.
	 * @param string              $google_refresh_url    Refresh action URL.
	 * @param string              $google_notice         One-shot Google notice text.
	 * @param string              $google_notice_type    One-shot Google notice type.
	 * @return void
	 */
	public function render(
		Settings $settings,
		string $action_url,
		string $preview_url = '',
		?GoogleSettings $google_settings = null,
		string $google_status = GoogleTokenStore::STATUS_DISCONNECTED,
		bool $google_secret_saved = false,
		string $google_connect_url = '',
		string $google_disconnect_url = '',
		string $google_refresh_url = '',
		string $google_notice = '',
		string $google_notice_type = GoogleConnectionController::NOTICE_ERROR
	): void {
		$google_settings = $google_settings ?? new GoogleSettings();

		echo '<div class="wrap cannyforge-archive-settings">';
		$this->render_brand_header( $preview_url );

		printf( '<form method="post" enctype="multipart/form-data" action="%s">', esc_url( $action_url ) );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$this->render_top_section(
			$settings,
			$google_settings,
			$google_status,
			$google_secret_saved,
			$google_connect_url,
			$google_disconnect_url,
			$google_refresh_url,
			$google_notice,
			$google_notice_type
		);
		$this->render_theme_section( $settings );
		$this->render_settings_grid( $settings );

		echo '<p class="cannyforge-archive-actions" style="margin-top: 2rem;">';
		submit_button( __( 'Save Settings', 'cannyforge-archive' ), 'primary', 'submit', false );
		echo '</p>';
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render the branded page header: the CannyForge wordmark above the page title.
	 *
	 * @param string $preview_url The URL to preview the archive.
	 * @return void
	 */
	private function render_brand_header( string $preview_url ): void {
		echo '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">';
		echo '<h1>' . esc_html__( 'Archive Generator', 'cannyforge-archive' ) . '</h1>';

		if ( '' !== $preview_url ) {
			printf(
				'<a class="button button-secondary" href="%s" target="_blank" rel="noopener noreferrer" style="border-radius:999px;padding:0.4rem 1.25rem;font-weight:600;display:inline-flex;align-items:center;text-decoration:none;border:1px solid var(--cf-violet);color:var(--cf-violet);">%s <span style="margin-left:0.4rem;">&rarr;</span></a>',
				esc_url( $preview_url ),
				esc_html__( 'Preview Archive', 'cannyforge-archive' )
			);
		}

		echo '</div>';
	}

	/**
	 * Render the top mode-and-panel section.
	 *
	 * @param Settings       $settings              Current settings.
	 * @param GoogleSettings $google_settings       Current Google settings.
	 * @param string         $google_status         Current Google connection status.
	 * @param bool           $google_secret_saved   Whether a client secret is already stored.
	 * @param string         $google_connect_url    Connect action URL.
	 * @param string         $google_disconnect_url Disconnect action URL.
	 * @param string         $google_refresh_url    Refresh action URL.
	 * @param string         $google_notice         One-shot Google notice text.
	 * @param string         $google_notice_type    One-shot Google notice type.
	 * @return void
	 */
	private function render_top_section(
		Settings $settings,
		GoogleSettings $google_settings,
		string $google_status,
		bool $google_secret_saved,
		string $google_connect_url,
		string $google_disconnect_url,
		string $google_refresh_url,
		string $google_notice,
		string $google_notice_type
	): void {
		echo '<div class="cannyforge-archive-top-section" style="margin-bottom: 2rem;">';
		echo '<div class="cannyforge-archive-col" style="width: 100%;">';
		$this->render_mode_and_pagination( $settings );
		$this->mode_panel->render(
			$settings,
			$google_settings,
			$google_status,
			$google_secret_saved,
			$google_connect_url,
			$google_disconnect_url,
			$google_refresh_url,
			$google_notice,
			$google_notice_type
		);
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render the mode toggle and pagination control.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_mode_and_pagination( Settings $settings ): void {
		$mode = $settings->mode();

		echo '<h2>' . esc_html__( 'Mode', 'cannyforge-archive' ) . '</h2>';
		echo '<div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">';

		echo '<label style="flex: 1; min-width: 200px; padding: 1rem; border: 1px solid var(--cf-border); border-radius: 12px; cursor: pointer; background: ' . ( Mode::News === $mode ? 'var(--cf-lavender-tint)' : '#fff' ) . ';">';
		echo '<div style="font-weight: 600; margin-bottom: 0.5rem;"><input type="radio" name="mode" value="news" ' . checked( Mode::News === $mode, true, false ) . '> ' . esc_html__( 'News Cycle', 'cannyforge-archive' ) . '</div>';
		echo '<div class="description" style="margin: 0; padding-left: 1.5rem;">' . esc_html__( 'Only newest', 'cannyforge-archive' ) . '</div>';
		echo '</label>';

		echo '<label style="flex: 1; min-width: 200px; padding: 1rem; border: 1px solid var(--cf-border); border-radius: 12px; cursor: pointer; background: ' . ( Mode::Blog === $mode ? 'var(--cf-lavender-tint)' : '#fff' ) . ';">';
		echo '<div style="font-weight: 600; margin-bottom: 0.5rem;"><input type="radio" name="mode" value="blog" ' . checked( Mode::Blog === $mode, true, false ) . '> ' . esc_html__( 'Blog', 'cannyforge-archive' ) . '</div>';
		echo '<div class="description" style="margin: 0; padding-left: 1.5rem;">' . esc_html__( 'Only your top content', 'cannyforge-archive' ) . '</div>';
		echo '</label>';

		echo '<label style="flex: 1; min-width: 200px; padding: 1rem; border: 1px solid var(--cf-border); border-radius: 12px; cursor: pointer; background: ' . ( Mode::Hybrid === $mode ? 'var(--cf-lavender-tint)' : '#fff' ) . ';">';
		echo '<div style="font-weight: 600; margin-bottom: 0.5rem;"><input type="radio" name="mode" value="hybrid" ' . checked( Mode::Hybrid === $mode, true, false ) . '> ' . esc_html__( 'Hybrid', 'cannyforge-archive' ) . '</div>';
		echo '<div class="description" style="margin: 0; padding-left: 1.5rem;">' . esc_html__( 'A hybrid approach', 'cannyforge-archive' ) . '</div>';
		echo '</label>';

		echo '</div>';

		echo '<p><label>' . esc_html__( 'Pagination (default 1)', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="number" min="1" name="pagination_limit" value="%d"></label></p>',
			absint( $settings->pagination_limit() )
		);

		echo '<p><label>' . esc_html__( '"View Archive" link URL (optional)', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="url" name="archive_url" value="%s" placeholder="%s"></label></p>',
			esc_attr( $settings->archive_url() ),
			esc_attr__( 'Defaults to the archive page', 'cannyforge-archive' )
		);
	}

	/**
	 * Render the theme section.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_theme_section( Settings $settings ): void {
		echo '<div class="cannyforge-archive-theme-section" style="margin-bottom: 2rem;">';
		echo '<div class="cannyforge-archive-col" style="width: 100%;">';
		$this->render_theme( $settings );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render the front-end theming controls.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_theme( Settings $settings ): void {
		$theme = $settings->theme();

		echo '<h2>' . esc_html__( 'Front-end Theme', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'These styles apply to the archive page and the "View Archive" pagination block.', 'cannyforge-archive' );
		echo '</p>';

		echo '<p><label>' . esc_html__( 'Layout', 'cannyforge-archive' ) . ' ';
		echo '<select name="theme_layout">';
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( Theme::LAYOUT_DEFAULT ),
			selected( $theme->layout(), Theme::LAYOUT_DEFAULT, false ),
			esc_html__( 'Default (follows blog pagination layout)', 'cannyforge-archive' )
		);
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( Theme::LAYOUT_LIST ),
			selected( $theme->layout(), Theme::LAYOUT_LIST, false ),
			esc_html__( 'Simple list', 'cannyforge-archive' )
		);
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( Theme::LAYOUT_CARDS ),
			selected( $theme->layout(), Theme::LAYOUT_CARDS, false ),
			esc_html__( 'Cards', 'cannyforge-archive' )
		);
		echo '</select></label></p>';

		echo '<p><button type="button" class="button button-secondary" onclick="document.getElementById(\'cf-colors-modal\').showModal()">' . esc_html__( 'Edit Colours', 'cannyforge-archive' ) . '</button></p>';
		echo '<dialog id="cf-colors-modal" style="border: none; border-radius: 16px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); max-width: 400px; width: 100%;">';
		echo '<h3>' . esc_html__( 'Edit Colours', 'cannyforge-archive' ) . '</h3>';

		printf( '<p><label>%s <input type="color" name="theme_accent_color" value="%s"></label></p>', esc_html__( 'Accent Color', 'cannyforge-archive' ), esc_attr( $theme->accent_color() ) );
		printf( '<p><label>%s <input type="color" name="theme_surface_color" value="%s"></label></p>', esc_html__( 'Surface Color', 'cannyforge-archive' ), esc_attr( $theme->surface_color() ) );
		printf( '<p><label>%s <input type="color" name="theme_text_color" value="%s"></label></p>', esc_html__( 'Text Color', 'cannyforge-archive' ), esc_attr( $theme->text_color() ) );
		printf( '<p><label>%s <input type="color" name="theme_border_color" value="%s"></label></p>', esc_html__( 'Border Color', 'cannyforge-archive' ), esc_attr( $theme->border_color() ) );

		echo '<div style="text-align: right; margin-top: 1.5rem;">';
		echo '<button type="button" class="button button-primary" onclick="document.getElementById(\'cf-colors-modal\').close()">' . esc_html__( 'Done', 'cannyforge-archive' ) . '</button>';
		echo '</div>';
		echo '</dialog>';
	}

	/**
	 * Render the lower settings grid.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_settings_grid( Settings $settings ): void {
		echo '<div class="cannyforge-archive-grid">';
		echo '<div class="cannyforge-archive-col">';
		$this->render_targeting( $settings );
		echo '</div>';
		echo '<div class="cannyforge-archive-col">';
		$this->render_filters( $settings );
		echo '</div>';
		echo '<div class="cannyforge-archive-col">';
		$this->render_content_selection( $settings );
		echo '</div>';
		echo '<div class="cannyforge-archive-col">';
		$this->render_link_types( $settings );
		echo '</div>';
		echo '<div class="cannyforge-archive-col">';
		$this->render_seo( $settings );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render the archive-type targeting checkboxes (ticket 109).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_targeting( Settings $settings ): void {
		$targeting = $settings->targeting();

		echo '<h2>' . esc_html__( 'Pagination Targeting', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'Replace pagination on these archive types.', 'cannyforge-archive' );
		echo '</p>';
		$this->fields->checkbox( 'target_category', __( 'Category archives', 'cannyforge-archive' ), $targeting->category() );
		$this->fields->checkbox( 'target_tag', __( 'Tag archives', 'cannyforge-archive' ), $targeting->tag() );
		$this->fields->checkbox( 'target_author', __( 'Author archives', 'cannyforge-archive' ), $targeting->author() );
		$this->fields->checkbox( 'target_date', __( 'Date archives', 'cannyforge-archive' ), $targeting->date() );
	}

	/**
	 * Render the client-side filter checkboxes.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_filters( Settings $settings ): void {
		$filters = $settings->filters();

		echo '<h2>' . esc_html__( 'User Filters', 'cannyforge-archive' ) . '</h2>';
		$this->fields->checkbox( 'filter_search', __( 'Search Box', 'cannyforge-archive' ), $filters->search() );
		$this->fields->checkbox( 'filter_category', __( 'Category filters', 'cannyforge-archive' ), $filters->category() );
		$this->fields->checkbox( 'filter_tag', __( 'Tag filters', 'cannyforge-archive' ), $filters->tag() );
		$this->fields->checkbox( 'filter_month_year', __( 'Month + Year filters', 'cannyforge-archive' ), $filters->month_year() );
		$this->fields->checkbox( 'filter_author', __( 'Author filters', 'cannyforge-archive' ), $filters->author() );
	}

	/**
	 * Render the content-selection controls (ticket 111).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_content_selection( Settings $settings ): void {
		$selection = $settings->content_selection();

		echo '<h2>' . esc_html__( 'Content Selection', 'cannyforge-archive' ) . '</h2>';

		$this->fields->list_field( 'select_include_categories', __( 'Include categories', 'cannyforge-archive' ), $selection->include_categories() );
		$this->fields->list_field( 'select_include_tags', __( 'Include tags', 'cannyforge-archive' ), $selection->include_tags() );
		$this->fields->list_field( 'select_exclude_categories', __( 'Exclude categories', 'cannyforge-archive' ), $selection->exclude_categories() );
		$this->fields->list_field( 'select_exclude_tags', __( 'Exclude tags', 'cannyforge-archive' ), $selection->exclude_tags() );
		$this->fields->checkbox( 'select_exclude_noindex', __( 'Exclude noindex content', 'cannyforge-archive' ), $selection->exclude_noindex() );
		$this->fields->list_field( 'select_pinned_urls', __( 'Pinned URLs (shown first)', 'cannyforge-archive' ), $selection->pinned_urls() );
	}

	/**
	 * Render the archive link-type checkboxes.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_link_types( Settings $settings ): void {
		$types = $settings->link_types();

		echo '<h2>' . esc_html__( 'Archive Link Types', 'cannyforge-archive' ) . '</h2>';
		$this->fields->checkbox( 'link_title', __( 'Title (default)', 'cannyforge-archive' ), $types->title() );
		$this->fields->checkbox( 'link_description', __( 'Description', 'cannyforge-archive' ), $types->description() );
		$this->fields->checkbox( 'link_featured_image', __( 'Featured Image', 'cannyforge-archive' ), $types->featured_image() );
	}

	/**
	 * Render the SEO controls (ticket 110).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_seo( Settings $settings ): void {
		$seo = $settings->seo();

		echo '<h2>' . esc_html__( 'SEO', 'cannyforge-archive' ) . '</h2>';

		echo '<p><label>' . esc_html__( 'Archive title', 'cannyforge-archive' ) . ' ';
		printf( '<input type="text" name="seo_title" value="%s"></label></p>', esc_attr( $seo->title() ) );

		echo '<p><label>' . esc_html__( 'Meta description', 'cannyforge-archive' ) . ' ';
		printf(
			'<textarea name="seo_meta_description" rows="2" cols="50">%s</textarea></label></p>',
			esc_textarea( $seo->meta_description() )
		);

		$this->fields->checkbox( 'seo_index', __( 'Allow indexing (index)', 'cannyforge-archive' ), $seo->index() );
		$this->fields->checkbox( 'seo_follow', __( 'Allow following links (follow)', 'cannyforge-archive' ), $seo->follow() );

		echo '<p><label>' . esc_html__( 'Canonical URL (optional)', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="url" name="seo_canonical" value="%s" placeholder="%s"></label></p>',
			esc_attr( $seo->canonical() ),
			esc_attr__( 'Defaults to the archive URL', 'cannyforge-archive' )
		);
	}
}

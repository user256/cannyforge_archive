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

/**
 * Renders the "HTML Sitemap Generator Settings" form from a Settings snapshot.
 *
 * Pure presentation: it echoes escaped markup and owns no persistence. The
 * mode-dependent right-hand panel and all controls follow the brief's mock-up
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
	 * Render the whole settings page.
	 *
	 * @param Settings $settings    Current settings to populate the form with.
	 * @param string   $action_url  The form post target.
	 * @param string   $preview_url The live archive URL for the "Preview" link.
	 * @return void
	 */
	public function render( Settings $settings, string $action_url, string $preview_url = '' ): void {
		echo '<div class="wrap cannyforge-archive-settings">';
		$this->render_brand_header( $preview_url );

		printf( '<form method="post" enctype="multipart/form-data" action="%s">', esc_url( $action_url ) );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		// Top Section: Mode & Mode Settings (Full Width).
		echo '<div class="cannyforge-archive-top-section" style="margin-bottom: 2rem;">';
		echo '<div class="cannyforge-archive-col" style="width: 100%;">';
		$this->render_mode_and_pagination( $settings );
		$this->render_mode_panel( $settings );
		echo '</div>';
		echo '</div>';

		// Theme Section.
		echo '<div class="cannyforge-archive-theme-section" style="margin-bottom: 2rem;">';
		echo '<div class="cannyforge-archive-col" style="width: 100%;">';
		$this->render_theme( $settings );
		echo '</div>';
		echo '</div>';

		// Grid Section: Other Settings.
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

		echo '<p class="cannyforge-archive-actions" style="margin-top: 2rem;">';
		submit_button( __( 'Save Settings', 'cannyforge-archive' ), 'primary', 'submit', false );
		echo '</p>';
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render the branded page header: the CannyForge wordmark above the page title.
	 *
	 * Mirrors the sibling cannyforge-lead-capture plugin's settings branding —
	 * the bundled wordmark SVG with a text fallback, skinned to the design system
	 * (forge violet, royal-purple heading) — but without the premium upgrade CTA
	 * (this plugin has no premium tier). The wordmark shows when a base URL is
	 * configured; the title always renders.
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
	 * Render the mode toggle and pagination control.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_mode_and_pagination( Settings $settings ): void {
		$mode = $settings->mode();

		echo '<h2>' . esc_html__( 'Mode', 'cannyforge-archive' ) . '</h2>';
		echo '<div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">';

		// News Mode.
		echo '<label style="flex: 1; min-width: 200px; padding: 1rem; border: 1px solid var(--cf-border); border-radius: 12px; cursor: pointer; background: ' . ( Mode::News === $mode ? 'var(--cf-lavender-tint)' : '#fff' ) . ';">';
		echo '<div style="font-weight: 600; margin-bottom: 0.5rem;"><input type="radio" name="mode" value="news" ' . checked( Mode::News === $mode, true, false ) . '> ' . esc_html__( 'News Cycle', 'cannyforge-archive' ) . '</div>';
		echo '<div class="description" style="margin: 0; padding-left: 1.5rem;">' . esc_html__( 'Only newest', 'cannyforge-archive' ) . '</div>';
		echo '</label>';

		// Blog Mode.
		echo '<label style="flex: 1; min-width: 200px; padding: 1rem; border: 1px solid var(--cf-border); border-radius: 12px; cursor: pointer; background: ' . ( Mode::Blog === $mode ? 'var(--cf-lavender-tint)' : '#fff' ) . ';">';
		echo '<div style="font-weight: 600; margin-bottom: 0.5rem;"><input type="radio" name="mode" value="blog" ' . checked( Mode::Blog === $mode, true, false ) . '> ' . esc_html__( 'Blog', 'cannyforge-archive' ) . '</div>';
		echo '<div class="description" style="margin: 0; padding-left: 1.5rem;">' . esc_html__( 'Only your top content', 'cannyforge-archive' ) . '</div>';
		echo '</label>';

		// Hybrid Mode.
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
	 * Render the archive link-type checkboxes.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_link_types( Settings $settings ): void {
		$types = $settings->link_types();

		echo '<h2>' . esc_html__( 'Archive Link Types', 'cannyforge-archive' ) . '</h2>';
		$this->checkbox( 'link_title', __( 'Title (default)', 'cannyforge-archive' ), $types->title() );
		$this->checkbox( 'link_description', __( 'Description', 'cannyforge-archive' ), $types->description() );
		$this->checkbox( 'link_featured_image', __( 'Featured Image', 'cannyforge-archive' ), $types->featured_image() );
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
		$this->checkbox( 'filter_search', __( 'Search Box', 'cannyforge-archive' ), $filters->search() );
		$this->checkbox( 'filter_category', __( 'Category filters', 'cannyforge-archive' ), $filters->category() );
		$this->checkbox( 'filter_tag', __( 'Tag filters', 'cannyforge-archive' ), $filters->tag() );
		$this->checkbox( 'filter_month_year', __( 'Month + Year filters', 'cannyforge-archive' ), $filters->month_year() );
		$this->checkbox( 'filter_author', __( 'Author filters', 'cannyforge-archive' ), $filters->author() );
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
		$this->checkbox( 'target_category', __( 'Category archives', 'cannyforge-archive' ), $targeting->category() );
		$this->checkbox( 'target_tag', __( 'Tag archives', 'cannyforge-archive' ), $targeting->tag() );
		$this->checkbox( 'target_author', __( 'Author archives', 'cannyforge-archive' ), $targeting->author() );
		$this->checkbox( 'target_date', __( 'Date archives', 'cannyforge-archive' ), $targeting->date() );
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

		$this->checkbox( 'seo_index', __( 'Allow indexing (index)', 'cannyforge-archive' ), $seo->index() );
		$this->checkbox( 'seo_follow', __( 'Allow following links (follow)', 'cannyforge-archive' ), $seo->follow() );

		echo '<p><label>' . esc_html__( 'Canonical URL (optional)', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="url" name="seo_canonical" value="%s" placeholder="%s"></label></p>',
			esc_attr( $seo->canonical() ),
			esc_attr__( 'Defaults to the archive URL', 'cannyforge-archive' )
		);
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

		// Colours Modal.
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
	 * Render the mode-dependent right-hand panel.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_mode_panel( Settings $settings ): void {
		echo '<div class="cf-panel-news" style="margin-top: 1rem; border-top: 1px solid var(--cf-border); padding-top: 1rem;">';
		echo '<h2>' . esc_html__( 'News Cycle Settings', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'A list of posts published in the last <insert newscycle settings>.', 'cannyforge-archive' );
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
		echo '<p><textarea name="blog_urls" rows="8" cols="50">';
		echo esc_textarea( implode( "\n", $settings->blog_urls() ) );
		echo '</textarea></p>';

		echo '<p><label>' . esc_html__( 'Import URLs from CSV', 'cannyforge-archive' ) . '<br>';
		echo '<input type="file" name="blog_urls_csv" accept=".csv,text/csv"></label></p>';
		$this->checkbox(
			'blog_urls_csv_replace',
			__( 'Replace the list with the CSV (otherwise merge)', 'cannyforge-archive' ),
			false
		);
		echo '<p class="description">';
		echo esc_html__( 'The first URL-like value in each CSV row is imported.', 'cannyforge-archive' );
		echo '</p>';
		echo '</div>';
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

		$this->list_field( 'select_include_categories', __( 'Include categories', 'cannyforge-archive' ), $selection->include_categories() );
		$this->list_field( 'select_include_tags', __( 'Include tags', 'cannyforge-archive' ), $selection->include_tags() );
		$this->list_field( 'select_exclude_categories', __( 'Exclude categories', 'cannyforge-archive' ), $selection->exclude_categories() );
		$this->list_field( 'select_exclude_tags', __( 'Exclude tags', 'cannyforge-archive' ), $selection->exclude_tags() );
		$this->checkbox( 'select_exclude_noindex', __( 'Exclude noindex content', 'cannyforge-archive' ), $selection->exclude_noindex() );
		$this->list_field( 'select_pinned_urls', __( 'Pinned URLs (shown first)', 'cannyforge-archive' ), $selection->pinned_urls() );
	}

	/**
	 * Render a labelled textarea holding one value per line.
	 *
	 * @param string   $name   Field name.
	 * @param string   $label  Human label.
	 * @param string[] $values Current values.
	 * @return void
	 */
	private function list_field( string $name, string $label, array $values ): void {
		printf( '<p><label>%s<br>', esc_html( $label ) );
		printf(
			'<textarea name="%s" rows="3" cols="40">%s</textarea></label></p>',
			esc_attr( $name ),
			esc_textarea( implode( "\n", $values ) )
		);
	}

	/**
	 * Render a single labelled checkbox.
	 *
	 * @param string $name    Field name.
	 * @param string $label   Human label.
	 * @param bool   $checked Whether it is checked.
	 * @return void
	 */
	private function checkbox( string $name, string $label, bool $checked ): void {
		printf(
			'<p><label><input type="checkbox" name="%s" value="1" %s> %s</label></p>',
			esc_attr( $name ),
			checked( $checked, true, false ),
			esc_html( $label )
		);
	}
}

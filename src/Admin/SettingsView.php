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
	 * Plugin base URL, for the brand logo (empty = omit the logo).
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Construct the view.
	 *
	 * @param string $base_url Plugin base URL (trailing slash optional).
	 */
	public function __construct( string $base_url = '' ) {
		$this->base_url = '' !== $base_url ? rtrim( $base_url, '/' ) . '/' : '';
	}

	/**
	 * Render the whole settings page.
	 *
	 * @param Settings $settings  Current settings to populate the form with.
	 * @param string   $action_url The form post target.
	 * @return void
	 */
	public function render( Settings $settings, string $action_url ): void {
		echo '<div class="wrap cannyforge-archive-settings">';
		$this->render_brand_header();

		printf( '<form method="post" action="%s">', esc_url( $action_url ) );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		echo '<div class="cannyforge-archive-grid">';
		echo '<div class="cannyforge-archive-col">';
		$this->render_mode_and_pagination( $settings );
		$this->render_link_types( $settings );
		$this->render_filters( $settings );
		$this->render_targeting( $settings );
		$this->render_seo( $settings );
		echo '</div>';

		echo '<div class="cannyforge-archive-col">';
		$this->render_mode_panel( $settings );
		$this->render_content_selection( $settings );
		echo '</div>';
		echo '</div>';

		submit_button( __( 'Save', 'cannyforge-archive' ) );
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render the branded page header (logo + title).
	 *
	 * The CannyForge wordmark is shown when a base URL is configured; the page
	 * title always renders so the header is meaningful without the asset.
	 *
	 * @return void
	 */
	private function render_brand_header(): void {
		echo '<div class="cannyforge-archive-brand">';

		if ( '' !== $this->base_url ) {
			printf(
				'<img class="cannyforge-archive-brand__logo" src="%s" alt="%s">',
				esc_url( $this->base_url . 'assets/branding/cannyforge-font-dark.svg' ),
				esc_attr__( 'CannyForge', 'cannyforge-archive' )
			);
		}

		printf( '<h1>%s</h1>', esc_html__( 'HTML Sitemap Generator Settings', 'cannyforge-archive' ) );
		echo '</div>';
	}

	/**
	 * Render the mode toggle and pagination control.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_mode_and_pagination( Settings $settings ): void {
		$is_news = Mode::News === $settings->mode();

		echo '<h2>' . esc_html__( 'Mode', 'cannyforge-archive' ) . '</h2>';
		echo '<p><label><input type="radio" name="mode" value="blog" ' . checked( ! $is_news, true, false ) . '> ';
		echo esc_html__( 'Create Blog Sitemap', 'cannyforge-archive' ) . '</label></p>';
		echo '<p><label><input type="radio" name="mode" value="news" ' . checked( $is_news, true, false ) . '> ';
		echo esc_html__( 'Create News Sitemap', 'cannyforge-archive' ) . '</label></p>';

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
	 * Render the mode-dependent right-hand panel.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_mode_panel( Settings $settings ): void {
		if ( Mode::News === $settings->mode() ) {
			echo '<h2>' . esc_html__( 'News Sitemap Settings', 'cannyforge-archive' ) . '</h2>';
			echo '<p><label>' . esc_html__( 'Include content published in the last (hours)', 'cannyforge-archive' ) . ' ';
			printf(
				'<input type="number" min="1" name="news_window_hours" value="%d"></label></p>',
				absint( $settings->news_window_hours() )
			);
			return;
		}

		echo '<h2>' . esc_html__( 'Blog URLs to include', 'cannyforge-archive' ) . '</h2>';
		echo '<p><label>' . esc_html__( 'Include up to (URLs)', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="number" min="1" name="blog_max_urls" value="%d"></label></p>',
			absint( $settings->blog_max_urls() )
		);
		echo '<p><textarea name="blog_urls" rows="8" cols="50">';
		echo esc_textarea( implode( "\n", $settings->blog_urls() ) );
		echo '</textarea></p>';
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

<?php
/**
 * Renders the settings-form section bodies (theme, targeting, filters,
 * content selection, link types, SEO, pagination).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\PaginationStyle;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Contracts\Settings\Theme;

/**
 * Presentation-only renderer for the settings-page section bodies.
 *
 * Split out of SettingsView (ticket 611) to keep both classes under the
 * PHPMD length budget; this class owns only the accordion/tab body markup,
 * not the page shell (header, nav, footer, preview panel).
 */
final class SettingsSectionsView {
	/**
	 * Shared field renderer.
	 *
	 * @var FormFieldView
	 */
	private FormFieldView $fields;

	/**
	 * Construct the sections renderer.
	 *
	 * @param FormFieldView|null $fields Shared field renderer.
	 */
	public function __construct( ?FormFieldView $fields = null ) {
		$this->fields = $fields ?? new FormFieldView();
	}

	/**
	 * Render the pagination-only settings panel.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	public function render_pagination_only( Settings $settings ): void {
		echo '<p><label><strong>' . esc_html__( 'Leading Pagination Pages', 'cannyforge-archive' ) . '</strong><br>';
		printf( '<input type="number" min="1" name="pagination_limit" value="%d"></label></p>', absint( $settings->pagination_limit() ) );

		echo '<p><label><strong>' . esc_html__( 'Pagination Pattern', 'cannyforge-archive' ) . '</strong><br>';
		echo '<select name="pagination_style">';
		printf( '<option value="%s"%s>%s</option>', esc_attr( PaginationStyle::Leading->value ), selected( $settings->pagination_style()->value, PaginationStyle::Leading->value, false ), esc_html__( '1, 2, 3, Archive', 'cannyforge-archive' ) );
		printf( '<option value="%s"%s>%s</option>', esc_attr( PaginationStyle::LeadingWithTail->value ), selected( $settings->pagination_style()->value, PaginationStyle::LeadingWithTail->value, false ), esc_html__( '1, 2, penultimate, last, Archive', 'cannyforge-archive' ) );
		echo '</select></label></p>';

		echo '<p><label><strong>' . esc_html__( '"View Archive" link URL (optional)', 'cannyforge-archive' ) . '</strong><br>';
		printf( '<input type="url" name="archive_url" value="%s" placeholder="%s"></label></p>', esc_attr( $settings->archive_url() ), esc_attr__( 'Defaults to the archive page', 'cannyforge-archive' ) );
	}

	/**
	 * Render the front-end theming controls.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	public function render_theme( Settings $settings ): void {
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
	 * Render the archive-type targeting checkboxes (ticket 109).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	public function render_targeting( Settings $settings ): void {
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
	public function render_filters( Settings $settings ): void {
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
	public function render_content_selection( Settings $settings ): void {
		$selection  = $settings->content_selection();
		$categories = $this->term_name_options( 'category' );
		$tags       = $this->term_name_options( 'post_tag' );

		echo '<h2>' . esc_html__( 'Content Selection', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'Choose categories and tags from your site instead of typing term names manually.', 'cannyforge-archive' );
		echo '</p>';
		$this->fields->multiselect_field( 'select_include_categories', __( 'Include categories', 'cannyforge-archive' ), $categories, $selection->include_categories(), __( 'Only show entries that match at least one selected category.', 'cannyforge-archive' ) );
		$this->fields->multiselect_field( 'select_include_tags', __( 'Include tags', 'cannyforge-archive' ), $tags, $selection->include_tags(), __( 'Only show entries that match at least one selected tag.', 'cannyforge-archive' ) );
		$this->fields->multiselect_field( 'select_exclude_categories', __( 'Exclude categories', 'cannyforge-archive' ), $categories, $selection->exclude_categories(), __( 'Hide any entry that matches one of these categories.', 'cannyforge-archive' ) );
		$this->fields->multiselect_field( 'select_exclude_tags', __( 'Exclude tags', 'cannyforge-archive' ), $tags, $selection->exclude_tags(), __( 'Hide any entry that matches one of these tags.', 'cannyforge-archive' ) );
		$this->fields->checkbox( 'select_exclude_noindex', __( 'Exclude noindex content', 'cannyforge-archive' ), $selection->exclude_noindex() );
		$this->fields->list_field( 'select_pinned_urls', __( 'Pinned URLs (shown first)', 'cannyforge-archive' ), $selection->pinned_urls() );
	}

	/**
	 * Render the archive link-type checkboxes.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	public function render_link_types( Settings $settings ): void {
		$types = $settings->link_types();

		echo '<h2>' . esc_html__( 'Archive Link Types', 'cannyforge-archive' ) . '</h2>';
		$this->fields->checkbox( 'link_title', __( 'Title (default)', 'cannyforge-archive' ), $types->title() );
		$this->fields->checkbox( 'link_description', __( 'Description / snippet', 'cannyforge-archive' ), $types->description() );
		$this->fields->checkbox( 'link_featured_image', __( 'Featured Image', 'cannyforge-archive' ), $types->featured_image() );
		$this->fields->checkbox( 'link_categories', __( 'Categories', 'cannyforge-archive' ), $types->categories() );
		$this->fields->checkbox( 'link_tags', __( 'Tags', 'cannyforge-archive' ), $types->tags() );
		$this->fields->checkbox( 'link_author', __( 'Author', 'cannyforge-archive' ), $types->author() );
		$this->fields->checkbox( 'link_published_date', __( 'Published date', 'cannyforge-archive' ), $types->published_date() );
	}

	/**
	 * Render the SEO controls (ticket 110).
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	public function render_seo( Settings $settings ): void {
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

	/**
	 * Available term names for a taxonomy, for admin multi-select menus.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int, array{value: string, label: string}>
	 */
	private function term_name_options( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$options = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$options[] = array(
					'value' => (string) $term->name,
					'label' => (string) $term->name,
				);
			}
		}

		return $options;
	}
}

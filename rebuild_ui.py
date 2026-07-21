import re

php_file = "/home/user256/GitRepos/cannyforge_archive/src/Admin/SettingsView.php"

with open(php_file, "r") as f:
    content = f.read()

# Replace render method completely
start_str = "	public function render("
end_str = "	/**\n	 * Render the branded page header"
new_render = """	public function render(
		Settings $settings,
		string $action_url,
		string $preview_url = '',
		?GoogleSettings $google_settings = null,
		string $google_status = GoogleTokenStore::STATUS_DISCONNECTED,
		bool $google_secret_saved = false,
		string $google_connect_url = '',
		string $google_disconnect_url = '',
		string $google_notice = '',
		string $google_notice_type = GoogleConnectionController::NOTICE_ERROR
	): void {
		$google_settings = $google_settings ?? new GoogleSettings();

		echo '<div class="cf-app-container">';
		$this->render_brand_header( $preview_url );

		printf( '<form method="post" enctype="multipart/form-data" action="%s" class="cf-app-form">', esc_url( $action_url ) );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		echo '<div class="cf-app-body">';
		
		echo '<aside class="cf-app-sidebar">';
		echo '<ul class="cf-app-nav">';
		echo '<li class="active"><a href="#tab-content">' . esc_html__( 'Content', 'cannyforge-archive' ) . '</a></li>';
		echo '<li><a href="#tab-display">' . esc_html__( 'Display', 'cannyforge-archive' ) . '</a></li>';
		echo '<li><a href="#tab-pagination">' . esc_html__( 'Pagination', 'cannyforge-archive' ) . '</a></li>';
		echo '<li><a href="#tab-filters">' . esc_html__( 'Filters', 'cannyforge-archive' ) . '</a></li>';
		echo '<li><a href="#tab-seo">' . esc_html__( 'SEO', 'cannyforge-archive' ) . '</a></li>';
		echo '<li><a href="#tab-advanced">' . esc_html__( 'Advanced', 'cannyforge-archive' ) . '</a></li>';
		echo '</ul>';
		echo '</aside>';

		echo '<main class="cf-app-main">';

		echo '<div id="tab-content" class="cf-tab-section active">';
		echo '<div class="cf-section-header">';
		echo '<h2>' . esc_html__( 'Content', 'cannyforge-archive' ) . '</h2>';
		echo '<p>' . esc_html__( 'Choose what content to show in your archive.', 'cannyforge-archive' ) . '</p>';
		echo '</div>';
		$this->render_mode_only( $settings );
		$this->mode_panel->render( $settings, $google_settings, $google_status, $google_secret_saved, $google_connect_url, $google_disconnect_url, $google_notice, $google_notice_type );
		echo '<div class="cf-card" style="margin-top:24px;">';
		$this->render_content_selection( $settings );
		echo '</div>';
		echo '</div>';

		$this->render_accordion( 'display', __( 'Display', 'cannyforge-archive' ), __( 'Choose layout and what information to show for each article.', 'cannyforge-archive' ), function() use ( $settings ) {
			$this->render_theme( $settings );
			echo '<hr style="margin:24px 0;border:0;border-top:1px solid var(--cf-border);">';
			$this->render_link_types( $settings );
		} );

		$this->render_accordion( 'pagination', __( 'Pagination', 'cannyforge-archive' ), __( 'Configure pagination and where the archive link appears.', 'cannyforge-archive' ), function() use ( $settings ) {
			$this->render_pagination_only( $settings );
		} );

		$this->render_accordion( 'filters', __( 'Filters', 'cannyforge-archive' ), __( 'Control which archive types and user filters replace pagination.', 'cannyforge-archive' ), function() use ( $settings ) {
			$this->render_filters( $settings );
		} );

		$this->render_accordion( 'seo', __( 'SEO', 'cannyforge-archive' ), __( 'Set archive title and meta description.', 'cannyforge-archive' ), function() use ( $settings ) {
			$this->render_seo( $settings );
		} );

		$this->render_accordion( 'advanced', __( 'Advanced', 'cannyforge-archive' ), __( 'Additional options for fine control.', 'cannyforge-archive' ), function() use ( $settings ) {
			$this->render_targeting( $settings );
		} );

		echo '</main>';

		echo '<aside class="cf-app-preview">';
		echo '<div class="cf-preview-header">';
		echo '<h3>' . esc_html__( 'Live preview', 'cannyforge-archive' ) . '</h3>';
		echo '<p>' . esc_html__( 'This preview reflects your current settings.', 'cannyforge-archive' ) . '</p>';
		echo '</div>';
		echo '<div class="cf-preview-controls">';
		echo '<select><option>Desktop</option></select>';
		echo '<div class="cf-preview-icons"><span class="dashicons dashicons-desktop"></span><span class="dashicons dashicons-smartphone"></span></div>';
		echo '</div>';
		echo '<div class="cf-preview-frame">';
		echo '<iframe src="' . esc_url( $preview_url ) . '" title="Preview"></iframe>';
		echo '</div>';
		echo '</aside>';

		echo '</div>';

		echo '<footer class="cf-app-footer">';
		echo '<div class="cf-footer-status"><span class="dashicons dashicons-saved"></span> ' . esc_html__( 'All changes saved', 'cannyforge-archive' ) . '</div>';
		echo '<div class="cf-footer-actions">';
		echo '<button type="button" class="cf-btn cf-btn-text">' . esc_html__( 'Reset to defaults', 'cannyforge-archive' ) . '</button>';
		submit_button( __( 'Save changes', 'cannyforge-archive' ), 'primary cf-btn cf-btn-primary', 'submit', false, array( 'id' => 'cf-save-btn' ) );
		echo '</div>';
		echo '</footer>';

		echo '</form>';
		echo '</div>';
	}

"""

pattern_render = re.compile(re.escape(start_str) + r".*?" + r"(?=" + re.escape(end_str) + r")", re.DOTALL)
content = pattern_render.sub(new_render, content)

# Now replace render_brand_header
start_bh = "	private function render_brand_header"
end_bh = "	/**\n	 * Render the top mode-and-panel section."
new_bh = """	private function render_brand_header( string $preview_url ): void {
		echo '<header class="cf-app-header">';
		echo '<div class="cf-header-left">';
		echo '<h1>' . esc_html__( 'CannyForge Archive', 'cannyforge-archive' ) . '</h1>';
		echo '<span class="cf-badge">' . esc_html__( 'Draft changes', 'cannyforge-archive' ) . '</span>';
		echo '</div>';
		echo '<div class="cf-header-right">';
		if ( '' !== $preview_url ) {
			printf(
				'<a class="cf-btn cf-btn-outline" href="%s" target="_blank" rel="noopener noreferrer">%s <span class="dashicons dashicons-external" style="font-size:16px;margin-top:2px;margin-left:4px;"></span></a>',
				esc_url( $preview_url ),
				esc_html__( 'Preview', 'cannyforge-archive' )
			);
		}
		echo '<button type="submit" class="cf-btn cf-btn-primary" onclick="document.getElementById(\\'cf-save-btn\\').click();">' . esc_html__( 'Save changes', 'cannyforge-archive' ) . '</button>';
		echo '<button type="button" class="cf-btn cf-btn-icon"><span class="dashicons dashicons-ellipsis"></span></button>';
		echo '</div>';
		echo '</header>';
	}

	private function render_accordion( string $id, string $title, string $desc, callable $render_cb ): void {
		echo '<details class="cf-accordion" id="accordion-' . esc_attr( $id ) . '">';
		echo '<summary class="cf-accordion-summary">';
		echo '<div class="cf-accordion-title">';
		echo '<span class="dashicons dashicons-admin-generic"></span>';
		echo '<div><strong>' . esc_html( $title ) . '</strong><p>' . esc_html( $desc ) . '</p></div>';
		echo '</div>';
		echo '<div class="cf-accordion-status"><span class="dashicons dashicons-arrow-down-alt2"></span></div>';
		echo '</summary>';
		echo '<div class="cf-accordion-body">';
		$render_cb();
		echo '</div>';
		echo '</details>';
	}

"""
content = re.sub(re.escape(start_bh) + r".*?" + r"(?=" + re.escape(end_bh) + r")", new_bh, content, flags=re.DOTALL)

# Delete render_top_section and render_mode_and_pagination, replace with render_mode_only and render_pagination_only
start_top = "	private function render_top_section("
end_top = "	/**\n	 * Render the theme section."
new_mode_pag = """	private function render_mode_only( Settings $settings ): void {
		$mode = $settings->mode();
		echo '<div class="cf-card" style="margin-top:24px;">';
		echo '<h3 style="margin-top:0;">' . esc_html__( 'Archive style', 'cannyforge-archive' ) . '</h3>';
		echo '<div class="cf-mode-cards">';

		$this->render_mode_card( 'news', __( 'Latest posts', 'cannyforge-archive' ), __( 'Show recently published content', 'cannyforge-archive' ), 'dashicons-rss', Mode::News === $mode );
		$this->render_mode_card( 'blog', __( 'Curated archive', 'cannyforge-archive' ), __( 'Show selected evergreen content', 'cannyforge-archive' ), 'dashicons-bookmark', Mode::Blog === $mode );
		$this->render_mode_card( 'hybrid', __( 'Latest + curated', 'cannyforge-archive' ), __( 'Combine recent and selected content', 'cannyforge-archive' ), 'dashicons-networking', Mode::Hybrid === $mode );

		echo '</div>';
		echo '</div>';
	}

	private function render_mode_card( string $value, string $title, string $desc, string $icon, bool $checked ): void {
		$class = $checked ? 'cf-mode-card active' : 'cf-mode-card';
		echo '<label class="' . esc_attr( $class ) . '">';
		echo '<input type="radio" name="mode" value="' . esc_attr( $value ) . '" ' . checked( $checked, true, false ) . ' style="display:none;">';
		echo '<div class="cf-mode-card-header">';
		echo '<div class="cf-radio-circle">' . ( $checked ? '<div class="cf-radio-dot"></div>' : '' ) . '</div>';
		if ( $checked ) echo '<div class="cf-check-badge"><span class="dashicons dashicons-yes"></span></div>';
		echo '</div>';
		echo '<div class="cf-mode-card-icon"><span class="dashicons ' . esc_attr( $icon ) . '"></span></div>';
		echo '<h4>' . esc_html( $title ) . '</h4>';
		echo '<p>' . esc_html( $desc ) . '</p>';
		echo '</label>';
	}

	private function render_pagination_only( Settings $settings ): void {
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

"""
content = re.sub(re.escape(start_top) + r".*?" + r"(?=" + re.escape(end_top) + r")", new_mode_pag, content, flags=re.DOTALL)

# Delete render_theme_section entirely
start_ts = "	private function render_theme_section("
end_ts = "	/**\n	 * Render the front-end theming controls."
content = re.sub(re.escape(start_ts) + r".*?" + r"(?=" + re.escape(end_ts) + r")", "", content, flags=re.DOTALL)

# Delete render_settings_grid entirely
start_rsg = "	private function render_settings_grid("
end_rsg = "	/**\n	 * Render the archive-type targeting checkboxes (ticket 109)."
content = re.sub(re.escape(start_rsg) + r".*?" + r"(?=" + re.escape(end_rsg) + r")", "", content, flags=re.DOTALL)

with open(php_file, "w") as f:
    f.write(content)

print("SettingsView.php updated")

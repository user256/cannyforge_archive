<?php
/**
 * Tests for the distributable plugin ZIP contents (ticket 611).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Packaging;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Builds the distributable ZIP via scripts/install-plugin.sh and asserts it
 * ships only the allowed runtime file/pattern list.
 *
 * This guards against the ticket 611 regression: a dev-only helper script
 * (`rebuild_ui.py`) leaked into the published 0.1.1 ZIP because nothing
 * checked the staged output against an allow list. `.distignore` is a
 * blocklist that is easy to forget to update; this test is the fail-closed
 * counterpart that catches any future leak regardless of what caused it.
 */
class DistributablePackageTest extends TestCase {
	/**
	 * Path prefixes (relative to the plugin slug directory inside the ZIP)
	 * that are allowed to ship, along with everything under them.
	 *
	 * @var string[]
	 */
	private const ALLOWED_PREFIXES = array(
		'assets/',
		'src/',
	);

	/**
	 * Exact top-level files allowed to ship.
	 *
	 * @var string[]
	 */
	private const ALLOWED_FILES = array(
		'autoload.php',
		'cannyforge-archive.php',
		'readme.txt',
		'uninstall.php',
	);

	/**
	 * Named leak categories the 2026-07-21 audit found (or could plausibly
	 * recur), checked by regex against the full list of ZIP entries. Kept
	 * separate from the allow-list check so a failure names the category.
	 *
	 * @var array<string, string>
	 */
	private const FORBIDDEN_CATEGORY_PATTERNS = array(
		'Python scripts'     => '/\.py$/m',
		'PHPUnit tests'      => '#(^|/)tests/#m',
		'Dev/tool caches'    => '/\.(cache|phpunit\.result\.cache)$/m',
		'Environment files'  => '/(^|\/)\.env(\..+)?$/m',
		'Ticket files'       => '#(^|/)tickets/#m',
		'Tool configuration' => '/(composer\.(json|lock)|phpcs\.xml(\.dist)?|phpstan.*\.neon(\.dist)?|phpmd\.xml|rector\.php|phparkitect\.php|phpinsights\.php|infection\.json5|deptrac\.yaml)$/m',
		'VCS/CI metadata'    => '#(^|/)\.(git|github)(/|$)#m',
	);

	/**
	 * Absolute path to the built ZIP, cached across the tests in this class.
	 *
	 * @var string|null
	 */
	private static ?string $zip_path = null;

	/**
	 * The staged ZIP contains only the allowed runtime files.
	 *
	 * @return void
	 */
	public function test_zip_contains_only_allowed_runtime_files(): void {
		$entries = $this->zip_entries( $this->built_zip_path() );
		$this->assertNotEmpty( $entries, 'The built ZIP has no entries.' );

		$unexpected = array_values(
			array_filter(
				$entries,
				fn ( string $entry ): bool => ! $this->is_allowed_entry( $entry )
			)
		);

		$this->assertSame(
			array(),
			$unexpected,
			"Unexpected files leaked into the distributable ZIP:\n" . implode( "\n", $unexpected )
		);
	}

	/**
	 * Uninstall.php ships in the distributable ZIP (ticket 606): without it,
	 * deleting the plugin through the Plugins screen leaves every option,
	 * transient, and encrypted Google credential behind.
	 *
	 * @return void
	 */
	public function test_zip_ships_uninstall_php(): void {
		$entries = $this->zip_entries( $this->built_zip_path() );

		$this->assertContains(
			'cannyforge-archive/uninstall.php',
			$entries,
			'uninstall.php did not ship in the distributable ZIP.'
		);
	}

	/**
	 * The staged ZIP contains none of the known dev-only leak categories.
	 *
	 * @return void
	 */
	public function test_zip_excludes_known_leak_categories(): void {
		$entries  = $this->zip_entries( $this->built_zip_path() );
		$haystack = implode( "\n", $entries );

		foreach ( self::FORBIDDEN_CATEGORY_PATTERNS as $label => $pattern ) {
			$this->assertDoesNotMatchRegularExpression(
				$pattern,
				$haystack,
				"{$label} leaked into the distributable ZIP."
			);
		}
	}

	/**
	 * Every PHP file staged into the ZIP is syntactically valid.
	 *
	 * @return void
	 */
	public function test_staged_php_files_pass_lint(): void {
		$this->built_zip_path();

		$staged_dir = $this->repo_root() . '/dist/cannyforge-archive';
		$this->assertDirectoryExists( $staged_dir, 'Staged plugin directory not found: ' . $staged_dir );

		$php_files = $this->php_files_under( $staged_dir );
		$this->assertNotEmpty( $php_files, 'No staged PHP files found to lint.' );

		foreach ( $php_files as $php_file ) {
			$output      = array();
			$result_code = 0;
			exec( 'php -l ' . escapeshellarg( $php_file ) . ' 2>&1', $output, $result_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- test-only: lints the staged dist output.

			$this->assertSame(
				0,
				$result_code,
				"php -l failed for {$php_file}:\n" . implode( "\n", $output )
			);
		}
	}

	/**
	 * Build the distributable ZIP once per test run and return its path.
	 *
	 * @return string
	 */
	private function built_zip_path(): string {
		if ( null !== self::$zip_path ) {
			return self::$zip_path;
		}

		$root_dir = $this->repo_root();
		$script   = $root_dir . '/scripts/install-plugin.sh';
		$this->assertFileExists( $script, 'Packaging script not found: ' . $script );

		$output      = array();
		$result_code = 0;
		exec( 'bash ' . escapeshellarg( $script ) . ' --build-only 2>&1', $output, $result_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- test-only: builds the dist ZIP via the existing packaging script.

		$this->assertSame( 0, $result_code, "Packaging build failed:\n" . implode( "\n", $output ) );

		$version  = $this->plugin_version( $root_dir );
		$zip_path = $root_dir . '/dist/cannyforge-archive-' . $version . '.zip';
		$this->assertFileExists( $zip_path, 'Built ZIP not found: ' . $zip_path );

		self::$zip_path = $zip_path;

		return $zip_path;
	}

	/**
	 * List every entry name inside a ZIP.
	 *
	 * @param string $zip_path Absolute path to the ZIP.
	 * @return string[]
	 */
	private function zip_entries( string $zip_path ): array {
		$zip = new ZipArchive();
		$this->assertTrue( true === $zip->open( $zip_path ), 'Could not open ZIP: ' . $zip_path );

		$entries = array();
		for ( $i = 0; $i < $zip->numFiles; $i++ ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- ZipArchive::$numFiles is a built-in ext-zip property name.
			$name = $zip->getNameIndex( $i );
			if ( false !== $name ) {
				$entries[] = $name;
			}
		}

		$zip->close();

		return $entries;
	}

	/**
	 * Whether a ZIP entry matches the allowed runtime file/pattern list.
	 *
	 * @param string $entry Full ZIP entry name (includes the plugin slug dir).
	 * @return bool
	 */
	private function is_allowed_entry( string $entry ): bool {
		$relative = preg_replace( '#^cannyforge-archive/#', '', $entry );
		if ( null === $relative || '' === $relative ) {
			// The top-level plugin directory entry itself.
			return true;
		}

		if ( in_array( $relative, self::ALLOWED_FILES, true ) ) {
			return true;
		}

		foreach ( self::ALLOWED_PREFIXES as $prefix ) {
			if ( 0 === strpos( $relative, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Recursively list PHP files under a directory.
	 *
	 * @param string $dir Directory to scan.
	 * @return string[]
	 */
	private function php_files_under( string $dir ): array {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
		);

		$files = array();
		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
				$files[] = $file->getPathname();
			}
		}

		return $files;
	}

	/**
	 * Detect the plugin version from the main plugin file header.
	 *
	 * @param string $root_dir Repository root.
	 * @return string
	 */
	private function plugin_version( string $root_dir ): string {
		$main_file = $root_dir . '/cannyforge-archive.php';
		$this->assertFileExists( $main_file, 'Main plugin file not found: ' . $main_file );

		$contents = (string) file_get_contents( $main_file );
		$matches  = array();
		$this->assertSame(
			1,
			preg_match( '/^\s*\*\s*Version:\s*(.+)$/m', $contents, $matches ),
			'Could not detect plugin version from ' . $main_file
		);

		return trim( $matches[1] );
	}

	/**
	 * The repository root (two levels above this test file).
	 *
	 * @return string
	 */
	private function repo_root(): string {
		return dirname( __DIR__, 2 );
	}
}

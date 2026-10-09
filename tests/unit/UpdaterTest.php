<?php

namespace ILT\Tests;

use ILT_Updater;

class UpdaterTest extends TestCase {

	private function release( $tag, $extra = array() ) {
		return array_merge(
			array(
				'tag_name'     => $tag,
				'html_url'     => 'https://github.com/LordArma/Iranian-League-Table/releases/tag/' . $tag,
				'body'         => "Changes <script>x</script>",
				'published_at' => '2026-10-09T00:00:00Z',
				'assets'       => array(
					array( 'name' => 'source.zip', 'browser_download_url' => 'https://github.com/LordArma/Iranian-League-Table/archive/x.zip' ),
					array( 'name' => 'iranian-league-table-' . ltrim( $tag, 'v' ) . '.zip', 'browser_download_url' => 'https://github.com/LordArma/Iranian-League-Table/releases/download/' . $tag . '/iranian-league-table-' . ltrim( $tag, 'v' ) . '.zip' ),
				),
			),
			$extra
		);
	}

	public function test_newer_release_is_offered() {
		$GLOBALS['ilt_github_release'] = $this->release( 'v99.0.0' );
		$update = ILT_Updater::check( false, array(), plugin_basename( ILT_PLUGIN_FILE ) );
		$this->assertSame( '99.0.0', $update['version'] );
		$this->assertStringEndsWith( '/iranian-league-table-99.0.0.zip', $update['package'] );
	}

	public function test_same_version_has_no_package() {
		$GLOBALS['ilt_github_release'] = $this->release( 'v' . ILT_VERSION );
		$update = ILT_Updater::check( false, array(), plugin_basename( ILT_PLUGIN_FILE ) );
		$this->assertSame( '', $update['package'] );
	}

	public function test_other_plugins_untouched() {
		$this->assertSame( 'x', ILT_Updater::check( 'x', array(), 'hello.php' ) );
	}

	public function test_rejects_drafts_prereleases_bad_tags_and_foreign_assets() {
		$this->assertNull( ILT_Updater::parse_release( $this->release( 'v9.0.0', array( 'prerelease' => true ) ) ) );
		$this->assertNull( ILT_Updater::parse_release( $this->release( 'nightly' ) ) );
		$foreign = $this->release( 'v9.0.0' );
		$foreign['assets'][1]['browser_download_url'] = 'https://evil.example/iranian-league-table-9.0.0.zip';
		$this->assertNull( ILT_Updater::parse_release( $foreign ) );
	}

	public function test_result_is_cached() {
		$GLOBALS['ilt_github_release'] = $this->release( 'v99.0.0' );
		ILT_Updater::latest_release();
		$GLOBALS['ilt_http_calls'] = array();
		ILT_Updater::latest_release();
		$this->assertSame( array(), $GLOBALS['ilt_http_calls'] );
	}

	public function test_details_popup_escapes_notes() {
		$GLOBALS['ilt_github_release'] = $this->release( 'v99.0.0' );
		$info = ILT_Updater::info( false, 'plugin_information', (object) array( 'slug' => 'iranian-league-table' ) );
		$this->assertStringContainsString( '&lt;script&gt;', $info->sections['changelog'] );
		$this->assertFalse( ILT_Updater::info( false, 'plugin_information', (object) array( 'slug' => 'other' ) ) );
	}
}

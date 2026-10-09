<?php
/**
 * Integration tests against a real WordPress.
 */

class PluginTest extends WP_UnitTestCase {

	/** @var string[] */
	private $requests = array();

	public function set_up() {
		parent::set_up();
		$this->requests = array();
		add_filter( 'pre_http_request', array( $this, 'mock_http' ), 10, 3 );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'mock_http' ), 10 );
		parent::tear_down();
	}

	public function mock_http( $pre, $args, $url ) {
		$this->requests[] = $url;
		$map = array( '/leagues/6/' => 'persiangulf', '/leagues/24/' => 'azadegan', '/leagues/54/' => 'kowsar' );
		foreach ( $map as $needle => $slug ) {
			if ( false !== strpos( $url, $needle ) ) {
				return array(
					'headers'  => array( 'content-type' => 'application/json' ),
					'body'     => file_get_contents( ILT_TEST_FIXTURES . '/' . $slug . '.json' ),
					'response' => array( 'code' => 200, 'message' => 'OK' ),
					'cookies'  => array(),
					'filename' => null,
				);
			}
		}
		return array(
			'headers'  => array( 'content-type' => 'image/png' ),
			'body'     => "\x89PNG test",
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	private function rest( $method, $route, $body = null, $user = 0 ) {
		wp_set_current_user( $user );
		$request = new WP_REST_Request( $method, $route );
		if ( null !== $body ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );
		}
		return rest_do_request( $request );
	}

	public function test_shortcode_renders_and_caches() {
		$html = do_shortcode( '[iran_league league="azadegan" mode="advanced"]' );
		$this->assertStringContainsString( 'class="il-table ilt ilt--advanced ilt--azadegan"', $html );
		$this->assertSame( 17, substr_count( $html, '<tr' ) );
		$this->assertTrue( wp_style_is( 'ilt-style', 'enqueued' ) );

		$this->requests = array();
		do_shortcode( '[iran_league league="azadegan"]' );
		$this->assertSame( array(), $this->requests, 'second render is served from the cache' );
	}

	public function test_widget_renders() {
		ob_start();
		the_widget( 'Iranian_League_Widget', array( 'league' => 'kowsar', 'title' => 'T' ), array( 'before_title' => '<h2>', 'after_title' => '</h2>' ) );
		$html = ob_get_clean();
		$this->assertStringContainsString( '<h2>T</h2>', $html );
		$this->assertStringContainsString( 'ilt--kowsar', $html );
	}

	public function test_block_renders() {
		$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( 'iranian-league-table/standings' ) );
		$html = do_blocks( '<!-- wp:iranian-league-table/standings {"options":{"league":"kowsar","mode":"advanced"}} /-->' );
		$this->assertStringContainsString( 'wp-block-iranian-league-table-standings', $html );
		$this->assertStringContainsString( 'ilt--advanced ilt--kowsar', $html );
	}

	public function test_rest_preview_permissions() {
		$this->assertSame( 401, $this->rest( 'POST', '/ilt/v1/preview', array( 'values' => array() ) )->get_status() );
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->assertSame( 403, $this->rest( 'POST', '/ilt/v1/preview', array( 'values' => array() ), $subscriber )->get_status() );
		$contributor = self::factory()->user->create( array( 'role' => 'contributor' ) );
		$response    = $this->rest( 'POST', '/ilt/v1/preview', array( 'values' => array( 'league' => '../../x', 'mode' => 'advanced' ) ), $contributor );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'persiangulf', $response->get_data()['values']['league'] );
		$this->assertSame( '[iran_league mode="advanced"]', $response->get_data()['shortcode'] );
	}

	public function test_saved_table_crud_and_permissions() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$create = $this->rest( 'POST', '/ilt/v1/tables', array( 'title' => 'Sidebar', 'values' => array( 'league' => 'kowsar', 'rows' => '5' ) ), $editor );
		$this->assertSame( 201, $create->get_status() );
		$id = $create->get_data()['id'];
		$this->assertSame( '[iran_league id="' . $id . '"]', $create->get_data()['shortcode'] );

		$html = do_shortcode( '[iran_league id="' . $id . '" mode="advanced"]' );
		$this->assertStringContainsString( 'ilt--advanced ilt--kowsar', $html );
		$this->assertSame( 5 + 1, substr_count( $html, '<tr' ) );

		$update = $this->rest( 'POST', '/ilt/v1/tables/' . $id, array( 'title' => 'Renamed', 'values' => array( 'league' => 'azadegan' ) ), $editor );
		$this->assertSame( 'Renamed', $update->get_data()['title'] );
		$this->assertSame( 'azadegan', ILT_Tables::get( $id )['values']['league'] );

		$contributor = self::factory()->user->create( array( 'role' => 'contributor' ) );
		$this->assertSame( 403, $this->rest( 'POST', '/ilt/v1/tables/' . $id, array( 'values' => array() ), $contributor )->get_status() );
		$this->assertSame( 403, $this->rest( 'DELETE', '/ilt/v1/tables/' . $id, null, $contributor )->get_status() );

		$this->assertSame( 200, $this->rest( 'DELETE', '/ilt/v1/tables/' . $id, null, $editor )->get_status() );
		$this->assertNull( ILT_Tables::get( $id ) );
	}

	public function test_usage_count() {
		$id = ILT_Tables::save( 'T', array() );
		self::factory()->post->create( array( 'post_content' => 'a [iran_league id="' . $id . '"] b [iran_league id="' . $id . '" mode="basic"]' ) );
		self::factory()->post->create( array( 'post_content' => '[iran_league id="' . ( $id + 1000 ) . '"]' ) );
		$usage = ILT_Tables::usage();
		$this->assertSame( 1, $usage[ $id ] );
	}

	public function test_cron_refresh_and_logo_sync() {
		do_action( ILT_Api::REFRESH_HOOK, 'kowsar' );
		$this->assertNotNull( ILT_Api::last_good( 'kowsar' ) );
		do_action( ILT_Logos::SYNC_HOOK, 'kowsar' );
		$this->assertGreaterThan( 0, ILT_Logos::count() );
		$this->assertStringStartsWith( 'http://example.org/ilt-it-uploads/iranian-league-table/', ILT_Logos::url( ILT_Api::last_good( 'kowsar' )['data']->teams[0]->logo ) );
	}

	public function test_unknown_league_notice_only_for_editors() {
		$this->assertStringNotContainsString( 'ilt-editor-notice', do_shortcode( '[iran_league league="xyz"]' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );
		$this->assertStringContainsString( 'ilt-editor-notice', do_shortcode( '[iran_league league="xyz"]' ) );
	}

	/**
	 * Runs last (name order is not guaranteed, so it restores what it needs itself).
	 */
	public function test_uninstall_removes_everything() {
		do_shortcode( '[iran_league league="kowsar"]' );
		do_action( ILT_Logos::SYNC_HOOK, 'kowsar' );
		update_option( 'ilt_settings', array( 'cache_ttl' => 600 ) );
		$table = ILT_Tables::save( 'T', array() );
		$site  = null;
		if ( is_multisite() ) {
			$site = self::factory()->blog->create();
			switch_to_blog( $site );
			update_option( 'ilt_settings', array( 'cache_ttl' => 600 ) );
			update_option( 'ilt_last_good_kowsar', array( 'time' => 1 ) );
			restore_current_blog();
		}

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'iranian-league-table/iranianleaguetable.php' );
		}
		include WP_PLUGIN_DIR . '/iranian-league-table/uninstall.php';

		$this->assertFalse( get_option( 'ilt_settings' ) );
		$this->assertFalse( get_option( 'ilt_last_good_kowsar' ) );
		$this->assertFalse( get_transient( 'ilt_standings_kowsar' ) );
		$this->assertNull( get_post( $table ) );
		$this->assertDirectoryDoesNotExist( ILT_Logos::dir() );
		if ( $site ) {
			switch_to_blog( $site );
			$this->assertFalse( get_option( 'ilt_settings' ) );
			$this->assertFalse( get_option( 'ilt_last_good_kowsar' ) );
			restore_current_blog();
		}
	}
}

<?php
/**
 * Tests for RIWTH_Was_This_Helpful bootstrap: singleton, schema, activation, upgrade.
 *
 * @package RIWTH
 */

class Test_RIWTH_Install_Activation extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Was_This_Helpful
	 */
	private $instance;

	public function set_up() {
		parent::set_up();
		$this->instance = RIWTH_Was_This_Helpful::get_instance();
	}

	public function test_get_instance_returns_same_singleton() {
		$a = RIWTH_Was_This_Helpful::get_instance();
		$b = RIWTH_Was_This_Helpful::get_instance();

		$this->assertSame( $a, $b );
	}

	public function test_clone_triggers_doing_it_wrong() {
		$this->setExpectedIncorrectUsage( '__clone' );
		clone $this->instance;
	}

	public function test_wakeup_triggers_doing_it_wrong() {
		$this->setExpectedIncorrectUsage( '__wakeup' );
		$this->instance->__wakeup();
	}

	public function test_define_constants() {
		$this->assertSame( 'riwth_helpful_feedback', RIWTH_DB_NAME );
		$this->assertNotEmpty( RIWTH_PLUGIN_DIR );
		$this->assertNotEmpty( RIWTH_PLUGIN_URL );
		$this->assertStringEndsWith( '/', RIWTH_PLUGIN_DIRNAME );
	}

	// -----------------------------------------------------------------
	// create_database()
	// -----------------------------------------------------------------

	public function test_create_database_creates_table() {
		global $wpdb;
		$table = $wpdb->prefix . RIWTH_DB_NAME;

		$this->assertSame( $table, $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) );
	}

	public function test_create_database_expected_columns() {
		global $wpdb;
		$table = $wpdb->prefix . RIWTH_DB_NAME;

		$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table}", ARRAY_A );
		$by_name = array();
		foreach ( $columns as $column ) {
			$by_name[ $column['Field'] ] = $column;
		}

		$this->assertArrayHasKey( 'id', $by_name );
		$this->assertArrayHasKey( 'post_id', $by_name );
		$this->assertArrayHasKey( 'helpful', $by_name );
		$this->assertArrayHasKey( 'created_at', $by_name );

		$this->assertStringContainsString( 'bigint', $by_name['post_id']['Type'] );
		$this->assertStringContainsString( 'tinyint', $by_name['helpful']['Type'] );
	}

	public function test_create_database_expected_indexes() {
		global $wpdb;
		$table = $wpdb->prefix . RIWTH_DB_NAME;

		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table}", ARRAY_A );
		$by_key  = array();
		foreach ( $indexes as $index ) {
			$by_key[ $index['Key_name'] ] = $index;
		}

		$this->assertArrayHasKey( 'PRIMARY', $by_key );
		$this->assertArrayHasKey( 'post_id', $by_key );
	}

	public function test_create_database_is_idempotent() {
		$this->instance->create_database();
		$this->instance->create_database();

		global $wpdb;
		$table = $wpdb->prefix . RIWTH_DB_NAME;
		$this->assertSame( $table, $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) );
	}

	// -----------------------------------------------------------------
	// maybe_upgrade_db()
	// -----------------------------------------------------------------

	public function test_maybe_upgrade_db_runs_when_option_missing() {
		delete_option( 'riwth_db_version' );

		$this->instance->maybe_upgrade_db();

		$this->assertSame( RIWTH_DB_VERSION, get_option( 'riwth_db_version' ) );
	}

	public function test_maybe_upgrade_db_runs_when_version_stale() {
		update_option( 'riwth_db_version', '0.9' );

		$this->instance->maybe_upgrade_db();

		$this->assertSame( RIWTH_DB_VERSION, get_option( 'riwth_db_version' ) );
	}

	public function test_maybe_upgrade_db_is_noop_when_current() {
		update_option( 'riwth_db_version', RIWTH_DB_VERSION );

		$this->instance->maybe_upgrade_db();

		$this->assertSame( RIWTH_DB_VERSION, get_option( 'riwth_db_version' ) );
	}

	// -----------------------------------------------------------------
	// set_initial_settings()
	// -----------------------------------------------------------------

	public function test_set_initial_settings_sets_defaults_when_unset() {
		delete_option( 'riwth_display_on' );

		$this->instance->set_initial_settings();

		$this->assertSame( array( 'post' ), get_option( 'riwth_display_on' ) );
	}

	public function test_set_initial_settings_never_clobbers_existing_value() {
		update_option( 'riwth_display_on', array( 'page' ) );

		$this->instance->set_initial_settings();

		$this->assertSame( array( 'page' ), get_option( 'riwth_display_on' ) );
	}

	// -----------------------------------------------------------------
	// activate_plugin()
	// -----------------------------------------------------------------

	public function test_activate_plugin_runs_all_three_steps() {
		delete_option( 'riwth_db_version' );
		delete_option( 'riwth_display_on' );

		$this->instance->activate_plugin();

		global $wpdb;
		$table = $wpdb->prefix . RIWTH_DB_NAME;
		$this->assertSame( $table, $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) );
		$this->assertSame( array( 'post' ), get_option( 'riwth_display_on' ) );
		$this->assertSame( RIWTH_DB_VERSION, get_option( 'riwth_db_version' ) );
	}

	// -----------------------------------------------------------------
	// add_settings_link()
	// -----------------------------------------------------------------

	public function test_add_settings_link_prepends_settings_link() {
		$result = $this->instance->add_settings_link( array( 'deactivate' => 'Deactivate' ) );

		$this->assertStringContainsString( 'Settings', $result[0] );
		$this->assertSame( 'Deactivate', $result['deactivate'] );
	}

	// -----------------------------------------------------------------
	// riwth_loaded action
	// -----------------------------------------------------------------

	public function test_riwth_loaded_action_fired() {
		// Fired once already during the shared test bootstrap's plugins_loaded.
		$this->assertGreaterThanOrEqual( 1, did_action( 'riwth_loaded' ) );
	}
}

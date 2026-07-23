<?php
defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php
	// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template included inside a class method; variables are method-scoped, not global.
	$tabs = array(
		'tab-general'      => __( 'General', 'riaco-was-this-helpful' ),
		'tab-feedback-box' => __( 'Feedback Box', 'riaco-was-this-helpful' ),
		'tab-extra'        => __( 'Extra', 'riaco-was-this-helpful' ),
	);

	/**
	 * Filter the settings page tabs. A tab added here becomes selectable
	 * automatically; sections/fields targeting it are registered via the
	 * 'riwth_settings_sections' / 'riwth_settings_fields' filters using
	 * 'tab' => 'riwth-settings-' . $tab_key.
	 *
	 * @param array $tabs Tab-key => label.
	 */
	$tabs = apply_filters( 'riwth_settings_tabs', $tabs );

	// Default to first tab
	$current_tab = array_key_first( $tabs );

	// Only process tab parameter if it's a valid tab; this only selects which
	// section to display, it doesn't process a submission, so no nonce is needed.
	if ( isset( $_GET['tab'] ) ) {
		$requested_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab routing, validated against the $tabs allowlist below.

		if ( array_key_exists( $requested_tab, $tabs ) ) {
			$current_tab = $requested_tab;
		}
	}

	?>
	<form method="post" action="options.php">
		<nav class="nav-tab-wrapper">
			<?php
			foreach ( $tabs as $tab_key => $tab_label ) {
				// CSS class for a current tab
				$current = $tab_key === $current_tab ? ' nav-tab-active' : '';
				// URL
				$tab_url = add_query_arg(
					array(
						'page' => 'riwth-settings',
						'tab'  => sanitize_key( $tab_key ),
					),
					''
				);
				// printing the tab link
				echo '<a class="nav-tab' . esc_attr( $current ) . '" href="' . esc_url( $tab_url ) . '">' . esc_html( $tab_label ) . '</a>';
			}
			?>
		</nav>

		<?php
		settings_fields( 'riwth-settings-' . sanitize_key( $current_tab ) );
		do_settings_sections( 'riwth-settings-' . sanitize_key( $current_tab ) );
		submit_button();
		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		?>
	</form>
</div>

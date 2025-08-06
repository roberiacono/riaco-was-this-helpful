<?php
defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
<?php
$tabs = array(
	'tab-general'      => __( 'General', 'riaco-was-this-helpful' ),
	'tab-feedback-box' => __( 'Feedback Box', 'riaco-was-this-helpful' ),
	'tab-extra'        => __( 'Extra', 'riaco-was-this-helpful' ),
);

// Default to first tab
$current_tab = array_key_first( $tabs );

// Only process tab parameter if it's a valid tab and nonce is verified
if ( isset( $_GET['tab'] ) && isset( $_GET['_wpnonce'] ) ) {
	$nonce_action = 'riaco_was_this_helpful_tab_switch';

	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action ) ) {
		$requested_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );

		if ( array_key_exists( $requested_tab, $tabs ) ) {
			$current_tab = $requested_tab;
		}
	}
}

?>
<form method="post" action="options.php">
	<nav class="nav-tab-wrapper">
		<?php
		$nonce_action = 'riaco_was_this_helpful_tab_switch';
		foreach ( $tabs as $tab_key => $tab_label ) {
			// CSS class for a current tab
			$current = $tab_key === $current_tab ? ' nav-tab-active' : '';
			// URL
			$tab_url = add_query_arg(
				array(
					'page'     => 'riwth-settings',
					'tab'      => esc_attr( $tab_key ),
					'_wpnonce' => wp_create_nonce( $nonce_action ),
				),
				''
			);
			// printing the tab link
			echo '<a class="nav-tab' . esc_attr( $current ) . '" href="' . esc_url( $tab_url ) . '">' . esc_html( $tab_label ) . '</a>';
		}
		?>
	</nav>

	<?php
	settings_fields( 'riwth-settings-' . esc_attr( $current_tab ) );
	do_settings_sections( 'riwth-settings-' . esc_attr( $current_tab ) );
	submit_button();
	?>
</form>
</div>

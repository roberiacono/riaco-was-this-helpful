<?php
defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
<?php
$tabs        = array(
	'tab-general'      => __( 'General', 'riaco-was-this-helpful' ),
	'tab-feedback-box' => __( 'Feedback Box', 'riaco-was-this-helpful' ),
	'tab-extra'        => __( 'Extra', 'riaco-was-this-helpful' ),
);
$current_tab = isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : array_key_first( $tabs );

// Make sure the selected value is valid
if ( ! array_key_exists( $current_tab, $tabs ) ) {
	$current_tab = array_key_first( $tabs );
}

?>
<form method="post" action="options.php">
<nav class="nav-tab-wrapper">
	<?php
	foreach ( $tabs as $tab => $name ) {
		// CSS class for a current tab
		$current = $tab === $current_tab ? ' nav-tab-active' : '';
		// URL
		$url = add_query_arg(
			array(
				'page' => 'riwth-settings',
				'tab'  => esc_attr( $tab ),
			),
			''
		);
		// printing the tab link
		echo '<a class="nav-tab' . esc_attr( $current ) . '" href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
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

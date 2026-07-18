<?php
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template included inside a class method; variables are method-scoped, not global.
// $this is the RIWTH_Admin_Feedback_List instance from render_page() (require_once runs in
// the calling method's scope) — reuse it instead of constructing a second, throwaway instance.
$feedback_list = $this;
// Constructed on load-{hook} (setup_list_table()), not here — WP_Screen's own
// screen-options rendering during admin-header.php needs the list table's
// column filter registered before it runs; see setup_list_table()'s docblock.
$list_table = $feedback_list->get_list_table();
$list_table->prepare_items();

$filtered_total = (int) $list_table->get_pagination_arg( 'total_items' );

$export_url = wp_nonce_url(
	add_query_arg( array( 'page' => 'riwth-feedback-list', 'export' => 'csv' ), admin_url( 'admin.php' ) ),
	'riwth_export_csv'
);
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div style="margin: 1em 0; display: flex; gap: 0.5em; align-items: center;">
		<a href="<?php echo esc_url( $export_url ); ?>" class="button">
			<?php esc_html_e( 'Export CSV', 'riaco-was-this-helpful' ); ?>
		</a>
		<span style="color:#555;">
			<?php
			/* translators: %d: number of records */
			echo esc_html( sprintf( _n( '%d record', '%d records', $filtered_total, 'riaco-was-this-helpful' ), $filtered_total ) );
			?>
		</span>
	</div>

	<form method="get" id="riwth-feedback-list-form">
		<input type="hidden" name="page" value="riwth-feedback-list" />
		<?php
		$list_table->search_box( __( 'Search Posts', 'riaco-was-this-helpful' ), 'riwth-feedback' );
		$list_table->display();
		?>
	</form>
</div>
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

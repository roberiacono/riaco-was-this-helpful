<?php
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template included inside a class method; variables are method-scoped, not global.
$feedback_list = new RIWTH_Admin_Feedback_List();
$per_page      = 20;
$current_page  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$total_records = $feedback_list->get_total_records();
$total_pages   = (int) ceil( $total_records / $per_page );
$records       = $feedback_list->get_feedback_records( $current_page, $per_page );

$export_url      = wp_nonce_url(
	add_query_arg( array( 'page' => 'riwth-feedback-list', 'export' => 'csv' ), admin_url( 'admin.php' ) ),
	'riwth_export_csv'
);
$delete_all_url  = wp_nonce_url(
	add_query_arg( array( 'action' => 'riwth_delete_all_feedback' ), admin_url( 'admin.php' ) ),
	'riwth_delete_all_feedback'
);
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div style="margin: 1em 0; display: flex; gap: 0.5em; align-items: center;">
		<a href="<?php echo esc_url( $export_url ); ?>" class="button">
			<?php esc_html_e( 'Export CSV', 'riaco-was-this-helpful' ); ?>
		</a>
		<?php if ( $total_records > 0 ) : ?>
			<a href="<?php echo esc_url( $delete_all_url ); ?>"
			   class="button"
			   onclick="return confirm('<?php echo esc_js( __( 'Delete all feedback records? This cannot be undone.', 'riaco-was-this-helpful' ) ); ?>')">
				<?php esc_html_e( 'Delete All', 'riaco-was-this-helpful' ); ?>
			</a>
		<?php endif; ?>
		<span style="color:#555;">
			<?php
			/* translators: %d: number of records */
			echo esc_html( sprintf( _n( '%d record', '%d records', $total_records, 'riaco-was-this-helpful' ), $total_records ) );
			?>
		</span>
	</div>

	<?php if ( empty( $records ) ) : ?>
		<p><?php esc_html_e( 'No feedback records found.', 'riaco-was-this-helpful' ); ?></p>
	<?php else : ?>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:40%"><?php esc_html_e( 'Post', 'riaco-was-this-helpful' ); ?></th>
					<th style="width:15%"><?php esc_html_e( 'Vote', 'riaco-was-this-helpful' ); ?></th>
					<th style="width:30%"><?php esc_html_e( 'Date', 'riaco-was-this-helpful' ); ?></th>
					<th style="width:15%"><?php esc_html_e( 'Actions', 'riaco-was-this-helpful' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $records as $record ) : ?>
					<?php
					$post_title = get_the_title( (int) $record->post_id );
					$post_title = $post_title ? $post_title : __( '(Post deleted)', 'riaco-was-this-helpful' );
					$edit_link  = get_edit_post_link( (int) $record->post_id );
					$vote_label = '1' === $record->helpful ? __( 'Yes', 'riaco-was-this-helpful' ) : __( 'No', 'riaco-was-this-helpful' );
					$vote_color = '1' === $record->helpful ? '#1e8a1e' : '#c0392b';
					$date       = get_date_from_gmt( $record->created_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
					$delete_url = wp_nonce_url(
						add_query_arg(
							array(
								'action'      => 'riwth_delete_feedback',
								'feedback_id' => $record->id,
							),
							admin_url( 'admin.php' )
						),
						'riwth_delete_feedback_' . $record->id
					);
					?>
					<tr>
						<td>
							<?php if ( $edit_link ) : ?>
								<a href="<?php echo esc_url( $edit_link ); ?>"><?php echo esc_html( $post_title ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $post_title ); ?>
							<?php endif; ?>
						</td>
						<td>
							<strong style="color:<?php echo esc_attr( $vote_color ); ?>">
								<?php echo esc_html( $vote_label ); ?>
							</strong>
						</td>
						<td><?php echo esc_html( $date ); ?></td>
						<td>
							<a href="<?php echo esc_url( $delete_url ); ?>"
							   onclick="return confirm('<?php echo esc_js( __( 'Delete this record?', 'riaco-was-this-helpful' ) ); ?>')"
							   style="color:#c0392b;">
								<?php esc_html_e( 'Delete', 'riaco-was-this-helpful' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'    => add_query_arg( 'paged', '%#%' ),
								'format'  => '',
								'current' => $current_page,
								'total'   => $total_pages,
							)
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>

	<?php endif; ?>
</div>
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>

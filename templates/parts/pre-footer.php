<?php
/**
 * Pre Footer Template.
 *
 * @package PrettyLinks
 */

defined( 'ABSPATH' ) || exit;

$links_count = count( $links );
?>

<div class="riwth-pre-footer">
	<p><?php echo esc_html( $title ); ?>

	<ul class="riwth-pre-footer--links">
	<?php foreach ( $links as $key => $item ) : ?>
		<li>
		<?php
		$attributes = array(
			'href'   => esc_url( $item['url'] ),
			'target' => isset( $item['target'] ) ? $item['target'] : false,
			'rel'    => isset( $item['target'] ) ? 'noopener noreferrer' : false,
		);

		$attribute_str = '';

		foreach ( $attributes as $attr_key => $attr_item ) {
			if ( $attr_item ) {
				$attribute_str .= sprintf( '%s="%s"', $attr_key, esc_attr( $attr_item ) );
			}
		}

		printf(
			'<a %1$s>%2$s</a>%3$s',
			$attribute_str,
			esc_html( $item['text'] ),
			$links_count === $key + 1 ? '' : '<span>/</span>'
		);
		?>
		</li>
	<?php endforeach; ?>
	</ul>
</div>

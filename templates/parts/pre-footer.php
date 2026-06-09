<?php
/**
 * Pre Footer Template.
 *
 * @package PrettyLinks
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template included inside a class method; variables are method-scoped, not global.
$links_count = count( $links );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>

<div class="riwth-pre-footer">
	<p><?php echo esc_html( $title ); ?>

	<ul class="riwth-pre-footer--links">
	<?php foreach ( $links as $key => $item ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
		<li>
				<a 
				href="<?php echo esc_url( $item['url'] ); ?>" 
				<?php if ( ! empty( $item['target'] ) ) : ?>
					target="<?php echo esc_attr( $item['target'] ); ?>" 
					rel="noopener noreferrer"
				<?php endif; ?>
			>
				<?php echo esc_html( $item['text'] ); ?>
			</a>
			<?php if ( $links_count !== $key + 1 ) : ?>
				<span>/</span>
			<?php endif; ?>

		</li>
	<?php endforeach; ?>
	</ul>
</div>

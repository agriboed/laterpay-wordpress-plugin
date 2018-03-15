<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<div id="lp_js_debugger" class="lp_debugger lp_is-hidden">
    <header id="lp_js_toggleDebuggerVisibility" class="lp_debugger-header">
        <a href="#" class="lp_debugger__close-link lp_right" data-icon="l"></a>
        <div class="lp_debugger-header__text lp_right">
			<?php echo esc_html( sprintf( __( '%s Memory Usage', 'laterpay' ),
				number_format( $laterpay_records['memory_peak'],
					1 ) . ' MB' ) ); ?>
        </div>
        <div data-icon="a" class="lp_debugger-header__title">
			<?php echo esc_html( __( 'Debugger', 'laterpay' ) ); ?></div>
    </header>

    <ul id="lp_js_debuggerTabs" class="lp_debugger-tabs lp_clearfix">
        <li class="lp_js_debuggerTabItem lp_is-selected lp_debugger-tabs__item">
            <a href="#" class="lp_debugger-tabs__link">
				<?php echo wp_kses_post( sprintf( __( 'Messages<span class="lp_badge">%s</span>',
					'laterpay' ), count( $laterpay_records['records'] ) ) ); ?>
            </a>
        </li>
		<?php foreach ( $laterpay_records['tabs'] as $tab ): ?>
			<?php if ( empty( $tab['content'] ) ) {
				continue;
			}
			?>
            <li class="lp_js_debuggerTabItem lp_debugger-tabs__item">
                <a href="#" class="lp_debugger-tabs__link">
					<?php echo wp_kses_post( __( $tab['name'], 'laterpay' ) ); ?>
                </a>
            </li>
		<?php endforeach ?>
    </ul>

    <ul class="lp_debugger-content-list">
        <li class="lp_js_debuggerContent lp_debugger-content-list__item">
            <ul class="lp_debugger-content-list">
				<?php echo $laterpay_records['formatted_records']; ?>
            </ul>
        </li>
		<?php foreach ( $laterpay_records['tabs'] as $tab ): ?>
			<?php if ( empty( $tab['content'] ) ) {
				continue;
			}
			?>
            <li class="lp_js_debuggerContent lp_debugger-content-list__item lp_is-hidden">
				<?php if ( $tab['type'] === 'html' ) : ?>
                    <ul class="lp_debugger-content-list">
						<?php echo $tab['content']; ?>
                    </ul>
				<?php else : ?>
                    <table class="lp_debugger-content__table">
						<?php foreach ( $tab['content'] as $key => $value ) : ?>
                            <tr>
                                <th class="lp_debugger-content__table-th">
									<?php echo wp_kses_post( $key ); ?>
                                </th>
                                <td class="lp_debugger-content__table-td"><?php echo esc_html($value);?></td>
                            </tr>
						<?php endforeach; ?>
                    </table>
				<?php endif; ?>
            </li>
		<?php endforeach ?>
    </ul>
</div>
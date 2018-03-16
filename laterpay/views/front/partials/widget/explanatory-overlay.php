<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lp_paid-content">
    <div class="lp_full-content">
        <!-- <?php esc_html_e( 'Preview a short excerpt from the paid post:', 'laterpay' ); ?> -->
		<?php echo wp_kses_post( $_['teaser'] ); ?>
        <br>
		<?php esc_html_e( 'Thanks for reading this short excerpt from the paid post! Fancy buying it to read all of it?', 'laterpay' ); ?>
    </div>
    <div class="lp_overlay-text">
        <div class="lp_benefits">
            <header class="lp_benefits__header">
                <h2 class="lp_benefits__title">
					<?php esc_html_e( $_['data']['title'] ); ?>
                </h2>
            </header>
            <ul class="lp_benefits__list">
				<?php foreach ( $_['data']['benefits'] as $benefit ) : ?>
                    <li class="lp_benefits__list-item <?php echo esc_attr( $benefit['class'] ); ?>">
                        <h3 class="lp_benefit__title">
							<?php echo wp_kses_post( $benefit['title'] ); ?>
                        </h3>
                        <p class="lp_benefit__text">
							<?php echo wp_kses_post( $benefit['text'] ); ?>
                        </p>
                    </li>
				<?php endforeach; ?>
            </ul>
            <div class="lp_benefits__action">
				<?php echo $_['data']['action']; ?>
            </div>
            <div class="lp_powered-by">
				<?php esc_html_e( 'powered by', 'laterpay' ); ?><span data-icon="a"></span>
            </div>
        </div>
    </div>

</div>

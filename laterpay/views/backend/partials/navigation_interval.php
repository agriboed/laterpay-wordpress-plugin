<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$disabled_class = '';
if ( $laterpay[ 'end_timestamp' ] > $laterpay[ 'interval_end' ] ) :
    $disabled_class = "lp_is-disabled";
endif;
?>
<a href="#" id="lp_js_loadPreviousInterval" class="lp_dashboard-title__arrow-link lp_tooltip <?php echo $disabled_class; ?>" data-tooltip="<?php _e( 'Show previous 8 days', 'laterpay' ); ?>">
    <div class="lp_dashboard-title__triangle lp_dashboard-title__triangle--left"></div>
</a>

<span id="lp_js_displayedInterval" data-interval-end-timestamp="<?php echo $laterpay[ 'end_timestamp' ]; ?>" data-start-timestamp="<?php echo $laterpay[ 'interval_start' ]; ?>">
    <?php echo date( 'd.m.Y.', $laterpay[ 'interval_end' ] ) . ' &ndash; ' . date( 'd.m.Y', $laterpay[ 'interval_start' ] ); ?>
</span>

<a href="#" id="lp_js_loadNextInterval" class="lp_dashboard-title__arrow-link lp_tooltip lp_is-disabled">
    <div class="lp_dashboard-title__triangle lp_dashboard-title__triangle--right"></div>
</a>

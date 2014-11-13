<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<?php if ( ! $laterpay['is_user_already_voted'] ) : ?>
    <div class="lp_js_ratePost">
        <form id="lp_js_ratingForm" method="post">
            <input type="hidden" name="action" value="laterpay_post_rate_purchased_content">
            <input type="hidden" name="post_id" value="<?php echo $laterpay['post_id']; ?>">
            <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>
            <div class="lp_rating">
                <input type="radio" class="lp_rating_input" id="rating-input-1" name="rating_value" value="1">
                <label for="rating-input-1" class="lp_rating_star"></label>
                <input type="radio" class="lp_rating_input" id="rating-input-2" name="rating_value" value="2">
                <label for="rating-input-2" class="lp_rating_star"></label>
                <input type="radio" class="lp_rating_input" id="rating-input-3" name="rating_value" value="3">
                <label for="rating-input-3" class="lp_rating_star"></label>
                <input type="radio" class="lp_rating_input" id="rating-input-4" name="rating_value" value="4">
                <label for="rating-input-4" class="lp_rating_star"></label>
                <input type="radio" class="lp_rating_input" id="rating-input-5" name="rating_value" value="5">
                <label for="rating-input-5" class="lp_rating_star"></label>
            </div>
        </form>
    </div>
<?php endif; ?>


<script>
    var lpVars = window.lpVars || {};
    lpVars.categoryDefaultPrice = <?php echo $category_default_price; ?>;
    lpVars.dynamicPricingData   = <?php echo $dynamic_pricing_data; ?>;
    lpVars.isStandardPost       = <?php echo $price_post_type ? 1 : 0; ?>;
</script>

<input type="hidden" name="price_post_type" value="<?php echo $price_post_type ?>">

<div id="laterpay_post_standard"<?php if ( $price_post_type ): ?> style="display:none;"<?php endif; ?>>
    <p>
        <?php _e('This post costs', 'laterpay'); ?>
        <input type="text"
                id="post-price"
                class="lp-input number"
                name="pricing-post"
                value="<?php echo LaterPayViewHelper::formatNumber($price, 2); ?>"
                placeholder="<?php _e('0.00', 'laterpay'); ?>">
        <?php echo $currency; ?>
    </p>
    <p>
        <?php if ( !is_null($category_default_price) ): ?>
            <a href="#" id="set_price_category">
                <?php _e('Apply category default price', 'laterpay'); ?> (<?php echo $category_default_price; ?> <?php echo $currency; ?>)
            </a>
        <?php endif; ?>
        <?php if ( !is_null($global_default_price) ): ?>
            <a href="#" id="set_price_global"<?php if ( !is_null($category_default_price) ): ?> style="display:none;"<?php endif; ?>>
                <?php _e('Apply global default price', 'laterpay'); ?> (<?php echo $global_default_price; ?> <?php echo $currency; ?>)
            </a>
        <?php endif; ?>
    </p>
    <p>
        <?php _e('Advanced pricing options', 'laterpay'); ?>
        <a href="#" id="show-advanced"><?php _e('Show', 'laterpay'); ?></a>
    </p>
</div>

<div id="laterpay-price-type">
    <ul class="lp-toggle clearfix">
        <li class="selected">
            <a href="#" class="use-individual-price"><?php _e('Individual Price', 'laterpay'); ?></a>
        </li>
        <li<?php if ( !($category_default_price > 0) ): ?> class="disabled"<?php endif; ?>>
            <a href="#" class="use-category-default-price"><?php _e('Category Default Price', 'laterpay'); ?></a>
        </li>
        <li<?php if ( !($global_default_price > 0) ): ?> class="disabled"<?php endif; ?>>
            <a href="#" class="use-global-default-price" data-price="<?php echo $global_default_price; ?>"><?php _e('Global Default<span></span> Price', 'laterpay'); ?></a>
        </li>
    </ul>
    <div id="laterpay-price-type-details">
        <div id="laterpay-dynamic-pricing" class="use-individual-price details-section" style="display:none;">
            <input type="hidden" name="laterpay_start_price">
            <input type="hidden" name="laterpay_end_price">
            <input type="hidden" name="laterpay_change_start_price_after_days">
            <input type="hidden" name="laterpay_transitional_period_end_after_days">
            <input type="hidden" name="laterpay_reach_end_price_after_days">
            <div id="container"></div>
<!--                 <div id="container2">
                <p><?php _e('Dynamic pricing presets', 'laterpay'); ?></p>
                <a href="#" class="blockbuster"><?php _e('Blockbuster', 'laterpay'); ?></a>
                <a href="#" class="breaking-news"><?php _e('Breaking News', 'laterpay'); ?></a>
                <a href="#" class="teaser"><?php _e('Teaser', 'laterpay'); ?></a>
                <a href="#" class="long-tail"><?php _e('Long-tail', 'laterpay'); ?></a>
                <a href="#" class="flat"><?php _e('Flat (default)', 'laterpay'); ?></a>
            </div> -->
        </div>
        <div class="use-category-default-price details-section" style="display:none;">
            <ul>
                <?php #foreach($category_prices as $c): ?>
                    <li data-category="<?php #echo $c[category_id]; ?>">
                        <a href="#" data-price="<?php #echo $c[category_price]; ?>">
                            <span><?php #echo $c[category_price]; ?> <?php #echo $currency; ?></span><?php #echo $c[category_name]; ?>
                        </a>
                    </li>
                <?php #endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<a href="#" id="use-dynamic-pricing" class=""><?php _e('Add dynamic pricing', 'laterpay'); ?></a>

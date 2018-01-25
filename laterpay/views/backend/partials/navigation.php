<?php
if ( ! defined('ABSPATH')) {
    // prevent direct access to this file
    exit;
}
?>
<ul class="lp_navigation-tabs">
    <?php foreach ($laterpay['menu'] as $page) : ?>
	    <?php $current_page_class = $laterpay['current_page'] === $page['url'] ? 'lp_is-current' : '' ?>
        <?php if ( ! current_user_can($page['cap'])) : ?>
            <?php continue; ?>
        <?php endif; ?>
        <li class="lp_navigation-tabs__item <?php echo esc_attr($current_page_class); ?>">
            <?php echo wp_kses_post(LaterPay\Helper\View::getAdminMenuLink($page)); ?>
            <?php if (isset($page['submenu'])) : ?>
                <ul class="lp_navigation-tabs__submenu">
                    <li class="lp_navigation-tabs__item">
                        <?php echo wp_kses_post(LaterPay\Helper\View::getAdminMenuLink($page['submenu'])); ?>
                    </li>
                </ul>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
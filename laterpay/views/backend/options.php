<?php
if ( ! defined('ABSPATH')) {
    // prevent direct access to this file
    exit;
}

if ( ! current_user_can('manage_options')) {
    wp_die(esc_html(__("You don't have sufficient permissions to manage options for this site.", 'laterpay')));
}
?>

<div class="wrap">
    <h2><?php echo esc_html($laterpay['settings_title']); ?></h2>

    <form method="POST" action="options.php">
        <?php settings_fields('laterpay'); ?>
        <?php do_settings_sections('laterpay'); ?>
        <?php submit_button(); ?>
    </form>
</div>

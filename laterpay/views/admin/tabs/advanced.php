<?php
if ( ! defined('ABSPATH')) {
    exit;
}
?>
<div class="lp_page wp-core-ui">

    <?php echo $_['header']; ?>

    <div class="lp_pagewrap">
        <div class="lp_clearfix">
            <form id="lp_js_advancedForm">

                <input type="hidden" name="form" value="advanced">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($_['nonce']); ?>">
                <input type="hidden" name="action" value="laterpay_advanced">

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('LaterPay Colors', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php esc_html_e('You can customize the colors of clickable LaterPay elements.', 'laterpay'); ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td style="width: 30%">
                                <input type="color"
                                       class="lp_input lp_input--minimum"
                                       id="main_color"
                                       required
                                       name="main_color" value="<?php echo esc_attr($_['main_color']); ?>">
                            </td>
                            <td>
                                <?php
                                esc_html_e('Main color for clickable elements. (Default: #01a99d)', 'laterpay');
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="color"
                                       class="lp_input  lp_input--minimum"
                                       id="hover_color"
                                       required
                                       name="hover_color" value="<?php echo esc_attr($_['hover_color']); ?>">
                            </td>
                            <td>
                                <?php
                                esc_html_e('Hover color for clickable elements. (Default: #01766e)', 'laterpay');
                                ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('Debugger Pane', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php
                        echo wp_kses_post(
                            __(
                                'The LaterPay debugger pane contains a lot of helpful plugin- and system-related information
               for debugging the LaterPay plugin and fixing configuration problems.<br>
               When activated, the debugger pane is rendered at the bottom of the screen.<br>
               It is visible both for users from address list<br>
               On a production installation you should switch it off again as soon as you don\'t need it anymore.',
                                'laterpay'
                            )
                        );
                        ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td style="width: 30%">
                                <label class="lp_toggle__label">
                                    <input
                                            type="checkbox"
                                            class="lp_toggle__input"
                                            name="debugger_enabled"
                                        <?php echo $_['debugger_enabled'] ? 'checked' : ''; ?>
                                            value="1">

                                    <span class="lp_toggle__text" data-on="" data-off=""></span>
                                    <span class="lp_toggle__handle"></span>
                                </label>
                            </td>
                            <td>
                                <?php
                                esc_html_e('I want to view the LaterPay debugger pane', 'laterpay');
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="text"
                                       class="lp_input lp_input--minimum"
                                       id="debugger_addresses"
                                       name="debugger_addresses"
                                       value="<?php echo esc_attr($_['debugger_addresses']); ?>">
                                <label for="debugger_addresses"
                                       placeholder="<?php esc_attr_e('LaterPay Debugger Address', 'laterpay'); ?>">
                                </label>
                            </td>
                            <td>
                                <?php
                                esc_html_e('List of allowed addresses to view debug(Ex.: 127.0.0.1,192.168.1.1)',
                                    'laterpay');
                                ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('Caching Compatibility Mode', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php
                        echo wp_kses_post(
                            __(
                                'You MUST enable caching compatiblity mode, if you are using a caching solution that caches
                entire HTML pages.<br>
                In caching compatibility mode the plugin works like this:<br>
                It renders paid posts only with the teaser content. This allows to cache them as static files without
                risking to leak the paid content.<br>
                When someone visits the page, it makes an Ajax request to determine, if the visitor has already bought
                the post and replaces the teaser with the full content, if required.', 'laterpay'
                            )
                        );
                        ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td style="width: 10%">
                                <label class="lp_toggle__label">
                                    <input
                                            type="checkbox"
                                            class="lp_toggle__input"
                                            name="caching_compatibility"
                                        <?php echo $_['caching_compatibility'] ? 'checked' : ''; ?>
                                            value="1">

                                    <span class="lp_toggle__text" data-on="" data-off=""></span>
                                    <span class="lp_toggle__handle"></span>
                                </label>
                            </td>
                            <td>
                                <?php
                                esc_html_e('I am using a caching plugin (e.g. WP Super Cache or Cachify)', 'laterpay');
                                ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('LaterPay-enabled Post Types', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php esc_html_e('Please choose, which standard and custom post types should be sellable with LaterPay.',
                            'laterpay'); ?>
                    </p>

                    <table class="lp_table--form">
                        <?php foreach ($_['enabled_post_types'] as $post_type) : ?>
                            <tr>
                                <td style="width: 10%">
                                    <label class="lp_toggle__label">
                                        <input type="checkbox" class="lp_toggle__input"
                                               name="enabled_post_types[]"
                                            <?php echo $post_type['checked'] ? 'checked' : ''; ?>
                                               value="<?php echo esc_attr($post_type['slug']); ?>">
                                        <span class="lp_toggle__text" data-on="" data-off=""></span>
                                        <span class="lp_toggle__handle"></span>
                                    </label>
                                </td>
                                <td>
                                    <?php echo esc_html($post_type['label']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('Offering Time Passes on Free Posts', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php esc_html_e('Please choose, if you want to show the time passes widget on free posts, or only on paid posts.',
                            'laterpay'); ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td style="width: 10%">
                                <label class="lp_toggle__label">
                                    <input type="checkbox" class="lp_toggle__input"
                                           name="show_time_passes_widget_on_free_posts"
                                        <?php echo $_['show_time_passes_widget_on_free_posts'] ? 'checked' : ''; ?>
                                           value="1">

                                    <span class="lp_toggle__text" data-on="" data-off=""></span>
                                    <span class="lp_toggle__handle"></span>
                                </label>
                            </td>
                            <td>
                                <?php esc_html_e('I want to display the time passes widget on free and paid posts',
                                    'laterpay'); ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('Require login', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php esc_html_e('Please choose if you want to require a login for "Pay Later" purchases.',
                            'laterpay'); ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td style="width: 10%">
                                <label class="lp_toggle__label">
                                    <input type="checkbox" class="lp_toggle__input"
                                           name="require_login"
                                        <?php echo $_['require_login'] ? 'checked' : ''; ?>
                                           value="1">

                                    <span class="lp_toggle__text" data-on="" data-off=""></span>
                                    <span class="lp_toggle__handle"></span>
                                </label>
                            </td>
                            <td>
                                <?php esc_html_e('Require the user to log in to LaterPay before a "Pay Later" purchase.',
                                    'laterpay'); ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('Gift Codes Limit', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php esc_html_e('Specify, how many times a gift code can be redeemed for the associated time pass.',
                            'laterpay'); ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td>
                                <input type="number"
                                       min="0"
                                       step="1"
                                       class="lp_input lp_input--minimum"
                                       id="maximum_redemptions_per_gift_code"
                                       required
                                       name="maximum_redemptions_per_gift_code"
                                       value="<?php echo esc_attr($_['maximum_redemptions_per_gift_code']); ?>">
                                <label for="maximum_redemptions_per_gift_code"
                                       placeholder="<?php echo esc_attr('Times Redeemable', 'laterpay'); ?>">
                                </label>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('Automatically Generated Teaser Content', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php
                        echo wp_kses_post(
                            __(
                                'The LaterPay WordPress plugin automatically generates teaser content for every paid post
                without teaser content.<br>
                While technically possible, setting this parameter to zero is HIGHLY DISCOURAGED.<br>
                If you really, really want to have NO teaser content for a post, enter one space
                into the teaser content editor for that post.', 'laterpay'
                            )
                        );
                        ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td style="width: 30%">
                                <input type="number"
                                       class="lp_input lp_input--minimum"
                                       id="teaser_content_word_count"
                                       required
                                       min="0"
                                       step="1"
                                       name="teaser_content_word_count"
                                       value="<?php echo esc_attr($_['teaser_content_word_count']); ?>">
                                <label for="teaser_content_word_count"
                                       placeholder="<?php esc_attr_e('Teaser Content Word Count	', 'laterpay'); ?>">
                                </label>
                            </td>
                            <td>
                                <?php esc_html_e('Number of words extracted from paid posts as teaser content.',
                                    'laterpay'); ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('Content Preview under Overlay', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php
                        echo wp_kses_post(
                            __(
                                'In the appearance tab, you can choose to preview your paid posts with the teaser content plus
                an excerpt of the full content, covered by a semi-transparent overlay.<br>
                The following three parameters give you fine-grained control over the length of this excerpt.<br>
                These settings do not affect the teaser content in any way.', 'laterpay'
                            )
                        );
                        ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td style="width: 30%">
                                <input type="number"
                                       class="lp_input lp_input--minimum"
                                       id="preview_excerpt_percentage_of_content"
                                       required
                                       min="0"
                                       max="100"
                                       name="preview_excerpt_percentage_of_content"
                                       value="<?php echo esc_attr($_['preview_excerpt_percentage_of_content']); ?>">
                                <label for="preview_excerpt_percentage_of_content"
                                       placeholder="<?php esc_attr_e('Teaser Content Word Count', 'laterpay'); ?>">
                                </label>
                            </td>
                            <td>
                                <?php esc_html_e('Percentage of content to be extracted; 20 means "extract 20% of the total number of words of the post".',
                                    'laterpay'); ?>
                            </td>

                        </tr>
                        <tr>
                            <td>
                                <input type="number"
                                       class="lp_input lp_input--minimum"
                                       id="preview_excerpt_word_count_min"
                                       required
                                       min="0"
                                       name="preview_excerpt_word_count_min"
                                       value="<?php echo esc_attr($_['preview_excerpt_word_count_min']); ?>">
                                <label for="preview_excerpt_word_count_min"
                                       placeholder="<?php esc_attr_e('Minimum Number of Words', 'laterpay'); ?>">
                                </label>
                            </td>
                            <td>
                                <?php esc_html_e('Applied if number of words as percentage of the total number of words is less than this value.',
                                    'laterpay'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="number"
                                       class="lp_input lp_input--minimum"
                                       id="preview_excerpt_word_count_max"
                                       required
                                       min="0"
                                       name="preview_excerpt_word_count_max"
                                       value="<?php echo esc_attr($_['preview_excerpt_word_count_max']); ?>">
                                <label for="preview_excerpt_word_count_max"
                                       placeholder="<?php esc_attr_e('Maximum Number of Words', 'laterpay'); ?>">
                                </label>
                            </td>
                            <td>
                                <?php esc_html_e('Applied if number of words as percentage of the total number of words exceeds this value.',
                                    'laterpay'); ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('Unlimited Access to Paid Content', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php
                        echo wp_kses_post(
                            __(
                                'You can give logged-in users unlimited access to specific categories depending on their user
                role.<br>
                This feature can be useful e.g. for giving free access to existing subscribers.<br>
                We recommend the plugin \'User Role Editor\' for adding custom roles to WordPress.', 'laterpay'
                            )
                        );
                        ?>
                    </p>

                    <table class="lp_table--form wp-list-table widefat striped">
                        <tr>
                            <td style="width: 15%">
                                <?php esc_html_e('User Role', 'laterpay'); ?>
                            </td>
                            <td style="width: 10%">
                                <?php esc_html_e('None', 'laterpay'); ?>
                            </td>
                            <td style="width: 10%"><?php esc_html_e('All categories', 'laterpay'); ?></td>
                            <td>
                                <?php esc_html_e('Unlimited Access to Categories', 'laterpay'); ?>
                            </td>
                        </tr>

                        <?php foreach ($_['unlimited_access'] as $role) : ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($role['name']); ?>
                                </td>
                                <td>
                                    <label class="lp_toggle__label">
                                        <input type="checkbox" class="lp_toggle__input lp_access-none"
                                               name="unlimited_access[<?php echo esc_attr($role['id']); ?>][]"
                                            <?php echo $role['none'] ? 'checked' : ''; ?>
                                               value="none">

                                        <span class="lp_toggle__text" data-on="" data-off=""></span>
                                        <span class="lp_toggle__handle"></span>
                                    </label>
                                </td>
                                <td>
                                    <label class="lp_toggle__label">
                                        <input type="checkbox" class="lp_toggle__input lp_access-all"
                                               name="unlimited_access[<?php echo esc_attr($role['id']); ?>][]"
                                            <?php echo $role['all'] ? 'checked' : ''; ?>
                                               value="all">

                                        <span class="lp_toggle__text" data-on="" data-off=""></span>
                                        <span class="lp_toggle__handle"></span>
                                    </label>
                                </td>
                                <td>
                                    <?php foreach ($role['categories'] as $category) : ?>
                                        <label style="display: none">
                                            <input type="checkbox"
                                                   name="unlimited_access[<?php echo esc_attr($role['id']); ?>][]"
                                                   class="lp_category-access-input"
                                                <?php echo $category['checked'] ? 'checked' : ''; ?>
                                                   value="<?php echo esc_attr($category['term_id']); ?>">

                                            <?php echo esc_html($category['name']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('LaterPay API Settings', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php echo wp_kses_post(__('Define fallback behavior in case LaterPay API is not responding and option to disallow plugin to contact LaterPay API on homepage',
                            'laterpay')); ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td style="width: 30%">
                                <select class="lp_input lp_input--minimum" name="api_fallback_behavior">
                                    <option
                                        <?php echo ($_['api_fallback_behavior'] === 0) ? 'selected' : ''; ?>
                                            value="0">
                                        <?php esc_html_e('Do nothing', 'laterpay'); ?>
                                    </option>
                                    <option
                                        <?php echo ($_['api_fallback_behavior'] === 1) ? 'selected' : ''; ?>
                                            value="1">
                                        <?php esc_html_e('Give full access', 'laterpay'); ?>
                                    </option>
                                    <option
                                        <?php echo ($_['api_fallback_behavior'] === 2) ? 'selected' : ''; ?>
                                            value="2">
                                        <?php esc_html_e('Hide premium content', 'laterpay'); ?>
                                    </option>
                                </select>
                            </td>
                            <td>
                                <?php esc_html_e('Fallback Behavior', 'laterpay'); ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset">
                    <legend class="lp_legend">
                        <?php esc_html_e('LaterPay Pro Merchant', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php esc_html_e('Please choose, if you have a LaterPay Pro merchant account.', 'laterpay'); ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td style="width: 10%">
                                <label class="lp_toggle__label">
                                    <input type="checkbox"
                                           id="lp_js_proMerchant"
                                           class="lp_toggle__input"
                                           data-confirm="<?php esc_attr_e('Only choose this option, if you have a LaterPay Pro merchant account. Otherwise, selling content with LaterPay might not work anymore.If you have questions about LaterPay Pro, please contact sales@laterpay.net. Are you sure that you want to choose this option?',
                                               'laterpay'); ?>"
                                           name="pro_merchant"
                                        <?php echo $_['pro_merchant'] ? 'checked' : ''; ?>
                                           value="1">
                                    <span class="lp_toggle__text" data-on="" data-off=""></span>
                                    <span class="lp_toggle__handle"></span>
                                </label>
                            </td>
                            <td>
                                <?php esc_html_e('I have a LaterPay Pro merchant account.', 'laterpay'); ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset class="lp_fieldset"
                          style="<?php echo $_['show_business_model'] ? 'display:block' : 'display:none'; ?>">
                    <legend class="lp_legend">
                        <?php esc_html_e('Business Model', 'laterpay'); ?>
                    </legend>

                    <p class="lp_bold">
                        <?php esc_html_e('Please choose, if you have a LaterPay Pro merchant account.', 'laterpay'); ?>
                    </p>

                    <table class="lp_table--form">
                        <tr>
                            <td style="width: 30%">
                                <select name="business_model"
                                        class="lp_input lp_input--minimum"
                                        id="lp_js_businessModel">
                                    <option
                                        <?php echo $_['business_model'] === 'paid' ? 'selected' : ''; ?>
                                            value="paid">
                                        <?php esc_attr_e('Paid-for content', 'laterpay'); ?>
                                    </option>
                                    <option
                                        <?php echo $_['business_model'] === 'contribution' ? 'selected' : ''; ?>
                                            data-confirm="<?php esc_attr_e('Only chose this option if you wish to receive contributions through your website but do not want to sell content through LaterPay. If you are unsure of which business model is best for your website please contact sales@laterpay.net 
Are you sure you want this option?', 'laterpay'); ?>"
                                            value="contribution">
                                        <?php esc_attr_e('Accept Contributions', 'laterpay'); ?>
                                    </option>
                                    <option
                                        <?php echo $_['business_model'] === 'donation' ? 'selected' : ''; ?>
                                            data-confirm="<?php esc_attr_e('Only chose this option if you are a registered 501(c)(3)charity and wish to receive donations through your website. If you are unsure of which business model is best for your website please contact sales@laterpay.net
Are you sure you want this option?', 'laterpay'); ?>"
                                            value="donation">
                                        <?php esc_attr_e('Accept Donations', 'laterpay'); ?>
                                    </option>
                                </select>
                            </td>
                            <td>
                                <?php esc_html_e('You can only offer one type of business model on your website',
                                    'laterpay'); ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>

                <button type="submit"
                        class="lp_button--default lp_mt- lp_mb-">
                    <?php esc_html_e('Save', 'laterpay'); ?>
                </button>
                <button type="reset"
                        class="lp_button--cancel lp_pd-">
                    <?php esc_html_e('Cancel', 'laterpay'); ?>
                </button>
            </form>
        </div>
    </div>
</div>

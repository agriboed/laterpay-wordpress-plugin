<?php

class LaterPay_Controller_Post_Content extends LaterPay_Controller_Abstract
{

    /**
     * Ajax method to get the cached article. This
     * is because there could be a price change in
     * laterpay and we always need the current article
     * price.
     *
     * @wp-hook wp_ajax_laterpay_article_script, wp_ajax_nopriv_laterpay_article_script
     * @return  void
     */
    public function get_cached_post() {
        global $post, $wp_query, $laterpay_show_statistics;

        // set the global vars so that WordPress
        // thinks it is in a single view
        $wp_query->is_single      = TRUE;
        $wp_query->in_the_loop    = TRUE;
        $laterpay_show_statistics = TRUE;

        // get the content
        $post_id        = absint( $_REQUEST[ 'post_id' ] );
        $post           = get_post( $post_id );
        $post_content   = $post->post_content;

        $content = apply_filters( 'the_content', $post_content );
        $content = str_replace( ']]>', ']]&gt;', $content );

        echo $content;

        exit;
    }

    /**
     * Ajax method to get the the modified title
     *
     * @wp-hook wp_ajax_laterpay_title_script, wp_ajax_nopriv_laterpay_title_script
     * @return  void
     */
    public function get_modified_title() {
        global $post, $wp_query;

        // set the global vars so that WordPress
        // thinks it is in a single view
        $wp_query->is_single      = TRUE;
        $wp_query->in_the_loop    = TRUE;

        // get the content
        $post_id    = absint( $_REQUEST[ 'id' ] );
        $post       = get_post( $post_id );
        $post_title = get_the_title( $post_id );
        echo $post_title;
        exit;
    }

    /**
     * This ajax callback loads the modified footer
     * and echos it.
     *
     * @wp-hook wp_ajax_laterpay_footer_script, wp_ajax_nopriv_laterpay_footer_script
     * @return  void
     */
    public function get_modified_footer() {
        $this->modify_footer();
        exit;
    }

    /**
     * Set up post statistics.
     */
    protected function get_post_statistics() {
        if ( ! $this->config->get( 'logging.access_logging_enabled' ) ) {
            return;
        }
		$post = get_post();
	    if ( $post === null ) {
		    return;
	    }

        // get currency
        $currency = get_option( 'laterpay_currency' );

        // get historical performance data for post
        $LaterPay_Payments_History_Model   = new LaterPay_Model_Payments_History();
        $LaterPay_Post_Views_Model = new LaterPay_Model_Post_Views();

        // get total revenue and total sales
        $total = array();
        $history_total = (array) $LaterPay_Payments_History_Model->get_total_history_by_post_id( $post->ID );
        foreach ( $history_total as $item ) {
            $total[$item->currency]['sum']      = round($item->sum, 2);
            $total[$item->currency]['quantity'] = $item->quantity;
        }

        // get revenue
        $last30DaysRevenue = array();
        $history_last30DaysRevenue = (array) $LaterPay_Payments_History_Model->get_last_30_days_history_by_post_id( $post->ID );
        foreach ( $history_last30DaysRevenue as $item ) {
            $last30DaysRevenue[$item->currency][$item->date] = array(
                'sum'       => round( $item->sum, 2 ),
                'quantity'  => $item->quantity,
            );
        }

        $todayRevenue = array();
        $history_todayRevenue = (array) $LaterPay_Payments_History_Model->get_todays_history_by_post_id( $post->ID );
        foreach ( $history_todayRevenue as $item ) {
            $todayRevenue[$item->currency]['sum']       = round( $item->sum, 2 );
            $todayRevenue[$item->currency]['quantity']  = $item->quantity;
        }

        // get visitors
        $last30DaysVisitors = array();
        $history_last30DaysVisitors = (array) $LaterPay_Post_Views_Model->get_last_30_days_history( $post->ID );
        foreach ( $history_last30DaysVisitors as $item ) {
            $last30DaysVisitors[$item->date] = array(
                'quantity' => $item->quantity,
            );
        }

        $todayVisitors = (array) $LaterPay_Post_Views_Model->get_todays_history( $post->ID );
        $todayVisitors = $todayVisitors[0]->quantity;

        // get buyers (= conversion rate)
        $last30DaysBuyers = array();
        if ( isset( $last30DaysRevenue[$currency] ) ) {
            $revenues = $last30DaysRevenue[$currency];
        } else {
            $revenues = array();
        }
        foreach ( $revenues as $date => $item ) {
            $percentage = 0;
            if ( isset( $last30DaysVisitors[$date] ) && ! empty( $last30DaysVisitors[$date]['quantity'] ) ) {
                $percentage = round( 100 * $item['quantity'] / $last30DaysVisitors[$date]['quantity'] );
            }
            $last30DaysBuyers[$date] = array( 'percentage' => $percentage );
        }

        $todayBuyers = 0;
        if ( ! empty( $todayVisitors ) && isset( $todayRevenue[$currency] ) ) {
            // percentage of buyers (sales/visitors)
            $todayBuyers = round( 100 * $todayRevenue[$currency]['quantity'] / $todayVisitors );
        }

        // assign variables
        $this->assign( 'total',                 $total );

        $this->assign( 'last30DaysRevenue',     $last30DaysRevenue );
        $this->assign( 'todayRevenue',          $todayRevenue );

        $this->assign( 'last30DaysBuyers',      $last30DaysBuyers );
        $this->assign( 'todayBuyers',           $todayBuyers );

        $this->assign( 'last30DaysVisitors',    $last30DaysVisitors );
        $this->assign( 'todayVisitors',         $todayVisitors );
    }

    /**
     * Save purchase in purchase history.
     *
     * @wp-hook template_redirect
     * @return  void
     */
    public function buy_post() {

        if ( !isset( $_GET[ 'buy' ] ) ){
            return;
        }

        // contains all required parameters for the GET-Request
        $required_params = array(
            'p',
            'buy',
            'vat',
            'hmac',
            'post_id',
            'id_currency',
            'price',
            'date',
            'ip',
            'hash'
        );

        // checking if all parameters are available in GET-Request
        $diff = array_diff(
            array_keys( $_GET ),
            $required_params
        );

        if ( count( $diff ) > 0 ){
            LaterPay_Core_Logger::error(
                __METHOD__ . ' some parameters are missing in GET-Request',
                array(
                    'get'               => $_GET,
                    'required_params'   => $required_params,
                    'diff'              => $diff,
                )
            );
            return;
        }

        // data to create the url and hash-check
        $url_data = array(
            'post_id'     => $_GET[ 'post_id' ],
            'id_currency' => $_GET[ 'id_currency' ],
            'price'       => $_GET[ 'price' ],
            'date'        => $_GET[ 'date' ],
            'buy'         => $_GET[ 'buy' ],
            'ip'          => $_GET[ 'ip' ],
        );
        $url    = $this->get_buy_redirect_url( $url_data );
        $hash   = $this->get_hash_by_url( $url );

        // checking if the parameters of $_GET are valid and not manipulated
        if ( $hash === $_GET[ 'hash' ] ) {

            $data = array(
                'post_id'       => $_GET[ 'post_id' ],
                'id_currency'   => $_GET[ 'id_currency' ],
                'price'         => $_GET[ 'price' ],
                'date'          => $_GET[ 'date' ],
                'ip'            => $_GET[ 'ip' ],
                'hash'          => $_GET[ 'hash' ],
            );

            $payment_history_model = new LaterPay_Model_Payments_History();
            $payment_history_model->set_payment_history( $data );
        }

        $post_id = absint( $_GET[ 'post_id' ] );
        $redirect_url = get_permalink( $post_id );
        wp_redirect( $redirect_url );
        exit;
    }


    /**
     * Check if current page is login page.
     *
     * @return boolean is login page
     */
    public static function is_login_page() {
        return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );
    }

    /**
     * Check if current page is cron page.
     *
     * @return boolean is cron page
     */
    public static function is_cron_page() {
        return in_array( $GLOBALS['pagenow'], array( 'wp-cron.php' ) );
    }

    /**
     * Update incorrect token or create token, if it doesn't exist.
     *
     * @wp-hook template_redirect
     *
     * @return  void
     */
    public function create_token() {
        $GLOBALS[ 'laterpay_access' ] = false;

        $is_singular                = is_singular();
        $browser_supports_cookies   = LaterPay_Helper_Browser::browser_supports_cookies();
        $browser_is_crawler         = LaterPay_Helper_Browser::is_crawler();

        $context =  array(
            'is_singular'       => $is_singular,
            'support_cookies'   => $browser_supports_cookies,
            'is_crawler'        => $browser_is_crawler
        );

        LaterPay_Core_Logger::debug(
            __METHOD__,
            $context
        );

        if ( !$is_singular || !$browser_supports_cookies || $browser_is_crawler ){
            return;
        }

        $laterpay_client = new LaterPay_Core_Client( $this->config );
        if ( isset( $_GET[ 'lptoken' ] ) ) {
            $laterpay_client->set_token( $_GET['lptoken'], true );
        }

        if ( !$laterpay_client->has_token() ) {
            $laterpay_client->acquire_token();
        }

        // fetch the current post
        $post = get_post();
        if ( $post === null ) {
            LaterPay_Core_Logger::error( __METHOD__ . ' post not found!' );
            return;
        }

        LaterPay_Core_Logger::debug(
            __METHOD__,
            array(
                "post" => $post
            )
        );

        $price      = LaterPay_Helper_Pricing::get_post_price( $post->ID );
        $access     = false;

        if ( $price == 0 ) {
            $result = $laterpay_client->get_access( array( $post->ID ) );

            if ( !empty( $result ) && isset( $result[ 'articles' ] ) && isset( $result[ 'articles' ][ $post->ID ] ) ) {
                $access = $result['articles'][ $post->ID ]['access'];
            }
        }

        $GLOBALS['laterpay_access'] = $access;

    }

    /**
     * Get the LaterPay link for the post.
     *
     * @param   int $post_id
     *
     * @return  string   url || empty string if something went wrong
     */
    public function get_laterpay_link( $post_id ) {

        $post = get_post( $post_id );
        if ( $post === null ) {
            return '';
        }

        // re-set the post-id
        $post_id    = $post->ID;

        $currency   = get_option( 'laterpay_currency' );
        $price      = LaterPay_Helper_Pricing::get_post_price( $post_id );

        $currency_model = new LaterPay_Model_Currency();
        $client         = new LaterPay_Core_Client( $this->config );

        // data to register purchase after redirect from LaterPay
        $url_params = array(
            'post_id'     => $post_id,
            'id_currency' => $currency_model->get_currency_id_by_iso4217_code( $currency ),
            'price'       => $price,
            'date'        => time(),
            'buy'         => 'true',
            'ip'          => ip2long( $_SERVER['REMOTE_ADDR'] ),
        );
        $url    = $this->get_buy_redirect_url( $url_params );
        $hash   = $this->get_hash_by_url( $url );

        // parameters for LaterPay purchase form
        $params = array(
            'article_id'    => $post_id,
            'purchase_date' => time() . '000', // fix for PHP 32bit
            'pricing'       => $currency . ( $price * 100 ),
            'vat'           => $this->config->get( 'currency.default_vat' ),
            'url'           => $url . '&hash=' . $hash,
            'title'         => $post->post_title,
        );

        return $client->get_add_url( $params );
    }

    /**
     * Returning the URL hash by a given URL
     *
     * @param   string $url
     *
     * @return  string $hash
     */
    protected function get_hash_by_url( $url ) {
        return md5( md5( $url ) . AUTH_SALT );
    }

    /**
     * Generate the redirect to buy a post URL by given data
     *
     * @param   array $data
     *
     * @return  String $url
     */
    protected function get_buy_redirect_url( array $data ) {

        $url = get_permalink( $data[ 'post_id' ] );

        if ( ! $url ) {
            LaterPay_Core_Logger::error(
                __METHOD__ . ' could not found a url for the given post_id',
                array( 'data' => $data )
            );
            return $url;
        }

        $url = add_query_arg( $data, $url );

        return $url;
    }

    /**
     * Helper function to detect, if the current post is a single post and can be parsed in frontend.
     *
     * @return  bool true|false
     */
    protected function post_is_a_laterpay_post() {

        // only modify the post_content on singular pages
        if ( ! is_singular() || ! in_the_loop() ) {
            return false;
        }

        // check if the current post type is allowed
        $post_type = get_post_type();
        if ( ! in_array( $post_type, $this->config->get( 'content.allowed_post_types' ) ) ) {
            return false;
        }

        return true;
    }

    /**
     * Render the content and add LaterPay purchase buttons and notices.
     *
     * @wp-hook the_content
     *
     * @param   string $content
     *
     * @return  string $content
     */
    public function modify_post_content( $content ) {
        global $laterpay_show_statistics;

        if ( ! $this->post_is_a_laterpay_post() ) {
            return $content;
        }

        $post = get_post();
        if ( $post === null ) {
            return $content;
        }
        $post_id = $post->ID;

        // get pricing data
        $currency   = get_option( 'laterpay_currency' );
        $price      = LaterPay_Helper_Pricing::get_post_price( $post_id );

        // get information if user has access to content
        $access     = $GLOBALS['laterpay_access'];

        // get purchase link
        $link       = $this->get_laterpay_link( $post_id );
        if ( $price == 0 ) {
            return $content;
        }

        // get teaser content
        $teaser_content         = get_post_meta( $post_id, 'laterpay_post_teaser', true );
        $teaser_content_only    = get_option( 'laterpay_teaser_content_only' );

        // check for required privileges to perform action
        if ( LaterPay_Helper_User::can( 'laterpay_read_post_statistics', $post_id ) ) {
            $access = true;
            $this->get_post_statistics();
        }

        // encrypt files contained in premium posts
        $content = LaterPay_Helper_File::get_encrypted_content( $post_id, $content, $access );
        $is_premium_content = $price > 0;

        $this->assign( 'post_id',                    $post_id );
        $this->assign( 'content',                    $content );
        $this->assign( 'teaser_content',             $teaser_content );
        $this->assign( 'teaser_content_only',        $teaser_content_only );
        $this->assign( 'currency',                   $currency );
        $this->assign( 'price',                      $price );
        $this->assign( 'is_premium_content',         $is_premium_content );
        $this->assign( 'access',                     $access );
        $this->assign( 'link',                       $link );
        $this->assign( 'can_show_statistic',         LaterPay_Helper_User::can( 'laterpay_read_post_statistics', $post_id ) && (! LaterPay_Helper_Request::is_ajax() || $laterpay_show_statistics) && $this->config->get( 'logging.access_logging_enabled' ) && $is_premium_content );
        $this->assign( 'post_content_cached',        LaterPay_Helper_Cache::site_uses_page_caching() );
        $this->assign( 'preview_post_as_visitor',    LaterPay_Helper_User::preview_post_as_visitor( $post ) );
        $this->assign( 'hide_statistics_pane',       LaterPay_Helper_User::statistics_pane_is_hidden() );

        $html = $this->get_text_view( 'frontend/post/single' );

        return $html;
    }

    /**
     * Prepend LaterPay purchase button to title (heading) of post on single post pages.
     *
     * @wp-hook the_title
     *
     * @param   string $the_title
     *
     * @return  string $the_title
     */
    public function modify_post_title( $the_title ) {
        $is_ajax                    = defined( 'DOING_AJAX' ) && DOING_AJAX;
        $post                       = get_post();
        $post_id                    = $post->ID;
        $price                      = LaterPay_Helper_Pricing::get_post_price( $post_id );
        $float_price                = (float) $price;
        $is_premium_content         = $float_price > 0;

        if ( ! $this->post_is_a_laterpay_post() ) {
            return $the_title;
        }

        $access                     = $GLOBALS['laterpay_access'] || LaterPay_Helper_User::can( 'laterpay_read_post_statistics', $post );
        $link                       = $this->get_laterpay_link( $post_id );
        $preview_post_as_visitor    = LaterPay_Helper_User::preview_post_as_visitor( $post );
        $post_content_cached        = $this->config->get('caching.compatible_mode' );
        $currency                   = get_option( 'laterpay_currency' );

        // assign variables to views
        $this->assign( 'post_id',   $post_id );
        $this->assign( 'link',      $link );
        $this->assign( 'price',     LaterPay_Helper_View::format_number( $price, 2 ) );
        $this->assign( 'currency',  $currency );

        if ( $post_content_cached && ! $is_ajax ) {
            $the_title              = $this->get_text_view( 'frontend/partials/post/title' );
        } else if ( ! $access || $preview_post_as_visitor ) {
            $purchase_button        = $this->get_text_view( 'frontend/partials/post/purchase_button' );
            $the_title              = $purchase_button . $the_title;
        }

        return $the_title;
    }

    /**
     * Add the LaterPay iframe to the footer.
     *
     * @wp-hook wp_footer
     *
     * @return void
     */
    public function modify_footer() {
        $post = get_post();
        if ( $post === null ) {
            return;
        }

        $price = LaterPay_Helper_Pricing::get_post_price( $post->ID );
        if ( $price > 0 ) {
            $laterpay_client = new LaterPay_Core_Client( $this->config );
            $identify_link = $laterpay_client->get_identify_url();

            $this->assign( 'post_id',       $post->ID );
            $this->assign( 'identify_link', $identify_link );

            echo $this->get_text_view( 'frontend/partials/identify/iframe' );
        }

    }

}

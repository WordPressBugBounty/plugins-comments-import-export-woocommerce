<?php

/**
 * Review request
 *  
 *
 * @package  Cookie_Law_Info  
 */
if (!defined('ABSPATH')) {
    exit;
}
class Comments_import_export_Review_Request
{
    /**
     * config options 
     */
    private $plugin_title               =   "WordPress Comments Import & Export";
    private $review_url                 =   "https://wordpress.org/support/plugin/comments-import-export-woocommerce/reviews/#new-post";  
    private $plugin_prefix              =   "wtcmtie"; /* must be unique name */
    private $activation_hook            =   "wtcmtie_activate"; /* hook for activation, to store activated date */
    private $deactivation_hook          =   "wtcmtie_deactivate"; /* hook for deactivation, to delete activated date */
    private $days_to_show_banner        =   7; /* when did the banner to show */
    private $remind_days                =   7; /* remind interval in days */
    private $webtoffee_logo_url         =   '/images/webtoffee-logo_small.png';



    private $start_date                 =   0; /* banner to show count start date. plugin installed date, remind me later added date */
    private $current_banner_state       =   2; /* 1: active, 2: waiting to show(first after installation), 3: closed by user/not interested to review, 4: user done the review, 5:remind me later */
    private $banner_state_option_name   =   ''; /* WP option name to save banner state */
    private $start_date_option_name     =   ''; /* WP option name to save start date */
    private $banner_css_class           =   ''; /* CSS class name for Banner HTML element. */
    private $banner_message             =   ''; /* Banner message. */
    private $later_btn_text             =   ''; /* Remind me later button text */
    private $never_btn_text             =   ''; /* Never review button text. */
    private $review_btn_text            =   ''; /* Review now button text. */
    private $ajax_action_name           =   ''; /* Name of ajax action to save banner state. */
    private $allowed_action_type_arr    = array(
        'later', /* remind me later */
        'never', /* never */
        'review', /* review now */
        'closed', /* not interested */
    );

    public function __construct()
    {
        //Set config vars
        $this->set_vars();

        add_action( $this->activation_hook, array( $this, 'on_activate' ) );
        add_action( $this->deactivation_hook, array( $this, 'on_deactivate' ) );
        add_action( 'admin_init', array( $this, 'init' ) );
        
        // Register WooCommerce Pages Banner
        add_action('admin_notices', array($this, 'show_wc_pages_banner'));
        add_action('wp_ajax_wt_iew_dismiss_wc_pages_banner', array($this, 'dismiss_wc_pages_banner_ajax'));
    }

    public function init(){
       if ( $this->check_condition() ) { /* checks the banner is active now */
            
            // translators: %1$s HTML strong tag opening, %2$s HTML strong tag closing.
            $this->banner_message = sprintf(__('Hey, we at %1$sWebToffee%2$s would like to thank you for using our plugin. We would really appreciate if you could take a moment to drop a quick review that will inspire us to keep going.', 'comments-import-export-woocommerce'), '<strong>', '</strong>');

            /* button texts */
            $this->later_btn_text   = __("Remind me later", 'comments-import-export-woocommerce');
            $this->never_btn_text   = __("Not interested", 'comments-import-export-woocommerce');
            $this->review_btn_text  = __("Review now", 'comments-import-export-woocommerce');

            add_action('admin_notices', array($this, 'show_banner')); /* show banner */
            add_action('admin_print_footer_scripts', array($this, 'add_banner_scripts')); /* add banner scripts */
            add_action('wp_ajax_' . $this->ajax_action_name, array($this, 'process_user_action')); /* process banner user action */
        } 
    }

    /**
     *	Set config vars
     */
    public function set_vars()
    {
        $this->ajax_action_name             =   $this->plugin_prefix . '_process_user_review_action';
        $this->banner_state_option_name     =   $this->plugin_prefix . "_review_request";
        $this->start_date_option_name       =   $this->plugin_prefix . "_start_date";
        $this->banner_css_class             =   $this->plugin_prefix . "_review_request";

        $this->start_date                   =   absint(get_option($this->start_date_option_name));
        $banner_state                       =   absint(get_option($this->banner_state_option_name));
        $this->current_banner_state         =   ($banner_state == 0 ? $this->current_banner_state : $banner_state);
    }

    /**
     *	Actions on plugin activation
     *	Saves activation date
     */
    public function on_activate()
    {
        $this->reset_start_date();
    }

    /**
     *	Actions on plugin deactivation
     *	Removes activation date
     */
    public function on_deactivate()
    {
        delete_option($this->start_date_option_name);
    }

    /**
     *	Reset the start date. 
     */
    private function reset_start_date()
    {
        update_option($this->start_date_option_name, time());
    }

    /**
     *	Update the banner state 
     */
    private function update_banner_state($val)
    {
        update_option($this->banner_state_option_name, $val);
    }

    /**
     *	Prints the banner 
     */
    public function show_banner()
    {
        $this->update_banner_state(1); /* update banner active state */
?>
        <div class="<?php echo esc_attr($this->banner_css_class); ?> notice-info notice is-dismissible">
            <?php
            if ($this->webtoffee_logo_url != "") {
            ?>
                <h3 style="margin: 10px 0;"><?php echo esc_html($this->plugin_title); ?></h3>
            <?php
            }
            ?>
            <p>
                <?php echo wp_kses_post($this->banner_message); ?>
            </p>
            <p>
                <a class="button button-secondary" style="color:#333; border-color:#ccc; background:#efefef;" data-type="later"><?php echo esc_html($this->later_btn_text); ?></a>
                <a class="button button-primary" data-type="review"><?php echo esc_html($this->review_btn_text); ?></a>
            </p>
            <div class="wt-cli-review-footer" style="position: relative;">
                <span class="wt-cli-footer-icon" style="position: absolute;right: 0;bottom: 10px;"><img src="<?php echo esc_url(plugins_url(basename(plugin_dir_path(HW_CMT_ImpExpCsv_FILE))).$this->webtoffee_logo_url); ?>" style="max-width:100px;"></span>
            </div>
        </div>
    <?php
    }

    /**
     *	Ajax hook to process user action on the banner
     */
    public function process_user_action()
    {
        check_ajax_referer($this->plugin_prefix);
        if (isset($_POST['wt_review_action_type'])) {
            $action_type = sanitize_text_field(wp_unslash($_POST['wt_review_action_type']));

            /* current action is in allowed action list */
            if (in_array($action_type, $this->allowed_action_type_arr)) {
                if ($action_type == 'never' || $action_type == 'closed') {
                    $new_banner_state = 3;
                } elseif ($action_type == 'review') {
                    $new_banner_state = 4;
                } else {
                    /* reset start date to current date */
                    $this->reset_start_date();
                    $new_banner_state = 5; /* remind me later */
                }
                $this->update_banner_state($new_banner_state);
            }
        }
        exit();
    }

    /**
     *	Add banner JS to admin footer
     */
    public function add_banner_scripts()
    {
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce($this->plugin_prefix);
    ?>
        <script type="text/javascript">
            (function($) {
                "use strict";

                /* prepare data object */
                var data_obj = {
                    _wpnonce: '<?php echo esc_js($nonce); ?>',
                    action: '<?php echo esc_js($this->ajax_action_name); ?>',
                    wt_review_action_type: ''
                };

                $(document).on('click', '.<?php echo esc_attr($this->banner_css_class); ?> a.button', function(e) {
                    e.preventDefault();
                    var elm = $(this);
                    var btn_type = elm.attr('data-type');
                    if (btn_type == 'review') {
                        window.open('<?php echo esc_url($this->review_url); ?>');
                    }
                    elm.parents('.<?php echo esc_attr($this->banner_css_class); ?>').hide();

                    data_obj['wt_review_action_type'] = btn_type;
                    $.ajax({
                        url: '<?php echo esc_url($ajax_url); ?>',
                        data: data_obj,
                        type: 'POST'
                    });

                }).on('click', '.<?php echo esc_attr($this->banner_css_class); ?> .notice-dismiss', function(e) {
                    e.preventDefault();
                    data_obj['wt_review_action_type'] = 'closed';
                    $.ajax({
                        url: '<?php echo esc_url($ajax_url); ?>',
                        data: data_obj,
                        type: 'POST',
                    });

                });

            })(jQuery)
        </script>
    <?php
    }

    /**
     *	Checks the condition to show the banner
     */
    private function check_condition()
    {
        
        if ($this->current_banner_state == 1) /* currently showing then return true */ {
            return true;
        }

        if ($this->current_banner_state == 2 || $this->current_banner_state == 5) /* only waiting/remind later state */ {
            if ($this->start_date == 0) /* unable to get activated date */ {
                /* set current date as activation date*/
                $this->reset_start_date();
                return false;
            }

            $days = ($this->current_banner_state == 2 ? $this->days_to_show_banner : $this->remind_days);

            $date_to_check = $this->start_date + (86400 * $days);
            if ($date_to_check <= time()) /* time reached to show the banner */ {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Show WooCommerce Pages Banner
     * Displays promotional banners on WooCommerce pages (orders, products, users)
     */
    public function show_wc_pages_banner()
    {
        global $wt_iew_review_banner_shown;
        global $wt_iew_wc_pages_banner_shown;
        
        // Check if another plugin is already showing a WC pages banner
        if (isset($wt_iew_wc_pages_banner_shown) && $wt_iew_wc_pages_banner_shown) {
            return;
        }
        
        $screen = get_current_screen();
        $wc_pages_banners = array(
            'woocommerce_page_wc-orders' => array(
                'option_name' => 'wt_iew_hide_did_you_know_wc_orders_banner_2026',
                'cookie_name' => 'hide_cta_wc_orders',
                'content' => '<span style="color: #212121;">' . esc_html__('There\'s a faster way to manage orders. Import, export, and update orders in bulk using CSV, XML, or Excel with the Order Import Export Plugin.', 'comments-import-export-woocommerce') . '</span>',
                'plugin_url' => 'https://www.webtoffee.com/product/order-import-export-plugin-for-woocommerce/?utm_source=free_plugin&utm_medium=woocommerce_orders&utm_campaign=Order_import_export',
                'plugin_check' => 'order-import-export-for-woocommerce/order-import-export-for-woocommerce.php',
                'banner_color' => '#4750CB',
                'banner_image' => 'includes/banner/assets/images/idea_bulb_blue.svg',
                'premium_plugin' => 'wt-import-export-for-woo-order/wt-import-export-for-woo-order.php'
            ),
            'edit-product' => array(
                'option_name' => 'wt_iew_hide_did_you_know_wc_products_banner_2026',
                'cookie_name' => 'hide_cta_wc_products',
                'content' => '<span style="color: #212121;">' . esc_html__('You can now easily import and export WooCommerce products with images using CSV, XML, or Excel files.', 'comments-import-export-woocommerce') . '</span>' ,
                'plugin_url' => 'https://www.webtoffee.com/product/product-import-export-woocommerce/?utm_source=free_plugin_cross_promotion&utm_medium=all_products_tab&utm_campaign=Product_import_export',
                'plugin_check' => 'product-import-export-for-woo/product-import-export-for-woo.php',
                'banner_color' => '#7B54E0',
                'banner_image' => 'includes/banner/assets/images/idea_bulb_gloomy_purple.svg',
                'premium_plugin' => 'wt-import-export-for-woo-product/wt-import-export-for-woo-product.php'
            ),
            'users' => array(
                'option_name' => 'wt_iew_hide_did_you_know_wc_customers_banner_2026',
                'cookie_name' => 'hide_cta_wc_customers',
                'content' => '<span style="color: #212121;">' . esc_html__('Easily import and export WordPress users & WooCommerce customers to CSV, XML, or Excel for seamless data management.', 'comments-import-export-woocommerce') . '</span>',
                'plugin_url' => 'https://www.webtoffee.com/product/wordpress-users-woocommerce-customers-import-export/?utm_source=free_plugin_cross_promotion&utm_medium=woocommerce_customers&utm_campaign=User_import_export',
                'plugin_check' => 'users-customers-import-export-for-wp-woocommerce/users-customers-import-export-for-wp-woocommerce.php',
                'banner_color' => '#9D47CB',
                'banner_image' => 'includes/banner/assets/images/idea_bulb_morado_purple.svg',
                'premium_plugin' => 'wt-import-export-for-woo-user/wt-import-export-for-woo-user.php'
            )
        );

        if (!isset($wc_pages_banners[$screen->id])) {
            return;
        }

        $banner_data = $wc_pages_banners[$screen->id];

        // Check if premium plugin is active - if so, don't show the banner
        if (isset($banner_data['premium_plugin']) && is_plugin_active($banner_data['premium_plugin'])) {
            return;
        }

        // Check if banner is hidden via database option (close button) or review banner is shown
        if ( true === $wt_iew_review_banner_shown || get_option( $banner_data['option_name'], false ) ) {
            return;
        }

        // Check if banner is temporarily hidden via cookie (maybe later button)
        if (isset($_COOKIE[$banner_data['cookie_name']]) && 'true' === sanitize_text_field(wp_unslash($_COOKIE[$banner_data['cookie_name']]))) {
            return;
        }

        // Mark that a banner is being shown
        $wt_iew_wc_pages_banner_shown = true;

        $title = esc_html__('Did You Know?', 'comments-import-export-woocommerce');
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('wt_iew_wc_pages_banner');
        ?>
        <div id="wt-iew-cta-banner" class="notice notice-info" style="position: relative; padding: 15px; height: 38px; background-color: #fff; border-left: 4px solid <?php echo esc_attr($banner_data['banner_color']); ?>; display: flex; justify-content: space-between; align-items: center; border-radius: 1px; margin: 10px 0px 10px 0;">
            <button type="button" class="wt-iew-notice-dismiss" data-option-name="<?php echo esc_attr($banner_data['option_name']); ?>" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); border: none; margin: 0; padding: 0; background: none; color: #6E6E6E; cursor: pointer; font-size: 20px; line-height: 1; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">×</button>
            <div style="display: flex; align-items: center; gap: 15px; flex: 1;">
                <div style="display: flex; align-items: center; ">
                    <img src="<?php echo esc_url(HF_CMT_IM_EX_PATH_URL . $banner_data['banner_image']); ?>" style="width: 25px; margin-right: 10px; color: <?php echo esc_attr($banner_data['banner_color']); ?>;">
                    <h2 style="color: <?php echo esc_attr($banner_data['banner_color']); ?>; font-weight: 500; font-size:15px;"><?php echo esc_html($title); ?></h2>
                    <span style="margin: 0 6px; font-size: 13px; color: #212121; line-height: 1.4;"><?php echo wp_kses_post($banner_data['content']); ?></span>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; ">
                    <a href="<?php echo esc_url($banner_data['plugin_url']); ?>" target="_blank" class="button-primary" style="background: <?php echo esc_attr($banner_data['banner_color']); ?>; color: white; border: none; padding: 8px 15px; border-radius: 4px; text-decoration: none; display: flex; align-items: center; justify-content: center; font-size: 13px; height: 32px; line-height: 1;"><?php esc_html_e('Check out plugin →', 'comments-import-export-woocommerce'); ?></a>
                    <button class="wt-iew-maybe-later button-secondary" data-cookie-name="<?php echo esc_attr($banner_data['cookie_name']); ?>" style="background-color: #fff; color: #64594D; border: 1px solid #FFF; border-radius: 4px; font-size: 13px; display: flex; align-items: center; justify-content: center; height: 32px; line-height: 1;"><?php esc_html_e('Maybe later', 'comments-import-export-woocommerce'); ?></button>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            (function($) {
                // Maybe later button - uses cookie (temporary, 30 days)
                $('.wt-iew-maybe-later').on('click', function(e) {
                    e.preventDefault();
                    var cookieName = $(this).data('cookie-name');
                    document.cookie = cookieName + "=true; path=/; max-age=" + (30 * 24 * 60 * 60) + ";";
                    $(this).closest('#wt-iew-cta-banner').remove();
                });

                // Close button - saves to database (permanent)
                $('.wt-iew-notice-dismiss').on('click', function(e) {
                    e.preventDefault();
                    var optionName = $(this).data('option-name');
                    var banner = $(this).closest('#wt-iew-cta-banner');
                    
                    $.ajax({
                        url: '<?php echo esc_url($ajax_url); ?>',
                        type: 'POST',
                        data: {
                            action: 'wt_iew_dismiss_wc_pages_banner',
                            option_name: optionName,
                            nonce: '<?php echo esc_js($nonce); ?>'
                        },
                        success: function(response) {
                            banner.remove();
                        }
                    });
                });
            })(jQuery);
        </script>
        <?php
    }

    /**
     * AJAX handler for dismissing WooCommerce Pages Banner (close button)
     * Saves to database option permanently
     */
    public function dismiss_wc_pages_banner_ajax()
    {
        check_ajax_referer('wt_iew_wc_pages_banner', 'nonce');
        
        if (isset($_POST['option_name'])) {
            $option_name = sanitize_text_field(wp_unslash($_POST['option_name']));
            // Save to database - permanently hide the banner
            update_option($option_name, true);
        }
        
        wp_send_json_success();
    }
}
new Comments_import_export_Review_Request();

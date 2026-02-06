<?php

/**
 * Plugin Name: Comments Import & Export
 * Plugin URI: https://wordpress.org/plugins/comments-import-export-woocommerce/
 * Description: Import and Export WordPress Comments From and To your Website.
 * Author: WebToffee
 * Author URI: https://www.webtoffee.com/
 * Version: 2.4.8
 * Text Domain: comments-import-export-woocommerce
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('WPINC')) {
   return;
}
if (!defined('HW_CMT_IMP_EXP_ID')) {

    define("HW_CMT_IMP_EXP_ID", "hw_cmt_imp_exp");
}

if (!defined('HW_CMT_CSV_IM_EX')) {

    define("HW_CMT_CSV_IM_EX", "hw_cmt_csv_im_ex");
}

if (!defined('WBTE_CMT_IMP_EXP_VERSION')) {

    define("WBTE_CMT_IMP_EXP_VERSION", "2.4.8");
}

define('HF_CMT_IM_EX_PATH_URL',  plugin_dir_url(__FILE__));

require_once(ABSPATH."wp-admin/includes/plugin.php");
// Change the Pack IF BASIC  mention switch('BASIC') ELSE mention switch('PREMIUM')

    register_deactivation_hook(__FILE__, 'eh_deactivate_work');
    // Enter your plugin unique option name below update_option function
    function eh_deactivate_work()
    {
        update_option('cmt_ex_im_option', '');
    }
    
    if (!class_exists('HW_Product_Comments_Import_Export_CSV')) :

            /**
             * Main CSV Import class
             */
        class HW_Product_Comments_Import_Export_CSV {

            public $cron;
            public $cron_import;

                /**
                 * Constructor
                 */
                public function __construct() {
                    define('HW_CMT_ImpExpCsv_FILE', __FILE__);
                    if (is_admin()) {
                        add_action('admin_notices', array($this, 'hw_product_comments_ie_admin_notice'), 15);
                    }

                    add_filter('woocommerce_screen_ids', array($this, 'woocommerce_screen_ids'));
                    add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'hw_plugin_action_links'));
                    add_action('init', array($this, 'catch_export_request'), 20);
                    add_action('init', array($this, 'catch_save_settings'), 20);
                    add_action('admin_init', array($this, 'register_importers'));
                    
                    add_filter('admin_footer_text', array($this, 'WT_admin_footer_text'), 100);
                    add_action('wp_ajax_wcie_wt_review_plugin', array($this, "review_plugin"));
                    
                    // Add filter for BFCM banner screens
                    add_filter('wt_bfcm_banner_screens', array($this, 'wt_bfcm_banner_screens'));

                    include_once( 'includes/class-hf_cmt_impexpcsv-system-status-tools.php' );
                    include_once( 'includes/class-hf_cmt_impexpcsv-admin-screen.php' );
                    include_once( 'includes/importer/class-hf_cmt_impexpcsv-importer.php' );
                    require_once( 'includes/class-hf_cmt_impexpcsv-cron.php' );
                    
                    $this->cron = new HW_Cmt_ImpExpCsv_Cron();
                    register_activation_hook(__FILE__, array($this->cron, 'hw_new_scheduled_cmt_export'));
                    register_deactivation_hook(__FILE__, array($this->cron, 'clear_hw_scheduled_cmt_export'));


                    if (defined('DOING_AJAX')) {
                        include_once( 'includes/class-hf_cmt_impexpcsv-ajax-handler.php' );
                    }

                    require_once( 'includes/class-hf_cmt_impexpcsv-import-cron.php' );
                    $this->cron_import = new HW_Cmt_ImpExpCsv_ImportCron();
                    register_activation_hook(__FILE__, array($this->cron_import, 'hw_new_scheduled_cmt_import'));
                    register_deactivation_hook(__FILE__, array($this->cron_import, 'clear_hw_scheduled_cmt_import'));
                    
                    // uninstall feedback catch
                    include_once 'includes/class-wf-cmt_impexp-plugin-uninstall-feedback.php';
                    
                    // review request
                    include_once 'includes/class-wt-cmt_impexp-plugin-review-request.php';
                    
                    // BFCM 2025 Banner
                    include_once 'includes/banner/class-wt-bfcm-twenty-twenty-five.php';
                    
                    // EMA Banner
                    include_once 'includes/banner/class-wbte-ema-banner.php';
                }

                public function hw_plugin_action_links($links) {                    
                    $plugin_links = array(
                        '<a href="' . admin_url('admin.php?page=hw_cmt_csv_im_ex') . '">' . __('Import Export', 'comments-import-export-woocommerce') . '</a>',
                        '<a href="https://www.webtoffee.com/setting-up-wordpress-woocommerce-comments-import-export-plugin" target="_blank">' . __('Documentation', 'comments-import-export-woocommerce') . '</a>',
                        '<a href="https://wordpress.org/support/plugin/comments-import-export-woocommerce/" target="_blank">' . __('Support', 'comments-import-export-woocommerce') . '</a>',
                        '<a href="https://www.webtoffee.com/plugins/" target="_blank"  style="color:#3db634;">' . __('Premium Plugins', 'comments-import-export-woocommerce') . '</a>',
                        '<a target="_blank" style="color:#f909ff;" href="https://wordpress.org/support/plugin/comments-import-export-woocommerce/reviews#new-post">' . __('Review', 'comments-import-export-woocommerce') . '</a>',
                        );
                      if (array_key_exists('deactivate', $links)) {
                    $links['deactivate'] = str_replace('<a', '<a class="cmt-deactivate-link"', $links['deactivate']);
                }
                    return array_merge($plugin_links, $links);
                }

                function hw_product_comments_ie_admin_notice() {

                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification not needed.
                    $wf_product_Comment_ie_msg = isset($_GET["hw_product_Comment_ie_msg"]) ? sanitize_text_field(wp_unslash($_GET["hw_product_Comment_ie_msg"])) : ''; // @codingStandardsIgnoreLine.
                    if (empty($wf_product_Comment_ie_msg)) {
                        return;
                    }

                    switch ($wf_product_Comment_ie_msg) {
                        case "1":
                        echo '<div class="update"><p>' . esc_html__('Successfully uploaded via FTP.', 'comments-import-export-woocommerce') . '</p></div>';
                        break;
                        case "2":
                        echo '<div class="error"><p>' . esc_html__('Error while uploading via FTP.', 'comments-import-export-woocommerce') . '</p></div>';
                        break;
                        case "3":
                        echo '<div class="error"><p>' . esc_html__('Please choose the file in CSV format either using Method 1 or Method 2.', 'comments-import-export-woocommerce') . '</p></div>';
                        break;
                    }
                }

                /**
                 * Add screen ID
                 */
                public function woocommerce_screen_ids($ids) {
                    $ids[] = 'admin'; // For import screen
                    return $ids;
                }

                /**
                 * Catches an export request and exports the data. This class is only loaded in admin.
                 */
                public function catch_export_request() {
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification not needed.
                    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : ''; // @codingStandardsIgnoreLine.
                    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : ''; // @codingStandardsIgnoreLine.
                    
                    if (!empty($action) && !empty($page) && $page == 'hw_cmt_csv_im_ex') {
                        switch ($action) {
                            case "export" :
                            $user_ok = self::hf_user_permission();
                            if ($user_ok) {
                                include_once( 'includes/exporter/class-hf_cmt_impexpcsv-exporter.php' );
                                HW_Cmt_ImpExpCsv_Exporter::do_export();
                            } else {
                                wp_redirect(wp_login_url());
                            }
                            break;
                        }
                    }
                }

                public function catch_save_settings() {
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification not needed.
                    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : ''; // @codingStandardsIgnoreLine.
                    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : ''; // @codingStandardsIgnoreLine.
                    if (!empty($action) && !empty($page) && $page == 'hw_cmt_csv_im_ex') {
                        switch ($action) {
                            case "settings" :
                            include_once( 'includes/settings/class-hf_cmt_impexpcsv-settings.php' );
                            HW_Cmt_ImpExpCsv_Settings::save_settings();
                            break;
                        }
                    }
                }

                /**
                 * Register importers for use
                 */
                public function register_importers() {
                    register_importer('product_comments_csv', 'WooCommerce Product Comments (CSV)', __('Import <strong>product Comments</strong> to your store via a csv file.', 'comments-import-export-woocommerce'), 'HW_Cmt_ImpExpCsv_Importer::product_importer');
                    register_importer('product_comments_csv_cron', 'WooCommerce Product Comments (CSV)', __('Cron Import <strong>product Comments</strong> to your store via a csv file.', 'comments-import-export-woocommerce'), 'WF_Cmt_ImpExpCsv_ImportCron::product_importer');
                }

                public static function hf_user_permission() {
                    // Check if user has rights to export
                    $current_user = wp_get_current_user();
                    $current_user->roles = apply_filters('hf_add_user_roles', $current_user->roles);
                    $current_user->roles = array_unique($current_user->roles);
                    $user_ok = false;
                    $wf_roles = apply_filters('hf_user_permission_roles', array('administrator', 'shop_manager', 'editor', 'author'));
                    if ($current_user instanceof WP_User) {
                        $can_users = array_intersect($wf_roles, $current_user->roles);
                        if (!empty($can_users) || is_super_admin($current_user->ID)) {
                            $user_ok = true;
                        }
                    }
                    return $user_ok;
                }
                
                public function WT_admin_footer_text($footer_text) {
                    if (!self::hf_user_permission()) {
                        return $footer_text;
                    }

                    $screen = get_current_screen();
                    $allowed_screen_ids = array('comments_page_hw_cmt_csv_im_ex');
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification not needed.
                    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : ''; // @codingStandardsIgnoreLine.
                    $import = isset($_GET['import']) ? sanitize_text_field(wp_unslash($_GET['import'])) : ''; // @codingStandardsIgnoreLine.
                    
                    if (in_array($screen->id, $allowed_screen_ids) || ($page == 'hw_cmt_csv_im_ex')|| ($import == 'product_comments_csv')) {
                        
                        if (!get_option('wcie_wt_plugin_reviewed')) {
                            
                            $footer_text = wp_kses_post(sprintf(
                                // Translators: %1$s is the link to the plugin review page with 5 stars symbol.
                                __('If you like the plugin please leave us a %1$s review.', 'comments-import-export-woocommerce'), '<a href="https://wordpress.org/support/plugin/comments-import-export-woocommerce/reviews#new-post" target="_blank" class="wt-review-link" data-rated="' . esc_attr__('Thanks :)', 'comments-import-export-woocommerce') . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
                            ));

                            // $user_js = "jQuery( 'a.wt-review-link' ).on( 'click', function() {
                            //                jQuery.post( '" . esc_url(admin_url("admin-ajax.php")) . "', { action: 'wcie_wt_review_plugin' } );
                            //                jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
                            //             });";
                            // $js = "<!-- User Import JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { " . esc_js( $user_js ) . "});\n</script>\n";
                        } else {
                            $footer_text = __('Thank you for your review.', 'comments-import-export-woocommerce');
                        }
                    }

                    return '<i>' . $footer_text . '</i>';
                }

                public function review_plugin() {
                    if (!self::hf_user_permission()) {
                        wp_die(-1);
                    }
                    update_option('wcie_wt_plugin_reviewed', 1);
                    wp_die();
                }

                /**
                 * Set screens to show BFCM promotional banner
                 *
                 * @since 2.4.7
                 * @param array $screens Array of screen IDs
                 * @return array Modified array of screen IDs
                 */
                public function wt_bfcm_banner_screens( $screens ) {
                    $screens[] = 'comments_page_hw_cmt_csv_im_ex';
                    return $screens;
                }

                /**
                 * To Check if the current date is on or between the start and end date of black friday and cyber monday banner.
                 * @since 2.4.7
                 */
                public static function is_bfcm_season() {
                    $start_date   = new DateTime( '17-NOV-2025, 12:00 AM', new DateTimeZone( 'Asia/Kolkata' ) ); // Start date.
                    $current_date = new DateTime( 'now', new DateTimeZone( 'Asia/Kolkata' ) ); // Current date.
                    $end_date     = new DateTime( '04-DEC-2025, 11:59 PM', new DateTimeZone( 'Asia/Kolkata' ) ); // End date.

                    // Check if the date is on or between the start and end date of black friday and cyber monday banner for 2025.
                    if ( $current_date < $start_date || $current_date > $end_date ) {
                        return false;
                    }
                    return true;
                }

    }

            endif;

            new HW_Product_Comments_Import_Export_CSV();

/*
 *  Displays update information for a plugin. 
 */
function wt_comments_import_export_woocommerce_update_message( $data, $response )
{
    if(isset( $data['upgrade_notice']))
    {
        printf(
        '<div class="update-message wt-update-message">%s</div>',
           wp_kses_post($data['upgrade_notice'])
        );
    }
}
add_action( 'in_plugin_update_message-comments-import-export-woocommerce/hf-comments-import-export.php', 'wt_comments_import_export_woocommerce_update_message', 10, 2 );

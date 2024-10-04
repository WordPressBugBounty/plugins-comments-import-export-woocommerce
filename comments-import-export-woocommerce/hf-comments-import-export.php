<?php

/*
  Plugin Name: WordPress Comments Import & Export
  Plugin URI: https://wordpress.org/plugins/comments-import-export-woocommerce/
  Description: Import and Export WordPress Comments From and To your Website.
  Author: WebToffee
  Author URI: https://www.webtoffee.com/
  Version: 2.3.9
  Text Domain: comments-import-export-woocommerce
  License: GPLv3
  License URI: https://www.gnu.org/licenses/gpl-3.0.html
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

if (!defined('PLUGIN_VERSION')) {

    define("PLUGIN_VERSION", "2.3.9");
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
                    add_action('init', array($this, 'load_plugin_textdomain'));
                    add_action('init', array($this, 'catch_export_request'), 20);
                    add_action('init', array($this, 'catch_save_settings'), 20);
                    add_action('admin_init', array($this, 'register_importers'));
                    
                    add_filter('admin_footer_text', array($this, 'WT_admin_footer_text'), 100);
                    add_action('wp_ajax_wcie_wt_review_plugin', array($this, "review_plugin"));

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
                    
                    // WT Security Helper
                    include_once ('includes/class-wt-security-helper.php');
                    // review request
                    include_once 'includes/class-wt-cmt_impexp-plugin-review-request.php';
                }

                public function hw_plugin_action_links($links) {                    
                    $plugin_links = array(
                        '<a href="' . admin_url('admin.php?page=hw_cmt_csv_im_ex') . '">' . __('Import Export', 'comments-import-export-woocommerce') . '</a>',
                        '<a href="https://www.webtoffee.com/setting-up-wordpress-woocommerce-comments-import-export-plugin" target="_blank">' . __('Documentation', 'comments-import-export-woocommerce') . '</a>',
                        '<a href="https://wordpress.org/support/plugin/comments-import-export-woocommerce/" target="_blank">' . __('Support', 'comments-import-export-woocommerce') . '</a>',
                        '<a href="https://www.webtoffee.com/" target="_blank"  style="color:#3db634;">' . __('Premium Plugins', 'comments-import-export-woocommerce') . '</a>',
                        '<a target="_blank" href="https://wordpress.org/support/plugin/comments-import-export-woocommerce/reviews?rate=5#new-post">' . __('Review', 'comments-import-export-woocommerce') . '</a>',
                        );
                      if (array_key_exists('deactivate', $links)) {
                    $links['deactivate'] = str_replace('<a', '<a class="cmt-deactivate-link"', $links['deactivate']);
                }
                    return array_merge($plugin_links, $links);
                }

                function hw_product_comments_ie_admin_notice() {
                    global $pagenow;
                    global $post;

                    if (!isset($_GET["hw_product_Comment_ie_msg"]) && empty($_GET["hw_product_Comment_ie_msg"])) {
                        return;
                    }

                    $wf_product_Comment_ie_msg = sanitize_text_field($_GET["hw_product_Comment_ie_msg"]);

                    switch ($wf_product_Comment_ie_msg) {
                        case "1":
                        echo '<div class="update"><p>' . __('Successfully uploaded via FTP.', 'comments-import-export-woocommerce') . '</p></div>';
                        break;
                        case "2":
                        echo '<div class="error"><p>' . __('Error while uploading via FTP.', 'comments-import-export-woocommerce') . '</p></div>';
                        break;
                        case "3":
                        echo '<div class="error"><p>' . __('Please choose the file in CSV format either using Method 1 or Method 2.', 'comments-import-export-woocommerce') . '</p></div>';
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
                 * Handle localisation
                 */
                public function load_plugin_textdomain() {
                    load_plugin_textdomain('comments-import-export-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/lang/');
                }

                /**
                 * Catches an export request and exports the data. This class is only loaded in admin.
                 */
                public function catch_export_request() {
                    if (!empty($_GET['action']) && !empty($_GET['page']) && $_GET['page'] == 'hw_cmt_csv_im_ex') {
                        switch ($_GET['action']) {
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
                    if (!empty($_GET['action']) && !empty($_GET['page']) && $_GET['page'] == 'hw_cmt_csv_im_ex') {
                        switch ($_GET['action']) {
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
                    if (in_array($screen->id, $allowed_screen_ids) || (isset($_GET['page']) && $_GET['page'] == 'hw_cmt_csv_im_ex')|| (isset($_GET['import']) && $_GET['import'] == 'product_comments_csv')) {
                        if (!get_option('wcie_wt_plugin_reviewed')) {
                            $footer_text = sprintf(
                                    __('If you like the plugin please leave us a %1$s review.', 'comments-import-export-woocommerce'), '<a href="https://wordpress.org/support/plugin/comments-import-export-woocommerce/reviews?rate=5#new-post" target="_blank" class="wt-review-link" data-rated="' . esc_attr__('Thanks :)', 'comments-import-export-woocommerce') . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
                            );

                            $user_js = "jQuery( 'a.wt-review-link' ).click( function() {
                                                           jQuery.post( '" . admin_url("admin-ajax.php") . "', { action: 'wcie_wt_review_plugin' } );
                                                           jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
                                                   });";
                            $js = "<!-- User Import JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $user_js });\n</script>\n";
                            echo $js;
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
           $data['upgrade_notice']
        );
    }
}
add_action( 'in_plugin_update_message-comments-import-export-woocommerce/hf-comments-import-export.php', 'wt_comments_import_export_woocommerce_update_message', 10, 2 );

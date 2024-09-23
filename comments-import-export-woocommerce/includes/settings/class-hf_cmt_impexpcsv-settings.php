<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HW_Cmt_ImpExpCsv_Settings {

	/**
	 * Product Exporter Tool
	 */
	public static function save_settings( ) {
            
            
                $_nonce=(isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '');
            
                if(!wp_verify_nonce($_nonce, HW_CMT_IMP_EXP_ID)){
                    wp_die(__('You do not have sufficient permissions to access this page.', 'hw_csv_import_export'));
                }
 
		global $wpdb;

		$ftp_server                             = ! empty( $_POST['ftp_server'] ) ? sanitize_text_field($_POST['ftp_server']) : '';
		$ftp_user				= ! empty( $_POST['ftp_user'] ) ? wp_unslash($_POST['ftp_user']) : '';
		$ftp_password                           = ! empty( $_POST['ftp_password'] ) ? wp_unslash($_POST['ftp_password']) : '';
                $ftp_port                               = ! empty( $_POST['ftp_port'] ) ? absint($_POST['ftp_port']) : 21;
		$use_ftps                               = ! empty( $_POST['use_ftps'] ) ? true : false;
		$enable_ftp_ie                          = ! empty( $_POST['enable_ftp_ie'] ) ? true : false;
                
                
                $auto_export                            = ! empty( $_POST['auto_export'] ) ? $_POST['auto_export'] : 'Disabled';
                $auto_export_start_time                 = ! empty( $_POST['auto_export_start_time'] ) ? sanitize_text_field($_POST['auto_export_start_time']) : '';
                $auto_export_interval                   = ! empty( $_POST['auto_export_interval'] ) ? absint($_POST['auto_export_interval'] ): '';
                $export_ftp_path                        = ! empty( $_POST['export_ftp_path'] ) ? sanitize_text_field($_POST['export_ftp_path']) : '';
                $export_ftp_file_name                   = ! empty( $_POST['export_ftp_file_name'] ) ? sanitize_text_field($_POST['export_ftp_file_name']) : '';
                
                $auto_import                            = ! empty( $_POST['auto_import'] ) ? $_POST['auto_import'] : 'Disabled';
                $auto_import_start_time                 = ! empty( $_POST['auto_import_start_time'] ) ? sanitize_text_field($_POST['auto_import_start_time']) : '';
                $auto_import_interval                   = ! empty( $_POST['auto_import_interval'] ) ? absint($_POST['auto_import_interval']) : '';
                $auto_import_profile                    = ! empty( $_POST['auto_import_profile'] ) ? sanitize_text_field($_POST['auto_import_profile']) : '';
                $auto_import_merge                      = ! empty( $_POST['auto_import_merge'] ) ?  true : false;
                $ftp_server_path                        = ! empty( $_POST['ftp_server_path'] ) ? sanitize_text_field($_POST['ftp_server_path']) : '';
                $use_ftps                               = ! empty( $_POST['use_ftps'] ) ? true : false;
                $use_pasv                               = ! empty( $_POST['use_pasv'] ) ? true : false;

		$settings				= array();
		$settings[ 'ftp_server' ]		= $ftp_server;
		$settings[ 'ftp_user' ]			= $ftp_user;
		$settings[ 'ftp_password' ]		= $ftp_password;
                $settings[ 'ftp_port' ]		        = $ftp_port;
		$settings[ 'use_ftps' ]			= $use_ftps;
		$settings[ 'enable_ftp_ie' ]            = $enable_ftp_ie;
                
                $settings[ 'auto_export' ]		= $auto_export;
                $settings[ 'auto_export_start_time' ]	= $auto_export_start_time;
                $settings[ 'auto_export_interval' ]	= $auto_export_interval;
                $settings[ 'export_ftp_path' ]    	= $export_ftp_path;
                $settings[ 'export_ftp_file_name' ]	= $export_ftp_file_name;
                
                $settings[ 'auto_import' ]		= $auto_import;
                $settings[ 'auto_import_start_time' ]	= $auto_import_start_time;
                $settings[ 'auto_import_interval' ]	= $auto_import_interval;
                $settings[ 'auto_import_profile' ]	= $auto_import_profile;
                $settings[ 'auto_import_merge' ]	= $auto_import_merge;
                $settings[ 'ftp_server_path' ]	        = $ftp_server_path;
                $settings[ 'use_ftps' ]	                = $use_ftps;
                $settings[ 'use_pasv' ]	                = $use_pasv;
                
                
                
                $settings_db = get_option( 'woocommerce_'.HW_CMT_IMP_EXP_ID.'_settings', null );
                
                $orig_export_start_inverval =  '';
                if(isset($settings_db['auto_export_start_time'])&& isset($settings_db['auto_export_interval'])){
                $orig_export_start_inverval = $settings_db['auto_export_start_time'] . $settings_db['auto_export_interval'];
                }
                
                $orig_import_start_inverval =  '';
                if(isset($settings_db['auto_import_start_time'])&& isset($settings_db['auto_import_interval'])){
                $orig_import_start_inverval = $settings_db['auto_import_start_time'] . $settings_db['auto_import_interval'];
                
                }
 
		update_option( 'woocommerce_'.HW_CMT_IMP_EXP_ID.'_settings', $settings );
                // clear scheduled export event in case export interval was changed
                if ($orig_export_start_inverval !== $settings['auto_export_start_time'] . $settings['auto_export_interval'] || (!$enable_ftp_ie)) {
            
                    // note this resets the next scheduled execution time to the time options were saved + the interval
                   wp_clear_scheduled_hook('hw_cmt_csv_im_ex_auto_export_products');
                  
                }
		
                // clear scheduled import event in case import interval was changed
                if ($orig_import_start_inverval !== $settings['auto_import_start_time'] . $settings['auto_import_interval'] || (!$enable_ftp_ie)) {
                    // note this resets the next scheduled execution time to the time options were saved + the interval
                    wp_clear_scheduled_hook('hw_cmt_csv_im_ex_auto_import_products');
                }
                
		wp_redirect( admin_url( '/admin.php?page='.hw_cmt_csv_im_ex.'&tab=settings' ) );
		exit;
	}
}
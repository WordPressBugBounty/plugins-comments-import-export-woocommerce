<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HW_Cmt_ImpExpCsv_Settings {

	/**
	 * Save settings securely
	 */
	public static function save_settings() {

		$_nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';

		if ( ! wp_verify_nonce( $_nonce, HW_CMT_IMP_EXP_ID ) ) {
			wp_die( esc_html__('You do not have sufficient permissions to access this page.', 'comments-import-export-woocommerce') );
		}

		if ( ! current_user_can('manage_options') ) {
			wp_die( esc_html__('You do not have permission to save these settings.', 'comments-import-export-woocommerce') );
		}

		$ftp_server     = ! empty($_POST['ftp_server']) ? sanitize_text_field(wp_unslash($_POST['ftp_server'])) : '';
		$ftp_user       = ! empty($_POST['ftp_user']) ? sanitize_text_field(wp_unslash($_POST['ftp_user'])) : '';
		$ftp_password   = ! empty($_POST['ftp_password']) ? sanitize_text_field(wp_unslash($_POST['ftp_password'])) : '';
		$ftp_port       = isset($_POST['ftp_port']) ? absint(wp_unslash($_POST['ftp_port'])) : 21;
		$use_ftps       = ! empty($_POST['use_ftps']);
		$enable_ftp_ie  = ! empty($_POST['enable_ftp_ie']);
		$use_pasv       = ! empty($_POST['use_pasv']);

		$allowed_modes = array('Enabled', 'Disabled');

		$auto_export                 = in_array($_POST['auto_export'] ?? '', $allowed_modes, true) ? $_POST['auto_export'] : 'Disabled';
		$auto_export_start_time     = isset($_POST['auto_export_start_time']) ? sanitize_text_field(wp_unslash($_POST['auto_export_start_time'])) : '';
		$auto_export_interval       = isset($_POST['auto_export_interval']) ? absint(wp_unslash($_POST['auto_export_interval'])) : 0;

		$export_ftp_path            = ! empty($_POST['export_ftp_path']) ? sanitize_text_field(wp_unslash($_POST['export_ftp_path'])) : '';
		$export_ftp_file_name       = ! empty($_POST['export_ftp_file_name']) ? sanitize_text_field(wp_unslash($_POST['export_ftp_file_name'])) : '';

		$auto_import                = in_array($_POST['auto_import'] ?? '', $allowed_modes, true) ? $_POST['auto_import'] : 'Disabled';
		$auto_import_start_time     = isset($_POST['auto_import_start_time']) ? sanitize_text_field(wp_unslash($_POST['auto_import_start_time'])) : '';
		$auto_import_interval       = isset($_POST['auto_import_interval']) ? absint(wp_unslash($_POST['auto_import_interval'])) : 0;
		$auto_import_profile        = ! empty($_POST['auto_import_profile']) ? sanitize_text_field(wp_unslash($_POST['auto_import_profile'])) : '';
		$auto_import_merge          = ! empty($_POST['auto_import_merge']);
		$ftp_server_path            = ! empty($_POST['ftp_server_path']) ? sanitize_text_field(wp_unslash($_POST['ftp_server_path'])) : '';

		$settings = array(
			'ftp_server'               => $ftp_server,
			'ftp_user'                 => $ftp_user,
			'ftp_password'             => $ftp_password,
			'ftp_port'                 => $ftp_port,
			'use_ftps'                 => $use_ftps,
			'enable_ftp_ie'            => $enable_ftp_ie,
			'use_pasv'                 => $use_pasv,
			'auto_export'              => $auto_export,
			'auto_export_start_time'   => $auto_export_start_time,
			'auto_export_interval'     => $auto_export_interval,
			'export_ftp_path'          => $export_ftp_path,
			'export_ftp_file_name'     => $export_ftp_file_name,
			'auto_import'              => $auto_import,
			'auto_import_start_time'   => $auto_import_start_time,
			'auto_import_interval'     => $auto_import_interval,
			'auto_import_profile'      => $auto_import_profile,
			'auto_import_merge'        => $auto_import_merge,
			'ftp_server_path'          => $ftp_server_path,
		);

		$settings_db = get_option('woocommerce_' . HW_CMT_IMP_EXP_ID . '_settings', null);

		$orig_export_start_inverval = '';
		if (isset($settings_db['auto_export_start_time'], $settings_db['auto_export_interval'])) {
			$orig_export_start_inverval = $settings_db['auto_export_start_time'] . $settings_db['auto_export_interval'];
		}

		$orig_import_start_inverval = '';
		if (isset($settings_db['auto_import_start_time'], $settings_db['auto_import_interval'])) {
			$orig_import_start_inverval = $settings_db['auto_import_start_time'] . $settings_db['auto_import_interval'];
		}

		update_option('woocommerce_' . HW_CMT_IMP_EXP_ID . '_settings', $settings);

		if ($orig_export_start_inverval !== $auto_export_start_time . $auto_export_interval || ! $enable_ftp_ie) {
			wp_clear_scheduled_hook('hw_cmt_csv_im_ex_auto_export_products');
		}
		if ($orig_import_start_inverval !== $auto_import_start_time . $auto_import_interval || ! $enable_ftp_ie) {
			wp_clear_scheduled_hook('hw_cmt_csv_im_ex_auto_import_products');
		}

		wp_redirect(admin_url('/admin.php?page=hw_cmt_csv_im_ex&tab=settings'));
		exit;
	}
}

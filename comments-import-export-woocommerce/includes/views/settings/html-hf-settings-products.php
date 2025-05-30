<?php
$settings = get_option('woocommerce_' . HW_CMT_IMP_EXP_ID . '_settings', null);
$ftp_server = isset($settings['ftp_server']) ? $settings['ftp_server'] : '';
$ftp_user = isset($settings['ftp_user']) ? $settings['ftp_user'] : '';
$ftp_password = isset($settings['ftp_password']) ? $settings['ftp_password'] : '';
$use_ftps = isset($settings['use_ftps']) ? $settings['use_ftps'] : '';
$ftp_port = isset($settings['ftp_port']) ? $settings['ftp_port'] : 21;
$enable_ftp_ie = isset($settings['enable_ftp_ie']) ? $settings['enable_ftp_ie'] : '';

$auto_export = isset($settings['auto_export']) ? $settings['auto_export'] : 'Disabled';
$auto_export_start_time = isset($settings['auto_export_start_time']) ? $settings['auto_export_start_time'] : '';
$auto_export_interval = isset($settings['auto_export_interval']) ? $settings['auto_export_interval'] : '';
$export_ftp_path = isset($settings['export_ftp_path']) ? $settings['export_ftp_path'] : '';
$export_ftp_file_name = isset($settings['export_ftp_file_name']) ? $settings['export_ftp_file_name'] : '';

$auto_import = isset($settings['auto_import']) ? $settings['auto_import'] : 'Disabled';
$auto_import_start_time = isset($settings['auto_import_start_time']) ? $settings['auto_import_start_time'] : '';
$auto_import_interval = isset($settings['auto_import_interval']) ? $settings['auto_import_interval'] : '';
$auto_import_profile = isset($settings['auto_import_profile']) ? $settings['auto_import_profile'] : '';
$auto_import_merge = isset($settings['auto_import_merge']) ? $settings['auto_import_merge'] : 0;
$ftp_server_path = isset($settings['ftp_server_path']) ? $settings['ftp_server_path'] : '';
$use_pasv = isset($settings['use_pasv']) ? $settings['use_pasv'] : '';

if (function_exists('WC')) {
    $timefor = wc_time_format();
    $datefor = wc_date_format();
} else {
    $timefor = apply_filters('woocommerce_time_format', get_option('time_format'));
    $datefor = apply_filters('woocommerce_date_format', get_option('date_format'));
}
wp_localize_script('woocommerce-product-csv-importer', 'woocommerce_product_csv_importer_params', array('auto_export' => $auto_export, 'auto_import' => $auto_import));
if ($scheduled_timestamp = wp_next_scheduled('hw_cmt_csv_im_ex_auto_export_products')) {
    $scheduled_desc = sprintf(__('The next export is scheduled on <code>%s</code>', 'comments-import-export-woocommerce'), get_date_from_gmt(date('Y-m-d H:i:s', $scheduled_timestamp), $datefor . ' ' . $timefor));
} else {
        $scheduled_desc = __('There is no export scheduled.', 'comments-import-export-woocommerce');
}
if ($scheduled_import_timestamp = wp_next_scheduled('hw_cmt_csv_im_ex_auto_import_products')) {
    $scheduled_import_desc = sprintf(__('The next import is scheduled on <code>%s</code>', 'comments-import-export-woocommerce'), get_date_from_gmt(date('Y-m-d H:i:s', $scheduled_import_timestamp), $datefor . ' ' . $timefor));
} else {
    $scheduled_import_desc = __('There is no import scheduled.', 'comments-import-export-woocommerce');
}
?>
<div class="tool-box bg-white p-20p pipe-view">
    <form action="<?php echo esc_url(admin_url('admin.php?page=hw_cmt_csv_im_ex&action=settings')); ?>" method="post">
                <?php wp_nonce_field(HW_CMT_IMP_EXP_ID) ?>

        <table class="form-table">
            <tr>
                <th>
                    <h3 class="title"><?php esc_html_e('FTP Settings for Export', 'comments-import-export-woocommerce'); ?></h3>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="enable_ftp_ie"><?php esc_html_e('Enable FTP', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="enable_ftp_ie" id="enable_ftp_ie" class="checkbox" <?php checked($enable_ftp_ie, 1); ?> />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="ftp_server"><?php esc_html_e('FTP Server Host/IP', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="text" name="ftp_server" id="ftp_server" placeholder="<?php esc_html_e('XXX.XXX.XXX.XXX', 'comments-import-export-woocommerce'); ?>" value="<?php echo esc_attr($ftp_server); ?>" class="input-text" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="ftp_user"><?php esc_html_e('FTP User Name', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="text" name="ftp_user" id="ftp_user"  value="<?php echo esc_attr($ftp_user); ?>" class="input-text" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="ftp_password"><?php esc_html_e('FTP Password', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="password" name="ftp_password" id="ftp_password"  value="<?php echo esc_attr($ftp_password); ?>" class="input-text" />
                </td>
            </tr>
             <tr>
                <th>
                    <label for="ftp_port"><?php esc_html_e('FTP Port', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="text" name="ftp_port" id="ftp_port" placeholder="21 (default) " value="<?php if(isset($ftp_port)) echo esc_attr($ftp_port); ?>" class="input-text" />
<!--                  <p style="font-size: 12px"><?php esc_html_e('Enter your port number', 'comments-import-export-woocommerce'); ?></p>-->
                </td>
            </tr>
            <tr>
                <th>
                    <label for="use_ftps"><?php esc_html_e('Use FTPS', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="use_ftps" id="use_ftps" class="checkbox" <?php checked($use_ftps, 1); ?> />
                </td>
            </tr>

            <tr>
                <th>
                    <label for="use_pasv"><?php esc_html_e('Enable Passive mode', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="use_pasv" id="use_pasv" class="checkbox"  <?php checked($use_pasv, 1); ?>/>
                </td> 
            </tr>
            
            <tr>
                <th>
                    <label for="export_ftp_path"><?php esc_html_e('Export Path', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="text" name="export_ftp_path" id="export_ftp_path"  value="<?php echo esc_attr($export_ftp_path); ?>"/>

                </td>
            </tr>

            <tr>
                <th>
                    <label for="export_ftp_file_name"><?php esc_html_e('Export Filename', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="text" name="export_ftp_file_name" id="export_ftp_file_name"  value="<?php echo esc_attr($export_ftp_file_name); ?>" placeholder="<?php echo esc_attr__('For example sample.csv', 'comments-import-export-woocommerce'); ?>"/>

                </td>
            </tr>


            <tr>
                <th>
                    <label for="auto_export"><?php esc_html_e('Automatically Export WordPress Comments', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <select class="" style="" id="auto_export" name="auto_export">
                        <option <?php selected($auto_export, 'Disabled'); ?> value="Disabled"><?php esc_html_e('Disabled', 'comments-import-export-woocommerce'); ?></option>
                        <option <?php selected($auto_export, 'Enabled'); ?> value="Enabled"><?php esc_html_e('Enabled', 'comments-import-export-woocommerce'); ?></option>
                    </select>
                </td>
            </tr>
            <tbody class="export_section">
                <tr>
                    <th>
                        <label for="auto_export_start_time"><?php esc_html_e('Export Start Time', 'comments-import-export-woocommerce'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="auto_export_start_time" id="auto_export_start_time"  value="<?php echo esc_attr($auto_export_start_time); ?>"/>
                        <span class="description"><?php echo sprintf(wp_kses_post('Local time is <code>%s</code>.', 'comments-import-export-woocommerce'), esc_html(date_i18n($timefor))) . ' ' . esc_html($scheduled_desc); ?></span>
                        <br/>
                        <span class="description"><?php echo wp_kses_post('<code>Enter like 6:18pm or 12:27am</code>', 'comments-import-export-woocommerce'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="auto_export_interval"><?php esc_html_e('Export Interval [ Minutes ]', 'comments-import-export-woocommerce'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="auto_export_interval" id="auto_export_interval"  value="<?php echo esc_attr($auto_export_interval); ?>"  />
                    </td>
                </tr>
            </tbody>





            <tr>
                <th>
                    <label for="auto_import"><?php esc_html_e('Automatically Import WordPress Comments', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <select class="" style="" id="auto_import" name="auto_import">
                        <option <?php selected($auto_import, 'Disabled'); ?> value="Disabled"><?php esc_html_e('Disabled', 'comments-import-export-woocommerce'); ?></option>
                        <option <?php selected($auto_import, 'Enabled'); ?> value="Enabled"><?php esc_html_e('Enabled', 'comments-import-export-woocommerce'); ?></option>
                    </select>
                </td>
            </tr>
            <tbody class="import_section">
                  <tr>
                    <th>
                        <label for="ftp_server_path"><?php esc_html_e('FTP Server Path', 'comments-import-export-woocommerce'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="ftp_server_path" id="ftp_server_path"  value="<?php echo esc_attr($ftp_server_path); ?>" class="input-text" />
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="auto_import_start_time"><?php esc_html_e('Import Start Time', 'comments-import-export-woocommerce'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="auto_import_start_time" id="auto_export_start_time"  value="<?php echo esc_attr($auto_import_start_time); ?>"/>
                        <span class="description"><?php echo sprintf(esc_html__('Local time is <code>%s</code>.', 'comments-import-export-woocommerce'), esc_html(date_i18n($timefor))) . ' ' . esc_html($scheduled_import_desc); ?></span>
                        <br/>
                        <span class="description"><?php esc_html_e('<code>Enter like 6:18pm or 12:27am</code>', 'comments-import-export-woocommerce'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="auto_import_interval"><?php esc_html_e('Import Interval [ Minutes ]', 'comments-import-export-woocommerce'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="auto_import_interval" id="auto_export_interval"  value="<?php echo esc_attr($auto_import_interval); ?>"  />
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="auto_import_merge"><?php esc_html_e('Update Comment if exist', 'comments-import-export-woocommerce'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="auto_import_merge" id="auto_import_merge"  class="checkbox" <?php checked($auto_import_merge, 1); ?> />
                    </td>
                </tr>
              


<?php
$mapping_from_db = get_option('hw_prod_cmt_csv_imp_exp_mapping');
if (!empty($mapping_from_db)) {
    ?>
                    <tr>
                        <th>
                            <label for="auto_import_profile"><?php esc_html_e('Select a mapping file.'); ?></label>
                        </th>
                        <td>
                            <select name="auto_import_profile">
                                <option value="">--Select--</option>
    <?php foreach ($mapping_from_db as $key => $value) { ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $auto_import_profile); ?>><?php echo esc_html($key); ?></option>

                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                            <?php } ?>

            </tbody>        


        </table>

        <p class="submit"><input type="submit" class="button button-primary" value="<?php esc_html_e('Save Settings', 'comments-import-export-woocommerce'); ?>" /></p>

    </form>
</div>
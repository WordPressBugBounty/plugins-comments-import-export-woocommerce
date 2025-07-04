<?php

$ftp_server = '';
$ftp_user = '';
$ftp_password = '';
$use_ftps = '';
$enable_ftp_ie = '';
$ftp_server_path = '';
$use_pasv = '';
if (!empty($ftp_settings)) {
	$ftp_server = !empty($ftp_settings['ftp_server']) ? $ftp_settings['ftp_server'] : '';
	$ftp_user = !empty($ftp_settings['ftp_user']) ? $ftp_settings['ftp_user'] : '';
	$ftp_password = !empty($ftp_settings['ftp_password']) ? $ftp_settings['ftp_password'] : '';
	$ftp_port = !empty($ftp_settings['ftp_port']) ? $ftp_settings['ftp_port'] : 21;
	$use_ftps = !empty($ftp_settings['use_ftps']) ? $ftp_settings['use_ftps'] : '';
	$use_pasv = !empty($ftp_settings['use_pasv']) ? $ftp_settings['use_pasv'] : '';
	$enable_ftp_ie = !empty($ftp_settings['enable_ftp_ie']) ? $ftp_settings['enable_ftp_ie'] : '';
	$ftp_server_path = !empty($ftp_settings['ftp_server_path']) ? $ftp_settings['ftp_server_path'] : '';
}

?>
<div class="tool-box bg-white p-20p pipe-view" style="width: 100%">
	<p><?php esc_html_e('You can import comments (in CSV format) in to the shop using any of below methods.', 'comments-import-export-woocommerce'); ?></p>

	<?php if (! empty($upload_dir['error'])) : ?>
		<div class="error">
			<p><?php esc_html_e('Before you can upload your import file, you will need to fix the following error:'); ?></p>
			<p><strong><?php echo esc_html($upload_dir['error']); ?></strong></p>
		</div>
	<?php else : ?>
		<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>">
			<table class="form-table">
				<tbody>
					<tr>
						<th>
							<label for="upload"><?php esc_html_e('Method 1: Select a file from your computer'); ?></label>
						</th>
						<td>
							<input type="file" id="upload" name="import" size="25" />
							<input type="hidden" name="action" value="save" />
							<input type="hidden" name="max_file_size" value="<?php echo esc_attr(absint($bytes)); ?>" />
							<small><?php printf(esc_html__('Maximum size: %s', 'comments-import-export-woocommerce'), esc_html($size)); ?></small>

						</td>
					</tr>

					<tr>
						<th>
							<label for="ftp"><?php esc_html_e('Method 2: Provide FTP Details:', 'comments-import-export-woocommerce'); ?></label>
						</th>
						<td>
							<table class="form-table">
								<tr>
									<th>
										<label for="enable_ftp_ie"><?php esc_html_e('Enable FTP import', 'comments-import-export-woocommerce'); ?></label>
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
										<input type="text" name="ftp_user" id="ftp_user" value="<?php echo esc_attr($ftp_user); ?>" class="input-text" />
									</td>
								</tr>
								<tr>
									<th>
										<label for="ftp_password"><?php esc_html_e('FTP Password', 'comments-import-export-woocommerce'); ?></label>
									</th>
									<td>
										<input type="password" name="ftp_password" id="ftp_password" value="<?php echo esc_attr($ftp_password); ?>" class="input-text" />
									</td>
								</tr>
								<tr>
									<th>
										<label for="ftp_ftp_port"><?php esc_html_e('FTP Port', 'comments-import-export-woocommerce'); ?></label>
									</th>
									<td>
										<input type="text" name="ftp_port" id="ftp_port" placeholder="21 (default) " value="<?php if (isset($ftp_port)) echo esc_attr($ftp_port); ?>" class="input-text" />
										<!--                                                                                  <p style="font-size: 12px"><?php esc_html_e('Enter your port number', 'comments-import-export-woocommerce'); ?></p>-->
									</td>
								</tr>
								<tr>
									<th>
										<label for="ftp_server_path"><?php esc_html_e('FTP Server Path', 'comments-import-export-woocommerce'); ?></label>
									</th>
									<td>
										<input type="text" name="ftp_server_path" id="ftp_server_path" value="<?php echo esc_attr($ftp_server_path); ?>" class="input-text" />
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
										<input type="checkbox" name="use_pasv" id="use_pasv" class="checkbox" <?php checked($use_pasv, 1); ?> />
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php
					$mapping_from_db = get_option('hw_prod_comment_csv_imp_exp_mapping');
					if (!empty($mapping_from_db)) {
					?>
						<tr>
							<th>
								<label for="profile"><?php esc_html_e('Select a mapping file.'); ?></label>
							</th>
							<td>
								<select name="profile">
									<option value="">--Select--</option>
									<?php foreach ($mapping_from_db as $key => $value) { ?>
										<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($key); ?></option>

									<?php } ?>
								</select>
							</td>
						</tr>
					<?php } ?>
					<tr>
						<th><label><?php esc_html_e('Delimiter', 'comments-import-export-woocommerce'); ?></label><br /></th>
						<td><input type="text" name="delimiter" placeholder="," size="2" /></td>
					</tr>

				</tbody>
			</table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e('Upload file and import'); ?>" />
			</p>
		</form>
	<?php endif; ?>
</div>
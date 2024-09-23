<div class="wrap woocommerce">
	<div class="icon32" id="icon-woocommerce-importer"><br></div>
    <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=hw_cmt_csv_im_ex') ?>" class="nav-tab <?php echo ($tab == 'import') ? 'nav-tab-active' : ''; ?>"><?php _e('WordPress Comments Import / Export', 'comments-import-export-woocommerce'); ?></a>
		<a href="<?php echo admin_url('admin.php?page=hw_cmt_csv_im_ex&tab=settings') ?>" class="nav-tab <?php echo ($tab == 'settings') ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'comments-import-export-woocommerce'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=hw_cmt_csv_im_ex&tab=help'); ?>" class="nav-tab <?php echo ('help' == $tab) ? 'nav-tab-active' : ''; ?>"><?php _e('Help', 'comments-import-export-woocommerce'); ?></a>
		<a href="<?php echo admin_url('admin.php?page=hw_cmt_csv_im_ex&tab=othersolutions'); ?>" class="nav-tab <?php echo ('othersolutions' == $tab) ? 'nav-tab-active' : ''; ?>"><?php _e('Other Solutions', 'comments-import-export-woocommerce'); ?></a>
    </h2>
	<?php
	if ('othersolutions' !== $tab) {
		include('market.php');
	}
	?>
	<?php
	switch ($tab) {
		case "help" :
			$this->admin_help_page();
			break;
		case "settings" :
			$this->admin_settings_page();
			break;
		case "othersolutions" :
			$this->admin_othersolutions_page();
			break;

		default :
			$this->admin_import_page();
			break;
	}
	?>
</div>
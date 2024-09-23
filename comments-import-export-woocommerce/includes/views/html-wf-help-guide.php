<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style>
    .help-guide .cols {
        display: flex;
    }
    .help-guide .inner-panel {
        padding: 40px;
        background-color: #FFF;
        margin: 15px 10px;
        box-shadow: 1px 1px 5px 1px rgba(0,0,0,.1);
        text-align: center;
    }
    .help-guide .inner-panel p{
        margin-bottom: 20px;
    }
    .help-guide .inner-panel img{
        margin:30px 15px 0;
    }

</style>
<div class="pipe-main-box">
    <div class="tool-box bg-white p-20p pipe-view">
        <div id="tab-help" class="coltwo-col panel help-guide">
            <div class="cols">
                <div class="inner-panel" style="">
                    <img src="<?php echo HF_CMT_IM_EX_PATH_URL . '/images/setup.png'; ?>"/>
                    <h3><?php _e('How-to-setup', 'comments-import-export-woocommerce'); ?></h3>
                    <p style=""><?php _e('Read the set-up guide to get started with the plugin', 'comments-import-export-woocommerce'); ?></p>
                    <a href="https://www.webtoffee.com/setting-up-wordpress-woocommerce-comments-import-export-plugin/" target="_blank" class="button button-primary">
                        <?php _e('Setup Guide', 'comments-import-export-woocommerce'); ?></a>
                </div>

                <div class="inner-panel" style="">
                    <img src="<?php echo HF_CMT_IM_EX_PATH_URL . '/images/documentation.png'; ?>"/>
                    <h3><?php _e('Documentation', 'comments-import-export-woocommerce'); ?></h3>
                    <p style=""><?php _e('Refer to our documentation to set and get started', 'comments-import-export-woocommerce'); ?></p>
                    <a target="_blank" href="https://www.webtoffee.com/category/documentation/product-import-export-plugin-for-woocommerce/" class="button-primary"><?php _e('Documentation', 'wf_csv_import_export'); ?></a> 
                </div>

                <div class="inner-panel" style="">
                    <img src="<?php echo HF_CMT_IM_EX_PATH_URL . '/images/support.png'; ?>"/>
                    <h3><?php _e('Support', 'comments-import-export-woocommerce'); ?></h3>
                    <p style=""><?php _e('We would love to help you on any queries or issues.', 'comments-import-export-woocommerce'); ?></p>
                    <a href="https://wordpress.org/support/plugin/comments-import-export-woocommerce/" target="_blank" class="button button-primary">
                        <?php _e('Contact Us', 'comments-import-export-woocommerce'); ?></a>
                </div>
            </div>
        </div>
    </div>
    
</div>
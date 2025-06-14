<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="market-box table-box-main">
    <?php /*<div class="getting-started-video">
        <h2><?php esc_html_e('Watch getting started video', 'comments-import-export-woocommerce');?></h2>
    <iframe src="https://www.youtube.com/embed/L-01qI1EZWE?rel=0&showinfo=0" frameborder="0" allowfullscreen="allowfullscreen" align="center"></iframe>
    </div> */?>
    <div class="pipe-review-widget">
        <?php
        echo  sprintf(wp_kses_post('<div class=""><p><i>If you like the plugin please leave us a %1$s review!</i><p></div>', 'comments-import-export-woocommerce'), '<a href="https://wordpress.org/support/plugin/comments-import-export-woocommerce/reviews#new-post" target="_blank" class="xa-pipe-rating-link" data-reviewed="' . esc_attr__('Thanks for the review.', 'comments-import-export-woocommerce') . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>');
        ?>
    </div>
	
	<div class="wt-ierpro-header wt-comment-pipe-premium-features">
		<div class="wt-comment-ierpro-name">
			<img src="<?php echo esc_url(HF_CMT_IM_EX_PATH_URL. 'images/wt-crown-gold.png'); ?>" alt="featured img" width="91" height="65">			
		</div>
		<div class="wt-comment-ierpro-mainfeatures">
			<ul>
				<li class="money-back"><?php esc_html_e('30 Day Money Back Guarantee'); ?></li>
				<li class="support"><?php esc_html_e('Fast and Superior Support'); ?></li>
				<li class="pipe-support"><?php esc_html_e('Third party plugin support'); ?></li>
			</ul>
			<div class="wt-ierpro-btn-wrapper">
				<a href="https://www.webtoffee.com/plugins/" class="wt-ierpro-blue-btn" target="_blank"><?php esc_html_e('PREMIUM PLUGINS'); ?></a>
			</div>                
		</div>
	</div>
	
    <div class="wt-comment-pipe-premium-features" style="margin-top: 20px;">
        <span >
            <h2 style="text-align: center; padding: 25px;"><?php esc_html_e('Related free plugins from WebToffee', 'comments-import-export-woocommerce');?></h2>
            <hr class="wt-ier-comment-hr"><!-- comment -->
			<ul class="ticked-list">
                <li><a href="https://wordpress.org/plugins/product-import-export-for-woo/" target="_blank" class=""><?php esc_html_e('Product Import Export for WooCommerce', 'comments-import-export-woocommerce'); ?></a> </li>                
                <li><a href="https://wordpress.org/plugins/users-customers-import-export-for-wp-woocommerce/" target="_blank" class=""><?php esc_html_e('Import Export WordPress Users and WooCommerce Customers', 'comments-import-export-woocommerce'); ?></a> </li>
                <li><a href="https://wordpress.org/plugins/order-import-export-for-woocommerce/" target="_blank" class=""><?php esc_html_e('Order Export & Order Import for WooCommerce', 'comments-import-export-woocommerce'); ?></a> </li>
                <li><a href="https://wordpress.org/plugins/wp-migration-duplicator/" target="_blank" class=""><?php esc_html_e('WordPress Backup & Migration', 'comments-import-export-woocommerce'); ?></a> </li>
                <li><a href="https://wordpress.org/plugins/webtoffee-product-feed/" target="_blank" class=""><?php esc_html_e('Product Feed for WooCommerce – Google Shopping Feed, Pinterest Feed, TikTok Ads & More', 'comments-import-export-woocommerce'); ?></a> </li>
            </ul>
                
        
        </span>
        
    </div>
    
    
    </div>

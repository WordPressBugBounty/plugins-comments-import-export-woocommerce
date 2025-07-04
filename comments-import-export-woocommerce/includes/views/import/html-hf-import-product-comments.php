<div class="tool-box bg-white p-20p pipe-view">
    <h3 class="title"><?php esc_html_e('Import Comments in CSV Format:', 'comments-import-export-woocommerce'); ?></h3>
    <p><?php esc_html_e('Import comments in CSV format from different sources (  from your computer OR from another server via FTP )', 'comments-import-export-woocommerce'); ?></p>
    <p class="submit">
        <?php
        $merge_url = admin_url('admin.php?import=product_comments_csv&merge=1');
        $import_url = admin_url('admin.php?import=product_comments_csv');
        ?>
        <a class="button button-primary" id="mylink" href="<?php echo esc_url(admin_url('admin.php?import=product_comments_csv')); ?>"><?php esc_html_e('Import Comments', 'comments-import-export-woocommerce'); ?></a>
        &nbsp;
        <input type="checkbox" id="merge" value="0"><?php esc_html_e('Merge comments if exists', 'comments-import-export-woocommerce'); ?> <br>
    </p>
</div>
<script type="text/javascript">
    jQuery('#merge').click(function () {
        if (this.checked) {
            jQuery("#mylink").attr("href", '<?php echo esc_url_raw($merge_url); ?>');
        } else {
            jQuery("#mylink").attr("href", '<?php echo esc_url($import_url); ?>');
        }
    });
</script>
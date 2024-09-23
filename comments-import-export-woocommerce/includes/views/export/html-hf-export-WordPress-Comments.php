<div class="tool-box bg-white p-20p pipe-view">
    <h3 class="title"><?php _e('Export Comments in CSV Format:', 'comments-import-export-woocommerce'); ?></h3>
    <p><?php _e('Export and download your Comments in CSV format. This file can be used to import Comments back into your Woocommerce shop.', 'comments-import-export-woocommerce'); ?></p>
    <form action="<?php echo admin_url('admin.php?page=hw_cmt_csv_im_ex&action=export'); ?>" method="post">
    <?php wp_nonce_field('comments-import-export-woocommerce') ?>

        <table class="form-table">
            <tr>
                <th>
                    <label for="v_limit"><?php _e('Limit', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="number" min="1" name="limit" id="v_limit" placeholder="<?php _e('Unlimited', 'comments-import-export-woocommerce'); ?>" class="input-text" />
                    <p style="font-size: 12px"><?php _e('The number of Comments to return.', 'comments-import-export-woocommerce'); ?></p>
                </td>
            </tr>
                 <tr>
                <th>
                    <label for="v_date"><?php _e('Date', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input  name="cmt_date_from" id="datepicker1" placeholder="<?php _e('From date', 'comments-import-export-woocommerce'); ?>" class="input-text" /> -
                     <input name="cmt_date_to" id="datepicker2" placeholder="<?php _e('To date', 'comments-import-export-woocommerce'); ?>" class="input-text" />
                    <p style="font-size: 12px"><?php _e('The comments date.', 'comments-import-export-woocommerce'); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_limit"><?php _e('WooDiscuz Comments', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="checkbox"  id="wodis_enable" name="woo_enable[]" value="1" onclick="ShowHideDiv(this)" /><?php _e('Enable', 'comments-import-export-woocommerce'); ?>
                </td>
            </tr>
            <script type="text/javascript">
                     jQuery( function( $ ) {
                        var product  = jQuery ( '#p_woodis').closest( 'tr' );
                        var article  = jQuery ( '#a_woodis').closest( 'tr' );
                        $( '#wodis_enable' ).change(function(){
                            if ( $( this ).is( ':checked' ) ) {
                                    $( product ).show();
                                    $( article ).hide();
                            } else {
                                    $( article ).show();
                                    $( product ).hide();
                                }
                        }).change();
                    });
            </script>
            <tr>
             
                <th id='p_woodis'>
                   
                    <label for="v_prods"><?php _e('Products', 'comments-import-export-woocommerce'); ?></label>
                   
                    </th>
                <td >
                
            <div id='p_woodis_body'>
                    <select id="v_prods" name="products[]" data-placeholder=" <?php _e('All Products', 'comments-import-export-woocommerce'); ?>" class="wc-enhanced-select" multiple="multiple">
                        <?php
                            $args = array(
                                'posts_per_page'   => -1,
                                'post_type'        => 'Product',
                                'post_status'      => 'publish',
                                'suppress_filters' => true 
                            );
                            $products   = get_posts($args);
                            foreach ($products as $product) {
                                echo '<option value="' . $product->ID . '">' . $product->post_title . '</option>';
                            }
                        ?>
                    </select>
                                                        
                    <p style="font-size: 12px"><?php _e('Comments under these Products will be exported.', 'comments-import-export-woocommerce'); ?></p>
           </div>                    
                </td>
           
                 </tr>
            <tr>
                <th id='a_woodis'>
                    <label for="v_prods"><?php _e('Articles', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
           <div id='a_woodis_body'>
           
                    <select id="v_article" name="articles[]" data-placeholder="<?php _e(' All Articles', 'comments-import-export-woocommerce'); ?>" class="wc-enhanced-select" multiple="multiple">
                        <?php
                            $args = array(
                                'posts_per_page'   => -1,
                                'post_type'        => 'Post',
                                'post_status'      => 'publish',
                                'suppress_filters' => true 
                            );
                            $articles   = get_posts($args);
                            foreach ($articles as $product) {
                                echo '<option value="' . $product->ID . '">' . $product->post_title . '</option>';
                            }
                        ?>
                    </select>
                                                        
                    <p style="font-size: 12px"><?php _e('Comments under these Articles will be exported.', 'comments-import-export-woocommerce'); ?></p>
                </td>
                </div>
           </tr>
            <tr>
                <th>
                    <label for="v_delimiter"><?php _e('Delimiter', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="text" name="delimiter" id="v_delimiter" placeholder="<?php _e(',', 'comments-import-export-woocommerce'); ?>" class="input-text" />
                    <p style="font-size: 12px"><?php _e('Column seperator for exported file', 'comments-import-export-woocommerce'); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_columns"><?php _e('Columns', 'comments-import-export-woocommerce'); ?></label>
                </th>
            <table id="datagrid">
                <th style="text-align: left;">
                    <label for="v_columns"><?php _e('Column', 'comments-import-export-woocommerce'); ?></label>
                </th>
                <th style="text-align: left;">
                    <label for="v_columns_name"><?php _e('Column Name', 'comments-import-export-woocommerce'); ?></label>
                </th>
                
                <?php foreach ($post_columns as $pkey => $pcolumn) {
                            $ena=($pkey =='comment_alter_id')?'style="display:none;"':'';
                         ?>
            <tr <?php echo $ena; ?> >
                <td>
                    
                    <input name= "columns[<?php echo $pkey; ?>]" type="checkbox"  value="<?php echo $pkey; ?>" checked>
                    <label for="columns[<?php echo $pkey; ?>]"><?php _e($pcolumn, 'comments-import-export-woocommerce'); ?></label>
                </td>
                <td>
                    <?php 
                    $tmpkey = $pkey;
                    if (strpos($pkey, 'yoast') === false) {
                            $tmpkey = ltrim($pkey, '_');
                        }
                    ?>
                     <input type="text" name="columns_name[<?php echo $pkey; ?>]"  value="<?php echo $tmpkey; ?>" class="input-text" />
                </td>
            </tr>
                <?php } ?>
                
            </table><br/>
            </tr>
            
            
        </table>
        <p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Export Comments', 'comments-import-export-woocommerce'); ?>" /></p>
    </form>
</div>
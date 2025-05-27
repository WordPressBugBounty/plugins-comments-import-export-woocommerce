<form action="<?php echo esc_url(admin_url('admin.php?import=' . $this->import_page . '&step=2&merge=' . $merge)); ?>" method="post">
    <?php wp_nonce_field('import-options'); ?>
    <input type="hidden" name="import_id" value="<?php echo esc_attr($this->id); ?>" />
    <?php if ($this->file_url_import_enabled) : ?>
        <input type="hidden" name="import_url" value="<?php echo esc_attr($this->file_url); ?>" />
    <?php endif; ?>
    <h3><?php esc_html_e('Map Fields', 'comments-import-export-woocommerce'); ?></h3>
    <?php if ($this->profile == '') { ?>
        <?php esc_html_e('Mapping file name:', 'comments-import-export-woocommerce'); ?> <input type="text" name="profile" value="" placeholder="Enter filename to save" />
    <?php } else { ?>
        <input type="hidden" name="profile" value="<?php echo esc_attr($this->profile); ?>" />
    <?php } ?>
    <p><?php esc_html_e('Here you can map your imported columns to product data fields.', 'comments-import-export-woocommerce'); ?></p>
    <table class="widefat widefat_importer">
        <thead>
            <tr>
                <th><?php esc_html_e('Map to', 'comments-import-export-woocommerce'); ?></th>
                <th><?php esc_html_e('Column Header', 'comments-import-export-woocommerce'); ?></th>
                <th><?php esc_html_e('Evaluation Field', 'comments-import-export-woocommerce'); ?>
                    <?php $plugin_url = HW_Cmt_ImpExpCsv_Admin_Screen::hw_get_wc_path(); ?>
                    <?php if (function_exists('WC')) { ?>
                        <img class="help_tip" style="float:none;" data-tip="<?php esc_html_e('Assign constant value Webtoffee to comment_author:</br>=Webtoffee</br>Append a value By Webtoffee to comments_content:</br>&By Webtoffee</br>Prepend a value Webtoffee to comments_content:</br>&Webtoffee [VAL].', 'comments-import-export-woocommerce'); ?>" src="<?php echo esc_url($plugin_url); ?>/assets/images/help.png" height="20" width="20" />
                    <?php } else { ?>
                        <img class="help_tip" style="float:none;" data-tip="<?php esc_html_e('Assign constant value Webtoffee to comment_author:</br>=Webtoffee</br>Append a value By Webtoffee to comments_content:</br>&By Webtoffee</br>Prepend a value Webtoffee to comments_content:</br>&Webtoffee [VAL].', 'comments-import-export-woocommerce'); ?>" src="<?php echo esc_url($plugin_url); ?>/images/help.png" height="20" width="20" />
                    <?php } ?>


                </th>
            </tr>
        </thead>
        <tbody>
            <?php
            $wpost_attributes = include(dirname(__FILE__) . '/../data/data-hf-reserved-fields-pair.php');

            foreach ($wpost_attributes as $key => $value) :
                $sel_key = ($saved_mapping && isset($saved_mapping[$key])) ? $saved_mapping[$key] : $key;
                $evaluation_value = ($saved_evaluation && isset($saved_evaluation[$key])) ? $saved_evaluation[$key] : '';
                $evaluation_value = stripslashes($evaluation_value);
                $values = explode('|', $value);
                $value = $values[0];
                $tool_tip = isset($values[1]) ? $value[1] : '';
            ?>
                <tr>
                    <td width="25%">
                        <?php if (function_exists('WC')) { ?>
                            <img class="help_tip" style="float:none;" data-tip="<?php echo esc_attr($tool_tip); ?>" src="<?php echo esc_url($plugin_url . '/assets/images/help.png'); ?> " width="20" height="20" alt="Help icon" />
                        <?php } else { ?>
                            <img
                                class="help_tip"
                                style="float:none;"
                                data-tip="<?php echo esc_attr($tool_tip); ?>"
                                src="<?php echo esc_url($plugin_url . '/images/help.png'); ?>"
                                width="20"
                                height="20"
                                alt="" />
                        <?php } ?>
                        <select name="map_to[<?php echo esc_attr($key); ?>]" disabled="true"
                            style=" -webkit-appearance: none;
                                        -moz-appearance: none;
                                        text-indent: 1px;
                                        text-overflow: '';
                                        background-color: #f1f1f1;
                                        border: none;
                                        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.07) inset;
                                        color: #32373c;
                                        outline: 0 none;
                                        transition: border-color 50ms ease-in-out 0s;">
                            <option value="<?php echo esc_attr($key); ?>" <?php if ($key == $key) echo 'selected="selected"'; ?>><?php echo esc_html($value); ?></option>
                        </select>
                    </td>
                    <td width="25%">
                        <select name="map_from[<?php echo esc_attr($key); ?>]">
                            <option value=""><?php esc_html_e('Do not import', 'comments-import-export-woocommerce'); ?></option>
                            <?php
                            foreach ($row as $hkey => $hdr):
                                $hdr = strlen(esc_attr($hdr)) > 50 ? substr(esc_attr($hdr), 0, 50) . "..." : esc_attr($hdr);
                            ?>
                           
                           <option
  value="<?php echo esc_attr( $raw_headers[ $hkey ] ); ?>"
  <?php selected( strtolower( $sel_key ), $hkey ); ?>
>
  <?php 
    // Escape each piece as HTML text
    echo esc_html( $raw_headers[ $hkey ] );
    echo ' : '; // you can still output literal non-breaking spaces if you really need them
    echo esc_html( $hdr );
  ?>
</option>

                           <?php endforeach; ?>
                        </select>
                        <?php do_action('product_comments_csv_product_data_mapping', $key); ?>
                    </td>
                    <td width="10%"><input type="text" name="eval_field[<?php echo esc_html($key); ?>]" value="<?php echo  esc_html($evaluation_value); ?>" /></td>
                </tr>
            <?php endforeach;
            ?>
        </tbody>
    </table>
    <p class="submit">
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Submit', 'comments-import-export-woocommerce'); ?>" />
        <input type="hidden" name="delimiter" value="<?php echo esc_attr($this->delimiter) ?>" />
        <input type="hidden" name="clean_before_import" value="<?php echo esc_attr($this->clean_before_import) ?>" />
    </p>
</form>
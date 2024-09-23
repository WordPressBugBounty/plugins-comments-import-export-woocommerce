<?php

if (!defined('ABSPATH')) {
    exit;
}

class HW_Cmt_ImpExpCsv_Exporter {

    /**
     * WordPress Comments Exporter Tool
     */
    public static function do_export($cmt_ids = array()) {
      
        global $wpdb;
       
        if(isset($_GET['_wpnonce'])){
            $_nonce = sanitize_text_field($_GET['_wpnonce']);
        }elseif(isset($_POST['_wpnonce'])){
            $_nonce = sanitize_text_field($_POST['_wpnonce']);
        }else{
            $_nonce = '';
        }
        
        $do_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
        
        if( 'download_to_cmtiew_csv_hf' === $do_action ){
            if(!check_admin_referer( 'bulk-comments' )){
                wp_die(__('You do not have sufficient permissions to access this page.', 'hw_csv_import_export'));
            }
        }else{          
            if(!wp_verify_nonce($_nonce,'comments-import-export-woocommerce')){
                wp_die(__('You do not have sufficient permissions to access this page.', 'hw_csv_import_export'));
            } 
        }  
        if (!function_exists('get_current_screen')) {
            require_once(ABSPATH . 'wp-admin/includes/screen.php');
        }
        if (!empty($cmt_ids)) {
            $selected_cmt_ids = implode(', ', $cmt_ids);
        } else {
            $selected_cmt_ids = '';
        }

        $export_limit = !empty($_POST['limit']) ? intval($_POST['limit']) : 999999999;
        $limit = 100;
        $delimiter = !empty($_POST['delimiter']) ? $_POST['delimiter'] : ','; // WPCS: CSRF ok, input var ok.
        $articles = !empty($_POST['articles']) ? Wt_WWCIEP_Security_Helper::sanitize_item($_POST['articles'],'int_arr') : '';
        $products = !empty($_POST['products']) ? Wt_WWCIEP_Security_Helper::sanitize_item($_POST['products'],'int_arr') : '';
        if ($limit > $export_limit)
            $limit = $export_limit;

        if (isset($_POST['woo_enable'])&&$_POST['woo_enable'] != 0) {
            $woo_set = '1';
            $product_enable = 'product';
            $cmd_type = 'woodiscuz';
        } else {
            $product_enable = 'Post';
            $woo_set = '0';
            $cmd_type = null;
        }
        $cmt_date_from = !empty($_POST['cmt_date_from']) ? $_POST['cmt_date_from'] : date('Y-m-d 00:00', 0);
        $cmt_date_to = !empty($_POST['cmt_date_to']) ? $_POST['cmt_date_to'] : date('Y-m-d 23:59', current_time('timestamp'));
        $csv_columns = include( 'data/data-hf-post-columns.php' );
        $user_columns_name = !empty($_POST['columns_name']) ? Wt_WWCIEP_Security_Helper::sanitize_item($_POST['columns_name'],'text_arr') : $csv_columns;
        $export_columns = !empty($_POST['columns']) ? Wt_WWCIEP_Security_Helper::sanitize_item($_POST['columns'],'text_arr') : '';
        if ($limit > $export_limit)
            $limit = $export_limit;
        $settings = get_option('woocommerce_' . HW_CMT_IMP_EXP_ID . '_settings', null);
        $ftp_server = isset($settings['ftp_server']) ? $settings['ftp_server'] : '';
        $ftp_user = isset($settings['ftp_user']) ? $settings['ftp_user'] : '';
        $ftp_password = isset($settings['ftp_password']) ? $settings['ftp_password'] : '';
        $use_ftps = isset($settings['use_ftps']) ? $settings['use_ftps'] : '';
        $ftp_port = isset($settings['ftp_port']) ? $settings['ftp_port'] : '';
        $enable_ftp_ie = isset($settings['enable_ftp_ie']) ? $settings['enable_ftp_ie'] : '';
        $use_pasv = isset($settings['use_pasv']) ? $settings['use_pasv'] : '';
        
        $wpdb->hide_errors();
        @set_time_limit(0);
        if (function_exists('apache_setenv'))
            @apache_setenv('no-gzip', 1);
        @ini_set('zlib.output_compression', 0);
        @ob_end_clean();
        if ($enable_ftp_ie) {
            $upload_path = wp_upload_dir();
            $file_path = $upload_path['path'] . '/';
            $file = (!empty($settings['export_ftp_file_name']) ) ? $file_path . $settings['export_ftp_file_name'] : "WP_comments-export-" . date('Y_m_d_H_i_s', current_time('timestamp')) . ".csv";;
//            $file = "WP_comments-export-" . date('Y_m_d_H_i_s', current_time('timestamp')) . ".csv";
            $fp = fopen($file, 'w');
        } else {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename=WP-comments-export-' . date('Y_m_d_H_i_s', current_time('timestamp')) . '.csv');
            header('Pragma: no-cache');
            header('Expires: 0');
            $fp = fopen('php://output', 'w');
        }

        // Headers
//        $all_meta_keys = array('');
         global $wpdb;

        $all_meta_keys = $wpdb->get_col("SELECT DISTINCT pm.meta_key FROM {$wpdb->commentmeta} AS pm LEFT JOIN {$wpdb->comments} AS p ON p.comment_ID = pm.comment_id WHERE p.comment_approved IN ( '0','1') ");
        $found_coupon_meta = array();
        // Some of the values may not be usable (e.g. arrays of arrays) but the worse
        // that can happen is we get an empty column.
        foreach ($all_meta_keys as $meta) {
            if (!$meta)
                continue;
            if (!in_array($meta, array_keys($csv_columns)) && substr((string) $meta, 0, 1) == '_')
                continue;
            if (in_array($meta, array_keys($csv_columns)))
                continue;
            $found_coupon_meta[] = $meta;
        }

        $found_coupon_meta = array_diff($found_coupon_meta, array_keys($csv_columns));

        // Variable to hold the CSV data we're exporting
        $row = array();


        // Export header rows
        foreach ($csv_columns as $column => $value) {
            
              if ('comment_meta' == $column && isset($export_columns[$column])) {//exprt commentmeta header 
                    foreach ($all_meta_keys as $comment_meta) {
                        $row[] = 'meta:' . self::format_data($comment_meta);
                    }
                    continue;
                }

            $temp_head = esc_attr($user_columns_name[$column]);
            if (strpos($temp_head, 'yoast') === false) {
                $temp_head = ltrim($temp_head, '_');
            }
            if (!$export_columns || in_array($column, $export_columns))
                $row[] = $temp_head;
        }

        if (!$export_columns || in_array('meta', $export_columns)) {
            foreach ($found_coupon_meta as $product_meta) {
                $row[] = 'meta:' . self::format_data($product_meta);
            }
        }
        $row = apply_filters('wt_comments_csv_export_columns', $row);
        $row = array_map('HW_Cmt_ImpExpCsv_Exporter::wrap_column', $row);
        fwrite($fp, implode($delimiter, $row) . "\n");
        unset($row);
        $args = array(
            'status' => 'all',
            'post_type' => $product_enable,
            'orderby' => 'comment_ID',
            'order' => 'ASC',
            'type' => $cmd_type,
            'number' => $export_limit,
            'suppress_filters' => false,
            'date_query' => array(
                array(
                    'before' => $cmt_date_to,
                    'after' => $cmt_date_from,
                    'inclusive' => true,
                ),),
        );

        if (!empty($selected_cmt_ids)) {
            $args['comment__in'] = $selected_cmt_ids;
        }

        if ($woo_set != '0') {
            if (!empty($products)) {
                for ($i = 0; $i < count($products); $i++) {
                    $args = array(
                        'post__in' => implode(',', $products),
                        'orderby' => 'comment_ID',
                        'order' => 'ASC',
                        'post_type' => $product_enable,
                        'type' => $cmd_type,
                        'number' => $export_limit,
                        'suppress_filters' => false,
                        'date_query' => array(
                            array(
                                'before' => $cmt_date_to,
                                'after' => $cmt_date_from,
                                'inclusive' => true,
                            ),),
                    );
                }
            }
        } else {
            if (!empty($articles)) {
                for ($i = 0; $i < count($articles); $i++) {
                   $args = array(
                        'post__in' => implode(',', $articles),
                        'orderby' => 'comment_ID',
                        'order' => 'ASC',
                        'post_type' => $product_enable,
                        'type' => $cmd_type,
                        'number' => $export_limit,
                        'suppress_filters' => false,
                        'date_query' => array(
                            array(
                                'before' => $cmt_date_to,
                                'after' => $cmt_date_from,
                                'inclusive' => true,
                            ),),
                    );
                  
                   // $args['post__in'] = implode(',', $articles);
                }
            }
        }
        
        $args = apply_filters('product_Comments_csv_product_export_args',$args);

        global $wpdb;

        $comments_query = new WP_Comment_Query;
        $comments = $comments_query->query($args);
        foreach ($comments as $comment) {
            $row = array();

            $comment_ID = $comment->comment_ID;
            $obj = new HW_Cmt_ImpExpCsv_Exporter();
            $meta_data = $obj->get_meta_status($comment_ID, $woo_set);

            if ($meta_data) {
                $comment->meta = new stdClass;
                $comment->meta->verified = get_comment_meta($comment_ID, 'rating', true);

                if ($woo_set != '0') {
                    // Meta data
                    foreach ($meta_data as $meta => $value) {
                        if (!$meta) {
                            continue;
                        }
                        if (!in_array($meta, array_keys($csv_columns)) && substr($meta, 0, 1) == '_') {
                            continue;
                        }


                        $meta_value = maybe_unserialize(maybe_unserialize($value));

                        if (is_array($meta_value)) {
                            $meta_value = json_encode($meta_value);
                        }

                        $comment->meta->$meta = self::format_export_meta($meta_value, $meta);
                    }
                }
                foreach ($csv_columns as $column => $value) {
                     

                    if (!$export_columns || in_array($column, $export_columns)) {
                        if ($column === 'comment_alter_id') {
                            $row[] = self::format_data($comment_ID);
                            continue;
                        }
                         if ($column === 'comment_post_title') {
                             $post_title = '';
                             if(isset($comment->comment_post_ID) && $comment->comment_post_ID){
                                $post_title = get_the_title($comment->comment_post_ID)?get_the_title($comment->comment_post_ID):'';
                             }
                            $row[] = self::format_data($post_title);
                            continue;
                        }
                        if ($column === 'comment_post_name') {
                             $post_name = '';
                             if(isset($comment->comment_post_ID) && $comment->comment_post_ID){
                                $post_name =  get_post_field( 'post_name', $comment->comment_post_ID )? get_post_field( 'post_name', $comment->comment_post_ID ):'';
                             }
                            $row[] = self::format_data($post_name);
                            continue;
                        }
                                         
                        // Export commentmeta data
                        if ( 'comment_meta' == $column ) {
                            foreach ($all_meta_keys as $commt_meta) {  
                                 $comment_meta_value = get_comment_meta($comment_ID, $commt_meta, true);
                                if (isset($comment_meta_value)) {
                                    if (is_array($comment_meta_value)) {
                                        $comment_meta_value = json_encode($comment_meta_value);
                                    }
                                    $row[] = self::format_data($comment_meta_value);
                                }
                                else {
                                    $row[] = '';
                                }
                            }
                            continue;
                        }

                        if (isset($comment->meta->$column)) {
                            $row[] = self::format_data($comment->meta->$column);
                        } elseif (isset($comment->$column) && !is_array($comments[0]->$column)) {
                            if ($column === 'post_title') {
                                $row[] = sanitize_text_field($comment->$column);
                            } elseif('comment_content'===$column){
                                $row[] = self::format_data(' '.$comment->$column);// for avoid vulnerable to Remote Command Execution
                            }else{
                                $row[] = self::format_data($comment->$column);
                            }
                        } else {
                            $row[] = '';
                        }
                    }
                }

                if (!$export_columns || in_array('meta', $export_columns)) {
                    foreach ($found_coupon_meta as $product_meta) {
                        if (isset($comment->meta->$product_meta)) {
                            $row[] = self::format_data($comment->meta->$product_meta);
                        } else {
                            $row[] = '';
                        }
                    }
                }
                $row = apply_filters('wt_comments_export_csv_data', $row);
                $row = array_map('HW_Cmt_ImpExpCsv_Exporter::wrap_column', $row);
                fwrite($fp, implode($delimiter, $row) . "\n");
                unset($row);
            }
        }
        if ($enable_ftp_ie) {
            
            
            $remote_path = isset($settings['export_ftp_path']) ? $settings['export_ftp_path'] : null;
            $file_name = isset($settings['export_ftp_file_name']) ? $settings['export_ftp_file_name'] : null;
             // Upload ftp path with filename
           $remote_file = ( substr($remote_path, -1) != '/' ) ? ( $remote_path . "/" . basename($file) ) : ( $remote_path . basename($file) );

            //if have SFTP Add-on for Import Export for WooCommerce 
        if (class_exists('class_wf_sftp_import_export')) {
            $sftp_export = new class_wf_sftp_import_export();
            if (!$sftp_export->connect($ftp_server, $ftp_user, $ftp_password, $ftp_port)) {
                $wf_product_ie_msg = 2;
                wp_redirect(admin_url('/admin.php?page=hw_cmt_csv_im_ex&hw_product_comment_ie_msg=' . $wf_product_ie_msg));
            }
            if ($sftp_export->put_contents($remote_file, file_get_contents($file))) {
                $wf_product_ie_msg = 1;
            } else {
                $wf_product_ie_msg = 2;
            }
            wp_redirect(admin_url('/admin.php?page=hw_cmt_csv_im_ex&hw_product_comment_ie_msg=' . $wf_product_ie_msg));
            
        }
            
            
            if ($use_ftps) {
                $ftp_conn = @ftp_ssl_connect($ftp_server) or die(__("Could not connect to $ftp_server",'comments-import-export-woocommerce'));
            } else {
                $ftp_conn = @ftp_connect($ftp_server) or die(__("Could not connect to $ftp_server",'comments-import-export-woocommerce'));
            }
            $login = @ftp_login($ftp_conn, $ftp_user, $ftp_password);
            if($use_pasv){
                ftp_pasv($ftp_conn, TRUE);
            }
              

            // Upload ftp path with filename
//            $remote_file = ( substr($remote_path, -1) != '/' ) ? ( $remote_path . "/" . $file_name ) : ( $remote_path . $file_name );
            // upload file
            if (@ftp_put($ftp_conn, $remote_file, $file, FTP_ASCII)) {
                $hw_product_comment_ie_msg = 1;
                wp_redirect(admin_url('/admin.php?page=hw_cmt_csv_im_ex&hw_product_comment_ie_msg=' . $hw_product_comment_ie_msg));
            } else {
                $hw_product_comment_ie_msg = 2;
                wp_redirect(admin_url('/admin.php?page=hw_cmt_csv_im_ex&hw_product_comment_ie_msg=' . $hw_product_comment_ie_msg));
            }

            // close connection
            ftp_close($ftp_conn);
        }

        fclose($fp);
        exit;
    }

    /**
     * Format the data if required
     * @param  string $meta_value
     * @param  string $meta name of meta key
     * @return string
     */
    public static function format_export_meta($meta_value, $meta) {
        switch ($meta) {
            case '_sale_price_dates_from' :
            case '_sale_price_dates_to' :
                return $meta_value ? date('Y-m-d', $meta_value) : '';
                break;
            case '_upsell_ids' :
            case '_crosssell_ids' :
                return implode('|', array_filter((array) json_decode($meta_value)));
                break;
            default :
                return $meta_value;
                break;
        }
    }

    public static function format_data($data) {
        if (!is_array($data))
            ;
        $data = (string) urldecode($data);
        $enc = mb_detect_encoding($data, 'UTF-8, ISO-8859-1', true);
        $data = ( $enc == 'UTF-8' ) ? $data : utf8_encode($data);
		return self::escape_data( $data );
    }

	/**
	 * Escape a string to be used in a CSV context
	 *
	 * Malicious input can inject formulas into CSV files, opening up the possibility
	 * for phishing attacks and disclosure of sensitive information.
	 *
	 * Additionally, Excel exposes the ability to launch arbitrary commands through
	 * the DDE protocol.
	 *
	 * @see http://www.contextis.com/resources/blog/comma-separated-vulnerabilities/
	 * @see https://patchstack.com/database/vulnerability/comments-import-export-woocommerce/wordpress-comments-import-export-plugin-2-0-4-csv-injection-vulnerability
	 *
	 * @param string $data CSV field to escape.
	 * @return string
	 */
	public static function escape_data( $data )
	{
		$active_content_triggers = array( '=', '+', '-', '@' );

		if ( in_array( mb_substr( $data, 0, 1 ), $active_content_triggers, true ) ) {
			$data = "'" . $data;
		}

		return $data;
	}	
	
	
	
	
    /**
     * Wrap a column in quotes for the CSV
     * @param  string data to wrap
     * @return string wrapped data
     */
    public static function wrap_column($data) {
        return '"' . str_replace('"', '""', $data) . '"';
    }

    public static function get_meta_status($id, $woo_set) {
        if ($woo_set != '0') {
            // global $wpdb;
            //  $query = "SELECT comment_type FROM $wpdb->comments WHERE comment_ID=".$id;
            // $result = $wpdb->get_var($query);
            // if($result === 'woodiscuz')
            // {
            $new_comment_type = get_comment_type($comment_id = $id);
            if ($new_comment_type == 'woodiscuz') {
               return true;
            }
            //  }
            return false;
        } else {
            if (count(get_comment_meta($id)) != 0) {
                return true;
            } else {
                return true;
            }
        }
    }

}

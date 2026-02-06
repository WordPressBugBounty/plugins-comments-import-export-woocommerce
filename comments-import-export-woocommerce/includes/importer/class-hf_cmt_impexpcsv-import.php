<?php



/**
 * WordPress Importer class for managing the import process of a CSV file
 *
 * @package WordPress
 * @subpackage Importer
 */
if (!class_exists('WP_Importer'))
    return;

$posti_id = '';


class HW_Cmt_ImpExpCsv_Import extends WP_Importer
{

    var $id;
    var $file_url;
    var $delimiter;
    var $profile;
    var $merge_empty_cells;

    var $processed_posts = array();
    var $post_orphans = array();

    var $parent_data = '';
    // Results
    var $import_results = array();
    var $new_id = array();
    var $clean_before_import = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (function_exists('WC')) {
            if ( version_compare( WC()->version, '2.7.0', '<' ) ) {
                $this->log = new WC_Logger();
            } else {
                $this->log = wc_get_logger();
            }
        }
        $this->import_page = 'product_comments_csv';
        $this->file_url_import_enabled = apply_filters('product_comments_csv_product_file_url_import_enabled', true);
    }

    public function hf_log_data_change($content = 'csv-import', $data = '')
    {
        if ( version_compare( WC()->version, '2.7.0', '<' ) ) {
            $this->log->add($content, $data);
        } else {
            $context = array('source' => $content);
            $this->log->log("debug", $data, $context);
        }
    }

    public function hf_cmt_im_ex_StartSession()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public function hf_cmt_im_ex_myEndSession()
    {
        session_destroy();
    }
    /**
     * Registered callback function for the WordPress Importer
     *
     * Manages the three separate stages of the CSV import process
     */
    public function dispatch()
    {
        global $wpdb;

        if (function_exists('WC')) {
            global $woocommerce;
        }

        // Nonce validations.
        $step = isset( $_GET['step'] ) ? absint( wp_unslash( $_GET['step'] ) ) : 0;
        switch ($step) {
            case 1:
                check_admin_referer('import-upload');
                break;

            case 2:
                check_admin_referer('import-options');
                break;

            case 3:
            case 4:
                // Strict nonce and permission check
                check_admin_referer( HW_CMT_IMP_EXP_ID, 'wt_nonce' );
                if ( ! HW_Product_Comments_Import_Export_CSV::hf_user_permission() ) {
                    wp_die(
                        esc_html__( 'Access Denied', 'comments-import-export-woocommerce' ),
                        esc_html__( 'Error', 'comments-import-export-woocommerce' ),
                        array( 'response' => 403 )
                    );
                }
                break;
        }


        add_action('init', array($this, 'hf_cmt_im_ex_StartSession'), 1);

        // Delimiter (default: comma)
        if ( isset( $_POST['delimiter'] ) ) {
            $delimiter = sanitize_text_field( wp_unslash( $_POST['delimiter'] ) );
        } elseif ( isset( $_GET['delimiter'] ) ) {
            $delimiter = sanitize_text_field( wp_unslash( $_GET['delimiter'] ) );
        } else {
            $delimiter = ',';
        }

        // Ensure it's a single character
        $this->delimiter = substr( $delimiter, 0, 1 );

        // Profile (default: empty string)
        if ( isset( $_POST['profile'] ) ) {
            $this->profile = sanitize_text_field( wp_unslash( $_POST['profile'] ) );
        } elseif ( isset( $_GET['profile'] ) ) {
            $this->profile = sanitize_text_field( wp_unslash( $_GET['profile'] ) );
        } else {
            $this->profile = '';
        }

        if (!$this->delimiter)
            $this->delimiter = ',';

        if (!empty($_POST['clean_before_import']) || !empty($_GET['clean_before_import'])) {
            $this->clean_before_import = 1;
        } else {
            $this->clean_before_import = 0;
        }

        $step = empty($_GET['step']) ? 0 : absint($_GET['step']);
        switch ($step) {
            case 0:
                $this->header();
                $this->greet();
                break;
            case 1:
                $this->header();

                if (!empty($_GET['file_url']))
                    $this->file_url = isset( $_GET['file_url'] ) ? sanitize_text_field( wp_unslash( $_GET['file_url'] ) ) : '';

                if (!empty($_GET['file_id']))
                    $this->id = isset( $_GET['file_id'] ) ? absint( wp_unslash( $_GET['file_id'] ) ) : 0;

                if ( ! empty( $_GET['clearmapping'] ) || $this->handle_upload() ) {
                    $this->import_options();
                }
                exit;
                break;
            case 2:
                $this->header();

                $this->id = isset( $_POST['import_id'] ) ? absint( wp_unslash( $_POST['import_id'] ) ) : 0;

                if ($this->file_url_import_enabled)
                    $this->file_url = isset( $_POST['import_url'] ) ? sanitize_text_field( wp_unslash( $_POST['import_url'] ) ) : '';
                if ($this->id){
                    $file = get_attached_file( $this->id );
                } else if ( $this->file_url_import_enabled ) {
                    // Build absolute path.
                    $target = ABSPATH . ltrim( $this->file_url, '/\\' );
                    
                    // Resolve symlinks and normalize path.
                    $real   = realpath( $target );

                    if ( false === $real ) {
                        wp_die( esc_html__( 'Invalid file path.', 'comments-import-export-woocommerce' ) );
                    }

                    // Ensure file is inside ABSPATH (prevents ../../ traversal)
                    if ( strpos( $real, realpath( ABSPATH ) ) !== 0 ) {
                        wp_die( esc_html__( 'Access denied.', 'comments-import-export-woocommerce' ), '', array( 'response' => 403 ) );
                    }

                    $file = $real;
                }

                $file = str_replace("\\", "/", $file);

                if ($file) {
                    ?>
                    <table id="import-progress" class="widefat_importer widefat">
                        <thead>
                            <tr>
                                <th class="status">&nbsp;</th>
                                <th class="row"><?php esc_html_e('Row', 'comments-import-export-woocommerce'); ?></th>
                                <th><?php esc_html_e('ID', 'comments-import-export-woocommerce'); ?></th>
                                <th><?php esc_html_e('Comment Link', 'comments-import-export-woocommerce'); ?></th>
                                <th class="reason"><?php esc_html_e('Status Msg', 'comments-import-export-woocommerce'); ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="importer-loading">
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                        <tbody></tbody>
                    </table>
                    <script type="text/javascript">
                        jQuery(document).ready(function($) {

                            if (!window.console) {
                                window.console = function() {};
                            }

                            //                        var processed_terms = [];
                            var processed_posts = [];
                            var post_orphans = [];
                            //                                                var attachments = [];
                            //                                        var upsell_skus = [];
                            //                        var crosssell_skus = [];
                            var i = 1;
                            var done_count = 0;

                            function import_rows(start_pos, end_pos) {
                                var data = {
                                    action: 'product_comments_csv_import_request',
                                    file: '<?php echo esc_js($file); ?>',
                                    mapping: '<?php echo wp_json_encode( ( ! empty($_POST['map_from']) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['map_from'] ) ) : array() ) ); ?>',
                                    profile: '<?php echo esc_js($this->profile); ?>',
                                    eval_field: '<?php echo wp_json_encode( ( ! empty($_POST['eval_field']) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['eval_field'] ) ) : array() ), JSON_HEX_APOS); ?>',
                                    delimiter: '<?php echo esc_js($this->delimiter); ?>',
                                    clean_before_import: '<?php echo esc_js($this->clean_before_import); ?>',
                                    start_pos: start_pos,
                                    end_pos: end_pos,
                                    wt_nonce: '<?php echo esc_js(wp_create_nonce(HW_CMT_IMP_EXP_ID)) ?>'
                                };

                                let decoded = data.eval_field.replace(/&quot;/g, '"');


                                data.eval_field = $.parseJSON(decoded);

                                <?php

                                $url = add_query_arg(
                                    [
                                        'import_page' => sanitize_key($this->import_page),
                                        'step'        => 3,
                                        'merge'       => ! empty($_GET['merge']) ? 1 : 0,
                                    ],
                                    admin_url('admin-ajax.php')
                                );

                                ?>

                                return $.ajax({
                                    url: '<?php echo esc_url_raw($url); ?>',
                                    data: data,
                                    type: 'POST',
                                    success: function(response) {
                                        if (response) {

                                            try {
                                                // Get the valid JSON only from the returned string
                                                if (response.indexOf("<!--WC_START-->") >= 0)
                                                    response = response.split("<!--WC_START-->")[1]; // Strip off before after WC_START 
                                                if (response.indexOf("<!--WC_END-->") >= 0)
                                                    response = response.split("<!--WC_END-->")[0]; // Strip off anything after WC_END

                                                // Parse
                                                var results = $.parseJSON(response);
                                                if (results.error) {

                                                    $('#import-progress tbody').append('<tr id="row-' + i + '" class="error"><td class="status" colspan="5">' + results.error + '</td></tr>');
                                                    i++;
                                                } else if (results.import_results && $(results.import_results).size() > 0) {

                                                    //                                    $.each(results.processed_terms, function(index, value) {
                                                    //                                    processed_terms.push(value);
                                                    //                                    });
                                                    $.each(results.processed_posts, function(index, value) {
                                                        processed_posts.push(value);
                                                    });
                                                    $.each(results.post_orphans, function(index, value) {
                                                        post_orphans.push(value);
                                                    });
                                                    //                                    $.each(results.attachments, function(index, value) {
                                                    //                                    attachments.push(value);
                                                    //                                    });
                                                    //                                    upsell_skus = jQuery.extend({}, upsell_skus, results.upsell_skus);
                                                    //                                    crosssell_skus = jQuery.extend({}, crosssell_skus, results.crosssell_skus);
                                                    $(results.import_results).each(function(index, row) {

                                                        $('#import-progress tbody').append('<tr id="row-' + i + '" class="' + row['status'] + '"><td><mark class="result" title="' + row['status'] + '">' + row['post_id'] + '</mark></td><td class="row">' + i + '</td><td>' + row['post_id'] + '</td><td> <a href="' + row['comment_link'] + '" target="_blank">Comment :' + row['post_id'] + '</a>  </td><td class="reason">' + row['reason'] + '</td></tr>');
                                                        i++;
                                                    });
                                                }

                                            } catch (err) {}

                                        } else {
                                            $('#import-progress tbody').append('<tr class="error"><td class="status" colspan="5">' + '<?php esc_html_e('AJAX Error', 'comments-import-export-woocommerce'); ?>' + '</td></tr>');
                                        }

                                        var w = $(window);
                                        var row = $("#row-" + (i - 1));
                                        if (row.length) {
                                            w.scrollTop(row.offset().top - (w.height() / 2));
                                        }

                                        done_count++;
                                        $('body').trigger('product_comments_csv_import_request_complete');
                                    }
                                });
                            }

                            var rows = [];
                            <?php
                            $limit = apply_filters('product_comments_csv_import_limit_per_request', 10);
                            $enc = mb_detect_encoding($file, 'UTF-8, ISO-8859-1', true);
                            if ($enc)
                                setlocale(LC_ALL, 'en_US.' . $enc);
                            // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
                            @ini_set('auto_detect_line_endings', true); // @codingStandardsIgnoreLine.

                            $count = 0;
                            $previous_position = 0;
                            $position = 0;
                            $import_count = 0;

                            // Get CSV positions
                            if ( file_exists( $file ) && is_readable( $file ) ) { 
                                
                                // PHPCS ignore reason: Direct read is intentional for CSV parsing.
                                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
                                $handle = @fopen( $file, 'r' );  // @codingStandardsIgnoreLine.

                                if ( false !== $handle ) {
                                    
                                    while (($postmeta = fgetcsv($handle, 0, $this->delimiter, '"', '"')) !== FALSE) {
                                        $count++;

                                        if ($count >= $limit) {
                                            $previous_position = $position;
                                            $position = ftell($handle);
                                            $count = 0;
                                            $import_count++;

                                            // Import rows between $previous_position $position
                                            ?>rows.push([<?php echo esc_js($previous_position); ?>, <?php echo esc_js($position); ?>]);
                                            <?php
                                        }
                                    }

                                    // Remainder
                                    if ($count > 0) {
                                        ?>
                                        rows.push([<?php echo esc_js($position); ?>, '']);
                                        <?php
                                        $import_count++;
                                    }

                                    // PHPCS ignore reason: Direct read is intentional for CSV parsing.
                                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
                                    @fclose( $handle );  // @codingStandardsIgnoreLine.
                                }
                            }
                        ?>

                        var data = rows.shift();
                        var regen_count = 0;
                        import_rows(data[0], data[1]);
                        $('body').on('product_comments_csv_import_request_complete', function() {
                            if (done_count == <?php echo esc_js($import_count); ?>) {

                                import_done();

                            } else {
                                // Call next request
                                data = rows.shift();
                                import_rows(data[0], data[1]);
                            }
                        });


                        function import_done() {
                            var data = {
                                action: 'product_comments_csv_import_request',
                                file: '<?php echo esc_js($file); ?>',
                                //                                processed_terms: processed_terms,
                                processed_posts: processed_posts,
                                post_orphans: post_orphans,
                                //                                upsell_skus: upsell_skus,
                                //                                crosssell_skus: crosssell_skus
                                wt_nonce: '<?php echo esc_js(wp_create_nonce(HW_CMT_IMP_EXP_ID)) ?>'
                            };

                            <?php 
                            
                            $raw_final_url = add_query_arg(
                                [
                                    'import_page' => sanitize_key( $this->import_page ),
                                    'step'        => 4,
                                    'merge'       => ! empty( $_GET['merge'] ) ? 1 : 0,
                                ],
                                admin_url( 'admin-ajax.php' )
                            );
                            ?>

                            $.ajax({
                                url: '<?php echo esc_url_raw( $raw_final_url ); ?>',
                                data: data,
                                type: 'POST',
                                success: function(response) {
                                    $('#import-progress tbody').append('<tr class="complete"><td colspan="5">' + response + '</td></tr>');
                                    $('.importer-loading').hide();
                                }
                            });
                        }
                        });
                    </script>
                    <?php
                } else {
                    echo '<p class="error">' . esc_html__('Error finding uploaded file!', 'comments-import-export-woocommerce') . '</p>';
                }
                break;
            case 3:

                // Sanitize and validate file path
                $file = ! empty( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
                if (filter_var($file, FILTER_VALIDATE_URL) || !self::is_valid_file_path($file)) {
                    wp_die(esc_html__('Invalid file path', 'comments-import-export-woocommerce'));
                }

                // Sanitize mapping and other inputs
                $raw_mapping = ! empty($_POST['mapping']) ? sanitize_text_field(wp_unslash($_POST['mapping'])) : '';
                $mapping = json_decode($raw_mapping, true);
                $mapping = is_array($mapping) ? array_map('sanitize_text_field', $mapping) : array();
                $profile = isset( $_POST['profile'] ) ? sanitize_text_field( wp_unslash( $_POST['profile'] ) ) : '';
                $eval_field = ! empty( $_POST['eval_field'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['eval_field'] ) ) : array();
                $start_pos = isset( $_POST['start_pos'] ) ? absint( wp_unslash( $_POST['start_pos'] ) ) : 0;
                $end_pos = isset( $_POST['end_pos'] ) ? absint( wp_unslash( $_POST['end_pos'] ) ) : '';

                add_filter('http_request_timeout', array($this, 'bump_request_timeout'));

                if (function_exists('gc_enable'))
                    gc_enable();

                // @codingStandardsIgnoreStart
                @set_time_limit(0);
                @ob_flush(); //phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                @flush(); //phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                // @codingStandardsIgnoreEnd
                $wpdb->hide_errors();

                $position = $this->import_start($file, $mapping, $start_pos, $end_pos, $eval_field);
                $this->import();
                $this->import_end();

                $results = array();
                $results['import_results'] = $this->import_results;
                //                $results['processed_terms'] = $this->processed_terms;
                $results['processed_posts'] = $this->processed_posts;
                $results['post_orphans'] = $this->post_orphans;
                //                $results['attachments'] = $this->attachments;
                //                $results['upsell_skus'] = $this->upsell_skus;
                //                $results['crosssell_skus'] = $this->crosssell_skus;
                // die($results);
                echo "<!--WC_START-->";
                echo wp_json_encode($results);
                echo "<!--WC_END-->";
                exit;
                break;
            case 4:
                // Sanitize processed posts and post orphans
                $this->processed_posts = isset( $_POST['processed_posts'] ) ?
                    array_map( 'absint', wp_unslash( (array) $_POST['processed_posts'] ) ) :
                    array();
                $this->post_orphans = isset( $_POST['post_orphans'] ) ?
                    array_map( 'absint', wp_unslash( (array) $_POST['post_orphans'] ) ) :
                    array();

                // Sanitize file path
                $file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';

                echo esc_html__('Step 1...', 'comments-import-export-woocommerce') . ' ';

                //                wp_defer_term_counting(true);
                wp_defer_comment_counting(true);

                echo esc_html__('Step 2...', 'comments-import-export-woocommerce') . ' ';

                echo esc_html__('Step 3...', 'comments-import-export-woocommerce') . ' '; // Easter egg
                // reset transients for products
                //                if(function_exists('WC'))
                //                {
                //                if (function_exists('wc_delete_product_transients')) {
                //                    wc_delete_product_transients();
                //                } else {
                //                    $woocommerce->clear_product_transients();
                //                }
                //                }
                //                delete_transient('wc_attribute_taxonomies');
                //
                //                $wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_product_type_%')");

                echo esc_html__('Finalizing...', 'comments-import-export-woocommerce') . ' ';

                // SUCCESS
                echo esc_html__('Finished. Import complete.', 'comments-import-export-woocommerce');

                if (in_array(pathinfo($file, PATHINFO_EXTENSION), array('txt', 'csv'))) {
                    // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_unlink
                    @unlink( $file );  // @codingStandardsIgnoreLine.
                }
                $this->import_end();
                delete_option('wt_post_comment_alter_id');
                exit;
                break;
        }

        $this->footer();
    }

    /**
     * format_data_from_csv
     */
    public function format_data_from_csv($data, $enc)
    {
        return ($enc == 'UTF-8') ? $data : utf8_encode($data);
    }

    /**
     * Display pre-import options
     */
    public function import_options()
    {
        $j = 0;

        if ($this->id)
            $file = get_attached_file($this->id);
        else if ($this->file_url_import_enabled)
            $file = ABSPATH . $this->file_url;
        else
            return;

        // Set locale
        $enc = mb_detect_encoding($file, 'UTF-8, ISO-8859-1', true);
        if ($enc){
            setlocale(LC_ALL, 'en_US.' . $enc);
        }
        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
        @ini_set('auto_detect_line_endings', true); // @codingStandardsIgnoreLine.
        delete_option('wt_post_comment_alter_id');
        // Get headers
        if ( file_exists( $file ) && is_readable( $file ) ) {
            // PHPCS ignore reason: Direct read is intentional for CSV parsing.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
            $handle = @fopen( $file, 'r' );  // @codingStandardsIgnoreLine.
            if ( false !== $handle ) {

                $row = $raw_headers = array();

                $header = fgetcsv($handle, 0, $this->delimiter, '"', '"');

                while (($postmeta = fgetcsv($handle, 0, $this->delimiter, '"', '"')) !== FALSE) {
                    foreach ($header as $key => $heading) {
                        if (!$heading)
                            continue;
                        $s_heading = strtolower($heading);
                        $row[$s_heading] = (isset($postmeta[$key])) ? $this->format_data_from_csv($postmeta[$key], $enc) : '';
                        $raw_headers[$s_heading] = $heading;
                    }
                    break;
                }
                // PHPCS ignore reason: Direct read is intentional for CSV parsing.
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
                @fclose( $handle );  // @codingStandardsIgnoreLine.
            }
        }

        $mapping_from_db = get_option('hw_prod_comment_csv_imp_exp_mapping');

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ($this->profile !== '' && !empty($_GET['clearmapping'])) { // @codingStandardsIgnoreLine.
            unset($mapping_from_db[$this->profile]);
            update_option('hw_prod_comment_csv_imp_exp_mapping', $mapping_from_db);
            $this->profile = '';
        }
        if ($this->profile !== '')
            $mapping_from_db = $mapping_from_db[$this->profile];

        $saved_mapping = null;
        $saved_evaluation = null;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $merge = (!empty($_GET['merge']) ? 1 : 0); // @codingStandardsIgnoreLine.

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ($mapping_from_db && is_array($mapping_from_db) && $this->profile !== '' && count($mapping_from_db) == 2 && empty($_GET['clearmapping'])) { // @codingStandardsIgnoreLine.
            	
            $reset_action = wp_nonce_url( 'admin.php?clearmapping=1&profile=' . $this->profile . '&import=' . $this->import_page . '&step=1&merge=' . $merge . '&file_url=' . $this->file_url . '&delimiter=' . $this->delimiter . '&merge_empty_cells=' . $this->merge_empty_cells . '&file_id=' . $this->id, 'import-upload' );
            printf(
				/* translators: 1: mapping file name, 2: reset link URL */
				esc_html__( 'Columns are pre-selected using the Mapping file: %1$s. %2$s this mapping file.', 'comments-import-export-woocommerce' ),
				'<b style="color:gray">' . esc_html( $this->profile ) . '</b>',
				'<a href="' . esc_url( $reset_action ) . '">' . esc_html__( 'Delete', 'comments-import-export-woocommerce' ) . '</a>'
			);
            $saved_mapping = $mapping_from_db[0];
            $saved_evaluation = $mapping_from_db[1];	
        }

        include('views/html-hf-import-options.php');
    }


    
    /**
     * The main controller for the actual import stage.
     */
    public function import()
    {
        global $wpdb;

        if (function_exists('WC')) {
            global $woocommerce;
        }


        if ($this->clean_before_import == 1) {

            $deletequery = "TRUNCATE TABLE {$wpdb->prefix}comments";
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
            if (! $wpdb->query( $deletequery ) ) { // @codingStandardsIgnoreLine.
                $this->add_import_result('failed', esc_html__('Didn`t able to clean the previous comments', 'comments-import-export-woocommerce'), esc_html__('Didn`t able to clean the previous comments', 'comments-import-export-woocommerce'), '-', '');
                return;
            }
        }



        wp_suspend_cache_invalidation(true);

        if (function_exists('WC')) {
            $this->hf_log_data_change('csv-import', '---');
            $this->hf_log_data_change('csv-import', esc_html__('Processing product comments.', 'comments-import-export-woocommerce'));
        } else {
            // $this->log = new WP_Logger();
        }
        foreach ($this->parsed_data as $key => &$item) {

            $product = $this->parser->parse_product_comment($item, 0);
            if (!is_wp_error($product))
                $this->process_product_comments($product);
            else
                $this->add_import_result('failed', $product->get_error_message(), 'Not parsed', json_encode($item), '-');

            unset($item, $product);
        }
        if (function_exists('WC')) {
            $this->hf_log_data_change('csv-import', esc_html__('Finished processing product comments.', 'comments-import-export-woocommerce'));
        } else {
            // $this->log = new WP_Logger();
        }
        wp_suspend_cache_invalidation(false);
    }

    public function wp_hf_let_to_num($size)
    {
        $l = substr($size, -1);
        $ret = substr($size, 0, -1);
        switch (strtoupper($l)) {
            case 'P':
                $ret *= 1024;
            case 'T':
                $ret *= 1024;
            case 'G':
                $ret *= 1024;
            case 'M':
                $ret *= 1024;
            case 'K':
                $ret *= 1024;
        }
        return $ret;
    }

    /**
     * Parses the CSV file and prepares us for the task of processing parsed data
     *
     * @param string $file Path to the CSV file for importing
     */
    public function import_start($file, $mapping, $start_pos, $end_pos, $eval_field)
    {
        if (function_exists('WC')) {

            if ( version_compare( WC()->version, '2.7.0', '<' ) ) {
                $memory = size_format(woocommerce_let_to_num(ini_get('memory_limit')));
                $wp_memory = size_format(woocommerce_let_to_num(WP_MEMORY_LIMIT));
            } else {
                $memory = size_format(wc_let_to_num(ini_get('memory_limit')));
                $wp_memory = size_format(wc_let_to_num(WP_MEMORY_LIMIT));
            }
            $this->hf_log_data_change('csv-import', '---[ New Import ] PHP Memory: ' . $memory . ', WP Memory: ' . $wp_memory);
            $this->hf_log_data_change('csv-import', esc_html__('Parsing product comments CSV.', 'comments-import-export-woocommerce'));
        } else {
            $memory = size_format($this->wp_hf_let_to_num(ini_get('memory_limit')));
            $wp_memory = size_format($this->wp_hf_let_to_num(WP_MEMORY_LIMIT));
        }

        $this->parser = new HW_CSV_Parser('product');

        list($this->parsed_data, $this->raw_headers, $position) = $this->parser->parse_data($file, $this->delimiter, $mapping, $start_pos, $end_pos, $eval_field);

        if (function_exists('WC')) {
            $this->hf_log_data_change('csv-import', esc_html__('Finished parsing product comments CSV.', 'comments-import-export-woocommerce'));
        }

        unset($import_data);

        //        wp_defer_term_counting(true);
        wp_defer_comment_counting(true);

        return $position;
    }

    /**
     * Performs post-import cleanup of files and the cache
     */
    public function import_end()
    {

        //        foreach (get_taxonomies() as $tax) {
        //            delete_option("{$tax}_children");
        //            _get_term_hierarchy($tax);
        //        }

        //        wp_defer_term_counting(false);
        wp_defer_comment_counting(false);

        do_action('import_end');
    }
    public function product_id_not_exists($id, $cmd_type)
    {
        global $wpdb;
        $args = apply_filters('hf_cmt_imp_post_exist_qry_args', array()); // Added a filter if anyone want to restrict import comments for post which has comment_status is closed.
        $args_allowed_columns = array(
            'post_status',
            'post_author',
            'post_date',
            'post_name',
            'post_parent',
        );


        $query = "SELECT ID FROM $wpdb->posts WHERE ID = %d AND post_type IN ('post', 'product')";
        $placeholder_arr = array( $id );
        
        $query = apply_filters( 'wt_cmt_imp_post_exists_query', $query, $placeholder_arr );
        if (is_array($args) && !empty($args)) {
            foreach ($args as $key => $value) {
                if ( ! in_array( $key, $args_allowed_columns, true ) ) {
                    continue;
                }
                $query .= " AND $key=%s";
                $placeholder_arr[] = $value;
            }
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $posts_that_exist = $wpdb->get_col( call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $query ), $placeholder_arr ) ) ); // @codingStandardsIgnoreLine.
        return ( ! $posts_that_exist );
    }
    /**
     * Handles the CSV upload and initial parsing of the file to prepare for
     * displaying author import options
     *
     * @return bool False if error uploading or invalid file, true otherwise
     */
    public function handle_upload()
    {
        if ($this->handle_ftp()) {
            return true;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification already done in the dispatch() method.
        if ( empty( $_POST['file_url'] ) ) { // @codingStandardsIgnoreLine.
            
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification already done in the dispatch() method.
            if ( empty( $_FILES['import']["name"] ) ) { // @codingStandardsIgnoreLine.
                ?>
                <script type="text/javascript">
                    window.location.href = '<?php echo esc_url( admin_url( 'edit-comments.php?page=hw_cmt_csv_im_ex' ) ); ?>';
                </script>
                <?php
            }


            $file = wp_import_handle_upload();

            if ( isset( $file['error'] ) ) {
                echo '<p><strong>' . esc_html__('Sorry, there has been an error.', 'comments-import-export-woocommerce') . '</strong><br />';
                echo wp_kses_post( $file['error'] );
                echo '&nbsp;<a href="'. esc_url( wp_get_referer() ) . '">'.esc_html__('Back', 'comments-import-export-woocommerce').' </a>';
                echo '</p>';
                return false;
            }

            $this->id = (int) $file['id'];
            return true;
        } else {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification already done in the dispatch() method.
            $sanitized_file_url = sanitize_text_field( wp_unslash( $_POST['file_url'] ) ); // @codingStandardsIgnoreLine.
            $full_path = realpath( ABSPATH . $sanitized_file_url );

            if ( $full_path !== false && strpos( $full_path, ABSPATH ) === 0 && file_exists( $full_path ) ) {
                $this->file_url = esc_attr( $sanitized_file_url );
                return true;
            } else {
                echo '<p><strong>' . esc_html__('Sorry, there has been an error.', 'comments-import-export-woocommerce') . '</strong></p>';
                return false;
            }
        }

        return false;
    }

    public function product_comment_exists( $id ) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $posts_that_exist = $wpdb->get_col( $wpdb->prepare("SELECT comment_ID FROM {$wpdb->comments} WHERE comment_ID = %d AND comment_approved != 'trash' ", $id) ); // @codingStandardsIgnoreLine.
        
        return (is_array( $posts_that_exist ) && ! empty( $posts_that_exist ) );
    }

    public function get_last_comment_id()
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $get_id = $wpdb->get_row("SHOW TABLE STATUS LIKE '" . $wpdb->prefix . "comments'");
        $last_id = $get_id->Auto_increment;
        return $last_id;
    }

    /**
     * Create new posts based on import information
     */
    public function process_product_comments($post)
    {

        $post_type_inserted_by_wtim = isset($post['added_by_wtci']) ? $post['added_by_wtci'] : false;
        $processing_product_id = absint($post['comment_ID']);
        $merging = !empty($post['merging']);
        $comment_txt = !empty($post['comment_content']) ? esc_html($post['comment_content']) : 'Empty';

        if ($post['comment_type'] != 'woodiscuz') {
            $cmd_type = 'comment';
            $product_post = __('The post doesn\'t exist.', 'comments-import-export-woocommerce');
        } else {
            $cmd_type = $post['comment_type'];
            $product_post = __('The product doesn\'t exist.', 'comments-import-export-woocommerce');
        }

        $processing_product_title = (!empty($post['post_title']) ? $post['post_title'] : '');


        if (!empty($processing_product_id) && isset($this->processed_posts[$processing_product_id])) {
            $this->add_import_result('skipped', __('Product comment already processed', 'comments-import-export-woocommerce'), $processing_product_id, $comment_txt);
            if (function_exists('WC')) {
                $this->hf_log_data_change('csv-import', __('> Post ID already processed. Skipping.', 'comments-import-export-woocommerce'), true);
            }
            unset($post);
            return;
        }

        if (!empty($post['post_status']) && $post['post_status'] == 'auto-draft') {
            $this->add_import_result('skipped', __('Skipping auto-draft', 'comments-import-export-woocommerce'), $processing_product_id, $comment_txt);
            if (function_exists('WC')) {
                $this->hf_log_data_change('csv-import', __('> Skipping auto-draft.', 'comments-import-export-woocommerce'), true);
            }
            unset($post);
            return;
        }
        // Check if post exists when importing
        $is_post_exist_in_db = $this->product_comment_exists($processing_product_id);
        if (!$merging) {

            if ($is_post_exist_in_db && ! $post_type_inserted_by_wtim) {

                $this->add_import_result('skipped', __('This Comment ID Already Exists', 'comments-import-export-woocommerce'), $processing_product_id, $comment_txt);
                if (function_exists('WC')) {
                    // translators: %s is the product title
                    $this->hf_log_data_change( 'csv-import', sprintf( __('> &#8220;%s&#8221; This Comment ID Already Exists', 'comments-import-export-woocommerce'), esc_html($processing_product_title)), true );
                }
                unset($post);
                return;
            }
        }


        if (!empty($post['comment_post_ID'])) {

            $is_product__id_not_exist = $this->product_id_not_exists($post['comment_post_ID'], $cmd_type);
            if ($is_product__id_not_exist) {
                $usr_msg = $product_post;
                $this->add_import_result('skipped', $usr_msg, $processing_product_id, $comment_txt);
                if (function_exists('WC')) {
                    // translators: %s is the product title
                    $this->hf_log_data_change( 'csv-import', sprintf(__('> &#8220;%s&#8221; ', 'comments-import-export-woocommerce') . $usr_msg, esc_html($processing_product_title)), true );
                }
                unset($post);
                return;
            }
        }

        if ($merging && !empty($is_post_exist_in_db)) {

            // Only merge fields which are set
            $post_id = $processing_product_id;
            if (function_exists('WC')) {
                // translators: %s is the product id
                $this->hf_log_data_change('csv-import', sprintf(__('> Merging post ID %s.', 'comments-import-export-woocommerce'), $post_id), true);
            }
            if (!empty($post['comment_post_ID'])) {
                $postdata['comment_post_ID'] = $post['comment_post_ID'];
            }

            if (!empty($post['comment_author'])) {
                $postdata['comment_author'] = $post['comment_author'];
            }
            if (!empty($post['comment_date'])) {
                $postdata['comment_date'] = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $post['comment_date'] ) ) );
            }
            if (!empty($post['comment_date_gmt'])) {
                $postdata['comment_date_gmt'] = gmdate( 'Y-m-d H:i:s', strtotime( $post['comment_date_gmt'] ) );
            }
            if (!empty($post['comment_author_email'])) {
                $postdata['comment_author_email'] = $post['comment_author_email'];
            }
            if (!empty($post['comment_author_url'])) {
                $postdata['comment_author_url'] = $post['comment_author_url'];
            }
            if (!empty($post['comment_author_IP'])) {
                $postdata['comment_author_IP'] = $post['comment_author_IP'];
            }
            if (!empty($post['comment_content'])) {
                $postdata['comment_content'] = $post['comment_content'];
            }
            if (!empty($post['comment_approved'])) {
                $postdata['comment_approved'] = $post['comment_approved'];
            }
            if ($post['comment_type'] != 'woodiscuz') {
                $postdata['comment_type'] = 'comment';
            } else {
                $postdata['comment_type'] = 'woodiscuz';
            }
            if (!empty($post['comment_parent'])) {
                $postdata['comment_parent'] = $post['comment_parent'];
            }
            if (!empty($post['user_id'])) {
                $postdata['user_id'] = $post['user_id'];
            }
            if (sizeof($postdata) > 1) {
                global $wpdb;
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $result = $wpdb->update('wp_comments', $postdata, array('comment_ID' => $post_id));
            }
            if (!empty($post['postmeta']) && is_array($post['postmeta'])) { //update data in commentmeta table
                foreach ($post['postmeta'] as $meta) {
                    update_comment_meta($post_id, $meta['key'], $meta['value']);
                }
                unset($post['postmeta']);
            }
        } else {
            $merging = FALSE;

            //check child data
            // Insert product
            if (function_exists('WC')) {
                // translators: %s is the product id
                $this->hf_log_data_change('csv-import', sprintf(__('> Inserting %s', 'comments-import-export-woocommerce'), esc_html($processing_product_id)), true);
            }
            /* if ($post['comment_parent'] === '0') {
                $this->parent_data = $post['comment_parent'];
                $_SESSION['new_id'][$post['comment_alter_id']] = $this->get_last_comment_id();
            } else {
				if(!empty($_SESSION['new_id'][$post['comment_parent']]))
				{
						$this->parent_data = $_SESSION['new_id'][$post['comment_parent']];
				}
				else
				{
					$this->parent_data = $post['comment_parent'];
				}
                $_SESSION['new_id'][$post['comment_alter_id']] = $this->get_last_comment_id();
            }*/

            $comment_parent = $post['comment_parent'];
            $comment_parent_session = unserialize(get_option('wt_post_comment_alter_id'));
            if ($post['comment_parent'] != 0) {
                $arr_index = $post['comment_parent'];
                if (isset($comment_parent_session['wt_comment_basic']) && array_key_exists($arr_index, $comment_parent_session['wt_comment_basic'])) {
                    $comment_parent = $comment_parent_session['wt_comment_basic'][$arr_index];
                }
            }
            $postdata = array(
                'comment_ID' => $processing_product_id,
                'comment_post_ID' => $post['comment_post_ID'],
                'comment_date' => ! empty( $post['comment_date'] ) ? get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $post['comment_date'] ) ) ) : '',
                'comment_date_gmt' => ! empty( $post['comment_date_gmt'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $post['comment_date_gmt'] ) ) : '',
                'comment_author' => $post['comment_author'],
                'comment_author_email' => $post['comment_author_email'],
                'comment_author_url' => $post['comment_author_url'],
                'comment_author_IP' => $post['comment_author_IP'],
                'comment_content' => ($post['comment_content']) ? $post['comment_content'] : sanitize_title(isset($post['comment_title']) ? $post['comment_title'] : ''),
                'comment_approved' => ($post['comment_approved']) ? $post['comment_approved'] : 0,
                'comment_type' => $cmd_type,
                'comment_parent' => $comment_parent,
                'user_id' => $post['user_id'],
            );

            if (isset($post['comment_ID']) && !empty($post['comment_ID'])) {
                if (sizeof($postdata) > 1) {
                    global $wpdb;
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $updated = $wpdb->update('wp_comments', $postdata, array('comment_ID' => $post['comment_ID']));
                    if (!$updated) {
                        if (!empty($post['comment_ID'])) {
                            $postdata['comment_ID'] = $post['comment_ID'];
                        }
                        $updated = wp_update_comment($postdata);
                    }
                    if ($updated) {
                        $post_id = $post['comment_ID'];
                    } else {
                        $post_id = $updated;
                    }
                }
            } else {
                $post_id = wp_insert_comment($postdata, true);
            }
            $comment_parent_session['wt_comment_basic'][$post['comment_alter_id']] = $post_id;
            update_option('wt_post_comment_alter_id', serialize($comment_parent_session));
            unset($comment_parent_session);

            if ($cmd_type === 'woodiscuz') {
                global $wpdb;
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SlowDBQuery
                $wpdb->insert($wpdb->commentmeta, array('comment_ID' => $post_id, 'meta_key' => 'verified', 'meta_value' => '1')); // @codingStandardsIgnoreLine.
            }
            if (!empty($post['postmeta']) && is_array($post['postmeta'])) { //insert comment meta to wp_commentmeta table
                foreach ($post['postmeta'] as $meta) {
                    add_comment_meta($post_id, $meta['key'], $meta['value']);
                }
                unset($post['postmeta']);
            }

            //$new_Id.push($post_id);
            if (function_exists('WC')) {
                $this->hf_log_data_change('csv-import', $post_id . 'hi'. esc_html($processing_product_title));
            }
            if (is_wp_error($post_id) || $post_id == false) {

                $this->add_import_result('failed', __('Failed to import product comment', 'comments-import-export-woocommerce'), $processing_product_id);
                if (function_exists('WC')) {
                    // translators: %s is the product title
                    $this->hf_log_data_change('csv-import', sprintf(__('Failed to import product comment &#8220;%s&#8221;', 'comments-import-export-woocommerce'), esc_html($processing_product_title)));
                }
                unset($post);
                return;
            } else {
                if (function_exists('WC')) {
                    // translators: %s is the product id
                    $this->hf_log_data_change('csv-import', sprintf(__('> Inserted - post ID is %s.', 'comments-import-export-woocommerce'), $post_id));
                }
            }
        }
        unset($postdata);
        // map pre-import ID to local ID
        if (empty($processing_product_id)) {
            $processing_product_id = (int) $post_id;
        }
        $this->processed_posts[intval($processing_product_id)] = (int) $post_id;

        if ($merging) {
            $this->add_import_result('merged', 'Merge successful', $post_id, $comment_txt);
            if (function_exists('WC')) {
                // translators: %s is the product id
                $this->hf_log_data_change('csv-import', sprintf(__('> Finished merging post ID %s.', 'comments-import-export-woocommerce'), $post_id));
            }
        } else {
            $this->add_import_result('imported', 'Import successful', $post_id, $comment_txt);
            if (function_exists('WC')) {
                // translators: %s is the product id
                $this->hf_log_data_change('csv-import', sprintf(__('> Finished importing post ID %s.', 'comments-import-export-woocommerce'), $post_id));
            }
        }
        unset($post);
    }

    /**
     * Log a row's import status
     */
    protected function add_import_result($status, $reason, $post_id = '', $cmd_title = '')
    {
        $this->import_results[] = array(
            'post_id' => $post_id,
            'status' => $status,
            'reason' => $reason,
            'comment_link' => get_comment_link($Comment = $post_id),
            'cmd_title' => $cmd_title,
        );
    }

    /**
     * Attempt to download a remote file attachment
     */
    public function fetch_remote_file($url, $post)
    {
        // Validate URL
        $url = filter_var($url, FILTER_VALIDATE_URL);
        if (!$url) {
            return new WP_Error('import_file_error', 'Invalid URL');
        }

        // Additional URL security checks
        $allowed_hosts = apply_filters('hw_import_allowed_hosts', array(
            wp_parse_url(home_url(), PHP_URL_HOST)
        ));
        $url_host = wp_parse_url($url, PHP_URL_HOST);

        if (!in_array($url_host, $allowed_hosts)) {
            return new WP_Error('import_file_error', 'Remote file host not allowed');
        }

        // extract the file name and extension from the url
        $file_name = basename(current(explode('?', $url)));
        $wp_filetype = wp_check_filetype($file_name, null);
        $parsed_url = wp_parse_url($url);

        // Check parsed URL
        if (!$parsed_url || !is_array($parsed_url))
            return new WP_Error('import_file_error', 'Invalid URL');

        // Ensure url is valid
        $url = str_replace(" ", '%20', $url);

        // Get the file
        $response = wp_remote_get($url, array(
            'timeout' => 10
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200)
            return new WP_Error('import_file_error', 'Error getting remote image');

        // Ensure we have a file name and type
        if (!$wp_filetype['type']) {

            $headers = wp_remote_retrieve_headers($response);

            if (isset($headers['content-disposition']) && strstr($headers['content-disposition'], 'filename=')) {

                $disposition = end(explode('filename=', $headers['content-disposition']));
                $disposition = sanitize_file_name($disposition);
                $file_name = $disposition;
            } elseif (isset($headers['content-type']) && strstr($headers['content-type'], 'image/')) {

                $file_name = 'image.' . str_replace('image/', '', $headers['content-type']);
            }

            unset($headers);
        }

        // Upload the file
        $upload = wp_upload_bits($file_name, null, wp_remote_retrieve_body($response));

        if ($upload['error'])
            return new WP_Error('upload_dir_error', $upload['error']);

        // Get filesize
        $filesize = filesize($upload['file']);

        if (0 == $filesize) {
            wp_delete_file($upload['file']);
            unset($upload);
            return new WP_Error('import_file_error', __('Zero size file downloaded', 'comments-import-export-woocommerce'));
        }

        unset($response);

        return $upload;
    }

    /**
     * Decide what the maximum file size for downloaded attachments is.
     * Default is 0 (unlimited), can be filtered via import_attachment_size_limit
     *
     * @return int Maximum attachment file size to import
     */
    public function max_attachment_size()
    {
        return apply_filters('import_attachment_size_limit', 0);
    }

    private function handle_ftp()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification already done in the dispatch() method.
        $enable_ftp_ie = !empty($_POST['enable_ftp_ie']); // @codingStandardsIgnoreLine.

        // Update the setting early if FTP is disabled
        if (!$enable_ftp_ie) {
            $settings_in_db = get_option('hw_shipment_tracking_importer_ftp', []);
            $settings_in_db['enable_ftp_ie'] = false;
            update_option('hw_shipment_tracking_importer_ftp', $settings_in_db);
            return false;
        }

        // Sanitize and validate user input

        // @codingStandardsIgnoreStart
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification already done in the dispatch() method.
        $ftp_server = !empty($_POST['ftp_server']) ? sanitize_text_field(wp_unslash(rtrim($_POST['ftp_server'], "-"))) : ''; 
        $ftp_server_path = !empty($_POST['ftp_server_path']) ? sanitize_text_field(wp_unslash($_POST['ftp_server_path'])) : '';
        $ftp_user = !empty($_POST['ftp_user']) ? sanitize_text_field(wp_unslash($_POST['ftp_user'])) : '';
        $ftp_port = !empty($_POST['ftp_port']) ? absint(wp_unslash($_POST['ftp_port'])) : 21;
        $ftp_password = !empty($_POST['ftp_password']) ? sanitize_text_field(wp_unslash($_POST['ftp_password'])) : '';
        $use_ftps = !empty($_POST['use_ftps']);
        $use_pasv = !empty($_POST['use_pasv']);
        // @codingStandardsIgnoreEnd

        // Save FTP settings
        $settings = [
            'ftp_server' => $ftp_server,
            'ftp_user' => $ftp_user,
            'ftp_password' => $ftp_password,
            'ftp_port' => $ftp_port,
            'use_ftps' => $use_ftps,
            'use_pasv' => $use_pasv,
            'enable_ftp_ie' => $enable_ftp_ie,
            'ftp_server_path' => $ftp_server_path,
        ];
        update_option('hw_shipment_tracking_importer_ftp', $settings);

        $local_file = 'wp-content/plugins/comments-import-export-woocommerce/temp-import.csv';
        $server_file = $ftp_server_path;
        $success = false;

        try {
            if (empty($ftp_server) || empty($ftp_user) || empty($ftp_password)) {
                throw new Exception('FTP/SFTP credentials are incomplete');
            }

            if (empty($server_file)) {
                throw new Exception('Please provide the remote file path.');
            }

            if ($ftp_port == 22) {
                include_once(plugin_dir_path(__FILE__) . '../vendor/sftp-modules/sftp.php');

                // Assume SFTP connection
                if (!class_exists('class_wf_sftp_import_export')) {
                    throw new Exception('SFTP module not found. Please install the SFTP add-on.');
                }

                $sftp_import = new class_wf_sftp_import_export();

                if (!$sftp_import->connect($ftp_server, $ftp_user, $ftp_password, $ftp_port)) {
                    throw new Exception('Unable to connect to the SFTP server. Please verify the Host/IP and Port number.');
                }

                $file_contents = $sftp_import->get_contents($server_file);

                if (empty($file_contents)) {
                    $errors = $sftp_import->getErrors();
                    $error_message = !empty($errors) ? implode(', ', $errors) : 'Failed to download the file from the SFTP server. Please check the file path or permissions.';
                    throw new Exception($error_message);
                }

                file_put_contents(ABSPATH . $local_file, $file_contents);
                $success = true;
            } else {
                // Assume FTP or FTPS connection
                // var_dump($ftp_server);exit;
                //                 var_dump(function_exists('ftp_connect'));       // should be true
                // var_dump(function_exists('ftp_ssl_connect')); exit;
                try {
                    // Confirm functions
                    if (!function_exists('ftp_connect')) {
                        throw new Exception('FTP functionality is not available. Please enable the PHP FTP extension.');
                    }

                    // Validate host format
                    if (empty($ftp_server) || (!filter_var($ftp_server, FILTER_VALIDATE_IP) && !filter_var($ftp_server, FILTER_VALIDATE_DOMAIN))) {
                        throw new Exception('Invalid FTP server address.');
                    }

                    // Set a connection timeout manually
                    $ftp_timeout = 10; // seconds

                    if ($use_ftps) {
                        // Try SSL connect with timeout
                        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                        $ftp_conn = @ftp_ssl_connect($ftp_server, 21, $ftp_timeout); // @codingStandardsIgnoreLine.
                        if (!$ftp_conn) {
                            // Try plain FTP fallback automatically
                            // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                            $ftp_conn = @ftp_connect($ftp_server, 21, $ftp_timeout); // @codingStandardsIgnoreLine.
                            if ($ftp_conn) {
                                $use_ftps = false; // Downgrade to FTP mode
                            }
                        }
                    } else {
                        // Try normal FTP connect
                        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                        $ftp_conn = @ftp_connect($ftp_server, 21, $ftp_timeout); // @codingStandardsIgnoreLine.
                    }

                    if (!$ftp_conn) {
                        throw new Exception('Could not connect to the FTP/FTPS server. Please verify the server address, port, and server status.');
                    }

                    // Login attempt
                    // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                    if (!@ftp_login($ftp_conn, $ftp_user, $ftp_password)) { // @codingStandardsIgnoreLine.
                        ftp_close($ftp_conn);
                        throw new Exception('FTP login failed. Please check your username and password.');
                    }

                    // Passive mode if needed
                    if ($use_pasv) {
                        ftp_pasv($ftp_conn, true);
                    }

                    // File download attempt
                    // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                    if (!@ftp_get($ftp_conn, ABSPATH . $local_file, $server_file, FTP_BINARY)) { // @codingStandardsIgnoreLine.
                        ftp_close($ftp_conn);
                        throw new Exception('Failed to download the file from the FTP/FTPS server. Check file path and permissions.');
                    }

                    ftp_close($ftp_conn);
                    $success = true;
                } catch (Exception $e) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('FTP error: ' . $e->getMessage()); // @codingStandardsIgnoreLine.
                    wp_die(esc_html($e->getMessage()));
                }

            }

            if ($success) {
                $this->file_url = $local_file;
                return true;
            } else {
                throw new Exception('Unknown error occurred during FTP/SFTP transfer.');
            }
        } catch (Exception $e) {
            wp_die(esc_html($e->getMessage()));
        }
    }

    // Display import page title
    public function header()
    {
        echo '<div class="wrap"><div class="icon32" id="icon-woocommerce-importer"><br></div>';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification not needed.
        echo '<h2>' . (empty($_GET['merge']) ? esc_html__('Import', 'comments-import-export-woocommerce') : esc_html__('Merge WordPress Comments', 'comments-import-export-woocommerce')) . '</h2>'; // @codingStandardsIgnoreLine.
    }

    // Close div.wrap
    public function footer()
    {
        echo '</div>';
        add_action('wp_logout', array($this, 'hf_cmt_im_ex_myEndSession'));
        add_action('wp_login', array($this, 'hf_cmt_im_ex_myEndSession'));
    }

    /**
     * Display introductory text and file upload form
     */
    public function greet()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification not needed.
        $action = 'admin.php?import=product_comments_csv&step=1&merge=' . (!empty($_GET['merge']) ? 1 : 0); // @codingStandardsIgnoreLine.
        $bytes = apply_filters('import_upload_size_limit', wp_max_upload_size());
        $size = size_format($bytes);
        $upload_dir = wp_upload_dir();
        $ftp_settings = get_option('hw_shipment_tracking_importer_ftp');
        include('views/html-hf-import-greeting.php');
    }

    /**
     * Added to http_request_timeout filter to force timeout at 60 seconds during import
     * @return int 60
     */
    public function bump_request_timeout($val)
    {
        return 60;
    }
    public static function is_valid_file_path($file_url)
    {
        $real_file_path = realpath($file_url);

        if (! $real_file_path) {
            return false;
        }

        $content_dir         = realpath(WP_CONTENT_DIR); // Get the real path of WP_CONTENT_DIR.
        $upload_dir         = wp_upload_dir();
        $current_upload_dir = realpath($upload_dir['path']); // Get the real path of wp upload directory.
        $plugin_upload_dir     = $content_dir . '/plugins/comments-import-export-woocommerce'; // Plugin directory for FTP upload.

        return (strpos($real_file_path, $content_dir) === 0) &&
            (($current_upload_dir && strpos($real_file_path, $current_upload_dir) === 0) ||
                (strpos($real_file_path, $plugin_upload_dir) === 0)
            );
    }

    // Add a more robust sanitization helper method in the class
    public static function sanitize_csv_input($input, $type = 'text')
    {
        if (is_array($input)) {
            return array_map(function ($item) use ($type) {
                return self::sanitize_csv_input($item, $type);
            }, $input);
        }

        switch ($type) {
            case 'int':
                return absint($input);
            case 'float':
                return floatval($input);
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'filename':
                return sanitize_file_name($input);
            default:
                return sanitize_text_field($input);
        }
    }
}

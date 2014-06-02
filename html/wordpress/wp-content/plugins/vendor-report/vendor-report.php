<?php
/**
 * Plugin Name: plugin_vendor_report
 * Plugin URI: http://www.pandoapps.com.br
 * Description: A plugin for woocommerce product vendor saying who bought each item
 * Version: 1.0
 * Author: Thiago Ferreira - Pandô APPs
 * Author URI: http://www.pandoapps.com.br
 * License: GPL2
 */

	add_action( 'admin_menu', 'plugin_vendor_report' );

	function plugin_vendor_report() {
		add_menu_page( 'Vendor Report', 'Vendor Report', 'read', 'plugin-vendor-report', 'plugin_vendor_report_options' );
	}

	function plugin_vendor_report_options() {
		echo '<div class="wrap">';
		echo vendors_buyers_list_report();
		echo '</div>';

	}


	/**
     * Detailed report - list of buyers - for logged in user
     * @return str Report HTML
     */
    function vendors_buyers_list_report() {

        $html = '';

   		global $current_user;
        wp_get_current_user();
        $user_id = $current_user->ID;

        $selected_year = ( isset( $_POST['report_year'] ) && $_POST['report_year'] != 'all' ) ? $_POST['report_year'] : false;
        $selected_month = ( isset( $_POST['report_month'] ) && $_POST['report_month'] != 'all' ) ? $_POST['report_month'] : false;

        // Get all vendor sales
        $sales = get_buyers_list( $user_id, $selected_year, $selected_month, false );

        $month_options = '<option value="all">' . __( 'All months', 'wc_product_vendors' ) . '</option>';
        for( $i = 1; $i <= 12; $i++ ) {
            $month_num = str_pad( $i, 2, 0, STR_PAD_LEFT );
            $month_name = date( 'F', mktime( 0, 0, 0, $i + 1, 0, 0 ) );
            $month_options .= '<option value="' . esc_attr( $month_num ) . '" ' . selected( $selected_month, $month_num, false ) . '>' . $month_name . '</option>';
        }

        $year_options = '<option value="all">' . __( 'All years', 'wc_product_vendors' ) . '</option>';
        $current_year = date( 'Y' );
        for( $i = $current_year; $i >= ( $current_year - 5 ); $i-- ) {
            $year_options .= '<option value="' . $i . '" ' . selected( $selected_year, $i, false ) . '>' . $i . '</option>';
        }


        $html .= '<div class="product_vendors_report_form">
                    <form name="product_vendors_report" action="' . get_permalink() . '" method="post">
                        ' . __( 'Select report date:', 'wc_product_vendors' ) . '
                        <select name="report_month">' . $month_options . '</select>
                        <select name="report_year">' . $year_options . '</select>
                        <input type="submit" class="button" value="Submit" />
                    </form>
                  </div>';


        $html .= '<table class="wp-list-table widefat fixed posts" cellspacing="0">
                    <thead>
                        <tr>
                            <th>' . __( 'ID', 'wc_product_vendors' ) . '</th>
                            <th>' . __( 'Nome', 'wc_product_vendors' ) . '</th>
                            <th>' . __( 'Email', 'wc_product_vendors' ) . '</th>
                            <th>' . __( 'Produto', 'wc_product_vendors' ) . '</th>
                            <th>' . __( 'Total', 'wc_product_vendors' ) . '</th>
                            <th>' . __( 'Data', 'wc_product_vendors' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>';

        $count = 0;
        foreach( $sales as $sale ) {
        	$count++;
            $tempHtml = '';
            $fist_name = get_post_meta( $sale->ID, '_billing_first_name', true );
            $last_name = get_post_meta( $sale->ID, '_billing_last_name', true );
            $address = get_post_meta( $sale->ID, '_billing_address_1', true );
            $city = get_post_meta( $sale->ID, '_billing_city', true );
            $state = get_post_meta( $sale->ID, '_billing_state', true );
            $postcode = get_post_meta( $sale->ID, '_billing_postcode', true );
            $email = get_post_meta( $sale->ID, '_billing_email', true );
            $fone = get_post_meta( $sale->ID, '_billing_phone', true );
            $method = get_post_meta( $sale->ID, '_payment_method_title', true );
            $discount = get_post_meta( $sale->ID, '_order_discount', true );
            $total = get_post_meta( $sale->ID, '_order_total', true );
            $paypal_address = get_post_meta( $sale->ID, 'Payer PayPal address', true );
            $vendor_paypal_name = get_post_meta( $sale->ID, 'Payer first name', true );
            $vendor_paypal_last_name = get_post_meta( $sale->ID, 'Payer last name', true );
            $paid_date = get_post_meta( $sale->ID, '_paid_date', true );
            $complete_date = get_post_meta( $sale->ID, '_completed_date', true );

            $tempHtml .= '<tr '. (($count%2==0)?'class="alternate"':'').'>
                            <td>' . $sale->ID . '</td>
                            <td>' . $fist_name . ' ' . $last_name . '</td>
                            <td>' . $email . '</td>
                            <td>';

            $orders = new WC_Order( $sale->ID );
            $add = false;

            foreach( $orders->get_items() as $order ) {
                $args = array(
                    'author' => $user_id,
                    'post_type' => 'product',
                    'posts_per_page' => 1,
                    'include' => array($order["product_id"])
                );
                $products = get_posts( $args );

                $tempHtml .= '<a href="' . esc_url( get_permalink( $order["product_id"] ) ) . '">' . $products[0]->post_title. '</a> - ' . $order["item_meta"]['_qty'][0] . '<br/>' ;

                if(!$add && sizeof($products)>0)
                    $add = true;
            }

            $tempHtml .=                 '</td>
                        <td>' . $total . '</td>
                        <td>' . $complete_date . '</td>
                      </tr>';

            if($add)
                $html .= $tempHtml;

        }

        $html .= '</tbody>
                </table>';
        

        return $html;
    }

    /**
	 * Get all buyers assigned to a specific vendor
	 * @param  int $vendor_id ID of vendor
	 * @return arr            Array or buyers objects
	 */
	function get_buyers_list( $vendor_id = 0, $year = false, $month = false, $day = false ) {
		$commissions = false;

		if( $vendor_id > 0 ) {
			$args = array(
				'post_type' => 'shop_order',
				'post_status' => array( 'publish', 'private' ),
				'posts_per_page' => -1
			);

			// Add date parameters if specified
			if( $year ) $args['year'] = $year;
			if( $month ) $args['monthnum'] = $month;
			if( $day ) $args['day'] = $day;

			$sales = get_posts( $args );
		}

		return $sales;
	}
?>
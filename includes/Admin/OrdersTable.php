<?php

/**

 * @package 1account for WooCommerce

 */



namespace Oneacc\Admin;



use Oneacc\Base\BaseVarsController;



class OrdersTable extends BaseVarsController {



    public function register() {

        add_filter('manage_shop_order_posts_columns', [$this, 'set_custom_edit_post_columns'], 99, 1);

        add_action('manage_shop_order_posts_custom_column', [$this, 'one_acc_woo_order_status_column'], 99, 2);

        //add_action('updated_user_meta', [$this, 'check_order_status_on_age_verification_update'], 10, 4);

    }



    // Add column on the Order page to show 1Account validation status.

    function set_custom_edit_post_columns($columns) {

        $columns['oneacc-validation-status'] = __('Order validation status', $this->plugin_text_domain);

        return $columns;

    }



    // Prepare content for the WooCommerce Orders table.

    function one_acc_woo_order_status_column($column, $post_id) {

        switch ($column) {

            case 'oneacc-validation-status':

                $order = wc_get_order($post_id);

                $user_id = $order->get_user_id();

                // Retrieve the user's age verification status.

                $av_status = get_user_meta($user_id, 'one_acc_woo_av_status', true);

                if (empty($user_id)) {

                    $user_staus = get_post_meta($post_id, 'av_order_tag_key', true);

                    $current_time = current_time('timestamp');
                    $order_time = strtotime($order->get_date_created());
                    $time_difference = $current_time - $order_time;

                    $serialized_option = get_option('wc_oneacc_order_status_settings', true);

                    if (isset($serialized_option['oneacc_status']) && isset($serialized_option['one_acc_user_status']) && is_array($serialized_option )) {
                        $order_statuses = $serialized_option['oneacc_status'];
                        $user_statuses = $serialized_option['one_acc_user_status'];
                        $hasEchoed = false;

                        foreach ($user_statuses as $key => $user_status ) {
                            $custom_order_status = strtolower(str_replace(' ', '_', $order_statuses[$key]));
                            $custom_user_status = strtolower(str_replace(' ', '_', $user_status));

                            if ($custom_order_status == $order->get_status() && !$hasEchoed ) {


                                if($custom_user_status == 'av_success'){

                                    echo "<mark class='order-status status-processing'><span>" . __('AV Pass', $this->plugin_text_domain) . "</span></mark>";

                                }elseif($custom_user_status == 'av_failed'){

                                    if ($time_difference >= 259200) {

                                        echo "<mark class='order-status status-on-hold'><span>" . __('AV Fail', $this->plugin_text_domain) . "</span></mark>";

                                    }else{
         
                                        echo "<mark class='order-status status-on-hold'><span>" . __('AV Pending', $this->plugin_text_domain) . "</span></mark>";

                                    }


                                }elseif($custom_user_status == 'no_uk_user'){

                                    echo "<mark class='order-status status-on-hold'><span>" . __('NO_UK_USER', $this->plugin_text_domain) . "</span></mark>";

                                }else{

                                     echo "<mark class='order-status status-on-hold'><span>" . __('AV Pending', $this->plugin_text_domain) . "</span></mark>";

                                }
                                    
                                 $hasEchoed = true;
                            }
                        }

                        if (!$hasEchoed) {


                            if($user_staus == 'AV_Pass'){

                                 echo "<mark class='order-status status-processing'><span>" . __('AV Pass', $this->plugin_text_domain) . "</span></mark>";

                            }elseif($user_staus == 'AV_Fail'){

                                if ($time_difference >= 259200) {

                                    echo "<mark class='order-status status-on-hold'><span>" . __('AV Fail', $this->plugin_text_domain) . "</span></mark>";

                                }else{

                                    echo "<mark class='order-status status-on-hold'><span>" . __('AV Pending', $this->plugin_text_domain) . "</span></mark>";

                                }

                            }elseif($user_staus == 'NO_UK_USER'){
                                
                                 echo "<mark class='order-status status-on-hold'><span>" . __('NO_UK_USER', $this->plugin_text_domain) . "</span></mark>";

                            }else{

                                echo "<mark class='order-status status-on-hold'><span>" . __('AV Pending', $this->plugin_text_domain) . "</span></mark>";

                            }

                        }

                    }

        
                }elseif ($av_status === 'av_success') {

                    echo "<mark class='order-status status-processing'><span>" . __('AV Pass', $this->plugin_text_domain) . "</span></mark>";

                } elseif ($av_status === 'av_failed') {

                     if ($time_difference >= 259200) {

                        echo "<mark class='order-status status-on-hold'><span>" . __('AV Fail', $this->plugin_text_domain) . "</span></mark>";

                    }else{

                         echo "<mark class='order-status status-on-hold'><span>" . __('AV Pending', $this->plugin_text_domain) . "</span></mark>";

                    }

                 } elseif ($av_status === 'no_uk_user') {

                    echo "<mark class='order-status status-on-hold'><span>" . __('NO_UK_USER', $this->plugin_text_domain) . "</span></mark>";

                } else {

                    if ($user_id) {

                    echo "<mark class='order-status status-on-hold'><span>" . __('AV Pending', $this->plugin_text_domain) . "</span></mark>";

                    }
                
                }


                break;

        }

    }



    // Change order status to 'age-check-hold' based on user's age verification status.

    function change_order_status_based_on_age_verification($order_id, $order){

        $user_id = $order->get_user_id();
        $av_status = get_user_meta($user_id, 'one_acc_woo_av_status', true);
        if ($av_status === 'av_failed' || $av_status === '') {

            // Change the order status to 'age-check-hold'.
            $order->update_status('age-check-hold');
        }
    }


    
    

}





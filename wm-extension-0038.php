<?php
/*
 * Plugin Name: Woomelly Extension 038 Add ons 
 * Version: 1.0.0
 * Plugin URI: https://woomelly.com
 * Description: Woomelly extension that allows importing questions and answers from Mercado Libre publications related to specific products.
 * Author: Team MakePlugins
 * Author URI: https://woomelly.com
 * Requires at least: 4.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'bulk_actions_edit_product_ext_038' ) ) {
    add_filter( 'bulk_actions-edit-product', 'bulk_actions_edit_product_ext_038', 100, 1 );
    function bulk_actions_edit_product_ext_038 ( $bulk_actions ) {
        $bulk_actions['woomelly_import_qandas'] = __( '— Importar P&Rs —', 'woomelly');
        return $bulk_actions;
    }
}

if ( ! function_exists( 'handle_bulk_actions_edit_product_ext_038' ) ) {
    add_filter( 'handle_bulk_actions-edit-product', 'handle_bulk_actions_edit_product_ext_038', 100, 3 );
    function handle_bulk_actions_edit_product_ext_038 ( $redirect_to, $doaction, $post_ids ) {
        $woomelly_alive = Woomelly()->woomelly_is_connect();
        if ( $woomelly_alive ) {
            if ( !empty($post_ids) ) {
                switch ( $doaction ) {
                    case 'woomelly_import_qandas':
                        foreach ( $post_ids as $post_id ) {
                            $code = get_post_meta( $post_id, '_wm_code_meli', true );
                            if ( $code != "" ) {
                                $data_resource = array();
                                $data_resource = WMeli::search_questions($code);
                                if ( is_object($data_resource) && !empty($data_resource->questions) ) {
                                    $woomellly_items_searched_key = array();
                                    $woomellly_items_searched_data = array();
                                    foreach ( $data_resource->questions as $value ) {
                                        $wmproduct = null;
                                        if ( !in_array($value->item_id, $woomellly_items_searched_key) ) {
                                            $wmproduct = wm_get_product_by_code( $value->item_id );
                                            $woomellly_items_searched_key[] = $value->item_id;
                                            $woomellly_items_searched_data[$value->item_id] = $wmproduct;
                                        } else {
                                            $wmproduct = $woomellly_items_searched_data[$value->item_id];
                                        }
                                        if ( $wmproduct != null ) {
                                            $wm_qanda_exit = wm_get_qand_by_question_id( $value->id );
                                            if ( $wm_qanda_exit == false ) {
                                                $wm_qanda = new WMQandA();
                                                $wm_qanda->set_question_id( $value->id );
                                                $wm_qanda->set_answer( $value->answer );
                                                $wm_qanda->set_date_created( $value->date_created );
                                                $wm_qanda->set_deleted_from_listing( $value->deleted_from_listing );
                                                $wm_qanda->set_hold( $value->hold );
                                                $wm_qanda->set_item_id( $value->item_id );
                                                $wm_qanda->set_seller_id( $value->seller_id );
                                                $wm_qanda->set_status( $value->status );
                                                $wm_qanda->set_text( $value->text );
                                                $wm_qanda->set_from_user( $value->from );
                                                $wm_qanda->set_product_id( $post_id );
                                                $data_user = WMeli::get_users( $value->from->id );
                                                if ( !empty($data_user) ) {
                                                    $wm_qanda->set_from_extra( $data_user );
                                                }
                                                $wm_qanda->set_connect_ml( true );
                                                $save_qanda = $wm_qanda->save();
                                                unset( $wm_qanda );
                                                unset( $data_user );
                                            } else {
                                                $wm_qanda_exit->set_answer( $value->answer );
                                                $wm_qanda_exit->set_deleted_from_listing( $value->deleted_from_listing );
                                                $wm_qanda_exit->set_hold( $value->hold );
                                                $wm_qanda_exit->set_status( $value->status );
                                                $wm_qanda_exit->set_text( $value->text );
                                                $wm_qanda_exit->set_from_user( $value->from );
                                                $wm_qanda_exit->set_connect_ml( true );
                                                $save_qanda = $wm_qanda_exit->update();
                                            }
                                            unset($wm_qanda_exit);
                                        }
                                        unset( $wmproduct );
                                    }
                                }
                                unset($data_resource);
                            }
                        }               
                        wc_setcookie( 'wmid_import_qandas', count( $post_ids ) );
                    break;
                    default:
                    break;
                }
            }
        }
        return $redirect_to;
    }
}

if ( ! function_exists( 'admin_notices_ext_038' ) ) {
    add_action( 'admin_notices', 'admin_notices_ext_038', 10 );
    function admin_notices_ext_038 () {
        if ( isset($_COOKIE['woomelly_import_qandas']) && $_COOKIE['woomelly_import_qandas'] != "" ) {
            $_count = absint( $_COOKIE['woomelly_import_qandas'] );
            if ( $_count > 1 ) {
                echo '<div class="updated"><p>' . sprintf( __( 'Preguntas y Respuestas importadas correctamente. Total: %s', 'woomelly' ), $_count ) . '</p></div>';
            } else {
                echo '<div class="updated"><p>' . __( 'Preguntas y Respuestas importadas correctamente.', 'woomelly' ) . '</p></div>';
            }
            wc_setcookie( 'woomelly_import_qandas', '', time() - HOUR_IN_SECONDS );
        }
    }
}


?>
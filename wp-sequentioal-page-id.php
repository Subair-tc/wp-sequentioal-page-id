<?php
/*
Plugin Name: WP Sequential Page ID
Plugin URI: https://github.com/Subair-tc/
Description: Plugin for making the WP pageID Sequential
Version: 0.1.0
Author: Subair TC
Author URI: https://github.com/Subair-tc/
*/

defined( 'ABSPATH' ) or exit;

class WP_sequential_pag_ID {

    /**
    * Write all Required hooks init
    * @since 0.1.0
    */
    public static function init(){
        add_filter('manage_page_posts_columns', __CLASS__ . '::WP_Sequential_page_columns_head', 10);
        add_action('manage_page_posts_custom_column', __CLASS__ . '::WP_Sequential_page_columns_content', 10, 2);
        add_filter( 'manage_edit-page_sortable_columns', __CLASS__ . '::WP_Sequential_page_sortable_columns_head' );
        add_action( 'wp_insert_post', __CLASS__ . '::WP_set_sequential_page_id' , 10, 2 );
    }

    /**
    * Add new Column header on backend.
    * @since 0.1.0
    */
    public function WP_Sequential_page_columns_head($defaults) {
        $defaults['page_d'] = 'Page ID';
        return $defaults;

    }

    /**
    * Make the newly added column sortable.
    * @since 0.1.0
    */
    public function WP_Sequential_page_sortable_columns_head( $columns ) {
        $columns['page_d'] = 'Page ID';
        return $columns;
    }

    /**
    * Define the content into new column added.
    * @since 0.1.0
    */
    public function WP_Sequential_page_columns_content($column_name, $post_ID) {
        if ($column_name == 'page_d') {
            $page_number = self::WP_get_current_post_id( $post_ID );
            echo $page_number;
        }      
    }

    /**
    * Get the meta value of created page number, if not exist will return the actual ID.
    * @since 0.1.0
    */
    public function WP_get_current_post_id( $actual_post_id ) {
        $post_id = get_post_meta( $actual_post_id, 'page_id_number',true);
        if( !$post_id  ) {
            return $actual_post_id;
        }
        return $post_id;
    }

    /**
    * Update the Page number into meta on new page added.
    * @since 0.1.0
    */
    public function WP_set_sequential_page_id( $post_id, $post ){
        if ( 'page' === $post->post_type && 'auto-draft' !== $post->post_status){
            global $wpdb;

            $previous_post = $wpdb->get_row("SELECT `ID` FROM bq_posts WHERE ID = 
            (SELECT MAX(ID) FROM bq_posts WHERE ID < 
            (SELECT MAX(ID) FROM bq_posts WHERE post_type='page' AND post_status !='auto-draft') 
            AND post_type='page' AND post_status !='auto-draft') ");

            if( $previous_post->ID ){
                $previous_post_number = get_post_meta( $previous_post->ID,'page_id_number',true);
                if( ! $previous_post_number ){
                    $started_id = $post_id;
                }
            } else {
                $started_id = 1;
            }
            

            // Check meta already updated.
            $get_page_number = get_post_meta($post_id,'page_id_number',true);
            if ( '' === $get_page_number ) {
                   
                   if( !$previous_post_number ){
                       update_post_meta( $post_id,'page_id_number', $post_id );
                   } else {
                       $query = $wpdb->prepare( "
                        INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
                        SELECT %d, 'page_id_number', IF( MAX( CAST( meta_value as UNSIGNED ) ) IS NULL, %d, MAX( CAST( meta_value as UNSIGNED ) ) + 1 )
                            FROM {$wpdb->postmeta}
                            WHERE meta_key='page_id_number'",
                        $post_id,$started_id );

                        $success = $wpdb->query( $query );

                   }

                    
                }
        }

    }

}

WP_sequential_pag_ID::init();

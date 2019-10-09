<?php

class KBX_Logger {
    /**
     * KBX_Logger constructor.
     *
     */
    public function __construct(){
        //check if table exists
    }

    public function log( $args = array() ){
        global $wpdb;

        if( empty($args) )
            return false;

        if( !$this->check_machine_translation_log_table() )
            return false;

        // expected structure.
        $log = array(
            'type'          => $args['type'],
            'kb_id'         => $args['bk_id'],
            'kb_title'      => $args['kb_title'],
            'kb_categories' => $args['kb_categories'],
            'user_id'       => $args['user_id'],
            'user_name'     => $args['user_name'],
            'timestamp'     => date ("Y-m-d H:i:s" )
        );

        $table_name = $wpdb->prefix . 'kbx_logs';

        $query = "INSERT INTO `$table_name` ( `type`, `kb_id`, `kb_title`, `kb_categories`, `user_id`, `user_name`, `timestamp` ) VALUES (%s, %s, %s, %s, %s, %s, %s)";

        $prepared_query = $wpdb->prepare( $query, $log );
        $wpdb->get_results( $prepared_query, OBJECT_K  );

        if ( $wpdb->last_error !== '' )
            return false;

        return true;
    }

    /**
     * Check if table for logs exists.
     *
     * If the table does not exists it is created.
     *
     * @return bool
     */
    private function check_machine_translation_log_table(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'kbx_logs';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name )
        {
            // table not in database. Create new table
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE `{$table_name}`(
                                    id bigint(20) AUTO_INCREMENT NOT NULL PRIMARY KEY,
                                    type text,
                                    kb_id bigint(20),
                                    kb_title text,
                                    kb_categories text,
                                    user_id bigint(20),
                                    user_name text,
                                    timestamp datetime DEFAULT '0000-00-00 00:00:00',                                    
                                    UNIQUE KEY id (id) )
                                     {$charset_collate};";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name )
            {
                // something failed. Table still doesn't exist.
                return false;
            }
            // table exists
            return true;
        }
        //table exists
        return true;
    }

}

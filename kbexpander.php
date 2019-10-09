<?php
/**
 *
 * Plugin Name:       Kbexpander
 * Plugin URI:        https://www.cozmoslabs.com
 * Description:       Companion plugin for Kbexpander text expander tool for Linux
 * Version:           1.0.0
 * Author:            Cristian Antohe
 * Author URI:        https://www.cozmoslabs.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       kbexpander
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Register Custom Post Type and Taxonomy
add_action( 'init', function(){
	$args = array(
		'label'                 => __( 'KB', 'kbexpander' ),
		'description'           => __( 'Kbexpander Snippets', 'kbexpander' ),
		'supports'              => array( 'title', 'editor', 'revisions'),
		'hierarchical'          => true,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 100,
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'show_in_rest'			=> true,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'capability_type'       => 'page',
	);
	register_post_type( 'kb', $args );

		// Add new taxonomy, make it hierarchical (like categories)
	$labels = array(
		'name'              => _x( 'Categories', 'taxonomy general name', 'kbexpander' ),
		'singular_name'     => _x( 'Category', 'taxonomy singular name', 'kbexpander' ),
		'search_items'      => __( 'Search Categories', 'kbexpander' ),
		'all_items'         => __( 'All Categories', 'kbexpander' ),
		'parent_item'       => __( 'Parent Category', 'kbexpander' ),
		'parent_item_colon' => __( 'Parent Category:', 'kbexpander' ),
		'edit_item'         => __( 'Edit Category', 'kbexpander' ),
		'update_item'       => __( 'Update Category', 'kbexpander' ),
		'add_new_item'      => __( 'Add New Category', 'kbexpander' ),
		'new_item_name'     => __( 'New Category Name', 'kbexpander' ),
		'menu_name'         => __( 'Category', 'kbexpander' ),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_in_rest'		=> true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'kbcategory' ),
	);

	register_taxonomy( 'kbcategory', array( 'kb' ), $args );

}, 0 );

// Add unrendered content to the rest api
add_action( 'rest_api_init', function () {
	register_rest_field( 'kb', 'content-unrendered', array(
		'get_callback' => function ($kb_arr){
			$kb_obj = get_post($kb_arr['id']);
			return htmlspecialchars_decode($kb_obj->post_content);
		}
	));

	register_rest_field( 'kb', 'content-categories', array(
        'get_callback' => function ($kb_arr){
            $categories = get_the_terms($kb_arr['id'], 'kbcategory');
            $content = '';
            if($categories === false){
                return $content;
            }
            foreach( $categories as $category) {
                $content .= "#" . $category->name . ' ';
            }
            return $content;
        }
    ));

});

// List all posts in rest api
add_filter( 'rest_kb_query', function($args, $request){
    $max = max( (int) $request->get_param( 'custom_per_page' ), 1000 );
    $args['posts_per_page'] = $max;
    return $args;
}, 10, 2 );

// Disable wyswyg for custom post type, using get_post_type() function
add_filter('user_can_richedit', function( $default ){
  if( get_post_type() === 'kb')  return false;
  return $default;
});

// Hide media buttons and quicktags 
add_action('admin_head', function (){
    global $post;
    if($post->post_type == 'kb' && current_user_can('edit_post') )
    {
        remove_action( 'media_buttons', 'media_buttons' );
  		echo '<style>.quicktags-toolbar{display:none;}</style>';
    }
});

// Logger and Cache
// prevent infinite loop.
global $kb_self_refresh_caching;
$kb_self_refresh_caching = null;
add_filter('rest_pre_dispatch', function( $result, $server, $request ){
    //header("Content-Type: text/html");
    global $kb_self_refresh_caching;
    if ( true == $kb_self_refresh_caching ) {
        return $result;
    }

    $path   = $request->get_route();

    if( strpos($path, '/wp/v2/kb/') !== false ){
        $kb_id = end(explode('/', $path));
        if (is_numeric($kb_id)){
            require_once('class_logger.php');
            $logger = New KBX_Logger();
            $user = kb_get_user();
            $args = array(
                'type'          => 'single',
                'kb_id'         => $kb_id,
                'kb_title'      => get_the_title($kb_id),
                'kb_categories' => kb_get_term_slugs($kb_id),
                'user_id'       => $user['id'],
                'user_name'     => $user['username']
            );
            $logger->log($args);

            $key = 'kb_rest_cache_'. $path;
            if ( false === ( $result = get_transient( $key ) ) ) {
                if ( is_null( $kb_self_refresh_caching ) ) {
                    $kb_self_refresh_caching = true;
                }
                $result  = $server->dispatch( $request );
                set_transient( $key, $result, 48 * HOUR_IN_SECONDS );
            }
        }
    }

    if($path == '/wp/v2/kb'){
        require_once('class_logger.php');
        $logger = New KBX_Logger();
        $user = kb_get_user();
        $args = array(
            'type'          => 'archive',
            'kb_id'         => 0,
            'kb_title'      => '',
            'kb_categories' => '',
            'user_id'       => $user['id'],
            'user_name'     => $user['username']
        );
        $logger->log($args);

        $key = 'kb_rest_cache_'. $path;
        if ( false === ( $result = get_transient( $key ) ) ) {
            if ( is_null( $kb_self_refresh_caching ) ) {
                $kb_self_refresh_caching = true;
            }
            $result  = $server->dispatch( $request );
            set_transient( $key, $result, 48 * HOUR_IN_SECONDS );
        }
    };

    return $result;
}, 10, 3);

add_action('save_post', function($post_id){

    if(get_post_type($post_id) == 'kb'){
        global $wpdb;
        return $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_kb_rest_cache_%'
        ) );
    }
});

function kb_get_user(){
    if(isset($_GET['username'])){
        $user = get_user_by( 'login', $_GET['username'] );
    } else {
        $user = false;
    }

    if ( $user ){
        $user_id = $user->ID;
        $user_name = $user->user_login;
    } else {
        $user_id = 0;
        $user_name = 'NoUserDefined';
    }

    return array('id' => $user_id, 'username' => $user_name);
}

function kb_get_term_slugs($kb_id){
    $terms = get_the_terms( $kb_id, 'kbcategory' );
    if ( !empty( $terms ) ){
        $term_slugs = implode(',', wp_list_pluck($terms, 'slug'));
    } else {
        $term_slugs = '';
    }

    return $term_slugs;
}

//logger and reporting
/*
Logging:

each singular endpoint is tracked individually. => kb / time / user
each all kbs-endpoint is also tracked individually => allbks / time / user

id
type: single / archive
kb_id (only for single - empty for archive)
kb_title (only for single - empty for archive)
user_id
user_name
timestamp
//number_of_key_presses
//number_of_key_presses_saved (ca be negative)

Reporting: 
* per day / month 
* list archive accesses(total/per user) -> graph with access / day / month; per user-> multiple graphs with the user
* most accessed kbs / period-> table
* most accessed kbs / user / period -> table

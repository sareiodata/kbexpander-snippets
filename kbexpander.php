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
		'public'                => false,
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
			return $kb_obj->post_content;
		}
	));

	register_rest_field( 'kb', 'content-categories', array(
        'get_callback' => function ($kb_arr){
            $categories = get_the_terms($kb_arr['id'], 'kbcategory');
            $content = '';
            foreach( $categories as $category) {
                $content .= $category->name . ' | ';
            }
            return $content;
        }
    ));

});

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
// track access to posts

add_filter( 'rest_dispatch_request', function( $result, $request, $route, $handler ){
	//header("Content-Type: text/html");
	//var_dump($route);

	if( $route == '/wp/v2/kb/(?P<id>[\d]+)' ){

		$kb_id = end(explode('/', $_SERVER['REQUEST_URI']));
		//var_dump($kb_id);
		if (is_numeric($kb_id)){
			//$count = get_post_meta( $kb_id, 'kb_counter', true );
			if ( ! $count ) {
			    $count = 0;  // if the meta value isn't set, use 0 as a default
			}
			$count++;
			//update_post_meta( $kb_id, 'kb_counter', $count );

		}
	};

	return $result;
}, 10, 4);

//logger and reporting
/*
Logging:

each singular endpoint is tracked individually. => kb / time / user
each all kbs-endpoint is also tracked individually => allbks / time / user

id
type: single / archive
kb_id (only for single - 0 for archive)
user_id
timestamp
number_of_key_presses
number_of_key_presses_saved (ca be negative)

Reporting: 
* per day / month 
* list archive accesses(total/per user) -> graph with acceses / day / month; per user-> multiple graphs with the user
* most accesed kbs / period-> table
* most accesed kbs / user / period -> table



// add_filter( 'rest_pre_dispatch', function( $result,  $server, $request ){
// 	if( $route == '/wp/v2/kb/(?P<id>[\d]+)' ){
// 		// do stuff.
// 	};
// 	var_dump($request);
// 	die();
// 	return $content;
// }, 10, 4);
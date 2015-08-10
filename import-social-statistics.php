<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Import_Social_Statistics
 *
 * @wordpress-plugin
 * Plugin Name:       Import Social Statistics
 * Plugin URI:        http://www.easantos.net/wordpress/import-social-statistics/
 * Description:       Import your number of Likes and Shares into your WordPress post list.
 * Version:           1.0.0
 * Author:            Easantos
 * Author URI:        http://www.easantos.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       import-social-statistics
 * Domain Path:       /languages
 */

if (!defined('WPINC')) {
    die;
}

add_filter('manage_edit-post_sortable_columns', 'iss_columns_sort');
add_action('the_content', 'iss_content');
add_action('manage_posts_columns', 'iss_columns_head');
add_action('manage_posts_custom_column', 'iss_columns_content');
add_action('admin_menu', 'iss_add_menu_items');
function iss_add_menu_items()
{
    global $wpdb;
    add_menu_page('Import Social Statistics', 'Import Social Statistics', 10, 'iss_main', 'iss_main', plugins_url('images/icon.png', __FILE__));
}

function iss_main()
{
    require_once 'views/main.php';
}

function iss_import_likes()
{
    $posts = get_posts(array('posts_per_page' => '1000'));
    foreach ($posts as $post) :
        $data = @file_get_contents('http://graph.facebook.com/?id=' . get_permalink($post->ID));
        if ($data === FALSE) {
            continue;
        }
        $obj = json_decode($data);
        update_post_meta($post->ID, 'iss_likes', $obj->{'shares'}, false);
    endforeach;
}

function iss_columns_sort($columns)
{
    $columns['iss_likes'] = 'iss_likes';
    return $columns;
}

function iss_sort($vars)
{
    /* Check if we're viewing the 'movie' post type. */
    if (isset($vars['post_type']) && 'post' == $vars['post_type']) {
        /* Check if 'orderby' is set to 'duration'. */
        if (isset($vars['orderby']) && 'iss_likes' == $vars['orderby']) {
            /* Merge the query vars with our custom variables. */
            $vars = array_merge(
                $vars,
                array(
                    'meta_key' => 'iss_likes',
                    'orderby' => 'meta_value_num'
                )
            );
        }
    }
    return $vars;
}

function iss_columns_head($cols)
{
    $cols['iss_likes'] = 'Likes';
    return $cols;
}

function iss_columns_content($name)
{
    global $post;
    switch ($name) {
        case 'iss_likes':
            $likes = get_post_meta($post->ID, 'iss_likes', TRUE);
            echo ($likes ? $likes : '0') . ' <img src="' . plugins_url('images/likes.png', __FILE__) . '">';
            break;
    }
}

function iss_content($content)
{
    $data = @file_get_contents('http://graph.facebook.com/?id=' . get_permalink());
    $json = $data;

    $obj = json_decode($json);
    $likes = $obj->{'shares'};
    update_post_meta(get_the_ID(), 'iss_likes', $likes, false);
    return $content;
}

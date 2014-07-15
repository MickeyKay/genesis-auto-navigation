<?php
/**
 * Genesis Auto Navigation
 *
 * @package   Genesis Auto Navigation
 * @author    Mickey Kay <mickey@mickeykaycreative.com>
 * @license   GPL-2.0+
 * @link      http://wordpress.org/plugins/genesis-auto-navigation
 * @copyright 2014 Mickey Kay
 *
 * @wordpress-plugin
 * Plugin Name: Genesis Auto Navigation
 * Plugin URI: http://wordpress.org/plugins/genesis-auto-navigation
 * Description: Auto-generate your navigation menus in Genesis
 * Version: 0.1.0
 * Author: Mickey Kay
 * Author URI: mickey@mickeykaycreative.com
 * License:     GPLv2+
 * Text Domain: gan
 * Domain Path: /languages
 */

/**
 * @todo Add support for custom menu widget placement (error right now).
 * @todo Add filters as needed.
 * @todo Move everything into a plugin class and separate out files (e.g. walker class).
 * @todo Add better commenting to reference original functions and functionality.
 */

define( 'GAN_MENU_NAME', __( 'Genesis Auto Navigation', 'gan' ) );

add_action( 'after_setup_theme', 'gan_create_auto_menu' );
/**
 * Register Genesis Auto Navigation menu.
 *
 * @since 1.0.0
 *
 * @return null Returns early if no Genesis menus are supported.
 */
function gan_create_auto_menu() {

	if ( ! current_theme_supports( 'genesis-menus' ) )
		return;

	wp_create_nav_menu( GAN_MENU_NAME );

}

add_filter( 'pre_wp_nav_menu', 'gan_test', 10, 2 );
function gan_test( $output, $args ) {

	$theme_locations = get_nav_menu_locations();
	$menu_obj = get_term( $theme_locations[ $args->theme_location ], 'nav_menu' );
	$menu_name = $menu_obj->name;
	if ( GAN_MENU_NAME == $menu_name ) {
		$args->walker = new Gan_Walker_Nav_Menu();
		$args->show_home = true;
		$args = (array) $args;

		$output = gan_page_menu( $args );
	}
	
	return $output;
}

/**
 * Display or retrieve list of pages with optional home link.
 *
 * The arguments are listed below and part of the arguments are for {@link
 * wp_list_pages()} function. Check that function for more info on those
 * arguments.
 *
 * <ul>
 * <li><strong>sort_column</strong> - How to sort the list of pages. Defaults
 * to 'menu_order, post_title'. Use column for posts table.</li>
 * <li><strong>menu_class</strong> - Class to use for the div ID which contains
 * the page list. Defaults to 'menu'.</li>
 * <li><strong>echo</strong> - Whether to echo list or return it. Defaults to
 * echo.</li>
 * <li><strong>link_before</strong> - Text before show_home argument text.</li>
 * <li><strong>link_after</strong> - Text after show_home argument text.</li>
 * <li><strong>show_home</strong> - If you set this argument, then it will
 * display the link to the home page. The show_home argument really just needs
 * to be set to the value of the text of the link.</li>
 * </ul>
 *
 * @since 2.7.0
 *
 * @param array|string $args
 * @return string html menu
 */
function gan_page_menu( $args = array() ) {
	$defaults = array('sort_column' => 'menu_order, post_title', 'menu_class' => 'menu', 'echo' => true, 'link_before' => '', 'link_after' => '');
	$args = wp_parse_args( $args, $defaults );

	/**
	 * Filter the arguments used to generate a page-based menu.
	 *
	 * @since 2.7.0
	 *
	 * @see wp_page_menu()
	 *
	 * @param array $args An array of page menu arguments.
	 */
	$args = apply_filters( 'wp_page_menu_args', $args );

	$menu = '';

	$list_args = $args;

	// Show Home in the menu
	if ( ! empty($args['show_home']) ) {

		if ( true === $args['show_home'] || '1' === $args['show_home'] || 1 === $args['show_home'] ) {
			$text = __( 'Home', 'genesis-auto-nav');
		} else {
			$text = $args['show_home'];
		}

		$class = 'menu-item';

		if ( is_front_page() && !is_paged() ) {
			$class .= ' current-menu-item';
		}

		$menu .= '<li class="' . $class . '"><a href="' . home_url( '/' ) . '">' . $args['link_before'] . $text . $args['link_after'] . '</a></li>';

		// If the front page is a page, add it to the exclude list
		if (get_option('show_on_front') == 'page') {
			if ( !empty( $list_args['exclude'] ) ) {
				$list_args['exclude'] .= ',';
			} else {
				$list_args['exclude'] = '';
			}
			$list_args['exclude'] .= get_option('page_on_front');
		}
	}

	$list_args['echo'] = false;
	$list_args['title_li'] = '';
	$menu .= str_replace( array( "\r", "\n", "\t" ), '', wp_list_pages($list_args) );

	if ( $menu )
		$menu = '<ul>' . $menu . '</ul>';

	$menu = '<div class="' . esc_attr($args['menu_class']) . '">' . $menu . "</div>\n";

	/**
	 * Filter the HTML output of a page-based menu.
	 *
	 * @since 2.7.0
	 *
	 * @see wp_page_menu()
	 *
	 * @param string $menu The HTML output.
	 * @param array  $args An array of arguments.
	 */
	$menu = apply_filters( 'wp_page_menu', $menu, $args );
	if ( $args['echo'] )
		echo $menu;
	else
		return $menu;
}


/**
 * Create HTML list of nav menu items.
 *
 * @since 1.0.0
 * @uses Walker
 */
class Gan_Walker_Nav_Menu extends Walker {
	/**
	 * @see Walker::$tree_type
	 * @since 2.1.0
	 * @var string
	 */
	var $tree_type = 'page';

	/**
	 * @see Walker::$db_fields
	 * @since 2.1.0
	 * @todo Decouple this.
	 * @var array
	 */
	var $db_fields = array ('parent' => 'post_parent', 'id' => 'ID');

	/**
	 * @see Walker::start_lvl()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 * @param array $args
	 */
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class='sub-menu'>\n";
	}

	/**
	 * @see Walker::end_lvl()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 * @param array $args
	 */
	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	/**
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page Page data object.
	 * @param int $depth Depth of page. Used for padding.
	 * @param int $current_page Page ID.
	 * @param array $args
	 */
	function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
		if ( $depth )
			$indent = str_repeat("\t", $depth);
		else
			$indent = '';

		extract($args, EXTR_SKIP);
		$css_class = array('menu-item', 'menu-item-'.$page->ID);

		if( isset( $args['pages_with_children'][ $page->ID ] ) )
			$css_class[] = 'menu_item_has_children';

		if ( !empty($current_page) ) {
			$_current_page = get_post( $current_page );
			if ( in_array( $page->ID, $_current_page->ancestors ) )
				$css_class[] = 'current-menu-ancestor';
			if ( $page->ID == $current_page )
				$css_class[] = 'current-menu-item';
			elseif ( $_current_page && $page->ID == $_current_page->post_parent )
				$css_class[] = 'current-menu-parent';
		} elseif ( $page->ID == get_option('page_for_posts') ) {
			$css_class[] = 'current-menu-parent';
		}

		/**
		 * Filter the list of CSS classes to include with each page item in the list.
		 *
		 * @since 2.8.0
		 *
		 * @see wp_list_pages()
		 *
		 * @param array   $css_class    An array of CSS classes to be applied
		 *                             to each list item.
		 * @param WP_Post $page         Page data object.
		 * @param int     $depth        Depth of page, used for padding.
		 * @param array   $args         An array of arguments.
		 * @param int     $current_page ID of the current page.
		 */
		$css_class = implode( ' ', apply_filters( 'page_css_class', $css_class, $page, $depth, $args, $current_page ) );

		if ( '' === $page->post_title )
			$page->post_title = sprintf( __( '#%d (no title)' ), $page->ID );

		/** This filter is documented in wp-includes/post-template.php */
		$output .= $indent . '<li class="' . $css_class . '"><a href="' . get_permalink($page->ID) . '">' . $link_before . apply_filters( 'the_title', $page->post_title, $page->ID ) . $link_after . '</a>';

		if ( !empty($show_date) ) {
			if ( 'modified' == $show_date )
				$time = $page->post_modified;
			else
				$time = $page->post_date;

			$output .= " " . mysql2date($date_format, $time);
		}
	}

	/**
	 * @see Walker::end_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page Page data object. Not used.
	 * @param int $depth Depth of page. Not Used.
	 * @param array $args
	 */
	function end_el( &$output, $page, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}

}
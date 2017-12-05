<?php
/**
 * Plugin Name: bbPress Dash
 * Plugin URI:  https://www.coreymcollins.com
 * Description: Adding Dashboard functionality to bbPress.
 * Version:     1.0.0
 * Author:      Corey M Collins
 * Author URI:  https://www.coreymcollins.com
 * Donate link: https://www.coreymcollins.com
 * License:     GPLv2
 * Text Domain: bbpress-dash
 * Domain Path: /languages
 *
 * @link    https://www.coreymcollins.com
 *
 * @package BbPress_Dash
 * @version 1.0.0
 *
 * Built using generator-plugin-wp (https://github.com/WebDevStudios/generator-plugin-wp)
 */

/**
 * Copyright (c) 2017 Corey M Collins (email : coreymcollins@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Autoloads files with classes when needed.
 *
 * @since  1.0.0
 * @param  string $class_name Name of the class being requested.
 */
function bbpress_dash_autoload_classes( $class_name ) {

	// If our class doesn't have our prefix, don't load it.
	if ( 0 !== strpos( $class_name, 'BPD_' ) ) {
		return;
	}

	// Set up our filename.
	$filename = strtolower( str_replace( '_', '-', substr( $class_name, strlen( 'BPD_' ) ) ) );

	// Include our file.
	BbPress_Dash::include_file( 'includes/class-' . $filename );
}
spl_autoload_register( 'bbpress_dash_autoload_classes' );

/**
 * Main initiation class.
 *
 * @since  1.0.0
 */
final class BbPress_Dash {

	/**
	 * Current version.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	const VERSION = '1.0.0';

	/**
	 * URL of plugin directory.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $basename = '';

	/**
	 * Detailed activation error messages.
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $activation_errors = array();

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    BbPress_Dash
	 * @since  1.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   1.0.0
	 * @return  BbPress_Dash A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since  1.0.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  1.0.0
	 */
	public function plugin_classes() {
		// $this->plugin_class = new BPD_Plugin_Class( $this );

	} // END OF PLUGIN CLASSES FUNCTION

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 *
	 * @since  1.0.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );

		// Dequeue BBP Scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 999 );

		// Removes posts from other authors in the dashboard.
		add_action( 'pre_get_posts', array( $this, 'remove_posts_from_other_users_in_dashboard' ) );

		// Adds user role capabilities.
		add_filter( 'bbp_get_caps_for_role', array( $this, 'add_bbpdash_caps' ), 10, 2 );

		// Filter our single topic to display preview content.
		add_action( 'bbp_get_single_topic_description', array( $this, 'get_preview_content' ), 999 );

		add_filter( 'views_edit-topic', array( $this, 'quicklinks_post_counts' ) );
	}

	/**
	 * Adjusts the output of Quck Links in the dashboard.
	 *
	 * @param  array $views Array of our default $views.
	 * @return array        Updated array of our $views.
	 * @author Corey Collins
	 */
	public function quicklinks_post_counts( $views ) {

		// If we have no views, return nothing.
		if ( empty ( $views ) ) {
			return '';
		}

		// If the user is an admin, return our normal views.
		if ( current_user_can( 'manage_options' ) ) {
			return $views;
		}

		// Loop through each of our views.
		foreach ( $views as $class => $view ) {

			// Query for each post status.
			$query = array(
	            'author'      => get_current_user_id(),
	            'post_type'   => 'topic',
	            'post_status' => $class,
	            'fields'      => 'ids',
	        );

	        $results = new WP_Query( $query );

	        // Grab our post count.
	        $post_count = $results->post_count;

	        // Replace the existing post count in parentheses with our user-specific post count.
	        $views[$class] = preg_replace( "/\([^)]*\)/", "($post_count)", $views[$class] );
		}

		return $views;
	}

	/**
	 * Adjust the topic description to include our preview content.
	 *
	 * @param  mixed $content HTML output of the default description.
	 * @return mixed          The adjusted output of our description.
	 * @author Corey Collins
	 */
	public function get_preview_content( $args = array() ) {

		// Bail if we're not on a single topic.
		if ( ! is_singular( 'topic' ) ) {
			return '';
		}

		// Also bail if we're not on a preview.
		if ( ! is_preview() ) {
			return '';
		}

		bbp_get_template_part( 'content', 'single-topic-lead' );
	}

	/**
	 * Activate the plugin.
	 *
	 * @since  1.0.0
	 */
	public function _activate() {

		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin.
	 * Uninstall routines should be in uninstall.php.
	 *
	 * @since  1.0.0
	 */
	public function _deactivate() {
		// Add deactivation cleanup functionality here.
	}

	/**
	 * Init hooks
	 *
	 * @since  1.0.0
	 */
	public function init() {

		// Load translated strings for plugin.
		load_plugin_textdomain( 'bbpress-dash', false, dirname( $this->basename ) . '/languages/' );

		// Initialize plugin classes.
		$this->plugin_classes();
	}

	/**
	 * Dequeues the BP script that hides the Draft button.
	 *
	 * @author Corey Collins
	 */
	public function enqueue_scripts() {

		// Get the current screen.
		$screen = get_current_screen();


		// Bail if current screen fails.
		if ( ! isset ( $screen ) ) {
			return '';
		}

		// Bail if not on the single topic page.
		if ( 'post' !== $screen->base || 'topic' !== $screen->post_type ) {
			return '';
		}

		// If WP is in script debug, or we pass ?script_debug in a URL - set debug to true.
		$debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG == true ) || ( isset( $_GET['script_debug'] ) ) ? true : false;

		// If we are debugging the site, use a unique version every page load so as to ensure no cache issues.
		$version = '1.0.0';

		// Should we load minified files?
		$suffix = ( true === $debug ) ? '' : '.min';

		// Dequeue the BP script which hides the Save Draft button.
		wp_dequeue_script( 'bbp-admin-topics-js' );

		// Enqueue our script to partially disable our Preview button until a Forum is selected.
		wp_enqueue_script( 'bbp-dash', $this->url . 'assets/scripts/bbp-dash' . $suffix . '.js', array( 'jquery' ), $version, true );
	}

	/**
	 * Removes posts from other users in the Topics and Replies dashboard, unless admin.
	 *
	 * @param  object $query The Query for posts in our CPT.
	 * @author Corey Collins
	 */
	public function remove_posts_from_other_users_in_dashboard( $query ) {

		// If we're not in the dashboard. bail.
		if ( ! is_admin() ) {
			return;
		}

		// If we're an admin, bail.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Find our current page.
		$screen = get_current_screen();

		// If no screen option is set, return.
		if ( ! isset( $screen->id ) ) {
			return;
		}

		// If we're not on the Topic or Replies archive, bail.
		if ( 'edit-topic' !== $screen->id && 'edit-reply' !== $screen->id ) {
			return;
		}

		// Get the post type from the query.
		$post_type = $query->query['post_type'];

		// If the CPT isn't Warriors, bail.
		if ( 'topic' !== $post_type && 'reply' !== $post_type ) {
			return;
		}

		// Grab the logged in user's ID>
		$user_id = get_current_user_id();

		// Adjust the query.
		$query->set( 'author', $user_id );
	}

	/**
	 * Adds custom capabilities for bbPress roles.
	 *
	 * @author Corey Collins
	 */
	function add_bbpdash_caps( $caps, $role ) {

		if ( 'bbp_participant' === $role ) {
	        $caps = $this->custom_capabilities( $role );
		}

	    return $caps;
	}

	/**
	 * Set custom capabilities per role.
	 *
	 * @param  string $role The current user role.
	 * @return string       The user role.
	 * @author Corey Collins
	 */
	function custom_capabilities( $role ) {

	    switch ( $role ) {

	        // The participant role.
	        case 'bbp_participant':
	            return array(
	                // Primary caps
	                'spectate'              => true,
	                'participate'           => true,
	                'moderate'              => true,

	                // Topic caps
	                'publish_topics'        => true,
	                'edit_topics'           => true,
	                'edit_others_topics'    => true,
	                'delete_topics'         => true,
	                'delete_others_topics'  => false,
	                'read_private_topics'   => false,

	                // Reply caps
	                'publish_replies'       => true,
	                'edit_replies'          => true,
	                'edit_others_replies'   => true,
	                'delete_replies'        => true,
	                'delete_others_replies' => false,
	                'read_private_replies'  => false,

	                // Topic tag caps
	                'manage_topic_tags'     => false,
	                'edit_topic_tags'       => false,
	                'delete_topic_tags'     => false,
	                'assign_topic_tags'     => true,
	            );

	            break;

	        default :
	            return $role;
	    }
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  1.0.0
	 */
	public function deactivate_me() {

		// We do a check for deactivate_plugins before calling it, to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $field Field to get.
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}

	/**
	 * Include a file from the includes directory.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $filename Name of the file to be included.
	 * @return boolean          Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( $filename . '.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
		return false;
	}

	/**
	 * This plugin's directory.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       Directory and path.
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's url.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       URL and path.
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}
}

/**
 * Grab the BbPress_Dash object and return it.
 * Wrapper for BbPress_Dash::get_instance().
 *
 * @since  1.0.0
 * @return BbPress_Dash  Singleton instance of plugin class.
 */
function bbpress_dash() {
	return BbPress_Dash::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( bbpress_dash(), 'hooks' ) );

// Activation and deactivation.
register_activation_hook( __FILE__, array( bbpress_dash(), '_activate' ) );
register_deactivation_hook( __FILE__, array( bbpress_dash(), '_deactivate' ) );

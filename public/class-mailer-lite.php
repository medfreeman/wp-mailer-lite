<?php
/**
 * Mailer_Lite
 *
 * @package   Mailer_Lite
 * @author    Mehdi Lahlou <mehdi.lahlou@free.fr>
 * @license   GPL-2.0+
 * @link      http://www.mappingfestival.com
 * @copyright 2014 Mehdi Lahlou
 */

/**
 * Mailer_Lite class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-mailer-lite-admin.php`
 *
 * @package Mailer_Lite
 * @author  Mehdi Lahlou <mehdi.lahlou@free.fr>
 */
 
 // Initiate mailer lite api
if ( ! class_exists( 'Mailer_Lite_Api' ) )
	require_once( dirname( __FILE__ ) . '/../includes/class-mailer-lite-api.php' );
 
class Mailer_Lite {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'mailer-lite';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;
	
	private $api = null;
	
	private $options = null;
	
	private $shortcode_increment = null;
	
	private $forms_states = null;
	
	private $ajax_handler = 'mailerlite_submit_form';
	
	private $default_options = array(
		'api_key' => '',
		'load_default_styles' => true,
		'loading_body_class' => ''
	);

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		
		$this->api = Mailer_Lite_Api::get_instance();
		$this->options = get_option( 'Mailer_Lite_Admin', $this->default_options );
		$this->shortcode_increment = 0;
		$this->forms_states = array();

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		add_shortcode( 'mailerlite', array( $this, 'shortcode' ) );
		
		if ( !is_admin() || !defined('DOING_AJAX') ) {
			add_action( 'init', array( $this, 'standard_process_form' ) );
		}
		add_action( 'wp_ajax_' . $this->ajax_handler, array( $this, 'ajax_process_form' ) );
		add_action( 'wp_ajax_nopriv_' . $this->ajax_handler, array( $this, 'ajax_process_form' ) );
	}
	
	public function get_default_options() {
		return $this->default_options;
	}
	
	public function get_options() {
		return $this->options;
	}
	
	public function shortcode( $atts ) {
		extract( shortcode_atts( array(
			'id' => false,
			'unsubscribe_checkbox' => true
		), $atts ) );
		
		if ( $id == false || !is_numeric($id) ) {
			return '<p>' . __('You must set a list id.', $this->plugin_slug) . '</p>';
		}
		$list_id = intval($id);
		
		if ( !isset( $this->options['api_key'] ) || !$this->options['api_key'] ) {
			return '<p>' . __('Please enter API key.', $this->plugin_slug) . '</p>';
		}
		
		$list = $this->api->get_list_info($this->options['api_key'], $list_id);
		if ( isset($list['error']) ) {
			return '<p>' . __('Error :', $this->plugin_slug) . ' ' . $list['error']['code'] . ' / ' . $list['error']['message'] . '</p>';
		}
		
		$list_nonce = wp_create_nonce( $this->plugin_slug . '-' . $list_id  );
		
		$highlights = isset( $this->forms_states[$list_id]['highlight_fields'] ) ? $this->forms_states[$list_id]['highlight_fields'] : array();
		
		$this->shortcode_increment++;
		
		ob_start();
		include( dirname( __FILE__ ) . '/views/public.php' );
		if( is_admin() && defined('DOING_AJAX') && DOING_AJAX ) {
			echo '<script type="text/javascript">';
			include( dirname( __FILE__ ) . '/assets/js/public.js' );
			echo '</script>';
		}
		return ob_get_clean();
	}
	
	public function standard_process_form() {
		$this->load_forms_states();
		if ( $this->process_form() ) {
			$this->save_forms_states_and_redirect();
		}
	}
	
	public function ajax_process_form() {
		$form_id = $this->process_form();
		
		header('Content-Type: application/json');
		echo json_encode( array( 'messages' => $this->get_messages_html( $form_id ) ) );
		exit;
	}
	
	public function process_form() {
		if ( !isset( $_POST['mailerlite_action'] ) || !$_POST['mailerlite_action'] ) {
			return false;
		}
			
		if ( !isset( $_POST['mailerlite_list_id'] ) || empty( $_POST['mailerlite_list_id'] ) || !is_numeric( $_POST['mailerlite_list_id'] ) ) {
			return false;
		}
		$list_id = intval($_POST['mailerlite_list_id']);
		
		$this->clear_form_state( $list_id );
			
		if ( !isset( $this->options['api_key'] ) || !$this->options['api_key'] ) {
			$this->add_message( $list_id, 'error', 'Configuration error.' );
			return $list_id;
		}
			
		if ( !wp_verify_nonce( $_POST['mailerlite_nonce'], $this->plugin_slug . '-' . $list_id ) ) {
			$this->add_message( $list_id, 'error', 'Security error.' );
			return $list_id;
		}
			
		if ( !isset( $_POST['mailerlite_email'] ) || empty( $_POST['mailerlite_email'] ) || !filter_var( $_POST['mailerlite_email'], FILTER_VALIDATE_EMAIL ) ) {
			$this->add_message( $list_id, 'error', 'Invalid email address.' );
			$this->highlight_field( $list_id, 'email' );
			return $list_id;
		}
		$email = $_POST['mailerlite_email'];
			
			
		if( $_POST['mailerlite_action'] == 'subscribe' ) {
				
			$subscriber = $this->api->get_subscriber_details( $this->options['api_key'], $email );
				
			if( isset( $subscriber['error'] ) ) {
				if ( $subscriber['error']['code'] != 404 ) { //404 = subscriber doesn't exist - np
					$this->add_message( $list_id, 'error', 'Temporary error. Please try again later.' );
					return $list_id;
				}
			} else {
				if ( isset( $subscriber['groups'] ) && !empty( $subscriber['groups'] ) ) {
					foreach( $subscriber['groups'] as $group ) {
						if ( $group['id'] == $list_id ) {
							//subscriber already in this list
							$this->add_message( $list_id, 'success', 'You have already subscribed to our newsletter ! Thank you again.' );
							return $list_id;
						}
					}
				}
			}
				
			//wp_die(print_r($subscriber));
				
			$subscriber = $this->api->add_subscriber( $this->options['api_key'], $list_id, $email );
				
			if( isset( $subscriber['error'] ) ) {
				$this->add_message( $list_id, 'error', 'Temporary error. Please try again later.' );
				return $list_id;
			} else if ( $subscriber == $email ) {
				$this->add_message( $list_id, 'success', 'You have subscribed to our newsletter ! Thank you.' );
				return $list_id;
			} else {
				$this->add_message( $list_id, 'error', 'Temporary error. Please try again later.' );
				return $list_id;
			}
			
		} elseif ( $_POST['mailerlite_action'] == 'unsubscribe' ) {
				
			$subscriber = $this->api->get_subscriber_details( $this->options['api_key'], $email );
				
			if( isset( $subscriber['error'] ) ) {
				if ( $subscriber['error']['code'] == 404 ) {
					$this->add_message( $list_id, 'error', 'You have not subscribed to this newsletter. I can\'t unsubscribe you.' );
					return $list_id;
				} else {
					$this->add_message( $list_id, 'error', 'Temporary error. Please try again later.' );
					return $list_id;
				}
			} else {
				if ( isset( $subscriber['groups'] ) && !empty( $subscriber['groups'] ) ) {
					$subscriber_is_in_group = false;
					foreach( $subscriber['groups'] as $group ) {
						if ( $group['id'] == $list_id ) {
							//subscriber is in this list
							$subscriber_is_in_group = true;
						}
					}
					if ( !$subscriber_is_in_group ) {
						//subscriber not in list
						$this->add_message( $list_id, 'error', 'You have not subscribed to this newsletter. I can\'t unsubscribe you.' );
						return $list_id;
					}
				}
			}
								
			$subscriber = $this->api->remove_subscriber( $this->options['api_key'], $list_id, $email );
				
			if( isset( $subscriber['error'] ) ) {
				$this->add_message( $list_id, 'error', 'Temporary error. Please try again later.' );
				return $list_id;
			} else if ( $subscriber == $email ) {
				$this->add_message( $list_id, 'success', 'You have unsubscribed from our newsletter !' );
				return $list_id;
			} else {
				$this->add_message( $list_id, 'error', 'Temporary error. Please try again later.' );
				return $list_id;
			}
		}
	}
	
	private function load_forms_states() {		
		if ( !session_id() ) {
			session_start();
		}
		
		if ( isset( $_SESSION[$this->plugin_slug] ) ) {
			$this->forms_states = $_SESSION[$this->plugin_slug];
		}
	}
	
	private function save_forms_states_and_redirect() {		
		if ( !session_id() ) {
			session_start();
		}
		
		$_SESSION[$this->plugin_slug] = $this->forms_states;
		
		//PRG design pattern - redirect
		$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://';
		wp_redirect( $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		exit;
	}
	
	private function clear_form_state( $form_id ) {
		if ( isset( $this->forms_states[$form_id] ) ) {
			$this->forms_states[$form_id] = array();
		}
	}
	
	private function add_message( $form_id, $type, $message  ) {
		if ( !isset( $this->forms_states[$form_id] ) ) {
			$this->forms_states[$form_id] = array( 'messages' => array(), 'highlight_fields' => array() );
		}
		array_push( $this->forms_states[$form_id]['messages'], array( 'type' => $type, 'message' => $message ) );
	}
	
	private function highlight_field( $form_id, $field_name, $message = '' ) {
		if ( !isset( $this->forms_states[$form_id] ) ) {
			$this->forms_states[$form_id] = array( 'messages' => array(), 'highlight_fields' => array() );
		}
		$this->forms_states[$form_id]['highlight_fields']['mailerlite_' . $field_name] = $message;
	}
	
	private function get_messages_html( $form_id ) {
		$html = '';
		if ( isset( $this->forms_states[$form_id]['messages'] ) ) {
			foreach( $this->forms_states[$form_id]['messages'] as $message ) {
				$html .= '<p class="' . $message['type'] . '">' . __( $message['message'], $this->plugin_slug ) . '</p>';
			}
		}
		return $html;
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		if ( $this->options['load_default_styles'] ) {
			wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
		}
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-validate', plugins_url( 'assets/js/jquery.validate.min.js', __FILE__ ), array( 'jquery' ), '1.11.1' );
		wp_enqueue_script( 'jquery-validate-fr_FR', plugins_url( 'assets/js/jquery.validate.translations.fr-FR.js', __FILE__ ), array( 'jquery', 'jquery-validate' ), '1.11.1' );
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery', 'jquery-validate', 'jquery-form', 'jquery-validate-fr_FR' ), self::VERSION );
		wp_localize_script( $this->plugin_slug . '-plugin-script', 'mailerLite', array( 'ajaxHandler' => $this->ajax_handler, 'ajaxUrl' => admin_url( 'admin-ajax.php', strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],':'))) ), 'errorMessage' => '<p>' . __('Temporary error. Please try again later.' , $this->plugin_slug ) . '</p>', 'loadingClass' => $this->options['loading_body_class'] ) );
	}

}

<?php
/**
 * Mailer_Lite
 *
 * @package   Mailer_Lite_Admin
 * @author    Mehdi Lahlou <mehdi.lahlou@free.fr>
 * @license   GPL-2.0+
 * @link      http://www.mappingfestival.com
 * @copyright 2014 Mehdi Lahlou
 */

/**
 * Mailer_Lite_Admin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @package Mailer_Lite_Admin
 * @author  Mehdi Lahlou <mehdi.lahlou@free.fr>
 */

// Initiate admin page options framework (https://github.com/michaeluno/admin-page-framework)
if ( ! class_exists( 'AdminPageFramework' ) )
	require_once( dirname( __FILE__ ) . '/includes/admin-page-framework.min.php' );
	
// Initiate mailer lite api
if ( ! class_exists( 'Mailer_Lite_Api' ) )
	require_once( dirname( __FILE__ ) . '/../includes/class-mailer-lite-api.php' );
 
class Mailer_Lite_Admin extends AdminPageFramework {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;
	
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
	protected $plugin_slug = null;
	
	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;
	
	protected $class_name = null;
	
	private $api = null;
	
	private $options = null;
	
	private $default_options = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	public function __construct() {
		
		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */
		
		self::$instance = $this;
		parent::__construct();

		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = Mailer_Lite::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$this->class_name = get_class($this);
		$this->default_options = $plugin->get_default_options();
		$this->options = $plugin->get_options();
		
		$this->api = Mailer_Lite_Api::get_instance();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

	}
	
	public function setUp() {

        $this->setRootMenuPage( 'Settings' );   // where to belong
        $this->addSubMenuItem(
            array(
                'title' => __( 'Mailer Lite', $this->plugin_slug ),
                'page_slug' => 'mailerLite'
            )
        );
        $this->plugin_screen_hook_suffix = 'settings_page_mailerLite';
		
		// Add form fields
        $this->addSettingFields(
            array(
                'field_id' => 'api_key',
                'type' => 'text',
                'title' => __( 'Mailer Lite API Key', $this->plugin_slug ),
                'description' => __( 'API key you can find in Developer API section in MailerLite (in site footer).', $this->plugin_slug ),
                'default' => $this->default_options['api_key']
            ),
            array(
                'field_id' => 'load_default_styles',
                'type' => 'checkbox',
                'title' => __( 'Load default css styles', $this->plugin_slug ),
                'description' => __( 'Disable to implement your own styles (you can copy the contents of wp-content/plugins/mailer-lite/public/assets/css/public.css in your own stylesheet and modify them).', $this->plugin_slug ),
				'default'	=>	$this->default_options['load_default_styles']
            ),
            array(
                'field_id' => 'loading_body_class',
                'type' => 'text',
                'title' => __( 'Loading body class', $this->plugin_slug ),
                'description' => __( 'Class added to the body element when the form is being submitted (Allows adding a loading sign for example).', $this->plugin_slug ),
				'default'	=>	$this->default_options['loading_body_class']
            )
        );
    }

    public function do_mailerLite() {  // do_{page slug}
		include_once( 'views/admin.php' );
    }

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Mailer_Lite::VERSION );
		}

	}

}

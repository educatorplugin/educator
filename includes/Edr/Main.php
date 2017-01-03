<?php

class Edr_Main {
	/**
	 * @var Edr_Main
	 */
	protected static $instance = null;

	/**
	 * @var array
	 */
	protected $gateways = array();

	/**
	 * Get instance of this class.
	 *
	 * @param string $file Path to educator.php
	 * @return Edr_Main
	 */
	public static function get_instance( $file = null ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $file );
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 * Setup core hooks.
	 */
	protected function __construct( $file ) {
		$this->includes();

		register_activation_hook( $file, array( $this, 'plugin_activation' ) );
		register_deactivation_hook( $file, array( $this, 'plugin_deactivation' ) );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init_gateways' ) );
		add_action( 'init', array( $this, 'add_rewrite_endpoints' ), 8 ); // Run before the plugin update.
		add_action( 'init', array( $this, 'process_actions' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_action( 'after_setup_theme', array( $this, 'require_template_functions' ) );
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		if ( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {
			require EDR_PLUGIN_DIR . 'includes/template-hooks.php';
		}
	}

	protected function includes() {
		require EDR_PLUGIN_DIR . 'includes/formatting.php';
		require EDR_PLUGIN_DIR . 'includes/functions.php';
		require EDR_PLUGIN_DIR . 'includes/shortcodes.php';
		require EDR_PLUGIN_DIR . 'includes/filters.php';

		Edr_EntryMeta::get_instance();

		// Setup the memberships feature.
		Edr_MembershipsRun::init();

		// Setup the post types and taxonomies.
		Edr_PostTypes::init();

		// Ajax action processing methods.
		Edr_AjaxActions::init();

		// Setup account processing (e.g. payment form).
		Edr_StudentAccount::init();

		// Parse incoming requests (e.g. PayPal IPN).
		Edr_RequestDispatcher::init();

		if ( is_admin() ) {
			Edr_Admin::init();
		}
	}

	/**
	 * Get gateways.
	 *
	 * @return array
	 */
	public function get_gateways() {
		return $this->gateways;
	}

	/**
	 * Process plugin activation.
	 */
	public function plugin_activation() {
		$install = new Edr_Install();
		$install->activate();
	}

	/**
	 * Process plugin deactivation.
	 */
	public function plugin_deactivation() {
		$install = new Edr_Install();
		$install->deactivate();
	}

	/**
	 * Load plugin's textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'educator', false, 'educator/languages' );
	}

	/**
	 * Initialize gateways.
	 */
	public function init_gateways() {
		$gateways = apply_filters( 'edr_payment_gateways', array(
			'paypal'        => array( 'class' => 'Edr_Gateway_Paypal' ),
			'cash'          => array( 'class' => 'Edr_Gateway_Cash' ),
			'check'         => array( 'class' => 'Edr_Gateway_Check' ),
			'bank-transfer' => array( 'class' => 'Edr_Gateway_BankTransfer' ),
			'free'          => array( 'class' => 'Edr_Gateway_Free' ),
			'stripe'        => array( 'class' => 'Edr_Gateway_Stripe' ),
		) );

		// Get the list of enabled gateways.
		$enabled_gateways = null;

		if ( ! is_admin() ) {
			$gateways_options = get_option( 'edr_payment_gateways', array() );
			$enabled_gateways = array( 'free' );

			foreach ( $gateways_options as $gateway_id => $options ) {
				if ( isset( $options['enabled'] ) && 1 == $options['enabled'] ) {
					$enabled_gateways[] = $gateway_id;
				}
			}

			$enabled_gateways = apply_filters( 'edr_enabled_gateways', $enabled_gateways );
		}

		foreach ( $gateways as $gateway_id => $gateway ) {
			if ( null !== $enabled_gateways && ! in_array( $gateway_id, $enabled_gateways ) ) {
				continue;
			}

			if ( isset( $gateway['file'] ) && is_readable( $gateway['file'] ) ) {
				require_once $gateway['file'];
			}

			$gateway_obj = new $gateway['class']();
			$this->gateways[ $gateway_obj->get_id() ] = $gateway_obj;
		}
	}

	/**
	 * Add endpoints.
	 */
	public function add_rewrite_endpoints() {
		// Used to pass course or membership ID to the payment page.
		add_rewrite_endpoint( 'edr-object', EP_PAGES );

		// If a payment gateway needs to add the second step to the payment process,
		// it can use this endpoint to specify step 2 and pass payment ID to the payment page.
		add_rewrite_endpoint( 'edr-pay', EP_PAGES );

		// Used to pass payment ID to the payment page.
		add_rewrite_endpoint( 'edr-payment', EP_PAGES );

		// Used to pass display various messages on the page (e.g., a quiz has been submitted).
		add_rewrite_endpoint( 'edr-message', EP_PAGES | EP_PERMALINK );

		// Used to process an external request (like PayPal IPN response)
		add_rewrite_endpoint( 'edr-request', EP_ROOT );
	}

	/**
	 * Process various actions (e.g., payment, quiz submission, etc).
	 */
	public function process_actions() {
		if ( ! isset( $_GET['edr-action'] ) ) {
			return;
		}

		switch ( $_GET['edr-action'] ) {
			case 'cancel-payment':
				Edr_FrontActions::cancel_payment();
				break;

			case 'submit-quiz':
				Edr_FrontActions::submit_quiz();
				break;

			case 'payment':
				Edr_FrontActions::payment();
				break;

			case 'join':
				Edr_FrontActions::join();
				break;

			case 'resume-entry':
				Edr_FrontActions::resume_entry();
				break;

			case 'quiz-file-download':
				Edr_FrontActions::quiz_file_download();
				break;
		}
	}

	/**
	 * Enqueue front-end scripts and styles.
	 */
	public function enqueue_scripts_styles() {
		if ( apply_filters( 'edr_stylesheet', true ) ) {
			wp_enqueue_style( 'edr-base', EDR_PLUGIN_URL . 'assets/public/css/base.css', array(), '2.0.3' );
		}

		if ( edr_is_page( 'payment' ) ) {
			// Scripts for the payment page.
			wp_enqueue_script( 'edr-payment', EDR_PLUGIN_URL . 'assets/public/js/payment.js', array( 'jquery' ), '2.0.1', true );
			wp_localize_script( 'edr-payment', 'edrPaymentVars', array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'edr_ajax' ),
				'get_states_nonce' => wp_create_nonce( 'edr_get_states' )
			) );
		}
	}

	/**
	 * Include template functions.
	 */
	public function require_template_functions() {
		// This file is included on 'after_setup_theme' action to allow
		// for its functions to be overriden in theme's functions.php
		require_once EDR_PLUGIN_DIR . 'includes/template-functions.php';
	}

	/**
	 * Register widgets.
	 */
	public function register_widgets() {
		register_widget( 'Edr_Widget_CourseCategories' );
		register_widget( 'Edr_Widget_LessonsList' );
	}
}

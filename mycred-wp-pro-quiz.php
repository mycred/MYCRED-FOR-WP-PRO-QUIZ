<?php
/**
 * Plugin Name: myCRED for WP Pro Quiz
 * Plugin URI: http://mycred.me
 * Description: Allows you to reward users with points for completing courses.
 * Version: 1.0.2
 * Tags: mycred, points, quiz
 * Author: Gabriel S Merovingi
 * Author URI: http://www.merovingi.com
 * Author Email: support@mycred.me
 * Requires at least: WP 4.0
 * Tested up to: WP 4.7.2
 * Text Domain: mycred_wp_pro_quiz
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
if ( ! class_exists( 'myCRED_WP_Pro_Quiz' ) ) :
	final class myCRED_WP_Pro_Quiz {

		// Plugin Version
		public $version             = '1.0.2';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		public $slug                = '';
		public $domain              = '';
		public $plugin              = NULL;
		public $plugin_name         = '';
		protected $update_url       = 'http://mycred.me/api/plugins/';

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->slug        = 'mycred-wp-pro-quiz';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred_wp_pro_quiz';
			$this->plugin_name = 'myCRED for WP-Pro-Quiz';

			$this->define_constants();
			$this->plugin_updates();

			add_filter( 'mycred_setup_hooks',    array( $this, 'register_hook' ) );
			add_action( 'mycred_init',           array( $this, 'load_textdomain' ) );
			add_action( 'mycred_all_references', array( $this, 'add_badge_support' ) );
			add_action( 'mycred_load_hooks',     'mycred_load_wp_pro_quiz_hook' );

		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		public function define_constants() {

			$this->define( 'MYCRED_WP_PRO_QUIZ_SLUG', $this->slug );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY', 'mycred_default' );

		}

		/**
		 * Includes
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() { }

		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( $this->plugin ) . '/lang/' );

		}

		/**
		 * Plugin Updates
		 * @since 1.0
		 * @version 1.0
		 */
		public function plugin_updates() {

			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_plugin_update' ), 330 );
			add_filter( 'plugins_api',                           array( $this, 'plugin_api_call' ), 330, 3 );
			add_filter( 'plugin_row_meta',                       array( $this, 'plugin_view_info' ), 330, 3 );

		}

		/**
		 * Register Hook
		 * @since 1.0
		 * @version 1.0
		 */
		public function register_hook( $installed ) {

			if ( ! defined( 'WPPROQUIZ_VERSION' ) ) return $installed;

			$installed['wpproquiz'] = array(
				'title'       => __( 'WP-Pro-Quiz', $this->domain ),
				'description' => __( 'Awards %_plural% to users who complete quizes.', 'mycred_wp_pro_quiz' ),
				'callback'    => array( 'myCRED_Hook_WP_Pro_Quiz' )
			);

			return $installed;

		}

		/**
		 * Add Badge Support
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_badge_support( $references ) {

			if ( ! defined( 'WPPROQUIZ_VERSION' ) ) return $references;

			$references['completing_quiz']      = __( 'Completing Quiz (WP-Pro-Quiz)', 'mycred_wp_pro_quiz' );
			$references['full_completing_quiz'] = __( '100% Completion (WP-Pro-Quiz)', 'mycred_wp_pro_quiz' );

			return $references;

		}

		/**
		 * Plugin Update Check
		 * @since 1.0
		 * @version 1.0
		 */
		public function check_for_plugin_update( $checked_data ) {

			global $wp_version;

			if ( empty( $checked_data->checked ) )
				return $checked_data;

			$args = array(
				'slug'    => $this->slug,
				'version' => $this->version,
				'site'    => site_url()
			);
			$request_string = array(
				'body'       => array(
					'action'     => 'version', 
					'request'    => serialize( $args ),
					'api-key'    => md5( get_bloginfo( 'url' ) )
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
			);

			// Start checking for an update
			$response = wp_remote_post( $this->update_url, $request_string );

			if ( ! is_wp_error( $response ) ) {

				$result = maybe_unserialize( $response['body'] );

				if ( is_object( $result ) && ! empty( $result ) )
					$checked_data->response[ $this->slug . '/' . $this->slug . '.php' ] = $result;

			}

			return $checked_data;

		}

		/**
		 * Plugin View Info
		 * @since 1.0
		 * @version 1.0
		 */
		public function plugin_view_info( $plugin_meta, $file, $plugin_data ) {

			if ( $file != $this->plugin ) return $plugin_meta;

			$plugin_meta[] = sprintf( '<a href="%s" class="thickbox" aria-label="%s" data-title="%s">%s</a>',
				esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $this->slug .
				'&TB_iframe=true&width=600&height=550' ) ),
				esc_attr( __( 'More information about this plugin', 'mycred_wp_pro_quiz' ) ),
				esc_attr( $this->plugin_name ),
				__( 'View details', 'mycred_wp_pro_quiz' )
			);

			return $plugin_meta;

		}

		/**
		 * Plugin New Version Update
		 * @since 1.0
		 * @version 1.0
		 */
		public function plugin_api_call( $result, $action, $args ) {

			global $wp_version;

			if ( ! isset( $args->slug ) || ( $args->slug != $this->slug ) )
				return $result;

			// Get the current version
			$args = array(
				'slug'    => $this->slug,
				'version' => $this->version,
				'site'    => site_url()
			);
			$request_string = array(
				'body'       => array(
					'action'     => 'info', 
					'request'    => serialize( $args ),
					'api-key'    => md5( get_bloginfo( 'url' ) )
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
			);

			$request = wp_remote_post( $this->update_url, $request_string );

			if ( ! is_wp_error( $request ) )
				$result = maybe_unserialize( $request['body'] );

			return $result;

		}

	}
endif;

function mycred_wp_pro_quiz_plugin() {
	return myCRED_WP_Pro_Quiz::instance();
}
mycred_wp_pro_quiz_plugin();

/**
 * WP Pro Quiz Hook
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_wp_pro_quiz_hook' ) ) :
	function mycred_load_wp_pro_quiz_hook() {

		if ( class_exists( 'myCRED_Hook_WP_Pro_Quiz' ) ) return;

		class myCRED_Hook_WP_Pro_Quiz extends myCRED_Hook {

			/**
			 * Construct Hook
			 */
			function __construct( $hook_prefs, $type = 'mycred_default' ) {

				// We use the abstract classes constructor to construct our own
				// We need to provide a unique hook id and the default settings
				parent::__construct( array(
					'id'       => 'wpproquiz',
					'defaults' => array(
						'completed'    => array(
							'creds'  => 1,
							'log'    => '%plural% for completing quiz',
							'limit'  => '0/x'
						),
						'fullscrore'    => array(
							'creds'  => 1,
							'log'    => '%plural% for 100% quiz completion',
							'limit'  => '0/x'
						)
					)
				), $hook_prefs, $type );

			}

			/**
			 * Hook into WP Pro Quiz
			 * The run() method fires of during WordPress's init instance.
			 * This method must be set and should be used to "hook" into the third-party
			 * plugin that we want to support.
			 * @since 1.0
			 * @version 1.0
			 */
			function run() {

				// Zero points means this feature is "off".
				if ( $this->prefs['completed']['creds'] != 0 )
					add_action( 'wp_pro_quiz_completed_quiz',             array( $this, 'completed_quiz' ) );

				// Zero points means this feature is "off".
				if ( $this->prefs['fullscrore']['creds'] != 0 )
					add_action( 'wp_pro_quiz_completed_quiz_100_percent', array( $this, 'completed_quiz_full' ) );

			}

			/**
			 * Complete Quiz
			 * This instance is provided by WP Pro Quiz and fires of when a 
			 * quiz was successfully completed.
			 * @since 1.0
			 * @version 1.2
			 */
			function completed_quiz() {

				// Must be logged in
				if ( ! is_user_logged_in() ) return;

				// We need a user ID and a Quiz ID
				$user_id = get_current_user_id();
				$quiz_id = absint( $_REQUEST['quizId'] );

				// Check for exclusions
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Award if not over limit
				if ( ! $this->over_hook_limit( 'completed', 'completing_quiz', $user_id ) )
					$this->core->add_creds(
						'completing_quiz',
						$user_id,
						$this->prefs['completed']['creds'],
						$this->prefs['completed']['log'],
						$quiz_id,
						array( 'ref_type' => 'post' ),
						$this->mycred_type
					);

			}

			/**
			 * Complete Quiz Full
			 * This instance is provided by WP Pro Quiz and fires of when a 
			 * quiz was successfully completed with full marks.
			 * @since 1.0
			 * @version 1.2
			 */
			function completed_quiz_full() {

				// Must be logged in
				if ( ! is_user_logged_in() ) return;

				// We need a user ID and a Quiz ID
				$user_id = get_current_user_id();
				$quiz_id = absint( $_REQUEST['quizId'] );

				// Check for exclusions
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Award if not over limit
				if ( ! $this->over_hook_limit( 'fullscrore', 'completing_quiz_full', $user_id ) )
					$this->core->add_creds(
						'completing_quiz_full',
						$user_id,
						$this->prefs['fullscrore']['creds'],
						$this->prefs['fullscrore']['log'],
						$quiz_id,
						array( 'ref_type' => 'post' ),
						$this->mycred_type
					);

			}

			/**
			 * Preference for this Hook
			 * The preferences() methos is optional and only needs to be defined
			 * if this hook needs to have settings that a user must have access to.
			 * To ensure the settings are correctly saved, you should use the built-in
			 * $this->field_name() and $this->field_id() methods. They will do the grunt
			 * work for you.
			 * @since 1.0
			 * @version 1.2.1
			 */
			public function preferences() {

				$prefs = $this->prefs;

?>
<label class="subheader" for="<?php echo $this->field_id( array( 'completed' => 'creds' ) ); ?>"><?php _e( 'Completing Quiz', 'mycred_wp_pro_quiz' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'completed' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'completed' => 'creds' ) ); ?>" value="<?php echo esc_attr( $prefs['completed']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'completed' => 'log' ) ); ?>"><?php _e( 'Log template', 'mycred_wp_pro_quiz' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'completed' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'completed' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['completed']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->core->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
	<li>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'completed' => 'limit' ) ), $this->field_id( array( 'completed' => 'limit' ) ), $prefs['completed']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'fullscrore' => 'creds' ) ); ?>"><?php _e( '100% Completion', 'mycred_wp_pro_quiz' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'fullscrore' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'fullscrore' => 'creds' ) ); ?>" value="<?php echo esc_attr( $prefs['fullscrore']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'fullscrore' => 'log' ) ); ?>"><?php _e( 'Log template', 'mycred_wp_pro_quiz' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'fullscrore' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'fullscrore' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['fullscrore']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->core->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
	<li>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'fullscrore' => 'limit' ) ), $this->field_id( array( 'fullscrore' => 'limit' ) ), $prefs['fullscrore']['limit'] ); ?>
	</li>
</ol>
<?php

			}

			/**
			 * Sanitise Preferences
			 * The sanitise_preferences() method fires when a user saved the hook settings.
			 * It should be used to sanitize and validate settings entered by the user and
			 * if the hook supports "Hook limits", save the limits setup.
			 * @since 1.2
			 * @version 1.0
			 */
			function sanitise_preferences( $data ) {

				// Hook limits consists of two variables: The actual limit and the frequency.
				// These two settings needs to be combined into one string divided by a forward slash.
				if ( isset( $data['completed']['limit'] ) && isset( $data['completed']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['completed']['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data['completed']['limit'] = $limit . '/' . $data['completed']['limit_by'];
					unset( $data['completed']['limit_by'] );
				}

				// Hook limits consists of two variables: The actual limit and the frequency.
				// These two settings needs to be combined into one string divided by a forward slash.
				if ( isset( $data['fullscrore']['limit'] ) && isset( $data['fullscrore']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['fullscrore']['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data['fullscrore']['limit'] = $limit . '/' . $data['fullscrore']['limit_by'];
					unset( $data['fullscrore']['limit_by'] );
				}

				return $data;

			}

		}

	}
endif;

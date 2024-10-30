<?php
/**
 * Plugin Name: Login On Checkout for Easy Digital Downloads
 * Plugin URI: https://ironikus.com/downloads/edd-login-on-checkout/
 * Description: Add a login possibility at the EDD checkout for already existing customers.
 * Version: 1.0
 * Author: Ironikus
 * Author URI: https://ironikus.com/
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists('EDD_Login_On_Checkout') ){
	class EDD_Login_On_Checkout{
		public function __construct() {
			$this->add_hooks();
		}

		public function add_hooks(){
			add_action( 'plugins_loaded', array( $this, 'ironikus_load_edd_auto_register_login_after_plugins' ) );
			add_action( 'edd_checkout_before_gateway', array( $this, 'edd_login_on_checkout_validate_password_and_login'), 10 );
			add_action( 'edd_checkout_error_checks', array( $this, 'edd_login_on_checkout_add_password_field_to_checkout_validate'), 10, 2 );
		}

		/**
		 * Add the hooks after the plugins are loaded to
		 * keep the functionality alive for Checkout Fields Manager
		 */
		public function ironikus_load_edd_auto_register_login_after_plugins(){
			if( class_exists( 'EDD_Checkout_Fields_Manager' ) ){
				add_action( 'edd_checkout_fields_add_password', array( $this, 'ironikus_edd_login_on_checkout_add_password_on_checkout'), 10 );
			} else {
				add_action( 'edd_purchase_form_user_info_fields', array( $this, 'ironikus_edd_login_on_checkout_add_password_on_checkout'), 10 );
			}
		}

		/**
		 * Add the password field at the checkout form
		 */
		public function ironikus_edd_login_on_checkout_add_password_on_checkout(){
			if( is_user_logged_in() ){
				return;
			}

			$html = $this->ironikus_get_edd_login_on_checkout_field();

			echo $html;
		}

		/**
		 * Returns the password field HTML
		 *
		 * @return string - The password field
		 */
		public function ironikus_get_edd_login_on_checkout_field(){
			ob_start();
			?>
            <script>
                jQuery(document).ready(function($) {

                    //First time init
                    togglePaswordField();

                    $( "#ironikus-pasword-activate" ).on( "change", function() {
                        togglePaswordField();
                    });

                    function togglePaswordField(){
                        if( $( "#ironikus-pasword-activate" ).is(':checked') ){
                            $( "#ironikus-password-wrap" ).css('display', 'block');
                        } else {
                            $( "#ironikus-password-wrap" ).css('display', 'none');
                        }
                    }
                });
            </script>
            <input id="ironikus-pasword-activate" type="checkbox" name="edd_activate_password" value="yes"> <?php echo esc_html_e( 'Already a customer?', 'easy-digital-downloads' ); ?><br>
            <p id="ironikus-password-wrap">
                <label class="edd-label" for="edd-password">
					<?php esc_html_e( 'Password', 'easy-digital-downloads' ); ?>
					<?php if( edd_field_is_required( 'edd_password' ) ) { ?>
                        <span class="edd-required-indicator">*</span>
					<?php } ?>
                </label>
                <span class="edd-description" id="edd-last-description"><?php esc_html_e( 'Enter your password to login into your account.', 'easy-digital-downloads' ); ?></span>
                <input class="edd-input" type="password" name="edd_password" id="edd-password" placeholder="<?php esc_html_e( 'Password', 'easy-digital-downloads' ); ?>"/>
            </p>
			<?php
			$res = ob_get_clean();
			return $res;
		}

		/**
		 * Validate the login for our checkout password field and
		 * log the user in if he exists and the password is correct.
		 *
		 * @param int $payment_id
		 * @param array $payment_data
		 */
		public function edd_login_on_checkout_validate_password_and_login() {

			if( ! isset( $_POST['edd_password'] ) || ! isset( $_POST['edd_email'] )){
				return;
			}

			$user = get_user_by( 'email', sanitize_email( $_POST['edd_email'] ) );

			// User account already exists
			if ( $user ) {

				if( wp_check_password( $_POST['edd_password'], $user->data->user_pass, $user->data->ID ) ){
					//Login the user -- password doesn't need to be defined
					edd_log_user_in( $user->data->ID, $user->data->user_login, $_POST['edd_password'] );
				} else {
					edd_send_back_to_checkout( '?payment-mode=' . $_POST['edd-gateway'] . '&eddloc=login-error' );
					//Just in case...
					die();
				}
			}
		}

		/**
		 * Set validation messages for the password call
		 *
		 * @param $valid_data
		 * @param $data
		 */
		public function edd_login_on_checkout_add_password_field_to_checkout_validate( $valid_data, $data ) {

			if( is_user_logged_in() )
				return;

			$user = get_user_by( 'email', sanitize_email( $data['edd_email'] ) );

			if(!empty($user)){
				if ( empty( $data['edd_password'] ) ) {
					edd_set_error( 'invalid_password', 'Your specified email is already in use. Please log in to make the purchase.' );
				} else {
					if( ! wp_check_password( $data['edd_password'], $user->data->user_pass, $user->data->ID ) ){
						edd_set_error( 'invalid_password', 'Invalid credentials. Please try again.' );
					}
				}
			}

		}

	}
}

/**
 * Init the class
 */
new EDD_Login_On_Checkout();
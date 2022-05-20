<?php
/*
Plugin Name: Spam Phone Block for CF7
Description: Plugin filters blacklisted phone numbers
Author: Pavel.s
Version: 1.0.0
Text Domain: no-spam-phone
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class SpamPhoneBlockCF7 {
	function __construct() {
		add_filter( 'shortcode_atts_wpcf7', [$this, 'my_shortcode_atts_wpcf7'], 10, 3 );
		add_filter( 'wpcf7_form_hidden_fields', [$this, 'my_form_add_hidden_field'] );
		add_filter( 'wpcf7_validate', [$this, 'my_form_validate'], 10, 2 );
		add_action( 'admin_menu', [ $this, 'adminPage' ] );
		add_action( 'admin_init', [ $this, 'optionSubPageSettings' ] );
		add_action( 'plugins_loaded', [ $this, 'languages' ] );
	}

	function languages() {
		load_plugin_textdomain( 'no-spam-phone', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	function my_shortcode_atts_wpcf7( $out, $pairs, $atts ) {
		if ( in_array( 'bad-phone', $atts ) ) {
			$out['bad-phone'] = 'form-check-phone';
		}

		return $out;
	}

	function my_form_add_hidden_field( $arr ) {

		$form = WPCF7_ContactForm::get_current();

		if ( $form->shortcode_attr( 'bad-phone' ) === 'form-check-phone' ) {
			$arr['form-check-phone'] = 1;
		}

		return $arr;
	}

	function my_form_validate( $result, $tags ) {
		$form = WPCF7_Submission::get_instance();

		$phoneCF7Code = get_option( 'cf7_phone_name' );
		$marker = $form->get_posted_data( 'form-check-phone' );
		$phone  = $form->get_posted_data( $phoneCF7Code );

		$error_msg = esc_html__('This number is spammed', 'no-spam-phone');

		if ( $marker === '1' && get_option( 'phone_to_filter' ) ) {

			$wordsTrimed = array_map( 'trim', explode( ",", get_option( 'phone_to_filter' ) ) );
			
			if ( in_array($phone, $wordsTrimed) ) {
			   $result->invalidate( $phoneCF7Code, $error_msg );
			}	

		}

		return $result;
	}


	function optionSubPageSettings() {
		add_settings_section( 'option_section', null, [$this,'description_settings_section'], 'filter_options' );

		register_setting( 'option_group', 'cf7_phone_name', [
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => ''
		] );
		add_settings_field(
			'cf7_phone_name',
			esc_html__('Phone field name in CF7', 'no-spam-phone'),
			[ $this, 'cf7_phone_name_html_input' ],
			'filter_options',
			'option_section',
			[
				'name' => 'cf7_phone_name',
			]
		);
	}

	function description_settings_section() {
		echo '<p>';
		esc_html_e('The attribute with the value "bad-phone" must be added to the shortcode. Example - ','no-spam-phone');
		echo '[contact-form-7 id="1" title="" <strong>bad-phone</strong>]';
		echo '</p>';
	}

	function cf7_phone_name_html_input( $args ) {
		?>
        <input name="<?php echo $args['name']; ?>"
               value="<?php echo esc_attr( get_option( 'cf7_phone_name', '' ) ); ?>"
               placeholder="your-phone"
               type="text">
		<?php
	}

	function adminPage() {
		$mainPageHookSuffix = add_menu_page(
			esc_html__('CF7 Phone filter', 'no-spam-phone'),
			esc_html__('Phone filter', 'no-spam-phone'),
			'manage_options',
			'filter_menu',
			[ $this, 'adminPageMain' ],
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiCgkgdmlld0JveD0iMCAwIDUxMiA1MTIiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDUxMiA1MTI7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPGc+Cgk8Zz4KCQk8cGF0aCBkPSJNMjQ1LjM2OCw0Mi42NjdoLTI5Ljg2N2MtNC40MTgsMC04LDMuNTgyLTgsOGMwLDQuNDE4LDMuNTgyLDgsOCw4aDI5Ljg2N2M0LjQxOSwwLDgtMy41ODIsOC04CgkJCUMyNTMuMzY4LDQ2LjI0OSwyNDkuNzg3LDQyLjY2NywyNDUuMzY4LDQyLjY2N3oiLz4KCTwvZz4KPC9nPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik0yNDUuMzY4LDEwMi40aC0yOS44NjdjLTQuNDE4LDAtOCwzLjU4Mi04LDhjMCw0LjQxOCwzLjU4Miw4LDgsOGgyOS44NjdjNC40MTksMCw4LTMuNTgyLDgtOAoJCQlDMjUzLjM2OCwxMDUuOTgyLDI0OS43ODcsMTAyLjQsMjQ1LjM2OCwxMDIuNHoiLz4KCTwvZz4KPC9nPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik0yNDUuMzY4LDEzMi4yNjdoLTI5Ljg2N2MtNC40MTgsMC04LDMuNTgyLTgsOGMwLDQuNDE4LDMuNTgyLDgsOCw4aDI5Ljg2N2M0LjQxOSwwLDgtMy41ODIsOC04CgkJCUMyNTMuMzY4LDEzNS44NDksMjQ5Ljc4NiwxMzIuMjY3LDI0NS4zNjgsMTMyLjI2N3oiLz4KCTwvZz4KPC9nPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik00ODkuNjQyLDU5LjkzOWMtOC43My0zMy4wODYtMzguODY2LTU3LjUxMS03Mi43ODktNTkuNzU0Yy0wLjA5Ny0wLjAxMS0wLjE5NC0wLjAxOS0wLjI5MS0wLjAyNwoJCQlDNDE2LjA0NywwLjA1NSwyNy43NjgsMCwyNy43NjgsMGMtNC40MTgsMC04LDMuNTgyLTgsOHMzLjU4Miw4LDgsOGgxNy41ODZsMTQ1LjA4MSwxNzQuMDk2djE0OC41NzEKCQkJYzAsMi45MDksMS41NzgsNS41ODcsNC4xMjIsNi45OTdjMS4yMDgsMC42NywyLjU0NCwxLjAwMywzLjg3OCwxLjAwM2MxLjQ3MywwLDIuOTQ1LTAuNDA2LDQuMjQtMS4yMTZsNjguMjY3LTQyLjY2NwoJCQljMi4zMzktMS40NjIsMy43Ni00LjAyNiwzLjc2LTYuNzg0VjE5MC4wOTZsNjMuNjg1LTc2LjQyMmM1LjUyNCwxMi4xMDIsMTQuMTUsMjIuOTYxLDI0LjgzMiwzMS4wOQoJCQljMS40NDgsMS4xMDIsMy4xNSwxLjYzNCw0LjgzOCwxLjYzNGMyLjQxNCwwLDQuNzk4LTEuMDg4LDYuMzcyLTMuMTU1YzIuNjc2LTMuNTE2LDEuOTk1LTguNTM1LTEuNTIxLTExLjIxMQoJCQljLTEwLjIzNC03Ljc4OC0xOC4xMjYtMTguNjY1LTIyLjIyNS0zMC42MjVjLTAuMTk0LTAuNTY3LTAuNDUxLTEuMDk1LTAuNzUyLTEuNTg2bDY5LjQ1My04My4zNDMKCQkJYzI1LjcxOSwzLjE4Miw0OC4wOTksMjIuMTkzLDU0Ljc4Nyw0Ny41NDJjNy4zNzksMjcuOTcyLTUuNTY5LDU4LjU4Mi0zMC43ODYsNzIuNzg1Yy0xNC4zNyw4LjA5NC0zMS44MDYsMTAuMzU1LTQ3Ljg0LDYuMjA3CgkJCWMtNC4yNzctMS4xMDYtOC42NDIsMS40NjMtOS43NDksNS43NGMtMS4xMDcsNC4yNzcsMS40NjIsOC42NDIsNS43NDEsOS43NDljNi41NjQsMS42OTksMTMuMzE3LDIuNTM1LDIwLjA2NiwyLjUzNQoJCQljMTMuODA1LDAsMjcuNTgyLTMuNTA0LDM5LjYzNC0xMC4yOTFjMTUuMzEtOC42MjIsMjcuNTc4LTIyLjQ0MSwzNC41NDctMzguOTA4QzQ5Mi43NTUsOTUuMzYzLDQ5NC4xMjYsNzYuOTMyLDQ4OS42NDIsNTkuOTM5egoJCQkgTTI1OC43MDIsMjkxLjU2NmwtNTIuMjY3LDMyLjY2N1YxOTUuMmg1Mi4yNjdWMjkxLjU2NnogTTI2Mi45NTQsMTc5LjJoLTYwLjc3M2wtNzUuNTU1LTkwLjY2N2gyMTEuODg0TDI2Mi45NTQsMTc5LjJ6CgkJCSBNMzUxLjg0Myw3Mi41MzNoLTIzOC41NUw2Ni4xODEsMTZoMzMyLjc3M0wzNTEuODQzLDcyLjUzM3oiLz4KCTwvZz4KPC9nPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik0yNjAuNTU5LDMzMy4xNjNjLTEuNDg1LTIuMjI2LTMuOTgyLTMuNTYzLTYuNjU3LTMuNTYzYy0yLjY3NSwwLTUuMTczLDEuMzM2LTYuNjU3LDMuNTYzCgkJCWMtNS4wNjksNy42MDYtMjEuNjEsMzMuNDA0LTIxLjYxLDQ2LjAzN2MwLDE1LjU4NiwxMi42ODEsMjguMjY3LDI4LjI2NywyOC4yNjdzMjguMjY3LTEyLjY4MSwyOC4yNjctMjguMjY3CgkJCUMyODIuMTY4LDM2Ni41NjgsMjY1LjYyNywzNDAuNzY5LDI2MC41NTksMzMzLjE2M3ogTTI1My45MDEsMzkxLjQ2N2MtNi43NjQsMC0xMi4yNjctNS41MDMtMTIuMjY3LTEyLjI2NwoJCQljMC0zLjk1OCw1LjU5LTE1LjMyOCwxMi4yNjgtMjYuNTdjNi42NzYsMTEuMjM1LDEyLjI2NiwyMi42MDQsMTIuMjY2LDI2LjU3QzI2Ni4xNjgsMzg1Ljk2NCwyNjAuNjY1LDM5MS40NjcsMjUzLjkwMSwzOTEuNDY3eiIvPgoJPC9nPgo8L2c+CjxnPgoJPGc+CgkJPHBhdGggZD0iTTIxOC45NTksMzg4LjYyOWMtMS40ODQtMi4yMjYtMy45ODItMy41NjMtNi42NTctMy41NjNzLTUuMTczLDEuMzM2LTYuNjU3LDMuNTYzYy01LjA3LDcuNjA2LTIxLjYxLDMzLjQwNC0yMS42MSw0Ni4wMzcKCQkJYzAsMTUuNTg2LDEyLjY4MSwyOC4yNjcsMjguMjY3LDI4LjI2N3MyOC4yNjctMTIuNjgxLDI4LjI2Ny0yOC4yNjdDMjQwLjU2OCw0MjIuMDM0LDIyNC4wMjgsMzk2LjIzNiwyMTguOTU5LDM4OC42Mjl6CgkJCSBNMjEyLjMwMSw0NDYuOTMzYy02Ljc2NCwwLTEyLjI2Ny01LjUwMy0xMi4yNjctMTIuMjY3YzAtMy45NTgsNS41OTEtMTUuMzI4LDEyLjI2OC0yNi41NwoJCQljNi42NzUsMTEuMjM1LDEyLjI2NiwyMi42MDQsMTIuMjY2LDI2LjU3QzIyNC41NjgsNDQxLjQzLDIxOS4wNjUsNDQ2LjkzMywyMTIuMzAxLDQ0Ni45MzN6Ii8+Cgk8L2c+CjwvZz4KPGc+Cgk8Zz4KCQk8cGF0aCBkPSJNMjYxLjYyNSw0MzcuNjk2Yy0xLjQ4NS0yLjIyNi0zLjk4Mi0zLjU2My02LjY1Ny0zLjU2M2MtMi42NzUsMC01LjE3MywxLjMzNy02LjY1NywzLjU2MwoJCQljLTUuMDY5LDcuNjA2LTIxLjYxLDMzLjQwNC0yMS42MSw0Ni4wMzdjMCwxNS41ODYsMTIuNjgxLDI4LjI2NywyOC4yNjcsMjguMjY3czI4LjI2Ny0xMi42OCwyOC4yNjctMjguMjY3CgkJCUMyODMuMjM1LDQ3MS4xMDEsMjY2LjY5NCw0NDUuMzAyLDI2MS42MjUsNDM3LjY5NnogTTI1NC45NjgsNDk2Yy02Ljc2NCwwLTEyLjI2Ny01LjUwMy0xMi4yNjctMTIuMjY3CgkJCWMwLTMuOTU4LDUuNTktMTUuMzI4LDEyLjI2OC0yNi41N2M2LjY3NiwxMS4yMzUsMTIuMjY2LDIyLjYwNCwxMi4yNjYsMjYuNTdDMjY3LjIzNSw0OTAuNDk3LDI2MS43MzIsNDk2LDI1NC45NjgsNDk2eiIvPgoJPC9nPgo8L2c+Cjwvc3ZnPgo=',
			99
		);

		add_submenu_page(
			'filter_menu',
			esc_html__('CF7 Phone filter', 'no-spam-phone'),
			esc_html__('Phone numbers', 'no-spam-phone'),
			'manage_options',
			'filter_menu',
			[ $this, 'adminPageMain' ]
		);

		add_submenu_page(
			'filter_menu',
			esc_html__('CF7 Phone filter settings', 'no-spam-phone'),
			esc_html__('Settings', 'no-spam-phone'),
			'manage_options',
			'filter_options',
			[ $this, 'adminSubPageOptions' ]
		);

		add_action( "load-{$mainPageHookSuffix}", [ $this, 'css_load' ] );

	}

	function css_load() {
		wp_enqueue_style( 'filter-admin', MY_PLUGIN_URL . 'css/filter-admin.css', '', '1.0' );
	}

	function handleForm() {
		if ( wp_verify_nonce( $_POST['ourNonce'], 'filterPhones' ) and current_user_can( 'manage_options' ) ) {
			update_option( 'phone_to_filter', sanitize_text_field( $_POST['phone_to_filter'] ) );
			?>
            <div class="updated">
                <p><?php esc_html_e('Updated', 'no-spam-phone'); ?></p>
            </div>
			<?php
		} else {
			?>
            <div class="error">
                <p><?php esc_html_e('Error validation', 'no-spam-phone'); ?></p>
            </div>
			<?php
		}
	}

	function adminPageMain() { 
		?>
        <div class="wrap">
            <h1>
            	<?php esc_html_e('Phone Filter', 'no-spam-phone'); ?>
            </h1>
			<?php if ( $_POST['btnsubmit'] == true ) $this->handleForm() ?>
            <form method="POST">
                <input type="hidden" name="btnsubmit" value="true">
				<?php wp_nonce_field( 'filterPhones', 'ourNonce' ); ?>
                <label for="phone_to_filter">
                	<p>
                		<?php esc_html_e('Enter your phone numbers separated by commas', 'no-spam-phone'); ?>
                	</p>
                </label>
                <div class="phone-filter-flex">
                    <textarea name="phone_to_filter" id="phone_to_filter" placeholder="<?php esc_attr_e('123456, 999999', 'no-spam-phone'); ?>"><?php echo esc_textarea( get_option( 'phone_to_filter', '' ) ); ?></textarea>
                </div>
                <input type="submit" id="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Save phones', 'no-spam-phone'); ?>">
            </form>
        </div>
		<?php
	}

	function adminSubPageOptions() {
		?>
        <div class="wrap">
            <h1>
            	<?php esc_html_e('Options', 'no-spam-phone'); ?>
            </h1>
            <form action="options.php" method="POST">
				<?php
                settings_errors();
				settings_fields( 'option_group' );
				do_settings_sections( 'filter_options' );
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

}

new SpamPhoneBlockCF7();
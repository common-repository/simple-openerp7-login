<?php

/*
Plugin Name: Simple OpenErp Login
Plugin URI: http://wordpress.org/plugins/simple-openerp7-login/
Description:  Authenticate your WordPress usernames against OpenErp V7.
Version: 1.0.4
Author: Alessio Gerace.
Author URI: http://www.thinkstudio.it
*/

class OpenErpLogin {
	static $instance = false;
	var $prefix = 'oe_';
	var $settings = array();
	var $openerp;
	var $ldap;

	public function __construct () {
		$this->settings = $this->get_settings_obj( $this->prefix );

        require_once( plugin_dir_path(__FILE__) . "include/OpenErp.php" );
        $this->openerp = new OpenErp(
            array (
                "oe_server"		        =>	$this->get_setting('oe_server'),
                "oe_database"	        =>	$this->get_setting('oe_database'),
            )
        );


		add_action('admin_init', array($this, 'save_settings') );
		add_action('admin_menu', array($this, 'menu') );

		if ( str_true($this->get_setting('enabled')) ) {
			add_filter('authenticate', array($this, 'authenticate'), 1, 3);
		}

		register_activation_hook( __FILE__, array($this, 'activate') );

	}

	public static function getInstance () {
		if ( !self::$instance ) {
		  self::$instance = new self;
		}
		return self::$instance;
	}

	function activate () {
		// Default settings
		$this->add_setting('oe_server', "http://localhost:8069/xmlrpc/");
		$this->add_setting('oe_database', "test");
		$this->add_setting('oe_username', "" );
		$this->add_setting('oe_password', "");
		$this->add_setting('m_domain', "@inrim.it");
		$this->add_setting('role', "Contributor");
		$this->add_setting('high_security', "true");
		$this->add_setting('create_users', "true");
		$this->add_setting('enabled', "false");


	}

	function menu () {
		add_options_page("OpenErp Login", "OpenErp 7 Login Manager",
                         'manage_options', "wp-openerp-plugin", array($this, 'admin_page') );
	}

	function admin_page () {
		include 'Oe-login-base-admin.php';
	}

	function get_settings_obj () {
		return get_option("{$this->prefix}settings", false);
	}

	function set_settings_obj ( $newobj ) {
		return update_option("{$this->prefix}settings", $newobj);
	}

	function set_setting ( $option = false, $newvalue ) {
		if( $option === false ) return false;

		$this->settings = $this->get_settings_obj($this->prefix);
		$this->settings[$option] = $newvalue;
		return $this->set_settings_obj($this->settings);
	}

	function get_setting ( $option = false ) {
		if($option === false || ! isset($this->settings[$option]) ) return false;

		return apply_filters($this->prefix . 'get_setting', $this->settings[$option], $option);
	}

	function add_setting ( $option = false, $newvalue ) {
		if($option === false ) return false;

		if ( ! isset($this->settings[$option]) ) {
			return $this->set_setting($option, $newvalue);
		} else return false;
	}

	function get_field_name($setting, $type = 'string') {
		return "{$this->prefix}setting[$setting][$type]";
	}

	function save_settings()
	{
		if( isset($_REQUEST["{$this->prefix}setting"]) && check_admin_referer('save_oe_settings','save_the_oe') ) {
			$new_settings = $_REQUEST["{$this->prefix}setting"];

			foreach( $new_settings as $setting_name => $setting_value  ) {
				foreach( $setting_value as $type => $value ) {
					if( $type == "array" ) {
						$this->set_setting($setting_name, explode(";", $value));
					} else {
						$this->set_setting($setting_name, $value);
					}
				}
			}

			add_action('admin_notices', array($this, 'saved_admin_notice') );
		}
	}

	function saved_admin_notice(){
	    echo '<div class="updated">
	       <p>OpenErp Login settings have been saved.</p>
	    </div>';

	    if( ! str_true($this->get_setting('enabled')) ) {
			echo '<div class="error">
				<p>OpenErp Login is disabled.</p>
			</div>';
	    }
	}

	function authenticate ($user, $username, $password) {
		// If previous authentication succeeded, respect that
		if ( is_a($user, 'WP_User') ) { return $user; }

		// Determine if user a local admin
		$local_admin = false;
		$user_obj = get_user_by('login', $username);
		if( user_can($user_obj, 'update_core') ) $local_admin = true;

		if ( empty($username) || empty($password) ) {
			$error = new WP_Error();

			if ( empty($username) )
				$error->add('empty_username', __('<strong>ERROR</strong>: The username field is empty.'));

			if ( empty($password) )
				$error->add('empty_password', __('<strong>ERROR</strong>: The password field is empty.'));

			return $error;
		}

		// If high security mode is enabled, remove default WP authentication hook
		if ( str_true( $this->get_setting('high_security') ) && ! $local_admin ) {
			remove_filter('authenticate', 'wp_authenticate_username_password', 20, 3);
		}

		// Sweet, let's try to authenticate our user and pass against LDAP
		//_log("Sweet, let's try to authenticate our user and pass against LDAP");
		$auth_result = $this->oe_auth($username, $password);



		if( $auth_result ) {
			// Authenticated, does user have required groups, if any?

            $user = get_user_by('login', $username);
            //_log($user);

            if ( ! $user || ( strtolower($user->user_login) !== strtolower($username) ) )  {
                if( ! str_true($this->get_setting('create_users')) ) {
                    //_log("wp_login_failed");
                    do_action( 'wp_login_failed', $username );
                    return new WP_Error('invalid_username', __('<strong>OpenErp Login Error</strong>: OpenErp credentials are correct, but there is no matching WordPress user and user creation is not enabled.'));
                }
                //_log("create new user");
                $new_user = wp_insert_user( $this->get_user_data( $username, $password ) );

                if( ! is_wp_error($new_user) )
                {
                    // Successful Login
                    $new_user = new WP_User($new_user);
                    do_action_ref_array($this->prefix . 'auth_success', array($new_user) );

                    return $new_user;
                }
                else
                {
                    do_action( 'wp_login_failed', $username );
                    return new WP_Error("{$this->prefix}login_error", __('<strong>Simple LDAP Login Error</strong>: LDAP credentials are correct and user creation is allowed but an error occurred creating the user in WordPress. Actual error: '.$new_user->get_error_message() ));
                }

            }
            else {
                return new WP_User($user->ID);
            }


		} elseif ( str_true($this->get_setting('high_security')) ) {
			return new WP_Error('invalid_username', __('<strong>OpenErp Login</strong>: OpenErp  Login could not authenticate your credentials. The security settings do not permit trying the WordPress user database as a fallback.'));
		}

		do_action($this->prefix . 'auth_failure');
		return false;
	}

	function oe_auth( $username, $password) {
		$result = false;
        $result = $this->openerp->authenticate( $username, $password );
		return apply_filters($this->prefix . 'oe_auth', $result);
		//return $result;
    }

	function get_user_data( $username,$password) {
        $data_mail='';
        if (empty($this->openerp->oe_mail)){
            $data_mail=$username.''.$this->get_setting('m_domain');
        }else{
            $data_mail=$this->openerp->oe_mail;

        }
		$user_data = array(
			'user_pass' => md5( $password ),
			'user_login' => $username,
			'user_nicename' => '',
			'user_email' => $data_mail,
			'display_name' => '',
			'first_name' => '',
			'last_name' => '',
			'role' => $this->get_setting('role')
		);


		return apply_filters($this->prefix . 'user_data', $user_data);
	}
}

if ( ! function_exists('str_true') ) {
	/**
	 * Evaluates natural language strings to boolean equivalent
	 *
	 * Used primarily for handling boolean text provided in shopp() tag options.
	 * All values defined as true will return true, anything else is false.
	 *
	 * Boolean values will be passed through.
	 *
	 * Replaces the 1.0-1.1 value_is_true()
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $string The natural language value
	 * @param array $istrue A list strings that are true
	 * @return boolean The boolean value of the provided text
	 **/
	function str_true ( $string, $istrue = array('yes', 'y', 'true','1','on','open') ) {
		if (is_array($string)) return false;
		if (is_bool($string)) return $string;
		return in_array(strtolower($string),$istrue);
	}
}

$OpenErpLogin = OpenErpLogin::getInstance();

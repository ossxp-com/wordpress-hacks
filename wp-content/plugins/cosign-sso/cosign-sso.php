<?php
/* 
Plugin Name: CoSign SSO
Plugin URI:  http://redmine.ossxp.com/redmine/projects/show/wp
Description: Alternative authentication plugin for WordPress. This plugin add two login method: LDAP login and CoSign Single Sign-on(SSO) login.
Version: 0.3.0
Author: Jiang Xin <jiangxin AT ossxp.com>
Author URI: http://www.ossxp.com/
Text Domain: cosign_sso
Domain Path: languages
Licensed under the The GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
*/ 

/**
 * Copyright (C) 2009,2010 Jiang Xin (jiangxin AT ossxp.com)
 * 
 * This file is part of CoSign SSO plugin for WordPress.
 * 
 * CoSign SSO is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * CoSign SSO is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CoSign SSO. If not, see <http://www.gnu.org/licenses/>.
 */

define('COSIGN_SSO_VERSION', "0.1");
define('COSIGN_SSO_FALLBACK_FILE', dirname(__FILE__) . '/FALLBACK');
define('COSIGN_LOGIN_DISABLED', 0);
define('COSIGN_LOGIN_LDAP',     1);
define('COSIGN_LOGIN_SSO',      2);

require_once( ABSPATH . 'wp-includes/registration.php' );
require_once( ABSPATH . 'wp-includes/formatting.php' );
require_once( ABSPATH . 'wp-includes/functions.php' );
require_once( dirname(__FILE__) . "/ldap_api.php" );

//----------------------------------------------------------------------------
//		SETUP FUNCTIONS & GLOBAL VARIABLES
//----------------------------------------------------------------------------

//On that very first include by register_activation_hook callback function,
//plugin is NOT included within the global scope. It's included in the
//activate_plugin function, and so its "main body" is not automatically 
//in the global scope. 
global $cosign_sso_opt, $cosign_sso_version, $cosign_sso_fallback;

//CoSign SSO Options
$cosign_sso_opt = get_option('cosign_sso_options');

//CoSign SSO Version
$cosign_sso_version = get_option('cosign_sso_version');

//Disable or fallback by 'FALLBACK' file
if( file_exists(COSIGN_SSO_FALLBACK_FILE)) {
	if ( trim(strtolower(file_get_contents(COSIGN_SSO_FALLBACK_FILE))) == 'ldap' )
		$cosign_sso_fallback = COSIGN_LOGIN_LDAP;
	else
		$cosign_sso_fallback = COSIGN_LOGIN_DISABLED;
} else {
	$cosign_sso_fallback = COSIGN_LOGIN_SSO;
}

//Remote User
$remote_user = @$_SERVER["REMOTE_USER"] ? @$_SERVER["REMOTE_USER"] : @$_SERVER["REDIRECT_REMOTE_USER"];

//Detect WordPress version to add compatibility with 2.3 or higher
$wpversion = preg_replace('/([0-9].[0-9])(.*)/', '$1', get_bloginfo('version')); //Boil down version number to X.X

//Plugin i18n
load_plugin_textdomain( 'cosign_sso', false, basename(dirname(__FILE__)) . "/languages/" );

//----------------------------------------------------------------------------
//	Activation and Deactivation Functions
//----------------------------------------------------------------------------

function cosign_sso_deactivate()
{
	if (file_exists(COSIGN_SSO_FALLBACK_FILE))
		unlink(COSIGN_SSO_FALLBACK_FILE);
}

function cosign_sso_activate()
{
	global $cosign_sso_opt;
	
	$cosign_sso_version = get_option('cosign_sso_version'); //CoSign SSO Version Number
	$cosign_sso_this_version = COSIGN_SSO_VERSION;
	
	// Check the version of CoSign SSO
	if (empty($cosign_sso_version))
	{
		add_option('cosign_sso_version', $cosign_sso_this_version);
	} 
	elseif ($cosign_sso_version != $cosign_sso_this_version)
	{
		update_option('cosign_sso_version', $cosign_sso_this_version);
	}
	
	// Setup Default Options Array
	$optionarray_def = array(
		'login_method' => COSIGN_LOGIN_DISABLED,
		'sso_login_url' => "https://foo.bar/cgi-bin/login",
		'sso_logout_url' => "https://foo.bar/cgi-bin/logout",
		'sso_protocol' => "3",
		'sso_srv_name' => "wordpress",
		'auto_user' => '1',
		'default_role' => 'subscriber',
		'ldap_server' => 'localhost',
		'ldap_port' => '389',
		'ldap_basedn' => 'dc=foo,dc=bar',
		'ldap_bind_dn' => '',
		'ldap_bind_passwd' => '',
		'ldap_search_filter' => '',
		'ldap_map_login' => 'uid',
		'ldap_map_lastname' => 'sn',
		'ldap_map_firstname' => 'givenName',
		'ldap_map_fullname' => 'cn',
		'ldap_map_mail' => 'mail',
	);
		
	if (empty($cosign_sso_opt)) { //If there aren't already options for CoSign SSO
		add_option('cosign_sso_options', $optionarray_def);
	}
}

//--------------------------------------------------------------------------
//	Add Admin Page
//--------------------------------------------------------------------------

function cosign_sso_add_options_page()
{
	if (function_exists('add_options_page'))
	{
		add_options_page(__('CoSign SSO', "cosign_sso"), __('CoSign SSO', "cosign_sso"), 8, basename(__FILE__), 'cosign_sso_options_page');
	}
}

//----------------------------------------------------------------------------
//	Disable user registration
//----------------------------------------------------------------------------
function cosign_sso_register_disabled()
{
	if (get_option('users_can_register'))
		update_option('users_can_register', FALSE);
}

//----------------------------------------------------------------------------
//	Redirect to CoSign Login page
//----------------------------------------------------------------------------
function cosign_sso_login_redirect()
{
	global $remote_user, $cosign_sso_opt;

	if ($remote_user)
		return;

	$sso_login_url    = $cosign_sso_opt["sso_login_url"];
	if (isset($cosign_sso_opt["sso_protocol"]))
		$sso_protocol  = $cosign_sso_opt["sso_protocol"];
	else
		$sso_protocol  = "2";
	$service_url  = "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];

	if ($sso_protocol == "3")
	{
		## CoSign protocol 3: redirect to weblogin only with cookie name.
		## It is the cgi's responsibility to generate cookie and the cookie is
		## set by uri /cosign/valid/ of this domain.
		$cookie_name = "cosign-" . $cosign_sso_opt["sso_srv_name"];
		$dest_url = $sso_login_url . "?" . $cookie_name . "&" .  $service_url;
	}
	else
	{
		## CoSign protocol 2: set cookie first, then redirect to weblogin with
		## cookie data in query string.
		$cookie_name = "cosign-" . $cosign_sso_opt["sso_srv_name"];
		$cookie_data = '';
		$sample_string =
		"0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		for ($i=0;$i<125;$i++) {
			$cookie_data .= $sample_string[mt_rand(0,61)];
		}
		setcookie( $cookie_name, $cookie_data );
		$dest_url = $sso_login_url . "?" . $cookie_name . "=" . $cookie_data . ";&" .  $service_url;
	}
	header( "Location: $dest_url" );
	exit;
}

//----------------------------------------------------------------------------
//	Redirect to CoSign Logout URL
//----------------------------------------------------------------------------
function cosign_sso_clear_auth_cookie()
{
	global $cosign_sso_opt;

	if (@$_SERVER['COSIGN_SERVICE'] || @$_SERVER['REDIRECT_COSIGN_SERVICE'])
	{
		$cookie_name = @$_SERVER["COSIGN_SERVICE"] ? @$_SERVER["COSIGN_SERVICE"] : @$_SERVER["REDIRECT_COSIGN_SERVICE"];
	}
	else
	{
		$cookie_name = "cosign-" . $cosign_sso_opt["sso_srv_name"];
	}

	setcookie( $cookie_name, "null", time() - 31536000, '/', "", 0 );
	setcookie( $cookie_name, "null", time() - 31536000 );
}

function cosign_sso_logout_redirect()
{
	global $cosign_sso_opt;

	$logout_url = $cosign_sso_opt["sso_logout_url"];
	$blogurl = get_bloginfo('url');
	$wpurl = get_bloginfo('wpurl');

	$logout_url .= "?" . ($blogurl ? $blogurl : $wpurl);
	wp_redirect($logout_url);
	exit();
}

//----------------------------------------------------------------------------
//	CoSign Authentication
//----------------------------------------------------------------------------
function cosign_sso_authenticate($user, $username="", $password="")
{
	global $remote_user, $cosign_sso_opt, $cosign_sso_fallback;

	if ( is_a($user, 'WP_User') ) { return $user; }

	if ( (int)$cosign_sso_opt["login_method"] == COSIGN_LOGIN_SSO &&
		  $cosign_sso_fallback != COSIGN_LOGIN_LDAP )
	{
		$username = $remote_user;
		if (!$username) {
			wp_die(__('CoSign login failed, no REMOTE_USER defined.', "cosign_sso"), __('login failed', "cosign_sso"));
		}
	}
	elseif ( (int)$cosign_sso_opt["login_method"] == COSIGN_LOGIN_LDAP ||
		      $cosign_sso_fallback == COSIGN_LOGIN_LDAP )
	{
		if ( empty($username) || empty($password) ) {
			$error = new WP_Error();

			if ( empty($username) )
				$error->add('empty_username', __('<strong>ERROR</strong>: The username field is empty.', "cosign_sso"));

			if ( empty($password) )
				$error->add('empty_password', __('<strong>ERROR</strong>: The password field is empty.', "cosign_sso"));

			return $error;
		}
		$ret = cosign_sso_ldap_authenticate( $username, $password );
		if (!$ret || !$ret->status_ok)
		{
			return new WP_Error('incorrect_password', sprintf(__('<strong>ERROR</strong>: Incorrect password. <a href="%s" title="Password Lost and Found">Lost your password</a>?', "cosign_sso"), site_url('wp-login.php?action=lostpassword', 'login')));
		}
	}
	else
	{
		$error = new WP_Error();
		$error->add('login failed', '<strong>ERROR</strong>: Unknown CoSign login mode, must be sso or ldap.');
		return $error;
	}

	$userdata = get_userdatabylogin($username);

	if ( !$userdata && !$cosign_sso_opt["auto_user"] )
	{
		wp_die(__('Invalid username. Turn on auto_user to create user account automatically.', "cosign_sso"), __('login failed', "cosign_sso"));
	}

	$ldap_userdata = cosign_sso_ldap_fetch_account($username);

	// User account exists.
	if ( $userdata )
	{
		// Update user account
		wp_update_user(array("ID"         => $userdata->ID,
		                     "user_email" => sanitize_text_field($ldap_userdata["mail"]),
		                     "first_name" => sanitize_text_field($ldap_userdata["firstname"]),
		                     "last_name"  => sanitize_text_field($ldap_userdata["lastname"]),
		                     "nickname"   => sanitize_text_field($ldap_userdata["fullname"]),
		                    ));
	} else {
		// Create new user account
		wp_insert_user(array("user_login" => "$username",
		                     "user_pass"  =>  "",
		                     "user_nicename"=>sanitize_text_field($ldap_userdata["fullname"]),
		                     "user_email" =>  sanitize_text_field($ldap_userdata["mail"]),
		                     "first_name" =>  sanitize_text_field($ldap_userdata["firstname"]),
		                     "last_name"  =>  sanitize_text_field($ldap_userdata["lastname"]),
		                     "nickname"   =>  sanitize_text_field($ldap_userdata["fullname"]),
		                     "role"       =>  $cosign_sso_opt["default_role"],
		                    ));
	}
	$userdata = get_userdatabylogin($username);

	$user =  new WP_User($userdata->ID);
	//$user->set_role( $new_role );

	return $user;
}

//----------------------------------------------------------------------------
//	Disable profile form fields using javascript.
//----------------------------------------------------------------------------
function cosign_sso_profile_notes()
{
	$text = "<p><span class='description'>".
		__("Note: Some fields of profile store in LDAP and can not be changed here. These fields are disabled below.", "cosign_sso") .
		"</span></p>";
	print $text;
}

function cosign_sso_profile_js()
{
	$script = <<<EOD
<script language='javascript'>
	var theform = document.getElementById("your-profile");
	theform.first_name.readOnly=true;
	theform.last_name.readOnly=true;
	theform.email.readOnly=true;
</script>
EOD;
	print $script;
}

//----------------------------------------------------------------------------
//	Disable password reset
//----------------------------------------------------------------------------
function cosign_sso_no_password_reset()
{
	return FALSE;
}

//----------------------------------------------------------------------------
//		ADMIN OPTION PAGE FUNCTIONS
//----------------------------------------------------------------------------

function cosign_sso_options_page()
{
	global $wpversion, $cosign_sso_fallback;

	if (isset($_POST['submit']) ) {
		// Options Array Update
		$optionarray_update = array (
			'login_method' => (int) $_POST['login_method'],
			'sso_login_url' => $_POST['sso_login_url'],
			'sso_logout_url' => $_POST['sso_logout_url'],
			'sso_protocol' => $_POST['sso_protocol'],
			'sso_srv_name' => $_POST['sso_srv_name'],
			'auto_user' => $_POST['auto_user'],
			'default_role' => "administrator" == strtolower(trim($_POST['default_role']))? "" : $_POST['default_role'],
			'ldap_server' => $_POST['ldap_server'],
			'ldap_port' => $_POST['ldap_port'],
			'ldap_basedn' => $_POST['ldap_basedn'],
			'ldap_bind_dn' => $_POST['ldap_bind_dn'],
			'ldap_bind_passwd' => $_POST['ldap_bind_passwd'],
			'ldap_search_filter' => $_POST['ldap_search_filter'],
			'ldap_map_login' => $_POST['ldap_map_login'],
			'ldap_map_lastname' => $_POST['ldap_map_lastname'],
			'ldap_map_firstname' => $_POST['ldap_map_firstname'],
			'ldap_map_fullname' => $_POST['ldap_map_fullname'],
			'ldap_map_mail' => $_POST['ldap_map_mail'],
		);
		
		update_option('cosign_sso_options', $optionarray_update);
	}
	
	// Get Options
	$optionarray_def = get_option('cosign_sso_options');

	// Setup Feed Key Reset Options
	$cosign_login_types = array(
		__('Disabled', 'cosign_sso') => COSIGN_LOGIN_DISABLED,
		__('LDAP', 'cosign_sso') => COSIGN_LOGIN_LDAP,
		__('SSO', 'cosign_sso') => COSIGN_LOGIN_SSO,
	);

	$login_method_options = "";
	foreach ($cosign_login_types as $option => $value) {
		if ($value == (int)$optionarray_def['login_method']) {
			$selected = 'selected="selected"';
		} else {
			$selected = '';
		}

		$login_method_options .= "\n\t<option value='$value' $selected>$option</option>";
	}

?>
	<div class="wrap">
	<h2><?php _e("CoSign SSO Options", "cosign_sso"); ?></h2>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=' . basename(__FILE__); ?>&updated=true">
	<fieldset class="options" style="border: none">
	<p>
	<?php
	print __("Checking the <em>CoSign SSO</em> option below, it will change login method and will affect all users including yourself.<br />".
	         "<strong>Bad configuration will ban all users!!!</strong><br />".
				"If you are banned cause of the bad cosign sso login configurations, ".
				"simply create a file named <em>\"FALLBACK\"</em> (blank file or contains \"LDAP\") ".
				"under this plugin directory, or modify the database record by hands.",
				"cosign_sso");
	?>
	</p>
	<table width="100%" <?php echo $wpversion >= 2.5 ? 'class="form-table"' : 'cellspacing="2" cellpadding="5" class="editform"'; ?> >
		<tr valign="top">
			<th width="200px" scope="row"><?php _e("Login method", "cosign_sso"); ?></th>
			<td width="100px"><select name="login_method"><?php echo $login_method_options; ?></select></td>
			<td>
				<span style="color: #555; font-size: .85em;">
				<?php
					if($cosign_sso_fallback == COSIGN_LOGIN_DISABLED)
						printf("<strong>". __("CoSign SSO login disable by file '%s'. To enable CoSign SSO login, remove that file.", "cosign_sso") . "</strong><br />", COSIGN_SSO_FALLBACK_FILE);
					elseif($cosign_sso_fallback == COSIGN_LOGIN_LDAP)
						printf("<strong>". __("Fallback to use LDAP auth mode. To enable CoSign SSO login, remove the file '%s'.", "cosign_sso") . "</strong><br />", COSIGN_SSO_FALLBACK_FILE);
					echo _e("Choose to disable internale auth login and authenticate all users using CoSign SSO login service.", "cosign_sso");
				?>
				</span>
			</td>
		</tr>
	</table>
	</p>

	<h3><?php _e("CoSign SSO Options", "cosign_sso"); ?></h3>
	<table width="100%" <?php echo $wpversion >= 2.5 ? 'class="form-table"' : 'cellspacing="2" cellpadding="5" class="editform"'; ?> >
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("CoSign Login URL", "cosign_sso"); ?></th>
			<td width="100px" colspan="2"><input type="text" name="sso_login_url" size="50" value="<?php echo  $optionarray_def['sso_login_url']; ?>"></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("CoSign Logout URL", "cosign_sso"); ?></th>
			<td width="100px" colspan="2"><input type="text" name="sso_logout_url" size="50" value="<?php echo  $optionarray_def['sso_logout_url']; ?>"></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("CoSign Protocol", "cosign_sso"); ?></th>
			<td width="100px">
				<select name="sso_protocol">
					<?php
					$cosign_protocol_types = array(
						__("for cosign 2.x", "cosign_sso") => "2",
						__("for cosign 3.x", "cosign_sso") => "3",
					);

					foreach ($cosign_protocol_types as $option => $value) {
						if ($value == $optionarray_def['sso_protocol']) {
							$selected = 'selected="selected"';
						} else {
							$selected = '';
						}

						echo "\n\t<option value='$value' $selected>$option</option>";
					}
					?>
				</select>
			</td>
			<td>
				<span style="color: #555; font-size: .85em;">
				<?php
					printf("<strong>". __("CoSign 2.x and 3.x use different protocol, and not compatible. If you choose wrong CoSign protocol version, you can not login any more.", "cosign_sso") . "</strong>");
				?>
				</span>
			</td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("CoSign Service Name", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="sso_srv_name" size="18" value="<?php echo  $optionarray_def['sso_srv_name']; ?>"></td>
			<td>
				<span style="color: #555; font-size: .85em;">
				<?php
					printf("<strong>". __("Must match with the settings of cosign filter, and/or cosign daemon. If you are not sure, ask for it form webmaster.", "cosign_sso") . "</strong>");
				?>
				</span>
			</td>
		</tr>
	</table>

	<h3><?php _e("User Account", "cosign_sso"); ?></h3>
	<table width="100%" <?php echo $wpversion >= 2.5 ? 'class="form-table"' : 'cellspacing="2" cellpadding="5" class="editform"'; ?> >
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("Auto create user account", "cosign_sso"); ?></th>
			<td colspan="2">
				<input type="checkbox" name="auto_user" value="1" <?php checked('1', $optionarray_def['auto_user']); ?> />
				<?php echo __("new user as", "cosign_sso"); ?>
				<select name="default_role" id="role">
					<?php
					// print the full list of roles with the primary one selected.
					wp_dropdown_roles($optionarray_def['default_role']);

					// print the 'no role' option. Make it selected if the user has no role yet.
					if ( ! $optionarray_def['default_role'] )
					  echo '<option value="" selected="selected">' . __('&mdash; No role for this blog &mdash;', "cosign_sso") . '</option>';
					else
					  echo '<option value="">' . __('&mdash; No role for this blog &mdash;', "cosign_sso") . '</option>';
					?>
				</select>
			</td>
		</tr>
	</table>

	<h3><?php _e("LDAP Options", "cosign_sso"); ?></h3>
	<table width="100%" <?php echo $wpversion >= 2.5 ? 'class="form-table"' : 'cellspacing="2" cellpadding="5" class="editform"'; ?> >
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("LDAP hostname", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="ldap_server" size="18" value="<?php echo  $optionarray_def['ldap_server']; ?>"></td>
			<td></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("LDAP port", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="ldap_port" size="4" value="<?php echo  $optionarray_def['ldap_port']; ?>">
			</td>
			<td></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("LDAP BaseDN", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="ldap_basedn" size="50" value="<?php echo  $optionarray_def['ldap_basedn']; ?>"></td>
			<td></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("LDAP Bind Username", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="ldap_bind_dn" size="50" value="<?php echo  $optionarray_def['ldap_bind_dn']; ?>"></td>
			<td></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("LDAP Bind Password", "cosign_sso"); ?></th>
			<td width="100px"><input type="password" name="ldap_bind_passwd" size="50" value="<?php echo  $optionarray_def['ldap_bind_passwd']; ?>"></td>
			<td></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("LDAP Search Filter", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="ldap_search_filter" size="50" value="<?php echo  $optionarray_def['ldap_search_filter']; ?>"></td>
			<td></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("Login Name", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="ldap_map_login" size="18" value="<?php echo  $optionarray_def['ldap_map_login']; ?>"></td>
			<td></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("Given Name", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="ldap_map_firstname" size="18" value="<?php echo  $optionarray_def['ldap_map_firstname']; ?>"></td>
			<td></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("Surname", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="ldap_map_lastname" size="18" value="<?php echo  $optionarray_def['ldap_map_lastname']; ?>"></td>
			<td></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("Nick Name", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="ldap_map_fullname" size="18" value="<?php echo  $optionarray_def['ldap_map_fullname']; ?>"></td>
			<td></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("Email", "cosign_sso"); ?></th>
			<td width="100px"><input type="text" name="ldap_map_mail" size="18" value="<?php echo  $optionarray_def['ldap_map_mail']; ?>"></td>
			<td></td>
		</tr>
	</table>

	</fieldset>
	<p />
	<div class="submit">
		<input type="submit" name="submit" value="<?php _e('Update Options', "cosign_sso") ?> &raquo;" />
	</div>
	</form>
<?php
}

//----------------------------------------------------------------------------
//		WORDPRESS FILTERS AND ACTIONS
//----------------------------------------------------------------------------

//Registers a plugin function to be run when the plugin is activated. 
register_activation_hook(basename(dirname(__FILE__)) . '/' .  basename(__FILE__),'cosign_sso_activate');

//Registers a plugin function to be run when the plugin is deactivated. 
register_deactivation_hook(basename(dirname(__FILE__)) . '/' .  basename(__FILE__),'cosign_sso_deactivate');

add_action('admin_menu', 'cosign_sso_add_options_page');

// run activation function if new revision of plugin
if ( $cosign_sso_version === false || OPENID_PLUGIN_REVISION != get_option('cosign_sso_version') )
{
	add_action('admin_init', 'cosign_sso_activate');
}

if ( (int)$cosign_sso_opt['login_method'] > COSIGN_LOGIN_DISABLED
     && $cosign_sso_fallback > COSIGN_LOGIN_DISABLED )
{
	// Common hooks
	add_action('login_form_register', 'cosign_sso_register_disabled');
	add_action('personal_options', 'cosign_sso_profile_notes');
	add_action('show_user_profile', 'cosign_sso_profile_js');
	add_action('edit_user_profile', 'cosign_sso_profile_js');
	add_filter('show_password_fields', 'cosign_sso_no_password_reset', 10, 0);
	add_filter('allow_password_reset', 'cosign_sso_no_password_reset', 10, 0);

	if ( (int)$cosign_sso_opt['login_method'] == COSIGN_LOGIN_SSO &&
		  $cosign_sso_fallback != COSIGN_LOGIN_LDAP )
	{
		// Hoos for CoSign SSO
		add_filter('authenticate', 'cosign_sso_authenticate', 10, 1);
		add_action('login_form_login', 'cosign_sso_login_redirect');
		add_action('clear_auth_cookie', 'cosign_sso_clear_auth_cookie');
		add_action('wp_logout', 'cosign_sso_logout_redirect');
	}
	elseif ( (int)$cosign_sso_opt['login_method'] == COSIGN_LOGIN_LDAP ||
		      $cosign_sso_fallback == COSIGN_LOGIN_LDAP )
	{
		// Hoos for LDAP Login
		add_filter('authenticate', 'cosign_sso_authenticate', 10, 3);
	}
}

// vim: noet sw=3 ts=3
?>

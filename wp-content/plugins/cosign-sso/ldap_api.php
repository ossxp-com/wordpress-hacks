<?php
/**
 * This ldap api is copied and adapted from testlink, which may adapted from
 * mantis:
 *
 *  - TestLink - a php based testcase management system (GPL)
 *  - Mantis   - a php based bugtracking system (GPL)
 *
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * Revisions:
 * 	20091230 - Jiang Xin - config update, refactorization
 * 
 */

define( 'ERROR_LDAP_AUTH_FAILED',       1400 );
define( 'ERROR_LDAP_SERVER_CONNECT_FAILED',   1401 );
define( 'ERROR_LDAP_UPDATE_FAILED',       1402 );
define( 'ERROR_LDAP_USER_NOT_FOUND',      1403 );
define( 'ERROR_LDAP_BIND_FAILED',       1404 );
 
if ( !function_exists('is_blank') ) :
function is_blank( $p_var ) 
{
	$p_var = trim( $p_var );
	$str_len = strlen( $p_var );
	if ( 0 == $str_len ) {
		return true;
	}
	return false;
}
endif;

//---------------------------------------------------------------------------
// Connect and bind to the LDAP directory
//---------------------------------------------------------------------------
function cosign_sso_ldap_connect_bind( $p_binddn = '', $p_password = '' ) 
{
	global $cosign_sso_opt;

	$ret = new stdClass();
	$ret->status 	= 0;
	$ret->handler 	= null;
  
	$t_ds = ldap_connect ( $cosign_sso_opt['ldap_server'] ? $cosign_sso_opt['ldap_server'] : "localhost" , $cosign_sso_opt['ldap_port'] ? $cosign_sso_opt['ldap_port'] : 389 );
	
	// BUGID 1247
	ldap_set_option($t_ds, LDAP_OPT_PROTOCOL_VERSION, $cosign_sso_opt['ldap_version'] ? $cosign_sso_opt['ldap_version'] : 3 );
	ldap_set_option($t_ds, LDAP_OPT_REFERRALS, 0);

	if ( $t_ds > 0 ) 
	{
		$ret->handler=$t_ds;
  
		# If no Bind DN and Password is set, attempt to login as the configured
		#  Bind DN.
		if ( is_blank( $p_binddn ) && is_blank( $p_password ) ) 
		{
			$p_binddn	= $cosign_sso_opt['ldap_bind_dn'];
			$p_password	= $cosign_sso_opt['ldap_bind_passwd'];
		}

		if ( !is_blank( $p_binddn ) && !is_blank( $p_password ) ) 
		{
			$t_br = ldap_bind( $t_ds, $p_binddn, $p_password );
		} else {
			# Either the Bind DN or the Password are empty, so attempt an anonymous bind.
			$t_br = ldap_bind( $t_ds );
		}
		
		if ( !$t_br ) 
		{
			$ret->status = ERROR_LDAP_BIND_FAILED;
		}
	} 
	else 
	{
		$ret->status=ERROR_LDAP_SERVER_CONNECT_FAILED;
	}
 
	return $ret;
}

//---------------------------------------------------------------------------
// Attempt to authenticate the user against the LDAP directory
//---------------------------------------------------------------------------
function cosign_sso_ldap_authenticate( $p_login_name, $p_password ) 
{
	global $cosign_sso_opt;

	# if password is empty and ldap allows anonymous login, then
	# the user will be able to login, hence, we need to check
	# for this special case.
	if ( is_blank( $p_password ) ) {
		return false;
	}

	$t_authenticated = new stdClass();
	$t_authenticated->status_ok = TRUE;
	$t_authenticated->status_code = null;
	$t_ldap_search_filter = $cosign_sso_opt['ldap_search_filter'];
	$t_ldap_basedn        = $cosign_sso_opt['ldap_basedn'];
	$t_ldap_map_login     = $cosign_sso_opt['ldap_map_login'];	// 'uid' by default

	$t_username           = $p_login_name;
	$t_search_filter      = "(&$t_ldap_search_filter($t_ldap_map_login=$t_username))";
	$t_search_attrs       = array( $t_ldap_map_login, 'dn' );
	$t_connect            = cosign_sso_ldap_connect_bind();

	if( !is_null($t_connect->handler) )
	{
		$t_ds = $t_connect->handler;
	  
		# Search for the user id
		$t_sr	= ldap_search( $t_ds, $t_ldap_basedn, $t_search_filter, $t_search_attrs );
		$t_info	= ldap_get_entries( $t_ds, $t_sr );
 
		$t_authenticated->status_ok = false;
		$t_authenticated->status_code = ERROR_LDAP_AUTH_FAILED;
	  
		if ( $t_info ) {
			# Try to authenticate to each until we get a match
			for ( $i = 0 ; $i < $t_info['count'] ; $i++ ) {
				$t_dn = $t_info[$i]['dn'];
 
				# Attempt to bind with the DN and password
				if ( @ldap_bind( $t_ds, $t_dn, $p_password ) ) {
					$t_authenticated->status_ok = true;
					break; # Don't need to go any further
				}
			}
		}
 
		ldap_free_result( $t_sr );
		ldap_unbind( $t_ds );
	}
	else
	{
			$t_authenticated->status_ok = false;
			$t_authenticated->status_code = $t_connect->status;
	}
	
	return $t_authenticated;
}

//---------------------------------------------------------------------------
// Read user attributes from the LDAP directory
//---------------------------------------------------------------------------
function cosign_sso_ldap_fetch_account( $login_name ) 
{
	global $cosign_sso_opt;

	$account = array();

	$t_ldap_search_filter = $cosign_sso_opt['ldap_search_filter'];
	$t_ldap_basedn        = $cosign_sso_opt['ldap_basedn'];
	$t_ldap_map_login     = strtolower( $cosign_sso_opt['ldap_map_login'] );	// 'uid' by default
	$t_ldap_map_firstname = strtolower( $cosign_sso_opt['ldap_map_firstname'] );	// 'givenName' by default
	$t_ldap_map_lastname  = strtolower( $cosign_sso_opt['ldap_map_lastname'] );	// 'sn' by default
	$t_ldap_map_fullname  = strtolower( $cosign_sso_opt['ldap_map_fullname'] );	// 'cn' by default
	$t_ldap_map_mail      = strtolower( $cosign_sso_opt['ldap_map_mail'] );	// 'mail' by default

	$t_username           = $login_name;
	$t_search_filter      = "(&$t_ldap_search_filter($t_ldap_map_login=$t_username))";
	$t_search_attrs       = array( 'dn',
											 $t_ldap_map_firstname,
											 $t_ldap_map_lastname,
											 $t_ldap_map_fullname,
											 $t_ldap_map_mail );
	$t_connect            = cosign_sso_ldap_connect_bind();

	if( !is_null($t_connect->handler) )
	{
		$t_ds = $t_connect->handler;

		# Search for the user id
		$t_sr = ldap_search( $t_ds, $t_ldap_basedn, $t_search_filter, $t_search_attrs );
		$t_info = ldap_get_entries( $t_ds, $t_sr );

		if ( $t_info ) {
			# Try to authenticate to each until we get a match
			$account['firstname'] = in_array($t_ldap_map_firstname, $t_info[0]) ? $t_info[0][$t_ldap_map_firstname][0] : '';
			$account['lastname'] = in_array($t_ldap_map_lastname, $t_info[0]) ? $t_info[0][$t_ldap_map_lastname][0] : '';
			$account['fullname'] = in_array($t_ldap_map_fullname, $t_info[0]) ? $t_info[0][$t_ldap_map_fullname][0] : '';
			$account['mail'] = in_array($t_ldap_map_mail, $t_info[0]) ? $t_info[0][$t_ldap_map_mail][0] : '';
		}

		ldap_free_result( $t_sr );
		ldap_unbind( $t_ds );
	}

	return $account;
}

// vim: noet sw=3 ts=3
?>

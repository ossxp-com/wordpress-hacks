<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	exit();

delete_option('cosign_sso_options');
delete_option('cosign_sso_version');

// vim: noet sw=3 ts=3
?>

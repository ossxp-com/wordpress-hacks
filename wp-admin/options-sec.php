<?php
/**
 * Options Management Administration Panel.
 *
 * Just allows for displaying of options.
 *
 * This isn't referenced or linked to, but will show all of the options and
 * allow editing. The issue is that serialized data is not supported to be
 * modified. Options can not be removed.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once('admin.php');

$title = __('Security Settings');
$this_file = 'options-sec.php';
$parent_file = 'options-security.php';

if ( !current_user_can('manage_options') )
	wp_die(__('Cheatin&#8217; uh?'));

$role = get_role('administrator');
$change_ok =  false;

if(empty($role))
	wp_die(__("Something went wrong. Please try again"));

if (!isset($_POST["unfiltered_upload"]) && $_POST["unf_up"]=="true"){
	$role->remove_cap('unfiltered_upload');
	$change_ok = true;
	}
else if (isset($_POST["unfiltered_upload"]) && $_POST["unf_up"]=="false"){
	$role->add_cap('unfiltered_upload');
	$change_ok = true;
	}

$wp_rewrite->flush_rules();

wp_cache_flush();

$goback = add_query_arg( 'updated', $change_ok, wp_get_referer() );
wp_redirect( $goback );

include('admin-footer.php');
?>

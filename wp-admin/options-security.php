<?php
/**
 * General settings administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once('./admin.php');

$title = __('Security Settings');
$parent_file = 'options-security.php';

/**
 * Display JavaScript on the page.
 *
 * @package WordPress
 * @subpackage General_Settings_Panel
 */
function add_js() {
?>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function($){
		$("input[name='date_format']").click(function(){
			if ( "date_format_custom_radio" != $(this).attr("id") )
				$("input[name='date_format_custom']").val( $(this).val() );
		});
		$("input[name='date_format_custom']").focus(function(){
			$("#date_format_custom_radio").attr("checked", "checked");
		});

		$("input[name='time_format']").click(function(){
			if ( "time_format_custom_radio" != $(this).attr("id") )
				$("input[name='time_format_custom']").val( $(this).val() );
		});
		$("input[name='time_format_custom']").focus(function(){
			$("#time_format_custom_radio").attr("checked", "checked");
		});
	});
//]]>
</script>
<?php
}
add_filter('admin_head', 'add_js');

include('./admin-header.php');
?>

<div class="wrap">
<?php screen_icon(); ?>
<h2><?php echo wp_specialchars( $title ); ?></h2>

<form method="post" action="options-sec.php">
<?php settings_fields('general'); ?>

<table class="form-table">
<tr valign="top">
<th scope="row"><?php _e('Unfiltered upload') ?></th>
<td> <fieldset><legend class="hidden"><?php _e('Unfiltered upload') ?></legend><label for="unfiltered_upload">
<input name="unfiltered_upload" type="checkbox" id="unfiltered_upload" value="1" <?php checked('1', get_role('administrator')->has_cap('unfiltered_upload')); ?> />
<?php _e('Admins can upload any type of file') ?></label>
<?php
$unf_up = get_role("administrator")->has_cap("unfiltered_upload");
if (!$unf_up)
	print('<input name="unf_up" type="hidden" value="false">');
else
	print('<input name="unf_up" type="hidden" value="true">');
?>
</fieldset></td>
</tr>
</table>
<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>

</div>

<?php include('./admin-footer.php') ?>

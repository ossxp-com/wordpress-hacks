<?php

require_once("../wp-config.php");

function my_wp_get_archives($limit)
{
    $show_post = "type=postbypost&limit=".$limit;
    return wp_get_archives($show_post); 
}

$limit = $_REQUEST['limit'];
echo my_wp_get_archives($limit);

// vim: et ts=4 sw=4
?>

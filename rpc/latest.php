<?php

require_once("../wp-config.php");

function my_wp_get_archives($limit)
{
    $callback = $_REQUEST['callback'];
    $args = array('type' => 'postbypost',
                  'limit' => $limit,
                  'json' => true,
                 );
    return $callback."(".wp_get_archives($args).")";
}

$limit = $_REQUEST['limit'];
header('Content-Type: application/x-javascript; charset=utf-8');
echo my_wp_get_archives($limit);

// vim: et ts=4 sw=4
?>

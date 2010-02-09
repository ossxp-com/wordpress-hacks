<?php

require_once("../wp-config.php");

$limit = $_REQUEST['limit'];
$callback = $_REQUEST['callback'];

$args = 'showposts='.$limit.'&orderby=date&order=DESC';

# cat in args is versatile. can be a list of categories id seperated by
# comma. id is a int, and can be negetive, ...
# category_name in args refers to only one category, and convert to id before 
# use.
if (!empty($_REQUEST['cat_id'])) {
    $args .= '&cat='.$_REQUEST['cat_id'];
} elseif (!empty($_REQUEST['cat_name'])) {
    $args .= '&category_name='.$_REQUEST['cat_name'];
} elseif (!empty($_REQUEST['cat'])) {
    $cat = $_REQUEST['cat'];
    if (strpos($cat, ',') !== false) {
        $args .= '&cat='.$cat;
    } elseif (is_numeric($cat) === TRUE && (int)$cat==$cat) {
        $args .= '&cat='.$cat;
    } else {
        $args .= '&category_name='.$cat;
    }
}

# tag_id in args is a int, only refers to one tag
# tag in args is versatile, can be a list of tags name.
if (!empty($_REQUEST['tag_id'])) {
    $args .= '&tag_id='.$_REQUEST['tag_id'];
} elseif (!empty($_REQUEST['tag_name'])) {
    $args .= '&tag='.$_REQUEST['tag_name'];
} elseif (!empty($_REQUEST['tag'])) {
    $tag = $_REQUEST['tag'];
    if (strpos($cat, ',') !== false) {
        $args .= '&tag='.$tag;
    } elseif (is_numeric($tag) === TRUE && (int)$tag==$tag) {
        $args .= '&tag_id='.$tag;
    } else {
        $args .= '&tag='.$tag;
    }
}

# offset of blog items.
if (!empty($_REQUEST['offset'])) {
    $args .= '&offset='.$_REQUEST['offset'];
}

# datetime format
if (!empty($_REQUEST['time_fmt']))
    $time_fmt = $_REQUEST['time_fmt'];
else
    $time_fmt = "Y-m-d";

header('Content-Type: application/x-javascript; charset=utf-8');

$query = new WP_Query($args);
$results = array();
while ($query->have_posts()) {
     $query->the_post();
     $text = get_the_title();
     $url = apply_filters('the_permalink', get_permalink());
     $time = apply_filters('the_time', get_the_time( $time_fmt ), $time_fmt);
     $results[] = array('url'=>$url, 'title'=>$text, 'time'=>$time);
     $count++;
     if ($count >= $limit)
         break;
}

echo $callback."(".json_encode($results).")";

// vim: et ts=4 sw=4
?>

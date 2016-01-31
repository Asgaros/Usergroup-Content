<?php

define( 'WP_USE_THEMES', false );
require( explode( "wp-content" , __FILE__ )[0] . "wp-load.php" );

$img = $_GET['img'];
$parts = explode( "/", $img );

global $wpdb;
$query = $wpdb->prepare(
   "SELECT postmeta.meta_value, posts.post_mime_type
FROM {$wpdb->prefix}postmeta AS postmeta
INNER JOIN {$wpdb->prefix}posts AS posts
ON postmeta.post_id = posts.id
WHERE postmeta.meta_value LIKE '%s'
AND postmeta.meta_key = '_wp_attachment_metadata'",
   '%' . end( $parts ) . '%' );

$result = $wpdb->get_row( $query, ARRAY_A );

if( ! $result ) {
   die("No such image!");
}

$attachment = maybe_unserialize( $result['meta_value'] );
$mime_type = $result['post_mime_type'];

if( $img == $attachment['file'] ) {
   header( 'Content-Type: ' . $mime_type );
   readfile( wp_upload_dir()['basedir'] . '/' . $attachment['file'] );
} else {
   $res = '';
   foreach( $attachment['sizes'] as $version ) {
      if( $version['file'] == end( $parts ) ) {
         $res = $version;
         break;
      }
   }

   header( 'Content-Type: ' . $res['mime-type'] );
   readfile( wp_upload_dir()['basedir'] . '/' . implode( '/', array_slice( $parts, 0, -1 ) ) . '/' . $res['file'] );
}

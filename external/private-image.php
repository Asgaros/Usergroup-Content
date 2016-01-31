<?php

define( 'WP_USE_THEMES', false );
require( explode( "wp-content" , __FILE__ )[0] . "wp-load.php" );

$img = $_GET['img'];
$parts = explode( '/', $img );

global $wpdb;

$querystring = "SELECT postmeta.meta_value, posts.post_mime_type
      FROM {$wpdb->prefix}postmeta AS postmeta
      INNER JOIN {$wpdb->prefix}posts AS posts
      ON postmeta.post_id = posts.id
      WHERE postmeta.meta_value LIKE '%s'
      AND postmeta.meta_key = '_wp_attachment_metadata'";
$searchstring = '%' . $img . '%';

$query = $wpdb->prepare( $querystring, $searchstring );
$result = $wpdb->get_row( $query, ARRAY_A );

//var_dump( maybe_unserialize( $result['meta_value'] )['file'] != $img );

// If empty result, maybe we have a thumbnail image
if( $result === false ) {
   $searchstring = '%' . end( $parts ) . '%';
   $query = $wpdb->prepare( $querystring, $searchstring );
   $results = $wpdb->get_results( $query, ARRAY_A );

   $result = false;
   foreach( $results as $r ) {
      $metadata = maybe_unserialize( $r['meta_value'] );

      foreach( $metadata['sizes'] as $size ) {
         if( $size['file'] == end( $parts ) ) {
            $result = $r;

            // We are where we want to be so we break both foreach loops...
            break 2;
         }
      }
   }
}

if( $result === false ) {
   die( __( "No such image". "usergroup-content" ) );
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

   $upload_dir = implode( '/', array_slice( explode( '/', $attachment['file'] ), 0, -1 ) );

   if( ! empty( $res ) && ( $upload_dir == '' || strpos( $img, $upload_dir . '/' ) !== false ) ) {
      header( 'Content-Type: ' . $res['mime-type'] );
      readfile( wp_upload_dir()['basedir'] . '/' . $img );
   } else {
      die( __( "No such image!", "usergroup-content" ) );
   }
}

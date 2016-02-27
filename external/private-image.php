<?php

define( 'WP_USE_THEMES', false );
require( explode( "wp-content" , __FILE__ )[0] . "wp-load.php" );

function uc_no_such_image () {
   header( 'Content-Type: ' . 'image/png' );
   readfile( plugin_dir_url(__FILE__) . 'no-such-image.png' );
}

$img = $_GET['img'];
$parts = explode( '/', $img );

global $wpdb;

$querystring = "SELECT posts.id, postmeta.meta_value, posts.post_mime_type
      FROM {$wpdb->prefix}postmeta AS postmeta
      INNER JOIN {$wpdb->prefix}posts AS posts
      ON postmeta.post_id = posts.id
      WHERE postmeta.meta_value LIKE '%s'
      AND postmeta.meta_key = '_wp_attachment_metadata'";
$searchstring = '%' . $img . '%';

$query = $wpdb->prepare( $querystring, $searchstring );
$result = $wpdb->get_row( $query, ARRAY_A );

// If $result is empty, perhaps we have a thumbnail image
if( $result === null ) {
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

// No such image
if( empty( $result ) ) {
   uc_no_such_image();
}

// Test if current user has the permission to display attachment
function uc_is_permitted() {
   global $uc_usergroups;
   global $result;
   $is_permitted = false;
   foreach ( $uc_usergroups->get_usergroups_for_user( wp_get_current_user()->ID ) as $group ) {
      if( in_array( $group->term_id,  $uc_usergroups->get_usergroups_for_post( $result['id'] ) ) ) {
         $is_permitted = true;
         break;
      }
   }
   if( current_user_can( 'manage_options' ) ) {
      $is_permitted = true;
   }
   return $is_permitted;
}

$attachment = maybe_unserialize( $result['meta_value'] );
$mime_type = $result['post_mime_type'];

// Show attachment or thumbnail
if( $img == $attachment['file'] ) {
   // We have an attachment
   if( uc_is_permitted() ) {
      header( 'Content-Type: ' . $mime_type );
      readfile( wp_upload_dir()['basedir'] . '/' . $attachment['file'] );
   } else {
      uc_no_such_image();
   }
} else {
   // We have a thumbnail
   $res = '';
   foreach( $attachment['sizes'] as $version ) {
      if( $version['file'] == end( $parts ) ) {
         $res = $version;
         break;
      }
   }

   $upload_dir = implode( '/', array_slice( explode( '/', $attachment['file'] ), 0, -1 ) );

   // Check for upload_dir
   if( ! empty( $res ) && ( $upload_dir == '' || strpos( $img, $upload_dir . '/' ) !== false ) ) {
      if( uc_is_permitted() ) {
         header( 'Content-Type: ' . $res['mime-type'] );
         readfile( wp_upload_dir()['basedir'] . '/' . $img );
      } else {
         uc_no_such_image();
      }
   } else {
      uc_no_such_image();
   }
}

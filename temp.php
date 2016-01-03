<?php
set_time_limit( 0);
ob_implicit_flush( 1);
//ini_set( 'memory_limit', '4000M');
for ( $prefix = is_dir( 'ajaxkit') ? 'ajaxkit/' : ''; ! is_dir( $prefix) && count( explode( '/', $prefix)) < 4; $prefix .= '../'); if ( ! is_file( $prefix . "env.php")) $prefix = '/web/ajaxkit/'; 
if ( ! is_file( $prefix . "env.php") && ! is_file( 'requireme.php')) die( "\nERROR! Cannot find env.php in [$prefix] or requireme.php in [.], check your environment! (maybe you need to go to ajaxkit first?)\n\n");
if ( is_file( 'requireme.php')) require_once( 'requireme.php'); else foreach ( explode( ',', ".,$prefix") as $p) foreach ( array( 'functions', 'env') as $k) if ( is_dir( $p) && is_file( "$p/$k.php")) require_once( "$p/$k.php");
//htg( clget( ''));

$where = '/test';
$what = s2s64( implode( '', file( 'test.py')));
$how = s2s64( 'python');
$url = "http://131.206.29.204:8001/api.php?action=exec&one=$where&two=$what&three=$how";
echo "$url\n";

?>
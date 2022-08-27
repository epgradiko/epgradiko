#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once('../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );
run_user_regulate();
new single_Program('del_everyday');
$settings = Settings::factory();

if( $argc == 1 ) exit();
$del_date = date('Y-m-d', strtotime("- ".$argv[1]." days"));

$rev_obj = new DBRecord( RESERVE_TBL );
$trans_obj = new DBRecord( TRANSCODE_TBL );

$del_list = $rev_obj->fetch_array( null, null, 'complete=1 and endtime < date("'.$del_date.'")' );

foreach( $del_list as $rec ){
	$transcodes = $trans_obj->fetch_array( null, null, 'rec_id='.$rec['id'].' ORDER BY status' );
	foreach( $transcodes as $transcode ){
		if( $transcode['status'] == 1){
			killtree( $rarr, (int)$transcode['pid'] );
			sleep(1);
		}
		$trans_obj->force_delete( $transcode['id'] );
		@unlink( $transcode['path'] );
		@unlink( INSTALL_PATH.settings->plogs.'/'.$rec['id'].'_'.$transcode['id'].'.ffmpeglog' );
	}
	// 予約取り消し実行
	try {
		$ret_code = Reservation::cancel( $rec['id'], TRUE );
	}catch( Exception $e ){
	// 無視
	}
}
?>

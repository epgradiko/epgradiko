<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );

if( ! isset( $_GET['program_id'] ) ) exit('Error: 番組が指定されていません' );
$program_id = $_GET['program_id'];

$settings = Settings::factory();
$mode     = (int)$settings->simplerec_mode;

try {
	$rval = Reservation::simple( $program_id , 0, $mode, ((int)$settings->force_cont_rec===0 ? 1 : 0) );
}
catch( Exception $e ) {
	exit( 'Error:'. $e->getMessage() );
}
if( isset( $RECORD_MODE[$mode]['tsuffix'] ) ){
	// 手動予約のトラコン設定
	list( , , $rec_id, ) = explode( ':', $rval );
	$tex_obj = new DBRecord( TRANSEXPAND_TBL );
	$tex_obj->key_id  = 0;
	$tex_obj->type_no = $rec_id;
	$tex_obj->mode    = $mode;
	$tex_obj->ts_del  = 1;
	$tex_obj->dir     = $settings->simplerec_trans_dir;
	$tex_obj->update();
}
exit( $rval );
?>

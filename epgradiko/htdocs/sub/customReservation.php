<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once( INSTALL_PATH . "/include/DBRecord.class.php" );
include_once( INSTALL_PATH . "/include/reclib.php" );
include_once( INSTALL_PATH . "/include/Reservation.class.php" );
include_once( INSTALL_PATH . "/include/Settings.class.php" );

$settings = Settings::factory();

$program_id = isset( $_POST['program_id'] ) ? (int)$_POST['program_id'] : 0;
$reserve_id = isset( $_POST['reserve_id'] ) ? (int)$_POST['reserve_id'] : 0;
$recorder = isset( $_POST['recorder'] ) ? $_POST['recorder'] : '';
$channel_disc = isset( $_POST['channel_disc'] ) ? $_POST['channel_disc'] : '';
$mirakc_timeshift_id = isset( $_POST['mirakc_timeshift_id'] ) ? (int)$_POST['mirakc_timeshift_id'] : 0;
$key_id = isset( $_POST['key_id'] ) ? (int)$_POST['key_id'] : 0;
if(!(
   isset($_POST['mode'])	&& 
   isset($_POST['shour'])	&& 
   isset($_POST['smin'])	&&
   isset($_POST['smonth'])	&&
   isset($_POST['sday'])	&&
   isset($_POST['syear'])	&&
   isset($_POST['ehour'])	&&
   isset($_POST['emin'])	&&
   isset($_POST['emonth'])	&&
   isset($_POST['eday'])	&&
   isset($_POST['eyear'])	&&
   isset($_POST['channel_id'])	&&
   isset($_POST['title'])	&&
   isset($_POST['pre_title'])	&&
   isset($_POST['post_title'])	&&
   isset($_POST['description'])	&&
   isset($_POST['rec_dir'])	&&
   isset($_POST['trans_dir'])	&&
   isset($_POST['category_id']) &&
   isset($_POST['record_mode']) &&
   isset($_POST['discontinuity']) &&
   isset($_POST['priority'])
)) {
	exit("Error:予約に必要な値がセットされていません");
}

$mode = $_POST['mode'];

$start_time = @mktime( $_POST['shour'], $_POST['smin'], $_POST['ssec'], $_POST['smonth'], $_POST['sday'], $_POST['syear'] );
if( ($start_time < 0) || ($start_time === false) ) {
	exit("Error:開始時間が不正です" );
}
$end_time = @mktime( $_POST['ehour'], $_POST['emin'], $_POST['esec'], $_POST['emonth'], $_POST['eday'], $_POST['eyear'] );
if( ($end_time < 0) || ($end_time === false) ) {
	exit("Error:終了時間が不正です" );
}

$channel_id = $_POST['channel_id'];
$title = $_POST['title'];
$pre_title = $_POST['pre_title'];
$post_title = $_POST['post_title'];
$description = $_POST['description'];
$rec_dir = $_POST['rec_dir'];
$trans_dir = $_POST['trans_dir'];
$category_id = $_POST['category_id'];
$record_mode = $_POST['record_mode'];
$discontinuity = $_POST['discontinuity'];
$priority = $_POST['priority'];

if( $rec_dir ){
	$chk_dir = INSTALL_PATH.$settings->spool.'/'.$rec_dir;
	if( ! file_exists( $chk_dir ) ) exit( "Error:録画ディレクトリがありません");
	else if( ! is_dir( $chk_dir ) ) exit( "Error:録画ディレクトリがディレクトリではありません");
}

if( $trans_dir ){
	$chk_dir = str_replace( '%VIDEO%', INSTALL_PATH.$settings->spool, TRANS_ROOT ).'/'.$trans_dir;
	if( ! file_exists( $chk_dir ) ) exit( "Error:変換後ディレクトリがありません");
	else if( ! is_dir( $chk_dir ) ) exit( "Error:変換後ディレクトリがディレクトリではありません");
}

$rval = 0;
try{
	switch( $mode ){
	case 'T':
	case 'F':
		$rval = Reservation::past_rec(
				$mode,
				$recorder,
				$mirakc_timeshift_id,
				$start_time,
				$end_time,
				$channel_disc,
				$title,
				$pre_title,
				$post_title,
				$description,
				$category_id,
				$program_id,
				0,
				$record_mode,
				$discontinuity,
				1,
				$priority,
				$rec_dir,
				0,
				0,
				0,
				0,
		);
		break;
	case 'P':
		$rval = Reservation::custom(
			toDatetime($start_time),
			toDatetime($end_time),
			$channel_id,
			$title,
			$pre_title,
			$post_title,
			$description,
			$category_id,
			$program_id,
			0,		// 自動録画
			$record_mode,	// 録画モード
			$discontinuity,
			1,		// ダーティフラグ
			$priority,
			$rec_dir,
		);
		break;
	case 'R':
		$rval = Reservation::update(
			$reserve_id,
			$title,
			$pre_title,
			$post_title,
			$description,
			$category_id,
			$record_mode,	// 録画モード
			$discontinuity,
			1,		// ダーティフラグ
			$rec_dir,
		);
		exit($ravl);
		break;
	}
}
catch( Exception $e ) {
	exit( "Error:".$e->getMessage() );
}
if( $key_id == 0 ){
	if( isset( $RECORD_MODE[$record_mode]['tsuffix'] ) ){
		// 手動予約のトラコン設定
		list( , , $rec_id, ) = explode( ':', $rval );
		$tex_obj = new DBRecord( TRANSEXPAND_TBL );
		$tex_obj->key_id  = 0;
		$tex_obj->type_no = $rec_id;
		$tex_obj->mode    = $record_mode;
		$tex_obj->ts_del  = isset($_POST['ts_del']) ? $_POST['ts_del'] : 0;
		$tex_obj->dir     = $trans_dir;
		$tex_obj->update();
	}
}
exit( $rval );
?>

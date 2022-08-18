<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . "/include/DBRecord.class.php" );
include_once( INSTALL_PATH . "/Smarty/Smarty.class.php" );
include_once( INSTALL_PATH . "/include/reclib.php" );

$week_tb = array( "日", "月", "火", "水", "木", "金", "土" );

if( ! isset( $_GET['reserve_id'] ) )
	exit("Error: 予約IDが指定されていません" );
$reserve_id = $_GET['reserve_id'];

try {
	$rec = new DBRecord( RESERVE_TBL, "id" , $reserve_id );
	$start_time = toTimestamp($rec->starttime);
	$end_time   = toTimestamp($rec->endtime);
	$duration   = $end_time - $start_time;
	$settings   = Settings::factory();
	$autorec = isset( $_GET['autorec'] ) ? $_GET['autorec'] : '0';
	$smarty = new Smarty();
	$smarty->template_dir = INSTALL_PATH . "/templates/";
	$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
	$smarty->cache_dir = INSTALL_PATH . "/cache/";
	$smarty->assign( "type", $rec->type );
	$smarty->assign( "channel", $rec->channel );
	$smarty->assign( "date", date( "Y年m月d日(", $start_time ).$week_tb[date( "w", $start_time )].')' );
	$smarty->assign( "starttime", date( "H:i", $start_time ) );
	$smarty->assign( "endtime", date( "H:i", $end_time ) );
	$smarty->assign( "duration", $duration%60>0 ? ((int)($duration/60)).'分'.($duration%60).'秒' : ($duration/60).'分' );
	$smarty->assign( "title", $rec->title );
	$smarty->assign( "reserve_id", $reserve_id );
	$smarty->assign( "autorec", $autorec );
	$smarty->display("cancelReservationForm.html");
}
catch( exception $e ) {
	exit( "Error:". $e->getMessage() );
}
?>

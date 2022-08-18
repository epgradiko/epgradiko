<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . "/include/DBRecord.class.php" );
include_once( INSTALL_PATH . "/Smarty/Smarty.class.php" );
include_once( INSTALL_PATH . "/include/reclib.php" );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
$settings = Settings::factory();

if( ! isset( $_GET['program_id'] ) ) exit("Error: 番組IDが指定されていません" );
$program_id = $_GET['program_id'];

$keyword_id = isset( $_GET['keyword_id'] ) ? (int)$_GET['keyword_id'] : 0;

try {
  $prec = new DBRecord( PROGRAM_TBL, "id", $program_id );
  if( $keyword_id ){
	$keyc = new DBRecord( KEYWORD_TBL, "id", $keyword_id );
	$start_time    = toTimestamp( $prec->starttime )  + $keyc->sft_start;
	$starttime     = toDatetime( $start_time );
	$endtime       = toDatetime( (boolean)$keyc->duration_chg ? $start_time+(int)$keyc->sft_end :  toTimestamp( $prec->endtime )+(int)$keyc->sft_end );
	$autorec_mode  = (int)$keyc->autorec_mode;
	$discontinuity = (boolean)$keyc->discontinuity ? ' checked="checked"' : '';
  }else{
	$starttime     = $prec->starttime;
	$endtime       = $prec->endtime;
	$autorec_mode  = (int)$settings->normalrec_mode;
	$discontinuity = $settings->force_cont_rec!=1 ? ' checked="checked" disabled' : '';
  }

  sscanf( $starttime, "%4d-%2d-%2d %2d:%2d:%2d", $syear, $smonth, $sday, $shour, $smin, $ssec );
  sscanf( $endtime, "%4d-%2d-%2d %2d:%2d:%2d", $eyear, $emonth, $eday, $ehour, $emin, $esec );
  
  $crecs = DBRecord::createRecords( CATEGORY_TBL );
  $cats = array();
  foreach( $crecs as $crec ) {
	$cat = array();
	$cat['id'] = $crec->id;
	$cat['name'] = $crec->name_jp;
	$cat['selected'] = $prec->category_id == $cat['id'] ? "selected" : "";
	
	array_push( $cats , $cat );
  }
  
  $smarty = new Smarty();
  $smarty->template_dir = INSTALL_PATH . "/templates/";
  $smarty->compile_dir = INSTALL_PATH . "/templates_c/";
  $smarty->cache_dir = INSTALL_PATH . "/cache/";
 
  $smarty->assign( "syear", $syear );
  $smarty->assign( "smonth", $smonth );
  $smarty->assign( "sday", $sday );
  $smarty->assign( "shour", $shour );
  $smarty->assign( "smin" ,$smin );
  $smarty->assign( "ssec" ,$ssec );
  $smarty->assign( "eyear", $eyear );
  $smarty->assign( "emonth", $emonth );
  $smarty->assign( "eday", $eday );
  $smarty->assign( "ehour", $ehour );
  $smarty->assign( "emin" ,$emin );
  $smarty->assign( "esec" ,$esec );
  
  $smarty->assign( "type", $prec->type );
  $smarty->assign( "channel", $prec->channel );
  $smarty->assign( "channel_id", $prec->channel_id );
  $smarty->assign( "record_mode" , $RECORD_MODE );
  $smarty->assign( "autorec_mode" , $autorec_mode );
  $smarty->assign( "discontinuity" , $discontinuity );
  $smarty->assign( 'priority', MANUAL_REV_PRIORITY );
  
  $smarty->assign( "title", $prec->title );
  $smarty->assign( "pre_title", $prec->pre_title );
  $smarty->assign( "post_title", $prec->post_title );
  $smarty->assign( "description", $prec->description );
  
  $smarty->assign( "rec_dir", $settings->normalrec_dir );
  $smarty->assign( "trans_dir", $settings->normalrec_trans_dir );

  $smarty->assign( "cats" , $cats );
  
  $smarty->assign( "program_id", $prec->id );
  
  $smarty->display("reservationform.html");
}
catch( exception $e ) {
	exit( "Error:". $e->getMessage() );
}
?>

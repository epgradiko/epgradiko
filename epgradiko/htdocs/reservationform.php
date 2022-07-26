<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . "/include/DBRecord.class.php" );
include_once( INSTALL_PATH . "/Smarty/Smarty.class.php" );
include_once( INSTALL_PATH . "/include/reclib.php" );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
$settings = Settings::factory();

if( !isset($_GET['program_id']) && !isset($_GET['reserve_id']) && !isset($_GET['recorder'])) exit("Error: 番組IDが指定されていません" );

if( isset( $_GET['program_id'] )){
	$program_id = (int)$_GET['program_id'];
	$mode = 'P';
}else if( isset( $_GET['reserve_id'] )){
	$reserve_id = (int)$_GET['reserve_id'];
	$mode = 'R';
}else if( isset( $_GET['recorder'] )){
	$recorder = $_GET['recorder'];
	$mode = 'T';
}

$timeshift_id = isset( $_GET['timeshift_id'] ) ? (int)$_GET['timeshift_id'] : 0;
$pgm_id = isset( $_GET['pgm_id'] ) ? (int)$_GET['pgm_id'] : 0;
$keyword_id = isset( $_GET['keyword_id'] ) ? (int)$_GET['keyword_id'] : 0;

if( $pgm_id ){
	$programs = DBRecord::createRecords( PROGRAM_TBL, 'WHERE id='.$pgm_id );
	if( count($programs) ) $program_id = $programs[0]->id;
}

if( isset($program_id) && $program_id ){
	$program = new DBRecord( PROGRAM_TBL, "id", $program_id );
	$starttime	= $program->starttime;
	$endtime	= $program->endtime;

	$category_id 	= $program->category_id;

	$type		= $program->type;
	$channel	= $program->channel;
	$channel_id	= $program->channel_id;
	$channel_disc	= $program->channel_disc;

	$title		= $program->title;
	$pre_title	= $program->pre_title;
	$post_title	= $program->post_title;
	$description	= $program->description;

	$autorec	= $program->autorec;
}
try {
	switch($mode){
		case 'P':
			$autorec_mode  = (int)$settings->normalrec_mode;
			$discontinuity = $settings->force_cont_rec!=1 ? ' checked="checked" disabled' : '';
			$priority = MANUAL_REV_PRIORITY;
			$rec_dir = $settings->normalrec_dir;
			if( $keyword_id ){
				$keyword = new DBRecord( KEYWORD_TBL, "id", $keyword_id );
				$start_time	= toTimestamp( $program->starttime )  + $keyword->sft_start;
				$starttime	= toDatetime( $start_time );
				$endtime	= toDatetime( (boolean)$keyword->duration_chg ? $start_time+(int)$keyword->sft_end :  toTimestamp( $program->endtime )+(int)$keyword->sft_end );
				$autorec_mode	= (int)$keyword->autorec_mode;
				$discontinuity	= (boolean)$keyword->discontinuity ? ' checked="checked"' : '';
				$priority	= (int)$keyword->priority;
				$rec_dir	= $keyword->directory;
			}
  
			$trans_dir = $settings->normalrec_trans_dir;

			$complete = 0;

			$recorder = '';
			$timeshift_id = 0;

			break;
		case 'R':
			$reserve = new DBRecord( RESERVE_TBL, "id", $reserve_id );
			$starttime = $reserve->starttime;
			$endtime = $reserve->endtime;
			$autorec_mode = $reserve->mode;
			$autorec = $reserve->autorec;
			$discontinuity = (boolean)$reserve->discontinuity ? ' checked="checked"' : '';
			$category_id = $reserve->category_id;

			$type = $reserve->type;
			$channel = $reserve->channel;
			$channel_id = $reserve->channel_id;
			$channel_disc = $reserve->channel_disc;

			$priority = $reserve->priority;

			$title = $reserve->title;
			$pre_title = $reserve->pre_title;
			$post_title = $reserve->post_title;
			$description = $reserve->description;

			$rec_dir = dirname( $reserve->path );

			$trans_dir = $settings->normalrec_trans_dir;
			if( $reserve->autorec ) $where_str = 'WHERE key_id='.$reserve->autorec;
			else $where_str = 'WHERE key_id=0 AND type_no='.$reserve->id;
			$transcodes = DBRecord::createRecords( TRANSCODE_TBL, $where_str );
			if( $transcodes ) $trans_dir = dirname(str_replace( str_replace('%VIDEO%', INSTALL_PATH.$settings->spool, TRANS_ROOT), '', $transcodes[0]->path ));

			$program_id = $reserve->program_id;
			$complete = $reserve->complete;

			$recorder = '';
			$timeshift_id = 0;

			break;
		case 'T':
			$ts_base_addr = 'http://'.$settings->timeshift_address.'/api/timeshift/';
			$channel_raw = json_decode(file_get_contents($ts_base_addr.urlencode($recorder)),TRUE);
			$program_raw = json_decode(file_get_contents($ts_base_addr.urlencode($recorder).'/records/'.$timeshift_id),TRUE);
			if( !isset($program_id) || !$program_id ){
				$autorec = 0;
				$category_id = $program_raw['program']['genres'][0]['lv1'] + 1;
				$type = $channel_raw['service']['channel']['type'];

				$channel = $channel_raw['service']['channel']['channel'];
				if( $channel_raw['service']['channel']['type'] == 'GR' ){
					$channel_disc = $channel_raw['service']['channel']['type'].$channel_raw['service']['channel']['channel'].'_'.$channel_raw['service']['serviceId'];
				}else{
					$channel_disc = $channel_raw['service']['channel']['type'].'_'.$channel_raw['service']['serviceId'];
				}
				$channel_obj = new DBRecord( CHANNEL_TBL, "channel_disc", $channel_disc );
				$channel_id = $channel_obj->id;
				$title = $program_raw['program']['name'];
				$pre_title = '';
				$post_title = '';
				$description = $program_raw['program']['description'];
			}
			if( !$program_raw['recording'] ){
				$starttime = strftime("%Y-%m-%d %H:%M:%S", (int)($program_raw['startTime'] / 1000));
				$endtime = strftime("%Y-%m-%d %H:%M:%S", (int)(($program_raw['startTime'] + $program_raw['duration']) / 1000));
			}
			$priority = MANUAL_REV_PRIORITY;
			$autorec_mode  = (int)$settings->normalrec_mode;
			$discontinuity = '';
  
			$rec_dir = $settings->normalrec_dir;
			$trans_dir = $settings->normalrec_trans_dir;

			$complete = 0;

			break;
	}

	sscanf( $starttime, "%4d-%2d-%2d %2d:%2d:%2d", $syear, $smonth, $sday, $shour, $smin, $ssec );
	sscanf( $endtime, "%4d-%2d-%2d %2d:%2d:%2d", $eyear, $emonth, $eday, $ehour, $emin, $esec );

	$categories = DBRecord::createRecords( CATEGORY_TBL );
	$cats = array();
	foreach( $categories as $category ) {
		$cat = array();
		$cat['id'] = $category->id;
		$cat['name'] = $category->name_jp;
		$cat['selected'] = $category_id == $cat['id'] ? "selected" : "";

		array_push( $cats , $cat );
	}

	$smarty = new Smarty();
	$smarty->template_dir = INSTALL_PATH . "/templates/";
	$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
	$smarty->cache_dir = INSTALL_PATH . "/cache/";
 
	$smarty->assign( "mode", $mode );
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
  
	$smarty->assign( "type", $type );
	$smarty->assign( "channel", $channel );
	$smarty->assign( "channel_id", $channel_id );
	$smarty->assign( "channel_disc", $channel_disc );
	$smarty->assign( "recorder", $recorder );
	$smarty->assign( "timeshift_id", $timeshift_id );
	$smarty->assign( "record_mode" , $RECORD_MODE );
	$smarty->assign( "autorec_mode" , $autorec_mode );
	$smarty->assign( "autorec" , $autorec );
	$smarty->assign( "discontinuity" , $discontinuity );
	$smarty->assign( 'priority', MANUAL_REV_PRIORITY );
  
	$smarty->assign( "title", $title );
	$smarty->assign( "pre_title", $pre_title );
	$smarty->assign( "post_title", $post_title );
	$smarty->assign( "description", $description );
 
	$smarty->assign( "rec_dir", $rec_dir );
	$smarty->assign( "trans_dir", $trans_dir );

	$smarty->assign( "program_id", $program_id );
  
	$smarty->assign( "cats" , $cats );

	$smarty->assign( "complete", $complete );
	$smarty->assign( "autorec", $autorec );
  
	$smarty->display("reservationform.html");
}catch( exception $e ){
	exit( "Error:". $e->getMessage() );
}
?>

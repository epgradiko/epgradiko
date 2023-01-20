<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/epg_const.php' );
include_once( INSTALL_PATH . '/include/menu_list.php' );

function ch_collect( $type, $select_ch, $sort_calm='sid' )
{
	$crec = DBRecord::createRecords( CHANNEL_TBL, 'WHERE type=\''.$type.'\' AND skip=0 ORDER BY '.$sort_calm );
	$single_ch_selects = array();
	if( strncmp( $type, $select_ch, 2 ) !== 0 )
		$single_ch_selects[] = array(
			'name'         => '---------',
			'channel_disc' => '#',
			'selected'     => '',
		);
	foreach( $crec as $val ) {
		array_push( $single_ch_selects, array(
			'name'         => $val->name,
			'channel_disc' => $val->channel_disc,
			'selected'     => ( $select_ch===$val->channel_disc ? ' selected' : '' ),
		));
	}
	return $single_ch_selects;
}


// 設定ファイルの有無を検査する
if( ! file_exists( INSTALL_PATH.'/settings/config.xml') && !file_exists( '/etc/epgrecUNA/config.xml' ) ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}

$settings = Settings::factory();

// 現在の時間
$now_time = time();

$height_per_hour = (int)$settings->height_per_hour;
$height_per_sec  = (float)$height_per_hour / 3600;

// パラメータの処理
// 表示する長さ（時間）
$program_length = (int)$settings->program_length;
if( isset( $_GET['length']) ) $program_length = (int) $_GET['length'];
// 地上=GR/BS=BS
$type = 'GR';
if( isset( $_GET['type'] ) ) $type = $_GET['type'];

// 番組表
// 表示チャンネル
$programs = array();
$single_ch_disc = $single_ch_sid = $single_ch_name = $single_ch = null;
$selected_channel = FALSE;
$single_gr_selects = $single_bs_selects = $single_cs_selects = $single_ex_selects = null;
$channel_map = array();
if( isset($_GET['ch']) ){
	$single_ch_disc = $_GET['ch'];
	$type           = strtoupper( substr($_GET['ch'], 0, 2) );
	// チャンネルセレクタ
	if( $settings->gr_tuners != 0 )
		$single_gr_selects = ch_collect( 'GR', $type==='GR' ? $single_ch_disc : FALSE, 'id' );
	if( $settings->bs_tuners != 0 ){
		$single_bs_selects = ch_collect( 'BS', $type==='BS' ? $single_ch_disc : FALSE );
		if( $settings->cs_rec_flg != 0 )
			$single_cs_selects = ch_collect( 'CS', $type==='CS' ? $single_ch_disc : FALSE );
	}
	if( $settings->ex_tuners != 0 )
		$single_ex_selects = ch_collect( 'EX', $type==='EX' ? $single_ch_disc : FALSE );
}
if( $type == 'GR' ){
	if($settings->gr_tuners > 0) $channel_map = $GR_CHANNEL_MAP;
	else $type = 'BS';
}
if( $type == 'BS' ){
	if($settings->bs_tuners > 0) $channel_map = $BS_CHANNEL_MAP;
	else $type = 'CS';
}
if( $type == 'CS' ){
	if($settings->bs_tuners > 0 && $settings->cs_rec_flg) $channel_map = $CS_CHANNEL_MAP;
	else $type = 'EX';
}
if( $type == 'EX' ){
	if($settings->ex_tuners > 0) $channel_map = $EX_CHANNEL_MAP;
	else{
		$type = 'GR';
		$channel_map = $GR_CHANNEL_MAP;
	}
}
if( $type == 'SELECT' ){
	$channel_map = $SELECTED_CHANNEL_MAP;
	$selected_channel =TRUE;
}

if( !count($channel_map) ){
	$type = 'GR';
	$channel_map = $GR_CHANNEL_MAP;
}

// 先頭(現在)の時間
if( isset( $_GET['time'] ) && sscanf( $_GET['time'] , '%04d%2d%2d%2d', $y, $mon, $day, $h )==4 ){
	$get_time = mktime( $h, 0, 0, $mon, $day, $y );
	$today    = mktime( $h, 0, 0 );
	if( $get_time < $today-3600*(24+$h) )
		$top_time = $today - 3600 * 24;
	else{
		if( $single_ch_disc && $get_time>$today+3600*(24-$h) )
			$top_time = $today;
		else
			$top_time = $get_time>$today+3600*(24*8-1-$h) ? $today+3600*(24*8-$program_length-$h) : $get_time;
	}
}else
	$top_time = mktime( date('H'), 0 , 0 );
$last_time = $top_time + 3600 * $program_length;

// ジャンル一覧
try {
	$genres = DBRecord::createRecords( CATEGORY_TBL );
}
catch( Exception $e ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}
$cats   = array();
$num    = 0;
foreach( $genres as $val ) {
	$cats[$num]['id']      = $num + 1;
	$cats[$num]['name_jp'] = $val->name_jp;
	$num++;
}
$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( 'cats', $cats );


$st = 0;
$prec = null;
try {
	$prec = new DBRecord(PROGRAM_TBL);
}
catch( Exception $e ) {
	exit('プログラムテーブルが存在しないようです。インストールをやり直してください.');
}
$num_ch     = 0;
$num_all_ch = 0;
if( $selected_channel ){
	$lp_lmt           = count($SELECTED_CHANNEL_MAP);
	$channel_map_keys = $SELECTED_CHANNEL_MAP;
}else{
	$lp_lmt           = $single_ch_disc ? 8 : count($channel_map);
	$channel_map_keys = array_keys($channel_map);
}
for( $i = 0; $i < $lp_lmt; $i++ ){
	try {
		if( $single_ch_disc ){
			$ch_full_duration = $st * 24*60*60;
			$ch_top_time = $top_time + $ch_full_duration;
			$ch_last_time = $ch_top_time + 24*60*60;
			$chd = DBRecord::createRecords( CHANNEL_TBL, 'WHERE channel_disc="'.$single_ch_disc.'"' );
		}else{
			$ch_full_duration = 0;
			$ch_top_time = $top_time;
			$ch_last_time = $last_time;
			if( $type !== 'EX' ){
				$chd = DBRecord::createRecords( CHANNEL_TBL, 'WHERE channel_disc LIKE "'.$channel_map_keys[$i].'%" ORDER BY sid ASC' );
			}else{
				$chd = DBRecord::createRecords( CHANNEL_TBL, 'WHERE channel_disc ="'.$channel_map_keys[$i].'" ORDER BY sid ASC' );
			}
		}
		foreach( $chd as $crec ){
			if( $channel_map[(string)$crec->channel_disc] == 'NC' ) continue;
			$num_all_ch++;
			$prev_end = $top_time + $ch_full_duration;
			$programs[$st]['id']   = $ch_id = $crec->id;
			$programs[$st]['skip'] = $single_ch_disc ? 0 : $crec->skip;
			$programs[$st]['channel_disc'] = $crec->channel_disc;
			$programs[$st]['station_name'] = $crec->name;
			$programs[$st]['sid'] = $crec->sid;
			$programs[$st]['ch_hash'] = md5($crec->channel_disc);
			$programs[$st]['channel'] = $crec->channel;
			if( isset($record_cmd[$type]['suffix']) ){
				$ext = $record_cmd[$type]['suffix'];
				$explode_text = explode('.', $ext);
				$programs[$st]['type'] = end($explode_text);
			}
			$programs[$st]['list'] = array();
			// シングルチャンネル用
			if ( $single_ch_disc ) {
				$single_ch      = $programs[$st]['channel'];
				$single_ch_sid  = $programs[$st]['sid'];
				$single_ch_name = $programs[$st]['station_name'];
				$programs[$st]['_day']        = date('d', $ch_top_time );
				$programs[$st]['start_time']  = date('m', $ch_top_time );
				$programs[$st]['start_time_dw']  = date('w', $ch_top_time ).'';
				$programs[$st]['link'] = 'index.php?type='.$type.'&time='.date( 'Ymd', $top_time + 24 * 3600 * $i) . date('H' , $top_time );
			}

			$reca = $prec->fetch_array( 'channel_id', $ch_id,
					'endtime>\''.toDatetime($ch_top_time).'\' AND starttime<\''.toDatetime($ch_last_time).'\' ORDER BY starttime ASC' );
			$num = 0;
			if( count( $reca )>1 || ( count( $reca )==1 && (string)$reca[0]['title']!=='放送休止' ) ){
				$ch_num = ( $crec->type==='GR' ? '地上D' : $crec->type ).':'.$crec->channel.'ch';
				foreach( $reca as $prg ) {
					// 前プログラムとの空きを調べる
					$program_id = (int)$prg['id'];
					$start_str  = $prg['starttime'];
					$start      = toTimestamp( $start_str );
					if( $start > $prev_end ){
						$programs[$st]['list'][$num]['genre']       = 0;
						$programs[$st]['list'][$num]['sub_genre']   = 0;
						$programs[$st]['list'][$num]['height']      = (int)( ($start-$prev_end) * $height_per_sec );
						$programs[$st]['list'][$num]['title']       = '';
						$programs[$st]['list'][$num]['pre_title']   = '';
						$programs[$st]['list'][$num]['post_title']  = '';
						$programs[$st]['list'][$num]['starttime']   = '';
						$programs[$st]['list'][$num]['description'] = '';
						$programs[$st]['list'][$num]['prg_start']   = '';
						$programs[$st]['list'][$num]['duration']    = '';
						$programs[$st]['list'][$num]['channel']     = '';
						$programs[$st]['list'][$num]['id']          = 0;
						$programs[$st]['list'][$num]['autorec']     = '';
						$programs[$st]['list'][$num]['rec']         = 0;
						$programs[$st]['list'][$num]['tuner']       = '';
						$programs[$st]['list'][$num]['key_id']      = 0;
						$programs[$st]['list'][$num]['prios']       = '';
						$programs[$st]['list'][$num]['keyword']     = '';
						$num++;
					}
					$prev_end = toTimestamp( $prg['endtime'] );

					// プログラムを埋める
					$programs[$st]['list'][$num]['genre']       = $prg['category_id'];
					$programs[$st]['list'][$num]['sub_genre']   = $prg['sub_genre'];
					$programs[$st]['list'][$num]['height']      =
						(int)( ( ($prev_end>=$ch_last_time ? $ch_last_time : $prev_end) - ($start<=$ch_top_time ? $ch_top_time : $start) ) * $height_per_sec );
					$programs[$st]['list'][$num]['title']       = $prg['title'];
					$programs[$st]['list'][$num]['pre_title']   = strtr($prg['pre_title'], array_column(ProgramMark, 'char', 'name'));
					$programs[$st]['list'][$num]['post_title']  = strtr($prg['post_title'], array_column(ProgramMark, 'char', 'name'));
					$programs[$st]['list'][$num]['starttime']   = date('H:i:s', $start );
					$programs[$st]['list'][$num]['description'] = $prg['description'];
					$programs[$st]['list'][$num]['prg_start']   = str_replace( '-', '/', $start_str);
					$programs[$st]['list'][$num]['duration']    = (string)($prev_end - $start);
					$programs[$st]['list'][$num]['channel']     = $ch_num;
					$programs[$st]['list'][$num]['id']          = $program_id;
					$programs[$st]['list'][$num]['autorec']     = $prg['autorec'];
					if( $program_id ){
						$rev = DBRecord::createRecords( RESERVE_TBL, 'WHERE complete=0 AND program_id='.$program_id.' ORDER BY starttime' );
						$programs[$st]['list'][$num]['rec'] = $rec_cnt = count( $rev );
						if( $rec_cnt ){
							$programs[$st]['list'][$num]['tuner']  = $rev[0]->tuner;
							$programs[$st]['list'][$num]['key_id'] = $rev[0]->autorec;
							// 複数ある場合の対処無し
							$pri_list = array();
							foreach( $rev as $re )
								$pri_list[] = $re->priority;
							$programs[$st]['list'][$num]['prios'] = 'P('.implode( ',', $pri_list ).')';
						}else{
							$programs[$st]['list'][$num]['tuner']  = '';
							$programs[$st]['list'][$num]['key_id'] = 0;
							$programs[$st]['list'][$num]['prios']  = '';
						}
					}else{
						$programs[$st]['list'][$num]['rec']    = 0;
						$programs[$st]['list'][$num]['tuner']  = '';
						$programs[$st]['list'][$num]['key_id'] = 0;
						$programs[$st]['list'][$num]['prios']  = '';
					}
					$programs[$st]['list'][$num]['keyword'] = putProgramHtml( $prg['title'], $crec->type, $ch_id, $prg['category_id'], $prg['sub_genre'] );
					$num++;
				}
				if( $programs[$st]['skip']==0 && $num>0 )
					$num_ch++;
			}
			// 空きを埋める
			if( $ch_last_time > $prev_end ){
				$programs[$st]['list'][$num]['genre']       = 0;
				$programs[$st]['list'][$num]['sub_genre']   = 0;
				$programs[$st]['list'][$num]['height']      = (int)( ( $ch_last_time - $prev_end ) * $height_per_sec );
				$programs[$st]['list'][$num]['title']       = '';
				$programs[$st]['list'][$num]['pre_title']   = '';
				$programs[$st]['list'][$num]['post_title']  = '';
				$programs[$st]['list'][$num]['starttime']   = '';
				$programs[$st]['list'][$num]['description'] = '';
				$programs[$st]['list'][$num]['prg_start']   = '';
				$programs[$st]['list'][$num]['duration']    = '';
				$programs[$st]['list'][$num]['channel']     = '';
				$programs[$st]['list'][$num]['id']          = 0;
				$programs[$st]['list'][$num]['autorec']     = '';
				$programs[$st]['list'][$num]['rec']         = 0;
				$programs[$st]['list'][$num]['tuner']       = '';
				$programs[$st]['list'][$num]['key_id']      = 0;
				$programs[$st]['list'][$num]['prios']       = '';
				$programs[$st]['list'][$num]['keyword']     = '';
			}
			$st++;
		}
	}
	catch( exception $e ) {
//		exit( $e->getMessage() );
//		何もしない
 	}
}
$prec = null;
 
// 局の幅
$ch_set_width = (int)($settings->ch_set_width);
// 全体の幅
$chs_width = $ch_set_width * $num_ch;

// GETパラメタ
$get_param  = $_SERVER['SCRIPT_NAME'] . '?type='.$type.'&length='.$program_length;
$get_param2 = $single_ch_disc ? $_SERVER['SCRIPT_NAME'].'?ch='.$single_ch_disc : $get_param;

// タイプ選択
$types = array();
$i = 0;
if( $settings->gr_tuners != 0 ) {
	$types[$i]['selected'] = $type==='GR' ? 'class="selected"' : '';
	$types[$i]['link']     = $_SERVER['SCRIPT_NAME'] . '?type=GR&length='.$program_length.'&time='.date( 'YmdH', $top_time);
	$types[$i]['link2']    = $_SERVER['SCRIPT_NAME'] . '?type=GR&length='.$program_length;
	$types[$i]['name']     = '地デジ';
	$types[$i]['chs']      = $single_gr_selects;
	$i++;
}
if( $settings->bs_tuners != 0 ) {
	$types[$i]['selected'] = $type==='BS' ? 'class="selected"' : '';
	$types[$i]['link']     = $_SERVER['SCRIPT_NAME'] . '?type=BS&length='.$program_length.'&time='.date( 'YmdH', $top_time);
	$types[$i]['link2']    = $_SERVER['SCRIPT_NAME'] . '?type=BS&length='.$program_length;
	$types[$i]['name']     = 'BS';
	$types[$i]['chs']      = $single_bs_selects;
	$i++;

	// CS
	if ($settings->cs_rec_flg != 0) {
		$types[$i]['selected'] = $type==='CS' ? 'class="selected"' : '';
		$types[$i]['link']     = $_SERVER['SCRIPT_NAME'] . '?type=CS&length='.$program_length.'&time='.date( 'YmdH', $top_time);
		$types[$i]['link2']    = $_SERVER['SCRIPT_NAME'] . '?type=CS&length='.$program_length;
		$types[$i]['name']     = 'CS';
		$types[$i]['chs']      = $single_cs_selects;
		$i++;
	}
}
if( $settings->ex_tuners != 0 ) {
	$types[$i]['selected'] = $type==='EX' ? 'class="selected"' : '';
	$types[$i]['link']     = $_SERVER['SCRIPT_NAME'] . '?type=EX&length='.$program_length.'&time='.date( 'YmdH', $top_time);
	$types[$i]['link2']    = $_SERVER['SCRIPT_NAME'] . '?type=EX&length='.$program_length;
	$types[$i]['name']     = 'ラジオ';
	$types[$i]['chs']      = $single_ex_selects;
	$i++;
}
if( isset($SELECTED_CHANNEL_MAP) ){
	$types[$i]['selected'] = $type==='SELECT' ? 'class="selected"' : '';
	$types[$i]['link']     = $_SERVER['SCRIPT_NAME'] . '?type=SELECT&length='.$program_length.'&time='.date( 'YmdH', $top_time);
	$types[$i]['link2']    = $_SERVER['SCRIPT_NAME'] . '?type=SELECT&length='.$program_length;
	$types[$i]['name']     = '選別';
	$types[$i]['chs']      = $single_ex_selects;
	$i++;
}

$smarty->assign( 'types', $types );

// 日付選択
$days = array();
$day = array();
$day['d'] = '昨日';
$day['link'] = $get_param2 . '&time='. date( 'YmdH', time() - 3600 *24 );
$day['ofweek'] = '';
$day['selected'] = $top_time < mktime( 0, 0 , 0) ? 'class="selected"' : '';
array_push( $days , $day );

$day['d'] = '現在';
$day['link'] = $get_param2;
$day['ofweek'] = '';
$day['selected'] = '';
array_push( $days, $day );

if( !$single_ch_disc ){
	for( $i = 0 ; $i < 8 ; $i++ ) {
		$day['d'] = date('d', time() + 24 * 3600 * $i ) . '日';
		$day['link'] = $get_param . '&time='.date( 'Ymd', time() + 24 * 3600 * $i) . date('H' , $top_time );
		$day['ofweek'] = date( 'w', time() + 24 * 3600 * $i );
		$day['selected'] = date('d', $top_time) == date('d', time() + 24 * 3600 * $i ) ? 'class="selected"' : '';
		array_push( $days, $day );
	}
}
$smarty->assign( 'days' , $days );

// 時間選択
$toptimes = array();
$lp_lmt   = !$single_ch_disc ? 28 : 24;
for( $i = 0 ; $i < $lp_lmt; $i+=2 ) {
	$tmp = array();
	$tmp['hour'] = sprintf( '%02d', $i<=24 ? $i : $i-24 );
	$tmp_time = $i<24 ? $top_time : $top_time + 24 * 60 * 60;
	$tmp['link'] =  $get_param2 . '&time='.date('Ymd', $tmp_time ) . sprintf('%02d', $i<24 ? $i : $i-24 );
	array_push( $toptimes, $tmp );
}
$smarty->assign( 'toptimes' , $toptimes );

// 時刻欄
$tvtimes = array();
$iMax = $single_ch_disc? 24 : $program_length;
for( $i = 0 ; $i < $iMax; $i++ ) {
	$tmp = array();
	$tmp_time    = $top_time + 3600 * $i;
	$tmp['hour'] = date('H', $tmp_time );
	$tmp['link'] = $get_param2 . '&time='.date('YmdH', $tmp_time );
	array_push( $tvtimes, $tmp );
}


$transcode = TRANSCODE_STREAM && $NET_AREA!==FALSE && $NET_AREA!=='H';
if( $transcode && !TRANS_SCRN_ADJUST ){
	for( $cnt=0; $cnt<count($TRANSSIZE_SET); $cnt++ )
		$TRANSSIZE_SET[$cnt]['selected'] = $cnt===TRANSTREAM_SIZE_DEFAULT ? ' selected' : '';
}


$smarty->assign( 'tvtimes', $tvtimes );
$smarty->assign( 'pre8link', $get_param2.'&time='.date('YmdH', $top_time - 8*3600 ) );
$smarty->assign( 'prelink', $get_param2.'&time='.date('YmdH', $top_time - 3600 ) );

$smarty->assign( 'delete_select', $settings->delete_select );
$smarty->assign( 'programs', $programs );
$smarty->assign( 'ch_set_width', $ch_set_width );
$smarty->assign( 'chs_width', $chs_width );
$smarty->assign( 'height_per_hour', $height_per_hour );
$smarty->assign( 'height_per_min', (int)($height_per_hour / 60) );
$smarty->assign( 'num_ch', $num_ch );
$smarty->assign( 'num_all_ch' , $num_all_ch );
$smarty->assign( 'single_ch', $single_ch );
$smarty->assign( 'single_ch_sid', $single_ch_sid );
$smarty->assign( 'single_ch_disc', $single_ch_disc );
$smarty->assign( 'single_ch_name', $single_ch_name );
$smarty->assign( 'dayweeks', array('日','月','火','水','木','金','土') );
$smarty->assign( '__nowDay', date('d', $now_time) );
$smarty->assign( 'REALVIEW', REALVIEW);
$smarty->assign( 'TRANSCODE_STREAM', $transcode ? 1 : 0 );
$smarty->assign( 'TRANS_SCRN_ADJUST', $transcode&&TRANS_SCRN_ADJUST ? 1 : 0 );
$smarty->assign( 'realview_cmd', 'viewer.php' );
$smarty->assign( 'transsize_set', $TRANSSIZE_SET );
$smarty->assign( 'transsize_set_cnt', $num_all_ch );
$smarty->assign( 'spool_freesize', spool_freesize() );

$sitetitle = ( $type==='SELECT' ? '選別' : ($type==='EX' ? 'ラジオ' : ( $type==='GR' ? '地デジ' : $type ))).'番組表'.($single_ch_disc ? '['.$single_ch_name.']' : '').' '.
			date( 'Y', $top_time ) . '年' . date( 'm', $top_time ) . '月' . date( 'd', $top_time ) . '日'. date( 'H', $top_time ) .'時～';

$smarty->assign('sitetitle', $sitetitle );

$smarty->assign('top_time', str_replace( '-', '/' ,toDatetime($top_time)) );
$smarty->assign('last_time', str_replace( '-', '/' ,toDatetime($last_time)) );
$smarty->assign( 'menu_list', link_menu_create('INDEX') );


$smarty->display('index.html');
?>

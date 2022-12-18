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

if( ! file_exists( INSTALL_PATH.'/settings/config.xml') && !file_exists( '/etc/epgrecUNA/config.xml' ) ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}

$settings = Settings::factory();

// 現在の時間
$now_time = time();

// パラメータの処理
// 表示する長さ（時間）
$program_length = isset( $_GET['length'] ) ? (int)$_GET['length'] : (int)$settings->program_length;
;

$tuners_que = $tuners_name = array();
if( (int)$settings->gr_tuners > 0 ){
	for( $cnt=0; $cnt<$settings->gr_tuners; $cnt++ ){
		$tuners_que[]  = 'type="GR" AND tuner='.$cnt;
		$tuners_name[] = 'GR'.$cnt;
	}
}
if( (int)$settings->bs_tuners > 0 ){
	for( $cnt=0; $cnt<$settings->bs_tuners; $cnt++ ){
		$tuners_que[]  = 'type IN ("BS","CS") AND tuner='.$cnt;
		$tuners_name[] = 'BS'.$cnt;
	}
}
if( (int)$settings->ex_tuners > 0 ){
	for( $cnt=0; $cnt<$settings->ex_tuners; $cnt++ ){
		$tuners_que[]  = 'type="EX" AND tuner='.$cnt;
		$tuners_name[] = 'EX'.$cnt;
	}
}

$single_tuner = isset( $_GET['tuner'] ) ? array_search( $_GET['tuner'], $tuners_name ) : FALSE;

$prec = new DBRecord(RESERVE_TBL);
// 先頭(現在)の時間
if( isset( $_GET['time'] ) && sscanf( $_GET['time'], '%04d%2d%2d%2d', $y, $mon, $day, $h )==4 ){
	$get_time = mktime( $h, 0, 0, $mon, $day, $y );
	$today    = mktime( $h, 0, 0 );
//	if( $get_time >= $today-3600*(24+$h) ){
		if( $single_tuner!==FALSE && $get_time>$today+3600*(24-$h) )
			$top_time = $today;
		else
			$top_time = $get_time>$today+3600*(24*8-1-$h) ? $today+3600*(24*8-$program_length-$h) : $get_time;
//	}
}else{
	$top_time = mktime( date('H'), 0 , 0 );
	$reca     = $prec->fetch_array( null, null, 'complete=0 ORDER BY starttime' );
	if( count($reca) ){
		$rev_top = (int)(toTimestamp( $reca[0]['starttime'] )/(60*60)) * 60 * 60;
		if( $top_time < $rev_top-3600 )
			$top_time = $rev_top;
	}
}
$last_time = $top_time + 3600 * $program_length;

$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

// ジャンル一覧
$genres = DBRecord::createRecords( CATEGORY_TBL );
$cats   = array();
$num    = 0;
foreach( $genres as $val ){
	$cats[$num]['id']      = $num + 1;
	$cats[$num]['name_jp'] = $val->name_jp;
	$num++;
}
$smarty->assign( 'cats', $cats );


$height_per_hour = (int)$settings->height_per_hour;
$height_per_sec  = (float)$height_per_hour / 3600;

if( $single_tuner !== FALSE ){
	$lp_lmt = 8;
	$t_lmt  = 1;
	if( $single_tuner )
		array_splice( $tuners_que, 0, $single_tuner );
	$tname[] = $tuners_name[$single_tuner];
}else{
	$lp_lmt = 1;
	$t_lmt  = count($tuners_que);
	$tname  = $tuners_name;
}

// 番組表
$pro_obj  = new DBRecord(PROGRAM_TBL);
$ch_obj   = new DBRecord(CHANNEL_TBL);
$num_ch   = 0;
$programs = array();
for( $st=0; $st<$lp_lmt; $st++ ){
	if( $single_tuner !== FALSE ){
		$ch_full_duration = $st * 24*60*60;
		$ch_top_time      = $top_time + $ch_full_duration;
		$ch_last_time     = $ch_top_time + 24*60*60;
	}else{
		$ch_full_duration = 0;
		$ch_top_time      = $top_time;
		$ch_last_time     = $last_time;
	}
	for( $t_lp=0; $t_lp<$t_lmt; $t_lp++ ){
//		$reca = $prec->fetch_array( null, null, $tuners_que[$t_lp].' AND complete=0 AND endtime>"'.toDatetime($ch_top_time).'" AND starttime<"'.toDatetime($ch_last_time).'" ORDER BY starttime ASC' );
		$rect = $prec->fetch_array( null, null, $tuners_que[$t_lp].' AND complete=0 AND endtime>"'.toDatetime($ch_top_time).'" AND starttime<"'.toDatetime($ch_last_time).
						'" GROUP BY sub_tuner ORDER BY sub_tuner ASC' );
//		if( count($reca) ){
		if( count($rect) ){
			foreach( $rect as $sub_t ){
			$reca = $prec->fetch_array( null, null, $tuners_que[$t_lp].' AND sub_tuner='.$sub_t['sub_tuner'].' AND complete=0 AND endtime>"'.toDatetime($ch_top_time).'" AND starttime<"'.toDatetime($ch_last_time).
							'" ORDER BY starttime ASC' );
			$prev_end = $ch_top_time;
			$num_ch++;
			$program  = array();
			if( count($rect) == 1 ) $program['name'] = $tname[$t_lp];
			else                    $program['name'] = $tname[$t_lp].'('.$sub_t['sub_tuner'].')';
			$program['link']          = $_SERVER['SCRIPT_NAME'].( $single_tuner===FALSE ? '?tuner='.$tname[$t_lp] : '?time='.date( 'YmdH', $ch_top_time ) );
			$program['_day']          = date('d', $ch_top_time );
			$program['start_time']    = date('m', $ch_top_time );
			$program['start_time_dw'] = date('w', $ch_top_time );
			$program['list']          = array();
			$num  = 0;
			foreach( $reca as $prg ){
				$start_str = $prg['starttime'];
				$start     = toTimestamp( $start_str );
				// 前プログラムとの空きを調べる
				if( $start > $prev_end ){
					$program['list'][$num]['genre']       = 0;
					$program['list'][$num]['sub_genre']   = 0;
					$program['list'][$num]['height']      = (int)round( ($start-$prev_end) * $height_per_sec );
					$program['list'][$num]['title']       = '';
					$program['list'][$num]['pre_title']   = '';
					$program['list'][$num]['post_title']  = '';
					$program['list'][$num]['starttime']   = '';
					$program['list'][$num]['description'] = '';
					$program['list'][$num]['prg_start']   = '';
					$program['list'][$num]['duration']    = '';
					$program['list'][$num]['type']        = '';
					$program['list'][$num]['channel']     = '';
					$program['list'][$num]['tuner']       = '';
					$program['list'][$num]['id']          = 0;
					$program['list'][$num]['key_id']      = 0;
					$program['list'][$num]['autorec']     = 0;
					$program['list'][$num]['rec_id']      = 0;
					$program['list'][$num]['prios']       = '';
					$program['list'][$num]['keyword']     = '';
					$num++;
				}
				$prev_end = toTimestamp( $prg['endtime'] );
				// プログラムを埋める
				$program['list'][$num]['genre']       = $prg['category_id'];
				$program['list'][$num]['sub_genre']   = $prg['sub_genre'];
				$program['list'][$num]['height']      =
					(int)round( ( ($prev_end>=$ch_last_time ? $ch_last_time : $prev_end) - ($start<=$ch_top_time ? $ch_top_time : $start) ) * $height_per_sec );
				$program['list'][$num]['title']       = $prg['title'];
				$program['list'][$num]['pre_title']   = strtr($prg['pre_title'], array_column(ProgramMark, 'char', 'name')); 
				$program['list'][$num]['post_title']  = strtr($prg['post_title'], array_column(ProgramMark, 'char', 'name'));
				$program['list'][$num]['starttime']   = date('H:i:s', $start );
				$program['list'][$num]['description'] = $prg['description'];
				$program['list'][$num]['prg_start']   = str_replace( '-', '/', $start_str);
				$program['list'][$num]['duration']    = (string)($prev_end - $start);
				$program['list'][$num]['type']        = $prg['type'];
				if( !isset( $ch[$prg['channel_id']] ) ){
					try {
						$tmp_ch = $ch_obj->fetch_array( 'id', $prg['channel_id'] );
						if( count($tmp_ch) )
							$ch[$prg['channel_id']] = $tmp_ch[0];
						else{
							$ch[$prg['channel_id']]['channel'] = '***';
							$ch[$prg['channel_id']]['sid']     = '***';
							$ch[$prg['channel_id']]['name']    = 'unknown';
						}
					}catch( exception $e ){
						$ch[$prg['channel_id']]['channel'] = '***';
						$ch[$prg['channel_id']]['sid']     = '***';
						$ch[$prg['channel_id']]['name']    = 'unknown';
					}
				}
				$program['list'][$num]['channel'] =
					$ch[$prg['channel_id']]['name'].'['.($ch[$prg['channel_id']]['type']==='GR' ? 'GR' : $ch[$prg['channel_id']]['type'].$ch[$prg['channel_id']]['sid']).']';
//				$program['list'][$num]['tuner']   = $prg['tuner'].'('.$prg['sub_tuner'].')';
				$program['list'][$num]['tuner']   = $prg['tuner'];
				$program['list'][$num]['id']      = $prg['program_id'];
				$program['list'][$num]['key_id']  = $prg['autorec'];
				if( (int)$prg['program_id'] > 0 ){
					try{
						$res = $pro_obj->fetch_array( 'id', $prg['program_id'] );
						$program['list'][$num]['autorec'] = count($res) ? $res[0]['autorec'] : 0;
					}catch( exception $e ){
						$program['list'][$num]['autorec'] = 0;
					}
				}else
//					$program['list'][$num]['autorec'] = 0;
					$program['list'][$num]['autorec'] = 1;
				$program['list'][$num]['rec_id']  = $prg['id'];
				$program['list'][$num]['prios']   = 'P('.$prg['priority'].')';
				$program['list'][$num]['keyword'] = putProgramHtml( $prg['title'], $prg['type'], $prg['channel_id'], $prg['category_id'], $prg['sub_genre'] );
				$num++;
			}
			// 空きを埋める
			if( $ch_last_time > $prev_end ){
				$program['list'][$num]['genre']       = 0;
				$program['list'][$num]['sub_genre']   = 0;
				$program['list'][$num]['height']      = (int)round( ( $ch_last_time - $prev_end ) * $height_per_sec );
				$program['list'][$num]['title']       = '';
				$program['list'][$num]['pre_title']   = '';
				$program['list'][$num]['post_title']  = '';
				$program['list'][$num]['starttime']   = '';
				$program['list'][$num]['description'] = '';
				$program['list'][$num]['prg_start']   = '';
				$program['list'][$num]['duration']    = '';
				$program['list'][$num]['type']        = '';
				$program['list'][$num]['channel']     = '';
				$program['list'][$num]['tuner']       = '';
				$program['list'][$num]['id']          = 0;
				$program['list'][$num]['key_id']      = 0;
				$program['list'][$num]['autorec']     = 0;
				$program['list'][$num]['rec_id']      = 0;
				$program['list'][$num]['prios']       = '';
				$program['list'][$num]['keyword']     = '';
			}
			array_push( $programs, $program );
			}
		}
	}
}
unset($prec);

// 局の幅
$ch_set_width = (int)($settings->ch_set_width);
// 全体の幅
$chs_width = $ch_set_width * $num_ch;

// GETパラメタ
$get_param  = $_SERVER['SCRIPT_NAME'].'?length='.$program_length;
$get_param2 = $single_tuner!==FALSE ? $_SERVER['SCRIPT_NAME'].'?tuner='.$tuners_name[$single_tuner] : $get_param;

$index_links = array();
if( $single_tuner === FALSE ){
	$tmp_link = str_replace( 'revchartTable', 'index', $get_param2 ).'&time='.date('YmdH', $top_time ).'&type=';
	if( (int)$settings->gr_tuners > 0 ){
		$work['name']  = '地デジ';
		$work['link']  = $tmp_link.'GR';
		$index_links[] = $work;
	}
	if( (int)$settings->bs_tuners > 0 ){
		$work['name']  = 'BS';
		$work['link']  = $tmp_link.'BS';
		$index_links[] = $work;
		if( $settings->cs_rec_flg != 0 ){
			$work['name']  = 'CS';
			$work['link']  = $tmp_link.'CS';
			$index_links[] = $work;
		}
	}
	if( (int)$settings->ex_tuners > 0 ){
		$work['name']  = 'ラジオ';
		$work['link']  = $tmp_link.'EX';
		$index_links[] = $work;
	}
}
$smarty->assign( 'index_links', $index_links );

// 日付選択
$days = $day = array();
$day['d']        = '昨日';
$day['link']     = $get_param2.'&time='.date( 'YmdH', time() - 3600 *24 );
$day['ofweek']   = '';
$day['selected'] = $top_time < mktime( 0, 0 , 0) ? 'class="selected"' : '';
array_push( $days , $day );

$day['d']        = '現在';
$day['link']     = $get_param2;
$day['ofweek']   = '';
$day['selected'] = '';
array_push( $days, $day );

if( $single_tuner === FALSE ){
	for( $st=0; $st<8; $st++ ){
		$cal_time        = $now_time + 24 * 3600 * $st;
		$day['d']        = date('d', $cal_time ).'日';
		$day['link']     = $get_param.'&time='.date( 'Ymd', $cal_time ).date( 'H' , $top_time );
		$day['ofweek']   = date( 'w', $cal_time );
		$day['selected'] = date('d', $top_time)===date('d', $cal_time ) ? 'class="selected"' : '';
		array_push( $days, $day );
	}
}else{
	$tmp_link = $get_param.'&time='.date( 'YmdH' , $top_time ).'&tuner=';
	foreach( $tuners_name as $t_name ){
		$day['d']        = $t_name;
		$day['link']     = $tmp_link.$t_name;
		$day['selected'] = $t_name===$tname[0] ? 'class="selected"' : '';
		array_push( $days, $day );
	}
}
$smarty->assign( 'days', $days );

// 時間選択
$toptimes = array();
$lp_lmt   = 28;
for( $i = 0 ; $i < $lp_lmt; $i+=2 ){
	$tmp = array();
	$tmp['hour'] = sprintf( '%02d', $i<=24 ? $i : $i-24 );
	$tmp_time    = $i<24 ? $top_time : $top_time + 24 * 60 * 60;
	$tmp['link'] = $get_param2.'&time='.date('Ymd', $tmp_time ).sprintf('%02d', $i<24 ? $i : $i-24 );
	array_push( $toptimes, $tmp );
}
$smarty->assign( 'toptimes' , $toptimes );

// 時刻欄
$tvtimes = array();
$iMax    = $single_tuner!==FALSE ? 24 : $program_length;
for( $i = 0 ; $i < $iMax; $i++ ){
	$tmp = array();
	$tmp_time    = $top_time + 3600 * $i;
	$tmp['hour'] = date('H', $tmp_time );
	$tmp['link'] = $get_param2.'&time='.date('YmdH', $tmp_time );
	array_push( $tvtimes, $tmp );
}
$smarty->assign( 'tvtimes',  $tvtimes );

$smarty->assign( 'pre8link', $get_param2.'&time='.date('YmdH', $top_time - 8*3600 ) );
$smarty->assign( 'prelink',  $get_param2.'&time='.date('YmdH', $top_time - 3600 ) );
$smarty->assign( 'aft8link', $get_param2.'&time='.date('YmdH', $top_time + 8*3600 ) );

$smarty->assign( 'delete_select', $settings->delete_select );
$smarty->assign( 'programs', $programs );
$smarty->assign( 'ch_set_width', $ch_set_width );
$smarty->assign( 'chs_width', $chs_width );
$smarty->assign( 'height_per_hour', $height_per_hour );
$smarty->assign( 'height_per_min', (int)($height_per_hour / 60) );
$smarty->assign( 'single_tuner', $single_tuner!==FALSE ? TRUE : FALSE );
$smarty->assign( 'dayweeks', array('日','月','火','水','木','金','土') );
$smarty->assign( '__nowDay', date('d', $now_time) );

$sitetitle = '予約遷移表 '.date( 'Y', $top_time ) . '年' . date( 'm', $top_time ) . '月' . date( 'd', $top_time ) . '日'. date( 'H', $top_time ) .
              '時～';

$smarty->assign('sitetitle', $sitetitle );

$smarty->assign('top_time', str_replace( '-', '/' ,toDatetime($top_time)) );
$smarty->assign('last_time', str_replace( '-', '/' ,toDatetime($last_time)) );
$smarty->assign( 'menu_list', link_menu_create() );
$smarty->assign( 'spool_freesize', spool_freesize() );


$smarty->display('revchartTable.html');
?>

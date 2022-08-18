<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/epg_const.php' );

// 設定ファイルの有無を検査する
if( ! file_exists( INSTALL_PATH.'/settings/config.xml') && !file_exists( '/etc/epgrecUNA/config.xml' ) ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}

$settings = Settings::factory();

$week_tb = array( '日', '月', '火', '水', '木', '金', '土' );

$page         = 1;
$pager_option = '';
$full_mode    = FALSE;
$order        = 'starttime+ASC';
if( isset($_REQUEST['order']) ){
        $order = str_replace( ' ', '+', $_REQUEST['order'] );
}

try{
	$res_obj = new DBRecord( RESERVE_TBL );
	$rvs     = $res_obj->fetch_array( null, null, 'complete=0 and now() <= starttime ORDER BY '.str_replace( '+', ' ', $order ));
	$res_cnt = count( $rvs );

	if( ( SEPARATE_RECORDS_RESERVE===FALSE && SEPARATE_RECORDS<1 ) || ( SEPARATE_RECORDS_RESERVE!==FALSE && SEPARATE_RECORDS_RESERVE<1 ) )	// "<1"にしているのはフェイルセーフ
		$full_mode = TRUE;
	else{
		if( isset( $_GET['page']) ){
			if( $_GET['page'] === '-' )
				$full_mode = TRUE;
			else
				$page = (int)$_GET['page'];
		}
		$separate_records = SEPARATE_RECORDS_RESERVE!==FALSE ? SEPARATE_RECORDS_RESERVE : SEPARATE_RECORDS;
		$view_overload    = VIEW_OVERLOAD_RESERVE!==FALSE ? VIEW_OVERLOAD_RESERVE : VIEW_OVERLOAD;
		if( $res_cnt <= $separate_records+$view_overload )
			$full_mode = TRUE;
	}

	if( $full_mode ){
		$start_record = 0;
		$end_record   = $res_cnt;
		$pager_option .= 'page=-&';
	}else{
		$start_record = ( $page - 1 ) * $separate_records;
		$end_record   = $page * $separate_records;
	}

	$reservations = array();
	$ch_name      = array();
	$ch_disc      = array();
	foreach( $rvs as $key => $r ){
		$arr = array();
		if( $start_record<=$key && $key<$end_record ){
			$arr['id']      = $r['id'];
			$arr['type']    = $r['type'];
			$arr['tuner']   = $r['tuner'];
			$arr['channel'] = $r['channel'];
			if( !isset( $ch_name[$r['channel_id']] ) ){
				$ch                        = new DBRecord( CHANNEL_TBL, 'id', $r['channel_id'] );
				$ch_name[$r['channel_id']] = $ch->name;
				$ch_disc[$r['channel_id']] = $ch->channel_disc;
				$ch_network_id[$r['channel_id']] = $ch->network_id;
				$ch_sid[$r['channel_id']] = $ch->sid;
			}
			if( $r['program_id'] ){
				try{
					$prg = new DBRecord( PROGRAM_TBL, 'id', $r['program_id'] );
					$sub_genre = $prg->sub_genre;
					if(isset($record_cmd[$r['type']]['program_rec']['command'])){
						$source    = '<br>番組予約:<br><small><small>'.sprintf('%d%05d%05d', $ch_network_id[$r['channel_id']], $ch_sid[$r['channel_id']], $prg->eid).'</small></small>';
					}else{
						$source    = '<br>時刻予約:<br>'.$r['channel_disc'];
					}
				}catch( exception $e ) {
					reclog( 'reservationTable.php::予約ID:'.$r['id'].'  '.$e->getMessage(), EPGREC_ERROR );
					$sub_genre = 16;
					$source    = '<br>時刻予約:<br>'.$r['channel_disc'];
				}
			}else{
				$sub_genre = 16;
				$source    = '<br>時刻予約:<br>'.$r['channel_disc'];
			}
			$arr['source']	     = $source;
			$start_time          = toTimestamp($r['starttime']);
			$end_time            = toTimestamp($r['endtime']);
			$arr['date']         = date( 'm/d(', $start_time ).$week_tb[date( 'w', $start_time )].')';
			$arr['starttime']    = date( 'H:i:s-', $start_time );
			$arr['endtime']      = !$r['shortened'] ? date( 'H:i:s', $end_time ) : '<font color="#0000ff">'.date( 'H:i:s', $end_time ).'</font>';
			$arr['duration']     = date( 'H:i:s', $end_time-$start_time-9*60*60 );
			$arr['prg_top']      = date( 'YmdH', ((int)$start_time/60)%60 ? $start_time : $start_time-60*60*1 );
			$arr['channel_name'] = '<a href="index.php?ch='.$ch_disc[$r['channel_id']].'&time='.$arr['prg_top'].'" title="単局EPG番組表へジャンプ">'.$ch_name[$r['channel_id']].'</a>';
			$arr['mode']         = $RECORD_MODE[$r['mode']]['name'];
			$arr['trans_mode']   = '';
			if( $r['autorec'] ) {
				$tran_ex = DBRecord::createRecords( TRANSEXPAND_TBL, 'WHERE key_id='.$r['autorec'].' AND type_no=0 ORDER BY id' );
			} else {
				$tran_ex = DBRecord::createRecords( TRANSEXPAND_TBL, 'WHERE key_id=0 AND type_no='.$r['id'].' ORDER BY id' );
			}
			if( $tran_ex ) {
				foreach( $tran_ex as $tran ) {
					if( $r['mode'] != $tran->mode ){
						$arr['trans_mode'] .= '<br>変換:'.$RECORD_MODE[(int)$tran->mode]['name'];
					}
				}
			}
			$arr['title']        = $r['title'];
			$arr['pre_title']    = strtr($r['pre_title'], array_column(ProgramMark, 'char', 'name'));
			$arr['post_title']   = strtr($r['post_title'], array_column(ProgramMark, 'char', 'name'));
			$mark_sp             = str_replace('][', ']&nbsp[', $r['pre_title'].$r['post_title']);
			$mark_bracketL       = str_replace('[', '<span class="mark_class">', $mark_sp);
			$mark_bracketR       = str_replace(']', '</span>', $mark_bracketL);
			$arr['mark']         = $mark_bracketR; // $p->mark;
			$arr['description']  = $r['description'];
			$arr['cat']          = $r['category_id'];
			$arr['autorec']      = $r['autorec'];
			$arr['keyword']      = putProgramHtml( $arr['title'], '*', 0, $r['category_id'], 16 );
			array_push( $reservations, $arr );
		}
	}


	$smarty = new Smarty();
	$smarty->template_dir = INSTALL_PATH . "/templates/";
	$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
	$smarty->cache_dir = INSTALL_PATH . "/cache/";
	$smarty->assign( 'sitetitle','録画予約一覧');
	$smarty->assign( 'reservations', $reservations );
	$smarty->assign( 'spool_freesize', spool_freesize() );
	$smarty->assign( 'pager', $full_mode ? '' : make_pager( 'reservationTable.php', $separate_records, $res_cnt, $page, $pager_option.'order='.$order.'&' ) );
	$smarty->assign( 'pager_option', 'reservationTable.php?'.$pager_option );
	$smarty->assign( 'order', $order );
	$smarty->assign( 'menu_list', link_menu_create() );
	$smarty->display('reservationTable.html');
}
catch( exception $e ) {
	exit( $e->getMessage() );
}
?>

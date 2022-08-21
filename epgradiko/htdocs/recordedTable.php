<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/epg_const.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );

function view_strlen( $str ){
	$byte_len = strlen( $str );
	$str_len  = mb_strlen( $str );
	$mc = (int)(( $byte_len - $str_len ) / 2);
	$sc = $str_len - $mc;
	return $mc*2+$sc;
}


function box_pad( $str, $width ){
/*
	// いいかげんなので保留
	$str_wd = view_strlen( $str );
	if( ($width-$str_wd)%2 === 1 )
		$str .= ' ';
	if( ($width-$str_wd)/4 > 0 )
		$str .= str_repeat( '　', ($width-$str_wd)/4 );
	return $str;
*/
	return '['.$str.']';
}

// 設定ファイルの有無を検査する
if( ! file_exists( INSTALL_PATH.'/settings/config.xml') && !file_exists( '/etc/epgrecUNA/config.xml' ) ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}

$settings = Settings::factory();

$week_tb = array( '日', '月', '火', '水', '木', '金', '土' );

$search       = '';
$category_id  = 0;
$station      = 0;
$key_id       = FALSE;
$page         = 1;
$pager_option = '';
$full_mode    = FALSE;
$order        = 'starttime+DESC';


$options = 'starttime<\''. date('Y-m-d H:i:s').'\'';	// ながら再生は無理っぽい？

$rev_obj = new DBRecord( RESERVE_TBL );

$act_trans = array_key_exists( 'tsuffix', end($RECORD_MODE) );
if( $act_trans )
	$trans_obj = new DBRecord( TRANSCODE_TBL );

if( isset( $_REQUEST['key']) )
	$key_id = (int)$_REQUEST['key'];

$rev_opt = $key_id!==FALSE ? ' AND autorec='.$key_id : '';


if( isset($_REQUEST['search']) ){
	if( $_REQUEST['search'] !== '' ){
		$search = $_REQUEST['search'];
		foreach( explode( ' ', trim($search) ) as $key ){
			$k_len = strlen( $key );
			if( $k_len>1 && $key[0]==='-' ){
				$k_len--;
				$key      = substr( $key, 1 );
				$rev_opt .= ' AND CONCAT(title,\' \', description) NOT LIKE ';
			}else
				$rev_opt .= ' AND CONCAT(title,\' \', description) LIKE ';
			if( $key[0]==='"' && $k_len>2 && $key[$k_len-1]==='"' )
				$key = substr( $key, 1, $k_len-2 );
			$sql_escape_text = $rev_obj->sql_escape( $key );
			$rev_opt .= '\'%'.$sql_escape_text.'%\'';
		}
	}
}
if( isset($_REQUEST['category_id']) ){
	if( $_REQUEST['category_id'] != 0 ){
		$category_id = $_REQUEST['category_id'];
		$rev_opt    .= ' AND category_id='.$_REQUEST['category_id'];
	}
}
if( isset($_REQUEST['station']) ){
	if( $_REQUEST['station'] != 0 ){
		$station  = $_REQUEST['station'];
		$rev_opt .= ' AND channel_id='.$_REQUEST['station'];
	}
}
if( isset($_REQUEST['full_mode']) )
	$full_mode = $_REQUEST['full_mode']==1;

if( isset($_REQUEST['order']) ){
	$order = str_replace( ' ', '+', $_REQUEST['order'] );
}

if( isset($_POST['do_delete']) && $_POST['do_delete']){
	$delete_file = isset($_POST['delrec']) && $_POST['delrec'];
	$id_list     = $rev_obj->fetch_array( null, null, '1=1'.$rev_opt );
	$del_list = array();
	foreach( $id_list as $del_id ){
		if( in_array($del_id['id'], $_POST['del']) ){
			array_push( $del_list, $del_id );
		}
	}
	foreach( $del_list as $rec ){
		$transcodes = $trans_obj->fetch_array( null, null, 'rec_id='.$rec['id'].' ORDER BY status' );
		foreach( $transcodes as $transcode ){
			// 処理中はキャンセル
			if( $transcode['status'] == 1){
				killtree( (int)$transcode['pid'] );
				sleep(1);
			}
			if( $delete_file ) @unlink( $transcode['path'] );
			@unlink( INSTALL_PATH.'/'.$settings->plogs.'/'.$rec['id'].'_'.$transcode['id'].'.ffmpeglog' );
			$trans_obj->force_delete( $transcode['id'] );
		}

		// 予約取り消し実行(delete)
		try {
			$ret_code = Reservation::cancel( $rec['id'], $delete_file );
		}catch( Exception $e ){
			// 無視
		}
	}
}


try{
	// CH一覧作成
	$ch_list   = $rev_obj->distinct( 'channel_id', 'WHERE '.$options );
	$ch_opt    = count( $ch_list ) ? ' AND id IN ('.implode( ',', $ch_list ).')' : '';
	$stations  = array();
	$chid_list = array();
	$stations[0]['id']       = $chid_list[0] = 0;
	$stations[0]['name']     = 'すべて';
	$stations[0]['selected'] = (! $station) ? 'selected' : '';
	$stations[0]['count']    = 0;

	foreach( $GR_CHANNEL_MAP as $channel_disc => $channel ) {
		if( $channel == 'NC' ) continue;
		if( !DBRecord::countRecords( CHANNEL_TBL, 'WHERE channel_disc="'.$channel_disc.'"' ) ) continue;
		$c = new DBRecord( CHANNEL_TBL, 'channel_disc', $channel_disc );
		if( !in_array( (int)$c->id, $ch_list) ) continue;
		$arr = array();
		$arr['id']       = $chid_list[] = (int)$c->id;
		$arr['name']     = $c->name;
		$arr['selected'] = $station == $c->id ? 'selected' : '';
		$arr['count']    = 0;
		array_push( $stations, $arr );
	}
	foreach( $BS_CHANNEL_MAP as $channel_disc => $channel ) {
		if( $channel == 'NC' ) continue;
		if( !DBRecord::countRecords( CHANNEL_TBL, 'WHERE channel_disc="'.$channel_disc.'"' ) ) continue;
		$c = new DBRecord( CHANNEL_TBL, 'channel_disc', $channel_disc );
		if( !in_array( (int)$c->id, $ch_list) ) continue;
		$arr = array();
		$arr['id']       = $chid_list[] = (int)$c->id;
		$arr['name']     = $c->name;
		$arr['selected'] = $station == $c->id ? 'selected' : '';
		$arr['count']    = 0;
		array_push( $stations, $arr );
	}
	foreach( $CS_CHANNEL_MAP as $channel_disc => $channel ) {
		if( $channel == 'NC' ) continue;
		if( !DBRecord::countRecords( CHANNEL_TBL, 'WHERE channel_disc="'.$channel_disc.'"' ) ) continue;
		$c = new DBRecord( CHANNEL_TBL, 'channel_disc', $channel_disc );
		if( !in_array( (int)$c->id, $ch_list) ) continue;
		$arr = array();
		$arr['id']       = $chid_list[] = (int)$c->id;
		$arr['name']     = $c->name;
		$arr['selected'] = $station == $c->id ? 'selected' : '';
		$arr['count']    = 0;
		array_push( $stations, $arr );
	}
	foreach( $EX_CHANNEL_MAP as $channel_disc => $channel ) {
		if( $channel == 'NC' ) continue;
		if( !DBRecord::countRecords( CHANNEL_TBL, 'WHERE channel_disc="'.$channel_disc.'"' ) ) continue;
		$c = new DBRecord( CHANNEL_TBL, 'channel_disc', $channel_disc );
		if( !in_array( (int)$c->id, $ch_list) ) continue;
		$arr = array();
		$arr['id']       = $chid_list[] = (int)$c->id;
		$arr['name']     = $c->name;
		$arr['selected'] = $station == $c->id ? 'selected' : '';
		$arr['count']    = 0;
		array_push( $stations, $arr );
	}
//	$chid_list = array_column( $stations, 'id' );		// PHP5.5

	// カテゴリー一覧作成
	$cat_list = $rev_obj->distinct( 'category_id', 'WHERE '.$options );
	$cat_opt  = count( $cat_list ) ? 'WHERE id IN ('.implode( ',', $cat_list ).')' : '';
	$crecs    = DBRecord::createRecords( CATEGORY_TBL, $cat_opt );
	$cats     = array();
	$cats[0]['id'] = 0;
	$cats[0]['name'] = 'すべて';
	$cats[0]['selected'] = $category_id == 0 ? 'selected' : '';
	$cats[0]['count']    = 0;
	$ct_len = 0;
	foreach( $crecs as $c ){
		$arr = array();
		$arr['id']       = $c->id;
		$arr['name']     = $c->name_jp;
		$tmp_len = view_strlen( $arr['name'] );
		if( $ct_len < $tmp_len )
			$ct_len = $tmp_len;
		$arr['selected'] = $c->id == $category_id ? 'selected' : '';
		$arr['count']    = 0;
		array_push( $cats, $arr );
	}

	// 自動キーワード一覧作成
	$cs_rec_flg = (boolean)$settings->cs_rec_flg;
	$key_list = $rev_obj->distinct( 'autorec', 'WHERE '.$options );
	$key_opt  = count( $key_list ) ? 'WHERE id IN ('.implode( ',', $key_list ).') ORDER BY sort_order, id' : '';
	$crecs    = DBRecord::createRecords( KEYWORD_TBL, $key_opt );
	$keyid_list = array();
	$keys     = array();
	$keys[0]['id']       = $keyid_list[] = 0;
	$keys[0]['name']     = '《手動録画》';
	$keys[0]['kw_name']  = '《手動録画》';
	$keys[0]['selected'] = $key_id===0 ? 'selected' : '';
	$keys[0]['count']    = 0;
	$id_len = $sn_len = 0;
	foreach( $crecs as $c ){
		$arr = array();
		$arr['id'] = $keyid_list[] = $c->id;
		$tmp_len = view_strlen( $arr['id'] );
		if( $id_len < $tmp_len )
			$id_len = $tmp_len;
		if( (int)$c->channel_id ){
			$chid_key     = array_search( (int)$c->channel_id, $chid_list );
			$station_name = $chid_key!==FALSE ? $stations[$chid_key]['name'] : '';
		}else
			$station_name = '';
		if( $station_name === '' ){
			if( !$c->typeGR || ( $settings->bs_tuners>0 && ( !$c->typeBS || ( $cs_rec_flg && !$c->typeCS ) ) ) || ( $settings->ex_tuners>0 && !$c->typeEX ) ){
				$types = array();
				if( $c->typeGR )
					$types[] = 'GR';
				if( $settings->bs_tuners > 0 ){
					if( $c->typeBS )
						$types[] = 'BS';
					if( $cs_rec_flg && $c->typeCS )
						$types[] = 'CS';
				}
				if( $settings->ex_tuners>0 && $c->typeEX )
					$types[] = 'EX';
				$station_name = implode( '+', $types );
			}else
				$station_name = 'ALL';
		}
		$arr['station']  = $station_name;
		$tmp_len = view_strlen( $station_name );
		if( $sn_len < $tmp_len )
			$sn_len = $tmp_len;
		if( $c->keyword !== '' ){
			$keywds = array();
			foreach( explode( ' ', trim($c->keyword) ) as $key ){
				if( strlen( $key )>0 && $key[0]!=='-' ){
					$keywds[] = $key;
				}
			}
			$arr['name'] = str_replace( '%', ' ', implode( ' ', $keywds ) );
		}else
			$arr['name'] = '';
		$arr['kw_name']  = $c->name;
		$arr['cat']      = (int)$c->category_id;
		$arr['subgenre'] = (int)$c->sub_genre;
		$arr['selected'] = (int)$c->id===$key_id ? ' selected' : '';
		$arr['count']    = 0;
		array_push( $keys, $arr );
	}


	$rvs = $rev_obj->fetch_array( null, null, $options.$rev_opt.' ORDER BY '.str_replace( '+', ' ', $order ) );
	$stations[0]['count'] = $cats[0]['count'] = count( $rvs );

	if( ( SEPARATE_RECORDS_RECORDED===FALSE &&  SEPARATE_RECORDS<1 ) || ( SEPARATE_RECORDS_RECORDED!==FALSE && SEPARATE_RECORDS_RECORDED<1 ) )	// "<1"にしているのはフェイルセーフ
		$full_mode = TRUE;
	else{
		if( isset( $_GET['page']) ){
			if( $_GET['page'] === '-' )
				$full_mode = TRUE;
			else
				$page = (int)$_GET['page'];
		}
		$separate_records = SEPARATE_RECORDS_RECORDED!==FALSE ? SEPARATE_RECORDS_RECORDED : SEPARATE_RECORDS;
		$view_overload    = VIEW_OVERLOAD_RECORDED!==FALSE ? VIEW_OVERLOAD_RECORDED : VIEW_OVERLOAD;
		if( $stations[0]['count'] <= $separate_records+$view_overload )
			$full_mode = TRUE;
	}

	if( $full_mode ){
		$start_record  = 0;
		$end_record    = $stations[0]['count'];
		$pager_option .= 'page=-&';
	}else{
		$start_record = ( $page - 1 ) * $separate_records;
		$end_record   = $page * $separate_records;
	}
	if( $key_id !== FALSE )
		$pager_option .= 'key='.$key_id.'&';
	if( $search !== '' )
		$pager_option .= 'search='.htmlspecialchars($search,ENT_QUOTES).'&';
	if( $category_id !== 0 )
		$pager_option .= 'category_id='.$category_id.'&';
	if( $station !== 0 )
		$pager_option .= 'station='.$station.'&';

	$part_path = explode( '/', $_SERVER['PHP_SELF'] );
	array_pop( $part_path );
	$base_path = implode( '/', $part_path );
	$view_url = $base_path;
	$transcode = TRANSCODE_STREAM && $NET_AREA!==FALSE && $NET_AREA!=='H';
	$records = array();
	foreach( $rvs as $key => $r ){
		$arr = array();
		if( (int)$r['channel_id'] ){
			$chid_key = array_search( (int)$r['channel_id'], $chid_list );
			if( $chid_key !== FALSE ){
				$arr['station_name'] = $stations[$chid_key]['name'];
				$stations[$chid_key]['count']++;
			}else{
				$arr['station_name'] = 'lost';
			}
		}else
			$arr['station_name'] = 'lost';
		$arr['cat'] = (int)$r['category_id'];
		if( $arr['cat'] ){
			$cat_key = array_search( $arr['cat'], $cat_list );
			if( $cat_key !== FALSE )
				$cats[$cat_key+1]['count']++;
		}
		$arr['key_id'] = (int)$r['autorec'];
		if( $arr['key_id'] ){
			if( DBRecord::countRecords( KEYWORD_TBL, 'WHERE id='.$arr['key_id'] )==0 ){
				$wrt_set = array();
				$arr['key_id'] = $wrt_set['autorec'] = 0;
				$rev_obj->force_update( $r['id'], $wrt_set );
			}
		}
		$keys[array_search($arr['key_id'],$keyid_list)]['count']++;
		if( $start_record<=$key && $key<$end_record ){
			$arr['id']          = (int)$r['id'];
			$start_time         = toTimestamp($r['starttime']);
			$end_time           = toTimestamp($r['endtime']);
			$arr['starttime']   = date( 'm/d(', $start_time ).$week_tb[date( 'w', $start_time )].')<br>'.date( 'H:i:s-', $start_time );
			$arr['endtime']     = !$r['shortened'] ? date( 'H:i:s', $end_time ) : '<font color="#0000ff">'.date( 'H:i:s', $end_time ).'</font>';
			$arr['duration']    = date( 'H:i:s', $end_time-$start_time-9*60*60 );
			$arr['asf']         = 'viewer.php?reserve_id='.$r['id'];
//			$arr['video']       = 'javascript:$(\'#id_video_'.$r['id'].'\')[0].play();';
			$arr['video']       = 'javascript:$(\'#id_video_'.$r['id'].'\')[0].paused ? $(\'#id_video_'.$r['id'].'\')[0].play() : $(\'#id_video_'.$r['id'].'\')[0].pause();';
                        $arr['title']       = htmlspecialchars($r['title'],ENT_QUOTES);
                        $arr['pre_title']   = htmlspecialchars(strtr($r['pre_title'], array_column(ProgramMark, 'char', 'name')),ENT_QUOTES);
                        $arr['post_title']  = htmlspecialchars(strtr($r['post_title'], array_column(ProgramMark, 'char', 'name')),ENT_QUOTES);
			$mark_sp            = str_replace('][', ']&nbsp[', $r['pre_title'].$r['post_title']);
			$mark_bracketL      = str_replace('[', '<span class="mark_class">', $mark_sp);
			$mark_bracketR      = str_replace(']', '</span>', $mark_bracketL);
			$arr['mark']        = $mark_bracketR; // $p->mark;
			$arr['description'] = $r['description'];
			$explode_text       = explode( '/', $r['path'] );
			$thumb_file = end($explode_text).'.jpg';
			if( file_exists(INSTALL_PATH.$settings->thumbs.'/'.$thumb_file) ){
				$arr['thumb'] = '<img src="/get_file.php?thumb='.$thumb_file.'" width="192px" height="auto"/>';
				$trans_set = get_lightest_trans($r['id']);
				$source_url = '/recorded/trans_id/'.$trans_set[0].'.'.$trans_set[2];
			}else
				$arr['thumb'] = '';
			$arr['packetlog'] = '';
			$arr['packetdlog'] = '';
			if( file_exists(INSTALL_PATH.$settings->plogs.'/'.end($explode_text).'.log' ) ) {
				$packetlog = file_get_contents( INSTALL_PATH.$settings->plogs.'/'.end($explode_text).'.log' );
				if( $packetlog === '' ) {
					$arr['packetlog'] = '<br><br>ts checking..';
				}else{
					$arr['packetlog'] = '<pre';
					if( file_exists(INSTALL_PATH.$settings->plogs.'/'.end($explode_text).'.pdl' ) ) {
						$arr['packetlog'] .= ' onClick="disp_plog(\''.end($explode_text).
									'.pdl\')" title="クリックで詳細ログ" style="cursor: pointer;"';
					}
					$arr['packetlog'] .= '>'.$packetlog.'</pre>';
				}
			}
			$arr['keyword']     = putProgramHtml( $r['title'], '*', 0, $r['category_id'], 16 );
			$arr['view_set'] = '';
			if( file_exists( INSTALL_PATH.$settings->spool.'/'.$r['path'] ) ){
				switch( $r['complete'] ) {
				case 0:
					if( time() > $start_time ){ //延長？失敗？
						if( !search_recps($r['id']) ){ //録画コマンド実行なし→失敗
							$wrt_set = array();
							$wrt_set['complete'] = 2;
							$rev_obj->force_update( $r['id'], $wrt_set );
							reclog('予約ID:'.$r['id'].' 録画開始時間超過 録画ジョブなしのためエラー', EPGREC_ERROR);
							$bg_color = 'red';
							$add_fileset = '<br>stop';
						}else{
							if( time() > $end_time ){ //録画中
								$bg_color = 'orange';
								$add_fileset = '<br>extend rec..';
							}else{
								$bg_color = 'greenyellow';
								$add_fileset = '<br>recording..';
							}
						}
					}else{
						$bg_color = 'greenyellow';
						$add_fileset = '<br>recording..';
					}
					break;
				case 1:
					$bg_color = 'limegreen';
					$add_fileset = '';
					break;
				case 2:
					$bg_color = 'red';
					$add_fileset = '<br>stop';
					break;
				case other:
					break;
				}
				$explode_text = explode(".", $record_cmd[$r['type']]['suffix']);
				$arr['file_set'] = '<a href="'.$arr['asf'].'" target="_blank" title="クリックすると視聴できます（視聴アプリを関連付けている必要があります）"'.
							' style="background-color: '.$bg_color.'; color: black;">'.trim(end($explode_text))."</a>";
				if( $transcode )	// 録画中のトランスコードストリームも可能
					$arr['file_set'] .= '&nbsp;<a href="'.$arr['asf'].'&trans=ON" target="_blank" title="トランスコード視聴" id="trans_url_'.($key-$start_record).
								'" style="color: white; background-color: royalblue;">&nbsp;視聴&nbsp;</a>';
				if( $r['complete'] > 0 ) 
					$arr['file_set'] .= '<br><input type="button" value="Download" title="ダウンロード" '.
								'onClick="javascript:PRG.downdialog(\''.$arr['id'].'\',\''.$arr['duration'].'\')" style="padding:0;">';
				$arr['file_set'] .= $add_fileset;
				// マニュアル・トランスコード
//				if( $act_trans ){
//					$arr['view_set'] .= ' <a href="manualtrans.php?reserve_id='.$r['id'].'&trans=ON" title="マニュアル・トランスコード" id="trans_url_'.($key-$start_record).
//										'" style="color: white; background-color: royalblue;">■</a>';
//				}
			}else{
				$arr['file_set'] = '';
			}
			if( $act_trans ){
				$tran_ex = $trans_obj->fetch_array( 'rec_id', $arr['id'] );
				foreach( $tran_ex as $loop => $tran_unit ){
					$element = '';
					switch( $tran_unit['status'] ){
						case 0:
							$element = '<a style="background-color: yellow;" title="wait trans"> '.$RECORD_MODE[$tran_unit['mode']]['name'].' </a>';
							break;
						case 1:
							get_ffmpeg_status($tran_unit['id']);
							$element = '<a style="background-color: greenyellow;" title="transcoding"> '.$tran_unit['name'].' </a>';
							break;
						case 2:
							if( file_exists( $tran_unit['path'] ) ){
								$element = '<a style="background-color: limegreen; color: black"'
									.'href="'.$arr['asf'].'&trans_id='.$tran_unit['id'].'" target="_blank" title="視聴"> '.$tran_unit['name'].' </a>';
								$element .= '<br><input type="button" value="Download" title="ダウンロード" onClick="location.href=\'sendstream.php?download&trans_id='.$tran_unit['id'].'\'" style="padding:0;">';
								$arr['thumb'] = '<video id="id_video_'.$r['id'].'" src="/recorded/trans_id/'.$tran_unit['id'].'.'.pathinfo($RECORD_MODE[$tran_unit['mode']]['tsuffix'], PATHINFO_EXTENSION).'" poster="/get_file.php?thumb='.$thumb_file.
									'" width="192px" preload="none" />';
							} else {
								$arr['view_set'] = '<a><del> '.$RECORD_MODE[$r['mode']]['name'].' </del></a>';
							}
							break;
						case 3:
							$element = '<a style="background-color: red; color: white;"'.
								( file_exists( $tran_unit['path'] ) ? ' href="'.$arr['asf'].'&trans_id='.$tran_unit['id'].'" target="_blank"' : '' ).' title="変換失敗"> '.$tran_unit['name'].' </a>';
							break;
					}
					if( $element !== '' ){
						if( $arr['view_set'] !== '' )
							$arr['view_set'] .= '<br>';
						$arr['view_set'] .= $element;
					}
					if( file_exists(INSTALL_PATH.$settings->plogs.'/'.$r['id'].'_'.$tran_unit['id'].'.ffmpeglog' ) ) {
						$ffmpeglog = file_get_contents( INSTALL_PATH.$settings->plogs.'/'.$r['id'].'_'.$tran_unit['id'].'.ffmpeglog' );
						$arr['ffmpeglog'] = '<pre>'.$ffmpeglog.'</pre>';
					}else{
						$arr['ffmpeglog'] = '<br><br>';
					}
				}
			}
			if( $arr['view_set'] === '' )
				if( array_key_exists('tsuffix',$RECORD_MODE[$r['mode']]))
					$arr['view_set'] = '<a> '.$RECORD_MODE[$r['mode']]['name'].' </a>';
			array_push( $records, $arr );
		}
	}

	if( $key_id === FALSE )
		$keys[0]['name'] = $keys[0]['name'].'('.$keys[0]['count'].') ';
	for( $piece=1; $piece<count($keys); $piece++ ){
		$cat_key  = array_search( $keys[$piece]['cat'], $cat_list );
		$cat_name = $cat_key===FALSE ? $cats[0]['name'] : $cats[$cat_key+1]['name'].'('.$keys[$piece]['subgenre'].')';
		if( $keys[$piece]['kw_name'] ) {
			$keys[$piece]['name'] = $keys[$piece]['kw_name'].'('.$keys[$piece]['count'].')';
		} else {
			$keys[$piece]['name'] = ($key_id===FALSE ? '('.$keys[$piece]['count'].') ' : '')
								.'ID:'.$keys[$piece]['id']
								.' '.htmlspecialchars($keys[$piece]['name']."\t",ENT_QUOTES)
								.htmlspecialchars(box_pad( $keys[$piece]['station'], $sn_len ).' '.box_pad( $cat_name, $ct_len ),ENT_QUOTES)
								.'('.$keys[$piece]['count'].')';
		}
	}

	if( $transcode && !TRANS_SCRN_ADJUST ){
		for( $cnt=0; $cnt<count($TRANSSIZE_SET); $cnt++ )
			$TRANSSIZE_SET[$cnt]['selected'] = $cnt===TRANSTREAM_SIZE_DEFAULT ? ' selected' : '';
	}

	if( isset($_COOKIE['podcast_urlscheme']) ){
		$protocol = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' || $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ? 'https' : 'http';

		$host = $_SERVER['HTTP_HOST'];
		$target_path = '/podcast.php';
		$address = '';
		$not_first_flg = FALSE;
		if( $key_id ) {
			if( $not_first_flg ) $address .= '&key_id='.rawurlencode($key_id);
			else {
				$address .= '?key_id='.rawurlencode($key_id);;
				$not_first_flg = TRUE;
			}
		}
		if ( $station ) {
			if( $not_first_flg ) $address .= '&station='.rawurlencode($station);
			else {
				$address .= '?station='.rawurlencode($station);
				$not_first_flg = TRUE;
			}
		}
		if ( $category_id ) {
			if( $not_first_flg ) $address .= '&category_id='.rawurlencode($category_id);
			else {
				$address .= '?category_id='.rawurlencode($category_id);
				$not_first_flg = TRUE;
			}
		}
		if ( $search ) {
			if( $not_first_flg ) $address .= '&search='.rawurlencode($search);
			else {
				$address .= '?search='.rawurlencode($search);
				$not_first_flg = TRUE;
			}
		}
		$base_address = $host.$settings->url.$target_path;
		$source_url = $protocol.'://'.$base_address.$address;
		$podcast_scheme = $_COOKIE['podcast_urlscheme'];
		$str_rep = array( '%PROTOCOL%'  => $protocol,
				'%ADDRESS%'     => $base_address.$address,
				'%address%'     => $base_address.rawurlencode($address),
		);
		$podcast = strtr( $podcast_scheme, $str_rep );
	}

	$smarty = new Smarty();
	$smarty->template_dir = INSTALL_PATH . "/templates/";
	$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
	$smarty->cache_dir = INSTALL_PATH . "/cache/";
	$smarty->assign( 'sitetitle','録画済一覧' );
	$smarty->assign( 'menu_list', link_menu_create() );
	$smarty->assign( 'spool_freesize', spool_freesize() );
	$smarty->assign( 'pager', $full_mode ? '' : make_pager( 'recordedTable.php', $separate_records, $stations[0]['count'], $page, $pager_option.'order='.$order.'&' ) );
	$smarty->assign( 'full_mode', $full_mode );
	$smarty->assign( 'pager_option', 'recordedTable.php?'.$pager_option );
	$smarty->assign( 'order', $order );
	$smarty->assign( 'delete_select', $settings->delete_select );
	$smarty->assign( 'records', $records );
	$smarty->assign( 'search', $search );
	$smarty->assign( 'stations', $stations );
	$smarty->assign( 'cats', $cats );
	$smarty->assign( 'keys', $keys );
	$smarty->assign( 'key_id', $key_id );
	$smarty->assign( 'station', $station );
	$smarty->assign( 'category_id', $category_id );
	$smarty->assign( 'use_thumbs', $settings->use_thumbs );
	$smarty->assign( 'use_plogs', $settings->use_plogs );
	$smarty->assign( 'TRANSCODE_STREAM', $transcode );
	$smarty->assign( 'TRANS_SCRN_ADJUST', $transcode && TRANS_SCRN_ADJUST ? 1 : 0 );
	$smarty->assign( 'transsize_set', $TRANSSIZE_SET );
	$smarty->assign( 'transsize_set_cnt', count($records) );
	if( isset($podcast) ) $smarty->assign( 'podcast', $podcast );
//	$smarty->assign( 'trans_mode', $act_trans ? $TRANS_MODE : FALSE );
	$smarty->display('recordedTable.html');
}
catch( exception $e ){
	exit( $e->getMessage() );
}
?>

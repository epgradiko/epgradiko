#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );

function sheep_release( $cmd ) {
	$descspec = array(
					0 => array( 'file','/dev/null','r' ),
					1 => array( 'file','/dev/null','w' ),
					2 => array( 'file','/dev/null','w' ),
	);
	$pro = proc_open( $cmd, $descspec, $pipes );
	if( is_resource( $pro ) )
		return $pro;
	return false;
}

function create_sql_time( $tmp_time ) {
	global	$settings;

	return ' AND endtime>subtime( now(), sec_to_time('.($settings->extra_time+2).') ) AND starttime<addtime( now(), sec_to_time('.$tmp_time.') )';
}

function get_ch_disk( $ch_obj, $ch_disk ){
	$ch_list = $ch_obj->distinct( 'channel_disc', 'WHERE channel_disc LIKE "'.$ch_disk.'$_%" ESCAPE "$"' );
	return count( $ch_list ) ? $ch_list[0] : $ch_disk;		//初回起動対処
}

function rest_check( $ch_disk, $sql_time ){
	$ch_tmp  = strpos( $ch_disk, '_' )!==FALSE ? $ch_disk : $ch_disk.'_%';		//初回起動対処
	$pro_sql = 'WHERE channel_disc LIKE "'.$ch_tmp.'"'.$sql_time;
	$num     = DBRecord::countRecords( PROGRAM_TBL, $pro_sql.' AND ( title LIKE "%放送%休止%" OR title LIKE "%放送設備%" )' );
	if( $num === 0 ){
		return FALSE;			//放送中or初回起動
	}else
		return TRUE;			//停波中
}
	run_user_regulate();
	$settings      = Settings::factory();
	$tuners        = (int)$settings->gr_tuners;
	$usable_tuners = (int)$argv[1];

	$map           = array_filter(array_unique($GR_CHANNEL_MAP), function($e){return $e!=='NC';});
// 地上波を処理する
if( $usable_tuners !== 0 ){
	$rec_time  = FIRST_REC;
	$base_time = $rec_time + $settings->rec_switch_time + 2;
	$sql_time  = create_sql_time( $base_time );
	if( !( $current = current( $map ) ) )
		exit();
	$explode_text = explode('_', key( $map ));
	$ch_disk = $explode_text[0];
	$value = $current;
	next( $map );
	$ch_obj  = new DBRecord( CHANNEL_TBL );
	$ch_disc = get_ch_disk( $ch_obj, $ch_disk );
	$loop_tim = 10;
	$sql_cmd  = 'complete=0 AND type="GR"'.create_sql_time( $base_time + $settings->rec_switch_time + $settings->former_time + $loop_tim + 2 );
	$sql_chk  = 'complete=0 AND type="GR" AND starttime>now() AND starttime<addtime( now(), sec_to_time('.( $base_time + PADDING_TIME ).') )';
	$use_cnt  = 0;
	$end_flag = FALSE;
	$pro_cnt  = 0;
	$pro      = array();
	$res_obj  = new DBRecord( RESERVE_TBL );
	do{
		if( !$end_flag ){
			if( $use_cnt < $usable_tuners ){
				// 録画重複チェック
				$revs       = $res_obj->fetch_array( null, null, $sql_cmd );
				$off_tuners = count( $revs );
				if( $off_tuners+$use_cnt < $tuners ){
					$lp_st = time();
					do{
						//空チューナー降順探索
						for( $slc_tuner=$tuners-1; $slc_tuner>=0; $slc_tuner-- ){
							for( $cnt=0; $cnt<$off_tuners; $cnt++ ){
								if( $revs[$cnt]['tuner'] == $slc_tuner )
									continue 2;
							}
							$rr = $res_obj->fetch_array( null, null, $sql_chk );
							if( count( $rr ) > 0 ){
								$motion = TRUE;
								if( $slc_tuner < (int)$settings->gr_tuners ){
									foreach( $rr as $rev ){
										if( $rev['tuner'] < (int)$settings->gr_tuners ){
											$motion = FALSE;
											break;
										}
									}
								}else{
									foreach( $rr as $rev ){
										if( $rev['tuner'] >= (int)$settings->gr_tuners ){
											$motion = FALSE;
											break;
										}
									}
								}
							}else
								$motion = TRUE;

							if( $motion ){
								// 停波再確認と受信CH更新
								while(1){
									if( !rest_check( $ch_disc, $sql_time ) )
										break;
									if( !( $current = current( $map ) ) ){
										$end_flag = TRUE;
										goto GATHER_SHEEPS;		// 終了
									}
									$explode_text = explode('_', key( $map ));
									$ch_disk = $explode_text[0];
									$value = $current;
									next( $map );
									$ch_disc = get_ch_disk( $ch_obj, $ch_disk );
								}

								$cmdline = INSTALL_PATH.'/bin/airwavesSheep.php GR '.$slc_tuner.' '.$value.' '.$rec_time.' '.$ch_disk;
								// 除外sid抽出
								$cut_sids = array();
								$cnt	  = 0;
								$nc_keys  = array_keys( $GR_CHANNEL_MAP, 'NC' );
								if( $nc_keys !== FALSE ){
									foreach( $nc_keys as $th_ch ){
										$tg_sid 	  = explode( '_', $th_ch );
										if( $tg_sid[0] == $ch_disk ){
											$cut_sids[$cnt++] = (string)$tg_sid[1];
										}
									}
								}
								$cmdline .= ' 0';
//								if( !HIDE_CH_EPG_GET ){
//									$chs_obj = new DBRecord( CHANNEL_TBL );
//									$cuts	 = $chs_obj->fetch_array( null, null,'channel_disc like "'.$ch_disk.'%" and skip=1 AND type="'.$type[$key].'"' );
//									$hit	 = count( $cuts );
//									if( $hit ){
//										foreach( $cuts as $cut_ch ){
//											if( in_array( (string)$cut_ch['sid'], $cut_sids ) === FALSE )
//												$cut_sids[$cnt++] = (string)$cut_ch['sid'];
//										}
//									}
//								}
								if( $cnt ) $cmdline .= ' '.implode( ',', $cut_sids );

								$rec_pro = sheep_release( $cmdline );
								if( $rec_pro !== FALSE )
									$pro[] = $rec_pro;
								else{
									reclog( 'sheepdog.php::コマンドに異常がある可能性があります<br>'.$cmdline, EPGREC_WARN );
									$end_flag = TRUE;
									goto GATHER_SHEEPS;		// 終了
								}
								$use_cnt++;

								// 受信CH更新
								while(1){
									if( $current = current( $map ) ){
										$explode_text = explode('_', key( $map ));
										$ch_disk = $explode_text[0];
										$value = $current;
										next( $map );
										$ch_disc = get_ch_disk( $ch_obj, $ch_disk );
										if( !rest_check( $ch_disc, $sql_time ) )
											continue 4;
									}else{
										$end_flag = TRUE;
										goto GATHER_SHEEPS;		// 終了
									}
								}
							}
						}
						sleep(1);
					}while( time()-$lp_st < $loop_tim );
					//時間切れ
				}else{
					//空チューナー無し
					//先行録画が同ChならそこからEPGを貰うようにしたい
					if( $off_tuners >= $tuners ){
						$end_flag = TRUE;
						goto GATHER_SHEEPS;		// 終了
					}
					sleep(1);
				}
			}else
				sleep(1);
			//EPG受信チューナー数確認
			$use = 0;
		}else
			sleep(1);
GATHER_SHEEPS:
		//全子プロセス(EPG受信・更新)終了待ち
		$pro_cnt = count($pro);
		if( $pro_cnt ){
			$cnt = 0;
			do{
				if( $pro[$cnt] !== FALSE ){
					$st = proc_get_status( $pro[$cnt] );
					if( $st['running'] == FALSE ){
						proc_close( $pro[$cnt] );
						array_splice( $pro, $cnt, 1 );
						$pro_cnt--;
					}else
						$cnt++;
				}else{
					array_splice( $pro, $cnt, 1 );
					$pro_cnt--;
				}
			}while( $cnt < $pro_cnt );
			$use_cnt = $pro_cnt;
		}
	}while( !$end_flag || $pro_cnt );
}
	exit();
?>

#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Keyword.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/storeProgram.inc.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );

function dog_release( $cmd ){
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

function exit_shephewrd(){
	global $shepherd_st;

	exit();
}

	run_user_regulate();
	$shepherd_st = time();
	$settings  = Settings::factory();
	$GR_tuners = (int)$settings->gr_tuners;
	$BS_tuners = (int)$settings->bs_tuners;
	// radiko
	$ex_use = (int)$settings->ex_tuners;
	$CS_flag   = $settings->cs_rec_flg==0 ? FALSE : TRUE;

	new single_Program('shepherd');

	garbageClean();			//  不要プログラム削除

/* 別口で対応
	// 定期EPG更新に録画開始前EPG更新が重ならないようにする。
	$sql_cmd = "WHERE complete = '0' AND starttime > now() AND starttime < addtime( now(), '00:13:00' )";
	while(1){
		$num = DBRecord::countRecords( RESERVE_TBL, $sql_cmd );
		if( $num ){
			$revs = DBRecord::createRecords( RESERVE_TBL, $sql_cmd.' ORDER BY starttime DESC' );
			$sleep_next = toTimestamp( $revs[0]->starttime );
			if( $sleep_next < $shepherd_st+2*60*60-(10+1)*60 )
				sleep( $sleep_next-time() );
			else
				exit_shephewrd();
		}else
			break;
	}
*/

	// 残留AT削除
	$res_obj = new DBRecord( RESERVE_TBL );
	$rvs     = $res_obj->fetch_array( null, null, 'complete=0 AND endtime<subtime(now(),'.sprintf( '"00:00:%02d"', (int)$settings->extra_time+3 ).')' );
//	foreach( $rvs as $r ){
//		switch( at_clean( $r ) ){
//			case 0:
//				// 予約終了化
//				$wrt_set['complete'] = 1;
//				$res_obj->force_update( $r['id'], $wrt_set );
//				continue 2;
//			case 1:	// トランスコード中
//				continue 2;
//			case 2:	// 別ユーザーでAT登録
//				break;
//		}
//	}

	$ps_output = shell_exec( PS_CMD.' 2>/dev/null' );
	$rarr      = explode( "\n", $ps_output );
	$my_pid    = posix_getpid();
	$kill_flg  = FALSE;
	for( $cc=0; $cc<count($rarr); $cc++ ){
		if( strpos( $rarr[$cc], 'shepherd.php' ) !== FALSE ){
			$ps = ps_tok( $rarr[$cc] );
			if( $my_pid === (int)$ps->pid ){
				$my_ppid = (int)$ps->ppid;
				foreach( $rarr as $ra ){
					if( strpos( $ra, 'shepherd.php' ) !== FALSE ){
						$ps       = ps_tok( $ra );
						$kill_pid = (int)$ps->pid;
						if( $kill_pid!==$my_pid && $kill_pid!==$my_ppid ){
							killtree( $rarr, $kill_pid );
							$kill_flg = TRUE;
						}
					}
				}
				if( $kill_flg )
					reclog( '前回の定期EPG更新が終了していなかったので中断させました。', EPGREC_WARN );
				break;
			}
		}
	}

	// EPG受信本数制御
	// 面倒なので手抜き テンポラリ容量は十分確保しましょう(^_^)
	$tmpdrive_size = disk_free_space( '/tmp' );
	$GR_num = count( array_filter(array_unique($GR_CHANNEL_MAP), function($e){return $e!=='NC';}) );
	if( $BS_tuners > 0 ){
		if( !$CS_flag ){
			$bs_max = 1;
			$bs_tim = array( 0, 220 + 15 + 30 );	// BS only
		}else{
			$bs_max = $BS_tuners>=3 ? 3 : $BS_tuners;
			if( $bs_max > (int)$settings->bs_epg_max ) $bs_max = (int)$settings->bs_epg_max;
			$bs_tim = array( 0, 750, 510, 330 );	// XML取り込み２並列
		}
	}
	$gr_pt1 = 0;
	$bs_pt1 = 0;
	$gr_oth = $GR_tuners - $gr_pt1;
	$bs_oth = $BS_tuners - $bs_pt1;
	if( $gr_pt1>0 || $bs_pt1>0 ){
		if( $gr_oth && $tmpdrive_size<=(GR_OTH_EPG_SIZE+GR_XML_SIZE) ){
			reclog( 'shepherd.php::テンポラリー容量が不十分なためEPG更新が出来ません。空き容量を確保してください。', EPGREC_ERR );
			exit_shephewrd();
		}
		if( $bs_oth && $tmpdrive_size<=(BS_OTH_EPG_SIZE+BS_XML_SIZE) ){
			reclog( 'shepherd.php::テンポラリー容量が不十分なためBS/CSのEPG更新が出来ません。空き容量を確保してください。', EPGREC_ERR );
			$bs_pt1 = 0;
			$bs_oth = 0;
		}

		$gr_use = 0;
		if( $gr_oth ){
			$gr_work_size = GR_OTH_EPG_SIZE + GR_XML_SIZE;
			if( $gr_work_size < $tmpdrive_size ){
				while( $GR_tuners > ++$gr_use ){
					if( $gr_oth > $gr_use ){
						if( $gr_work_size+GR_OTH_EPG_SIZE < $tmpdrive_size ){
							$gr_work_size += GR_OTH_EPG_SIZE;
						}else{
							if( $gr_work_size+GR_OTH_EPG_SIZE == $tmpdrive_size ){
								$gr_work_size += GR_OTH_EPG_SIZE;
								$gr_use++;
							}
							goto GR_ESP;
						}
					}else
						break;
				}
			}else{
				if( $gr_work_size > $tmpdrive_size )
					$gr_work_size = 0;
				else
					$gr_use = 1;
				goto GR_ESP;
			}
		}else
			$gr_work_size = 0;
		if( $gr_pt1 && $GR_tuners>$gr_use ){
			if( $gr_oth == 0 ){
				$gr_work_size = GR_PT1_EPG_SIZE + GR_XML_SIZE;
				if( $gr_work_size >= $tmpdrive_size ){
					if( $gr_work_size > $tmpdrive_size )
						$gr_work_size = 0;
					else
						$gr_use = 1;
					goto GR_ESP;
				}else
					$gr_use = 1;
			}
			while( $GR_tuners > $gr_use ){
				if( $gr_work_size+GR_PT1_EPG_SIZE < $tmpdrive_size ){
					$gr_work_size += GR_PT1_EPG_SIZE;
					$gr_use++;
				}else{
					if( $gr_work_size+GR_PT1_EPG_SIZE == $tmpdrive_size ){
						$gr_work_size += GR_PT1_EPG_SIZE;
						$gr_use++;
					}
					break;
				}
			}
		}
GR_ESP:
		$bs_use = 0;
		if( $bs_oth ){
			$st_work_size = BS_OTH_EPG_SIZE + BS_XML_SIZE;
			if( $st_work_size < $tmpdrive_size ){
				while( $bs_max > ++$bs_use ){
					if( $bs_oth > $bs_use ){
						if( $st_work_size+CS_OTH_EPG_SIZE < $tmpdrive_size ){
							$st_work_size += CS_OTH_EPG_SIZE;
						}else{
							if( $st_work_size+CS_OTH_EPG_SIZE == $tmpdrive_size ){
								$st_work_size += CS_OTH_EPG_SIZE;
								$bs_use++;
							}
							goto ST_ESP;
						}
					}else
						break;
				}
			}else{
				if( $st_work_size > $tmpdrive_size )
					$st_work_size = 0;
				else
					$bs_use = 1;
				goto ST_ESP;
			}
		}else
			$st_work_size = 0;
		if( $bs_pt1 && $bs_max>$bs_use ){
			if( $bs_oth == 0 ){
				$st_work_size = BS_PT1_EPG_SIZE + BS_XML_SIZE;
				if( $st_work_size >= $tmpdrive_size ){
					if( $st_work_size > $tmpdrive_size )
						$st_work_size = 0;
					else
						$bs_use = 1;
					goto ST_ESP;
				}else
					$bs_use = 1;
			}
			while( $bs_max > $bs_use ){
				if( $st_work_size+CS_PT1_EPG_SIZE < $tmpdrive_size ){
					$st_work_size += CS_PT1_EPG_SIZE;
					$bs_use++;
				}else{
					if( $st_work_size+CS_PT1_EPG_SIZE == $tmpdrive_size ){
						$st_work_size += CS_PT1_EPG_SIZE;
						$bs_use++;
					}
					break;
				}
			}
		}
ST_ESP:
		$gr_bs_sepa = $gr_work_size+$st_work_size <= $tmpdrive_size ? FALSE : TRUE;
	}else{
		$tune_cnts = (int)( $tmpdrive_size / GR_OTH_EPG_SIZE );
		if( $tune_cnts == 0 ){
			reclog( 'shepherd.php::テンポラリー容量が不十分なためEPG更新が出来ません。空き容量を確保してください。', EPGREC_ERR );
			exit_shephewrd();
		}
		// XML取り込みは、BS 2.5分(atomD525) CS 1分(仮定)を想定
		$gr_rec_tm = FIRST_REC + $settings->rec_switch_time + 1;
		$gr_bs_sepa = FALSE;
		if( $BS_tuners > 0 ){
			if( $tune_cnts < 3 ){
				$gr_use = $GR_tuners>$tune_cnts ? $tune_cnts : $GR_tuners;
				$bs_use = 0;
				reclog( 'shepherd.php::テンポラリー容量が不十分なため衛星波のEPG更新が出来ません。空き容量を確保してください。', EPGREC_ERR );
			}else{
				if( $tune_cnts == 3 ){
					$gr_bs_sepa = TRUE;
					$gr_use = $GR_tuners>=3 ? 3 : $GR_tuners;
					$bs_use = 1;
					reclog( 'shepherd.php::テンポラリー容量が不十分なため地上波･衛星波並列受信が出来ません。空き容量を確保してください。', EPGREC_WARN );
				}else{
					$bs_tmp = array( 0, 3, 4, 6 );
					if( $GR_tuners > 0 ){
						if( $bs_tmp[$bs_max]+$GR_tuners > $tune_cnts ){
							$minimam =11 * 60;
							$bs_use  = $bs_max;
							for( $bs_stk=$bs_max; $bs_stk>0; $bs_stk-- )
								if( $tune_cnts > $bs_tmp[$bs_stk] ){
									$temp = abs( $bs_tim[$bs_stk] - (int)ceil( $GR_num / ($tune_cnts-$bs_tmp[$bs_stk]) )*$gr_rec_tm );
									if( $minimam >= $temp ){
										$minimam = $temp;
										$bs_use  = $bs_stk;
									}
								}
							$gr_use = $tune_cnts - $bs_tmp[$bs_use];
							//所要時間算出
							$gr_times = (int)ceil( $GR_num / $gr_use ) * $gr_rec_tm;
							$para_tm  = $gr_times<$bs_tim[$bs_use] ? $bs_tim[$bs_use] : $gr_times;
							//セパレート･モード時の所要時間算出
							$gr_use_sepa = $GR_tuners>$tune_cnts ? $tune_cnts : $GR_tuners;
							$gr_times    = (int)ceil( $GR_num / $gr_use_sepa ) * $gr_rec_tm;
							for( $bs_use_sepa=$bs_max; $bs_use_sepa>0; $bs_use_sepa-- )
								if( $bs_tmp[$bs_use_sepa] <= $tune_cnts )
									break;
							$sepa_tm = $gr_times + $bs_tim[$bs_use_sepa];
							//地上波･衛星波 分離判定
							if( $sepa_tm < $para_tm ){
								$gr_bs_sepa = TRUE;
								$gr_use = $gr_use_sepa;
								$bs_use = $bs_use_sepa;
							}
						}else{
							$gr_use = $GR_tuners;
							$bs_use = $bs_max;
						}
					}else{
						$gr_use = 0;
						for( $bs_use=$bs_max; $bs_use>0; $bs_use-- )
							if( $bs_tmp[$bs_use] <= $tune_cnts )
								break;
					}
				}
			}
		}else{
			$gr_use = $GR_tuners>$tune_cnts ? $tune_cnts : $GR_tuners;
			$bs_use = 0;
		}
	}

	// BS/CSを処理する
	if( $bs_use > 0 ){
		$proST = dog_release( INSTALL_PATH.'/bin/collie.php '.$bs_use );
		if( $gr_bs_sepa ){
			//セパレート･モード時のウェイト
			sleep( $bs_tim[$bs_use]+10 );
			$ST_tm = 0;
		}else
			//初期スリープ時間設定
			$ST_tm = $bs_tim[$bs_use] - 120;		// 設定により変動が多いので
	}else{
		$proST = FALSE;
		$ST_tm = 0;
	}
	// radiko
	if( $ex_use > 0 ){
		$ex_use = 1;
		if( count( $EX_CHANNEL_MAP ) ){
			$proEX = dog_release( INSTALL_PATH.'/bin/radikoProgram.php ' );
		}else{
			$proEX = dog_release( INSTALL_PATH.'/bin/radikoStation.php ' );
		}
		//初期スリープ時間設定(大体)
		$EX_tm = (count($EX_CHANNEL_MAP) - count(array_keys($EX_CHANNEL_MAP, 'NC'))) * 8;
	}else{

		$proEX = FALSE;
		$EX_tm = 0;

	}

	// 地上波を処理する
	if( $gr_use > 0 ){
		if($settings->gr_epg_max && ($gr_use > $settings->gr_epg_max)) $gr_use = (int)$settings->gr_epg_max;
		$proGR    = dog_release( INSTALL_PATH.'/bin/sheepdog.php '.$gr_use );
		$sleep_tm = (int)ceil( $GR_num / $gr_use ) * FIRST_REC;
	}else{
		$proGR    = FALSE;
		$sleep_tm = 0;
	}
	// 初期スリープ時間設定
	if( $sleep_tm < $ST_tm )
		$sleep_tm = $ST_tm;
	if( $sleep_tm < $EX_tm )
		$sleep_tm = $EX_tm;

	// EPG更新待ち
	$wtd_tm = $sleep_tm;
	while( $proST !== FALSE || $proGR !== FALSE || $proEX !== FALSE ){
		sleep( $sleep_tm );
		$sleep_tm = 1;
		if( $proST !== FALSE ){
			$st = proc_get_status( $proST );
			if( $st['running'] == FALSE ){
				proc_close( $proST );
				$proST = FALSE;
			}
		}
		if( $proEX !== FALSE ){
			$st = proc_get_status( $proEX );
			if( $st['running'] == FALSE ){
				proc_close( $proEX );
				$proEX = FALSE;
			}
		}
		if( $proGR !== FALSE ){
			$st = proc_get_status( $proGR );
			if( $st['running'] == FALSE ){
				proc_close( $proGR );
				$proGR = FALSE;
			}
		}
		// タイムアウト(1H)
		if( $wtd_tm++ >= 60*60 ){
			$ps_output = shell_exec( PS_CMD.' 2>/dev/null' );
			$rarr      = explode( "\n", $ps_output );
			if( $proST !== FALSE ){
				proc_terminate( $proST, 9 );
			}
			if( $proEX !== FALSE ){
				proc_terminate( $proEX, 9 );
			}
			if( $proGR !== FALSE ){
				proc_terminate( $proGR, 9 );
			}
			break;
		}
	}
	reclog( 'EPG更新完了('.transTime(time()-$shepherd_st,TRUE).')' );
	// キーワード予約
	doKeywordReservation( '*' );

	exit_shephewrd();
?>

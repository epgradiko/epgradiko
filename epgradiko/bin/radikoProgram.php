#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Keyword.class.php' );
include_once( INSTALL_PATH . '/include/epg_const.php' );
include_once( INSTALL_PATH . '/include/radiko_const.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );

run_user_regulate();
new single_Program('radikoProgram');

$settings = Settings::factory();

if( $settings->ex_tuners == 0 ) exit();

libxml_use_internal_errors(true);
// radikoProgram.php station-id any
$single_mode = FALSE;
$single_station = "";
if( isset($argv[1]) ){
	//	single station mode
	$single_station = $argv[1];
	$single_mode = TRUE;
}

$days = array();
$day_mode = 'normal';
if( isset($argv[2]) ){
	if( $argv[2] == 'timeshift' ) $day_mode = 'timeshift';		// day mode
	else $day_mode = 'single';
}
$today = date("Y-m-d", strtotime( "-5 hour" ));
switch( $day_mode ){
	case 'single':
		$days[0] = $today;
		break;
	case 'normal':
		for($i = 0; $i < 7; $i++) {
			$days[$i] = date("Ymd",strtotime($today .' '.$i." day"));
		}
		break;
	case 'timeshift':
		for($i = -7; $i < 1; $i++) {
			$days[$i + 7] = date("Ymd",strtotime($today .' '.$i." day"));
		}
		break;
}

// Get radiko Area ID
$radiko_area_chk_url = "https://radiko.jp/area";
$radiko_area_string = file_get_Contents($radiko_area_chk_url);
preg_match('/span.*class="(\w+)"/', $radiko_area_string, $radiko_area);
// Get radiko Area Stations
$radiko_free_station = array();
if( !isset($radiko_area[1]) or substr($radiko_area[1],0,2) !== 'JP' ) {
	$radiko_area_check = FALSE;
}else{
	$radiko_stations = 'http://radiko.jp/v3/station/list/'.$radiko_area[1].'.xml';
	$radiko_stations_contents = @file_get_Contents($radiko_stations);
	if( $radiko_stations_contents !== false ){
		$regions_stations = simplexml_load_string($radiko_stations_contents);
		$radiko_area_check = TRUE;
	}else{
		reclog( 'radiko番組表更新::radikoエリア('.$radiko_area[1].')放送局にアクセスできません', EPGREC_WARN );
		$regions_stations = FALSE;
	}
	if( $regions_stations === FALSE ){
		reclog( 'radiko番組表更新::radikoエリア('.$radiko_area[1].')放送局xmlが読み込めません', EPGREC_WARN );
		foreach(libxml_get_errors() as $error) {
			reclog( 'radiko番組表更新:xml:'.$error->message, EPGREC_WARN );
		}
		$radiko_area_check = FALSE;
	}else{
		foreach( $regions_stations->station as $station ){
			$radiko_free_station[] = (string)$station->id;
		}
	}
}

$map = $EX_CHANNEL_MAP;
$stk_rev    = array();
$stk_auto   = array();
$ev_cnt     = 0;
$now_time   = toDatetime( time() );
$epg_time   = toDatetime( time() + 3*60*60 );
$pro_obj    = new DBRecord( PROGRAM_TBL );
$key_cnt    = 0;

if( !count($map) ) exec( INSTALL_PATH.'/bin/radikoStation.php');

$radiko_programs_base = "http://radiko.jp/v3/program/station/date/";
foreach($map as $station => $channel) {
	$station_id	= substr($station, 3);
	$channel_disc	= 'EX_'.$station_id;
	$channel_ch	= 'EXradiko_'.$station_id;
	$channel_rec	= new DBRecord( CHANNEL_TBL, 'channel_disc', $channel_disc );
	$channel_id	= (int)$channel_rec->id;
	$skip_ch	= (boolean)$channel_rec->skip;
	// NC or 単局は読み飛ばし
	if( ($single_mode && $station_id !== $single_station )|| $channel == 'NC' ) continue;
	// 自地域はフリーとする
	if( !$radiko_area_check or array_search($station_id, $radiko_free_station) ){
		$free   = 0;
	}else{
		$free   = 1;
	}
	foreach($days as $radiko_date) {
		$radiko_program_url = $radiko_programs_base.$radiko_date.'/'.$station_id.'.xml';
		$radiko_program_contents = @file_get_Contents($radiko_program_url);
		if( $radiko_program_contents !== false ){
			$programs = simplexml_load_string($radiko_program_contents)->stations->station->progs->prog;
			foreach( $programs as $program ){
				$starttime    = date('Y-m-d H:i:s', strtotime($program->attributes()->ft));
				$endtime      = date('Y-m-d H:i:s', strtotime($program->attributes()->to));
				//	過去分は相手にしない
				if( $endtime <= date('Y-m-d H:i:s', time()) ) continue;
				//	title to mark
				$marks = '';
				$title = str_replace('　', ' ', $program->title);
				foreach( RadikoProgramTitle as $mark => $values ){
					$title = str_replace( $values, '', $title, $count );
					if( $count > 0 ) $marks .= $mark;
				}

				$pre_title    = regular_mark( $marks, 'pre' );
				$post_title   = regular_mark( $marks, 'post' );
				$desc	      = '';
				if( isset($program->pfm) and trim($program->pfm) !== '') {
					$desc .= '🈤'.str_replace('　', ' ', $program->pfm).' ';
				}
				if( isset($program->genre->personality->attributes()->id) ){
					$desc .= '◇出演者ジャンル:'.$program->genre->personality->name.' ';
				}
				$category_id  = 16;
				$sub_genre    = 0;
				if( isset($program->genre->program->attributes()->id) ){
					$desc .= '◇番組ジャンル:'.$program->genre->program->name.' ';
					$radiko_genre = (String)$program->genre->program->attributes()->id;
					$category_id = RadikoProgramGenre[$radiko_genre]["category_id"];
					$sub_genre = RadikoProgramGenre[$radiko_genre]["sub_genre"];
				}
				if( $desc ) $desc .= "\n";
				$desc	     .= str_replace('　', ' ', strip_tags( $program->frm.$program->desc.$program->info ));		// シリアライズのエスケープ解除(改行を含むためエスケープしている)
				$mark	      = '';
				$free_CA_mode = $free;
				$eid	      = $program->attributes()->id;	// eidは信じられない局が多々ある。ので、信じない。
				$genre2       = 0;
				$genre3       = 0;
				$sub_genre2   = 0;
				$sub_genre3   = 0;
				$video_type   = 0;
				$audio_type   = 0;
				$multi_type   = 0;
				if( !isset($program->img) or !$program->img ){
					$image_url = $channel_rec->logo;
				}else{
					$image_url = $program->img;
				}
				$timeshift    = (int)$program->ts_in_ng;
//				$program_disc = md5( $channel_disc . $eid . $starttime . $endtime );	// eidは信じられない局が多々ある。ので、信じない。
				$program_disc = md5( $channel_disc . $title . $starttime . $endtime );	// eidは信じられない局が多々ある。ので、信じない。
				// プログラム登録
				$records = $pro_obj->fetch_array( 'program_disc', $program_disc );
				if( count( $records ) == 0 ){
					// 新規番組
					$rewrite_eid = 0;
					try {
						// 重複チェック 同時間帯にある番組
						$records = $pro_obj->fetch_array( 'channel_id', $channel_id, 'starttime<\''.$endtime.'\' AND endtime>\''.$starttime.'\' ORDER BY starttime ASC' );
						if( count( $records ) ){
							if( $category_id==15 && $sub_genre==14 )		// 不確定な補完した放送休止は除外
								continue;
							// 重複発生＝おそらく放映時間の変更
							foreach( $records as $rec ){
								// 自動録画予約された番組は放映時間変更と同時にいったん削除する
								$prg_st = toTimestamp( $rec['starttime'] );
								$prg_ed = toTimestamp( $rec['endtime'] );
								try {
									$prev_recs = DBRecord::createRecords( RESERVE_TBL, 'WHERE complete=0 AND program_id='.$rec['id'].' ORDER BY starttime DESC', FALSE );
									foreach( $prev_recs as $reserve ){
										$rev_st    = $reserve->starttime;
										$rec_start = toTimestamp( $rev_st );
										$rev_ed    = $reserve->endtime;
										$rec_end   = toTimestamp( $rev_ed );
										$prev_tuner = $reserve->tuner;
										$rev_ds    = '予約ID:'.$reserve->id.' '.$channel_disc.':T'.$prev_tuner.'-Ch'.$reserve->channel.' '.$rev_st.'『'.$reserve->title.'』';
										if( time() >= $rec_start - $settings->former_time ) {
											if( $rewrite_eid!==$eid && (int)$rec['eid']===$eid ){
												//録画中番組の予約DB更新($rec[]の方は以降に要素を使わないので放置)
												$wrt_set = array();
												$wrt_set['starttime']	 = $starttime;
												$wrt_set['endtime']	 = $endtime;
												$wrt_set['program_disc'] = $program_disc;
												$pro_obj->force_update( $rec['id'], $wrt_set );
												$rewrite_eid = $eid;
											}
										}else{
											if( (int)($reserve->autorec) === 0 ){
												// 手動予約の退避
												$arr = array();
												$arr['old_id'] = $reserve->id;
												$arr['eid']    = (int)$rec['eid'];
												$arr['old_st'] = $prg_st;
												$arr['st_tm']  = $rec_start;
												$arr['ed_tm']  = !$reserve->shortened ? $rec_end : $rec_end+$ed_tm_sft;
												$arr['ch_id']  = channel_id;
												$arr['title']  = $reserve->title;
												$arr['pre_title']  = $reserve->pre_title;
												$arr['post_title'] = $reserve->post_title;
												$arr['desc']   = $reserve->description;
												$arr['mark']   = $reserve->pre_title.$reserve->post_title;
												$arr['free_CA_mode']  = (int)$reserve->free_CA_mode;
												$arr['cat_id'] = (int)$reserve->category_id;
												$arr['rs_md']  = (int)$reserve->mode;
												$arr['discon'] = (int)$reserve->discontinuity;
												$arr['rs_dt']  = (int)$reserve->dirty;
												$arr['prior']  = (int)$reserve->priority;
												$arr['image_url'] = $reserve->image_url;
												array_push( $stk_rev, $arr );
											}else{
//												reclog( 'radiko番組表更新:'.$rev_ds.'は時間変更の可能性があり予約取り消し' );
												$key_stk[$key_cnt++] = (int)$reserve->autorec;
											}
											Reservation::cancel( $reserve->id );
										}
									}
									unset( $reserve );
									unset( $prev_recs );
								}
								catch( Exception $e ) {
									// 無視
								}
								if( (int)$rec['eid'] !== $rewrite_eid ){
									// 番組削除
									if( !$skip_ch ){
										// 非表示CHはログを出さない
										reclog( 'radiko番組表更新::時間重複した番組ID'.$rec['id'].': '.$channel_disc.'::'.$rec['eid'].' '.date('Y-m-d H:i-',$prg_st).date('H:i',$prg_ed).
										       '『'.$rec['title'].'』を削除', EPGREC_DEBUG );
										// 自動予約禁止フラグの保存
										if( !(boolean)$rec['autorec'] )
											$stk_auto[] = (int)$rec['eid'];
									}
									$pro_obj->force_delete( $rec['id'] );
								}
							}
							unset( $rec );
						}

						// 番組延伸による過去分の重複削除
						if( $eid !== -1 ){
							$reco = DBRecord::createRecords( PROGRAM_TBL, 'WHERE channel_id='.$channel_id.' AND eid='.$eid.' AND program_disc!=\''.$program_disc.'\'' );
							foreach( $reco as $del_pro ){
								$prg_st    = toTimestamp( $del_pro->starttime );
								$prev_recs = DBRecord::createRecords( RESERVE_TBL, 'WHERE complete=0 AND program_id='.$del_pro->id.' ORDER BY starttime DESC' );
								foreach( $prev_recs as $reserve ){
									if( (int)($reserve->autorec) === 0 ){
										// 手動予約の退避
										$arr = array();
										$arr['old_id'] = $reserve->id;
										$arr['eid']    = $eid;
										$arr['old_st'] = $prg_st;
										$arr['st_tm']  = toTimestamp( $reserve->starttime );
										$arr['ed_tm']  = !$reserve->shortened ? toTimestamp( $reserve->endtime ) : toTimestamp( $reserve->endtime )+$ed_tm_sft;
										$arr['ch_id']  = $channel_id;
										$arr['title']  = $reserve->title;
										$arr['pre_title']  = $reserve->pre_title;
										$arr['post_title'] = $reserve->post_title;
										$arr['desc']   = $reserve->description;
										$arr['mark']   = $reserve->pre_title.$reserve->post_title;
										$arr['free_CA_mode']  = (int)$reserve->free_CA_mode;
										$arr['cat_id'] = (int)$reserve->category_id;
										$arr['rs_md']  = (int)$reserve->mode;
										$arr['discon'] = (int)$reserve->discontinuity;
										$arr['rs_dt']  = (int)$reserve->dirty;
										$arr['prior']  = (int)$reserve->priority;
										$arr['image_url'] = $reserve->image_url;
										array_push( $stk_rev, $arr );
									}else{
//										reclog( 'radiko番組表更新:'.$rev_ds.'は時間変更の可能性があり予約取り消し' );
										$key_stk[$key_cnt++] = (int)$reserve->autorec;
									}
									Reservation::cancel( $reserve->id );
								}
								unset( $reserve );
								unset( $prev_recs );
								if( !$skip_ch ){		// 非表示CHはログを出さない
									reclog( 'radiko番組表更新::時間重複した番組ID'.$del_pro->id.': '.$channel_disc.'::'.$eid.' '.date('Y-m-d H:i-',$prg_st).date('H:i',toTimestamp( $del_pro->endtime )).
										'『'.$del_pro->title.'』を削除', EPGREC_DEBUG  );
									// 自動予約禁止フラグの保存
									if( !(boolean)$del_pro->autorec )
										$stk_auto[] = $eid;
								}
								$del_pro->delete();
							}
						}

						//録画中番組以外を登録
						if( $eid !== $rewrite_eid ){
							$wrt_set = array();
							$wrt_set['channel_disc'] = $channel_disc;
							$wrt_set['channel_id']	 = $channel_id;
							$wrt_set['channel']	 = $channel_ch;
							$wrt_set['title']	 = $title;
							$wrt_set['pre_title']	 = $pre_title;
							$wrt_set['post_title']	 = $post_title;
							$wrt_set['description']  = $desc;
							$wrt_set['free_CA_mode'] = $free_CA_mode;
							$wrt_set['category_id']  = $category_id;
							$wrt_set['eid'] 	 = $eid;
							$wrt_set['starttime']	 = $starttime;
							$wrt_set['endtime']	 = $endtime;
							$wrt_set['program_disc'] = $program_disc;
							$wrt_set['type']	 = 'EX';
							$wrt_set['image_url']	 = $image_url;
							$wrt_set['timeshift']	 = $timeshift;
							// 初期値のものを間引き
							if( $sub_genre !== 16 )
								$wrt_set['sub_genre'] = $sub_genre;
							if( $genre2 !== 0 )
								$wrt_set['genre2'] = $genre2;
							if( $sub_genre2 !== 16 )
								$wrt_set['sub_genre2'] = $sub_genre2;
							if( $genre3 !== 0 )
								$wrt_set['genre3'] = $genre3;
							if( $sub_genre3 !== 16 )
								$wrt_set['sub_genre3'] = $sub_genre3;
							if( $video_type !== 0 )
								$wrt_set['video_type'] = $video_type;
							if( $audio_type !== 0 )
								$wrt_set['audio_type'] = $audio_type;
							if( $multi_type !== 0 )
								$wrt_set['multi_type'] = $multi_type;
							$pro_obj->force_update( 0, $wrt_set );
							$ev_cnt++;
						}
					}
					catch( Exception $e ){
						reclog( 'radiko番組表更新:: プログラムテーブルに問題が生じた模様<br>'.$e->getMessage() , EPGREC_ERROR);
						exit;
					}
				}else{
					$rec = $records[0];
					// 番組内容更新
					$genre_chg = $media_chg = $desc_chg = $mark_chg = $free_CA_mode_chg = FALSE;
					$wrt_set   = FALSE;
					if( strcmp( $rec['title'], $title ) != 0 ){
						$title_old	  = $rec['title'];
						$wrt_set['title'] = $title;
						$title_chg	  = TRUE;
					}else{
						$title_old = $title;
						$title_chg = FALSE;
					}
					if( strcmp( $rec['pre_title'], $pre_title ) != 0 ){
						$wrt_set['pre_title']  = $pre_title;
						$mark_chg	 = TRUE;
					}
					if( strcmp( $rec['post_title'], $post_title ) != 0 ){
						$wrt_set['post_title']  = $post_title;
						$mark_chg	 = TRUE;
					}
					if( (int)($rec['free_CA_mode']) !== $free_CA_mode ){
						$wrt_set['free_CA_mode'] = $free_CA_mode;
						$free_CA_mode_chg	 = TRUE;
					}
					if( (int)($rec['category_id']) !== $category_id ){
						$wrt_set['category_id'] = $category_id;
						$genre_chg		= TRUE;
					}
					if( (int)($rec['sub_genre']) !== $sub_genre ){
						$wrt_set['sub_genre'] = $sub_genre;
						$genre_chg	      = TRUE;
					}
					if( $category_id===15 && $sub_genre===14 )		// 補完した放送休止は除外
						$genre_chg = FALSE;
					if( strcmp( $rec['description'] , $desc ) != 0 ){
						$wrt_set['description'] = $desc;
						$desc_chg		= TRUE;
					}
					if( (int)($rec['genre2']) !== $genre2 ){
						$wrt_set['genre2'] = $genre2;
					}
					if( (int)($rec['sub_genre2']) !== $sub_genre2 ){
						$wrt_set['sub_genre2'] = $sub_genre2;
					}
					if( (int)($rec['genre3']) !== $genre3 ){
						$wrt_set['genre3'] = $genre3;
					}
					if( (int)($rec['sub_genre3']) !== $sub_genre3 ){
						$wrt_set['sub_genre3'] = $sub_genre3;
					}
					if( (int)($rec['video_type']) !== $video_type ){
						$wrt_set['video_type'] = $video_type;
						$media_chg	       = TRUE;
					}
					if( (int)($rec['audio_type']) !== $audio_type ){
						$wrt_set['audio_type'] = $audio_type;
						$media_chg	       = TRUE;
					}
					if( (int)($rec['multi_type']) !== $multi_type ){
						$wrt_set['multi_type'] = $multi_type;
						$media_chg	       = TRUE;
					}
					if( (int)($rec['timeshift']) !== $timeshift ){
						$wrt_set['timeshift'] = $timeshift;
					}
					if( $wrt_set !== FALSE ){
						$pro_obj->force_update( $rec['id'], $wrt_set );
						if( $genre_chg || $title_chg || $media_chg || $desc_chg ){
							try {
								$prev_recs = DBRecord::createRecords( RESERVE_TBL, 'WHERE complete=0 AND program_id='.$rec['id'].' ORDER BY starttime ASC', FALSE );
								foreach( $prev_recs as $reserve ){
									if( !(boolean)$reserve->dirty && time()<toTimestamp( $reserve->starttime )-$ed_tm_sft ){
										// dirtyが立っていない録画予約であるなら
										$chg_rev = FALSE;
										if( $sch_obtain && (int)$reserve->autorec ){
											if( !$media_chg ){
												$keyword = new DBRecord( KEYWORD_TBL, 'id', $reserve->autorec );
												$filename_format = $keyword->filename_format!='' ? $keyword->filename_format : $settings->filename_format;
												if( $title_chg && ( strpos( $filename_format, '%T' )!==FALSE || (boolean)$keyword->ena_title ) )
													$chg_rev = TRUE;
												else
													if( $desc_chg && (boolean)$keyword->ena_desc )
														$chg_rev = TRUE;
													else
														if( $genre_chg && (int)$keyword->category_id ){
															// 第2・第3については忘れる
															if( (int)$keyword->category_id === $category_id ){
																if( (int)$keyword->sub_genre<16 && (int)$keyword->sub_genre!==$sub_genre &&
																		!( $category_id===7 && $keyword->sub_genre==3 && $sub_genre===4 ) )		// この1行は裏仕様
																	$chg_rev = TRUE;
															}else
																$chg_rev = TRUE;
														}
												unset( $keyword );
											}else
												$chg_rev = TRUE;
										}

										if( $chg_rev ){
											//自動キーワード再予約のためキャンセル
											$key_stk[$key_cnt++] = (int)$reserve->autorec;
											Reservation::cancel( $reserve->id );
										}else{
											$elememts = '';
											if( $title_chg ){
												$reserve->title = $title;
												$elememts = 'タイトル';
											}
											if( $desc_chg ){
												$reserve->description = $desc;
												if( $elememts !== '' )
													$elememts .= '・';
												$elememts .= '概要';
											}
											if( $genre_chg ){
												$reserve->category_id = $category_id;
												$reserve->sub_genre   = $sub_genre;
												if( $elememts !== '' )
													$elememts .= '・';
												$elememts .= 'ジャンルコード';
											}
											if( $genre_chg || $title_chg || $desc_chg ){
												$reserve->update();
												reclog( 'radiko番組表更新:: 予約ID'.$reserve->id.'のEPG情報['.$elememts.']が更新された' );
											}
										}
									}
								}
								unset( $reserve );
								unset( $prev_recs );
							}
							catch( Exception $e ) {
								// 無視する
							}
						}
					}
				}
			}
		}
	}
}
// 自動キーワ－ド再予約
// 残りのEPG処理に時間がかかる場合に直近番組が始まってしまい再予約に失敗する対策
//$shm_id = shmop_open_surely();
if( $key_cnt ){
//	$sem_key = sem_get_surely( SEM_KW_START );
	$result  = array_unique( $key_stk, SORT_NUMERIC );	// keyword IDの重複解消
	foreach( $result as $keyword_id ){
		$rec = new Keyword( 'id', $keyword_id );
//		$rec->reservation( 'EX', $shm_id, $sem_key );
		$rec->reservation( 'EX' );
	}
}
//shmop_close( $shm_id );
?>

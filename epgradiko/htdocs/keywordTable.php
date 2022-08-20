<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/Keyword.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );

function word_chk( $chk_wd ){
	return ( strpos( $chk_wd, '"' )===FALSE && strpos( $chk_wd, '\'' )===FALSE ? $chk_wd : '' );
}

function word_chk_DQ( $chk_wd ){
	return ( strpos( $chk_wd, '"' )===FALSE ? $chk_wd : '' );
}

if( ! file_exists( INSTALL_PATH.'/settings/config.xml') && !file_exists( '/etc/epgrecUNA/config.xml' ) ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}

$settings = Settings::factory();

$weekofdays = array( '月', '火', '水', '木', '金', '土', '日' );
$prgtimes = array();
for( $i=0 ; $i < 25; $i++ ) {
	$prgtimes[$i] = $i == 24 ? 'なし' : $i.'時';
}

// 新規キーワードがポストされた

if( isset($_POST['add_keyword']) ) {
	if( $_POST['add_keyword'] == 1 ) {
		try {
			$keyword_id = $_POST['keyword_id'];
			if( $keyword_id ){
				$rec = new Keyword( 'id', $keyword_id );
			} else {
				$rec = new Keyword();
			}
			$rec->name	      = $_POST['kw_name'];
			$rec->keyword         = $_POST['k_search'];
			$rec->keyword_ex      = $_POST['k_search_ex'];
			$rec->kw_enable       = isset( $_POST['k_kw_enable'] );
			$rec->free            = (int)($_POST['k_free'] ? 1 : 0);
			$rec->typeGR          = (int)($_POST['k_typeGR'] ? 1 : 0);
			$rec->typeBS          = (int)($_POST['k_typeBS'] ? 1 : 0);
			$rec->typeCS          = (int)($_POST['k_typeCS'] ? 1 : 0);
			$rec->typeEX          = (int)($_POST['k_typeEX'] ? 1 : 0);
			$rec->category_id     = $_POST['k_category'];
			$rec->sub_genre       = $_POST['k_sub_genre'];
			$rec->first_genre     = (int)($_POST['k_first_genre'] ? 1 : 0);
			$rec->channel_id      = $_POST['k_station'];
			$rec->use_regexp      = (int)($_POST['k_use_regexp'] ? 1 : 0);
			$rec->use_regexp_ex   = (int)($_POST['k_use_regexp_ex'] ? 1 : 0);
			$rec->collate_ci      = (int)($_POST['k_collate_ci'] ? 1 : 0);
			$rec->collate_ci_ex   = (int)($_POST['k_collate_ci_ex'] ? 1 : 0);
			$rec->ena_title       = (int)($_POST['k_ena_title'] ? 1 : 0);
			$rec->ena_title_ex    = (int)($_POST['k_ena_title_ex'] ? 1 : 0);
			$rec->ena_desc        = (int)($_POST['k_ena_desc'] ? 1 : 0);
			$rec->ena_desc_ex     = (int)($_POST['k_ena_desc_ex'] ? 1 : 0);
			$rec->search_marks    = $_POST['k_search_marks'];
			$rec->search_exmarks  = $_POST['k_search_exmarks'];
			$rec->weekofdays      = $_POST['k_weekofday'];
			$rec->prgtime         = $_POST['k_prgtime'];
			$rec->period          = $_POST['k_period'];
			$rec->duration_from   = $_POST['k_duration_from'];
			$rec->duration_to     = $_POST['k_duration_to'];
			$rec->autorec_mode    = $_POST['autorec_mode'];
			$rec->sft_start       = parse_time( $_POST['k_sft_start'] );
			if( $_POST['k_sft_end'][0] === '@' ){
				$rec->duration_chg = 1;
				$rec->sft_end      = parse_time( substr( $_POST['k_sft_end'], 1 ) );
			}else{
				$rec->duration_chg = 0;
				$rec->sft_end      = parse_time( $_POST['k_sft_end'] );
			}
			$rec->discontinuity   = isset($_POST['k_discontinuity']);
			$rec->priority        = $_POST['k_priority'];
			$rec->overlap         = (int)(isset( $_POST['k_overlap'] ) ? 1 : 0);
			$rec->rest_alert      = isset( $_POST['k_rest_alert'] );
			$rec->criterion_dura  = isset( $_POST['k_criterion_enab'] ) ? $_POST['k_criterion_dura'] : 0;
			$rec->smart_repeat    = (int)(isset( $_POST['k_smart_repeat'] ) ? 1 : 0);
			$rec->split_time      = parse_time( $_POST['k_split_time'] );
			if( $rec->split_time < 0 )
				$rec->split_time = 0;
			else
				if( $rec->split_time > 0 )
					$rec->overlap = 1;
			$rec->filename_format = word_chk( $_POST['k_filename'] );
//			$rec->directory       = word_chk( $_POST['k_directory'] );
			$rec->directory       = word_chk_DQ( $_POST['k_directory'] );
			$rec->sort_order      = (int)$_POST['sort_order'];
//			$sem_key              = sem_get_surely( SEM_KW_START );
//			$shm_id               = shmop_open_surely();
//			$rec->keyid_acquire( $shm_id, $sem_key );	// keyword_id占有
			$rec->update();
			if( $keyword_id )
				$rec->rev_delete();
			else
				$keyword_id = $rec->id;
			// transcode
			$mode = $_POST['autorec_mode'];
			$cnt = 0;
			for( $loop=0; $loop<TRANS_SET_KEYWD; $loop++ ){
				$pool = DBRecord::createRecords( TRANSEXPAND_TBL, 'WHERE key_id='.$keyword_id.' AND type_no='.$loop );
				if( isset($RECORD_MODE[$mode]['tsuffix']) ){
					if( count($pool) ){
						$trans_ex = $pool[0];
					}else{
						$trans_ex = new DBRecord( TRANSEXPAND_TBL );
						$trans_ex->key_id = $keyword_id;
					}
					$trans_ex->type_no = $cnt++;
					$trans_ex->mode    = $mode;
//					$trans_ex->dir     = word_chk( $_POST['k_transdir'.$loop] );
					$trans_ex->dir     = word_chk_DQ( $_POST['k_transdir'.$loop] );
					$trans_ex->ts_del  = isset( $_POST['k_auto_del'] );
					$trans_ex->update();
				}else
					if( count($pool) )
						$pool[0]->delete();
			}
			if( (boolean)$rec->kw_enable ){
				$t_cnt = 0;
				if( (boolean)$rec->typeGR ){
					$type = 'GR';
					$t_cnt++;
				}
				if( (boolean)$rec->typeBS ){
					$type = 'BS';
					$t_cnt++;
				}
				if( (boolean)$rec->typeCS ){
					$type = 'CS';
					$t_cnt++;
				}
				if( (boolean)$rec->typeEX ){
					$type = 'EX';
					$t_cnt++;
				}
				if( $t_cnt > 1 )
					$type = '*';
				// 録画予約実行
				$rec->reservation( $type );
			}else{
				// transcode
				$cnt = 0;
				for( $loop=0; $loop<TRANS_SET_KEYWD; $loop++ ){
					$pool = DBRecord::createRecords( TRANSEXPAND_TBL, 'WHERE key_id='.$keyword_id.' AND type_no='.$loop );
					$mode = $_POST['autorec_mode'];
					if( isset($RECORD_MODE[$mode]['tsuffix']) ){
						if( count($pool) ){
							$pool[0]->delete();
						}
					}
				}
//				$rec->keyid_release();	// keyword_id開放
			}
//			shmop_close( $shm_id );
		}catch( Exception $e ) {
			exit( $e->getMessage() );
		}
	}
}


$cs_rec_flg = (boolean)$settings->cs_rec_flg;
$keywords   = array();
try {
	$recs = Keyword::createRecords(KEYWORD_TBL, 'ORDER BY sort_order, id' );
	foreach( $recs as $rec ) {
		$arr = array();
		$arr['id'] = $rec->id;
		$arr['name'] = $rec->name;
		$arr['keyword'] = mb_strimwidth($rec->keyword, 0,180, '…', 'UTF-8');
		$arr['keyword_ex'] =mb_strimwidth( $rec->keyword_ex, 0, 180, '…', 'UTF-8');
		$arr['type'] = '';
		if( $rec->typeGR && $rec->typeBS && ( !$cs_rec_flg || $rec->typeCS ) && ( $settings->ex_tuners == 0 || $rec->typeEX ) ){
			$arr['type'] .= '';
		}else{
			$cnt = 0;
			if( $rec->typeGR ){
				$arr['type'] .= '地デジ';
				$cnt++;
			}
			if( $settings->bs_tuners > 0 ){
				if( $rec->typeBS ){
					if( $cnt )
						$arr['type'] .= '+';
					$arr['type'] .= 'BS';
					$cnt++;
				}
				if( $cs_rec_flg && $rec->typeCS ){
					if( $cnt )
						$arr['type'] .= '+';
					$arr['type'] .= 'CS';
				}
			}
			if( $settings->ex_tuners>0 && $rec->typeEX ){
				if( $cnt )
					$arr['type'] .= '+';
				$arr['type'] .= 'ラジオ';
			}
		}
		$arr['k_type'] = $rec->kw_enable;
		if( $rec->channel_id ) {
			try {
				$crec = new DBRecord(CHANNEL_TBL, 'id', $rec->channel_id );
				$arr['channel'] = $crec->name;
				$arr['type'] = '';
			}catch( exception $e ){
				$rec->channel_id = 0;
				$arr['channel']  = '全チャンネル';
			}
		}
		else $arr['channel'] = '全チャンネル';
//		$arr['k_channel'] = $rec->channel_id;
		if( $rec->category_id ) {
			$crec = new DBRecord(CATEGORY_TBL, 'id', $rec->category_id );
			$arr['category'] = $crec->name_jp;
		}
		else $arr['category'] = '全ジャンル';
		$arr['k_category'] = $rec->category_id;
		$arr['sub_genre'] = $rec->sub_genre;
		$arr['first_genre'] = $rec->first_genre;

		$arr['options'] = '';
		if( $rec->ena_title && $rec->ena_desc ) {
			$arr['options'] = 'タイトル＋概要';
		} else {
			if( $rec->ena_title ) {
				$arr['options'] = 'タイトル';
			} else {
				if( $rec->ena_desc ) {
					$arr['options'] = '概要';
				}
			}
		}
		if( $arr['options'] ) {
			if( $rec->use_regexp ) {
				$arr['options'] .= '(正規表現検索)';
			} else {
				if( $rec->collate_ci ) {
					$arr['options'] .= '(全角半角区別しない)';
				} else {
					$arr['options'] .= '(通常検索)';
				}
			}
		}

		$arr['options_ex'] = '';
		if( $rec->ena_title_ex && $rec->ena_desc_ex ) {
			$arr['options_ex'] = 'タイトル＋概要';
		} else {
			if( $rec->ena_title_ex ) {
				$arr['options_ex'] = 'タイトル';
			} else {
				if( $rec->ena_desc_ex ) {
					$arr['options_ex'] = '概要';
				}
			}
		}
		if( $arr['options_ex'] ) {
			if( $rec->use_regexp_ex ) {
				$arr['options_ex'] .= '(正規表現検索)';
			} else {
				if( $rec->collate_ci_ex ) {
					$arr['options_ex'] .= '(全角半角区別しない)';
				} else {
					$arr['options_ex'] .= '(通常検索)';
				}
			}
		}

		$arr['alert'] = '';
		if( $rec->rest_alert ) {
			$arr['alert'] = '予約なし警告 ';
		}
		if( $rec->criterion_dura ) {
			$arr['alert'] = '録画短縮時警告 ';
		}

		if( $rec->weekofdays != 0x7f ){
			$arr['weekofday'] = '';
			for( $b_cnt=0; $b_cnt<7; $b_cnt++ ){
				if( $rec->weekofdays & ( 0x01 << $b_cnt ) ){
					$arr['weekofday'] .= $weekofdays[$b_cnt];
				}
			}
		} else {
			$arr['weekofday'] = '毎日';
		}
		$arr['disp_prgtime'] = '';
		if( $rec->prgtime != 24 ) {
			$arr['disp_prgtime'] = $rec->prgtime.'～';
			if( $rec->prgtime + $rec->period > 23 ) {
				$arr['disp_prgtime'] .= '翌'.$prgtimes[ $rec->prgtime + $rec->period - 24 ];
			} else {
				$arr['disp_prgtime'] .= $prgtimes[ $rec->prgtime + $rec->period ];
			}	
		}	
		$arr['autorec_mode'] = $RECORD_MODE[(int)$rec->autorec_mode]['name'];
		$tran_ex = DBRecord::createRecords( TRANSEXPAND_TBL, 'WHERE key_id='.$rec->id.' AND type_no=0 ORDER BY id' );
		$arr['trans_mode'] = '';
		foreach( $tran_ex as $tran ) {
			$arr['trans_mode'] .= $RECORD_MODE[(int)$tran->mode]['name'].'<br>';
		}
		$arr['sft_start'] = transTime( $rec->sft_start, TRUE );
		$arr['sft_end']   = ((boolean)$rec->duration_chg ? '@':'').transTime( $rec->sft_end, TRUE );
		$arr['discontinuity'] = $rec->discontinuity;
		$arr['priority'] = $rec->priority;
		$arr['free'] = $rec->free;
                $mark_sp            = str_replace('][', ']&nbsp[', $rec->search_marks);
                $mark_bracketL      = str_replace('[', '<span class="mark_class">', $mark_sp);
                $mark_bracketR      = str_replace(']', '</span>', $mark_bracketL);
                $arr['disp_search_marks'] = $mark_bracketR;
                $mark_sp            = str_replace('][', ']&nbsp[', $rec->search_exmarks);
                $mark_bracketL      = str_replace('[', '<span class="mark_class">', $mark_sp);
                $mark_bracketR      = str_replace(']', '</span>', $mark_bracketL);
                $arr['disp_search_exmarks'] = $mark_bracketR;
		if( $rec->duration_from !== '' && $rec->duration_to !== '' ) {
			$arr['disp_duration'] = $rec->duration_from.'～'.$rec->duration_to.'分';
		} else {
			if( $rec->duration_from !== '' ) {
				$arr['disp_duration'] = $rec->duration_from.'分以上';
			} else {
				if( $rec->duration_to !=='' ) {
					$arr['disp_duration'] = $rec->duration_to.'分以下';
				} else {
					$arr['disp_duration'] = '';
				}
			}
		}
		$arr['res_cnt'] = DBRecord::countRecords( RESERVE_TBL, 'WHERE complete = 0 AND autorec='.$arr['id'] );
		$arr['rec_cnt'] = DBRecord::countRecords( RESERVE_TBL, 'WHERE complete > 0 AND autorec='.$arr['id'] );
		$precs = Keyword::search( $rec->keyword, $rec->use_regexp, $rec->collate_ci, $rec->ena_title, $rec->ena_desc, $rec->search_marks,
				$rec->keyword_ex, $rec->use_regexp_ex, $rec->collate_ci_ex, $rec->ena_title_ex, $rec->ena_desc_ex, $rec->search_exmarks,
				$rec->free, $rec->typeGR, $rec->typeBS, $rec->typeCS, $rec->typeEX, $rec->category_id, $rec->channel_id, $rec->weekofdays,
				$rec->prgtime, $rec->period, $rec->duration_from, $rec->duration_to, $rec->sub_genre, $rec->first_genre );
		$arr['res_able_cnt'] = count( $precs );
		array_push( $keywords, $arr );
	}
}
catch( Exception $e ) {
	exit( $e->getMessage() );
}


$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( 'keywords', $keywords );
$smarty->assign( 'menu_list', link_menu_create() );
$smarty->assign( 'spool_freesize', spool_freesize() );
$smarty->assign( 'sitetitle', 'キーワード管理' );
$smarty->display( 'keywordTable.html' );
?>

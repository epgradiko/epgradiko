<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/Keyword.class.php' );
include_once( INSTALL_PATH . '/include/epg_const.php' );

// 設定ファイルの有無を検査する
if( ! file_exists( INSTALL_PATH.'/settings/config.xml') && !file_exists( '/etc/epgrecUNA/config.xml' ) ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}

$settings = Settings::factory();

// 曜日
$weekofdays = array(
					array( 'name' => '月', 'value' => 0, 'checked' => '' ),
					array( 'name' => '火', 'value' => 1, 'checked' => '' ),
					array( 'name' => '水', 'value' => 2, 'checked' => '' ),
					array( 'name' => '木', 'value' => 3, 'checked' => '' ),
					array( 'name' => '金', 'value' => 4, 'checked' => '' ),
					array( 'name' => '土', 'value' => 5, 'checked' => '' ),
					array( 'name' => '日', 'value' => 6, 'checked' => '' )
);
$week_tb = array( '日', '月', '火', '水', '木', '金', '土' );

$autorec_modes  = $RECORD_MODE;
$autorec_mode   = (int)$settings->autorec_mode;
$cs_rec_flg     = (boolean)$settings->cs_rec_flg;

$kw_enable      = TRUE;
$kw_name        = '';
$overlap        = FALSE;
$search         = '';
$search_ex      = '';
$use_regexp     = 0;
$use_regexp_ex  = 0;
$ena_title      = FALSE;
$ena_title_ex   = FALSE;
$ena_desc       = FALSE;
$ena_desc_ex    = FALSE;
$collate_ci     = FALSE;
$collate_ci_ex  = FALSE;
$disp_choose_marks = '';
$choose_marks   = '';
$disp_choose_exmarks = '';
$choose_exmarks = '';
$search_marks   = '';
$search_exmarks = '';
$free           = '';
$typeGR         = TRUE;
$typeBS         = TRUE;
$typeCS         = TRUE;
$typeEX         = TRUE;
$first_genre    = 1;
$category_id    = 0;
$sub_genre      = 16;
$channel_id     = 0;
$weekofday      = 0;
$prgtime        = 24;
$period         = 1;
$duration_from  = '';
$duration_to    = '';
$sft_start      = 0;
$sft_end        = 0;
$discontinuity  = 0;
$priority       = 10;
$sort_order     = 0;
$keyword_id     = 0;
$do_keyword     = 0;
$filename       = '';
$spool          = $settings->spool.'/';
$directory      = $settings->autorec_dir;
$criterion_dura = 0;
$criterion_enab = CRITERION_CHECK;
$rest_alert     = REST_ALERT;
$smart_repeat   = FALSE;
$split_time     = 0;
$trans_set      = '';

if( isset($_POST['isfirst']) ) {
	$isfirst = false;
} else {
	$isfirst = true;
}

try{
	$stations  = array();
	$chid_list = array();
	$stations[0]['id']       = $chid_list[0] = 0;
	$stations[0]['name']     = 'すべて';
	$stations[0]['type']     = 'ALL';
	$stations[0]['selected'] = '';
	foreach( $GR_CHANNEL_MAP as $channel_disc => $channel ) {
		if( $channel == 'NC' ) continue;
		if( !DBRecord::countRecords( CHANNEL_TBL, 'WHERE channel_disc="'.$channel_disc.'"' ) ) continue;
		$c = new DBRecord( CHANNEL_TBL, 'channel_disc', $channel_disc );
		$arr = array();
		$arr['id']       = $chid_list[] = (int)$c->id;
		$arr['name']     = $c->name;
		$arr['type']     = 'GR';
		$arr['disc']     = $c->channel_disc;
		$arr['network_id']     = $c->network_id;
		$arr['sid']      = $c->sid;
		$arr['selected'] = '';
		array_push( $stations, $arr );
	}
	foreach( $BS_CHANNEL_MAP as $channel_disc => $channel ) {
		if( $channel == 'NC' ) continue;
		if( !DBRecord::countRecords( CHANNEL_TBL, 'WHERE channel_disc="'.$channel_disc.'"' ) ) continue;
		$c = new DBRecord( CHANNEL_TBL, 'channel_disc', $channel_disc );
		$arr = array();
		$arr['id']       = $chid_list[] = (int)$c->id;
		$arr['name']     = $c->name;
		$arr['type']     = 'BS';
		$arr['disc']     = $c->channel_disc;
		$arr['network_id']     = $c->network_id;
		$arr['sid']      = $c->sid;
		$arr['selected'] = '';
		array_push( $stations, $arr );
	}
	foreach( $CS_CHANNEL_MAP as $channel_disc => $channel ) {
		if( $channel == 'NC' ) continue;
		if( !DBRecord::countRecords( CHANNEL_TBL, 'WHERE channel_disc="'.$channel_disc.'"' ) ) continue;
		$c = new DBRecord( CHANNEL_TBL, 'channel_disc', $channel_disc );
		$arr = array();
		$arr['id']       = $chid_list[] = (int)$c->id;
		$arr['name']     = $c->name;
		$arr['type']     = 'CS';
		$arr['disc']     = $c->channel_disc;
		$arr['network_id']     = $c->network_id;
		$arr['sid']      = $c->sid;
		$arr['selected'] = '';
		array_push( $stations, $arr );
	}
	foreach( $EX_CHANNEL_MAP as $channel_disc => $channel ) {
		if( $channel == 'NC' ) continue;
		if( !DBRecord::countRecords( CHANNEL_TBL, 'WHERE channel_disc="'.$channel_disc.'"' ) ) continue;
		$c = new DBRecord( CHANNEL_TBL, 'channel_disc', $channel_disc );
		$arr = array();
		$arr['id']       = $chid_list[] = (int)$c->id;
		$arr['name']     = $c->name;
		$arr['disc']     = $c->channel_disc;
		$arr['type']     = 'EX';
		$arr['network_id']     = $c->network_id;
		$arr['sid']      = $c->sid;
		$arr['selected'] = '';
		array_push( $stations, $arr );
	}
}catch( exception $e ) {
	exit( $e->getMessage() );
}

// パラメータの処理
if(isset( $_POST['do_search'] )) {
	if( isset($_POST['search']) ){
		$search = trim($_POST['search']);
		if( $search != '' ){
			$use_regexp = isset($_POST['use_regexp']);
			if( !$use_regexp )
				$collate_ci = isset($_POST['collate_ci']);
			$ena_title  = isset($_POST['ena_title']);
			$ena_desc   = isset($_POST['ena_desc']);
		}
	}
	if( isset($_POST['search_ex']) ){
		$search_ex = trim($_POST['search_ex']);
		if( $search_ex != '' ){
			$use_regexp_ex = isset($_POST['use_regexp_ex']);
			if( !$use_regexp_ex )
				$collate_ci_ex = isset($_POST['collate_ci_ex']);
			$ena_title_ex  = isset($_POST['ena_title_ex']);
			$ena_desc_ex   = isset($_POST['ena_desc_ex']);
		}
	}
	if( isset($_POST['choose_marks']) ){
		$choose_marks_tmp = explode(',', $_POST['choose_marks']);
		foreach( $choose_marks_tmp as $choose_mark ) {
			if( isset($_POST['mark'.$choose_mark]) ) {
				$choose_marks_array[$choose_mark] = $_POST['mark'.$choose_mark];
				$search_marks .= key(array_slice( array_column(ProgramMark, 'choice', 'name'), $choose_mark - 1, 1, true));
			} else {
				$choose_marks_array[$choose_mark] = '';
			}
		}
	}
	if( isset($_POST['choose_exmarks']) ){
		$choose_marks_tmp = explode(',', $_POST['choose_exmarks']);
		foreach( $choose_marks_tmp as $choose_mark ) {
			if( isset($_POST['exmark'.$choose_mark]) ) {
				$choose_exmarks_array[$choose_mark] = $_POST['exmark'.$choose_mark];
				$search_exmarks .= key(array_slice( array_column(ProgramMark, 'choice', 'name'), $choose_mark - 1, 1, true));
			} else {
				$choose_exmarks_array[$choose_mark] = '';
			}
		}
	}
	if( isset($_POST['free']) )
		$free = (int)($_POST['free']);
	if( isset($_POST['station']) )
		$channel_id = (int)($_POST['station']);
	if( $channel_id ){
		switch( $stations[array_search( $channel_id, $chid_list )]['type'] ){
			case 'GR':
				$typeBS = $typeCS = $typeEX = FALSE;
				break;
			case 'BS':
				$typeGR = $typeCS = $typeEX = FALSE;
				break;
			case 'CS':
				$typeGR = $typeBS = $typeEX = FALSE;
				break;
			case 'EX':
				$typeGR = $typeBS = $typeCS = FALSE;
				break;
		}
	}else{
		$typeGR = isset($_POST['typeGR']);
		$typeBS = isset($_POST['typeBS']);
		$typeCS = isset($_POST['typeCS']);
		$typeEX = isset($_POST['typeEX']);
	}
	if( isset($_POST['category_id']) ){
		$category_id = (int)($_POST['category_id']);
		$first_genre = !isset($_POST['first_genre']);
		if( isset($_POST['sub_genre']) )
			$sub_genre = (int)($_POST['sub_genre']);
	}
	if( isset($_POST['week0']) )
		$weekofday += 0x1;
	if( isset($_POST['week1']) )
		$weekofday += 0x2;
	if( isset($_POST['week2']) )
		$weekofday += 0x4;
	if( isset($_POST['week3']) )
		$weekofday += 0x8;
	if( isset($_POST['week4']) )
		$weekofday += 0x10;
	if( isset($_POST['week5']) )
		$weekofday += 0x20;
	if( isset($_POST['week6']) )
		$weekofday += 0x40;
	if( isset($_POST['prgtime']) )
		$prgtime = (int)($_POST['prgtime']);
	if( isset($_POST['period']) )
		$period = (int)($_POST['period']);

	if( isset($_POST['duration_from']) ) {
		if( $_POST['duration_from'] === '' ) {
			$duration_from = '';
		} else {
			$duration_from = (int)($_POST['duration_from']);
		}
	}
	if( isset($_POST['duration_to']) ) {
		if( $_POST['duration_to'] === '' ) {
			$duration_to = '';
		} else {
			$duration_to = (int)($_POST['duration_to']);
		}
	}
	if( $duration_from !== '' && $duration_to !=='' ) {
		if( $duration_from > $duration_to ) {
			list($duration_from, $duration_to) = array($duration_to, $duration_from);
		}
	}

	if( isset($_POST['keyword_id']) ){
		$keyword_id = (int)($_POST['keyword_id']);
		if( $keyword_id ){
			if( isset($_POST['sort_order']) )
				$sort_order = $_POST['sort_order'];
			if( isset($_POST['kw_name']) )
				$kw_name = $_POST['kw_name'];
			if( isset($_POST['kw_enable']) )
				$kw_enable = (boolean)$_POST['kw_enable'];
			if( isset($_POST['sft_start']) )
				$sft_start = transTime( parse_time( $_POST['sft_start'] ) );
			if( isset($_POST['sft_end']) ){
				if( $_POST['sft_end'][0] === '@' )
					$sft_end = '@'.transTime( parse_time(  substr( $_POST['sft_end'], 1 ) ) );
				else
					$sft_end = transTime( parse_time( $_POST['sft_end'] ) );
			}
			if( isset($_POST['discontinuity']) )
				$discontinuity = (boolean)$_POST['discontinuity'];
			if( isset($_POST['priority']) )
				$priority = (int)($_POST['priority']);
			if( isset($_POST['overlap']) )
				$overlap = (boolean)$_POST['overlap'];
			if( isset($_POST['filename']) )
				$filename = $_POST['filename'];
			if( isset($_POST['directory']) )
				$directory = $_POST['directory'];
			if( isset($_POST['autorec_mode']) )
				$autorec_mode = (int)($_POST['autorec_mode']);
			if( isset($_POST['rest_alert']) )
				$rest_alert = (boolean)$_POST['rest_alert'];
			if( isset($_POST['criterion_enab']) )
				$criterion_enab = (boolean)$_POST['criterion_enab'];
			if( isset($_POST['smart_repeat']) )
				$smart_repeat = (boolean)$_POST['smart_repeat'];
			if( isset($_POST['split_time']) )
				$split_time = parse_time( $_POST['split_time'] );
		}
	}
	$do_keyword = 1;
}else{
	if( isset($_GET['keyword_id']) ) {
		$keyword_id    = (int)($_GET['keyword_id']);
		if( DBRecord::countRecords( KEYWORD_TBL, 'WHERE id='.$keyword_id ) == 0 ){
			echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body onLoad="var ref = document.referrer;var key = \'keywordTable.php\';if( ref.indexOf(key) > -1 ){location.href = key;}else{if( ref.indexOf(\'programTable.php\') > -1 ){location.href = key;}else{location.href = ref;}}"></body></html>';
			exit( 1 );
		}
		$keyc          = new DBRecord( KEYWORD_TBL, 'id', $keyword_id );
		$search        = $keyc->keyword;
		$search_ex     = $keyc->keyword_ex;
		$use_regexp    = (int)($keyc->use_regexp);
		$use_regexp_ex = (int)($keyc->use_regexp_ex);
		$ena_title     = (boolean)$keyc->ena_title;
		$ena_title_ex  = (boolean)$keyc->ena_title_ex;
		$ena_desc      = (boolean)$keyc->ena_desc;
		$ena_desc_ex   = (boolean)$keyc->ena_desc_ex;
		$collate_ci    = (boolean)$keyc->collate_ci;
		$collate_ci_ex = (boolean)$keyc->collate_ci_ex;
		$search_marks  = $keyc->search_marks;
		$search_exmarks = $keyc->search_exmarks;
		$free          = (boolean)$keyc->free;
		$typeGR        = (boolean)$keyc->typeGR;
		$typeBS        = (boolean)$keyc->typeBS;
		$typeCS        = (boolean)$keyc->typeCS;
		$typeEX        = (boolean)$keyc->typeEX;
		$channel_id    = (int)($keyc->channel_id);
		$category_id   = (int)($keyc->category_id);
		$first_genre   = (boolean)($keyc->first_genre);
		$sub_genre     = (int)($keyc->sub_genre);
		$weekofday     = (int)($keyc->weekofdays);
		$prgtime       = (int)($keyc->prgtime);
		$period        = (int)$keyc->period;
		$duration_from = $keyc->duration_from;
		$duration_to   = $keyc->duration_to;
		$kw_name       = $keyc->name;
		$kw_enable     = (boolean)$keyc->kw_enable;
		$sft_start     = transTime( $keyc->sft_start );
		$sft_end       = ((boolean)$keyc->duration_chg ? '@':'').transTime( $keyc->sft_end );
		$discontinuity = (int)($keyc->discontinuity);
		$priority      = (int)($keyc->priority);
		$overlap       = (boolean)$keyc->overlap;
		$filename      = $keyc->filename_format;
		$directory     = $keyc->directory;
		$criterion_dura = (int)$keyc->criterion_dura;
		$criterion_enab = $criterion_dura ? TRUE : FALSE;
		$rest_alert    = (int)$keyc->rest_alert==0 ? FALSE : TRUE;
		$smart_repeat  = (boolean)$keyc->smart_repeat;
		$split_time    = (int)$keyc->split_time;
		$autorec_mode  = (int)$keyc->autorec_mode;
		$sort_order    = (int)$keyc->sort_order;
		$do_keyword = 1;
		$choose_mark = '';
		$choose_exmark = '';
		foreach( array_keys(array_column(ProgramMark, 'choice', 'name'),true) as $mark ) {
			if( strpos($search_marks, $mark) !== false) {
				$mark_id = array_search($mark, array_keys(array_column(ProgramMark, 'choice', 'name'))) + 1;
				$choose_marks_array[$mark_id] = '1';
			}
			if( strpos($search_exmarks, $mark) !== false) {
				$mark_id = array_search($mark, array_keys(array_column(ProgramMark, 'choice', 'name'))) + 1;
				$choose_exmarks_array[$mark_id] = '1';
			}
		}
	}else{
		if( isset($_GET['search'])){
			$search = trim($_GET['search']);
			if( $search != '' ){
				if( isset($_GET['use_regexp']) && ($_GET['use_regexp']) ) {
					$use_regexp = (int)($_GET['use_regexp']);
				}
				if( !$use_regexp && isset($_GET['collate_ci']) )
					$collate_ci = (boolean)$_GET['collate_ci'];
				if( isset($_GET['ena_title'])){
					$ena_title = (boolean)$_GET['ena_title'];
				}else
					$ena_title = TRUE;
				if( isset($_GET['ena_desc'])){
					$ena_desc = (boolean)$_GET['ena_desc'];
				}else
					$ena_desc = FALSE;
				$do_keyword = 1;
			}
		}
		if( isset($_GET['search_ex'])){
			$search_ex = trim($_GET['search_ex']);
			if( $search_ex != '' ){
				if( isset($_GET['use_regexp_ex']) && ($_GET['use_regexp_ex']) ) {
					$use_regexp_ex = (int)($_GET['use_regexp_ex']);
				}
				if( !$use_regexp_ex && isset($_GET['collate_ci_ex']) )
					$collate_ci_ex = (boolean)$_GET['collate_ci_ex'];
				if( isset($_GET['ena_title_ex'])){
					$ena_title_ex = (boolean)$_GET['ena_title_ex'];
				}else
					$ena_title_ex = TRUE;
				if( isset($_GET['ena_desc_ex'])){
					$ena_desc_ex = (boolean)$_GET['ena_desc_ex'];
				}else
					$ena_desc_ex = FALSE;
				$do_keyword = 1;
			}
		}

		if( isset($_POST['choose_marks']) ){
			$choose_marks_tmp = explode(',', $_POST['choose_marks']);
			foreach( $choose_marks_tmp as $choose_mark ) {
				if( isset($_POST['mark'.$choose_mark]) ) {
					$choose_marks_array[$choose_mark] = $_POST['mark'.$choose_mark];
					$search_marks .= key(array_slice( array_column(ProgramMark, 'choice', 'name'), $choose_mark - 1, 1, true));
				} else {
					$choose_marks_array[$choose_mark] = '';
				}
			}
		}
		if( isset($_POST['choose_exmarks']) ){
			$choose_marks_tmp = explode(',', $_POST['choose_exmarks']);
			foreach( $choose_marks_tmp as $choose_mark ) {
				if( isset($_POST['exmark'.$choose_mark]) ) {
					$choose_exmarks_array[$choose_mark] = $_POST['exmark'.$choose_mark];
					$search_exmarks .= key(array_slice( array_column(ProgramMark, 'choice', 'name'), $choose_mark - 1, 1, true));
				} else {
					$choose_exmarks_array[$choose_mark] = '';
				}
			}
		}

		if( isset($_GET['free'])) {
			$free = (int)($_GET['free']);
			$do_keyword = 1;
		}

		if( isset($_GET['station'])) {
			$channel_id = (int)($_GET['station']);
			if( $channel_id ){
				switch( $stations[array_search( $channel_id, $chid_list )]['type'] ){
					case 'GR':
						$typeBS = $typeCS = $typeEX = FALSE;
						break;
					case 'BS':
						$typeGR = $typeCS = $typeEX = FALSE;
						break;
					case 'CS':
						$typeGR = $typeBS = $typeEX = FALSE;
						break;
					case 'EX':
						$typeGR = $typeBS = $typeCS = FALSE;
						break;
				}
				$do_keyword = 1;
			}
		}
		if( !$channel_id && isset($_GET['type'])) {
			switch( $_GET['type'] ){
				case 'GR';
					$typeBS = FALSE;
					$typeCS = FALSE;
					$typeEX = FALSE;
					break;
				case 'BS';
					$typeGR = FALSE;
					$typeCS = FALSE;
					$typeEX = FALSE;
					break;
				case 'CS';
					$typeGR = FALSE;
					$typeBS = FALSE;
					$typeEX = FALSE;
					break;
				case 'EX';
					$typeGR = FALSE;
					$typeBS = FALSE;
					$typeCS = FALSE;
					break;
			}
			$do_keyword = 1;
		}
		if( isset($_GET['category_id'])) {
			$category_id = (int)($_GET['category_id']);
			if( isset($_GET['sub_genre'])) {
				$sub_genre = (int)($_GET['sub_genre']);
			}
			$do_keyword = 1;
		}
	}
}

$id_selected                        = array_search( $channel_id, $chid_list );
$stations[$id_selected]['selected'] = 'selected';

if( !$typeGR && !$typeBS && !$typeCS && !$typeEX ){
	$typeGR = TRUE;
	$typeBS = TRUE;
	if( $cs_rec_flg )
		$typeCS = TRUE;
	if( (int)$settings->ex_tuners > 0 )
		$typeEX = TRUE;
}

if( $search!=NULL && !$ena_title && !$ena_desc ){
	$ena_title  = TRUE;
	$ena_desc   = TRUE;
}

if( $search_ex!=NULL && !$ena_title_ex && !$ena_desc_ex ){
	$ena_title_ex  = TRUE;
	$ena_desc_ex   = TRUE;
}

if( $weekofday == 0 )
	$weekofday = 0x7f;

$k_category_name = '';
$crecs = DBRecord::createRecords(CATEGORY_TBL);
$cats = array();
$cats[0]['id'] = 0;
$cats[0]['name'] = 'すべて';
$cats[0]['selected'] = $category_id == 0 ? 'selected' : '';
foreach( $crecs as $c ) {
	$arr = array();
	$arr['id'] = $c->id;
	$arr['name'] = $c->name_jp;
	$arr['selected'] = $c->id == $category_id ? 'selected' : '';
	if( $c->id == $category_id ) $k_category_name = $c->name_jp;
	array_push( $cats, $arr );
}
	
try{
	$programs = array();
	if( $do_keyword ){
		$precs = Keyword::search( $search, $use_regexp, $collate_ci, $ena_title, $ena_desc, $search_marks,
					 $search_ex, $use_regexp_ex, $collate_ci_ex, $ena_title_ex, $ena_desc_ex, $search_exmarks,
					 $free, $typeGR, $typeBS, $typeCS, $typeEX, $category_id, $channel_id, $weekofday,
					 $prgtime, $period, $duration_from, $duration_to, $sub_genre, $first_genre );
	
		$prg_cnt = count( $precs );
		foreach( $precs as $key => $p ){
			try{
				$arr = array();
				$arr['no']           = (string)($key + 1);
				$arr['type']         = $p->type;
				$start_time          = toTimestamp($p->starttime);
				$end_time            = toTimestamp($p->endtime);
				$duration            = $end_time - $start_time;
				if( $duration > $criterion_dura ) $criterion_dura = $duration;
				$arr['date']         = date( 'm/d(', $start_time ).$week_tb[date( 'w', $start_time )].')';
				$arr['starttime']    = date( 'H:i:s-', $start_time );
				$arr['endtime']      = date( 'H:i:s', $end_time );
				$arr['duration']     = '('.date( 'H:i:s', $duration-9*60*60 ).')';
				$arr['prg_top']      = date( 'YmdH', ((int)$start_time/60)%60 ? $start_time : $start_time-60*60*1 );
				$station_point       = array_search( (int)$p->channel_id, $chid_list );
				$arr['station_name'] = '<a href="index.php?ch='.$stations[$station_point]['disc'].'&time='.$arr['prg_top'].'" title="単局EPG番組表へジャンプ">'.
							$stations[$station_point]['name'].'</a>';
				$arr['title']       = $p->title;
				$arr['pre_title']   = strtr($p->pre_title, array_column(ProgramMark, 'char', 'name'));
				$arr['post_title']  = strtr($p->post_title, array_column(ProgramMark, 'char', 'name'));
//				$arr['description'] = mb_strimwidth($p->description, 0, 200, '…', 'UTF-8');
				$arr['description'] = $p->description;
				$arr['id']          = $p->id;
				$arr['cat']         = $p->category_id;
				$arr['autorec']     = $p->autorec;
				$arr['keyword']     = putProgramHtml( $arr['title'], $p->type, $p->channel_id, $p->category_id, $p->sub_genre );
				$arr['rev_id']      = 0;
				$arr['key_id']      = 0;
				$arr['genres']	    = $cats[$p->category_id]['name'];
				if( $p->genre2 && $p->category_id !== $p->genre2) $arr['genres'] .= '<br>'.$cats[$p->genre2]['name'];
				if( $p->genre3 && $p->genre2 !== $p->genre3) $arr['genres'] .= '<br>'.$cats[$p->genre3]['name'];
				$arr['video_info']  = isset(VIDEO_INFO[$p->video_type]) ? VIDEO_INFO[$p->video_type]: '';
				$arr['audio_info']  = isset(AUDIO_INFO[$p->audio_type]) ? AUDIO_INFO[$p->audio_type]: '';
				$mark_sp            = str_replace('][', ']&nbsp[', $p->pre_title.$p->post_title);
				$mark_bracketL      = str_replace('[', '<span class="mark_class">', $mark_sp);
				$mark_bracketR      = str_replace(']', '</span>', $mark_bracketL);
				$arr['mark']	    = $mark_bracketR; // $p->mark;
				$arr['rev_info']    = ''; 
				// 予約情報取得
				$wherestr = "WHERE ((starttime BETWEEN '".$p->starttime."' AND ('".$p->endtime."' - INTERVAL 1 SECOND)) OR "
					."((endtime - INTERVAL 1 SECOND) BETWEEN '".$p->starttime."' AND ('".$p->endtime."' - INTERVAL 1 SECOND))) AND "
					."channel_disc='".$p->channel_disc."' AND complete=0 ORDER BY starttime";
				$rev = DBRecord::createRecords(RESERVE_TBL, $wherestr);
				$arr['excl'] = count( $rev );
				if( $arr['excl'] ){
					$arr['rec']   = 1;
					$arr['resvd'] = 'resvd';
					if( $keyword_id ){
						foreach( $rev as $r ){
							if( (int)$r->autorec == $keyword_id ){
								$arr['key_id'] = $keyword_id;
								goto EXIT_REV;
							}
						}
						unset( $r );
					}
					foreach( $rev as $r ){
						// 複数の場合はどうする？排他のみはID付きが1つだけなので判別可能
						if( (int)$r->autorec ){
							$arr['key_id'] = (int)$r->autorec;
							goto EXIT_REV;
						}
					}
EXIT_REV:;
					array_push( $programs, $arr );

					$arr['excl']         = 1;
					$arr['resvd']        = 'resvd_child';
					$stk_no              = $arr['no'];
					$arr['keyword']      = '';
					foreach( $rev as $cnt => $r ){
						$arr['no']           = $stk_no.'_'.($cnt+1);
						$arr['title'] = $r->title;
						$arr['pre_title'] = strtr($r->pre_title, array_column(ProgramMark, 'char', 'name'));
						$arr['post_title'] = strtr($r->post_title, array_column(ProgramMark, 'char', 'name'));
						if( $r->title !== $p->title ) $arr['title_color'] = 'black';
						else $arr['title_color']   = 'gray';
						$start_time          = toTimestamp($r->starttime);
						$end_time            = toTimestamp($r->endtime);
						$duration            = $end_time - $start_time;
						$arr['date']         = date( 'm/d(', $start_time ).$week_tb[date( 'w', $start_time )].')';
						$arr['starttime']    = date( 'H:i:s-', $start_time );
						$arr['endtime']      = date( 'H:i:s', $end_time );
						$arr['duration']     = '('.date( 'H:i:s', $duration-9*60*60 ).')';
						if (($r->starttime !== $p->starttime) || ($r->endtime !== $p->endtime)) $arr['time_color'] = 'black';
						else $arr['time_color'] = 'gray';
						if( $r->autorec ){
							$arr['genres'] = '<a href="programTable.php?keyword_id='.$r->autorec.'" title="キーワード編集にジャンプ">自動</a><br>';
						} else {
							$arr['genres'] = '手動<br>';
						}
						if( $r->program_id ){
							$chid = array_search( $r->channel_id, $chid_list );
							$arr['genres']    .= '<br>番組予約:<br><small><small>'.
										sprintf('%d%05d%05d', $stations[$chid]['network_id'], $stations[$chid]['sid'], $p->eid).'</small></small>';
						} else {
							$arr['genres']    .= '<br>時刻予約:<br>'.$r->channel_disc;
						}
						$arr['rev_info']     = $RECORD_MODE[$r->mode]['name'];
						$arr['description']  = $r->description;
						if( $r->description !== $p->description ) $arr['desc_color']  = 'black';
						else $arr['desc_color']  = 'gray';
						$arr['prg_top']      = date( 'YmdH', ((int)$start_time/60)%60 ? $start_time : $start_time-60*60*1 );
						$arr['rev_id']       = $r->id;
						$arr['rec']          = $r->tuner + 1;
						$arr['key_id']       = (int)$r->autorec;
						array_push( $programs, $arr );
					}
				}else{
					$arr['rec']   = 0;
					$arr['resvd'] = '';
					array_push( $programs, $arr );
				}
			}catch( exception $e ){
			}
		}
	}else
		$prg_cnt = 0;

	if( $criterion_dura===0 && $criterion_enab )
		$criterion_dura = 1;

	$types = array();
	$type_names = '';
	if( $settings->gr_tuners != 0 ) {
		$arr = array();
		$arr['name'] = '地デジ';
		$arr['value'] = 'GR';
		if( $typeGR ){
			$arr['checked'] = 'checked="checked"';
			$type_names     = 'GR';
		}else
			$arr['checked'] =  '';
		array_push( $types, $arr );
	}
	if( $settings->bs_tuners != 0 ) {
		$arr = array();
		$arr['name'] = 'BS';
		$arr['value'] = 'BS';
		if( $typeBS ){
			$arr['checked'] = 'checked="checked"';
			if( $type_names != '' )
				$type_names .= '+';
			$type_names    .= 'BS';
		}else
			$arr['checked'] =  '';
		array_push( $types, $arr );

		// CS
		if( $cs_rec_flg ){
			$arr = array();
			$arr['name'] = 'CS';
			$arr['value'] = 'CS';
			if( $typeCS ){
				$arr['checked'] = 'checked="checked"';
				if( $type_names != '' )
					$type_names .= '+';
				$type_names    .= 'CS';
			}else
				$arr['checked'] =  '';
			array_push( $types, $arr );
		}
	}
	if( $settings->ex_tuners != 0 ){
		$arr = array();
		$arr['name'] = 'ラジオ';
		$arr['value'] = 'EX';
		if( $typeEX ){
			$arr['checked'] = 'checked="checked"';
			if( $type_names != '' )
				$type_names .= '+';
			$type_names    .= 'EX';
		}else
			$arr['checked'] =  '';
		array_push( $types, $arr );
	}

	$wds_name = '';
	for( $b_cnt=0; $b_cnt<7; $b_cnt++ ){
		if( $weekofday & ( 0x01 << $b_cnt ) ){
			$weekofdays[$b_cnt]['checked'] = 'checked="checked"' ;
			$wds_name                     .= $weekofdays[$b_cnt]['name'];
		}
	}
	// 時間帯
	$prgtimes = array();
	for( $i=0; $i < 25; $i++ ) {
		array_push( $prgtimes, 
			array(  'name' => ( $i == 24  ? 'なし' : sprintf('%d時',$i) ),
					'value' => $i,
					'selected' =>  ( $i == $prgtime ? 'selected' : '' ) )
		);
	}
	// 時間幅
	$periods = array();
	for( $i=1; $i < 24; $i++ ) {
		array_push( $periods, 
			array(  'name' => sprintf('%d時間',$i),
					'value' => $i,
					'selected' =>  ( $i===$period ? 'selected' : '' ) )
		);
	}

	// ディレクトリ
	$dir_collection = get_directories( INSTALL_PATH.$settings->spool );

	// トランスコード設定
	if( array_key_exists( 'tsuffix', end($autorec_modes) ) ){
		if( $keyword_id ){
			$trans_obj = new DBRecord( TRANSEXPAND_TBL );
			$trans_ex  = $trans_obj->fetch_array( null, null, 'key_id='.$keyword_id.' ORDER BY type_no' );
		}else
			$trans_ex  = array();
		$loop_cnt = 0;
		for( $loop=count($trans_ex); $loop<TRANS_SET_KEYWD; $loop++ ){
			$arr = array();
			if( $loop_cnt === 0) {
				$arr['mode']   = $settings->autorec_mode;
				$arr['ts_del'] = TRUE;
				$arr['dir']    = htmlspecialchars($settings->autorec_trans_dir,ENT_QUOTES);
				$loop_cnt++;
			} else {
				$arr['mode']   = 0;
				$arr['ts_del'] = FALSE;
				$arr['dir']    = '';
			}
			$trans_ex[]    = $arr;
		}

		$trans_path = str_replace( '%VIDEO%', INSTALL_PATH.$settings->spool, TRANS_ROOT );
		$path_html  = htmlspecialchars( $trans_path, ENT_QUOTES );
		$tsdel      = FALSE;
		$trans_set  = '<fieldset><legend><b>トランスコード設定</b></legend>';
		foreach( $trans_ex as $key => $trans_unit ){
//			$trans_set .= '<b>設定'.($key+1).':</b> <b>モード</b><select name="k_trans_mode'.$key.'" >';
//			$trans_set .= '<option value="0"'.( $trans_unit['mode']===0 ? ' selected ':'' ).'>未指定</option>';
//			foreach( $autorec_modes as $loop => $mode ){
//				if( isset($mode['tsuffix']) ) {
//					$trans_set .= '<option value="'.$loop.'"'.( (int)$trans_unit['mode']===$loop ? ' selected ':'' ).'>'.$mode['name'].'</option>';
//				}
//			}
//			$trans_set .= '</select> '
			$trans_set .= '<input type="hidden" name="k_trans_mode'.$key.' value="0"> ';
			$trans_set .= '<b>保存ディレクトリ </b>'.$path_html.
					'/<input type="text" name="k_transdir'.$key.'" value="'.htmlspecialchars($trans_unit['dir'],ENT_QUOTES).'" size="80" class="required" list="trans_ex"><br>';
			if( $trans_unit['ts_del'] )
				$tsdel = TRUE;
		}
		$trans_set .= '<datalist id="trans_ex">'.get_directories( $trans_path ).'</datalist>';
		$trans_set .= '<label><input type="checkbox" name="k_auto_del" value="1" '.($tsdel ? 'checked="checked"' : '').'><b>元ファイルの自動削除</b></label></fieldset>';
	}

	// 録画設定一覧からトランスコード設定を削除
	foreach( $autorec_modes as $loop => $mode ){
//		if( isset($mode['tsuffix']) && $autorec_mode<$loop ){
//			array_splice( $autorec_modes, $loop );
//			break;
//		}
		$autorec_modes[$loop]['selected'] = '';
	}
	$autorec_modes[$autorec_mode]['selected'] = 'selected';

	$choose_mark = '';
	$choose_exmark = '';
	foreach( array_keys(array_column(ProgramMark, 'choice', 'name'), true) as $mark ) {
		$mark_id = array_search($mark, array_keys(array_column(ProgramMark, 'choice', 'name'))) + 1;
		$disp_mark = str_replace('[', '', str_replace(']', '',$mark));
		$choose_mark .= '&nbsp;<input type="checkbox" name="mark'.$mark_id.'" id="mark_id'.$mark_id.'" class="mark_checkbox" value="1"';
		$choose_exmark .= '&nbsp;<input type="checkbox" name="exmark'.$mark_id.'" id="exmark_id'.$mark_id.'" class="mark_checkbox" value="1"';
		if( isset($choose_marks_array[$mark_id]) && $choose_marks_array[$mark_id] !== '') {
			$choose_mark .= ' checked';
		} else {
			$choose_marks_array[$mark_id] = '';
		}
		if( isset($choose_exmarks_array[$mark_id]) && $choose_exmarks_array[$mark_id] !== '') {
			$choose_exmark .= ' checked';
		} else {
			$choose_exmarks_array[$mark_id] = '';
		}
		$choose_mark .= ' onclick="mark_change(\'mark_id'.$mark_id.'\', \'exmark_id'.$mark_id.'\','
				.' \'mark_btn_id'.$mark_id.'\', \'exmark_btn_id'.$mark_id.'\')"';
		$choose_mark .= ' /><label for="mark_id'.$mark_id.'" class="mark_class" id="mark_btn_id'.$mark_id.'"';
		if( $choose_marks_array[$mark_id] ) {
			$choose_mark .= ' style="background-color: skyblue;"';
		}
		$choose_mark .= '>'.$disp_mark.'</label>';

		$choose_exmark .= ' onclick="mark_change(\'exmark_id'.$mark_id.'\', \'mark_id'.$mark_id.'\','
				.' \'exmark_btn_id'.$mark_id.'\', \'mark_btn_id'.$mark_id.'\')"';
		$choose_exmark .= ' /><label for="exmark_id'.$mark_id.'" class="mark_class" id="exmark_btn_id'.$mark_id.'"';
		if( $choose_exmarks_array[$mark_id] ) {
			$choose_exmark .= ' style="background-color: skyblue;"';
		}
		$choose_exmark .= '>'.$disp_mark.'</label>';
	}

	$smarty = new Smarty();
	$smarty->template_dir = INSTALL_PATH . "/templates/";
	$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
	$smarty->cache_dir = INSTALL_PATH . "/cache/";
	$smarty->assign( 'isfirst', $isfirst );
	$smarty->assign( 'sitetitle', !$keyword_id ? '番組検索' : 'キーワード編集' );
	$smarty->assign( 'menu_list', link_menu_create() );
	$smarty->assign( 'spool_freesize', spool_freesize() );
	$smarty->assign( 'do_keyword', $do_keyword );
	$smarty->assign( 'programs', $programs );
	$smarty->assign( 'cats', $cats );
	$smarty->assign( 'prg_cnt', $prg_cnt );
	$smarty->assign( 'k_category', $category_id );
	$smarty->assign( 'k_category_name', $k_category_name );
	$smarty->assign( 'k_sub_genre', $sub_genre );
	$smarty->assign( 'first_genre', $first_genre );
	$smarty->assign( 'types', $types );
	$smarty->assign( 'kw_enable', $kw_enable ? 1 : 0 );
	$smarty->assign( 'kw_name', $kw_name );
	$smarty->assign( 'overlap', $overlap ? 1 : 0 );
	$smarty->assign( 'free', $free ? 1 : 0 );
	$smarty->assign( 'k_typeGR', $typeGR ? 1 : 0 );
	$smarty->assign( 'k_typeBS', $typeBS ? 1 : 0 );
	$smarty->assign( 'k_typeCS', $typeCS ? 1 : 0 );
	$smarty->assign( 'k_typeEX', $typeEX ? 1 : 0 );
	$smarty->assign( 'type_names', $type_names );
	$smarty->assign( 'search' , $search );
	$smarty->assign( 'search_ex' , $search_ex );
	$smarty->assign( 'use_regexp', $use_regexp ? 1 : 0 );
	$smarty->assign( 'use_regexp_ex', $use_regexp_ex ? 1 : 0 );
	$smarty->assign( 'ena_title', $ena_title ? 1 : 0 );
	$smarty->assign( 'ena_title_ex', $ena_title_ex ? 1 : 0 );
	$smarty->assign( 'ena_desc', $ena_desc ? 1 : 0 );
	$smarty->assign( 'ena_desc_ex', $ena_desc_ex ? 1 : 0 );
	$smarty->assign( 'collate_ci', $collate_ci ? 1 : 0 );
	$smarty->assign( 'collate_ci_ex', $collate_ci_ex ? 1 : 0 );
	$smarty->assign( 'stations', $stations );
	$smarty->assign( 'k_station', $channel_id );
	$smarty->assign( 'k_station_name', $stations[$id_selected]['name'] );
	$smarty->assign( 'weekofday', $weekofday );
	$smarty->assign( 'wds_name', $wds_name );
	$smarty->assign( 'weekofdays', $weekofdays );
	$smarty->assign( 'autorec_modes', $autorec_modes );
	$smarty->assign( 'autorec_mode', $autorec_mode );
	$smarty->assign( 'prgtimes', $prgtimes );
	$smarty->assign( 'prgtime', $prgtime );
	$smarty->assign( 'periods', $periods );
	$smarty->assign( 'period', $period );
	$smarty->assign( 'keyword_id', $keyword_id );
	$smarty->assign( 'duration_from', $duration_from );
	$smarty->assign( 'duration_to', $duration_to );
	$smarty->assign( 'sft_start', $sft_start );
	$smarty->assign( 'sft_end', $sft_end );
	$smarty->assign( 'discontinuity', $discontinuity ? 1 : 0 );
	$smarty->assign( 'priority', $priority );
	$smarty->assign( 'filename', $filename );
	$smarty->assign( 'defaultname', $settings->filename_format );
	$smarty->assign( 'spool', $spool );
	$smarty->assign( 'directory', $directory );
	$smarty->assign( 'dir_collection', $dir_collection );
	$smarty->assign( 'criterion_dura', $criterion_dura );
	$smarty->assign( 'criterion_enab', $criterion_enab ? 1 : 0 );
	$smarty->assign( 'rest_alert', $rest_alert );
	$smarty->assign( 'smart_repeat', $smart_repeat ? 1 : 0 );
	$smarty->assign( 'split_time', transTime($split_time) );
	$smarty->assign( 'trans_set', $trans_set );
	$smarty->assign( 'search_marks', $search_marks );
	$smarty->assign( 'search_exmarks', $search_exmarks );
	$smarty->assign( 'choose_mark', $choose_mark );
	$smarty->assign( 'disp_choose_marks', $disp_choose_marks );
	$smarty->assign( 'choose_marks', implode(',', array_keys($choose_marks_array)) );
	$smarty->assign( 'choose_exmark', $choose_exmark );
	$smarty->assign( 'disp_choose_exmarks', $disp_choose_exmarks );
	$smarty->assign( 'choose_exmarks', implode(',', array_keys($choose_exmarks_array)) );
	$smarty->assign( 'sort_order', $sort_order );
	$smarty->assign( 'norec', isset($_GET['norec']) ? TRUE : FALSE );
	$smarty->display('programTable.html');
}
catch( exception $e ) {
	exit( $e->getMessage() );
}
?>

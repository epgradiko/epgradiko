<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );

// 設定ファイルの有無を検査する
if( ! file_exists( INSTALL_PATH.'/settings/config.xml') && !file_exists( '/etc/epgrecUNA/config.xml' ) ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}

$settings = Settings::factory();

if( isset( $_GET['levels'] ) ){
	$level0 = $level1 = $level2 = $level3 = FALSE;
	foreach( explode( ',', $_GET['levels'] ) as $who_lv ){
		switch( $who_lv ){
			case '0':
				$level0 = TRUE;
				break;
			case '1':
				$level1 = TRUE;
				break;
			case '2':
				$level2 = TRUE;
				break;
			case '3':
				$level3 = TRUE;
				break;
		}
	}
}else{
	$level0 = isset($_POST['level0']);
	$level1 = isset($_POST['level1']);
	$level2 = isset($_POST['level2']);
	$level3 = isset($_POST['level3']);
	if( !$level0 && !$level1 && !$level2 && !$level3 )
		$level0 = $level1 = $level2 = TRUE;
}

$log_levels = array(
	0 => array( 'label' => '情報',   'view' => $level0 ),
	1 => array( 'label' => '警告',   'view' => $level1 ),
	2 => array( 'label' => 'エラー', 'view' => $level2 ),
	3 => array( 'label' => 'DEBUG',  'view' => $level3 ),
);

$search = '';
foreach( $log_levels as $key => $level ){
	if( $level['view'] ){
		if( $search !== '' )
			$search .= ',';
		$search .= (string)$key;
	}
}
$sql_que = 'level IN ('.$search.')';

$page             = 1;
$full_mode        = FALSE;
$separate_records = SEPARATE_RECORDS_LOGVIEW!==FALSE ? SEPARATE_RECORDS_LOGVIEW : SEPARATE_RECORDS;
if( $separate_records===FALSE || $separate_records<1 )
	$full_mode = TRUE;
else
	if( isset( $_GET['page']) ){
		if( $_GET['page'] === '-' )
			$full_mode = TRUE;
		else
			$page = (int)$_GET['page'];
	}
if( $full_mode ){
	$log_limit = '';
	$pager     = '';
}else{
	$all_cnt   = DBRecord::countRecords( LOG_TBL, $sql_que!=='' ? 'WHERE '.$sql_que : '' );
	$log_limit = ' LIMIT '.(($page-1)*$separate_records).','.$separate_records;
	$pager     = make_pager( 'logViewer.php', $separate_records, $all_cnt, $page, 'levels='.$search.'&' ) ;
}

$log_obj = new DBRecord( LOG_TBL );
$arr     = $log_obj->fetch_array( null, null, $sql_que.' ORDER BY logtime DESC, id DESC'.$log_limit );
$logs    = array();
foreach( $arr as $low ){
	$log = array();
	$log['level']   = (int)$low['level'];
	$log['label']   = $log_levels[$log['level']]['label'];
	$log['logtime'] = $low['logtime'];
	$log['message'] = $low['message'];
	array_push( $logs, $log );
}


$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( 'sitetitle' , '動作ログ' );
$smarty->assign( 'logs',       $logs );
$smarty->assign( 'log_levels', $log_levels );
$smarty->assign( 'pager',      $pager );
$smarty->assign( 'menu_list',  link_menu_create() );
$smarty->assign( 'spool_freesize', spool_freesize() );

$smarty->display( 'logTable.html' );
?>

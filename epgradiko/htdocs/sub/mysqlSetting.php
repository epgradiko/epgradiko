<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once(INSTALL_PATH."/Smarty/Smarty.class.php");
include_once(INSTALL_PATH."/include/Settings.class.php");
include_once(INSTALL_PATH."/include/reclib.php");
include_once(INSTALL_PATH."/include/tableStruct.inc.php");

function check_database( $host, $user, $pass, $database ){
	global $settings;
	$return_str ='';
	try {
		$dbh	= @mysqli_connect( $host, $user, $pass, $database );
		if( mysqli_connect_errno() !== 0 ){
			$return_str = 'Error: データベースに接続できません。';
		}
	}catch( Exception $e ) {
		exit( 'Error: データベース接続失敗' );
	}
	return $return_str;
}

function cat_maker( $category_obj, $id, $category_ja, $category_en ){
	$result = $category_obj->fetch_array( 'id', $id );
	if( count($result) == 0 ){
		$wrt_set = array();
		$wrt_set['name_jp']	  = $category_ja;
		$wrt_set['name_en']	  = $category_en;
		$wrt_set['category_disc'] = md5( $category_ja . $category_en );
		$category_obj->force_update( 0, $wrt_set );
	}
}

function init_database( $prefix ){
	global $settings;
	$return_str ='';
	try {
		// Table Create
		$reserve = new DBRecord( RESERVE_TBL );
		$reserve->createTable( RESERVE_STRUCT );

		$program = new DBRecord( PROGRAM_TBL );
		$program->createTable( PROGRAM_STRUCT );

		$channel = new DBRecord( CHANNEL_TBL );
		$channel->createTable( CHANNEL_STRUCT );

		$category = new DBRecord( CATEGORY_TBL );
		$category->createTable( CATEGORY_STRUCT );

		$keyword = new DBRecord( KEYWORD_TBL );
		$keyword->createTable( KEYWORD_STRUCT );

		$log = new DBRecord( LOG_TBL );
		$log->createTable( LOG_STRUCT );

		$transcode = new DBRecord( TRANSCODE_TBL );
		$transcode->createTable( TRANSCODE_STRUCT );

		$transexpand = new DBRecord( TRANSEXPAND_TBL );
		$transexpand->createTable( TRANSEXPAND_STRUCT );
	}
	catch( Exception $e ) {
		$return_str = 'Error: テーブルの作成に失敗しました。データベースに権限がない等の理由が考えられます。';
	}
	if( !$return_str ){
		try {
			cat_maker( $category,  1, 'ニュース・報道', 'news' );
			cat_maker( $category,  2, 'スポーツ', 'sports' );
			cat_maker( $category,  3, '情報', 'information' );
			cat_maker( $category,  4, 'ドラマ', 'drama' );
			cat_maker( $category,  5, '音楽', 'music' );
			cat_maker( $category,  6, 'バラエティ', 'variety' );
			cat_maker( $category,  7, '映画', 'cinema' );
			cat_maker( $category,  8, 'アニメ・特撮', 'anime' );
			cat_maker( $category,  9, 'ドキュメンタリー・教養', 'documentary' );
			cat_maker( $category, 10, '演劇', 'stage' );
			cat_maker( $category, 11, '趣味・実用', 'hobby' );
			cat_maker( $category, 12, '福祉', 'welfare' );
			cat_maker( $category, 13, '予備1', 'etc1' );
			cat_maker( $category, 14, '予備2', 'etc2' );
			cat_maker( $category, 15, '拡張', 'expand' );
			cat_maker( $category, 16, 'その他', 'etc' );
		}
		catch( Exception $e ) {
			$return_str = 'Error: カテゴリーの初期設定に失敗しました。データベースに権限がない等の理由が考えられます。';
		}
	}
	return $return_str;
}

$settings = Settings::factory();
if( isset($_POST['host']) && isset($_POST['user']) && isset($_POST['pass']) && isset($_POST['database']) && isset($_POST['prefix']) ){
	$exit_str = '';
	if( $_POST['host'] === '' )		$exit_str .= 'Error: MySQLホスト未入力です。';
	if( $_POST['user'] === '' )		$exit_str .= 'Error: MySQL接続ユーザ名未入力です。';
	if( $_POST['database'] === '' )		$exit_str .= 'Error: 使用データベース名未入力です。';
	else $exit_str = check_database( $_POST['host'], $_POST['user'], $_POST['pass'], $_POST['database'] );
	if( !$exit_str ){
		$settings->db_host = $_POST['host'];
		$settings->db_user = $_POST['user'];
		$settings->db_pass = $_POST['pass'];
		$settings->db_name = $_POST['database'];
		$settings->tbl_prefix = $_POST['prefix'];
		$settings->save();
		$exit_str = init_database( $_POST['prefix'] );
		if( !$exit_str ){
			$exit_str = '設定しました';
		}
	}
	exit($exit_str);
}

$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";
$smarty->assign( "settings", $settings );
$smarty->assign( "return", 'mysql' );
$smarty->display("sub/mysqlSetting.html");
?>

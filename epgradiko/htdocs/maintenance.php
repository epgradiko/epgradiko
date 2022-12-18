<?PHp
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/menu_list.php' );

if( !INSTALL_PATH ){
	exit("conpig.phpが正しくインストールできていません。<br><br>"
		."インストールパスを確認して、再度インストールしてください。<br>");
}
$settings_dir = INSTALL_PATH."/settings";
$ss = @stat( $settings_dir );
$perm = sprintf('%o', ($ss['mode'] & 00777));
if( !in_array($perm, ['777', '775', '755']) ){
	exit($settings_dir."が書き込めません。書き込み許可を与えてください。<br>");
}

$settings = Settings::factory();

$return = "";
if( isset($_POST['return']) ) $return = $_POST['return'];
else if( isset($_GET['return']) ) $return = $_GET['return'];
switch( $return ){
	case 'mysql':
		$act = 0;
		break;
	case 'directory':
		$act = 1;
		break;
	case 'command':
		$act = 2;
		break;
	case 'tuner':
		$act = 3;
		break;
	case 'recording':
		$act = 4;
		break;
	case 'program':
		$act = 5;
		break;
	case 'view':
		$act = 6;
		break;
	case 'gr':
		$act = 7;
		break;
	case 'epg':
		$act = 8;
		break;
	case 'channel':
		$act = 9;
		break;
	case 'initial':
		$act = 10;
		break;
	default:
		if( isset($settings->initial_done) ){
			$act = 0;
		}else{
			$act = 10;
		}
}
$type = "";
if( isset($_POST['type']) ) $type = $_POST['type'];
else if( isset($_GET['type']) ) $type = $_GET['type'];

if( isset($_POST['initial_step']) ) $settings->initial_step = $_POST['initial_step'];
else if( isset($_GET['initial_step']) ) $settings->initial_step = $_GET['initial_step'];
else $settings->initial_step = 1;
$settings->save();

$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( 'settings', $settings );
$smarty->assign( 'menu_list', link_menu_create() );
$smarty->assign( 'act',       $act  );
$smarty->assign( 'type',      $type  );
$smarty->assign( 'sitetitle', 'メンテナンス' );
$smarty->assign( 'spool_freesize', spool_freesize() );
if( $settings->gr_tuners == 0 ) $smarty->assign( 'gr_disabled', TRUE );
if( !(count($GR_CHANNEL_MAP)||count($BS_CHANNEL_MAP)||count($CS_CHANNEL_MAP)||count($EX_CHANNEL_MAP)) ) $smarty->assign( 'channel_disabled', TRUE );
$smarty->display('maintenance.html');
?>

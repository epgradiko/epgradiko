<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once(INSTALL_PATH."/Smarty/Smarty.class.php");
include_once(INSTALL_PATH."/include/Settings.class.php");

function check_program( $program_length, $ch_set_width, $height_per_hour ){
	global $settings;
        $return_str ='';

        return $return_str;
}

$settings = Settings::factory();

$exit_str = '';
if( isset($_POST['program_length']) || isset($_POST['ch_set_width']) || isset($_POST['height_per_hour']) ){
        if( $_POST['program_length'] === '' )		$exit_str = 'Error: ページに表示する番組表の長さ未入力';
        else if( $_POST['ch_set_width'] === '' )	$exit_str = 'Error: 1局当たりの幅未入力';
        else if( $_POST['height_per_hour'] === '' )	$exit_str = 'Error: 1時間あたりの高さ未入力';
        else $exit_str = check_program( $_POST['program_length'], $_POST['ch_set_width'], $_POST['height_per_hour'] );
        if( !$exit_str ){
                $settings->program_length	= $_POST['program_length'];
                $settings->ch_set_width		= $_POST['ch_set_width'];
                $settings->height_per_hour	= $_POST['height_per_hour'];
                $settings->save();
                $exit_str = '設定しました。';
        }
        exit($exit_str);
}

$settings = Settings::factory();
$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";

$smarty->assign( "settings", $settings );

$smarty->display("maintenance/programSetting.html");
?>

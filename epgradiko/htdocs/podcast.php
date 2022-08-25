<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );

$protocol = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' || $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ? 'https://' : 'http://';
$host = $protocol. $_SERVER["HTTP_HOST"];

$settings = Settings::factory();

$wherestr = "WHERE complete='1'";
$site_title = "";

if ( isset($_GET['station'])) {
    $channel_id = rawurldecode($_GET['station']);
    $wherestr .= ' AND channel_id = '.$channel_id;
    $ch  = new DBRecord(CHANNEL_TBL,  "id", $channel_id );
    $site_title = $ch->name;
}
if ( isset( $_GET['category_id'] )){
    $category_id = rawurldecode($_GET['category_id']);
    $wherestr .= ' AND category_id = '.$category_id;
    $cat = new DBRecord(CATEGORY_TBL, 'id', $category_id);
    $site_title = $cat->name_jp;
}
if ( isset( $_GET['key_id']) && $_GET['key_id']) {
    $keyword_id = rawurldecode($_GET['key_id']);
    $wherestr .= ' AND autorec = '.$keyword_id;
    $keyword = new DBRecord(KEYWORD_TBL, 'id', $keyword_id);
    $site_title = $keyword->name;
}
if ( isset( $_GET['search'] )){
    $search = rawurldecode($_GET['search']);
    $wherestr .= ' AND (CONCAT(title,description) like "%'.$search.'%")';
    $site_title = $_GET['search'];
}
if ( isset( $_GET['site_title'] )){
    $site_title = rawurldecode($_GET['site_title']);
}
if ( isset( $_GET['episode_title'] )){
    $episode_title = rawurldecode($_GET['episode_title']);
}else{
    $episode_title = '$TITLE.($YEAR.$MONTH.$DAY.)';
}
if ( !$site_title ) {
    $site_title = '録画一覧';
}

$week_name_J = array("日", "月", "火", "水", "木", "金", "土");
$week_name = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");

$reserves = DBRecord::createRecords(RESERVE_TBL, $wherestr.' ORDER BY starttime');
$records = array();
foreach( $reserves as $reserve ) {
    $arr = array();
    $rep = array();
    $rep['$TITLE.'] = $reserve->title;
    list($rep['$YEAR.'], $rep['$MONTH.'], $rep['$DAY.'], $daynum, $rep['$HOUR.'], $rep['$MIN.'], $rep['$SEC.']) = 
	explode(' ', date("Y m d w h i s", strtotime($reserve->starttime)));
    $rep['$DOW.'] = $week_name[$daynum];
    $rep['$DOWJ.'] = $week_name_J[$daynum];
    $title = htmlspecialchars(strtr( $episode_title, $rep ));
    if( $title ){
    	$arr['title'] = $title;
    }else{
	$arr['title'] = $reserve->title;
    }
    $arr['author'] = ""; //$ch->name;
    $arr['subtitle'] = "";
    $arr['description'] = htmlspecialchars($reserve->description);
    $arr['thumb'] = $host."/get_file.php?thumb=".$reserve->id.".jpg";
    $trans_set = get_lightest_trans( $reserve->id );
    if( $trans_set[0] == 0 ) continue;
    $arr['url'] = $host."/recorded/trans_id/".$trans_set[0].".".$trans_set[2];
    $arr['type'] = get_content_type( $trans_set[2] );
    $arr['pubdate'] = gmdate('D, j M Y H:i:s', strtotime($reserve->starttime)).' GMT';
    $arr['guid'] = 'epgradiko'.$reserve->id; //$reserve->starttime;
    $arr['duration'] = strftime("%T",strtotime($reserve->endtime)-strtotime($reserve->starttime)-60*60*9);
    $arr['category'] = "";
    array_push( $records, $arr );
    $site_image_url = $arr['thumb'];
}
if( !isset($site_image_url) ) $site_image_url = '';
$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";
$smarty->assign("site_title", $site_title);
$smarty->assign("site_image_url", $site_image_url);
$smarty->assign( "records", $records );
$smarty->display("podcast.xml");
?>

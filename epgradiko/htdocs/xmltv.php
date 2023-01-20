<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/epg_const.php' );

$host_proto = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' || isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ? 'https://' : 'http://';
$host = $host_proto. $_SERVER["HTTP_HOST"];
$settings = Settings::factory();

$channel = array();
$program = array();
$i = 0;

foreach( $IPTV_CHANNEL_MAP as $channel_disc ) {
    $c_arr = array();

    $c = new DBRecord(CHANNEL_TBL, "channel_disc", $channel_disc);
    $c_arr['channel'] = htmlspecialchars($c->channel);
    $c_arr['sid'] = htmlspecialchars($c->sid);
    $c_arr['name'] = htmlspecialchars($c->name);
    $c_arr['channel_disc'] = htmlspecialchars($c->channel_disc);
    $c_arr['logo'] = htmlspecialchars($host.'/logoImage.php?channel_disc='.$c->channel_disc);
    $c_arr['GuideNumber'] = (string) ($i + 1);
    $wherestr = "WHERE channel_disc = '".$c_arr['channel_disc']."'".
		"AND endtime >= now() ".
		"ORDER BY starttime";
    $progs = DBRecord::createRecords(PROGRAM_TBL, $wherestr);
    if (!empty($progs)) {
        $program[$c_arr['channel_disc']] = array();
        foreach( $progs as $p ) {
            $p_arr = array();
            $cat = new DBRecord(CATEGORY_TBL, "id", $p->category_id );
//            $title = $p->pre_title.$p->title.$p->post_title;
            $title = strtr($p->pre_title, array_column(ProgramMark, 'char', 'name')).$p->title.strtr($p->post_title, array_column(ProgramMark, 'char', 'name'));

            $p_arr['starttime'] = date('YmdHis O', strtotime($p->starttime));
            $p_arr['endtime'] = date('YmdHis O', strtotime($p->endtime));
            $p_arr['title'] = htmlspecialchars(mb_convert_kana($title, 'as', 'UTF-8'));
            $p_arr['desc'] = htmlspecialchars(mb_convert_kana($p->description, 'as', 'UTF-8'));
            $p_arr['category_jp'] = htmlspecialchars($cat->name_jp);
            $p_arr['category_en'] = htmlspecialchars($cat->name_en);

            array_push( $program[$c_arr['channel_disc']], $p_arr );
	}
        array_push( $channel, $c_arr );
    }
    $i++;
}
$smarty = new Smarty();
$smarty->template_dir = INSTALL_PATH . "/templates/";
$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
$smarty->cache_dir = INSTALL_PATH . "/cache/";
$smarty->assign("channels", $channel);
$smarty->assign("programs", $program);
$smarty->display("xmltv.xml");
?>

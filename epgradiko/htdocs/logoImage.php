<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once(INSTALL_PATH . '/include/DBRecord.class.php' );
include_once(INSTALL_PATH . '/include/reclib.php' );
include_once(INSTALL_PATH . '/include/Settings.class.php' );


$settings = Settings::factory();
if( isset( $_GET['channel_disc'] ) ) $channel_disc = $_GET['channel_disc'];
else {
	header("HTTP/1.0 404 Not Found");
	echo '404 Not Found';
	exit(0);
}

//curlセッション初期化
$ch = curl_init();
//URLとオプションを指定する
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);				//curl_exec()の返り値を文字列で返す
if( DBRecord::countRecords( CHANNEL_TBL, 'WHERE channel_disc=\''.$channel_disc.'\'' ) ){
	$channel = new DBRecord( CHANNEL_TBL, 'channel_disc', $channel_disc );
	$url = $channel->logo;
	if( $url == 'mirakurun' ){
		if( $settings->mirakurun == 'uds' ){
			curl_setopt($ch, CURLOPT_UNIX_DOMAIN_PATH, $settings->mirakurun_uds);
			$mirakurun_server = 'http://mirakurun';
		}else{
			$mirakurun_server = 'http://'.$settings->mirakurun_address;
		}
		$url = $mirakurun_server.'/api/services/'.trim(sprintf('%5d%05d', (int)$channel->network_id, (int)$channel->sid)).'/logo';
		curl_setopt($ch, CURLOPT_URL, $url);					//取得するURL
	}else{
		curl_setopt($ch, CURLOPT_URL, $url);					//取得するURL
	}
	//URLの情報を取得
	$img_data = curl_exec($ch);
	if( $img_data ){
		$scheme='data:application/octet-stream;base64,';
		$image_size=getimagesize($scheme . base64_encode($img_data));
		if( isset($image_size['mime']) ){
			header('Content-Type: '.$image_size['mime']);
			echo $img_data;
			//セッション終了
			curl_close($ch);
			exit(0);
		}
	}
	curl_close($ch);
}
header("HTTP/1.0 404 Not Found");
echo '404 Not Found';
exit(0);

?>

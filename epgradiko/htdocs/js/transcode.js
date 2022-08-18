	// トランスコード解像度を変更
    // タグは'trans_url_*'にすること
	function setScreensize( id, select )
	{
		var targetID = document.getElementById('trans_url_'+id);
		if( targetID == null )
			return;
		var trans_link = targetID.getAttribute('href');
		var resuolt    = trans_link.split('&trans=');
		targetID.href  = resuolt[0] + '&trans=' + select;
	}

	// 一覧からの選択を元にトランスコード解像度を変更
	function chgScreensize( single, loop_limit, select )
	{
		if( single )
			setScreensize( 's', select );
		else
			for( i=0; i<loop_limit; i++ )
				setScreensize( i, select );
		var expire = new Date();
		expire.setTime( expire.getTime() + 365 * 24 * 3600 * 1000 );
		document.cookie = 'trans_size=' + select + '; expires=' + expire.toUTCString();		// cookie書き込み
	}

	// トランスコード解像度の初期値を設定
	function initScreensize( single, loop_limit, active_mode )
	{
		var width      = null;
		var cookieName = 'trans_size=';
		var allcookies = document.cookie;
		var position   = allcookies.indexOf( cookieName );
		var translp    = document.getElementById('trans_size');
		var len        = translp.options.length;

		if( position != -1 ){
			// cookie読み込み
			var startIndex = position + cookieName.length;
			var endIndex   = allcookies.indexOf( ';', startIndex );

			if( endIndex == -1 )
				endIndex = allcookies.length;
			width = parseInt( decodeURIComponent( allcookies.substring( startIndex, endIndex ) ) );
			if( width < len ){
				translp.options[width].selected = true;
				chgScreensize( single, loop_limit, width );
				return;
			}
		}else{
			if( !active_mode )
				return;
			// クライアント画面サイズを取得
			var wid = window.screen.width;
			var hei = window.screen.height;

			width   = wid>hei ? wid : hei;
		}

		// トランスコード解像度を変更
		var widstr  = String(width) + 'x';
		for( select=0; select<len; select++ ){
			if( translp.options[select].text.indexOf(widstr) == 0 ){
				translp.options[select].selected = true;
				chgScreensize( single, loop_limit, select );
				return;
			}
		}
		chgScreensize( single, loop_limit, width );
	}

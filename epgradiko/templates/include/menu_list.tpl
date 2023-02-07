{literal}
<style type="text/css">
/* ---------------------------- */
/* Menu				*/
/* ---------------------------- */
.circle{
  display: inline-block;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: darkblue;
  text-align:center;
  line-height: 20px;
}
.Menu {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  opacity: 0;
  z-index: 100;
}
.Menu.isShow {
  pointer-events: auto;
  opacity: 1;
}
.subMenu {
  position: fixed;
  top: 0;
  left: 0;
  width: 0%;
  height: 0%;
  pointer-events: none;
  opacity: 0;
  z-index: 100;
}
.subMenu.isShow {
  pointer-events: auto;
  opacity: 1;
}
.subMenu2 {
  position: fixed;
  top: 0;
  left: 0;
  width: 0%;
  height: 0%;
  pointer-events: none;
  opacity: 0;
  z-index: 100;
}
.subMenu2.isShow {
  pointer-events: auto;
  opacity: 1;
}
.diskUsage {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  opacity: 0;
  z-index: 100;
}
.diskUsage.isShow {
  pointer-events: auto;
  opacity: 1;
}
</style>
<script type="text/javascript">
	/* starクリックでメニューを開く  */
	function menu_open(){
		$('.Menu').addClass('isShow');
	}
	/* オーバーレイをタップでメニューを閉じる */
	function menu_close(){
		submenu_close();
		$('.Menu').removeClass('isShow');
	}
	/* diskUsageクリックで詳細を開く  */
	function diskUsage_open(){
		$.get('/sub/diskUsage.php',{a:1}, function(data){
		diskUsageTable.innerHTML = data;
		});
		$('.diskUsage').addClass('isShow');
	}
	/* オーバーレイをタップで詳細を閉じる */
	function diskUsage_close(){
		$('.diskUsage').removeClass('isShow');
	}
	/* メニュー項目マウスオーバーイベント */
	function menulist_mouseover( obj, mode, sub_menu = 0 ){
		if( mode == 'on'  ){
			obj.color = 'red';
			if( sub_menu == 1 ) submenu_open();
			if( sub_menu == 2 ) submenu2_open();
		}
		if( mode == 'off' ){
			obj.color = 'blue';
		}
	}
	/* サブメニュー項目マウスオーバーでメニューを開く  */
	function submenu_open(){
		$('.subMenu').addClass('isShow');
	}
	/* サブメニュー項目マウスオーバーでメニューを開く  */
	function submenu2_open(){
		$('.subMenu2').addClass('isShow');
	}
	/* オーバーレイをタップでメニューを閉じる */
	function submenu_close(){
		$('.subMenu').removeClass('isShow');
		$('.subMenu2').removeClass('isShow');
	}
</script>
{/literal}
<div class="Menu" onClick="submenu_close();menu_close();">
 <div class="MenuSection" style="text-align: left; position: fixed; top: 30px; width: 170px; background: #f0f0f0;">
  <div class="Menu-Title" style="font-size: 16px;">&nbsp;epgradikoメニュー</div>
  <div class="Menu-Text" style="font-size: 14px;">
{if count($menu_list[0]) > 0}
{if count($menu_list[0]) > 1}
   <a href="/selectProgram.php" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 1);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　番組表　　　　　▶　</b>
    </font>
   </a><br>
{else}
   <a href="{$menu_list[0][0].url}" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　{$menu_list[0][0].name}</b>
    </font>
   </a><br>
{/if}
{/if}
   <a href="/reservationTable.php" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　予約一覧　　　　　　</b>
    </font>
   </a><br>
   <a href="/revchartTable.php" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　予約遷移表　　　　　</b>
    </font>
   </a><br>
   <a href="/recordedTable.php" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　録画済一覧　　　　　</b>
    </font>
   </a><br>
   <a href="/searchProgram.php" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　番組検索　　　　　　</b>
    </font>
   </a><br>
   <a href="/keywordTable.php" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　キーワード管理　　　</b>
    </font>
   </a><br>
{if count($menu_list[1]) > 0}
{if count($menu_list[1]) > 1}
   <a href="/selectTimeshift.php" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 2);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　Timeshift 　　　▶　</b>
    </font>
   </a><br>
{else}
   <a href="{$menu_list[1][0].url}" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　{$menu_list[1][0].name}</b>
    </font>
   </a><br>
{/if}
{/if}
   <a href="/logViewer.php" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　動作ログ　　　　　　</b>
    </font>
   </a><br>
   <a href="/maintenance.php" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;　メンテナンス　　　　</b>
    </font>
   </a><br>
  </div>
 </div>
</div>
<div class="subMenu" onClick="submenu_close();">
{if count($menu_list[0]) > 1}
 <div class="subMenuSection" style="text-align: left; position: fixed; top: 53px; left: 145px; width: 150px; background: #e0e0e0;">
  <div class="subMenu-Text" style="font-size: 14px;">
{foreach from=$menu_list[0] item=record}
   <a href="{$record.url}" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;{$record.name}</b>
    </font>
   </a><br>
{/foreach}
  </div>
 </div>
{/if}
</div>

<div class="subMenu2" onClick="submenu_close();">
{if count($menu_list[1]) > 1}
 <div class="subMenu2Section" style="text-align: left; position: fixed; top: 183px; left: 145px; width: 150px; background: #e0e0e0;">
  <div class="subMenu2-Text" style="font-size: 14px;">
{foreach from=$menu_list[1] item=record}
   <a href="{$record.url}" style="text-decoration:none;line-height:22px;">
    <font color="blue" onMouseOver="menulist_mouseover(this,'on', 0);" onMouseOut="menulist_mouseover(this,'off', 0);">
     <b>&nbsp;{$record.name}</b>
    </font>
   </a><br>
{/foreach}
  </div>
 </div>
{/if}
</div>

<div class="diskUsage" onClick="diskUsage_close();">
	<div class="diskUsageSection" style="text-align: left; position: fixed; top: 30px; right: 0px; width: 640px; background: #f0f0f0;">
<div id="diskUsageTable">
</div>
</div>

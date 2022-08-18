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
</script>
{/literal}
<div class="Menu" onClick="menu_close();">
	<div class="MenuSection" style="text-align: left; position: fixed; top: 30px; width: 200px; background: #f0f0f0;">
		<div class="Menu-Title" style="font-size: 16px;">
　epgradikoメニュー
		</div>
		<div class="Menu-Text" style="font-size: 14px;">
{foreach from=$menu_list item=record}
<a href="{$record.url}" style="text-decoration:none;line-height:22px;"><font color="blue" onMouseOver="this.color='red'" onMouseOut="this.color='blue'"><b>　　{$record.name}</b></font></a><br>
{/foreach}
		</div>
	</div>
</div>

<div class="diskUsage" onClick="diskUsage_close();">
	<div class="diskUsageSection" style="text-align: left; position: fixed; top: 30px; right: 0px; width: 640px; background: #f0f0f0;">
<div id="diskUsageTable">
</div>
</div>

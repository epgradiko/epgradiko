<div id="top_nav" style="width:calc(100vw - 20px); display: inline-block; position: fixed; z-index: 2;">
<span style="float:right; margin:0 0.5em 0 0; list-style-type:none;">
 <input type="checkbox" id="menu_button" style="display: none;">
  <label for="menu_button" onClick="menu_open();" style="cursor: pointer; font-size: 20px; position: fixed; top: 0px; left: 0px;">
   <span class="circle" style="color: white;">★</span>
   <span><b>{$sitetitle}</b></span>
 </label>
</span>
</div>
<div id="naver" style="width:calc(100vw - 20px); display: inline-block; height: 25px; text-align: right; vertical-align: middle; z-index: 1;">
<form name="top_navi_form">
{if isset($transsize_set)}
 <label>📺
  <select name="trans_size" id="trans_size" title="視聴解像度" onChange="chgScreensize(0,{$transsize_set_cnt},this.selectedIndex)">
   {foreach from=$transsize_set name=lp item=size_set}
    <option value="{$smarty.foreach.lp.index}"{$size_set.selected}>{$size_set.name}</option>
   {/foreach}
  </select>
 </label>
{/if}
{if isset($spool_freesize)}<label onClick="diskUsage_open();" style="cursor: pointer;" titile="ディスク残量">💿{$spool_freesize}</label>{/if}
{if isset($transsize_set)}
 <script type="text/javascript">
 $(window).ready(function () {
        initScreensize(0,{$transsize_set_cnt},{$TRANS_SCRN_ADJUST});
 });
 </script>
{/if}
</form>
</div>

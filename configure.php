<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action='root';
if (!$zbp->CheckRights($action)) {$zbp->ShowError(6);die();}
if (!$zbp->CheckPlugin('dingstudio_sso')) {$zbp->ShowError(48);die();}

$blogtitle='单点登录客户端配置';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';

if(isset($_POST['Items'])){
    foreach($_POST['Items'] as $key=>$val){
       $zbp->Config('DCPSSO')->$key = $val;
    }
  	$zbp->SaveConfig('DCPSSO');
  	$zbp->ShowHint('good');
    Redirect('./configure.php');
}
?>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle;?></div>
  <div class="SubMenu">
	<a href="configure.php" ><span class="m-left m-now">基本设置</span></a>
	<a href="http://www.dingstudio.cn/"><span class="m-left">作者网站</span></a>
	<a href="http://954759397.qzone.qq.com/" target="_blank"><span class="m-left">作者QQ</span></a>
	<a href="http://help.dingstudio.cn/" target="_blank"><span class="m-right">联系我们</span></a>
  </div>
  <div id="divMain2">
<style type="text/css">
.xtips{color:#999;margin-left:15px}
div.xtips{line-height:1.5;padding:6px 0;}
textarea{font-size:12px;margin-bottom:3px;}
</style>
	<form id="form1" name="form1" method="post">	
    <table class="tb-set" width="100%">
        <tr height="50">
            <td colspan="2"><strong><?php echo $blogtitle;?></strong>友情提示：可以随时通过作者作品链接关注当前插件更新情况，以防错过更佳体验。</td>
        </tr>
        <tr height="50">
            <td width="180" align="right"><b>是否启用：</b></td>
            <td><input name="Items[available]" type="text" value="<?php echo $zbp->Config('DCPSSO')->available;?>" class="checkbox"><span class="xtips">开启全站登陆/注册接管</span></td>
        </tr>
        <tr height="50">
            <td align="right"><b>服务端域名：</b></td>
            <td><input name="Items[server]" type="text" value="<?php echo $zbp->Config('DCPSSO')->server;?>"><span class="xtips">填写您的服务端域名，如：passport.example.org</span></td>
        </tr>
        <tr height="50">
            <td align="right"><b>启用HTTPS：</b></td>
            <td><input name="Items[isHttps]" type="text" value="<?php echo $zbp->Config('DCPSSO')->isHttps;?>" class="checkbox"><span class="xtips">如认证服务端使用https通信，请开启此选项</span></td>
        </tr>
        <tr height="50">
            <td align="right"><b>服务路径：</b></td>
            <td><input type="text" value="<?php echo $zbp->Config('DCPSSO')->path;?>" name="Items[path]" size="15"><span class="xtips">此处填写您的服务端入口路径</span></td>
        </tr>
		<tr height="50">
            <td align="right"><b>Cookie共享名：</b></td>
            <td><input type="text" value="<?php echo $zbp->Config('DCPSSO')->cookieName;?>" name="Items[cookieName]" size="15"><span class="xtips">此处填写您的cookie共享名</span></td>
        </tr>
		<tr height="50">
            <td align="right"><b>自动注册：</b></td>
            <td><input name="Items[regUser]" type="text" value="<?php echo $zbp->Config('DCPSSO')->regUser;?>" class="checkbox"><span class="xtips">ON状态时新用户首次登陆将自动同步用户资料完成注册</span></td>
        </tr>
		<tr height="50">
            <td align="right"><b>注册级别：</b></td>
            <td><input type="text" value="<?php echo $zbp->Config('DCPSSO')->regLevel;?>" name="Items[regLevel]" size="15"><span class="xtips">自动注册时默认创建的账户级别，级别编号：3-作者，4-协作者，5-评论者。不建议自动注册为管理员级别！</span></td>
        </tr>
        <tr>
            <td height="80">&nbsp;</td>
            <td valign="top"><input type="submit" name="submit" value="保存设置"></td>
        </tr>
        <tr>
            <td colspan="2"><div class="xtips">重要事项：<br>本插件启用后，您的ZBlogPHP登陆/注册过程将被系统全权接管。如遇SSO服务端宕机，可能会导<br>致您的ZBlogPHP无法正常登陆。
            此时如需恢复内建的账号登陆体系，请登陆您的Web服务器手动<br>移除本插件的所有文件即可恢复。
            成品应用仅为满足大众化需求，如需个性化欢迎 <a href="http://wpa.qq.com/msgrd?v=3&uin=954759397&site=qq&menu=yes" target="_blank">联系作者</a> 定制。</div></td>
        </tr>
      </table>
      <p><br></p>
    </form>
  </div>
  </div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
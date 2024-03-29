<?php
#注册插件
RegisterPlugin("dingstudio_sso","ActivePlugin_dingstudio_sso");

/**
 * 挂接HOOK函数
 */
function ActivePlugin_dingstudio_sso() {
	Add_Filter_Plugin('Filter_Plugin_Autoload', 'sso_recheck');
	Add_Filter_Plugin('Filter_Plugin_Cmd_Begin', 'custom_login_process');
	Add_Filter_Plugin('Filter_Plugin_Login_Header', 'login_page_interceptor');
	Add_Filter_Plugin('Filter_Plugin_Member_Edit_Response', 'custom_ucenter');
}
/**
 * 插件安装时创建配置信息存储
 */
function InstallPlugin_dingstudio_sso() {
	global $zbp;
	if (!$zbp->Config('DCPSSO')->HasKey('version')) {
		$zbp->Config('DCPSSO')->version = '1.0';
		$zbp->Config('DCPSSO')->available = '0';
		$zbp->Config('DCPSSO')->server = 'passport.dingstudio.cn';
		$zbp->Config('DCPSSO')->isHttps = '1';
		$zbp->Config('DCPSSO')->path = '/sso/';
		$zbp->Config('DCPSSO')->regUser = '1';
		$zbp->Config('DCPSSO')->cookieName = 'dingstudio_sso';
		$zbp->Config('DCPSSO')->regLevel = 5;
		$zbp->SaveConfig('DCPSSO');
	}
	$zbp->ShowHint('good','ZBP系统接口挂接成功！插件启用完成。');
}
/**
 * 插件卸载善后
 */
function UninstallPlugin_dingstudio_sso() {
	global $zbp;
	$zbp->DelConfig('DCPSSO');
	$zbp->ShowHint('good','ZBP系统接口挂接取消！插件停用完成。');
}
/**
 * SSO全局状态检查
 */
function sso_recheck() {
	global $zbp;
	if ($zbp->Config('DCPSSO')->available != '1') {
		return true;
	}
	else if (!isset($_COOKIE[$zbp->Config('DCPSSO')->cookieName])) {
		if ($zbp->CheckRights('admin')) {
			Logout();
			$zbp->ShowError('系统检测到您已从统一身份认证平台退出或本次会话已过期，请重新登录。', __FILE__, __LINE__);
		}
	}
}
/**
 * cmd.php请求过滤器
 */
function custom_login_process() {
	global $zbp;
	if ($zbp->Config('DCPSSO')->available != '1') {
		return true;
	}
	else {
		global $action;
		switch ($action) {
			case 'login':
			ssologin();
			break;
			case 'logout':
			ssologout();
			break;
			default:
			return true;
			break;
		}
	}
}
/**
 * login.php请求过滤器
 */
function login_page_interceptor() {
	global $zbp;
	if ($zbp->Config('DCPSSO')->available != '1') {
		return true;
	}
	else {
		echo '<script>alert("已启用统一身份验证，将为您跳转至单点登录页面。");location.href = bloghost + "zb_system/cmd.php?act=login";</script>';
	}
}
/**
 * 用户中心会话劫持过程
 * @intro 防止在启用单点登录时访问内建用户中心
 */
function custom_ucenter() {
	global $zbp;
	if ($zbp->Config('DCPSSO')->available != '1') {
		return true;
	}
	$isHttps = $zbp->Config('DCPSSO')->isHttps;
	if ($isHttps == '1') {
		$protocol = 'https://';
	}
	else {
		$protocol = 'http://';
	}
	$server = $zbp->Config('DCPSSO')->server;
	$authpath = $zbp->Config('DCPSSO')->path;
	Redirect($protocol.$server.$authpath.'ucenter?appname=zblogphp&referer='.urlencode($zbp->host.$zbp->currenturl));
}
/**
 * 单点登录过程
 * @intro 实现cmd.php?act=login时的请求拦截
 */
function ssologin() {
	global $zbp;
	$isHttps = $zbp->Config('DCPSSO')->isHttps;
	if ($isHttps == '1') {
		$protocol = 'https://';
	}
	else {
		$protocol = 'http://';
	}
	$server = $zbp->Config('DCPSSO')->server;
	$authpath = $zbp->Config('DCPSSO')->path;
	if (isset($_COOKIE[$zbp->Config('DCPSSO')->cookieName])) {
		foreach ($zbp->members as $key => $m) {
			if ($m->Name == $_COOKIE[$zbp->Config('DCPSSO')->cookieName]) {
				$m = $zbp->members[$m->ID];
				if (function_exists('SetLoginCookie')) {
					SetLoginCookie($m, time() + 3600);
					Redirect('admin/?act=admin');
					die();
				}
				$un = $m->Name;
				if ($zbp->version > 131221) {
					$ps = md5($m->Password . $zbp->guid);
				}
				else {
					$ps = md5($m->Password . $zbp->path);
				}
				setcookie("username", $un, 0, $zbp->cookiespath);
				setcookie("password", $ps, 0, $zbp->cookiespath);
				Redirect('admin/?act=admin');
				die();
			}
		}
		if ($zbp->Config('DCPSSO')->regUser != '1') {
			$zbp->ShowError('该用户：'.$_COOKIE[$zbp->Config('DCPSSO')->cookieName].'，首次登录本系统。但由于管理员尚未开启自动注册机制，所以本次登录被迫取消！', __FILE__, __LINE__);
		}
		$member = new Member;
		$member->Level = $zbp->Config('DCPSSO')->regLevel;
		$member->Name = $_COOKIE[$zbp->Config('DCPSSO')->cookieName];
		$member->Password = Member::GetPassWordByGuid(md5($_COOKIE[$zbp->Config('DCPSSO')->cookieName].'@sso'), 'sso');
		$member->PostTime = time();
		$member->IP = GetGuestIP();
		$member->Save();
		$keyvalue = array();
		$keyvalue['reg_AuthorID'] = $member->ID;
		$sql = $zbp->db->sql->Update($RegPage_Table, $keyvalue, array(array('=', 'reg_ID', $reg->ID)));
		$zbp->db->Update($sql);
		Redirect('cmd.php?act=login');
		die();
	}
	Redirect($protocol.$server.$authpath.'classicLoginDispatcher?mod=crossdomain&returnUrl='.urlencode($zbp->host.$zbp->currenturl));
}
/**
 * 单点登出过程
 * @intro 实现cmd.php?act=logout时的请求拦截
 */
function ssologout() {
	global $zbp;
	$isHttps = $zbp->Config('DCPSSO')->isHttps;
	if ($isHttps == '1') {
		$protocol = 'https://';
	}
	else {
		$protocol = 'http://';
	}
	$server = $zbp->Config('DCPSSO')->server;
	$authpath = $zbp->Config('DCPSSO')->path;
	Logout();
	setcookie($zbp->Config('DCPSSO')->cookieName, null, time() - 3600, '/', '', false, true);
	unset($_COOKIE[$zbp->Config('DCPSSO')->cookieName]);
	Redirect($protocol.$server.$authpath.'logout?action=dologout&url='.urlencode($zbp->host));
}

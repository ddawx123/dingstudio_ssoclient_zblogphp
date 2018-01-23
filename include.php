<?php
#注册插件
RegisterPlugin("dingstudio_sso","ActivePlugin_dingstudio_sso");

function ActivePlugin_dingstudio_sso() {
	Add_Filter_Plugin('Filter_Plugin_Cmd_Begin', 'custom_login_process');
	Add_Filter_Plugin('Filter_Plugin_Member_Edit_Response', 'custom_ucenter');
}
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
function UninstallPlugin_dingstudio_sso() {
	global $zbp;
	$zbp->DelConfig('DCPSSO');
	$zbp->ShowHint('good','ZBP系统接口挂接取消！插件停用完成。');
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
	Redirect($protocol.$server.$authpath.'usercenter.php?appname=zblogphp&referer='.urlencode($zbp->host.$zbp->currenturl));
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
	if (isset($_COOKIE[$zbp->Config('DCPSSO')->cookieName]) && isset($_COOKIE['dingstudio_ssotoken'])) {
		foreach ($zbp->members as $key => $m) {
			if ($m->Name == $_COOKIE[$zbp->Config('DCPSSO')->cookieName]) {
				$m = $zbp->members[$m->ID];
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
	Redirect($protocol.$server.$authpath.'login.php?mod=caslogin&returnUrl='.urlencode($zbp->host.$zbp->currenturl));
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
	Redirect($protocol.$server.$authpath.'login.php?action=dologout&url='.urlencode($zbp->host));
}
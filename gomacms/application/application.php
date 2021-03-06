<?php
/**
  *@package goma cms
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 09.12.2012
  * $Version 1.1.4
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

/**
 * here you can define the seperator for the dynamic title in the <title></title>-Tag
*/
define('TITLE_SEPERATOR',' - ');

SQL::Init();

loadFramework();

if(isset($_SESSION["welcome_screen"]) || (!file_exists(APP_FOLDER . "application/.WELCOME_RUN") && !file_exists(APP_FOLDER . "application/WELCOME_RUN.php") && !isset($_SESSION["dev_without_perms"]) && DataObject::count("user") == 0)) {
	$request = new Request(
						(isset($_SERVER['X-HTTP-Method-Override'])) ? $_SERVER['X-HTTP-Method-Override'] : $_SERVER['REQUEST_METHOD'],
						URL
						);
	$welcomeController = new welcomeController();
	return Core::serve($welcomeController->handleRequest($request));
}

if(PROFILE) Profiler::mark("settings");

settingsController::preInit();

if(PROFILE) Profiler::unmark("settings");

Resources::$gzip = settingsController::get("gzip");
RegisterExtension::$enabled = settingsController::get("register_enabled");
RegisterExtension::$validateMail = settingsController::get("register_email");
RegisterExtension::$registerCode = settingsController::get("register");
Core::setCMSVar("ptitle", settingsController::get("titel"));
Core::setCMSVar("title", settingsController::get("titel"));
Core::setTheme(settingsController::Get("stpl"));
Core::setHeader("keywords", settingsController::Get("meta_keywords"));
Core::setHeader("description", settingsController::Get("meta_description"));
Core::setHeader("robots", "index,follow");
Core::setHeader("copyright", date("Y", NOW) . " - " . settingsController::get("titel"));

date_default_timezone_set(Core::GetCMSVar("TIMEZONE"));

if(PROFILE) Profiler::unmark("settings");

$core = new Core();
$core->render(URL);
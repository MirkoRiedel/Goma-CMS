<?php
/**
  *@package goma
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 16.04.2012
  * $Version 2.0.1
*/   

 
defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)


$pm_lang = array(
	"from"              => "Von",
	"inbox"             => "Posteingang",
	"sent"           	=> "Die Nachricht wurde erfolgreich gesendet!",
	"reply"           	=> "Antworten",
	"add_sig"       	=> "Signatur anh&auml;ngen",
	"picture"           => "Bild",
	"url"               => "Link",
	"subject"           => "Betreff",
	"send"              => "Absenden",
	"confirm_delete"    => "Wollen sie die Nachricht wirklich löschen?",
	"read"              => "Nachricht lesen",
	"no_subject"        => "Kein Betreff",
	"to"				=> "An",
	"delete"			=> "Nachricht löschen",
	"no_messages"		=> "Sie haben keine Nachrichten.",
	"earlier"			=> "Vorherige Nachrichten",
	"later"				=> "Neuere Nachrichten",
	"compose"			=> "Nachricht schreiben"
);
foreach($pm_lang as $key => $value)
{
	$GLOBALS['lang']['pm_'.$key] = $value;
}

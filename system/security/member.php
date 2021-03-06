<?php
/**
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 21.03.2012
  * $Version 2.4.8
*/   

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class userController extends Controller
{
	/**
	 * gets userdata
	 *@name getuserdata
	 *@param id - userid
	 *@return array
	*/
	public function getuserdata($id)
	{
		return DataObject::get_one($this, array('id' => $id));
	}
	
	/**
	 * saves the user-pwd
	 *@access public
	 *@name savepwd
	*/
	public function pwdsave($result)
	{
		AddContent::add('<div class="success">'.lang("edit_password_ok", "The password were successfully changed!").'</div>');
		DataObject::update("user", array("password" => Hash::getHashFromDefaultFunction($result["password"])), array('recordid' => $result["id"]));
		$this->redirectback();
	}
		
}


class User extends DataObject implements HistoryData, PermProvider, Notifier
{
		/**
		 * the name of this dataobject
		 *
		 *@name name
		 *@access public
		*/
		public static $cname = '{$_lang_user}';
		
		/**
		 * the database fields of a user
		 *
		 *@name db
		 *@access public
		*/
		static $db = array(	'nickname'		=> 'varchar(200)',
							'name'			=> 'varchar(200)',
							'email'			=> 'varchar(200)',
							'password'		=> 'varchar(1000)',
							'signatur'		=> 'text',
							'status'		=> 'int(2)',
							'phpsess'		=> 'varchar(200)',
							"code"			=> "varchar(200)",
							"timezone"		=> "timezone",
							"custom_lang"	=> "varchar(10)");
		
		
		/**
		 * we add an index to username and password, because of logins
		 *
		 *@name index
		 *@access public
		*/
		static $index = array(
			"login"	=> array("type"	=> "INDEX", "fields" => 'nickname, password')
		);
		
		/**
		 * fields which are searchable
		 *
		 *@name search_fields
		 *@access public
		*/
		static $search_fields = array(
			"nickname", "name", "email", "signatur"
		);
		
		/**
		 * the table is users not user
		 *
		 *@name table
		 *@access public
		*/
		static $table = "users";
		
		/**
		 * use versions here
		 *
		 *@name versions
		*/
		static $versions = true;
		
		/**
		 * every user has one group and an avatar-picture, which is reflected in this relation
		 *
		 *@name has_one
		 *@access public
		*/
		static $has_one = array("avatar" => "Uploads"); 
		
		/**
		 * every user has additional groups
		 *
		 *@name many_many
		 *@access public
		*/
		static $many_many = array("groups" => "group");
		
		/**
		 * sort by name
		*/
		static $default_sort = array("name", "ASC");
		
		/**
		 * users are activated by default
		 *
		 *@name defaults
		 *@access public
		*/
		static $default = array(
				'status'	=> '1'
		);
		
		public $insertRights = 1;
		
		/**
		 * gets all groups if a object
		 *
		 *@name getAllGroups
		 *@access public 
		*/
		public function getAllGroups() {
			$groups = $this->groups();
			$groups->add($this->group());
			return $groups;
		}
		
		/**
		 * returns true if you can write
		 *
		 *@name canWrite
		 *@access public
		*/
		
		public function canWrite($data = null)
		{
			if($data["id"] == member::$id)
				return true;
			
			return Permission::check("USERS_MANAGE");
		}
		
		/**
		 * returns true if you can write
		 *
		 *@name canDelete
		 *@access public
		*/
		
		public function canDelete($data = null)
		{
			return Permission::check("USERS_MANAGE");
		}
		
		/**
		 * returns true if the current user can insert a record
		 *
		 *@name canInsert
		 *@access public
		*/
		public function canInsert($data = null)
		{
			return true;
		}
		
		/**
		 * forms
		*/
		public function getForm(&$form)
		{
				// add default tab
				$form->add(new TabSet("tabs", array(
					$general = new Tab("general",array(
							new TextField("nickname", $GLOBALS["lang"]["username"]),
							new TextField("name",$GLOBALS["lang"]["name"]),
							$mail = new TextField("email", $GLOBALS["lang"]["email"]),
							new PasswordField("password", $GLOBALS["lang"]["password"], ""),
							new PasswordField("repeat", $GLOBALS["lang"]["repeat"], ""),
							new langSelect("custom_lang", lang("lang"), Core::$lang)
						),$GLOBALS["lang"]["general"])
				)));
				
				$mail->info = lang("email_correct_info");
				
				if(Permission::check("USERS_MANAGE"))
				{
					$form->add(new Manymanydropdown("groups", lang("groups", "Groups"), "name"),0, "general");
				}
				
				if(!member::login())
				{
						$code = RegisterExtension::$registerCode;
						if($code != "")
						{
								$general->add(new TextField("code", lang("register_code", "Code")));
								$form->addValidator(new FormValidator(array($this, 'validatecode')), "validatecode");
						}
				}
				if(Permission::check("USERS_MANAGE"))
				{
					$form->addValidator(new RequiredFields(array("nickname", "password", "groups", "repeat", "email")), "required_users");
				} else {
					$form->addValidator(new RequiredFields(array("nickname", "password", "repeat", "email")), "required_users");
				}
				$form->addValidator(new FormValidator(array($this, '_validateuser')), "validate_user");
				
				$form->addAction(new CancelButton("cancel", lang("cancel")));
				$form->addAction(new FormAction("submit", lang("save"), "publish", array("green")));
		}
		
		/**
		 * gets the edit-form for profile-edit or admin-edit
		 *
		 *@name getEditForm
		 *@access public
		 *@param object - form
		*/
		public function getEditForm(&$form)
		{
				
				unset($form->result["password"]);
				
				$form->add(new TabSet("tabs", array(
					new Tab("general",array(
							new TextField("nickname", lang("username")),
							new TextField("name",  lang("name", "name")),
							new TextField("email", lang("email", "email")),
							new ManyManyDropdown("groups", lang("groups", "Groups"), "name"),
							$this->doObject("timezone")->formfield(lang("timezone")),
							new LangSelect("custom_lang", lang("lang")),
							// password management in external window
							new ExternalForm("passwort", lang("password", "password"), lang("edit_password", "change password"), '**********', array($this, "pwdform")),
							new ImageUpload("avatar", lang("pic", "image")),
							new TextArea("signatur", lang("signatur", "signature"), null, "100px")
							
						), lang("general"))			
				)));
				
				$form->email->info = lang("email_correct_info");
				$form->nickname->disable();
				$form->addValidator(new RequiredFields(array("nickname", "groupid", "email")), "requirefields");
				
				
				// group selection for admin
				if($this["id"] != member::$id && Permission::check("USERS_MANAGE"))
				{
					
					// if a user is not activated by mail, admin should have a option to activate him manually
					if($this->status == 0) {
						$status = new radiobutton("status", lang("status", "Status"), array(0 => lang("not_unlocked", "Not activated yet"),1 => lang("not_locked", "Unlocked"), 2 => lang("locked", "Locked")));
					} else {
						$status = new radiobutton("status", lang("status", "Status"), array(1 => lang("not_locked", "Unlocked"), 2 => lang("locked", "Locked")));
					}
					
					$form->add(new Tab("admin", array(
						$status
					), $GLOBALS["lang"]["administration"]),0,"tabs");
				} else {
					$form->remove("groups");
				}
				
				// generate actions
				if(right("USERS_MANAGE") && defined("IS_BACKEND"))
				{
						$form->addAction(new HTMLAction("delete", '<a href="'.ROOT_PATH.'admin/usergroup/model/user/'.$this->id.'/delete'.URLEND.'?redirect='.urlencode(ROOT_PATH . "admin/usergroup/").'" rel="ajaxfy" class="button red">'.lang("delete", "Delete").'</a>'));
				}
				
				$form->addAction(new CancelButton("cancel", lang("cancel")));
				$form->addAction(new FormAction("submit", lang("save"), "publish", array("green")));
		}
		
		/**
		 * form for password-edit
		 *
		 *@name pwdform
		*/
		public function pwdform($id = null)
		{
				if(!isset($id))
					$id = $this->id;
				
				if(Permission::check("USERS_MANAGE") && $id != member::$id) {
					$pwdform = new Form($this->controller(), "editpwd", array(
						new HiddenField("id", $id),
						new PasswordField("password",$GLOBALS["lang"]["new_password"]),
						new PasswordField("repeat", $GLOBALS["lang"]["repeat"])
					));
					$pwdform->addValidator(new FormValidator(array($this, "admin_validatepwd")), "pwdvalidator");
					$pwdform->addAction(new FormAction("submit", lang("save", "save"), "pwdsave"));
				} else {
					$pwdform = new Form($this->controller(), "editpwd", array(
						new HiddenField("id", $id),
						new PasswordField("oldpwd", $GLOBALS["lang"]["old_password"]),
						new PasswordField("password",$GLOBALS["lang"]["new_password"]),
						new PasswordField("repeat", $GLOBALS["lang"]["repeat"])
					));
					$pwdform->addValidator(new FormValidator(array($this, "validatepwd")), "pwdvalidator");
					$pwdform->addAction(new FormAction("submit", lang("save", "save"),"pwdsave"));
				}
				return $pwdform;
		}
		
		/**
		 * validates an new user
		 *
		 *@name validateuser
		 *@access public
		*/
		public function _validateuser($obj)
		{
				if($obj->form->result["password"] == $obj->form->result["repeat"])
				{
						// check if username is unique
						if(DataObject::count("user", array("nickname" => $obj->form->result["nickname"])) > 0)
						{
								return lang("register_username_bad", "The username is already taken.");
						}
						return true;
				} else
				{
						return lang("passwords_not_match");
				}
		}
		
		/**
		 * nickname is always lowercase
		 *
		 *@name onbeforewrite
		*/
		public function onBeforeWrite() {
			parent::onBeforeWrite();
			
			$this->nickname = strtolower($this->nickname);
		}
		
		/**
		 * sets the password with md5
		 *
		 *@name setpassword
		 *@access public
		*/
		public function setpassword($value)
		{
				$this->setField("password", Hash::getHashFromDefaultFunction($value));
				return true;
		}
		
		/**
		 * valdiates code
		 *
		 *@name validatecode
		 *@access public
		 *@param string - value
		 *@return true|string
		*/
		public function validateCode($value)
		{
				if(is_array($value)) {
					return true;
				}
				if(!defined("IS_BACKEND")) {
						$code = RegisterExtension::$registerCode;;
						if($code != "")
								return ($code == $value) ? true : lang("register_code_wrong", "The Code was wrong!");
						else
								return true;
				}
				return true;
		}
		
		/**
		 * password should not be visible
		 *
		 *@name getpassword
		 *@access public
		*/
		public function getpassword() {
				return "";
		}
		
		/**
		 * validates the pwd
		 *
		 *@name validatepwd
		 *@access public		 
		*/
		public function validatepwd($obj) {
			if(isset($obj->form->result["oldpwd"]))
			{
				$data = DataObject::get_one("user", array("id" => $obj->form->result["id"]));
				if($data) {
					// export data
					$data = $data->ToArray();
					$pwd = $data["password"];
					
					// check old password
					if(Hash::checkHashMatches($obj->form->result["oldpwd"], $pwd))
					{
						if(isset($obj->form->result["password"], $obj->form->result["repeat"]) && $obj->form->result["password"] != "")
						{
							if($obj->form->result["password"] == $obj->form->result["repeat"])
							{
								return true;
							} else
							{
								return lang("passwords_not_match");
							}
						} else {
							return lang("passwords_not_match");
						}
					} else {
						return lang("password_wrong");
					}
				} else {
					return lang("error");
				}
			} else
			{
				return lang("password_wrong");
			}
		}
		
		/**
		 * validates the pwd if you're an admin and set the password
		 *
		 *@name admin_validatepwd
		 *@access public		 
		*/
		public function admin_validatepwd($obj) {
			if(isset($obj->form->result["password"], $obj->form->result["repeat"]) && $obj->form->result["password"] != "")
			{
				if($obj->form->result["password"] == $obj->form->result["repeat"])
				{
					return true;
				} else
				{
					return lang("passwords_not_match");
				}
			} else {
				return lang("passwords_not_match");
			}
		}
		
		/**
		 * returns the title of the person
		 *
		 *@name title
		 *@access public
		*/
		public function title() {
			if($this->fieldGet("name")) {
				return $this->fieldGet("name");
			}
			
			return $this->nickname;
		}
		
		/**
		 * returns the representation of this record
		 *
		 *@name generateResprensentation
		 *@access public
		*/
		public function generateRepresentation($link = false) {
			$title = $this->title;
			
			$title = $this->image()->setSize(20, 20) . " " . $title;
			
			if($link)
				$title = '<a href="member/'.$this->id.'" target="_blank">' . $title . '</a>';
			
			return $title;
		}
		
		/**
		 * performs a login
		 *
		 *@name performLogin
		 *@access public
		*/
		public function performLogin() {
			if($this->custom_lang != Core::$lang && $this->custom_lang) {
				i18n::Init($this->custom_lang);
			}
			
			// now write login to database
			$this->code = randomString(20);
			
			$this->callExtending("performLogin");
			
			$this->write(false, true);
		}
		
		/**
		 * performs a logout
		 *
		 *@name performLogout
		 *@access public
		*/
		public function performLogout() {
			$this->callExtending("performLogout");
			
			if($this->wasChanged()) {
				$this->write(false, true);
			}
		}
		
		/**
		 * returns text what to show about the event
		 *
		 *@name generateHistoryData
		 *@access public
		*/
		public static function generateHistoryData($record) {
			if(!$record->newversion()) {
				return false;
			}
			
			$relevant = true;
			if(!$record->autor)
				$relevant = false;
			
			switch($record->action) {
				case "update":
				case "publish":
					if($record->oldversion() && $record->newversion() && $record->oldversion()->phpsess != $record->newversion()->phpsess) {
						$icon = "images/icons/fatcow16/user_go.png";
						$lang = lang("h_user_login");
						$lang = str_replace('$euser', '<a href="member/'.$record->record()->ID . URLEND .'">' . convert::raw2text($record->record()->title) . '</a>', $lang);
						$relevant = false;
					} else {
						if($record->autorid == $record->newversion()->id) {
							$lang = lang("h_profile_update", '$user updated the own profile');
						} else {
							$lang = lang("h_user_update", '$user updated the user <a href="$userUrl">$euser</a>');
						}
						$icon = "images/icons/fatcow16/user_edit.png";
					}
				break;
				case "insert":
					$lang = lang("h_user_create", '$user created the user <a href="$userUrl">$euser</a>');
					$icon = "images/icons/fatcow16/user_add.png";
				break;
				case "remove":
					$lang = lang("h_user_remove", '$user removed the user $euser');
					$icon = "images/icons/fatcow16/user_delete.png";
				break;
				default:
					$lang = "Unknowen event " . $record->action;
					$icon = "images/icons/fatcow16/user_edit.png";
			}
			$lang = str_replace('$userUrl', "member/" . $record->newversion()->id . URLEND, $lang);
			$lang = str_replace('$euser', convert::Raw2text($record->newversion()->title), $lang);
			
			return array("icon" => $icon, "text" => $lang, "relevant" => $relevant);
		}
		
		/**
		 * returns a comma-seperated list of all groups
		 *
		 *@name getGroupList
		 *@access public
		*/
		public function getGroupList() {
			$str = "";
			$i = 0;
			foreach($this->groups() as $group) {
				if($i == 0) {
					$i++;
				} else {
					$str .= ", ";
				}
				$str .= Convert::raw2text($group->name);
			}
			return $str;
		}
		
		/**
		 * provides some permissions
		 *
		 *@name providePerms
		 *@access public
		*/
		public function providePerms() {
			return array(
				"USERS_MANAGE"	=> array(
					"title"		=> '{$_lang_administration}: {$_lang_user}',
					"default"	=> array(
						"type"	 	=> "admins",
						"inherit"	=> "superadmin"
					),
					"category"	=> "superadmin"
				)
			);
		}
		
		/**
		 * gets the avatar
		 *
		 *@name getImage
		 *@access public
		*/
		public function getImage() {
			if($this->avatar) {
				if((ClassInfo::exists("gravatarimagehandler") && $this->avatar->filename == "no_avatar.png" && $this->avatar->class != "gravatarimagehandler") || $this->avatar->class == "gravatarimagehandler") {
					$this->avatarid = 0;
					$this->write(false, true, 2, false, false);
					return new GravatarImageHandler(array("email" => $this->email));
				}
				return $this->avatar;
			} else {
				return new GravatarImageHandler(array("email" => $this->email));
			}
		}
		
	/**
	 * returns information about notification-settings of this class
	 * these are:
	 * - title
	 * - icon
	 * this API may extended with notification settings later
	 * 
	 *@name NotifySettings
	 *@access public
	*/
	public static function NotifySettings() {
		return array("title" => lang("user"), "icon" => "images/icons/fatcow16/user@2x.png");
	}
}

/**
 * this class reflects some data of the current logged in user
*/
class Member extends Object {
	/**
	 * id of the current logged in user
	 *
	 *@name id
	 *@access public
	*/
	public static $id;
	
	/**
	 * nickname of the user logged in
	 *
	 *@name nickname
	 *@access public
	*/
	public static $nickname;
	
	/**
	 * this var reflects the status of the highest group in which the user is
	 *
	 *@name groupType
	 *@access public
	 *@var enum(0,1,2)
	*/
	public static $groupType = 0;
	
	/**
	 * set of groups of this user
	 *
	 *@name groups
	 *@access public
	*/
	public static $groups = array();
	
	/**
	 * default-admin
	 *
	 *@name default_admin
	 *@access public
	*/
	public static $default_admin;
	
	/**
	 * object of logged in user
	 *
	 *@name loggedIn
	 *@access public
	*/
	public static $loggedIn;
	
	/**
	 * checks for default admin and basic groups
	 *
	 *@name checkDefaults
	 *@access public
	*/
	public static function checkDefaults() {
		
		$cacher = new Cacher("groups-checkDefaults");
		if($cacher->checkValid()) {
		
		} else {
			if(DataObject::count("group", array("type" => 2)) == 0) {
				$group = new Group();
				$group->name = lang("admins", "admin");
				$group->type = 2;
				$group->write(true, true, 2, false, false);
			}
			
			if(DataObject::count("group", array("type" => 1)) == 0) {
				$group = new Group();
				$group->name = lang("user", "users");
				$group->type = 1;
				$group->write(true, true, 2, false, false);
			}
			
			if(isset(self::$default_admin) && DataObject::count("user") == 0) {
				$user = new User();
				$user->nickname = self::$default_admin["nickname"];
				$user->password = self::$default_admin["password"];
				$user->write(true, true);
				$user->groups()->add(DataObject::get_one("group", array("type" => 2)));
				$user->groups()->write(false, true);
			}
			
			$cacher->write(true, 3600);
		}
		
	}
	
	/**
	 * checks the login and writes the types
	 *
	 *@name checkLogin
	 *@access public
	*/
	public static function Init() {
		if(PROFILE) Profiler::mark("member::Init");
		if(isset(self::$id)) {
			return true;
		}
		
		self::checkDefaults();
		
		if(isset($_SESSION["g_userlogin"])) {
			if($data = DataObject::get_one("user", array("id" => $_SESSION["g_userlogin"]))) {
				$currsess = session_id();
				
				if($data['phpsess'] != $currsess)
				{
					self::doLogout();
					if(PROFILE) Profiler::unmark("member::Init");
					return false;
				}
				
				if($data["timezone"]) {
					Core::setCMSVar("TIMEZONE", $data["timezone"]);
					date_default_timezone_set(Core::getCMSVar("TIMEZONE"));
				}
				
				self::$id = $data->id;
				self::$nickname = $data->nickname;
				
				self::$groups = $data->groups(null, "type DESC");
				
				// if no group is set, set default group user
				if(self::$groups->forceData()->Count() == 0) {
					$group = DataObject::get_one("group", array("type" => 1));
					if(!$group) {
						$group = new Group(array("name" => lang("user"), "type" => 1));
						$group->write(true, true, 2, false, false);
					}
					
					self::$groups->add($group);
					self::$groups->write(false, true, 2, false, false);
				}
				
				self::$groupType = self::$groups->first()->type;
				
				// every group has at least the type 1, 0 is just for guests
				if(self::$groupType == 0) {
					self::$groupType = 1;
					self::$groups->first()->type = 1;
					self::$groups->first()->write(false, true, 2, false, false);
				}
				
				self::$loggedIn = $data;
				if(PROFILE) Profiler::unmark("member::Init");
				return true;
			} else {
				self::doLogout();
				if(PROFILE) Profiler::unmark("member::Init");
				return false;
			}
		}
	}
	
	/**
	 * old method
	*/
	public static function checkLogin() {}
	
	/**
	 * returns the groupids of the groups of the user
	 *
	 *@name groupids
	 *@access public
	*/
	public static function groupIDs() {
		if(is_array(self::$groups)) {
			return self::$groups;
		}
		return self::$groups->fieldToArray("id");
	}
	
	/**
	 * returns if the user is logged in
	 *
	 *@name login
	 *@access public
	*/
	public static function login() {
		return (self::$groupType > 0);
	}
	
	/**
	 * returns if the user is an admin
	 *
	 *@name admin
	 *@access public
	*/
	public function admin() {
		return (self::$groupType == 2);
	}
	
	/**
	 * checks if an user have the rights
	 *@name right
	 *@access public
	 *@param string|numeric - if numeric: the rights from 1 - 10, if string: the advanced rights
	 *@return bool
	*/
	public static function right($name)
	{
			return right($name);
	}
	
	/**
	 * login an user with the params
	 * if the params are incorrect, it returns false
	 *
	 *@name doLogin
	 *@access public
	 *@param string - nickname
	 *@param string - password
	 *@return bool
	*/
	public static function doLogin($user, $pwd)
	{
		self::checkDefaults();

		$data = DataObject::get_one("user", array("nickname" => trim(strtolower($user)), "OR", "email" => array("LIKE", $user)));
		
		if($data) {
			// check password
			if(Hash::checkHashMatches($pwd, $data->fieldGet("password"))) {
				if($data->status == 1) {
					// register login
					$_SESSION["g_userlogin"] = $data->id;
					
					$data->phpsess = session_id();
					$data->performLogin();
					
					return true;
				} else if($data->status == 0) {
					addcontent::addError(lang("login_not_unlocked"));
					return false;
				} else {
					addcontent::addError(lang("login_locked"));
					return false;
				}
			} else {
				logging("Login with wrong Username/Password with IP: ".$_SERVER["REMOTE_ADDR"].""); // just for security
				AddContent::addError(lang("wrong_login"));
				return false;
			}
		} else {
			logging("Login with wrong Username/Password with IP: ".$_SERVER["REMOTE_ADDR"].""); // just for security
			AddContent::addError(lang("wrong_login"));
			return false;
		}
	}
	
	/**
	 * forces a logout
	 *
	 *@name doLogout
	 *@access public
	*/
	public function doLogout() {
		$data = DataObject::get_by_id("user", $_SESSION["g_userlogin"]);
		if($data) {
			$data->performLogout();
		}
		unset($_SESSION["g_userlogin"]);
	}
	
	/**
	 * require login
	 *
	 *@name require_Login
	 *@access public
	*/
	public function require_login() {
		if(!self::login()) {
			AddContent::addNotice(lang("require_login"));
			HTTPResponse::redirect(ROOT_PATH . BASE_SCRIPT . "profile/login/?redirect=" . $_SERVER["REQUEST_URI"]);
		}
		return true;
	}
}
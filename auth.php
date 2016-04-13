<?php

/**
 * DokuWiki Plugin skautis (Auth Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Jiri Dorazil <alex@skaut.cz>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
define('SKAUTIS_LIBS_DIR', dirname(__FILE__).'/libs/');
require_once SKAUTIS_LIBS_DIR. 'skautis-minify.php';

global $conf;
// define cookie and session id, append server port when securecookie is configured
if (!defined('AUTHSKAUTIS_COOKIE')){
    define('AUTHSKAUTIS_COOKIE', 'SPGG'.md5(DOKU_REL.(($conf['securecookie'])?$_SERVER['SERVER_PORT']:'')));
}

class auth_plugin_authskautis extends auth_plugin_authplain {

    /**
     * Constructor.
     */
    public function __construct() {
        global $config_cascade;
        parent::__construct(); // for compatibility
        $this->url = Skautis\Config::URL_PRODUCTION . '/Login/?appid=';
        $this->testUrl = Skautis\Config::URL_TEST . '/Login/?appid=';

        $this->success = true;

        $this->cando['addUser']     = true; // can Users be created?
        $this->cando['external']    = true; // does the module do external auth checking?
        $this->cando['logout']      = true; // can the user logout again? (eg. not possible with HTTP auth)

    }

    /**
     * Do all authentication [ OPTIONAL ]
     *
     * @param   string  $user    Username
     * @param   string  $pass    Cleartext Password
     * @param   bool    $sticky  Cookie should not expire
     * @return  bool             true on successful auth
     */
    public function trustExternal($user, $pass, $sticky = false) {
        global $USERINFO;

        //get user info in session
        if (!empty($_SESSION[DOKU_COOKIE]['authskautis']['info'])) {
            $USERINFO['name'] = $_SESSION[DOKU_COOKIE]['authskautis']['info']['name'];
            $USERINFO['mail'] = $_SESSION[DOKU_COOKIE]['authskautis']['info']['mail'];
            $USERINFO['grps'] = $_SESSION[DOKU_COOKIE]['authskautis']['info']['grps'];
            $USERINFO['is_skautis'] = $_SESSION[DOKU_COOKIE]['authskautis']['info']['is_skautis'];
            $_SERVER['REMOTE_USER'] = $_SESSION[DOKU_COOKIE]['authskautis']['user'];
            return true;
        }

        //get form login info
        if(!empty($user)){
            //var_dump($user,$pass);die;
            if($this->checkPass($user,$pass)){
                $uinfo  = $this->getUserData($user);

                //set user info
                $USERINFO['name'] = $uinfo['name'];
                $USERINFO['mail'] = $uinfo['email'];
                $USERINFO['grps'] = $uinfo['grps'];
                $USERINFO['pass'] = $pass;

                //save data in session
                $_SERVER['REMOTE_USER'] = $uinfo['name'];
                $_SESSION[DOKU_COOKIE]['authskautis']['user'] = $uinfo['name'];
                $_SESSION[DOKU_COOKIE]['authskautis']['info'] = $USERINFO;

                return true;
            }else{
                //invalid credentials - log off
                msg($this->getLang('badlogin'),-1);
                return false;
            }
        }


        //$sticky ? $sticky = true : $sticky = false; //sanity check
        if (!empty($_POST)){

            $skautisAppId = $this->getConf('skautis_app_id');
            $skautIsTestmode = $this->getConf('skautis_test_mode');
            $skautIsAllowedAddUser = $this->getConf('skautis_allowed_add_user');
            $skautIs = SkautIs\skautIs::getInstance($skautisAppId,$skautIsTestmode);
            $skautIs->setLoginData($_POST);

            $skautisUser = $skautIs->getUser();

            if ($skautisUser->isLoggedIn(true)) {
                $userData = $skautIs->user->userDetail();
                $token = $skautIs->getUser()->getLoginId();
                $person = $skautIs->org->PersonDetail(array('ID_Login' => $token, 'ID' => $userData->ID_Person));
                $skautisEmail = $person->Email;
                $skautisUsername = $person->FirstName . ' ' . $person->LastName;

                $login = 'skautis'.$userData->ID;
                $udata = $this->getUserData($login);

                //create and update user in base
                if($skautIsAllowedAddUser){
                    if (!$udata) {
                        //default groups
                        $grps = null;
                        if ($this->getConf('default_groups')){
                            $grps = explode(' ', $this->getConf('default_groups'));
                        }
                        //create user
                        $this->createUser($login, md5(rand().$login), $skautisUsername, $skautisEmail, $grps);
                        $udata = $this->getUserData($login);
                    } elseif ($udata['name'] != $skautisUsername || $udata['email'] != $skautisEmail) {
                        //update user
                        $this->modifyUser($login, array('name'=>$skautisUsername, 'email'=>$skautisEmail));
                    }
                }

                if ($udata['login'] == $login){
                    //set user info
                    $USERINFO['pass'] = "";
                    $USERINFO['name'] = $skautisUsername;
                    $USERINFO['mail'] = $skautisEmail;
                    $USERINFO['grps'] = $udata['grps'];
                    $USERINFO['is_skautis'] = true;
                    $_SERVER['REMOTE_USER'] = $skautisUsername;

                    //save user info in session
                    $_SESSION[DOKU_COOKIE]['authskautis']['user'] = $_SERVER['REMOTE_USER'];
                    $_SESSION[DOKU_COOKIE]['authskautis']['info'] = $USERINFO;

                    //if login page - redirect to main page
                    if (isset($_GET['do']) && $_GET['do']=='login'){
                        header("Location: ".wl('start', '', true));
                    }

                    return true;
                } else {
                    msg($this->getLang('nouser'),-1);
                    $this->logOff();
                    return false;
                }
            } else {
                msg($this->getLang('badskautis'),-1);
                $this->logOff();
                return false;
            }
        } else {
            //return false;
        }
        return false;
    }

    function logOff(){
        unset($_SESSION[DOKU_COOKIE]['authskautis']['user']);
        unset($_SESSION[DOKU_COOKIE]['authskautis']['info']);
    }
}
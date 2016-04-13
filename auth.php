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

class auth_plugin_authskautis extends auth_plugin_authplain {


    /**
     * Constructor.
     */
    /*public function __construct() {
        global $config_cascade;
        parent::__construct(); // for compatibility

        // FIXME intialize your auth system and set success to true, if successful
        $this->success = true;
        // FIXME set capabilities accordingly
        /*$this->cando['addUser']     = false; // can Users be created?
        $this->cando['delUser']     = false; // can Users be deleted?
        $this->cando['modLogin']    = false; // can login names be changed?
        $this->cando['modPass']     = false; // can passwords be changed?
        $this->cando['modName']     = false; // can real names be changed?
        $this->cando['modMail']     = false; // can emails be changed?
        $this->cando['modGroups']   = false; // can groups be changed?
        $this->cando['getUsers']    = false; // can a (filtered) list of users be retrieved?
        $this->cando['getUserCount']= false; // can the number of users be retrieved?
        $this->cando['getGroups']   = false; // can a list of available groups be retrieved?*/
       // $this->cando['external']    = true; // does the module do external auth checking?
       // $this->cando['logout']      = true; // can the user logout again? (eg. not possible with HTTP auth)

   // }


    /**
     * Log off the current user [ OPTIONAL ]
     */
    //public function logOff() {
    //}

    /**
     * Do all authentication [ OPTIONAL ]
     *
     * @param   string  $user    Username
     * @param   string  $pass    Cleartext Password
     * @param   bool    $sticky  Cookie should not expire
     * @return  bool             true on successful auth
     */
    /*public function trustExternal($user, $pass, $sticky = false) {
        /* some example:

        global $USERINFO;
        global $conf;
        //$sticky ? $sticky = true : $sticky = false; //sanity check

        // do the checking here

        // set the globals if authed
        $USERINFO['name'] = 'alex';
        $USERINFO['mail'] = 'alex@skaut.cz';
        //$USERINFO['grps'] = array('FIXME');
        $USERINFO['grps'] = array('admin');
        $_SERVER['REMOTE_USER'] = $user;
        //$_SESSION[DOKU_COOKIE]['auth']['user'] = $user;
        //$_SESSION[DOKU_COOKIE]['auth']['pass'] = $pass;
        //$_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;
        return true;

    }*/

}
<?php
if (!defined('DOKU_INC')) die();

define('SKAUTIS_LIBS_DIR', dirname(__FILE__).'/libs/');

class action_plugin_authskautis extends DokuWiki_Action_Plugin {
    /**
     * Registers the event handlers.
     */
    function register(&$controller)
    {
        $controller->register_hook('HTML_LOGINFORM_OUTPUT', 'BEFORE',  $this, 'hook_html_loginform_output', array());
        //$controller->register_hook('HTML_UPDATEPROFILEFORM_OUTPUT', 'BEFORE', $this, 'hook_updateprofileform_output', array());
    }

    /**
     * Handles the login form rendering.
     */
    function hook_html_loginform_output(&$event, $param) {
        
        //$event->data = null;
        //echo print_r($event,true);
        //echo "111";

        //if (isset($_SESSION[DOKU_COOKIE]['authskautis']['auth_url'])) {
            //$auth_url = $_SESSION[DOKU_COOKIE]['authskautis']['auth_url'];
            $auth_url = 'test-is.skaut.cz/Login/?appid=';

            $a_style = "width: 200px;margin:0 auto;color: #666666;cursor: pointer;text-decoration: none !important;display: block;padding-bottom:1.4em;";//-moz-linear-gradient(center top , #F8F8F8, #ECECEC)
            $div_style = "float:left;line-height: 30px;background-color: #F8F8F8;border: 1px solid #C6C6C6;border-radius: 2px 2px 2px 2px;padding: 0px 5px 0px 5px;position: relative;";
            echo "<a href='$auth_url' style='$a_style' title='".$this->getLang('enter_google')."'><div style=\"$div_style\">".$this->getLang('enter_google')."</div>";
            echo "<div style='clear: both;'></div></a>";
        //}
    }
}

?>
<?php
if (!defined('DOKU_INC')) die();

define('SKAUTIS_LIBS_DIR', dirname(__FILE__).'/libs/');
require_once SKAUTIS_LIBS_DIR. 'skautis-minify.php';


class action_plugin_authskautis extends DokuWiki_Action_Plugin {

    protected $url;
    protected $testUrl;

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

        $this->url = Skautis\Config::URL_PRODUCTION . '/Login/?appid=';
        $this->testUrl = Skautis\Config::URL_TEST . '/Login/?appid=';

        $skautisAppId = $this->getConf('skautis_app_id');
        if($skautisAppId!=''){
            $skautIsTestmode = $this->getConf('skautis_test_mode');
            if ($skautIsTestmode){
                $auth_url = $this->testUrl.$skautisAppId;
            } else {
                $auth_url = $this->url.$skautIsTestmode;
            }


            $a_style = "width: 200px;margin:0 auto;color: #666666;cursor: pointer;text-decoration: none !important;display: block;padding-bottom:1.4em;";//-moz-linear-gradient(center top , #F8F8F8, #ECECEC)
            $div_style = "float:left;line-height: 30px;background-color: #F8F8F8;border: 1px solid #C6C6C6;border-radius: 2px 2px 2px 2px;padding: 0px 5px 0px 5px;position: relative;";
            echo "<a href='$auth_url' style='$a_style' title='".$this->getLang('enter_skautis')."'><div style=\"$div_style\">".$this->getLang('enter_skautis')."</div>";
            echo "<div style='clear: both;'></div></a>";
        }
    }
}

?>
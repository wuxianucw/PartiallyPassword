<?php
if(!defined('__TYPECHO_ROOT_DIR__'))exit;
/**
 * 文章部分加密
 * 
 * @package PartiallyPassword
 * @author wuxianucw
 * @version 1.0.0
 * @link https://ucw.moe
 */
class PartiallyPassword_Plugin implements Typecho_Plugin_Interface{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx=array('PartiallyPassword_Plugin','render');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->getDefaultFieldItems=array('PartiallyPassword_Plugin','pluginFields');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        //$form->addInput(new Typecho_Widget_Helper_Form_Element_Text('nothing', NULL, 'Hello World', _t('说点什么')));
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @param string $content
     * @param Widget_Abstract_Contents $widget
     * @return string
     */
    public static function render($content,Widget_Abstract_Contents $widget){
        if($widget->fields->pp_isEnabled){
            
        }
        return $content;
    }

    /**
     * 插件自定义字段
     * 
     * @access public
     * @param $layout
     */
    public static function pluginFields($layout){
        $layout->addItem(new Typecho_Widget_Helper_Form_Element_Select('pp_isEnabled',array(0=>'关闭',1=>'开启',),0,_t('是否开启文章部分加密'),'是否对这篇文章启用部分加密功能'));
        $layout->addItem(new Typecho_Widget_Helper_Form_Element_Text('pp_passwords',NULL,'',_t('密码'),'按照文章中调用的顺序定义密码，如果需要多个密码，请在下面定义一个分隔符，然后在相邻密码间用分隔符分隔。详细例子将在下方给出。'));
        $layout->addItem(new Typecho_Widget_Helper_Form_Element_Text('pp_sep',NULL,'',_t('分隔符'),'用于分隔多个密码。例如填写<code>|</code>作为分隔符，填写<code>114514|1919|810</code>作为密码，则表示依次定义了三个密码：<code>114514</code> <code>1919</code> <code>810</code>。不填写分隔符默认只定义一个密码。'));
    }
}

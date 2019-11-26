<?php
if(!defined('__TYPECHO_ROOT_DIR__'))exit;
/**
 * 文章部分加密
 * 
 * @package PartiallyPassword
 * @author wuxianucw
 * @version 1.1.1
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
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt=array('PartiallyPassword_Plugin','escapeExcerpt');
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
        $default=<<<TEXT
<style>
.pp-block{text-align:center;border-radius:3px;background-color:rgba(0, 0, 0, 0.45);padding:20px 0 20px 0;}
.pp-block>p{margin:0!important;}
.pp-block>p>input{height:24px;border:1px solid #fff;background-color:transparent;width:50%;border-radius:3px;color:#fff;text-align:center;}
.pp-block>p>input::placeholder{color:#fff;}
.pp-block>p>input::-webkit-input-placeholder{color:#fff;}
.pp-block>p>input::-moz-placeholder{color:#fff;}
.pp-block>p>input:-moz-placeholder{color:#fff;}
.pp-block>p>input:-ms-input-placeholder{color:#fff;}
</style>
<script src="https://cdn.jsdelivr.net/npm/jquery.cookie@1.4.1/jquery.cookie.min.js"></script>
<script>
//need jQuery ans jQuery.cookie
$("div.pp-block>p>input").keypress(function(e){
    if(e.which==13){
        let id=$(this).data('id');
        let p=$(this).val();
        if(!p)return false;
        $.cookie("PartiallyPassword"+id,p);
        if($.pjax!=undefined)$.pjax.reload('#content',{fragment:'#content',timeout:8000});
        else window.location.reload();
    }
});
</script>
TEXT;
        $tips=<<<TEXT
将插入在文章末尾。可以使用的变量包括：
<ul>
<li><code>{currentPage}</code>：当前页面URL</li>
</ul>
TEXT;
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('common',NULL,$default,_t('页面公共HTML'),$tips));
        $default=<<<TEXT
<div class="pp-block">
<p>
<input name="pp-password-{uniqueId}" type="password" placeholder="{additionalContent}" data-id="{id}" data-uid="{uniqueId})">
</p>
</div>
TEXT;
        $tips=<<<TEXT
密码区域的HTML。可以使用的变量包括：
<ul>
<li><code>{id}</code>：当前页面加密区块编号</li>
<li><code>{uniqueId}</code>：当前页面加密区块唯一编号</li>
<li><code>{currentPage}</code>：当前页面URL</li>
<li><code>{additionalContent}</code>：附加信息</li>
</ul>
TEXT;
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('placeholder',NULL,$default,_t('密码区域HTML'),$tips));
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
            if(strpos($content,'[ppblock')!==false){
                @$sep=$widget->fields->pp_sep;
                @$pwds=$widget->fields->pp_passwords;
                if($sep)$pwds=explode($sep,$pwds);
                else $pwds=array($pwds);
                $mod=count($pwds);
                if(!$mod){
                    $mod=1;
                    $pwds=array('');
                }
                $content=preg_replace_callback('/'.self::get_shortcode_regex('ppblock').'/',function($matches)use($widget,$pwds,$mod){
                    static $count=0;
                    if($matches[1]=='['&&$matches[6]==']')return substr($matches[0],1,-1);//不解析类似 [[ppblock]] 双重括号的代码
                    $now=$count%$mod;
                    $count++;
                    $attr=htmlspecialchars_decode($matches[3]);//还原转义前的参数列表
                    $attrs=self::shortcode_parse_atts($attr);//获取短代码的参数
                    $ex='';
                    if(is_array($attrs)&&isset($attrs['ex']))$ex=$attrs['ex'];
                    $inner=$matches[5];
                    if($pwds[$now]=='')return $inner;
                    $input=$_COOKIE['PartiallyPassword'.$now];
                    if($input&&$input===$pwds[$now])return $inner;
                    else{
                        @$placeholder=Typecho_Widget::widget('Widget_Options')->plugin('PartiallyPassword')->placeholder;
                        if(!$placeholder)$placeholder='<div><strong style="color:red">请配置密码区域HTML！</strong></div>';
                        $placeholder=str_replace(array('{id}','{uniqueId}','{currentPage}','{additionalContent}'),array($now,$count,$widget->permalink,$ex),$placeholder);
                        return $placeholder;
                    }
                },$content);
                @$common_content=Typecho_Widget::widget('Widget_Options')->plugin('PartiallyPassword')->common;
                if(!$common_content)$common_content='';
                $content.=str_replace(array('{currentPage}'),array($widget->permalink),$common_content);
            }
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
        $layout->addItem(new Typecho_Widget_Helper_Form_Element_Select('pp_isEnabled',array(0=>'关闭',1=>'开启'),0,_t('是否开启文章部分加密'),'是否对这篇文章启用部分加密功能'));
        $layout->addItem(new Typecho_Widget_Helper_Form_Element_Text('pp_passwords',NULL,'',_t('密码'),'按照文章中调用的顺序定义密码，如果需要多个密码，请在下面定义一个分隔符，然后在相邻密码间用分隔符分隔。详细例子将在下方给出。'));
        $layout->addItem(new Typecho_Widget_Helper_Form_Element_Text('pp_sep',NULL,'',_t('分隔符'),'用于分隔多个密码。例如填写<code>|</code>作为分隔符，填写<code>114514|1919|810</code>作为密码，则表示依次定义了三个密码：<code>114514</code> <code>1919</code> <code>810</code>。不填写分隔符默认只定义一个密码。'));
    }

    /**
     * 隐藏摘要
     * 
     * @access public
     * @param string $text
     * @param Widget_Abstract_Contents $widget
     * @return string
     */
    public static function escapeExcerpt($text,$widget){
        if($widget->fields->pp_isEnabled){
            if(strpos($text,'[ppblock')!==false){
                $text=preg_replace('/'.self::get_shortcode_regex('ppblock').'/','',$text);
            }
        }
        return $text;
    }

    /**
     * 获取匹配短代码的正则表达式
     * @param string $tagnames
     * @return string
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php#L254
     */
    public static function get_shortcode_regex( $tagname ) {
        $tagregexp = preg_quote( $tagname );
        // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
        // Also, see shortcode_unautop() and shortcode.js.
        // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
        return
            '\\['                                // Opening bracket
            . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
            . "($tagregexp)"                     // 2: Shortcode name
            . '(?![\\w-])'                       // Not followed by word character or hyphen
            . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
            .     ')*?'
            . ')'
            . '(?:'
            .     '(\\/)'                        // 4: Self closing tag ...
            .     '\\]'                          // ... and closing bracket
            . '|'
            .     '\\]'                          // Closing bracket
            .     '(?:'
            .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
            .             '[^\\[]*+'             // Not an opening bracket
            .             '(?:'
            .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
            .                 '[^\\[]*+'         // Not an opening bracket
            .             ')*+'
            .         ')'
            .         '\\[\\/\\2\\]'             // Closing shortcode tag
            .     ')?'
            . ')'
            . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
        // phpcs:enable
    }

    /**
     * 获取短代码属性数组
     * @param $text
     * @return array|string
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php#L508
     */
    public static function shortcode_parse_atts($text) {
        $atts    = array();
        $pattern = self::get_shortcode_atts_regex();
        $text    = preg_replace( "/[\x{00a0}\x{200b}]+/u", ' ', $text );
        if ( preg_match_all( $pattern, $text, $match, PREG_SET_ORDER ) ) {
            foreach ( $match as $m ) {
                if ( ! empty( $m[1] ) ) {
                    $atts[ strtolower( $m[1] ) ] = stripcslashes( $m[2] );
                } elseif ( ! empty( $m[3] ) ) {
                    $atts[ strtolower( $m[3] ) ] = stripcslashes( $m[4] );
                } elseif ( ! empty( $m[5] ) ) {
                    $atts[ strtolower( $m[5] ) ] = stripcslashes( $m[6] );
                } elseif ( isset( $m[7] ) && strlen( $m[7] ) ) {
                    $atts[] = stripcslashes( $m[7] );
                } elseif ( isset( $m[8] ) && strlen( $m[8] ) ) {
                    $atts[] = stripcslashes( $m[8] );
                } elseif ( isset( $m[9] ) ) {
                    $atts[] = stripcslashes( $m[9] );
                }
            }
            // Reject any unclosed HTML elements
            foreach ( $atts as &$value ) {
                if ( false !== strpos( $value, '<' ) ) {
                    if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                        $value = '';
                    }
                }
            }
        } else {
            $atts = ltrim( $text );
        }
        return $atts;
    }

    private static function get_shortcode_atts_regex(){return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|\'([^\']*)\'(?:\s|$)|(\S+)(?:\s|$)/';}

    //private static function get_markdown_regex($tagName='?'){return '\\'.$tagName.'&gt; (.*)(\n\n)?';}
}

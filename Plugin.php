<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文章部分加密
 *
 * @package PartiallyPassword
 * @author wuxianucw
 * @version 3.1.2
 * @link https://ucw.moe
 */
class PartiallyPassword_Plugin implements Typecho_Plugin_Interface {
    /**
     * 激活插件
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('PartiallyPassword_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->getDefaultFieldItems = array('PartiallyPassword_Plugin', 'pluginFields');
        Typecho_Plugin::factory('Widget_Archive')->singleHandle = array('PartiallyPassword_Plugin', 'handleSubmit');
        Typecho_Plugin::factory('Widget_Archive')->header = array('PartiallyPassword_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('PartiallyPassword_Plugin', 'footer');
    }

    /**
     * 禁用插件
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {
        /** Referer 检查 */
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
            'checkReferer',
            array(0 => '关闭', 1 => '开启'),
            1,
            _t('Referer 检查'),
            '若开启，将对每个密码请求进行 Referer 检查。'
        ));

        /** 额外 Markdown 标记 */
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
            'extraMdToken',
            array(0 => '开启', 1 => '关闭'),
            0,
            _t('额外 Markdown 标记'),
            '若开启，对于 Markdown 格式的文章，将在每个加密块首尾插入 <code>!!!</code> 标记。本配置为兼容性配置，' .
            '对于 Typecho 1.1 默认的 HyperDown 解析器，需要保持开启以确保 HTML 生效。如果您在使用过程中发现加密块' .
            '前后有多余的 <code>!!!</code> 标记，请尝试将本配置项设置为关闭。'
        ));

        /** 自定义页头 HTML */
        $default = <<<TEXT
<style>
.pp-block {text-align: center; border-radius:3px; background-color: rgba(0, 0, 0, 0.45); padding: 20px 0 20px 0;}
.pp-block>form>input {height: 24px; border: 1px solid #fff; background-color: transparent; width: 50%; border-radius: 3px; color: #fff; text-align: center;}
.pp-block>form>input::placeholder{color: #fff;}
.pp-block>form>input::-webkit-input-placeholder{color: #fff;}
.pp-block>form>input::-moz-placeholder{color: #fff;}
.pp-block>form>input:-moz-placeholder{color: #fff;}
.pp-block>form>input:-ms-input-placeholder{color: #fff;}
</style>
TEXT;
        $tips = <<<TEXT
将插入在所有页面的页头（header）。
TEXT;
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('header', NULL, $default, _t('自定义页头 HTML'), $tips));

        /** 自定义页脚 HTML */
        $default = <<<TEXT
<script>
// Powered by wuxianucw
console.log('PartiallyPassword is enabled.');
</script>
TEXT;
        $tips = <<<TEXT
将插入在所有页面的页脚（footer）。
TEXT;
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('footer', NULL, $default, _t('自定义页脚 HTML'), $tips));

        /** 密码区域 HTML */
        $default = <<<TEXT
<div class="pp-block">
<form action="{targetUrl}" method="post" style="margin: 0;">
<input name="partiallyPassword" type="password" placeholder="{additionalContent}">
<input name="pid" type="hidden" value="{id}">
</form>
</div>
TEXT;
        $tips = <<<TEXT
密码区域的HTML。可以使用的变量包括：
<ul>
<li><code>{id}</code>：当前加密块 ID</li>
<li><code>{additionalContent}</code>：附加信息</li>
<li><code>{currentPage}</code>：当前页面 URL</li>
<li><code>{cid}</code>：当前日志 ID</li>
<li><code>{targetUrl}</code>：POST 提交接口页面URL</li>
</ul>
TEXT;
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('placeholder', NULL, $default, _t('密码区域 HTML'), $tips));
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 自定义输出header
     *
     * @access public
     * @param string $header
     * @param Widget_Archive $archive
     * @return void
     */
    public static function header($header, Widget_Archive $archive) {
        @$header_html = Helper::options()->plugin('PartiallyPassword')->header;
        if ($header_html) echo $header_html;
    }

    /**
     * 自定义输出footer
     *
     * @access public
     * @param Widget_Archive $archive
     * @return void
     */
    public static function footer(Widget_Archive $archive) {
        @$footer_html = Helper::options()->plugin('PartiallyPassword')->footer;
        if ($footer_html) echo $footer_html;
    }

    /**
     * 取得请求发送的密码
     *
     * @access private
     * @param mixed $cid
     * @param mixed $pid
     * @param mixed $currentCid
     * @return string
     */
    private static function getRequestPassword($cid, $pid, $currentCid = -1) {
        if ($currentCid == -1) $currentCid = $cid;
        $request = new Typecho_Request();
        $request_pid = $request->get('pid') != null ? intval($request->get('pid')) : 0;
        if ($request->get('pid') != null && $request_pid === $pid) {
            if (@Helper::options()->plugin('PartiallyPassword')->checkReferer)
                if (stripos($request->getReferer(), Helper::options()->rootUrl) !== 0) return;
            return (new PasswordHash(8, true))->HashPassword($request->get('partiallyPassword'));
        }
        return Typecho_Cookie::get("partiallyPassword_{$cid}_{$pid}", '');
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @param array $value
     * @param Widget_Abstract_Contents $contents
     * @return string
     */
    public static function render($value, Widget_Abstract_Contents $contents) {
        if ($value['type'] != 'page' && $value['type'] != 'post') return $value;
        if (defined('__TYPECHO_ADMIN__')) {
            if ($value['authorId'] != $contents->widget('Widget_User')->uid && !$contents->widget('Widget_User')->pass('editor', true))
                $value['hidden'] = true;
            return $value;
        }
        $fields = array();
        $db = Typecho_Db::get();
        $rows = $db->fetchAll($db->select()->from('table.fields')->where('cid = ?', $value['cid']));
        foreach ($rows as $row) {
            $fields[$row['name']] = $row[$row['type'] . '_value'];
        }
        $fields = new Typecho_Config($fields);
        if ($fields->pp_isEnabled) {
            @$pwds = json_decode($fields->pp_passwords, true);
            if (!is_array($pwds)) $pwds = array();
            array_map('strval', $pwds);
            $options = Helper::options()->plugin('PartiallyPassword');
            $placeholder = isset($options->placeholder) ? $options->placeholder : '';
            $extraMdToken = isset($options->extraMdToken) ? intval($options->extraMdToken) : 0;
            if (!$placeholder) $placeholder = '<div><strong style="color:red">请配置密码区域 HTML！</strong></div>';
            if ($value['isMarkdown'] && $extraMdToken === 0) $placeholder = "\n!!!\n{$placeholder}\n!!!\n";
            $hasher = new PasswordHash(8, true);
            $value['text'] = preg_replace_callback(
                '/' . self::getShortcodeRegex(array('ppblock', 'ppswitch')) . '/',
                function($matches) use ($contents, $value, $pwds, $placeholder, $extraMdToken, $hasher) {
                    static $id = -1;
                    if ($matches[1] == '[' && $matches[6] == ']') return substr($matches[0], 1, -1); // 不解析类似 [[ppblock]] 双重括号的代码
                    $id++;
                    $attrs = self::shortcodeParseAtts($matches[3]); // 获取短代码的参数
                    $ex = '';
                    $pwd_idx = strval($id);
                    if (is_array($attrs)) {
                        if (isset($attrs['ex'])) $ex = $attrs['ex'];
                        if (isset($attrs['pwd'])) $pwd_idx = $attrs['pwd'];
                    }
                    $inner = trim($matches[5]);
                    $input = self::getRequestPassword($value['cid'], $id, $contents->cid);
                    if ($matches[2] == 'ppswitch') {
                        if (!$input) {
                            $placeholder = str_replace(
                                array('{id}', '{currentPage}', '{cid}', '{additionalContent}', '{targetUrl}'),
                                array($id, $value['permalink'], $value['cid'], $ex, $value['permalink']),
                                $placeholder
                            );
                            return $placeholder;
                        }
                        $succ = false;
                        $inner = preg_replace_callback(
                            '/' . self::getShortcodeRegex(array('case')) . '/',
                            function($matches) use ($pwds, $input, $hasher, &$succ) {
                                if ($matches[1] == '[' && $matches[6] == ']') return substr($matches[0], 1, -1);
                                $attrs = self::shortcodeParseAtts($matches[3]);
                                if (!isset($attrs['pwd']) || !in_array($attrs['pwd'], array_keys($pwds))) return '';
                                if ($hasher->CheckPassword($pwds[$attrs['pwd']], $input)) {
                                    $succ = true;
                                    return trim($matches[5]);
                                } else return '';
                            },
                            $inner
                        );
                        if ($succ) return $inner;
                        else {
                            $placeholder = str_replace(
                                array('{id}', '{currentPage}', '{cid}','{additionalContent}', '{targetUrl}'),
                                array($id, $value['permalink'], $value['cid'], $ex, $value['permalink']),
                                $placeholder
                            );
                            return $placeholder;
                        }
                    }
                    if (!in_array($pwd_idx, array_keys($pwds))) {
                        if (isset($pwds['fallback'])) $pwd_idx = 'fallback';
                        else {
                            $err = "<div><strong style=\"color:red\">错误：id = {$id} 的加密块未设置密码！</strong></div>";
                            if ($value['isMarkdown'] && $extraMdToken === 0) $err = "\n!!!\n{$err}\n!!!\n";
                            return $err;
                        }
                    }
                    if ($input && $hasher->CheckPassword($pwds[$pwd_idx], $input)) return $inner;
                    else {
                        $placeholder = str_replace(
                            array('{id}', '{currentPage}', '{cid}', '{additionalContent}', '{targetUrl}'),
                            array($id, $value['permalink'], $value['cid'], $ex, $value['permalink']),
                            $placeholder
                        );
                        return $placeholder;
                    }
                },
                $value['text']
            );
        }
        return $value;
    }

    /**
     * 插件自定义字段
     *
     * @access public
     * @param mixed $layout
     * @return void
     */
    public static function pluginFields($layout) {
        $layout->addItem(new Typecho_Widget_Helper_Form_Element_Select(
            'pp_isEnabled',
            array(0 => '关闭', 1 => '开启'),
            0,
            _t('是否开启文章部分加密'),
            '是否对这篇文章启用部分加密功能'
        ));
        $layout->addItem(new Typecho_Widget_Helper_Form_Element_Textarea(
            'pp_passwords',
            NULL,
            '',
            _t('密码组'),
            'JSON 格式的密码组，参考 <a href="https://github.com/wuxianucw/PartiallyPassword/blob/master/README.md" target="_blank">README</a>'
        ));
    }

    /**
     * 处理密码提交
     *
     * @access public
     * @param Widget_Archive $archive
     * @param Typecho_Db_Query $select
     * @return void
     */
    public static function handleSubmit(Widget_Archive $archive, Typecho_Db_Query $select) {
        if (!$archive->is('page') && !$archive->is('post')) return;
        if ($archive->fields->pp_isEnabled && $archive->request->get('partiallyPassword') != null) {
            $pid = $archive->request->get('pid') != null ? intval($archive->request->get('pid')) : 0;
            if ($pid < 0) return;
            if (@Helper::options()->plugin('PartiallyPassword')->checkReferer)
                if (stripos($archive->request->getReferer(), Helper::options()->rootUrl) !== 0) return;
            Typecho_Cookie::set(
                "partiallyPassword_{$archive->cid}_{$pid}",
                (new PasswordHash(8, true))->HashPassword($archive->request->get('partiallyPassword'))
            );
        }
    }

    /**
     * 获取匹配短代码的正则表达式
     *
     * @access protected
     * @param string $tagnames
     * @return string
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php
     */
    protected static function getShortcodeRegex($tagnames) {
        $tagregexp = implode('|', array_map('preg_quote', $tagnames));
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
     *
     * @access protected
     * @param $text
     * @return array|string
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php
     */
    protected static function shortcodeParseAtts($text) {
        $atts = array();
        $pattern = self::getShortcodeAttsRegex();
        $text = preg_replace( "/[\x{00a0}\x{200b}]+/u", ' ', $text );
        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                if (!empty($m[1])) {
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                } elseif (!empty($m[3])) {
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                } elseif (!empty($m[5])) {
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                } elseif (isset($m[7]) && strlen($m[7])) {
                    $atts[] = stripcslashes($m[7]);
                } elseif (isset($m[8]) && strlen($m[8])) {
                    $atts[] = stripcslashes($m[8]);
                } elseif (isset($m[9])) {
                    $atts[] = stripcslashes($m[9]);
                }
            }
            // Reject any unclosed HTML elements
            foreach ($atts as &$value) {
                if (strpos($value, '<') !== false) {
                    if (preg_match('/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value) !== 1) {
                        $value = '';
                    }
                }
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    /**
     * 获取短代码属性正则表达式
     *
     * @access private
     * @return string
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php
     */
    private static function getShortcodeAttsRegex() {
        return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|\'([^\']*)\'(?:\s|$)|(\S+)(?:\s|$)/';
    }
}

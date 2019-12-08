# Typecho 文章部分加密插件（PartiallyPassword）

Typecho 文章部分加密插件（PartiallyPassword）支持对某一篇文章的特定部分创建密码，访客需要正确输入密码才能查看内容。

PartiallyPassword Plugin for Typecho supports the creation of a password for a specific part of an article, and the visitor needs to enter the password correctly before viewing the content.

## 安装 Installation

### A. 直接下载 Download directly

访问本项目的 Release 页面，下载最新的 Release 版本，解压后将其中的文件夹重命名为 `PartiallyPassword` 并移动到 Typecho 插件目录下。

Visit the Release page of our project, download the latest Release version, unzip it, and then rename the folder to `PartiallyPassword` and move it to the Typecho plugin directory.

### B. 从仓库克隆 Clone from repo

> [!] 请谨慎克隆当前 master 分支，其中可能包含尚未完成的新功能。  
[!] Please be careful when cloning the current master branch, as it may contain new features that have not yet been completed.

在 Typecho 插件目录下启动终端，执行命令即可。

Start the terminal in the Typecho plugin directory and execute the command below.

```bash
git clone https://github.com/wuxianucw/PartiallyPassword.git
```

或下载压缩包（`Download ZIP`）并解压，将其中的文件夹重命名为 `PartiallyPassword` 并移动到 Typecho 插件目录下。

Or `Download ZIP` and unzip it, rename the folder to `PartiallyPassword` and move it to the Typecho plugin directory.

## 使用方法 Usage

### 初始化 Initialization

启用插件，即完成全部初始化工作。默认配置是一套非常简单的演示样式，建议根据主题特性进行自定义修改。

Activate the plugin, and that is all initialization work. The default configuration is a very simple presentation style, it is recommended to customize the changes according to your theme features.

### 调用方法举例 Samples

#### 基础用法 Basic

```text
不加密的内容
something that doesn't require password
[ppblock]
加密的内容
something that requires password
[/ppblock]
别的东西
something else...
```

这就是一个最简单的例子。你也可以进一步给加密块添加附加信息：

This is the simplest demo. You can also add `ex` attribute, just like:

```text
[ppblock ex="Please Enter Password"]
加密的内容
something that requires password
[/ppblock]
```

附加信息将会在输入密码处显示。

Additional Content set by `ex` attribute will be displayed at the password-inputting area.

如果你仍然想书写一段 `[ppblock]...[/ppblock]` 形式的文本，而不希望它被解析，请使用 `[[ppblock]...[/ppblock]]`，两侧多余的方括号会被自动移除。

If you still need a text like `[ppblock]...[/ppblock]`, please use `[[ppblock]...[/ppblock]]`. Extra square brackets are automatically removed.

#### 插入多个区块 Multiple blocks

```text
This is a simple demo.
[ppblock]
AAA
[/ppblock]
Something else...
[ppblock ex="Haha, one more"]
BBB
[/ppblock]
Something else...
[ppblock ex="Another!"]
CCC
[/ppblock]
end
```

如果你只配置了一个密码，那么输入这个密码后，所有被加密的内容都可见。  
如果你配置的密码数量小于加密区块（ppblock）的数量，那么将会产生循环。假设有 `n` 个区块、`m` 个密码，即当 `n>m` 时，第 `m+1` 个区块将会使用第 1 个密码，第 `m+2` 个区块将会使用第 2 个密码，以此类推。第 `i` 个区块实际使用的密码为第 `(i-1)%m+1` 个密码。但我们不推荐这种设置，因为它并不一定能够正常工作。  
如果你配置的密码数量大于加密区块的数量，多余的密码将被舍弃。

为上例配置 3 个密码，即可达到不同区块使用不同密码的目的。

If you only have one password configured, all encrypted content will be visible after entering this password.  
If you configure a smaller number of passwords than the number of encrypted blocks (ppblocks), then you will start a loop. Suppose there are `n` blocks, `m` passwords, ie when `n>m`, the `m+1`-th block will use the first password. The `m+2`-th block will use the second password, and so on. The password actually used by the `i`-th block is the `(i-1)%m+1`-th password. But we don't recommend this setting because it doesn't necessarily work as you think.  
If the number of passwords you configure is greater than the number of encrypted blocks, the extra password will be discarded.

For the above example, configure three passwords to achieve different passwords for different blocks.

### 提示 Tips

- 请勿不成对或嵌套地使用 `[ppblock]` 标记，它的展现无法预期。  
Do not use the `[ppblock]` tag unpaired or nested, it will not be displayed as expected.

## TODO List

- [x] 在 `Widget_Abstract_Contents` 的 `excerpt` 下挂接函数，屏蔽所有 `[ppblock]` 以及其中的内容，不判断 Cookie。（Since v1.1.0）
- [x] ~~寻找一个方案可以直接操作 `$widget->text` 取出的内容，实现完美屏蔽。~~ 已经更改为在 `Widget_Abstract_Contents` 的 `filter` 下挂接插件实现方法，这样操作后从 Widget 中取出的数据已经全部进行了过滤，除非直接读取数据库，否则理论上不存在加密区块不解析的情形。（Since v2.0.0）
- [x] ~~现有的鉴权逻辑较为不完善，应增加提交密码时的后端相关处理，并合理优化流程。~~ 已经完全交由后端处理 Cookie，流程变更为直接向文章页面 POST 数据。（Since v2.0.0）
- [x] ~~默认外观需要优化，包括样式和插入位置。~~ 已经完成优化，现在的默认样式是一套极简风格的密码输入框。（Since v1.1.1）公共 HTML 的插入位置变更为页头和页脚。（Since v2.0.0）
- [ ] 考虑增加加密区块语法支持，后续将可能支持更加复杂的语法。具体方案暂时未定。

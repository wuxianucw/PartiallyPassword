# Typecho 文章部分加密插件(PartiallyPassword)

Typecho 文章部分加密插件(PartiallyPassword)支持对某一篇文章的特定部分创建密码，访客需要正确输入密码才能查看内容。

PartiallyPassword Plugin for Typecho supports the creation of a password for a specific part of an article, and the visitor needs to enter the password correctly before viewing the content.

## 安装 Installation

### A. 直接下载 Download directly

~~访问本项目的Release页面，下载最新的Release版本，并将其移动到 Typecho 插件目录下。~~

~~Visit the Release page of our project, download the latest Release version, and then move it to the Typecho plugin directory.~~

暂不提供。

Not support yet.

### B. 从仓库克隆 Clone from repo

在 Typecho 插件目录下启动终端，执行命令即可。

Start the terminal in the Typecho plugin directory and execute the command below.

```bash
git clone https://github.com/wuxianucw/PartiallyPassword.git
```

或下载压缩包(`Download ZIP`)并解压，将其中的文件夹重命名为`PartiallyPassword`并移动到 Typecho 插件目录下。

Or `Download ZIP` and unzip it, rename the folder to `PartiallyPassword` and move it to the Typecho plugin directory.

## 使用方法 Usage

### 初始化 Initialization

启用插件，在设置界面填写`页面公共HTML`和`密码区域HTML`，即完成全部初始化工作。默认填入的HTML是一套非常简单的演示样式，建议根据主题特性进行自定义修改。

Activate the plugin, fill in the `Page Public HTML` and `Password Area HTML` in the settings page, and that is all initialization work. The default HTML is a very simple presentation style, it is recommended to customize the changes according to your theme features.

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
如果你配置的密码数量小于加密区块(ppblock)的数量，那么将会产生循环。假设有`n`个区块、`m`个密码，即当`n>m`时，第`m+1`个区块将会使用第1个密码，第`m+2`个区块将会使用第2个密码，以此类推。第`i`个区块实际使用的密码为第`(i-1)%m+1`个密码。但我们不推荐这种设置，因为它并不一定能够正常工作。  
如果你配置的密码数量大于加密区块的数量，多余的密码将被舍弃。

为上例配置3个密码，即可达到不同区块使用不同密码的目的。

If you only have one password configured, all encrypted content will be visible after entering this password.  
If you configure a smaller number of passwords than the number of encrypted blocks (ppblocks), then you will start a loop. Suppose there are `n` blocks, `m` passwords, ie when `n>m`, the `m+1`-th block will use the first password. The `m+2`-th block will use the second password, and so on. The password actually used by the `i`-th block is the `(i-1)%m+1`-th password. But we don't recommend this setting because it doesn't necessarily work as you think.  
If the number of passwords you configure is greater than the number of encrypted blocks, the extra password will be discarded.

For the above example, configure three passwords to achieve different passwords for different blocks.

### 提示 Tips

- 此版本尚未对基于`text`的调用进行内容替换操作，部分依赖这一属性的功能中，加密内容仍然有可能暴露，此时请向我提出issue，我会尽快在后续版本中修复相关问题。  
This version has not yet done a content replacement operation based on the `text` call. In some functions that depend on this property, the encrypted content may still be exposed. Please submit an issue to me at this time, I will fix the related issue in the subsequent version as soon as possible.

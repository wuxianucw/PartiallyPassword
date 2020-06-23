# Typecho 文章部分加密插件（PartiallyPassword）

> **当前分支是 dev 分支。如果你不是开发者，请切换到 master 分支。**

Typecho 文章部分加密插件（PartiallyPassword）支持对某一篇文章的特定部分创建密码，访客需要正确输入密码才能查看内容。

## 安装 Installation

### A. 直接下载

访问本项目的 Release 页面，下载最新的 Release 版本，解压后将其中的文件夹重命名为 `PartiallyPassword` 并移动到 Typecho 插件目录下。

### B. 从仓库克隆

> **[\!]** 当前分支是 dev 分支，请不要直接克隆它。dev 分支常常包含未经充分测试的新功能和可能造成严重后果的破坏性更改。  

在 Typecho 插件目录下启动终端，执行命令即可。

```bash
git clone -b master --single-branch https://github.com/wuxianucw/PartiallyPassword.git
```

或下载压缩包（`Download ZIP`）并解压，将其中的文件夹重命名为 `PartiallyPassword` 并移动到 Typecho 插件目录下。

## 使用方法 Usage

### 初始化

启用插件，即完成全部初始化工作。默认配置是一套非常简单的演示样式，建议根据主题特性进行自定义修改。

### 调用方法举例

#### 基础用法

在书写加密语法之前，请先将对应文章下方“自定义字段”中“是否开启文章部分加密”一项调整为“开启”状态。该项目默认为“关闭”状态，在此情况下，任何加密语法都不会被解析。

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

```text
[ppblock ex="Please Enter Password"]
加密的内容
something that requires password
[/ppblock]
```

附加信息将会在输入密码处显示。

如果你仍然想书写一段 `[ppblock]...[/ppblock]` 形式的文本，而不希望它被解析，请使用 `[[ppblock]...[/ppblock]]`，两侧多余的方括号会被自动移除。

#### 插入多个区块

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

~~如果你只配置了一个密码，那么输入这个密码后，所有被加密的内容都可见。~~  
~~如果你配置的密码数量小于加密区块（ppblock）的数量，那么将会产生循环。假设有 `n` 个区块、`m` 个密码，即当 `n > m` 时，第 `m + 1` 个区块将会使用第 1 个密码，第 `m + 2` 个区块将会使用第 2 个密码，以此类推。第 `i` 个区块实际使用的密码为第 `(i - 1) % m + 1` 个密码。但我们不推荐这种设置，因为它并不一定能够正常工作。~~  
如果有加密块未被指定密码，在该加密块的位置将会产生一条报错信息。  
如果你配置的密码数量大于加密区块的数量，多余的密码将被舍弃。

为上例配置 3 个密码，即可达到不同区块使用不同密码的目的。

### 提示

- 请勿不成对或嵌套地使用 `[ppblock]` 标记，它的展现无法预期。  

## TODO List

- [x] 在 `Widget_Abstract_Contents` 的 `excerpt` 下挂接函数，屏蔽所有 `[ppblock]` 以及其中的内容，不判断 Cookie。（Since v1.1.0）
- [x] ~~寻找一个方案可以直接操作 `$widget->text` 取出的内容，实现完美屏蔽。~~ 已经更改为在 `Widget_Abstract_Contents` 的 `filter` 下挂接插件实现方法，这样操作后从 Widget 中取出的数据已经全部进行了过滤，除非直接读取数据库，否则理论上不存在加密区块不解析的情形。（Since v2.0.0）
- [x] ~~现有的鉴权逻辑较为不完善，应增加提交密码时的后端相关处理，并合理优化流程。~~ 已经完全交由后端处理 Cookie，流程变更为直接向文章页面 POST 数据。（Since v2.0.0）
- [x] ~~默认外观需要优化，包括样式和插入位置。~~ 已经完成优化，现在的默认样式是一套极简风格的密码输入框。（Since v1.1.1）公共 HTML 的插入位置变更为页头和页脚。（Since v2.0.0）
- [x] ~~考虑增加加密区块语法支持，后续将可能支持更加复杂的语法。具体方案暂时未定。~~ 新增 `ppswitch` 语法，能够实现不同密码对应不同内容（[#2](https://github.com/wuxianucw/PartiallyPassword/issues/2)）。（Since v3.0.0）

## In Progress

- 完善 README
- 暂时没了，咕咕咕

## Changelog

- 移除对输入密码的 `md5` 加密，改为 `PasswordHash`。（2020.06.22）
- 新增 `Referer 检查` 配置，内部名称为 `checkReferer`，用于对抗一些异常操作。（2020.06.22）
- 模板变量 `uniqueId` 改为从 0 开始，与模板变量 `id` 保持一致。在 v2.0.2 及以前版本（包括 v2.0.2），`uniqueId` 是从 1 开始的，而 `id` 始终是从 0 开始的。（2020.06.23）
- 允许空密码。（2020.06.23）
- `ppblock` 加密块中内容默认 `trim` 一次。（2020.06.23）
- 采用 JSON 密码组方案，新增 `pwd` 命名密码参数，废弃模数循环。（2020.06.23）
- 废弃模板变量 `uniqueId`，因为它现在与 `id` 完全一致。该变量自 v3.0.0 起移除，v2.x 不受影响。
- 新增 `ppswitch` 语法，能够实现不同密码对应不同内容（[#2](https://github.com/wuxianucw/PartiallyPassword/issues/2)）。

## Note

### 2020-06-23

设计了一套不同密码对应不同内容（[#2](https://github.com/wuxianucw/PartiallyPassword/issues/2)）的语法 ~~（未实现，未确定，不在计划内）~~ ，大致框架形如：

```text
outside
[ppswitch]
inside
[case pwd="x"]xxx[/case]
[case pwd="y"]yyy[/case]
[/ppswitch]
```

没有输入密码时，展现为：

```text
outside
[///NEED PASSWORD///]
```

输入第一个 `case` 的密码后，展现为：

```text
outside
inside
xxx
```

当所有 `case` 都不满足，且输入密码不为空时，显示 `default` 中的内容。

此设计目前尚有几点有待明确：

1. `case` 密码的指定方式；
2. 是否需要提供清除已输入密码的接口。

密码应当由自定义字段指定，但目前的指定方式过于简单，采取直接隐式索引很不优雅，可以考虑改为 JSON 格式，但这将是一个 breaking change，无法兼容已经设置的密码。

如果使用 JSON 格式指定密码，将能够实现“具名密码”，密码复用也将变得简便。例如：

```json
{
    "test": "114514",
    "2": "1919810",
    "fallback": "000000"
}
```

```text
AAA
[ppblock pwd="test"]BBB[/ppblock] // id = 0
[ppblock pwd="test"]CCC[/ppblock] // id = 1
[ppblock]DDD[/ppblock]            // id = 2
[ppblock]EEE[/ppblock]            // id = 3
```

密码的寻找逻辑为：

1. 检查 `pwd` 属性
    - 若该属性存在，从第 2 步继续后续操作 →
    - 若该属性不存在，从第 3 步继续后续操作 →
2. 寻找 JSON 中是否有索引为 `pwd` 的值的项目
    - 是，使用该项目作为当前块的密码，结束 ↓
    - 否，从第 4 步继续后续操作 →
3. 寻找 JSON 中是否有索引为 `id` 的值的项目
    - 是，使用该项目作为当前块的密码，结束 ↓
    - 否，从第 4 步继续后续操作 →
4. 寻找 JSON 中是否有索引为 `fallback` 的项目
    - 是，使用该项目作为当前块的密码，结束 ↓
    - 否，当前块展现为“密码未设置”错误提示

因此，上例展现为：

```text
AAA
[///114514///]  // "test"
[///114514///]  // "test"
[///1919810///] // "2"
[///000000///]  // "fallback"
```

如果移除 `fallback` 项目，则展现为：

```text
AAA
[///114514///]  // "test"
[///114514///]  // "test"
[///1919810///] // "2"
[ERROR]         // "fallback" is undefined
```

方便起见，还允许使用一个 `string[]` 类型的 JSON 数组描述密码组：

```json
["114514", "114514", "1919810"]
```

展现与上例相同。（废弃模数循环，这样一来 `uniqueId` 将与 `id` 完全相同）

这似乎是个很好的 idea，考虑在 v3.0.0 版本中将其实现，同时继续维护 v2.x 版本。

已实现 JSON 方案，现在还需要一个 v2 到 v3 的密码组配置转换器。

已实现 `ppswitch` 语法，但废除了前面提到的 `default` 语法。同时，`ppswitch` 与现有的 `ppblock` 共用一套 `id` 系统。

```text
[ppblock]AAA[/ppblock]       // id = 0
[ppswitch]                   // id = 1
[case pwd="case1"]BBB[/case]
[case pwd="case2"]CCC[/case]
[/ppswitch]
[ppblock]DDD[/ppblock]       // id = 2
```

`case` 必须具有 `pwd` 属性，否则无论如何都不会显示。

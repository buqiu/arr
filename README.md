# 数组增强
数组增强组件主要是对数组等数据进行处理，如无限级分类操作、商品规格的迪卡尔乘积运算等

### 安装组件

使用 composer 命令安装或下载元代码使用

```php
composer require buqiu/arrays
```

### 功能介绍

##### 根据键名获取数据

如果键名不存在时返回默认值，支持键名的点语法
```php
$d=['a'=>1,'b'=>2];
(new buqiu\Arrays\Arrays())->get($d,'c','没有数据哟');
```
使用点语法查找：
```
$d = ['web' => [ 'id' => 1, 'url' => 'lnmp.org.cn' ]];
(new buqiu\Arrays\Arrays())->get($d,'web.url');
```

#### 排队字段获取数据
以下代码获取除 id、url以外的数据

```php
$d = ['id' => 1,'url' => 'lnmp','name'=>'ken'];
print_r((new buqiu\Arrays\Arrays())->getExtName($d,['id','url']));
```

#### 设置数组元素值支持点语法

```php
$data = (new buqiu\Arrays\Arrays())->set([],'a.b.c',99);
```

#### 改变数组键名大小写

```php
$data = array('name'=>'lnmp',array('url'=>'lnmp.org.cn'));
$data = (new buqiu\Arrays\Arrays())->keyCase($data,1); 
第2个参数为类型： 1 大写  0 小写
```

#### 不区分大小写检测键名是否存

```php
(new buqiu\Arrays\Arrays())->keyExists('K',['K'=>'Kne']);
```

#### 数组值大小写转换

```php
(new buqiu\Arrays\Arrays())->valueCase(['name'=>'lnmp'],1); 
第2个参数为类型： 1 大写  0 小写
```

#### 数组进行整数映射转换

```php
$data = ['status'=>1];
$d = (new buqiu\Arrays\Arrays()))->intToString($data,['status'=>[0=>'关闭',1=>'开启']]); 
```

#### 数组中的字符串数字转为数值类型

```php
$data = ['status'=>'1','click'=>'200'];
$d = (new buqiu\Arrays\Arrays())->stringToInt($data); 
```

#### 根据下标过滤数据元素

```php
$d = [ 'id' => 1, 'url' => 'lnmp.org.cn','title'=>'Ken' ];
print_r((new buqiu\Arrays\Arrays())->filterKeys($d,['id','url']));
//过滤 下标为 id 的元素
```

当第三个参数为 0 时只保留指定的元素
```php
$d = [ 'id' => 1, 'url' => 'lnmp.org.cn','title'=>'ken' ];
print_r((new buqiu\Arrays\Arrays())->filterKeys($d,['id'],0));
//只显示id与title 的元素
```

#### 获得树状结构

```php
(new buqiu\Arrays\Arrays())->tree($data, $title, $fieldPri = 'id', $fieldPid = 'pid');
参数                   	说明
$data                 	数组
$title                	字段名称
$fieldPri             	主键 id
$fieldPid             	父 id
```

#### 获得目录列表

```php
(new buqiu\Arrays\Arrays())->channelList($data, $pid = 0, $html = "&nbsp;", $fieldPri = 'id', $fieldPid = 'pid', $level = 1);
参数                      	说明 
data                 	操作的数组
pid                  	父级栏目的 id 值
html                	栏目名称前缀，用于在视图中显示层次感的栏目列表 
fieldPri              	唯一键名，如果是表则是表的主键
fieldPid              	父 ID 键名
level                 	等级（不需要传参数，系统运行时使用 ) 
```

#### 获得多级目录列表（多维数组）

```php
(new buqiu\Arrays\Arrays())->channelLevel($data, $pid = 0, $html = "&nbsp;", $fieldPri = 'id', $fieldPid = 'pid') 
参数                          	说明
data                      	操作的数组
pid                      	父级栏目的 id 值
html                     	栏目名称前缀，用于在视图中显示层次感的栏目列表
fieldPri                 	唯一键名，如果是表则是表的主键
fieldPid                  	父 ID 键名
```

#### 获得所有父级栏目

```php
(new buqiu\Arrays\Arrays())->parentChannel($data, $sid, $fieldPri = 'id', $fieldPid = 'pid');
参数                          	说明
data                      	操作的数组
sid                      	子栏目
fieldPri                 	唯一键名，如果是表则是表的主键
fieldPid                  	父 ID 键名

```

#### 是否为子栏目

```php
(new buqiu\Arrays\Arrays())->isChild($data, $sid, $pid, $fieldPri = 'cid', $fieldPid = 'pid')
参数                          	说明
data                      	操作的数组
sid                      	子栏目id
pid                      	父栏目id
fieldPri                 	唯一键名，如果是表则是表的主键
fieldPid                  	父 ID 键名
```

#### 是否有子栏目

```php
(new buqiu\Arrays\Arrays())->hasChild($data, $id, $fieldPid = 'pid')
参数                          	说明
data                      	操作的数组
cid                      	栏目id
fieldPid                  	父 ID 键名
```

#### 无限级栏目分类

```php
(new hasChild)->category($categories,$pid = 0,$title = 'title',$id = 'id',$parent_id = 'parent_id')
参数								说明
$categories						操作的数组
$pid								父级编号
$title                  		栏目字段
$id								主键名
$parent_id						父级字段名
```

#### 迪卡尔乘积

```php
(new buqiu())->descarte($arr, $tmp = array())
```


# 使用说明

## 免费接口

1、云端同步

~~~
https://ai-img.ycubbs.cn/api/duanju/list
~~~

2、短剧搜索

~~~
https://ai-img.ycubbs.cn/api/duanju/search?name=赘婿
~~~

3、每日更新

~~~
https://ai-img.ycubbs.cn/api/duanju/daily
~~~

## 夸克命令行脚本

> 注意：使用前先获取夸克cookie保存到当前目录下的cookie.txt

1、自动签到

~~~
php think quark sign

要确保当前目录下cookie.txt有可用cookie
~~~

2、自动转存

~~~
php think quark save

必填参数

--path './quark-save.txt' 转存文件地址，支持txt、csv、xlsx、xls格式

可选参数

--fid '转存目录的fid' 为空则需要手动选择转存目录

--explode '>>>' 指定txt文件分隔符，默认为空格分割
~~~

3、自动分享

> 注意：分享前需要先执行4、同步目录

~~~
php think quark share

可选参数

--fid '分享目录的fid' 为空则需要手动选择分享目录

--type 'repeat' 重复性检测，为空则不进行重复检测
~~~

4、同步目录

~~~
php think quark syn_dir

可选参数

--fid '同步目录的fid' 为空则需要手动选择同步目录
~~~

5、自动同步更新

~~~
php think quark auto

可选参数

--fid '同步目录的fid' 为空则需要手动选择同步目录

--update 'all' 更新类型，为空默认更新当日新增。可选值为：all、daily
--name '赘婿' 搜索短剧并转存，与--update 二选一

--type 'check' 仅输出每日待更新数据，不进行自动更新。可用来检查今日待更新内容
--type 'repeat' 重复性检测，为空则不进行重复检测
~~~
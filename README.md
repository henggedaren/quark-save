# 夸克网盘自动转存

夸克网盘签到、自动转存、自动分享一条龙。

配合定期任务执行食用更佳。🥳

## 声明

本程序为个人兴趣开发，开源仅供学习与交流使用。

程序没有任何破解行为，只是对于夸克已有的API进行封装，所有数据来自于夸克官方API，本人不对网盘内容负责、不对夸克官方API未来可能的改动导致的后果负责。

任何单位或个人因下载使用此软件而产生的任何意外、疏忽、合约毁坏、诽谤、版权或知识产权侵犯及其造成的损失 (包括但不限于直接、间接、附带或衍生的损失等)，本人不承担任何法律责任。

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
php ./QuarkService.php --options sign

要确保当前目录下cookie.txt有可用cookie
~~~

2、自动转存

~~~
php ./QuarkService.php --options save

必填参数

--path './quark-save.txt' 转存文件地址，支持txt、csv、xlsx、xls格式

可选参数

--fid '转存目录的fid' 为空则需要手动选择转存目录

--explode '>>>' 指定txt文件分隔符，默认为空格分割
~~~

3、自动分享

> 注意：分享前需要先执行4、同步目录

~~~
php ./QuarkService.php --options share

可选参数

--fid '分享目录的fid' 为空则需要手动选择分享目录

--type 'repeat' 重复性检测，为空则不进行重复检测
~~~

4、同步目录

~~~
php ./QuarkService.php --options syn_dir

可选参数

--fid '同步目录的fid' 为空则需要手动选择同步目录
~~~

5、自动同步更新

~~~
php ./QuarkService.php --options auto

可选参数

--fid '同步目录的fid' 为空则需要手动选择同步目录

--update 'all' 更新类型，为空默认更新当日新增。可选值为：all、daily
--name '赘婿' 搜索短剧并转存，与--update 二选一

--type 'check' 仅输出每日待更新数据，不进行自动更新。可用来检查今日待更新内容
--type 'repeat' 重复性检测，为空则不进行重复检测
~~~

## windows批量处理脚本

> 可直接双击运行对应的.bat文件

## 沟通交流

使用中您有任何问题可以进群交流：331446855 <a target="_blank" href="https://qm.qq.com/cgi-bin/qm/qr?k=alwjBo-4oy8uA3dN6m9xuevF9hxPn2Mg&jump_from=webapi"><img border="0" src="//pub.idqqimg.com/wpa/images/group.png" alt="技术交流" title="技术交流"></a>

## 打赏

如果这个项目让你受益，你可以打赏我1块钱，让我知道开源有价值。谢谢！


![微信打赏](https://files.ycubbs.cn/image/public/wx-dashang.png)

![支付宝打赏](https://files.ycubbs.cn/image/public/zfb-dashang.png)
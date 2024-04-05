<?php
declare(strict_types=1);

@include './vendor/autoload.php';
include './Quark.php';

use quark\Quark;
use Dcat\EasyExcel\Excel;

class QuarkService
{
    private $options;

    public function execute()
    {
        // 检查是否安装 Console_Table 组件 ，此组件用于在命令行中输出表格
        $composer = @file_get_contents('./composer.json');
        $composer = $composer === false ? [] : json_decode( $composer, true);
        if (!isset($composer['require']['pear/console_table'])) {
            fwrite(STDOUT, 'it found that you have not install [pear/console_table]?Y/N' . PHP_EOL);
            $answer = strtolower(trim(fread(STDIN, 1024), PHP_EOL));
            if ($answer == 'y' || $answer == 'yes') {
                exec('composer require pear/console_table');
            }
        }

        if (!isset($composer['require']['yzh52521/easy-excel'])) {
            fwrite(STDOUT, 'it found that you have not install [yzh52521/easy-excel]?Y/N' . PHP_EOL);
            $answer = strtolower(trim(fread(STDIN, 1024), PHP_EOL));
            if ($answer == 'y' || $answer == 'yes') {
                exec('composer require yzh52521/easy-excel');
            }
        }

        $short_options = "";
        $long_options  = [
            'options:',
            'explode:',
            'path:',
            'fid:',
            'type:',
            'update:',
            'name:'
        ];
        $this->options = getopt($short_options, $long_options);

        // 获取第一个参数
        $argument_options = $this->options['options'] ?? '';
        // 指令输出
        switch ($argument_options) {
            case 'sign':
                $this->writeln("开始自动签到。");
                $this->sign();
                break;
            case 'save':
                $this->writeln("开始转存资源。");
                $this->save();
                break;
            case 'share':
                $this->writeln("开始分享资源。");
                $this->share();
                break;
            case 'syn_dir':
                $this->writeln("开始同步目录。");
                $this->synDir();
                break;
            case 'auto':
                $this->writeln("开始自动同步网盘。");
                $this->auto();
                break;
            default:
                $this->writeln("--options 未指定参数：sign、save、share、syn_dir、auto");
        }
    }

    private function sign()
    {
        $cookie = $this->getCookie();

        // 签到
        $quarkClass = new Quark();
        $quarkClass->cookie = $cookie;
        $account_info = $quarkClass->getAccountInfo();

        if($account_info === false){
            $this->writeln("cookie无效");
            exit();
        }

        $is_sign = $quarkClass->doGrowthSign();
        if($is_sign === false){
            $this->writeln("自动签到失败");
        }else{
            $this->writeln("自动签到成功");
        }

        $growth_info = $quarkClass->getGrowthInfo();
        if($growth_info === false){
            $this->writeln("获取签到信息失败");
            exit();
        }

        $account_info = formatQuarkAccountInfo(array_merge($account_info, $growth_info));

        $header = [
            'nick_name'      => '昵称',
            'member_type'    => '会员类型',
            'sign_daily'     => '是否签到',
            'cur_total_sign_day' => '签到天数',
            'total_capacity' => '总容量',
            'use_capacity'   => '使用容量',
        ];

        $user_data[] = [
            'nick_name'      => $account_info['nick_name'],
            'member_type'    => $account_info['member_type'],
            'sign_daily'     => $account_info['sign_daily'] == 1 ? '已签到' : '未签到',
            'cur_total_sign_day' => $account_info['cur_total_sign_day'],
            'total_capacity' => $account_info['total_capacity'],
            'use_capacity'   => $account_info['use_capacity'],
        ];

        $table = new Console_Table();
        $table->setHeaders($header);
        $table->addData($user_data);

        $this->writeln($table->getTable());
    }

    private function save()
    {
        $cookie = $this->getCookie();

        ini_set('memory_limit', '300M');
        $file_extension_arr = ['txt','csv','xlsx','xls'];

        $explode = $this->options['explode'] ?? ' ';

        // 文件地址
        $path = $this->options['path'] ?? '';
        if(!$path){
            $this->writeln("请指定转存文件地址");
            exit();
        }

        $file_info = pathinfo($path);
        $file_extension = $file_info['extension'] ?? '';
        if(!in_array($file_extension, $file_extension_arr)){
            $this->writeln("仅支持文件格式：".implode('、', $file_extension_arr));
            exit();
        }

        $save_arr = [];
        if($file_extension == 'txt'){
            if (!file_exists($path)) {
                $this->writeln("转存文件不存在");
                exit();
            }

            $f_fp = fopen($path, "r");
            while (($line = fgets($f_fp)) !== false) {
                if($line){
                    $arr = explode($explode, $line);
                    if(count($arr) >= 2){
                        // 是否有夸克链接
                        if (strpos($arr[1],'pan.quark.cn') !== false){
                            $save_arr[] = [
                                'name' => $arr[0],
                                'url'  => $arr[1],
                            ];
                        }
                    }
                }
            }
            fclose($f_fp);
        }else{
            $allSheets = Excel::import($path)
                ->headings(false)
                ->sheet(0)
                ->toArray();

            foreach ($allSheets as $val){
                if(count($val) >= 2){
                    // 是否有夸克链接
                    if (strpos($val[1],'pan.quark.cn') !== false){
                        $save_arr[] = [
                            'name' => $val[0],
                            'url'  => $val[1],
                        ];
                    }
                }
            }
        }

        $save_count = count($save_arr);
        if($save_count == 0){
            $this->writeln("转存文件内无内容，请检查格式。");
            exit();
        }

        // 开始转存前指定转存目录
        $fid = $this->options['fid'] ?? '';

        if(!$fid){
            $fid_info = $this->getFidInfoByRootDir();
            $fid = $fid_info['fid'];
        }

        foreach ($save_arr as $key=>$save){
            $idx = $key + 1;

            $quarkClass = new Quark();
            $quarkClass->cookie = $cookie;
            $res = $quarkClass->saveShare($fid, $save['url']);
            if($res[0] === true){
                $this->writeln(date('Y-m-d H:i:s',time())."---{$idx}/{$save_count}---【{$save['name']}】=>转存成功");
                sleep(1);
            }else{
                $this->writeln(date('Y-m-d H:i:s',time())."---{$idx}/{$save_count}---【{$save['name']}】=>转存失败【". $res[1] ?? '未知' ."】");
            }
        }

        $this->writeln(date('Y-m-d H:i:s',time())."---转存完毕");
    }

    private function share()
    {
        $cookie = $this->getCookie();
        $type = $this->options['type'] ?? '';
        $sleep = 3;

        // 开始分享前指定分享目录
        $fid = $this->options['fid'] ?? '';

        if(!$fid){
            $fid_info = $this->getFidInfoByRootDir();
            $fid = $fid_info['fid'];
        }

        $syn_dir = './quark-dir-'.$fid.'.txt';
        if(!file_exists($syn_dir)){
            $this->writeln("分享目录为空，请先执行：--options syn_dir 同步目录");
            exit();
        }

        $data = [];
        $f_fp = fopen($syn_dir, "r");
        while (($line = fgets($f_fp)) !== false) {
            if($line){
                $arr = explode('>>>', trim($line));
                $data[] = [
                    'name' => trim($arr[0]),
                    'fid'  => trim($arr[1])
                ];
            }
        }
        fclose($f_fp);

        // 重复性检测
        $syn_share_list = [];
        if($type == 'repeat'){
            $this->writeln("进行重复性检测");
            $share_list = $this->synShareList();
            foreach ($share_list as $k=>$v){
                $syn_share_list[trim($v['name'])] = $v;
            }
            unset($share_list);
        }

        $count = count($data);
        $success = 0;
        $fail = 0;
        $repeat = 0;

        $save_name = './quark-share-'.$fid.'.txt';
        $f_fp = fopen($save_name,'w');

        foreach ($data as $key=>$list){
            $idx = $key + 1;

            if($type == 'repeat'){
                if(isset($syn_share_list[$list['name']])){
                    $this->writeln(date('Y-m-d H:i:s',time())."---{$idx}/{$count}---【{$list['name']}】=>重复分享");
                    $repeat++;
                    fwrite($f_fp,$list['name'].'>>>'.$syn_share_list[$list['name']]['share_url']."\r\n");
                    continue;
                }
            }

            $quarkClass = new Quark();
            $quarkClass->cookie = $cookie;
            $share_res = $quarkClass->share([$list['fid']], $list['name'], 1, 1);

            if($share_res[0] === false){
                $fail++;
                $this->writeln(date('Y-m-d H:i:s',time())."---{$idx}/{$count}---【{$list['name']}】=>分享失败：".$share_res[1] ?? '');
            }else{
                $success++;
                fwrite($f_fp,$list['name'].'>>>'.$share_res[1]['share_url']."\r\n");
                $this->writeln(date('Y-m-d H:i:s',time())."---{$idx}/{$count}---【{$list['name']}】=>分享成功：".$share_res[1]['share_url'] ?? '');
                sleep((int)$sleep);
            }
        }
        fclose($f_fp);

        $this->writeln("共需分享：{$count}条，分享成功：{$success}条，失败：{$fail}条，重复：{$repeat}条");
    }

    private function synDir()
    {
        $cookie = $this->getCookie();
        $fid = $this->options['fid'] ?? '';

        if(!$fid){
            $fid_info = $this->getFidInfoByRootDir();
            $fid = $fid_info['fid'];
        }

        $size = 500;
        $sleep = 3;

        $quarkClass = new Quark();
        $quarkClass->cookie = $cookie;

        $page = 1;
        $dir = $quarkClass->getDirByFid($fid,$size,$page);

        if($dir === false){
            $this->writeln("请求出错，请检测CK");
            exit();
        }

        if(!isset($dir['data']['list']) || empty($dir['data']['list'])){
            $this->writeln("同步目录为空");
            exit();
        }

        $insert_dir = [];
        foreach ($dir['data']['list'] as $list){
            $insert_dir[] = [
                'name'  => $list['file_name'] ?? '',
                'fid'   => $list['fid'] ?? '',
            ];
        }

        $total = $dir['metadata']['_total'];
        $pages = ceil($total/$size);
        $this->writeln("需同步：{$pages}页数据，当前第{$page}页,共{$total}个数据");

        for ($i=0;$i<$pages;$i++){
            $this_page = $i+1;
            if($this_page > 1){
                $page++;

                $this->writeln("需同步：{$pages}页数据，当前第{$this_page}-{$page}页,共{$total}个数据");

                $quarkClass = new Quark();
                $quarkClass->cookie = $cookie;
                $dir = $quarkClass->getDirByFid($fid,$size,$this_page);

                if($dir === false){
                    $this->writeln("请求出错，请检测CK");
                    continue;
                }

                if(!isset($dir['data']['list'])){
                    $this->writeln("同步目录为空");
                    continue;
                }

                foreach ($dir['data']['list'] as $list){
                    $insert_dir[] = [
                        'name'  => $list['file_name'] ?? '',
                        'fid'   => $list['fid'] ?? '',
                    ];
                }

                $count = count($insert_dir);
                $this->writeln("已同步{$count}条数据，等待{$sleep}秒");
                sleep((int)$sleep);
            }
        }

        $save_name = './quark-dir-'.$fid.'.txt';
        $f_fp = fopen($save_name,'w');
        foreach ($insert_dir as $value){
            fwrite($f_fp,implode(">>>", $value) . "\r\n");
        }
        fclose($f_fp);

        $this->writeln("同步数据完成");
    }

    private function auto()
    {
        $cookie = $this->getCookie();

        $update = $this->options['update'] ?? 'daily';
        $name   = $this->options['name'] ?? '';
        $type   = $this->options['type'] ?? '';

        $fid = $this->options['fid'] ?? '';
        if(!$fid){
            $fid_info = $this->getFidInfoByRootDir();
            $fid = $fid_info['fid'];
        }

        if($update == 'all'){
            $url = 'https://ai-img.ycubbs.cn/api/duanju/list';
        }else if ($update == 'daily'){
            $url = 'https://ai-img.ycubbs.cn/api/duanju/daily';
        }else if(!empty($name)){
            $url = 'https://ai-img.ycubbs.cn/api/duanju/search?name='.$name;
        }else{
            $url = 'https://ai-img.ycubbs.cn/api/duanju/list';
        }

        $res = curl_get($url);
        $res = (array)json_decode($res,true);

        if($res['code'] == 0){
            $this->writeln("接口请求错误");
            exit();
        }

        $data = [];
        foreach ($res['data'] as $val){
            $data[] = [
                'name' => $val['name'],
                'url'  => $val['url']
            ];
        }

        $count = count($data);
        if($count == 0){
            $this->writeln("待同步数据为空");
            exit();
        }

        // 重复性检测
        $syn_dir_list = [];
        if($type == 'repeat'){
            $this->writeln("进行重复性检测");

            $dir_path = './quark-dir-'.$fid.'.txt';
            $this->options['fid'] = $fid;
            $this->synDir();

            $f_fp = fopen($dir_path,'r');
            while (($line = fgets($f_fp)) !== false) {
                if($line){
                    $arr = explode('>>>', trim($line));
                    $syn_dir_list[trim($arr[0])] = [
                        'name' => trim($arr[0]),
                        'fid'  => trim($arr[1])
                    ];
                }
            }
            fclose($f_fp);
        }

        $success = 0;
        $fail = 0;
        $repeat = 0;
        foreach ($data as $key=>$value){
            $idx = $key + 1;

            if($type == 'check'){
                continue;
            }
            if($type == 'repeat'){
                if(isset($syn_dir_list[$value['name']])){
                    $this->writeln(date('Y-m-d H:i:s',time())."---{$idx}/{$count}---【{$value['name']}】=>重复转存");
                    $repeat++;
                    continue;
                }
            }

            $quarkClass = new Quark();
            $quarkClass->cookie = $cookie;

            $res = $quarkClass->saveShare($fid, $value['url']);
            if($res[0] === true){
                $success++;
                $this->writeln(date('Y-m-d H:i:s',time())."---{$idx}/{$count}---【{$value['name']}】=>转存成功");
                sleep(1);
            }else{
                $fail++;
                $this->writeln(date('Y-m-d H:i:s',time())."---{$idx}/{$count}---【{$value['name']}】=>转存失败：". $res[1] ?? '');
            }
        }

        $this->writeln("获取到{$count}个短剧，转存成功：{$success}个，失败：{$fail}个，重复：{$repeat}个");
    }

    private function writeln($message)
    {
        echo $message . PHP_EOL;
    }

    private function getCookie()
    {
        $cookie = file_get_contents('./cookie.txt');

        if(empty($cookie)){
            $this->writeln("当前目录下【cookie.txt】为空");
            exit();
        }

        return $cookie;
    }

    private function getFidInfoByRootDir()
    {
        $fid_info = [
            'fid'  => 0,
            'name' => ''
        ];
        $cookie = $this->getCookie();

        $quarkClass = new Quark();
        $quarkClass->cookie = $cookie;
        $root_list = $quarkClass->getDirByFid(0,50,1);

        if($root_list === false){
            $this->writeln('获取账号根目录失败');
            exit();
        }

        $root_list = $root_list['data']['list'] ?? [];
        if(empty($root_list)){
            $this->writeln('账号根目录为空！');
            exit();
        }

        $header = [
            'id'      => '序号',
            'name'    => '目录名',
            'fid'     => 'FID',
        ];

        $arr = [];
        foreach ($root_list as $key=>$value){
            if($value['dir'] === true && $value['ban'] === false){
                $arr[] = [
                    'id'      => $key + 1,
                    'name'    => $value['file_name'],
                    'fid'     => $value['fid'],
                ];
            }
        }

        $table = new Console_Table();
        $table->setHeaders($header);
        $table->addData($arr);

        $this->writeln($table->getTable());
        fwrite(STDOUT, '请选择目录序号：' . PHP_EOL);

        $answer = intval(trim(fread(STDIN, 1024), PHP_EOL));

        foreach ($arr as $v){
            if($v['id'] === $answer){
                $fid_info = $v;
                break;
            }
        }

        return $fid_info;
    }

    private function synShareList(): array
    {
        $cookie = $this->getCookie();

        $size = 500;
        $sleep = 3;

        $quarkClass = new Quark();
        $quarkClass->cookie = $cookie;

        $page = 1;
        $list = $quarkClass->getShareList($size,$page);

        if($list === false){
            $this->writeln("请求出错，请检测CK");
            return [];
        }

        if(!isset($list['data']['list'])){
            $this->writeln("分享文件为空");
            return [];
        }

        $share_list = [];
        foreach ($list['data']['list'] as $list){
            $share_list[] = [
                'name'       => $list['title'] ?? '',
                'first_fid'  => $list['first_fid'] ?? '',
                'path_info'  => $list['path_info'] ?? '',
                'share_id'   => $list['share_id'] ?? '',
                'share_url'  => $list['share_url'] ?? '',
                'status'     => $list['status']
            ];
        }

        $total = $list['metadata']['_total'];
        $pages = ceil($total/$size);
        $this->writeln("分享链接共：{$pages}页数据，当前第{$page}页,共{$total}个数据");

        for ($i=0;$i<$pages;$i++){
            $this_page = $i+1;
            if($this_page > 1){
                $page++;

                $this->writeln("分享链接共：{$pages}页数据，当前第{$this_page}-{$page}页,共{$total}个数据");

                $quarkClass = new Quark();
                $quarkClass->cookie = $cookie;
                $list = $quarkClass->getShareList($size,$page);

                if($list === false){
                    $this->writeln("请求出错，请检测CK");
                    break;
                }

                if(!isset($list['data']['list'])){
                    $this->writeln("分享文件为空");
                    break;
                }

                foreach ($list['data']['list'] as $list){
                    $share_list[] = [
                        'name'       => $list['title'] ?? '',
                        'first_fid'  => $list['first_fid'] ?? '',
                        'path_info'  => $list['path_info'] ?? '',
                        'share_id'   => $list['share_id'] ?? '',
                        'share_url'  => $list['share_url'] ?? '',
                        'status'     => $list['status']
                    ];
                }

                $c = count($share_list);
                $this->writeln("已同步{$c}条数据，等待{$sleep}秒");
                sleep((int)$sleep);
            }
        }

        return $share_list;
    }
}

$quarkService = new QuarkService();
$quarkService->execute();
<?php
namespace quark;

include './common.php';

class Quark{

    private $headers = [
        'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'accept: application/json, text/plain, */*',
        'content-type: application/json',
        'sec-ch-ua-mobile: ?0',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'sec-ch-ua-platform: "Windows"',
        'origin: https://pan.quark.cn',
        'sec-fetch-site: same-site',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://pan.quark.cn/',
        'accept-encoding: gzip, deflate, br',
        'accept-language: zh-CN,zh;q=0.9',
    ];

    public $cookie = '';

    private function setCookie()
    {
        $found = false;
        foreach ($this->headers as $value) {
            if (strpos('cookie', $value) !== false) {
                $found = true;
                break;
            }
        }
        if($found === false){
            $this->headers[] = "cookie: {$this->cookie}";
        }
    }
    /*
     *  获取网盘目录，如果为0，则获取根目录
     */
    public function getDirByFid($fid = 0, $size = 20, $page = 1)
    {
        $url = "https://drive-pc.quark.cn/1/clouddrive/file/sort";

        $param = [
            "pr" => "ucpro",
            "fr" => "pc",
            "uc_param_str" => "",
            "pdir_fid" => $fid,
            "_page" => $page,
            "_size" => $size,
            "_fetch_total" => 1,
            "_fetch_sub_dirs" => 0,
            "_sort" => "file_type:asc,file_name:asc"
        ];
        $url = $url .'?'. http_build_query($param);

        $this->setCookie();
        $res = curl_get($url,$this->headers);
        $res = json_decode($res,true);

        if($res && $res['status'] === 200 && $res['code'] == 0){
            return $res;
        }else{
            return  false;
        }
    }

    /*
     * 分享指定目录或文件
     */
    public function share($fid_list,$title,$expired_type,$url_type,$passcode='')
    {
        $url = 'https://drive-pc.quark.cn/1/clouddrive/share';

        $querystring = [
            "pr" => "ucpro",
            "fr" => "pc",
            "uc_param_str" => "",
            "__dt" => rand(100, 999),
            "__t" => getMilliseconds(),

        ];

        $url = $url . '?'.http_build_query($querystring);

        $post = [
            "fid_list" => $fid_list,
            "title" => $title,
            "expired_type" => $expired_type,
            "url_type" => $url_type,
        ];

        if(!empty($passcode)){
            $post['passcode'] = $passcode;
        }

        $this->setCookie();
        $res = curl_post($url,json_encode($post),$this->headers);
        $res = json_decode($res,true);

        // 获取到task_id
        if($res && $res['status'] == 200 && $res['code'] == 0){

            sleep(1);

            $task_id = $res['data']['task_id'];

            $task_url = 'https://drive-pc.quark.cn/1/clouddrive/task?pr=ucpro&fr=pc&uc_param_str=&task_id='.$task_id.'&retry_index=0';
            $task_res = curl_get($task_url,$this->headers);
            $task_res = json_decode($task_res,true);

            $share_id = '';
            if($task_res && $task_res['status'] == 200 && $task_res['code'] == 0){
                if(isset($task_res['data']['share_id'])){
                    $share_id = $task_res['data']['share_id'];
                }
            }

            sleep(1);

            if(empty($share_id)){
                $task2_url = 'https://drive-pc.quark.cn/1/clouddrive/task?pr=ucpro&fr=pc&uc_param_str=&task_id='.$task_id.'&retry_index=1';
                $task2_res = curl_get($task2_url,$this->headers);
                $task2_res = json_decode($task2_res,true);

                if($task2_res && $task2_res['status'] == 200 && $task2_res['code'] == 0){
                    if(isset($task2_res['data']['share_id'])){
                        $share_id = $task2_res['data']['share_id'];
                    }
                }
            }
            if(empty($share_id)){
                $task3_url = 'https://drive-pc.quark.cn/1/clouddrive/task?pr=ucpro&fr=pc&uc_param_str=&task_id='.$task_id.'&retry_index=2';
                $task3_url = curl_get($task3_url,$this->headers);
                $task3_url = json_decode($task3_url,true);

                if($task3_url && $task3_url['status'] == 200 && $task3_url['code'] == 0){
                    if(isset($task3_url['data']['share_id'])){
                        $share_id = $task3_url['data']['share_id'];
                    }
                }
            }
            if(empty($share_id)){
                $task4_url = 'https://drive-pc.quark.cn/1/clouddrive/task?pr=ucpro&fr=pc&uc_param_str=&task_id='.$task_id.'&retry_index=3';
                $task4_url = curl_get($task4_url,$this->headers);
                $task4_url = json_decode($task4_url,true);

                if($task4_url && $task4_url['status'] == 200 && $task4_url['code'] == 0){
                    if(isset($task4_url['data']['share_id'])){
                        $share_id = $task4_url['data']['share_id'];
                    }
                }
            }
            if(!empty($share_id)){
                // 获取分享链接
                $share_url = 'https://drive-pc.quark.cn/1/clouddrive/share/password?pr=ucpro&fr=pc&uc_param_str=';

                $share_post = [
                    'share_id' => $share_id
                ];

                $share_res = curl_post($share_url,json_encode($share_post),$this->headers);

                $share_res = json_decode($share_res,true);

                if($share_res && $share_res['code'] == 0 && $share_res['status'] == 200){
                    return [true,array_merge((array)$share_res['data'], $share_post)];
                }else{
                    return [false,'获取share_url失败'];
                }
            }else{
                return [false,'获取share_id失败'];
            }
        }else{
            return [false,'获取task_id失败'];
        }
    }

    /*
     * 转存分享链接
     */
    public function saveShare($fid, $share_url)
    {
        // 这里读取链接的fid和提取码
        list($pwd_id, $pdir_fid, $passcode) = $this->getIdFromUrl($share_url);

        $stoken_res = $this->getStoken($pwd_id,$passcode);

        if($stoken_res[0] === false){
            return [false,'stoken获取失败'];
        }
        $stoken = $stoken_res[1];

        // 保存的分享链接可能是多个文件夹或文件，所以需要循环处理一下。
        // 先获取当前分享目录
        $share_file_list = $this->getDetail($pwd_id, $stoken, $pdir_fid);
        if(empty($share_file_list)){
            return [false,'分享目录为空'];
        }

        $fid_list = [];
        $fid_token_list = [];
        foreach ($share_file_list as $save_list){
            $fid_list[] = $save_list['fid'];
            $fid_token_list[] = $save_list['share_fid_token'];
        }

        $url = "https://drive.quark.cn/1/clouddrive/share/sharepage/save";

        $querystring = [
            "pr" => "ucpro",
            "fr" => "pc",
            "uc_param_str" => "",
            "__dt" => rand(100, 999),
            "__t" => getMilliseconds(),
        ];

        $url = $url . '?'.http_build_query($querystring);

        $post = [
            "fid_list" => $fid_list,
            "fid_token_list" => $fid_token_list,
            "to_pdir_fid" => $fid,
            "pwd_id" => $pwd_id,
            "stoken" => $stoken,
            "pdir_fid" => "0",
            "scene" => "link",
        ];

        $this->setCookie();
        $res = curl_post($url,json_encode($post),$this->headers);
        $res = json_decode($res,true);
        if($res && $res['status'] == 200 && $res['code'] == 0){
            return [true,$res['data']];
        }else{
            return [false,$res['message'] ?? ''];
        }
    }

    /*
     * 查询task_id是否执行成功，主要是转存时查询
     */
    public function queryTask($task_id): array
    {
        $url = 'https://drive-pc.quark.cn/1/clouddrive/task';

        $querystring = [
            "pr" => "ucpro",
            "fr" => "pc",
            "uc_param_str" => "",
            "task_id" => $task_id,
            "retry_index" => "1",
            "__dt" => rand(100, 999),
            "__t" => getMilliseconds(),
        ];

        $url = $url . '?'.http_build_query($querystring);

        $this->setCookie();
        $res = curl_get($url,$this->headers);
        $res = json_decode($res,true);

        if($res && $res['status'] == 200 && $res['code'] == 0){
            return [true,$res];
        }else{
            if(isset($res['code']) && $res['code'] == 32003){
                $res['message'] = '容量限制';
            }
            return [false,$res];
        }
    }

    /*
     *  获取账号昵称，可以用来检测cookie是否有效
     */
    public function getAccountInfo()
    {
        $url = 'https://pan.quark.cn/account/info';

        $querystring = [
            "platform" => "pc",
            "fr" => "pc",
        ];

        $url = $url . '?'.http_build_query($querystring);

        $this->setCookie();
        $res = curl_get($url,$this->headers);
        $res = json_decode($res,true);

        if($res && $res['success'] === true){
            return $res['data'];
        }else{
            return  false;
        }
    }

    /*
     *  用来获取今日是否签到
     */
    public function getGrowthInfo()
    {
        $url = 'https://drive-m.quark.cn/1/clouddrive/capacity/growth/info';

        $querystring = [
            "pr" => "ucpro",
            "fr" => "pc",
            "uc_param_str" => ''
        ];

        $url = $url . '?'.http_build_query($querystring);

        $this->setCookie();
        $res = curl_get($url,$this->headers);
        $res = json_decode($res,true);

        if($res && $res['status'] === 200 && $res['code'] == 0){
            return $res['data'];
        }else{
            return  false;
        }
    }

    /*
     * 进行今日签到
     */
    public function doGrowthSign()
    {
        $url = 'https://drive-m.quark.cn/1/clouddrive/capacity/growth/sign';

        $querystring = [
            "pr" => "ucpro",
            "fr" => "pc",
            "uc_param_str" => ''
        ];

        $url = $url . '?'.http_build_query($querystring);

        $post = [
            'sign_cyclic' => True
        ];

        $this->setCookie();
        $res = curl_post($url,json_encode($post),$this->headers);
        $res = json_decode($res,true);
        if($res && $res['status'] === 200 && $res['code'] == 0){
            return $res['data'];
        }else{
            return  false;
        }
    }

    public function isDir($fid): array
    {
        $file_list = [];
        $page = 1;
        $size = 500;

        while (true) {
            $response = $this->getDirByFid($fid, $size, $page);

            if($response === false){
                break;
            }else{
                if (!empty($response["data"]["list"])) {
                    $file_list = array_merge($file_list, $response["data"]["list"]);
                    $page++;
                    sleep(1);
                } else {
                    break;
                }
            }

            if (count($file_list) >= $response["metadata"]["_total"]) {
                break;
            }
        }

        return $file_list;
    }

    /*
     * 删除文件或目录
     */
    public function delete($file_list)
    {
        $url = 'https://drive-pc.quark.cn/1/clouddrive/file/delete';

        $querystring = [
            "pr" => "ucpro",
            "fr" => "pc",
            "uc_param_str" => ''
        ];

        $url = $url . '?'.http_build_query($querystring);

        $post = [
            'action_type'  => 2,
            'exclude_fids' => [],
            'filelist'     => $file_list
        ];

        $this->setCookie();;
        $res = curl_post($url,json_encode($post),$this->headers);
        $res = json_decode($res,true);

        if($res && $res['status'] === 200 && $res['code'] == 0){
            return $res['data'];
        }else{
            return  false;
        }
    }

    /*
     *  获取网盘分享链接
     */
    public function getShareList($size = 20, $page = 1)
    {
        $url = "https://drive-pc.quark.cn/1/clouddrive/share/mypage/detail";

        $param = [
            "pr" => "ucpro",
            "fr" => "pc",
            "uc_param_str" => "",
            "_page" => $page,
            "_size" => $size,
            "_order_field" => "created_at",
            "_order_type"  => "desc",
            "_fetch_total" => 1,
            "_fetch_notify_follow" => 1
        ];
        $url = $url .'?'. http_build_query($param);

        $this->setCookie();
        $res = curl_get($url,$this->headers);
        $res = json_decode($res,true);

        if($res && $res['status'] === 200 && $res['code'] == 0){
            return $res;
        }else{
            return  false;
        }
    }

    private function getIdFromUrl($url): array
    {
        $pwd_id = '';
        $pdir_fid = 0;
        $passcode = '';

        $pattern = "/\/s\/(\w+)(#\/list\/share.*\/(\w+))?/";
        if (preg_match($pattern, $url, $matches)) {
            $pwd_id = $matches[1];
            $pdir_fid = $matches[3] ?? 0;
        }

        $pattern2 = "/提取码[:：](\S+\d{1,4}\S*)/";
        if (preg_match($pattern2, $url, $matches)) {
            $passcode = $matches[1];
        }

        return array($pwd_id, $pdir_fid,$passcode);
    }

    private function getStoken($pwd_id,$passcode): array
    {
        $url = 'https://pan.quark.cn/1/clouddrive/share/sharepage/token';

        $querystring = [
            "pr" => "ucpro",
            "fr" => "pc",
            "uc_param_str" => "",
            "__dt" => rand(100, 999),
            "__t" => getMilliseconds(),

        ];

        $url = $url . '?'.http_build_query($querystring);

        $post = [
            'passcode' => $passcode,
            'pwd_id'   => $pwd_id
        ];

        $this->setCookie();
        $res = curl_post($url,json_encode($post),$this->headers);
        $res = json_decode($res,true);

        if($res && $res['status'] == 200 && $res['code'] == 0){
            return array(true,$res['data']['stoken']);
        }else{
            return array(false,$res['message'] ?? '');
        }
    }

    private function getDetail($pwd_id, $stoken, $pdir_fid): array
    {
        $file_list = [];
        $page = 1;

        while (true) {
            $url = "https://pan.quark.cn/1/clouddrive/share/sharepage/detail";
            $querystring = http_build_query([
                "pr" => "ucpro",
                "fr" => "pc",
                "pwd_id" => $pwd_id,
                "stoken" => $stoken,
                "pdir_fid" => $pdir_fid,
                "force" => "0",
                "_page" => $page,
                "_size" => "50",
                "_fetch_banner" => "0",
                "_fetch_share" => "0",
                "_fetch_total" => "1",
                "_sort" => "file_type:asc,updated_at:desc"
            ]);

            $url = $url . '?' . $querystring;

            $this->setCookie();
            $res = curl_get($url,$this->headers);
            $res = json_decode($res, true);

            if($res && $res['code'] == 0 && $res['status'] == 200){
                if (!empty($res["data"]["list"])) {
                    $file_list = array_merge($file_list, $res["data"]["list"]);
                    $page++;
                } else {
                    break;
                }

                if (count($file_list) >= $res["metadata"]["_total"]) {
                    break;
                }
            }else{
                break;
            }
        }

        return $file_list;
    }
}
<?php

function getMilliseconds() {
    list($msec, $sec) = explode(' ', microtime());
    return (int)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
}

function formatQuarkAccountInfo($data)
{
    return [
        'nick_name' => $data['nickname'],
        'avatar'    => $data['avatarUri'],
        'sign_daily'=> $data['cap_sign']['sign_daily'] ? 1 : 0,
        'cur_total_sign_day' => $data['cap_growth']['cur_total_sign_day'] ?? 0,
        'total_capacity' => formatBytes($data['total_capacity']),
        'use_capacity'   => formatBytes($data['use_capacity']),
        'cur_total_cap'  => formatBytes($data['cap_growth']['cur_total_cap']),
        'member_type'    => $data['member_type'],
        'super_vip_exp_at' => isset($data['super_vip_exp_at']) ? $data['super_vip_exp_at']/1000 : 0,
    ];
}

function formatBytes($bytes)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $unit = 0;

    while ($bytes >= 1024 && $unit < count($units) - 1) {
        $bytes /= 1024;
        $unit++;
    }

    return round($bytes, 2) . ' ' . $units[$unit];
}

function curl_get($url, $headers = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    $SSL = substr($url, 0, 8) == "https://";
    if ($SSL) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

function curl_post($url, $data = null, $headers = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FAILONERROR, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
    curl_setopt($curl, CURLOPT_ENCODING, 'UTF-8');
    $SSL = substr($url, 0, 8) == "https://";
    if ($SSL) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
    }
    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
    }
    if (!empty($headers)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }

    $output = curl_exec($curl);
    curl_close($curl);
    return ($output);
}

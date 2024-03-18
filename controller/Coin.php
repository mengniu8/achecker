<?php
/**
 * Created by PhpStorm.
 * User: zhy
 * Date: 2018/12/11
 * Time: 16:06
 */
namespace app\datanec\controller;
use think\facade\View;
use think\facade\Db;


class Coin
{
    public function index()
    {
        $platform = input("param.platform/s");
        return view($platform, [
            'platform' => $platform,
        ]);
    }
    public function test(){
        $data='123';
        if(!strstr($data,'456')){
            echo "456 not in\r\n";

        }
        echo strstr($data,'123');
    }
    public function log_yitai(){
        $db_user='user_'.input("param.platform/s");
        $user=input("param.username/s");
        $yitai=input("param.yitai/s");
        $data=Db::table($db_user)->where('username',$user)->find();
        if(!$data){
            return '用户不存在：'.$user;
        }
        if(!is_numeric($yitai)){
            return '以太数值错误：'.$yitai;
        }
        $yitai=intval($yitai);

        $r=Db::table($db_user)->where('id',$data['id'])->limit(1)->update(['yitai'=>$yitai]);
        return '更新以太：'.$user.'-'.$yitai." 更新数据数量：".$r;
    }
    //查询卡使用时，先查缓存，缓存有就直接返回数据，没有缓存再查card表，查表可用后标记不可用，然后写入缓存。
    public function creat(){
        $db_user='card';
        $username=input("param.username/s");
        $type=input("param.type/s");
        $count=input("param.count/s");
        $times=input("param.times/s")?input("param.times/s"):1;
        $show=input("param.show/s");
        $line=input("?param.line")?input("param.line/b"):true;
        if(empty($type) || !$type || $type==''){
            $type='t';
        }

        $type=strtoupper($type);
        $card_str='';
        $count_redo=0;
        for ($i = 1; $i <= $count; $i++) {
            $card_0=$this->getRandStr(10);
            $card=$type.$card_0;

            $data_check=Db::table($db_user)->where('card',$card)->find();
            if($data_check){
                //该卡号已存在，重新生成
                $i--;
                $count_redo++;
                if($count_redo>2){
                    echo "重复卡号重试次数过多：".$card;
                    return;
                }
                continue;
            }
            if($line){
                $card_str.=$i.':'.$card."\r\n<br>";
            }else{
                $card_str.=$card."\r\n<br>";
            }

            $data=[
                'card'=>$card,
                'type'=>$type,
                'count'=>$times
            ];

            Db::table($db_user)->insert($data);
        }
        //Db::table($db_user)->where("username",$username)->update(['level'=>$level,'times_suc'=>$times_suc]);
        echo "creat cards ok type:{$type}".'-'.$this->get_time();
        if($count==1){
            echo "\r\n<br>\r\n<div style='margin: 20px; text-align: center;'>您的查询卡：<span style='margin: 20px; color: red; font-weight: bold;'>{$card_0}</span><br>查询网站：https://cha.335.im</div>";
        }
        if($show){
            echo "\r\n<br>{$card_str}";
        }
        //echo "\r\n<br>{$card_str}";
    }
    public  function get_time($data_time=false){
        $now = time(); // 获取当前时间戳
        if($data_time && is_int($data_time)){
            if($data_time>time()) $data_time=floor($data_time/1000);
            if($data_time<1000000000) $data_time=time();
            $now=$data_time;
        }
        $formattedTime = date("Y-m-d H:i:s", $now); // 格式化时间戳
        return $formattedTime;
    }
    function getRandStr($length, $chars = '') {
        if (empty($chars)) {
            $chars = 'ABCDEFGHJKLMNPRSTWXYZ';
        }
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
    public function post($url, $param=""){
        $httph =curl_init($url);
        curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($httph, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($httph,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($httph, CURLOPT_HEADER, false);//不返回response头部信息
        curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
        curl_setopt($httph, CURLOPT_POST, 1);//设置为POST方式
        curl_setopt($httph, CURLOPT_POSTFIELDS, $param);
        $rst=curl_exec($httph);
        curl_close($httph);
        return $rst;
    }
    public function get($url){
        $httph =curl_init($url);
        curl_setopt($httph,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($httph, CURLOPT_HEADER, false);//不返回response头部信息
        curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
        $rst=curl_exec($httph);
        curl_close($httph);
        return $rst;
    }
    public  function get_diff_time($start_time,$diff){
        return date("Y-m-d H:i:s", strtotime($diff, strtotime($start_time)));
    }
    public function getIP(){
        static $realip;
        if (isset($_SERVER)){
            if (isset($_SERVER["X-Real-IP"])){
                $realip = $_SERVER["X-Real-IP"].'-X-Real-IP';
            } else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"].'-HTTP_X_FORWARDED_FOR';
            }else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"].'-HTTP_CLIENT_IP';
            } else {
                $realip = $_SERVER["REMOTE_ADDR"].'-REMOTE_ADDR';
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        $regex = "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/";
        $regex_v6 = "/^([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}/";
        $regex_last = "/^(.+?),/";
        // 使用preg_match()函数匹配字符串
        if (preg_match($regex, $realip, $matches)) {
            // 返回匹配的IP地址
            return $matches[0];
        }elseif(preg_match($regex_v6, $realip, $matches)){
            return $matches[0];
        }elseif(preg_match($regex_last, $realip, $matches)){
            return $matches[1];
        }
        return $realip;
    }
}
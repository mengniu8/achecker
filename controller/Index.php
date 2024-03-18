<?php
/**
 * Created by PhpStorm.
 * User: zhy
 * Date: 2018/12/11
 * Time: 16:06
 */
namespace app\datanec\controller;
use think\facade\View;

class Index
{
    public function index()
    {
        echo "hello";
        //$this->daoru();

        //return View::fetch();
        //return $this->fetch();
    }
    public function daoru(){

        $file_='3.txt';
        $file = file_get_contents($file_);
        $rep = str_replace("\r\n", ',', $file);
        $cont = explode(',',$rep);

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

}
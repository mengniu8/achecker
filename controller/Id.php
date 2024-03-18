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

class Id
{
    public function index()
    {
        echo "id.index.ok";
    }
    public function get_id($count=1){
        $data_count=Db::table('id')->count();
        $index=rand(1,$data_count);
        $data=Db::table('id')->where('id',$index)->find();
        //todo:检查身份证是否可用
        //$check=$this->post('');
        return json_encode($data);
    }
    public function reset_id($count=1){
        $num_begin=100000*0;
        $data=Db::table('id')->where("id",">",$num_begin)->where("id","<=",$num_begin+100000)->select()->toArray();
        for ($i = 0; $i < count($data); ++$i) {
            $index_y=$i+$num_begin+1;
            //echo $data[$i]["id"]."|";
            if ($data[$i]["id"] != $index_y){
                $data_new=['id'=>$index_y];
                //Db::table('id')->where('id',$data[$i]["id"])->update($data_new);
                echo $data[$i]["id"].'-'.$index_y."|";
            }else{
                echo ($i+1)."-".$data[$i]["id"].'|';
            }
        }

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
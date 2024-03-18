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
use think\facade\Config;
use think\facade\Request;

class Ssjjfuwen
{
    public function index()
    {
        $platform=input("param.platform/s");
        if(Request::isMobile()){
            return view('fuwen_m',[
                'platform'=>$platform,
            ]);
        }
        return view('fuwen',[
            'platform'=>$platform,
        ]);
    }
    public function code_to_name($code){
        if(!$code) $code=100;
        $data_type_list_json='{
                "type_100": "异化-深红-蓝",
                "type_101": "异化-深红-紫",
                "type_102": "异化-深红-金",
                "type_107": "异化-蔚蓝-蓝",
                "type_108": "异化-蔚蓝-紫",
                "type_109": "异化-蔚蓝-金",
                "type_110": "异化-翠绿-蓝",
                "type_111": "异化-翠绿-紫",
                "type_112": "异化-翠绿-金",
                "type_113": "异化-橙黄-蓝",
                "type_114": "异化-橙黄-紫",
                "type_115": "异化-橙黄-金",
                "type_120": "璀璨-深红-绿",
                "type_121": "璀璨-蔚蓝-绿",
                "type_122": "璀璨-翠绿-绿",
                "type_123": "璀璨-橙黄-绿",
                "type_124": "璀璨-深红-蓝",
                "type_125": "璀璨-蔚蓝-蓝",
                "type_126": "璀璨-翠绿-蓝",
                "type_127": "璀璨-橙黄-蓝"
            }';
        $data_type_list=json_decode($data_type_list_json,true);
        if(!$data_type_list){
            echo "\r\n错误：符文类型代码表转换出错！";
            return "";
        }
        echo '符文类型：'.$data_type_list["type_".$code];
        return $data_type_list["type_".$code];
    }
    public function get_user_fuwen(){
        $db_user='user_'.input("param.platform/s");
        $ip=$this->getIP();
        $tm=$this->get_time();
        //查找上次登录IP为本IP，上次获取时间大于30分钟，排序为随机
        $data_check=Db::table($db_user)->where('lastsignip',$ip)->where("level",">=",20)->where('lv_rpg','>=',20)->whereTime('lastget','<',time()-60*30)
            ->where('disabled',0)
            ->orderRand($tm)
            ->find();
        if($data_check){
            $data_check['time_request']=$tm;
            $data_check['your_ip']=$ip;
            return json($data_check);
        }else{
            return json(['username'=>'',
                'password'=>''
            ]);
        }
    }
    public function get_new(){
        $db_user='user_'.input("param.platform/s").'_fuwen';
        $data_json=[
            'code'=>0,
            'msg'=>'',
            'count'=>0,
            'data'=>[]
        ];
        $data_list=Db::table($db_user)->where("new",1)->select();
        if($data_list){
            foreach ($data_list as $k => $v) {
                Db::table($db_user)->where("id",$v['id'])->update(['new'=>0]);
            }
            $data_json['data']=$data_list;
            $data_json['count']=count($data_list);
        }
        return json($data_json);
    }
    public function get_all(){
        $db_user='user_'.input("param.platform/s").'_fuwen';
        $keyword=input("param.keyword/s");
        $type=input("param.type/s");
        $card=input("param.card/s");
        $data_json=[
            'code'=>0,
            'msg'=>'',
            'count'=>0,
            'data'=>[]
        ];
        $data_card=Db::table('card')->where("card",$card)->find();
        if(!$data_card || $data_card['disabled']==1){
            $msg='充值卡无效。';
            if(isset($data_card['msg']) && strlen($data_card['msg'])>0){
                $msg=$data_card['msg'];
            }
            return json([
                'code'=>1000,
                'msg'=>$msg,
                'count'=>0,
                'data'=>[]
            ]);
        }
        $type_color="%";
        if($type!='全部'){
            $type_color="%$type%";
        }
        if($keyword == ""){
            $data_list=Db::table($db_user)->field('typeb,price,txt,pos,tm')->where('disabled',0)->whereLike('typeb',$type_color)->order('tm','DESC')->select();
        }else{
            $data_list=Db::table($db_user)->whereLike('txt',"%".$keyword."%")->where('disabled',0)->whereLike('typeb',$type_color)->field('typeb,price,txt,pos,tm')->order('tm','DESC')->select();
        }

        if($data_list){
            $data_new=[];
            $index=0;
            foreach ($data_list as $k => $v) {
                $index+=1;
                $num=$this->sumDecimalsInString($v['txt']);
                if($keyword != ""){
                    $data_num=explode('|',$v['txt']);
                    $data_num_str='';
                    foreach ($data_num as $v_num){
                        if(strstr($v_num,$keyword)){
                            $data_num_str.='|'.$v_num;
                        }
                    }
                    $num=$this->sumDecimalsInString($data_num_str);
                }
                $data_new[]=[
                    'id'=>$index,
                    'typeb'=>$v['typeb'],
                    'price'=>$v['price'],
                    'txt'=>$v['txt'],
                    'pos'=>floor($v['pos']/3)+1,
                    'tm'=>$v['tm'],
                    'num'=>$num,
                    'danjia'=>sprintf("%.2f万", $v['price']/$num/10000)
                ];
            }
            $data_json['data']=$data_new;
            $data_json['count']=count($data_new);
        }
        $data_card_log=[
            'card'=>$card,
            'ip'=>$this->getIP(),
            'word'=>"符文-".$keyword
        ];
        Db::table('card_log')->insert($data_card_log);
        return json($data_json);
    }

    public function get_tiaowen($data){
        $data_json=json_decode($data,true);
        //格式化符文条文 runeInfo.affixes
        if(!$data_json){
            return "";
        }
        $config_tiaowen=Config::get('fuwenname');
        //echo "符文条文转换结果：\r\n";
        //print_r($data_json);
        $data_new='';
        foreach ($data_json['runeInfo']['affixes'] as $v) {
            $kk="code_".$v['id'];
            $lock="";
            if($v['lock'] === true){
                $lock="(锁)";
            }
            if(array_key_exists($kk,$config_tiaowen)){
                $data_new.=$config_tiaowen[$kk].'-'.number_format($v['value']/10,2).$lock.'|';
            }else{
                $data_new.=$v['id'].'-'.number_format($v['value']/10,2).$lock.'|';
            }
        }
        $data_new = substr($data_new, 0, -1);
        return $data_new;
    }
    public function save(){
        $data=request()->post();
        echo gettype($data)."\r\n";
        echo 'count:'.count($data)."\r\n";
        $db_user='user_'.input("param.platform/s").'_fuwen';
        $ip=$this->getIP();
        $list_order=[];//保存本次新提交的大类列表，然后把数据库中该大类下面没在此列表的数据删除，或者移到另外一个数据表
        $sort_code=0;

        foreach ($data as $v) {
            //print_r($v);
            $vv = $v;
            $list_order[]=$vv['orderid'];
            if($vv['sortb']<120){
                $vv['typea']="异化";
            }elseif ($vv['sortb']<130){
                $vv['typea']="璀璨";
            }
            $vv['typeb']=$this->code_to_name($vv['sortb']);
            //记录符文条文 runeInfo.affixes
            $vv["txt"]=$this->get_tiaowen($vv['data']);

            if($sort_code==0) $sort_code=$vv['sorta'];
            $flag_exist=0;
            $data_old=Db::table($db_user)->where("orderid",$vv['orderid'])->find();
            if(!$data_old){
                Db::table($db_user)->strict(false)->save($vv);
            }else{
                $flag_exist=1;
                Db::table($db_user)->where('id',$data_old['id'])->strict(false)->update($vv);
            }
            echo $vv['orderid'].' | ' . $vv['sorta'] .' | ' . $vv['sortb'] .' | ' . $vv['price'] .' | ' . $vv['pos'] .' | ' . $flag_exist ."\r\n";
        }
        $data_old=Db::table($db_user)->where("sorta",$sort_code)->where('disabled',0)->select();
        if($data_old){
            foreach ($data_old as $k => $v) {
                if(!in_array($v['orderid'],$list_order)){
                    echo '标记失效记录：'.$v['orderid'].' pos:'.$v['pos'].' sorta:'.$v['sorta']."\r\n";
                    //Db::table($db_user)->where('orderid',$v['orderid'])->limit(1)->delete();
                    Db::table($db_user)->where('orderid',$v['orderid'])->limit(1)->update([
                        'disabled'=>1,
                        'tm_disabled'=>$this->get_time()
                    ]);
                }
            }
        }
    }
    public function deel_input($data){
        return preg_replace('/[^a-zA-Z0-9]/', '', $data);
    }
    public function get_luandou(){
        $db_user='user_'.input("param.platform/s").'_luandou';
        $username=input("param.username/s");
        $qu=input("param.qu/s");
        $seasonid=input("param.seasonid/s");
        $card=input("param.card/s");
        if($card == ""){
            $data_json=array('code'=>1000,'msg'=>'查询卡无效','count'=>0,'data'=>[]);
            return json($data_json);
        }
        if($qu == ""){
            $data_json=array('code'=>1000,'msg'=>'请输入【战区号】或者【游戏昵称】再查询。','count'=>0,'data'=>[]);
            $data_card=Db::table('card')->where("card",$card)->find();
            if($data_card){
                $data_json['msg'].="\r\n<br>您的查询卡剩余次数：{$data_card['count']}";
            }
            return json($data_json);
        }
        //$qu=str_replace($qu,"战区","");

        $card=$this->deel_input($card);
        $card=strtoupper($card);
        $code = 0;
        $msg = '';
        $user_ip=$this->getIP();
        $cache_name=$card.'-'.$user_ip;
        $time_min=10;//查询结果缓存分钟数
        $time_min_y=1440;

        //查询卡使用时，先查缓存（20分钟内），缓存有就直接返回数据，没有缓存再查card表，查表可用后标记不可用，然后写入缓存。
        $data_cache=cache($cache_name);
        if($data_cache){
            $data_cache['msg']='';
            $data_cache['cache']=$cache_name;
            $data_card=Db::table('card')->where("card",$card)->find();
            if($data_card && $data_card['type']=='Y'){
                $data_cache['msg'].="该充值卡【{$card}】已有查询记录。\r\n<br>本次显示的是上次查询的结果，和您输入的战区号或者昵称无关。\r\n<br>该演示卡每人{$time_min_y}分钟内仅可查询一次。要查询新数据，可稍后再试。";
            }else{
                $data_cache['msg'].="该充值卡【{$card}】有{$time_min}分钟内的查询记录。\r\n<br>本次显示的是上次查询的结果。\r\n<br>请您{$time_min}分钟内后再来查询。";
            }
            return json($data_cache);
        }else{
            //查card表
            $data_card=Db::table('card')->where("card",$card)->find();
            if (!$data_card){
                $data_json=array('code'=>1000,'msg'=>"充值卡【{$card}】不存在。",'count'=>0,'data'=>[]);
                return json($data_json);
            }else{
                if($data_card['disabled']==1){

                    $data_json=array('code'=>1000,'msg'=>"查询卡【{$card}】已经失效。",'count'=>0,'data'=>[]);
                    if($data_card['usedtime']){
                        $data_json["msg"].="\r\n<br>使用时间：{$data_card['usedtime']}";
                    }
                    if($data_card['usedip']){
                        $data_json["msg"].="\r\n<br>使用IP：{$data_card['usedip']}";
                    }
                    if($data_card['msg']){
                        $data_json["msg"].="\r\n<br><span style='color: red;'>{$data_card['msg']}</span>";
                    }
                    if(strtotime('now')-strtotime($data_card['usedtime'])>1800){
                        return json($data_json);
                    }else{
                        $data_json["msg"]="该充值卡已经使用，并已绑定查询信息：【{$qu}】<br>该区数据会优先更新。<br>您可以在".date('Y-m-d H:i:s',strtotime($data_card['usedtime'])+1800).'之前再次查询。';
                    }

                }else{

                }
                //检查绑定信息
                if(isset($data_card['bind']) && $data_card['bind']!=null && $data_card['bind']!='' && $data_card['bind']!=$qu){
                    //判断上次使用时间，如果是5天内，就不允许使用
                    //类型为X的卡不限战区
                    if($data_card['type']!='X') {
                        if ($this->get_time_diff_sec($data_card['usedtime'], $this->get_time()) < 3600 * 24 * 5) {
                            $data_json = array('code' => 1000,
                                'msg' => "该卡已绑定的查询信息为：{$data_card['bind']}\r\n<br>绑定时间：" .
                                    $data_card['usedtime'] . "\r\n<br>允许解绑时间：" . $this->get_diff_time($data_card['usedtime'], "+5 days") .
                                    "\r\n<br>允许解绑时间之前只允许查询：{$data_card['bind']}" .
                                    "\r\n<br>您的查询卡剩余次数：{$data_card['count']}"
                            ,
                                'count' => 0, 'data' => []
                            );
                            return json($data_json);
                        } else {
                            $data_json['cardinfo'] = '次数卡已绑定战区，但已超过5天。';
                        }
                    }
                }
            }
        }

        //判断客户输入的是战区还是昵称，如果是3位以内纯数字就是战区，其它是昵称
        $data=[];
        $is_qu=false;
        if (is_numeric($qu)){//战区数最大999
            if (strlen($qu)<4){
                $is_qu=true;
                if(cache('q-'.$qu)){
                    $data_qu_cache=cache('q-'.$qu);
                    $data_qu_cache['msg']='';//重置缓存数据的msg
                    //$data_qu_cache['from']='c';
                    //return json($data);
                }else{
                    //查询战区
                    $data=Db::table($db_user)->where("qu",$qu)->where('seasonid',$seasonid)->whereTime('time','>',time()-3600*24)->order('score', 'desc')->limit(50)->select()->all();
                    $index=0;
                    if($data) {
                        foreach ($data as $k => $v) {
                            $index += 1;
                            $data[$k]['id'] = $index;
                            //$v['id']=$index;
                            if ($data[$k]['zhanli'] == 0) {
                                $data[$k]['zhanli'] = '未知';
                            }
                        }
                    }else{
                        $code = 1000;
                        $msg="暂无【{$qu}】战区数据，请稍后再试，持续2天无数据可以联系我们解决。赛季：".$seasonid." 区：".$qu;
                        $data=[];
                    }
                }
            }else{
                $data_json=array('code'=>1000,'msg'=>"输入的区号【{$qu}】错误",'count'=>0,'data'=>[]);
                return json($data_json);
            }
        } else {
            //查询昵称
            if(strlen($qu)>0){
                $data_user=Db::table($db_user)->whereLike("name","%{$qu}%")->where('seasonid',$seasonid)->select()->all();
                if(count($data_user)>1){
                    //如果完全匹配能匹配到一个玩家，就按完全匹配算.如果完全匹配没有数据，则按模糊匹配算
                    $data_user_one=Db::table($db_user)->where("name",$qu)->where('seasonid',$seasonid)->find();
                    if(!$data_user_one) {
                        //多个用户名匹配，提示用户完善
                        $msg = "您输入的昵称【{$qu}】有多个匹配记录，请从下面列出的昵称中复制一个完整昵称进行查询。\r\n<br>";
                        foreach ($data_user as $k => $v) {
                            $msg .= "【{$v['name']}】 战队：{$v['team']} 战力：{$v['zhanli']} 战区：{$v['qu']}\r\n<br>";
                        }
                        $data_json = array('code' => 1000, 'msg' => $msg, 'count' => 0, 'data' => []);
                        return json($data_json);
                    }
                }

                $data_user=Db::table($db_user)->where("name",$qu)->where('seasonid',$seasonid)->find();
                if(!$data_user){
                    $data_user=Db::table($db_user)->whereLike("name","%{$qu}%")->where('seasonid',$seasonid)->find();
                }
                if($data_user){
                    //$data=Db::table($db_user)->where("qu",$data_user['qu'])->where('seasonid',$seasonid)->where('id','>',$data_user['id']-20)->where('id','<=',$data_user['id']+5)->order('score', 'desc')->select()->all();
                    $data=Db::table($db_user)->where("qu",$data_user['qu'])->where('seasonid',$seasonid)->whereTime('time','>',time()-3600*24)->order('score', 'desc')->limit(50)->select()->all();
                    $index=0;
                    foreach ($data as $k => $v) {
                        $index += 1;
                        $data[$k]['id'] = $index;
                        if ($data[$k]['zhanli'] == 0) {
                            $data[$k]['zhanli'] = '未知';
                        }
                    }
                }else{
                    $code = 1000;
                    $msg="用户名【{$qu}】，没有匹配的数据。\r\n<br>使用【昵称】查询时，如果有的字符不容易输入，可以只输入部分关键字。\r\n<br>例如您要查询“<span style='color: red'>拜建国ಠ_ಠ</span>”，可以只输入“<span style='color: red'>建国</span>”，系统会返回符合条件的完整名称供您选择。";
                    $data=[];
                }
            }else{
                $code = 1000;
                $msg="用户名【{$qu}】长度过短。";
                $data=[];
            }
        }
        $time_cache=60*$time_min;
        if(count($data)==0 && isset($data_qu_cache)){
            $data_json=$data_qu_cache;
            $data=$data_json['data'];
            //cache($cache_name,$data_json,$time_cache);
        }else{
            $data_json=array('code'=>$code,'msg'=>$msg,'count'=>count($data),'data'=>$data);
        }
        //$data_json=array('code'=>$code,'msg'=>$msg,'count'=>count($data),'data'=>$data);
        if(count($data)>0){
            $time_diff=$this->get_time_diff($this->get_time(),$data[0]['time']);
            $time_diff_sec=$this->get_time_diff_sec($this->get_time(),$data[0]['time']);
            $data_json['msg'].="查询成功，数据记录于：{$time_diff} ({$time_diff_sec}秒)前。";
            if($time_diff_sec>5*60) {
                //需要更改逻辑，改为上次使用时间不为null或者半小时内时，不提示。为null或者超过半小时时，提示。
                if($data_card['disabled']==1){
                    //已经是使用过的状态，不再提示用户可以再次查询。
                }else{
                    $data_json['msg'].="\r\n<br>该区数据将会优先更新，您可以在{$time_min}分钟后，30分钟之前，【免费】再次查询最新的数据。";
                }
            }
            if($time_diff_sec>10*60){
                //搞福利时，用于用户能查询到自己区的实时数据
                //$data_json['msg'].="\r\n<br>由于数据延时大于10分钟，您本次查询卡不扣除次数，仍然可以继续使用。";

                //$data_card['count']+=1;//查询延时过大不扣次数   不再需要此行，因为逻辑更改为半小时内重复查询一个区不扣次数

                $data_card['bind']=$data[0]['qu'];//绑定查询区

                //$data_json['msg'].="\r\n<br>另外战区【{$qu}】已加入优先更新列表，请10分钟后再来查询。";
                //将本区加入优先更新列表
                $db_user_luandou='user_'.input("param.platform/s").'_luandou_update';
                Db::table($db_user_luandou)->where('qu',$data[0]['qu'])->update(['first'=>1]);
            }

            //标记查询卡
            if($data_card['type']!='Y'){//非演示卡
                //$data_json['msg'].="\r\n<br>该数据可于20分钟内重复查看。";
                if($data_card['disabled']==1){
                    $time_used=$data_card['usedtime'];
                }else{
                    $time_used=$this->get_time();
                }
                //半小时内的查询，不扣次数，并且不更新最后使用时间。不更新使用时间的目的是防止无限半小时。
                if($data_card['usedtime'] !== null && $this->get_time_diff_sec($this->get_time(),$data_card['usedtime'])<60*30){
                    $time_used=$data_card['usedtime'];
                    //多次卡提示剩余次数
                    if($data_card['count']>1){
                        $data_json['msg']="本次查询不扣次数。";
                    }
                    $data_card['count']+=1;
                }
                $data_card=[
                    'count'=>$data_card['count']-1,
                    'disabled'=>1,
                    'usedtime'=>$time_used,
                    'usedip'=>$this->getIP(),
                    'bind'=>$qu
                ];

                if($data_card['count']>0){
                    $data_card['disabled']=0;
                    $data_json['msg']=$data_json['msg']."\r\n<br>您的查询卡剩余次数：".$data_card['count'];
                }
                //Db::table('card')->where("card",$card)->limit(1)->update(['bind'=>$qu]);
                Db::table('card')->where("card",$card)->limit(1)->update($data_card);
            }else{//演示卡
                $time_cache=60*$time_min_y;
                if(!strstr($data_json['msg'],"该卡号为演示卡")){
                    $data_json['msg'].="\r\n<br>该卡号为演示卡，每人{$time_min_y}分钟内只能查询一次。\r\n<br>{$time_min_y}分钟内重复查询会显示相同结果。\r\n<br>如果要查询新数据，请稍后后再试。";
                }
                if($card=='NIUGEHAO'){
                    $time_cache=2;
                    $data_json['msg']='';
                }
                //$data_json['msg'].="\r\n<br>该卡号为演示卡，每人{$time_min_y}分钟内只能查询一次。\r\n<br>{$time_min_y}分钟内重复查询会显示相同结果。\r\n<br>如果要查询新数据，请稍后后再试。";
            }
            cache($cache_name,$data_json,$time_cache);
        }else{
            if(strstr($qu,"战区")){
                $data_json['msg'].="\r\n<br>请只输入战区号码（不带“战区”两个字），或者只输入玩家昵称。";
            }
        }
        $data_card_log=[
            'card'=>$card,
            'ip'=>$this->getIP(),
            'word'=>$qu
        ];
        Db::table('card_log')->insert($data_card_log);
        $data_json['cache']=$cache_name;
        cache('q-'.$qu,$data_json,60*1);
        return json($data_json);
    }
    public function get_luandou_update_time()
    {
        $db_user = 'user_' . input("param.platform/s");
        $db_user_luandou = 'user_' . input("param.platform/s") . '_luandou_update';
        $ip = $this->getIP();

        $data = Db::table($db_user_luandou)->order('qu')->field('id,qu,updatetime')->select()->all();
        if ($data) {
            $data_json=array('code'=>0,'msg'=>'','count'=>count($data),'data'=>$data);
        }else{
            $data_json=array('code'=>0,'msg'=>'暂无数据','count'=>0,'data'=>[]);
        }
        return json($data_json);
    }
    public function get_luandou_user(){
        $db_user='user_'.input("param.platform/s");
        $db_user_luandou='user_'.input("param.platform/s").'_luandou_update';
        $time_delay=60*60*1;//查询间隔
        $ip=$this->getIP();
        $data_json=['your_ip'=>$ip];
        //随机延时，避免多机同时执行
        $delay = rand(1, 20);
        sleep($delay);
        $data_luandou=Db::table($db_user_luandou)->where('qu','>',0)->where('first',1)->whereLike('lastsucip',"%$ip%")->order('updatetime')->whereTime('updatetime','<',time()-60*3)->whereTime('lastget','<',time()-60*3)->find();
        if(!$data_luandou){
            $data_json['more']='没有需要优先更新的战区';
            $data_luandou=Db::table($db_user_luandou)->where('qu','>',0)->whereLike('lastsucip',"%$ip%")->order('updatetime')->whereTime('updatetime','<',time()-$time_delay)->whereTime('lastget','<',time()-60*3)->find();
        }else{
            $data_json['more']='优先更新战区';
        }

        if(!$data_luandou){
            $data_json['msg']="IP不符合";

            //没有IP符合的，随便找一个，并把CK置空
            //$data_luandou=Db::table($db_user_luandou)->where('qu','>',0)->order('updatetime')->whereTime('updatetime','<',time()-$time_delay)->whereTime('lastget','<',time()-60*3)->find();
            //if($data_luandou){
            //    $data_luandou['ck']="";
            //    $data_json['msg']="IP不符合，时间符合";
            //}else{
            //    $data_json['msg']="IP不符合，没有数据";
            //}
        }else{
            $data_json['msg']="IP符合";
        }
        if(!$data_luandou){
            return json($data_json);
        }

        //$data_luandou=Db::table($db_user_luandou)->where('qu','>',0)->whereLike('lastsucip',"%$ip%")->order('updatetime')->whereTime('updatetime','<',time()-$time_delay)->whereTime('lastget','<',time()-60*3)->find();


        //更新获取时间，避免多机同时获取同一个区
        Db::table($db_user_luandou)->where('id',$data_luandou['id'])->update(['lastget'=>$this->get_time(),'lastgetip'=>$ip]);

        $data_json['qu']=$data_luandou['qu'];
        $data_json['yourip']=$ip;
        $data_json['updatetime']=$data_luandou['updatetime'];

        //匹配 qu，IP，排序：lastget 只获取20分钟前登录过的账号
        $data=Db::table($db_user)->where('qu',$data_luandou['qu'])->where('lastsignip',$ip)->order('lastget','DESC')->whereTime('lastget','<',time()-60*5)->find();
        if($data){
            //echo "\r\nuser:".$data['username'].' lastget:'.$data['lastget'];
            $data_json['username']=$data['username'];
            $data_json['password']=$data['password'];
            $data_json['lastget']=$data['lastget'];
            $data_json['server']=$data['server'];
            $data_json['level']=$data['level'];
            $data_json['ck']=$data['ck'];
            if(isset($data_luandou['ck'])){
                $data_json['ck']=$data_luandou['ck'];
            }
        }else{
            $data_json['msg_user']="没有符合条件的用户，查询区：".$data_luandou['qu'].' 查询IP：'.$ip;
            //更新该区的lastsucip
            $data_luandou['qu']=str_replace($ip,"",$data_luandou['qu']);
            $data_luandou['qu']=str_replace('||',"|",$data_luandou['qu']);
            Db::table($db_user_luandou)->where('id',$data_luandou['id'])->update($data_luandou);

            //本ip没有这个战区的账号，在user库中找一个最近登陆过这个区的ip
            //考虑随机查询，或者排除已经有的IP
            $data_user=Db::table($db_user)->where('qu',$data_luandou['qu'])->order('lastget','DESC')->whereTime('lastget','<',time()-60*20)->find();
            if ($data_user){
                $data_json['msg_user']=$data_json['msg_user']."分配一个登录过该区的IP：".$data_user['lastsignip'].',登录时间：'.$data_user['lastsignday'].'，最后获取时间：'.$data_user['lastget'];
                if(!strstr($data_luandou['lastsucip'],$data_user['lastsignip'])){
                    Db::table($db_user_luandou)->where('id',$data_luandou['id'])->update(['lastsucip'=>$data_luandou['lastsucip']."|".$data_user['lastsignip']]);
                }
            }


        }
        return json($data_json);
    }
    public function get_new_sign(){
        $db_user='user_'.input("param.platform/s");
        $day=date('d');
        $ip=$this->getIP();
        //没用本机签到过的用户，再查找本机注册的用户，然后再查找其它用户
        $data_check=Db::table($db_user)->where('lastsignip',$ip)->where("lastsignday","<>",$day)->whereTime('lastget','<',time()-3600*3)->where('disabled',0)->find();
        if(!$data_check){
            $data_check=Db::table($db_user)->where('ip',$ip)->where("lastsignday","<>",$day)->whereTime('lastget','<',time()-3600*3)->where('disabled',0)->find();
        }
        if(!$data_check){
            $data_check=Db::table($db_user)->where("lastsignday","<>",$day)->whereTime('lastget','<',time()-3600*3)->where('disabled',0)->find();
        }
        if($data_check){
            if($data_check['times_get']-$data_check['times_suc']>10){
                Db::table($db_user)->where("username",$data_check["username"])->update(['disabled'=>1,'more'=>'连续失败3次']);
                return json(['username'=>'',
                    'password'=>''
                ]);
            }
            Db::table($db_user)->where("username",$data_check["username"])->update(['lastget'=>date('Y-m-d H:i:s',time()),'more'=>'lastgetip:'.$ip,
                'times_get'=>$data_check['times_get']+1
            ]);
            return json($data_check);
        }else{
            return json(['username'=>'',
                'password'=>''
            ]);
        }
    }
    //获取老号，可快速登录
    public function get_new_sign_old(){
        $db_user='user_'.input("param.platform/s");
        $day=date('d');
        $ip=$this->getIP();
        //本机IP登录过，并且保存过CK的账号
        $data_check=Db::table($db_user)->where('lastsignip',$ip)->where("lastsignday","<>",$day)->whereTime('lastget','<',time()-3600*3)->where('disabled',0)->whereNotNull('ck')->find();
        if($data_check){
            $data_check['addedinfo']='取老号';
            if($data_check['times_get']-$data_check['times_suc']>10){
                Db::table($db_user)->where("username",$data_check["username"])->update(['disabled'=>1,'more'=>'连续失败3次']);
                return json(['username'=>'',
                    'password'=>''
                ]);
            }
            Db::table($db_user)->where("username",$data_check["username"])->update(['lastget'=>date('Y-m-d H:i:s',time()),'more'=>'lastgetip:'.$ip,
                'times_get'=>$data_check['times_get']+1
            ]);
            return json($data_check);
        }else{
            return json(['username'=>'',
                'password'=>''
            ]);
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
    function sumDecimalsInString($str) {
        // 使用正则表达式匹配所有带小数点的数字
        preg_match_all("/\d+\.\d+/", $str,$matches);

        // 将数组中的所有数字相加
        $sum = 0;
        for ($i = 0; $i < count($matches[0]); $i++) {
            $sum += floatval($matches[0][$i]);
        }
        $sum = sprintf("%.2f", $sum);
        // 返回数字之和
        return $sum;
    }
    public  function get_diff_time($start_time,$diff){
        return date("Y-m-d H:i:s", strtotime($diff, strtotime($start_time)));
    }
    public function get_time_diff_sec($start_time, $end_time) {
        // 获取两个时间的绝对时间差
        $diff = abs(strtotime($end_time) - strtotime($start_time));
        return $diff;
    }
    public function get_time_diff($start_time, $end_time) {
        // 获取两个时间的绝对时间差
        $diff = abs(strtotime($end_time) - strtotime($start_time));

        // 如果相差的时间小于1小时，时间差显示为分钟数
        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "{$minutes} 分钟";
        } else {
            // 如果相差的时间大于等于1小时，显示的时间差为几小时几分钟
            $hours = floor($diff / 3600);
            $minutes = floor($diff % 3600 / 60);
            return "$hours 小时 $minutes 分钟";
        }
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

    public function get($url){
        $httph =curl_init($url);
        curl_setopt($httph,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($httph, CURLOPT_HEADER, false);//不返回response头部信息
        curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
        $rst=curl_exec($httph);
        curl_close($httph);
        return $rst;
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
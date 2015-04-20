<?php
/**
* wechat php test
*/

//define your token
define("TOKEN", "moses");
$wechatObj = new wechatCallbackapiTest();
//$wechatObj->valid();
$wechatObj->responseMsg();

class wechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    public function responseMsg()
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		
        ## 判断当前进来之后相应的事件
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $RX_TYPE = trim($postObj->MsgType);
        
        ## 选择判断
        switch($RX_TYPE)
                    {
                        case "text":
                        $resultStr = $this->handleText($postObj);
                        break;
                        case "event":
                        $resultStr = $this->handleEvent($postObj);
                        break;
                        default:
                        $resultStr = "Unknow msg type: ".$RX_TYPE;
                        break;
                    }
                    echo $resultStr;
    }
	
    ## 响应回复
	public function handleText($postObj)
    {
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = trim($postObj->Content);
        $time = time();
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>0</FuncFlag>
                    </xml>";             
       
         if(!empty( $keyword )){
                $msgType = "text";
             	
             	## 预处理接受不到表情的情况
                 /*
                    if($msgType != "text") { 
                        $contentStr = "小明准备加班到天亮来完善这部分东西";
                    
                        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                        echo $resultStr;
                    }
                  */
             	## 对输入的内容进行处理
             	 $word = mb_substr(trim($keyword),-2,2,"UTF-8");
             	 $num = mb_strlen($keyword, 'UTF8')-2;
             	 $plac = mb_substr(trim($keyword),0,$num,"UTF-8");
             	switch($word)
                    {
                    	case '歌词':
                     		$contentStr = $this->autoChat($plac);break;
                     	case '天气':
                    		//$contentStr = $keyword;break;
                    		$contentStr = $this->weather($word,$plac);break;
                     	default:
                     		//$contentStr = $word;
                     		$contentStr = $this->chickChat($keyword);break;
                    }
             	
           }else{
               $resultStr = 'Input somethings...';
               echo $resultStr;
           }
         
         $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
         echo $resultStr;
    }
    
    ## 地方天气
    public function weather($word, $place){
        include("weather_cityId.php");
       
        $c_name = $weather_cityId[$place];
        
        if(!empty($c_name)){
            $json=file_get_contents("http://m.weather.com.cn/atad/".$c_name.".html");
            $info = json_decode($json, true);
            $checkRst = $info['weatherinfo'];
        } else {
            return null;
        }
        
        if(empty($checkRst)){
            $checkRust = "抱歉,网络不给力,没有查到\"".$place."\"的天气信息,请稍后重试!";
        } else {
            //$checkRust = "【".$checkRst['city']."天气预报】\n".$checkRst['date_y']." ".$checkRst['fchh']."时发布"."\n\n实时天气\n".$checkRst['weather1']." ".$checkRst['temp1']." ".$checkRst['wind1']."\n\n温馨提示：".$checkRst['index_d']."\n\n明天\n".$checkRst['weather2']." ".$checkRst['temp2']." ".$checkRst['wind2']."\n\n后天\n".$checkRst['weather3']." ".$checkRst['temp3']." ".$checkRst['wind3'];
            $checkRust = "【".$checkRst['city']."天气预报】\n".$checkRst['date_y']." ".$checkRst['fchh']."时发布"."\n\n实时天气\n".$checkRst['weather1']."\n".$checkRst['temp1']."\n".$checkRst['wind1']."\n温馨提示：".$checkRst['index_d'];
        }
        
        $contentStr = $checkRust;
        return $contentStr;
    }
    
    ## 自动聊天
    public function autoChat($keyword){
        $now = array();     
        mysql_connect(SAE_MYSQL_HOST_M .':'. SAE_MYSQL_PORT, SAE_MYSQL_USER, SAE_MYSQL_PASS) or die('cannot connect server');
        mysql_select_db("app_moses") or die('databases is error');
        $rst= mysql_query( "select * from moses where msg like '%{$keyword}%'order by id asc limit 30");
        mysql_query('set names utf-8');
        
        $i = 0;
        while($rt = mysql_fetch_array($rst)){
            $song = str_replace(strstr($rt['msg'], '@'),'',$rt['msg']);
            $now[$i++] = $song.']';
        }
        
        $nums = count($now);
        $num = rand(0,$nums);
        if( $nums > 0) {
            $contentStr= $now[$num];
        } else {
            $contentStr='曾经沧海难为水,直到膝盖中了箭.';
        }
        
        return $contentStr;
    }
    
    ## 图灵聊天
    public function chickChat($keyword){
        
        $apiKey = "236aa55332813daeec46f7c00fa1f070"; 
        $apiURL = "http://www.tuling123.com/openapi/api?key=KEY&info=INFO";
        
        // 设置报文头, 构建请求报文 
        header("Content-type: text/html; charset=utf-8"); 
        $reqInfo = $keyword; 
        $url = str_replace("INFO", $reqInfo, str_replace("KEY", $apiKey, $apiURL)); 
        
        /** 方法一、用file_get_contents 以get方式获取内容 */ 
        $contentStr = file_get_contents($url);
        $js = json_decode($contentStr);
        $contentStr = preg_replace("<br>","\n\r",$js->text);
        
        if(empty($js->text)) {
        	$forme = array(
                0 => '小明给你的温馨提示:好好学习,天天吃药',
                1 => '世界这么大,我们一起去看看吧',
                2 => '你可以怀疑任何人，但绝不要怀疑你自己'
            );
            
            $contentStr = $forme[rand(0, count($forme))];
        }
        
        return $contentStr;
        
    }
    
    ## 推送事件
     public function handleEvent($object)
    {
        $contentStr = "";
        switch ($object->Event)
        {
            case "subscribe":
            $contentStr = "恭喜你已经关注成功："."\n[1]:查看天气情况请输入: \n'xx天气' 可查看xx地方的天气, \n如：'北京天气',即可查看北京的天气;\n[2]:查找歌词输入：\n'xxxx歌词' 可以查找到歌词;\n[3]:输入其他的内容小明会回复你.";
                break;
            default :
                $contentStr = "Unknow Event: ".$object->Event;
                break;
        }
        $resultStr = $this->responseText($object, $contentStr);
        return $resultStr;
    }
    
    ## 事件响应方法
    public function responseText($object, $content, $flag=0)
    {
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>%d</FuncFlag>
                    </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $flag);
        return $resultStr;
    }
    
    
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

?>
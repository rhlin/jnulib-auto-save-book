<?php
/*
  Auto-save-book
  Author: rhlin
  Version: 0.4
  Date:041-2015-06-06/ 042-2015-06-24
  Fixed:
  1. ilegal filename cannot generate pdf file.(replace illegal symbol with full width characters)
  2. Cross domain ajax POST became OPTIONS request.(setTimeout 2 seconds,until jquery1.9 is loaded.)
  Added:
  1. Interact UI ( assemble to pocket )
  Problem List: 
  1.conflick on mutiple-thread accesing the same name file
  2.auto clean out the out-date files
  3.processing progress bar(UI) 
*/

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Max-Age: 3628800');

//$_POST['data']='{"urls":["http://www.baidu.com/img/baidu_sylogo1.gif"],"title":"aaaa"}'; /*测试数据*/

if($_POST && $_POST['data']){
    /*初始设置*/
    ini_set ('memory_limit', '1000M'); /*最大PDF为500M*/
    $rawdata = json_decode($_POST['data']);
    //var_dump($rawdata);
    $data =  new stdClass();
    $data->{'title'} = str_replace(array('/','\\',':','*','?','<','>','|'), array('／','＼','：','×','？','＜','＞','｜'), $rawdata->{'title'});/*避免windows里文件名含非法字符*/
    $data->{'urls'} =array();

    for ($i=0; $i <sizeof($rawdata->{'pagetype'}); $i++) { 
        for ($s= $rawdata->{'pages'}[$i][0]; $s <= $rawdata->{'pages'}[$i][1]; $s++) {
            $imgn = "";for ($imi = 0; $imi < ( $rawdata->{'digit'} - strlen($rawdata->{'pagetype'}[$i]) - strlen($s)); $imi++, $imgn=$imgn."0");
            $data->{'urls'}[] = ($rawdata->{'url_prefix'}).($rawdata->{'pagetype'}[$i]).$imgn.$s.($rawdata->{'url_bacfix'});
        }
    }
    // var_dump($data);
    // exit();
    set_time_limit(sizeof($data->{'urls'})*10+180);/*防止时间不够*/

    $resp = array();
    $resp['imgSize']=(sizeof($data->{'urls'}));

    $startTime = microtime(true);
    $img = array();
    foreach ($data->{'urls'} as $pic_url){
        $img[] = DownImage(urldecode($pic_url),iconv('utf-8', 'gbk', $data->{'title'}));
        //break;//单页测试
    }
    /*生成PDF*/
    $imgType=array(
       '1' => 'GIF',
       '2' => 'JPG',
       '3' => 'PNG'
    );
    require('fpdf.php');
    $pdf = new FPDF();
    $pdf->SetTitle($rawdata->{'title'},true);
    for($i = 0;$i<sizeof($img);$i++) {
        list($width, $height, $type, $attr) = getimagesize($img[$i]);
        $pdf->AddPage($width > $height ? 'L':'P',[$width,$height]);
        $pdf->Image($img[$i], 0, 0, $width,$height,$imgType[$type]);
    }
    //unlink(($data->{'title'}).'.pdf');
    $pdf->Output(iconv('utf-8', 'gbk', $data->{'title'}).'.pdf','F');
    $resp['file'] = ($data->{'title'}).'.pdf';

    //Todo:删掉目录和目录下文件
    for($i=0;$i<sizeof($img);$i++){
        @unlink($img[$i]);
    }
    @rmdir(pathinfo($img[0],PATHINFO_DIRNAME));

    $endTime = microtime(true); 
    $resp['runtime'] = ($endTime - $startTime);

    //输出json
    header('Content-type: application/json');  
    echo json_encode($resp);

}elseif($_GET && isset($_GET['html']) && $_GET['html']=="savebook"){
    header('Content-type: text/html'); 
?>

<div id="lib_book_box" class="lib-book-message-box">
<div class="title">
    <h2>图书馆存书工具</h2>
    <span class="close-btn" onclick="javascript:$('#lib_book_box').remove();">x</span>
</div>
<ul class="line">
    <li style="background:#FF7373">&nbsp;</li>
    <li style="background:#FCD209">&nbsp;</li>
    <li style="background:#409D40">&nbsp;</li>
    <li style="background:#659ED4">&nbsp;</li>
</ul>
<div class="content">
    <div id="lib_boook_message">
<div class="message-loading">
    执行中...
</div>
    </div>
    <div class="lib_boook_tips">
        <span class="le"><a href="<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];?>" target="_blank">使用说明</a></span>
        <span class="ri"><a href="<?php echo 'http://'.'202.116.3.152:20023/?p=125';?>" target="_blank">意见反馈</a></span>
    </div>
</div>
</div>
<style>
#lib_book_box *{
    margin: 0;
    padding: 0;
    font-family: "Microsoft Yahei";
    font-size: 14px;
    color: #666;
}
#lib_book_box.lib-book-message-box{
    border:2px #F3F3F3 solid;
    box-shadow: 1px 2px 1px 0 #999; 
    width:300px;
    height: 200px;
    background: ;
    position: absolute;
    top:30px;
    right: 40px;
    z-index: 10000;
}
#lib_book_box .title{
    height: 50px;
}
#lib_book_box .title h2{
    line-height: 50px;
    font-size: 18px;
    padding-left:18px;
    color: #333;
    background: #F9F9F9;
    width: 100%;
}
#lib_book_box .close-btn{
    position: absolute;
    right: 5px;
    top: 5px;
    line-height: 10px;
    width: 10px;
    height: 10px;
    cursor: pointer;
}
#lib_book_box .line li{
    float: left;
    display: block;
    width: 25%;
    height: 2px;
    line-height: 2px;
}
#lib_book_box .content{
    background: #EAEAEA;
    height: 130px;
    padding: 10px;
}
#lib_book_box .lib_boook_tips{
    position: absolute;
    bottom: 5px;
    left: 0px;
    font-size: 14px;
    padding: 6px 14px;
}
#lib_book_box .lib_boook_tips a{
    color: #666;
    text-decoration: none;
}
#lib_book_box .lib_boook_tips span{width: 135px;display: block;float: left;}
#lib_book_box .lib_boook_tips span.le{text-align: left;}
#lib_book_box .lib_boook_tips span.ri{text-align: right;}
#lib_book_box .lib_boook_tips a:hover{
    color: #69D;
}
#lib_book_box .content .message-pdfdown {
    text-align: center;
    line-height: 60px;
}
#lib_book_box .content .message-pdfdown .down-btn{
    display: block;
    width: 86px;
    line-height: 32px;
    height: 32px;
    background: #F04D4D;
    border-radius: 4px;
    margin: 2px auto;
    text-decoration: none;
    text-align: center;
    font-size: 16px;
    font-weight: bold;
    color: #FFF;
}
#lib_book_box .content .message-error{
	text-align: center;
	font-size: 16px;
	line-height: 80px;
}
#lib_book_box .content .message-error .icon{
	display: inline-block;
	width: 34px;
	height: 30px;
	line-height: 30px;
	margin: 2px 15px 2px 5px;
background: url(data:image/gif;base64,R0lGODlhJAAfALMAAGZmZu7chMzMd7q6uqqqqszMzMy+fOrq6tDKrt3d3X98bOHVmgAAAAAAAAAAAAAAACH5BAEHAAkALAAAAAAkAB8AAAS5MMlJ6yHk1M27HQgyaF5pEkEwmGwHpsvYztKQpkNBt8Ny4ztTwfYLiIKdi6+o0iErQyaM8KwQltJjNVFASH+rLep7i1VfPwPAUMw9iel1cUHdHdBxdlFEakWZanptTix3WHlSMX0lf4ByWYQlPWQAZCosjVKVZHySlgEKn24cd16fn2YunwoAoZZaFKWfALSnYRVwZKyulnR9hqfBNwiEmcKndQmTx8JHxsyiBVfQx3QDGNjZ2tvc3REAOw==) center no-repeat;
	text-indent: -10000;
}
#lib_book_box .content .message-error .code{
	text-align: center;
	font-size: 14px;
	line-height: 100%;
	color: #BBB;
}
</style>


<?php
}elseif($_GET && isset($_GET['js']) && $_GET['js']=="savebook"){
    //此处是js 展示书签脚本
    header('Content-type: text/javascript'); 
?>

$(function(){
    if($('#lib_book_box').length!=0) { console.log("请勿重复执行脚本");return; }
    var title = $("body").html().match(/<!--[\w\W\r\n]*?-->/)[0].match(/\>([^><]*?)\</)[1].replace(/[\r\n\t]/g,"");
    var url_prefix = window.params.jpgPath;
    var pages =  window.params.pages;
    var pagetype = ["cov", "bok", "leg", "fow", "!", "", "att", "cov"];
    
    var submitData = {
        'title': title,
        'url_prefix': encodeURIComponent(url_prefix),
        'pagetype': pagetype,
        'pages': pages,
        'digit': 6,
        'url_bacfix': ".jpg"
    };
    $.ajax({
        type:"GET",
        url:"<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];?>",
        data:"html=savebook",
        success: function(data){
            $("body").append(data);
            $.ajax({
                type : "POST",
                url : "<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];?>",
                dataType : "JSON",
                data : 'data='+JSON.stringify(submitData),
                /*Todo: 下载进度显示*/
                success : function(data){         
                    $("#lib_boook_message").html("<div class='message-pdfdown'>单击打开或右键另存为<br/><a class='down-btn' href='<?php echo 'http://'.$_SERVER['HTTP_HOST'].'/';?>"+encodeURIComponent(data['file'])+"'  target='_blank'>PDF</a></div>");
                },
                error : function(data){
                    console.log(data);
                    $("#lib_boook_message").html("<div class='message-error'><span class='icon'>!</span>发生错误=。=||<br/><p class='code'>(错误调试信息请查看Console。)</p></div>");
                }

            });
            console.log("已经添加存书任务啦，耐心等候。（800页大概要8分钟）");
        }
    });

});
<?php
}else{
    //此处是html 展示用法
    header('Content-type: text/html'); 
?>
<html>
<head>
<title>图书馆电子书保存到本地</title>
<style>
.bookmark {display: block; width: 100px;height: 30px;line-height: 30px; text-align: center;
background: #2ea2cc ;border: #0074a2 solid 1px; border-radius: 4px;text-decoration: none;color:#FFF;
box-shadow: inset 0 2px 0 rgba(120,200,230,.5),0 2px 0 rgba(0,0,0,.15);}
</style>
</head>
<body>
<a onclick="" href="javascript:(
function(){
JQUERY='http://libs.baidu.com/jquery/1.9.0/jquery.js';
try{
    var x=document.createElement('SCRIPT');
    x.type='text/javascript';
    x.src=JQUERY;
    x.charset='utf-8';
    x.async=false;
    document.getElementsByTagName('head')[0].appendChild(x);
setTimeout('
    var y=document.createElement(\'SCRIPT\');
    y.type=\'text/javascript\';
    y.src=\'http://<?php echo ($_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);?>?js=savebook&t=\'+(new Date().getTime()/100000);
    y.charset=\'utf-8\';
    document.getElementsByTagName(\'head\')[0].appendChild(y);
    ',2000);
}catch(e){
    alert(e);
}
}
)();
" class="bookmark">存书</a>

<div><br/>使用说明：把【存书】链接拖到书签栏，在图书馆书籍页面【点击阅读电子版】打开的页面上点击一下该书签，耐心等待足够长的时间，右上角会弹出生成pdf的对话框。<br/>注意：该版本不支持多个相同名字任务同时执行。<br/>欢迎反馈bug、建议和指导意见。</div>

<div><br/>更新：v0.4<br/>
1.更新UI交互界面；<br/>
2.修复书籍名字含有文件名不允许的字符而导致任务失败；<br/>
3.延时让任务调用正确版本的JQuery。<br/>
<br/>更新提醒：需要重新拖书签到书签栏。
</div><!---->
</body>
</html>

<?php
}

function DownImage($url,$dirname="",$filename="",$timeout=60){
    $dirname = empty($dirname) ? "temp" : $dirname;
    $filename = empty($filename) ? pathinfo($url,PATHINFO_BASENAME) : $filename;

    $file =  $dirname."/".$filename;
    $dir = pathinfo($file,PATHINFO_DIRNAME);

    !is_dir($dir) && @mkdir($dir,0755,true);
    //$url = str_replace(" ","%20",$url);

    if(function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $temp = curl_exec($ch);
        if(file_put_contents($file, $temp) && !curl_error($ch)) {
            return $file;
        } else {
            return false;
        }
    } else {
        $opts = array(
            "http"=>array(
            "method"=>"GET",
            "header"=>"",
            "timeout"=>$timeout)
        );
        $context = stream_context_create($opts);
        if(@copy($url, $file, $context)) {
            return $file;
        } else {
            return false;
        }
    }
}


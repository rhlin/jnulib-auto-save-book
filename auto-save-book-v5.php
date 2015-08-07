<?php
/*
  Auto-save-book
  Author: rhlin
  Version: 0.5
  Date: 2015-06-24/2015-06-25
  Fixed:
  Added:
    1.processing progress bar(UI) 
  Problem List: 
  1.conflick on mutiple-thread accesing the same name file(avoid execution? add timestamp?)
  2.auto clean out the out-date files
  3.auto simple bookmark
  4.Eroor Messeage Code & Text
  5.User delete
*/

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Max-Age: 3628800');

//$_POST['data']='{"urls":["http://www.baidu.com/img/baidu_sylogo1.gif"],"title":"aaaa"}'; /*测试数据*/
date_default_timezone_set("Asia/Hong_Kong");
if($_POST && isset($_POST['data'])){
    /*初始设置*/
    ini_set ('memory_limit', '1000M'); /*最大PDF为1000M*/
    $rawdata = json_decode($_POST['data']);
    //var_dump($rawdata);
    $data =  new stdClass();
    $data->{'title'} = str_replace(array('/','\\',':','*','?','<','>','|'), array('／','＼','：','×','？','＜','＞','｜'), $rawdata->{'title'});/*避免windows里文件名含非法字符*/
    $data->{'urls'} =array();

    /*Log 文件,记录进度*/
    @$logfile=fopen(iconv('utf-8', 'gbk', $data->{'title'}).'.log',"w");


    for ($i=0; $i <sizeof($rawdata->{'pagetype'}); $i++) { 
        for ($s= $rawdata->{'pages'}[$i][0]; $s <= $rawdata->{'pages'}[$i][1]; $s++) {
            $imgn = "";for ($imi = 0; $imi < ( $rawdata->{'digit'} - strlen($rawdata->{'pagetype'}[$i]) - strlen($s)); $imi++, $imgn=$imgn."0");
            $data->{'urls'}[] = ($rawdata->{'url_prefix'}).($rawdata->{'pagetype'}[$i]).$imgn.$s.($rawdata->{'url_bacfix'});

        }
    }
    @fwrite($logfile, "0"."|".Date("Y-m-d h:i:sa")."\n");/*log-初始阶段*/
    @fflush($logfile);
    // var_dump($data);
    // exit();
    set_time_limit(sizeof($data->{'urls'})*10+180);/*防止时间不够*/

    $resp = array();
    $resp['imgSize']=(sizeof($data->{'urls'}));

    $startTime = microtime(true);
    $img = array();
    foreach ($data->{'urls'} as $pic_url){
        $img[] = DownImage(urldecode($pic_url),iconv('utf-8', 'gbk', $data->{'title'}));
        @fwrite($logfile, floor(100*sizeof($img)/sizeof($data->{'urls'})*0.95)."|".Date("Y-m-d h:i:sa")."\n");/*log-文件下载进度1-95*/
        @fflush($logfile);
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
        @fwrite($logfile, (floor(100*($i+1)/sizeof($img)*0.03)+95)."|".Date("Y-m-d h:i:sa")."\n");/*log-文件合并95-98*/
        @fflush($logfile);
    }
    //unlink(($data->{'title'}).'.pdf');
    $pdf->Output(iconv('utf-8', 'gbk', $data->{'title'}).'.pdf','F');
    $resp['file'] = ($data->{'title'}).'.pdf';

    //Todo:删掉目录和目录下文件
    for($i=0;$i<sizeof($img);$i++){
        @unlink($img[$i]);
        @fwrite($logfile, (floor(100*($i+1)/sizeof($img)*0.02)+98)."|".Date("Y-m-d h:i:sa")."\n");/*log-文件合并98-100*/
        @fflush($logfile);
    }
    @rmdir(pathinfo($img[0],PATHINFO_DIRNAME));

    $endTime = microtime(true);
    $resp['runtime'] = ($endTime - $startTime);
    fwrite($logfile, "100"."|".Date("Y-m-d h:i:sa")."\n");/*log-完成*/
    @fflush($logfile);
    //@unlink($data->{'title'}."log");/*删除log文件*/
    //输出json
    header('Content-type: application/json');  
    echo json_encode($resp);
}elseif( $_POST && isset($_GET['json']) && $_GET['json']=="progress" && isset($_POST['title'])){

    $filename =str_replace(array('/','\\',':','*','?','<','>','|'), array('／','＼','：','×','？','＜','＞','｜'), $_POST['title']);/*避免windows里文件名含非法字符*/
    if($fp = file(iconv('utf-8', 'gbk', $filename).'.log')){
        header('Content-type: application/json'); 
        $resp = array();
        $rawinfo=$fp[count($fp)-1];
        $info=explode('|', $rawinfo);
        $resp['percent']=trim($info[0]);
        $resp['time']=$info[1];
        echo json_encode($resp);
    }else{
        echo "Error: Cannot query the progress.";
    }

}elseif($_GET && isset($_GET['html']) && $_GET['html']=="savebook"){
    header('Content-type: text/html'); 
?>

<div id="lib_book_box" class="lib-book-message-box">
<div class="title">
    <h2>图书馆存书工具<span class="version">v0.5</span></h2>
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
#lib_book_box .title h2 .version{
    font-size: 12px;
    color: #CCC;
    padding-left: 10px;
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
#lib_book_box .content .message-process {
    text-align: center;
}
#lib_book_box .content .message-process .percent {
    text-align: center;
    font-size: 16px;
    line-height: 40px;
    font-family: "Candara";
    font-weight: bold;
    letter-spacing:2px;
    margin: 20px auto 0;
}
#lib_book_box .content .message-process .progress-bar{
    margin: auto;
    width: 260px;
    line-height: 8px;
    height: 8px;
    background: #BBB;
    border-radius: 2px;
}
#lib_book_box .content .message-process .progress-bar span{
    display: block;
    float: left;
    line-height: 8px;
    height: 8px;
    background: url(data:image/gif;base64,R0lGODlhCgAIAIAAAJXdjTzBLSH5BAAHAP8ALAAAAAAKAAgAAAIORB6Gq9edEHxM1mVztAUAOw==) 0 0 repeat-x;
    animation:bar-animation 1s  steps(20) infinite;
}
@keyframes bar-animation{
  from {background-position: 1px 0;}
  to {background-position: 20px 0;}
}
</style>


<?php
}elseif($_GET && isset($_GET['js']) && $_GET['js']=="savebook"){
    //此处是js 展示书签脚本
    header('Content-type: text/javascript'); 
?>
var title = $("body").html().match(/<!--[\w\W\r\n]*?-->/)[0].match(/\>([^><]*?)\</)[1].replace(/[\r\n\t]/g,"");
function queryProcess(starttime, prevpercent){/*轮询进度*/
    $.ajax({
            type : "POST",
            url : "<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];?>"+"?json=progress",
            dataType : "JSON",
            data : 'title='+title+'&t='+new Date(),
            success : function(data){
                if($("#lib_boook_message .message-process").length!=0){
                /*update 页面 进度条*/
                    $("#lib_boook_message .message-process .percent").html(data.percent +" %");
                    $("#lib_boook_message .message-process .progress-bar span").css('width', data.percent + '%');
                    var test;
                    var timeout;
                /*设置下次查询时间*/
                   if(data.percent-prevpercent!=0){
                        test = (new Date()-starttime)/1000/(data.percent-prevpercent);
                    }else{
                        test = (new Date()-starttime)/1000 * 3;
                    }
                    console.log(test);
                    if (test < 1+1 ){
                        timeout = 1;
                    }else if (test < 3+1 ){
                        timeout = 3;
                    }else if (test < 7+1 ){
                        timeout = 5;
                    }else if (test < 10+2 ){
                        timeout = 10;
                    }else if (test < 30+3 ){
                        timeout = 30;
                    }else if (test < 40+5){
                        timeout = 40;
                    }else {
                        timeout = 60;
                    }
                    console.log(timeout);
                    if(parseInt(data.percent)<=99){
                        console.log(data.percent);
                        setTimeout("queryProcess("+new Date().getTime()+", "+data.percent+")", timeout*1000);
                    }
                }
            },
            error : function(data){
                var timeout = 10;
                setTimeout("queryProcess("+new Date().getTime()+", "+prevpercent+")",timeout*1000);

            }/**/

    });
}
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
            /*Todo:检查参数*/
            $.ajax({
                type : "POST",
                url : "<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];?>",
                dataType : "JSON",
                data : 'data='+JSON.stringify(submitData),
                /*Todo: 下载进度显示*/
                success : function(data){ 
                 <?php   /*Todo：检验code，根据code显示出错原因，图片损坏，图片无效，超出可执行时间，超出文件大小*/   ?>
                    $("#lib_boook_message").html("<div class='message-pdfdown'>单击打开或右键另存为<br/><a class='down-btn' href='<?php echo 'http://'.$_SERVER['HTTP_HOST'].'/';?>"+encodeURIComponent(data['file'])+"'  target='_blank'>PDF</a></div>");
                },
                error : function(data){  <?php    /*Todo：处理网络异常的错误，网络被中断（如何恢复？），404服务不可用  --这些测试环境怎么搭建*/?>
                    console.log("出错信息："+data.responseText);
                    $("#lib_boook_message").html("<div class='message-error'><span class='icon'>!</span>发生错误=。=||<br/><p class='code'>(错误调试信息请查看Console。)</p></div>");
                }
            });
            console.log("已经添加存书任务啦，耐心等候。（800页大概要8分钟）");
            <?php //不输出注释
            /*Todo：如何查询进度
                ajax查询记录文件，记录文件如何记录，txt?html?json?
            */?>      
            setTimeout("$(\"#lib_boook_message\").html(\"<div class='message-process'><div class='percent'>0 %</div><div class='progress-bar'><span style='width:0%'></span></div></div>\");queryProcess(new Date(), 10)",3000);
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

<div><br/>使用说明：把【存书】链接拖到书签栏，在图书馆书籍页面【点击阅读电子版】打开的页面上点击一下该书签，耐心等待足够长的时间，右上角会弹出生成pdf的对话框。<br/>注意：该版本不支持多个相同任务同时执行。<br/>欢迎反馈bug、建议和指导意见。</div>

<div>
<br/>更新：v0.5<br/>
1.增加处理进度条。<br/>

<br/>更新：v0.4<br/>
1.更新UI交互界面；<br/>
2.修复书籍名字含有文件名不允许的字符而导致任务失败；<br/>
3.延时让任务调用正确版本的JQuery。<br/>
<br/>更新提醒：需要重新拖书签到书签栏。<br/>
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


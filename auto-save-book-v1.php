<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Max-Age: 3628800');

//$_POST['data']='{"urls":["http://www.baidu.com/img/baidu_sylogo1.gif"],"title":"aaaa"}'; /*测试数据*/

if($_POST && $_POST['data']){
    /*初始设置*/
    ini_set ('memory_limit', '500M'); /*最大PDF为500M*/
    $data = json_decode($_POST['data']);
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
    $pdf->SetTitle($data->{'title'},true);
    for($i = 0;$i<sizeof($img);$i++) {
        list($width, $height, $type, $attr) = getimagesize($img[$i]);
        $pdf->AddPage($width > $height ? 'L':'P',[$width,$height]);
        $pdf->Image($img[$i], 0, 0, $width,$height,$imgType[$type]);
    }
    //unlink(($data->{'title'}).'.pdf');
    $pdf->Output(iconv('utf-8', 'gbk', $data->{'title'}).'.pdf','F');
    $resp['file'] = ($data->{'title'}).'.pdf';

    //Todo:删掉目录和目录下文件

    $endTime = microtime(true); 
    $resp['runtime'] = ($endTime - $startTime);

    //输出json
    header('Content-type: application/json');  
    echo json_encode($resp);

}elseif($_GET && $_GET['js']=="savebook"){
    //此处是js 展示书签脚本
    header('Content-type: text/javascript'); 
?>
$(function(){
    var title = $("body").html().match(/<!--[\w\W\r\n]*?-->/)[0].match(/\>([^><]*?)\</)[1].replace(/[\r\n\t]/g,"");
    var srcArr = new Array();
    $("#Readweb .duxiuimg input[type='image']").each(function(){
        srcArr.push(encodeURIComponent($(this).attr("scr")));
    });
    var submitData = {
        'urls':  srcArr,
        'title': title
    };
    $.ajax({
        type : "POST",
        url : "<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];?>",
        dataType : "JSON",
        data : 'data='+JSON.stringify(submitData),
        success : function(data){
            console.log(data); 
            //Todo: 缺少下载进度；
            $("body").append("<style>#close{display:block;position:absolute;width: 5px;height: 5px; right: 2px;top: 2px; }#my_box{display: block;  position: absolute;  top:20px; right:40px; width: 100px; height: 40px; line-height: 20px; padding: 8px;  border: 4px solid #0074a2;  background-color: white;  z-index:10000;  overflow: auto;text-align: center;}</style> <div id='my_box' class='my_box'>单击打开或右键另存为 <a href='<?php echo 'http://'.$_SERVER['HTTP_HOST'].'/';?>"+data['file']+"'  target='_blank' style='text-decoration:underline'>PDF</a><a id='close' href=javascript:$('.my_box').hide();>x</a></div>");
            //$("body").append('<script>document.write("<iframe id=\'new\' src=\'<?php echo 'http://'.$_SERVER['HTTP_HOST'].'/';?>'+ data['file'] + '\' style=\'display:none\'></iframe>");</script>');
        }
    });
});
<?php
}else{
    //此处是html 展示用法
    header('Content-type: text/html'); 
?>
</html>
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
    document.getElementsByTagName('head')[0].appendChild(x);
    var y=document.createElement('SCRIPT');
    y.type='text/javascript';
    y.src='http://<?php echo ($_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);?>?js=savebook&t='+(new Date().getTime()/100000);
    y.charset='utf-8';
    document.getElementsByTagName('head')[0].appendChild(y);
}catch(e){
    alert(e);
}
}
)();
" class="bookmark">存书</a>
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
    $url = str_replace(" ","%20",$url);

    if(function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $temp = curl_exec($ch);
        $file +=judgeJpgPng($temp);
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
        //$file +=judgeJpgPng($context);
        if(@copy($url, $file, $context)) {
            //$http_response_header
            return $file;
        } else {
            return false;
        }
    }
}

function judgeJpgPng($content){
    if (substr($content,0, 4)=="\x89\x50\x4e\x47"){
        return ".png";
    }elseif (substr($content,0, 2)=="\xff\xd8"){
        return "";//.jpg
    }else{
        return "";
    }
}

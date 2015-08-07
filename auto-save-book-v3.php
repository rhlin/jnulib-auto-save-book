<?php
/*
  Auto-save-book
  Author: rhlin
  Version: 0.3
  Date:2015-5-19
*/

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Max-Age: 3628800');

//$_POST['data']='{"urls":["http://www.baidu.com/img/baidu_sylogo1.gif"],"title":"aaaa"}'; /*测试数据*/

if($_POST && $_POST['data']){
    /*初始设置*/
    ini_set ('memory_limit', '500M'); /*最大PDF为500M*/
    $rawdata = json_decode($_POST['data']);
    //var_dump($rawdata);
    $data =  new stdClass();
    $data->{'title'} = $rawdata->{'title'};
    $data->{'urls'} =array();

    for ($i=0; $i <sizeof($rawdata->{'pagetype'}); $i++) { 
        //echo $i;
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
    for($i=0;$i<sizeof($img);$i++){
        @unlink($img[$i]);
    }
    @rmdir(pathinfo($img[0],PATHINFO_DIRNAME));

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
    var url_prefix = window.params.jpgPath;
    var pages =  window.params.pages;
    var pagetype = ["cov", "bok", "leg", "fow", "!", "", "att", "cov"];

    /*var srcArr = new Array();
    for(i in pagetype){
        for(var s = pages[i][0];s < pages[i][1];s++){
            var imgn = "";for (var imi = 0; imi < ( (6 - pagetype[i].length) - s.toString().length); imi++, imgn += "0");
            srcArr.push(url_prefix + pagetype[i] + imgn + s + ".jpg");
        }
    };
    //console.log(srcArr);
    */
    
    var submitData = {
        'title': title,
        'url_prefix': encodeURIComponent(url_prefix),
        'pagetype': pagetype,
        'pages': pages,
        'digit': 6,
        'url_bacfix': ".jpg"
    };
    $.ajax({
        type : "POST",
        url : "<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];?>",
        dataType : "JSON",
        crossDomain:true,
        data : 'data='+JSON.stringify(submitData),
        //Todo: 下载进度显示
        success : function(data){         
            $("body").append("<style>#close{display:block;position:absolute;width: 5px;height: 5px; right: 2px;top: 2px; }#my_box{display: block;  position: absolute;  top:20px; right:40px; width: 100px; height: 40px; line-height: 20px; padding: 8px;  border: 4px solid #0074a2;  background-color: white;  z-index:10000;  overflow: auto;text-align: center;}</style> <div id='my_box' class='my_box'>单击打开或右键另存为 <a href='<?php echo 'http://'.$_SERVER['HTTP_HOST'].'/';?>"+encodeURIComponent(data['file'])+"'  target='_blank' style='text-decoration:underline'>PDF</a><a id='close' href=javascript:$('.my_box').hide();>x</a></div>");
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

<div><br/>使用说明：把【存书】链接拖到书签栏，在图书馆书籍页面【点击阅读电子版】打开的页面上点击一下该书签，耐心等待足够长的时间，右上角会弹出生成pdf的对话框。<br/>注意：该版本不支持多个相同任务同时执行。<br/>欢迎反馈bug、建议和指导意见。</div>
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
        //$file +=judgeJpgPng($temp);
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

/*
function judgeJpgPng($content){
    if (substr($content,0, 4)=="\x89\x50\x4e\x47"){
        return ".png";
    }elseif (substr($content,0, 2)=="\xff\xd8"){
        return "";//.jpg
    }else{
        return "";
    }
}*/

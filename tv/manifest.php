<?php
// Per-TV web app manifest: "Install app" / "Add to Home screen" on a TV browser opens this TV full screen.
header('Content-Type: application/manifest+json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
$slug=(string)($_GET['slug']??'');
if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*-tv-[1-9][0-9]*$/D',$slug)){http_response_code(404);exit('{}');}
$base=rtrim(str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_NAME']))),'/');
echo json_encode(['name'=>'ShopSmoke TV · '.$slug,'short_name'=>'Store TV','id'=>$base.'/'.$slug.'/','start_url'=>$base.'/'.$slug.'/','scope'=>$base.'/'.$slug.'/','display'=>'fullscreen','orientation'=>'landscape','background_color'=>'#060918','theme_color'=>'#060918',
 'icons'=>[['src'=>$base.'/icons/icon-192.png','sizes'=>'192x192','type'=>'image/png'],['src'=>$base.'/icons/icon-512.png','sizes'=>'512x512','type'=>'image/png']]],JSON_UNESCAPED_SLASHES);

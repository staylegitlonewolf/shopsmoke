<?php
declare(strict_types=1);
/** ShopSmoke Marketing Studio v0.0.40 cPanel PHP TV publishing service.
 *  Server-side JSON registry in private HOME directory, owner login, image-only
 *  uploads, scheduled revisions, publicly readable TV manifests.
 *  NO default credentials; server remains locked until config.php is edited and
 *  setup is completed. Does NOT execute uploaded JavaScript/HTML.
 *  v0.0.10: session-free public TV polling, TV check-in heartbeats, revision
 *  rollback, owner deployment diagnostics and an optional cPanel cron runner:
 *    php /home/USER/public_html/marketing-editor/api/index.php cron
 *  The registry file keeps its v0.0.9 name (v009-data.json) so upgrades keep data.
 *  v0.0.11: works at the domain root (https://example.com/) or in a subfolder;
 *  optional MySQL registry storage (FT_STORAGE='mysql', written by setup/install.php);
 *  FT_PRIVATE_DIR in config.php pins the private folder outside the web root.
 *  v0.0.22: setup/install.php only asks for an email and password, so new installs use the
 *  private-folder registry (no database) and the owner logs in with that email.
 */
const FT_VERSION='0.0.40';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
function reply(int $http, array $value): never {http_response_code($http);echo json_encode($value,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
function fail(int $http,string $reason): never {reply($http,['ok'=>false,'error'=>$reason]);}
/* Uncaught errors still answer in JSON; database details are logged, never shown. */
set_exception_handler(function(Throwable $e){error_log('ShopSmoke API: '.$e->getMessage());if(PHP_SAPI==='cli'){fwrite(STDERR,$e->getMessage()."\n");exit(1);}reply(500,['ok'=>false,'error'=>'Server error — review the private PHP error log and deployment checks']);});
function installed_config():bool {return is_file(__DIR__.'/config.php');}
if (installed_config()) {require_once __DIR__.'/config.php';}
$secret = defined('FT_SETUP_KEY') ? (string)FT_SETUP_KEY : '';
$configured = strlen($secret)>=32 && !str_contains($secret,'REPLACE_WITH');
/* Base URL path of the app: '' at the domain root, '/marketing-editor' in a subfolder. */
if(PHP_SAPI==='cli')$basePath=defined('FT_BASE_PATH')?(string)FT_BASE_PATH:'';
else{$basePath=str_replace('\\','/',dirname(str_replace('\\','/',dirname((string)($_SERVER['SCRIPT_NAME']??'/api/index.php')))));}
$basePath=rtrim(preg_replace('#[^/a-zA-Z0-9_-]#','',$basePath),'/');
$action=(string)($_GET['action']??'status');
$method=(string)($_SERVER['REQUEST_METHOD']??'GET');
/* cPanel cron (optional): applies due schedules even when no TV or owner is online. */
if(PHP_SAPI==='cli'){
 if(!$configured){fwrite(STDERR,"ShopSmoke publishing is not configured (api/config.php)\n");exit(1);}
 try{$n=transact(fn(&$db)=>apply_due($db));@file_put_contents(priv_dir().'/cron-last-run.txt',iso());fwrite(STDOUT,iso()." ShopSmoke cron: applied $n due schedule(s)\n");exit(0);}
 catch(Throwable $e){fwrite(STDERR,'ShopSmoke cron failed: '.$e->getMessage()."\n");exit(1);}
}
if (!in_array($method,['GET','POST'],true)) fail(405,'Unsupported method');
$https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off') || (string)($_SERVER['SERVER_PORT']??'')==='443' || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO']??''))==='https';
$local=in_array($_SERVER['HTTP_HOST']??'', ['localhost','127.0.0.1','localhost:8765','127.0.0.1:8765'],true);
if (!$https && !$local && !in_array($action,['status'],true)) fail(426,'HTTPS is required to use the publishing service');
/* Public, read-only TV manifest. Handled BEFORE session_start so 1-second polling
   from every TV does not create a new PHP session file on each request. */
if($action==='screen'){
 if($method!=='GET')fail(405,'GET required');
 if(!$configured)fail(503,'Publishing is locked until the owner completes /setup/install.php');
 $slug=valid_id($_GET['slug']??'');
 $payload=transact(function(&$db)use($slug){apply_due($db);$si=find_index($db['screens'],$slug,'slug');if($si<0)return null;$screen=$db['screens'][$si];$st=find_index($db['stores'],$screen['store_id']);$rev=$screen['revision_id']?find_index($db['revisions'],$screen['revision_id']):-1;
 return ['slug'=>$slug,'name'=>$screen['name'],'store'=>$st>=0?$db['stores'][$st]['name']:'Store','revision_id'=>$screen['revision_id'],'updated_at'=>$screen['updated_at'],'pages'=>$rev>=0?public_pages($db['revisions'][$rev]['pages']):[]];});
 if(!$payload)fail(404,'TV screen not found');
 if(!isset($_GET['preview'])){$showing=(string)($_GET['showing']??'');record_heartbeat($slug,preg_match('/^[a-f0-9]{26}$/D',$showing)?$showing:'');} /* v0.0.15: preview=1 (editor) is not a TV check-in */
 /* v0.0.22: a TV already showing this revision doesn't need its page list again every second */
 if((string)($_GET['showing']??'')===(string)$payload['revision_id']&&$payload['revision_id'])unset($payload['pages']);
 reply(200,['ok'=>true,'screen'=>$payload]);
}
ini_set('session.use_strict_mode','1');
ini_set('session.use_only_cookies','1');
ini_set('session.cookie_httponly','1');
/* v0.0.35: a login lasts 30 days. Sessions live in the private folder, because hosts clear their shared session folder after about 24 minutes.
   If that folder can't be made, everything still works and logins just end sooner. */
const LOGIN_SECONDS=30*86400;
$sessions=(defined('FT_PRIVATE_DIR')?rtrim((string)FT_PRIVATE_DIR,'/'):default_priv_dir()).'/sessions';
if(is_dir($sessions)||@mkdir($sessions,0700,true)){session_save_path($sessions);ini_set('session.gc_maxlifetime',(string)LOGIN_SECONDS);ini_set('session.gc_probability','1');ini_set('session.gc_divisor','200');}
session_name('FTMARKETING');
session_set_cookie_params(['lifetime'=>LOGIN_SECONDS,'path'=>$basePath.'/','secure'=>$https,'httponly'=>true,'samesite'=>'Strict']);
@session_start();
if (!isset($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(16));
function is_auth():bool{return (!empty($_SESSION['owner'])||!empty($_SESSION['uid'])) && ($_SESSION['last']??0)>time()-LOGIN_SECONDS;}
/** v0.0.17: permissions the owner can give each person. Owner-only (never delegable): stores, team accounts,
 *  deployment check, deleting TVs, clearing activity. Presets: manager = everything below, staff = drafts + photos.
 *  v0.0.21: publish = show on a TV now; schedule = publish later; submit = send a photo for approval;
 *  approve = approve or decline what others sent. Staff now also get submit. */
const PERMS=['publish','schedule','submit','approve','tvs','history','activity','drafts','photos','delete_any'];
const PRESETS=['manager'=>['publish','schedule','submit','approve','tvs','history','activity','drafts','photos','delete_any'],'staff'=>['drafts','photos','submit']];
/** Phone numbers work as logins: "(305) 555-1234", "305-555-1234" and "3055551234" are the same login. */
function norm_login(string $s):string{$s=strtolower(trim($s));if(preg_match('/^\+?[0-9 ().-]{7,24}$/D',$s))return preg_replace('/[^0-9]/','',$s);return $s;}
/** v0.0.17: the signed-in person. Owner = email from setup (v0.0.22), or a blank username on older installs; team users have a role and stores. */
function load_user(array $db):?array{
 /* v0.0.22: changing the owner password moves owner_version on, which ends every other logged-in owner session. */
 if(!empty($_SESSION['owner'])){if((int)($_SESSION['ov']??0)!==(int)($db['owner_version']??0))return null;return ['id'=>'owner','name'=>'Owner','username'=>'owner','email'=>(string)($db['owner_email']??''),'role'=>'owner','stores'=>array_column($db['stores'],'id'),'perms'=>PERMS];}
 /* v0.0.35: a new password moves pv on, which ends that person's other logins. They last 30 days, so a reset has to reach them. */
 $i=find_index($db['users']??[],(string)($_SESSION['uid']??''));if($i<0)return null;$u=$db['users'][$i];if(empty($u['active'])||(int)($_SESSION['pv']??0)!==(int)($u['pv']??0))return null;
 return ['id'=>$u['id'],'name'=>$u['name'],'username'=>$u['username'],'role'=>$u['role'],'stores'=>array_values(array_intersect($u['stores']??[],array_column($db['stores'],'id'))),'perms'=>user_perms($u)];
}
function user_perms(array $u):array{$p=$u['perms']??(PRESETS[$u['role']]??[]);if(empty($u['perms_v'])&&in_array('publish',$p,true))$p[]='schedule';return array_values(array_intersect(PERMS,$p));}
function public_user(array $u):array{return ['id'=>$u['id'],'name'=>$u['name'],'username'=>$u['username'],'phone'=>$u['phone']??'','role'=>$u['role'],'stores'=>array_values($u['stores']??[]),'perms'=>user_perms($u),'active'=>!empty($u['active']),'created_at'=>$u['created_at']??null];}
function authed():void{if (!is_auth())fail(401,'Owner login required');$_SESSION['last']=time();}
function csrf():void{if(!hash_equals((string)($_SESSION['csrf']??''),(string)($_SERVER['HTTP_X_FT_CSRF']??'')))fail(403,'Session security token missing or expired');}
function post_json():array{$raw=file_get_contents('php://input');if(strlen($raw)>500000)fail(413,'Request metadata is too large');$v=json_decode($raw,true);if(!is_array($v))fail(400,'Invalid JSON request');return $v;}
function text_field($v,int $max=100):string{return substr(trim((string)($v??'')),0,$max);}
function valid_id($v):string{$s=(string)$v;if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$s) || strlen($s)>110)fail(400,'Invalid identifier');return $s;}
function uid():string{return bin2hex(random_bytes(13));}
function iso():string{return gmdate('Y-m-d\TH:i:s\Z');}
function default_data():array{return [
 'version'=>1,'stores'=>[
 ['id'=>'galaxy-goulds','name'=>'Galaxy Goulds','website'=>'/galaxy-goulds/','next_tv_number'=>1],
 ['id'=>'galaxy-wynwood','name'=>'Galaxy Wynwood','website'=>'/galaxy-wynwood/','next_tv_number'=>1],
 ['id'=>'the-farmacy','name'=>'The Farmacy','website'=>'/the-farmacy/','next_tv_number'=>1],
 ['id'=>'57th-ave','name'=>'57th Ave','website'=>'/57th-ave/','next_tv_number'=>1],
 ['id'=>'the-square','name'=>'The Square Smoke Shop','website'=>'/the-square/','next_tv_number'=>1]
 ],'screens'=>[],'revisions'=>[],'schedules'=>[],'activity'=>[],'password_hash'=>null ];}
/** Store outside public_html, not beneath a browser-addressable subfolder.
 *  config.php FT_PRIVATE_DIR wins; otherwise the parent of public_html (or of the
 *  document root), which is the same folder v0.0.9/v0.0.10 used in /marketing-editor/. */
function default_priv_dir():string{
 for($d=dirname(__DIR__);dirname($d)!==$d;$d=dirname($d))if(basename($d)==='public_html')return dirname($d).'/.4tier-marketing-private';
 $doc=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));return ($doc?dirname($doc):dirname(__DIR__,2)).'/.4tier-marketing-private';
}
function priv_dir():string{
 static $path=null;if($path!==null)return $path;
 $p=defined('FT_PRIVATE_DIR')?rtrim((string)FT_PRIVATE_DIR,'/\\'):default_priv_dir();
 if(!is_dir($p)&&!@mkdir($p,0700,true))fail(500,'Cannot create a private storage directory outside the public website; ask your host to enable it');
 @chmod($p,0700);return $path=$p;
}
function storage_mysql():bool{return defined('FT_STORAGE')&&FT_STORAGE==='mysql';}
function pdo():PDO{static $pdo=null;if($pdo)return $pdo;
 if(!extension_loaded('pdo_mysql'))throw new RuntimeException('The PHP pdo_mysql extension is not enabled');
 $dsn='mysql:host='.FT_DB_HOST.(defined('FT_DB_PORT')?';port='.(int)FT_DB_PORT:'').';dbname='.FT_DB_NAME.';charset=utf8mb4';
 return $pdo=new PDO($dsn,FT_DB_USER,FT_DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);}
/** MySQL storage keeps the same registry document in one row, locked with SELECT … FOR UPDATE,
 *  so every action behaves exactly like the file registry (see BACKEND-PLAN.md). */
function transact_mysql(callable $func){$pdo=pdo();$pdo->beginTransaction();
 try{$raw=$pdo->query('SELECT data FROM ft_registry WHERE id=1 FOR UPDATE')->fetchColumn();$data=$raw?json_decode((string)$raw,true):null;
 if(!is_array($data))$data=default_data();$old=json_encode($data,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);$value=$func($data);
 $new=json_encode($data,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);if($new===false)throw new RuntimeException('Cannot encode state');
 if($new!==$old||$raw===false)$pdo->prepare('INSERT INTO ft_registry (id,data,updated_at) VALUES (1,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE data=VALUES(data),updated_at=VALUES(updated_at)')->execute([$new]);
 $pdo->commit();return $value;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}}
function transact(callable $func){if(storage_mysql())return transact_mysql($func);$dir=priv_dir();$fp=@fopen($dir.'/v009-data.json','c+');if(!$fp)fail(500,'Private storage is not writable');
 if(!flock($fp,LOCK_EX)){fclose($fp);fail(500,'Storage lock unavailable');}
 try{rewind($fp);$raw=stream_get_contents($fp);$data=$raw?json_decode($raw,true):null;
 if(!is_array($data))$data=default_data();$old=json_encode($data,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);$value=$func($data);
 $new=json_encode($data,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
 if($new===false)throw new RuntimeException('Cannot encode state');
 if($new!==$old){rewind($fp);ftruncate($fp,0);if(fwrite($fp,$new)===false)throw new RuntimeException('Cannot save state');fflush($fp);}
 flock($fp,LOCK_UN);fclose($fp);return $value;
 }catch(Throwable $e){flock($fp,LOCK_UN);fclose($fp);throw $e;}}
function audit(array &$db,string $action,string $info):void {global $ME;array_unshift($db['activity'],['at'=>iso(),'action'=>$action,'info'=>substr($info,0,200),'by'=>$ME['name']??null]);$db['activity']=array_slice($db['activity'],0,500);}
function find_index(array $items,string $id,string $field='id'):int{foreach($items as $i=>$v)if(($v[$field]??'')===$id)return $i;return -1;}
function screen_url(string $slug):string{global $basePath;return $basePath.'/'.$slug.'/';}
function fallback_url(string $slug):string{global $basePath;return $basePath.'/tv/player.php?slug='.$slug;}
function content_url(string $file):string{global $basePath;return $basePath.'/tv-content/'.$file;}
/** Image URLs are derived on every read so they stay correct for cron-activated revisions or a moved install. */
function public_pages(array $pages):array{return array_map(fn($p)=>['name'=>$p['name']??'Page','image'=>$p['image'],'src'=>content_url(basename((string)$p['image'])),'duration'=>$p['duration']??10,'transition'=>$p['transition']??'fade','transitionDuration'=>$p['transitionDuration']??.8,'video'=>public_video($p['video']??null)],$pages);}
/** v0.0.22: a page can play a video on the TV. Its JPEG stays as the poster, so a TV that can't play the video still shows the page. */
function public_video($v):?array{if(!is_array($v))return null;if(($v['kind']??'')==='file')$v['src']=content_url(basename((string)($v['file']??'')));return $v;}
function clean_asset($v):?string{$s=(string)($v??'');return preg_match('/^[A-Za-z0-9-]{4,100}$/D',$s)?$s:null;}
/** A page's video source plus its optional cut (v0.0.24): start and end in seconds. */
function clean_video($v):?array{$out=video_source($v);if(!$out)return null;$start=max(0,(float)($v['start']??0));$end=(float)($v['end']??0);if($start>0)$out['start']=$start;if($end>$start)$out['end']=$end;if(!empty($v['sound']))$out['sound']=true;return $out;}
function video_source($v):?array{if(!is_array($v))return null;$kind=(string)($v['kind']??'');
 if($kind==='file'){$f=(string)($v['file']??'');if(!preg_match('/^[a-f0-9]{26}\.(mp4|webm)$/D',$f)||!is_file(dirname(__DIR__).'/tv-content/'.$f))fail(400,'A video on this page is no longer on the server');return ['kind'=>'file','file'=>$f];}
 if($kind==='youtube'&&preg_match('/^[A-Za-z0-9_-]{6,20}$/D',(string)($v['id']??'')))return ['kind'=>'youtube','id'=>(string)$v['id']];
 if($kind==='vimeo'&&preg_match('/^[0-9]{5,12}$/D',(string)($v['id']??'')))return ['kind'=>'vimeo','id'=>(string)$v['id']];
 if($kind==='url'){$u=(string)($v['url']??'');if(strlen($u)<=500&&str_starts_with($u,'https://')&&filter_var($u,FILTER_VALIDATE_URL))return ['kind'=>'url','url'=>$u];}
 fail(400,'A video link on this page is not valid');}
function heartbeat_file(string $slug):string{return priv_dir().'/heartbeats/'.$slug.'.json';}
function record_heartbeat(string $slug,string $showing):void{$dir=priv_dir().'/heartbeats';if(!is_dir($dir))@mkdir($dir,0700,true);
 @file_put_contents(heartbeat_file($slug),json_encode(['at'=>iso(),'showing'=>$showing,'agent'=>substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,140)]),LOCK_EX);}
function read_heartbeat(string $slug):?array{$f=heartbeat_file($slug);if(!is_file($f))return null;$v=json_decode((string)@file_get_contents($f),true);return is_array($v)?$v:null;}
function activate_revision(array &$db,string $screenId,string $revisionId,string $why='published'):void{
 $si=find_index($db['screens'],$screenId);$ri=find_index($db['revisions'],$revisionId);
 if($si<0||$ri<0)throw new RuntimeException('Missing screen or revision');
 $pub=dirname(__DIR__).'/tv-content';if(!is_dir($pub)&&!mkdir($pub,0755,true))throw new RuntimeException('Cannot create TV content folder');
 $priv=priv_dir().'/staged';
 foreach($db['revisions'][$ri]['pages'] as &$p){$img=basename((string)$p['image']);if(!preg_match('/^[a-f0-9]{26}\.jpg$/',$img))throw new RuntimeException('Invalid image filename');
 $dest=$pub.'/'.$img;if(!is_file($dest)){if(!is_file($priv.'/'.$img) || !@copy($priv.'/'.$img,$dest))throw new RuntimeException('Could not publish image to TV content folder');@chmod($dest,0644);}
 $p['src']=content_url($img);
 }unset($p);
 $db['screens'][$si]['revision_id']=$revisionId;$db['screens'][$si]['updated_at']=iso();
 audit($db,$why,$db['screens'][$si]['slug'].' revision '.$revisionId);
}
function apply_due(array &$db):int{$n=0;usort($db['schedules'],fn($a,$b)=>strcmp((string)($a['at']??''),(string)($b['at']??'')));foreach($db['schedules'] as &$item){if(($item['status']??'')==='pending' && strtotime($item['at'])<=time()){
 try{activate_revision($db,$item['screen_id'],$item['revision_id']);$item['status']='published';$item['executed_at']=iso();$n++;}
 catch(Throwable $e){$item['status']='failed';$item['error']='Could not apply scheduled revision';audit($db,'schedule-failed',(string)$item['id']);}
 }}unset($item);return $n;}
/** Owner-facing summary of one screen: what it shows now, its recent revisions and its last TV check-in. */
function screen_summary(array $db,array $screen):array{
 $screen['url']=screen_url($screen['slug']);$screen['fallback_url']=fallback_url($screen['slug']);
 $hb=read_heartbeat($screen['slug']);$screen['last_seen']=$hb['at']??null;$screen['showing']=$hb['showing']??null;$screen['agent']=$hb['agent']??null;
 $pending=[];foreach($db['schedules'] as $s)if($s['screen_id']===$screen['id']&&$s['status']==='pending')$pending[$s['revision_id']]=$s['at'];
 $mine=array_values(array_filter($db['revisions'],fn($r)=>($r['screen_id']??'')===$screen['id']));usort($mine,fn($a,$b)=>strcmp((string)$b['created_at'],(string)$a['created_at']));
 $screen['revisions']=array_map(function($r)use($screen,$pending){$first=basename((string)($r['pages'][0]['image']??''));$public=$first&&is_file(dirname(__DIR__).'/tv-content/'.$first);
  return ['id'=>$r['id'],'created_at'=>$r['created_at'],'mode'=>$r['mode']??'now','pages'=>count($r['pages']),'seconds'=>array_sum(array_map(fn($p)=>(float)($p['duration']??10),$r['pages'])),'thumb'=>$public?content_url($first):null,'images'=>$public?array_values(array_map(fn($p)=>content_url(basename((string)$p['image'])),$r['pages'])):[],
  'state'=>$r['id']===$screen['revision_id']?'live':(isset($pending[$r['id']])?'scheduled':'previous'),'scheduled_at'=>$pending[$r['id']]??null,'draft_id'=>$r['draft_id']??null];},array_slice($mine,0,8));
 return $screen;
}
function max_upload():int{$u=ini_bytes((string)ini_get('upload_max_filesize'));$p=ini_bytes((string)ini_get('post_max_size'));$m=5*1048576;if($u>0)$m=min($m,$u);if($p>0)$m=min($m,$p-65536);return max(262144,$m);}
function ini_bytes(string $v):int{$v=trim($v);if($v==='')return 0;$n=(float)$v;$u=strtolower(substr($v,-1));return (int)($n*match($u){'g'=>1073741824,'m'=>1048576,'k'=>1024,default=>1});}
if($action==='status'){
 if(!$configured)reply(200,['ok'=>true,'version'=>FT_VERSION,'backend'=>'unconfigured','installed'=>false,'authenticated'=>false,'setup_instructions'=>'Open /setup/install.php to configure the MySQL database and create the owner account']);
 try{$st=transact(fn($db)=>['installed'=>!empty($db['password_hash']),'user'=>is_auth()?load_user($db):null]);$installed=$st['installed'];}
 catch(Throwable $e){reply(200,['ok'=>false,'version'=>FT_VERSION,'backend'=>'storage-error','installed'=>false,'error'=>'Storage unavailable — check the database connection and PHP error log']);}
 $who=$installed&&is_auth()?$st['user']:null;reply(200,['ok'=>true,'version'=>FT_VERSION,'backend'=>'php','installed'=>$installed,'authenticated'=>(bool)$who,'csrf'=>$who?$_SESSION['csrf']:null,'user'=>$who?['name'=>$who['name'],'role'=>$who['role'],'email'=>$who['email']??null,'stores'=>$who['stores'],'perms'=>$who['perms']]:null,'max_upload'=>max_upload()]);
}
if(!$configured)fail(503,'Publishing is locked until the owner completes /setup/install.php');
if($action==='setup'&&$method==='POST'){
 $in=post_json();$pw=(string)($in['password']??'');$key=(string)($in['setup_key']??'');
 if(strlen($pw)<4||strlen($pw)>20)fail(400,'Owner password must be 4 to 20 characters');
 if(!hash_equals($secret,$key))fail(403,'Incorrect cPanel setup key');
 $created=transact(function(&$db)use($pw){if(!empty($db['password_hash']))return false;$db['password_hash']=password_hash($pw,PASSWORD_DEFAULT);audit($db,'setup','Owner access initialized');return true;});
 if(!$created)fail(409,'Owner account already exists');session_regenerate_id(true);$_SESSION['owner']=true;$_SESSION['last']=time();reply(200,['ok'=>true,'csrf'=>$_SESSION['csrf']]);
}
function login_rate(bool $success=false,bool $failed=false):bool {
 $key=hash('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'));
 $dir=priv_dir().'/login-rates';if(!is_dir($dir))@mkdir($dir,0700,true);
 $fp=@fopen($dir.'/'.$key.'.json','c+');if(!$fp)return false;
 flock($fp,LOCK_EX);$raw=stream_get_contents($fp);$v=$raw?json_decode($raw,true):[];if(!is_array($v))$v=[];
 $now=time();if(($v['start']??0)<$now-900)$v=['start'=>$now,'attempts'=>0,'locked_until'=>0];
 if($success)$v=['start'=>$now,'attempts'=>0,'locked_until'=>0];
 elseif($failed){$v['attempts']=(int)($v['attempts']??0)+1;if($v['attempts']>=8)$v['locked_until']=$now+900;}
 rewind($fp);ftruncate($fp,0);fwrite($fp,json_encode($v));flock($fp,LOCK_UN);fclose($fp);
 return ($v['locked_until']??0)>$now;
}
if($action==='login'&&$method==='POST'){
 $in=post_json();$pw=(string)($in['password']??'');$now=time();if(login_rate())fail(429,'Too many failed logins from this network; try again in 15 minutes');$_SESSION['login_count']=(int)($_SESSION['login_count']??0);
 if(($_SESSION['lock_until']??0)>$now)fail(429,'Too many attempts; try again later');
 $username=norm_login((string)($in['username']??''));
 /* v0.0.24.1: a site set up before v0.0.22 has no owner email. The owner's first login with an email and the right password saves it, so email login and Forgot password work from then on. */
 $found=transact(function($db)use($username){$email=strtolower((string)($db['owner_email']??''));$i=find_index($db['users']??[],$username,'username');$first=$email===''&&$i<0&&strlen($username)<=120&&filter_var($username,FILTER_VALIDATE_EMAIL);if($username===''||$username==='owner'||($email!==''&&$username===$email)||$first)return ['hash'=>(string)($db['password_hash']??''),'uid'=>null,'ov'=>(int)($db['owner_version']??0),'first'=>$first?$username:''];if($i<0||empty($db['users'][$i]['active']))return null;return ['hash'=>(string)$db['users'][$i]['password_hash'],'uid'=>$db['users'][$i]['id'],'pv'=>(int)($db['users'][$i]['pv']??0)];});
 $owner=$found&&$found['uid']===null;
 if(!$found||!$found['hash']||!password_verify($pw,$found['hash'])){
 login_rate(false,true);$_SESSION['login_count']++;if($_SESSION['login_count']>=5){$_SESSION['lock_until']=$now+900;$_SESSION['login_count']=0;}fail(401,'Incorrect email, username or password');
 }
 login_rate(true,false);if($owner&&!empty($found['first']))transact(function(&$db)use($found){if(empty($db['owner_email'])){$db['owner_email']=$found['first'];audit($db,'owner-updated','Owner email saved at login');}});session_regenerate_id(true);unset($_SESSION['owner'],$_SESSION['uid']);if($owner){$_SESSION['owner']=true;$_SESSION['ov']=$found['ov'];}else{$_SESSION['uid']=$found['uid'];$_SESSION['pv']=$found['pv'];}$_SESSION['last']=$now;$_SESSION['login_count']=0;reply(200,['ok'=>true,'csrf'=>$_SESSION['csrf']]);
}
/* v0.0.22: owner password recovery by email: an 8-digit code that lasts 15 minutes and allows 5 tries. The reply never says
   whether the email matched. The code is stored hashed; the email holds only the code, never a link built from the request. */
function send_mail(string $to,string $subject,string $body):bool{$host=preg_replace('/^www\./','',strtolower((string)($_SERVER['SERVER_NAME']??'localhost')));$from='no-reply@'.(preg_replace('/[^a-z0-9.-]/','',$host)?:'localhost');
 return @mail($to,$subject,$body,'From: ShopSmoke <'.$from.">\r\nContent-Type: text/plain; charset=UTF-8",'-f'.$from);}
if($action==='recover_request'&&$method==='POST'){
 if(login_rate())fail(429,'Too many tries; wait 15 minutes');login_rate(false,true);
 $email=strtolower(trim((string)(post_json()['email']??'')));$code=(string)random_int(10000000,99999999);
 $send=transact(function(&$db)use($email,$code){$owner=strtolower((string)($db['owner_email']??''));if($owner===''||$email!==$owner||($db['password_reset']['sent']??0)>time()-60)return false;
  $db['password_reset']=['hash'=>password_hash($code,PASSWORD_DEFAULT),'sent'=>time(),'expires'=>time()+900,'tries'=>0];audit($db,'recovery-requested','Password recovery code emailed');return true;});
 if($send&&!send_mail($email,'Your ShopSmoke recovery code','Your ShopSmoke password recovery code is '.substr($code,0,4).' '.substr($code,4).".\r\n\r\nIt works for 15 minutes. If you did not ask for this, ignore this email: your password has not changed.\r\n"))error_log('ShopSmoke: could not send the recovery email');
 reply(200,['ok'=>true]);
}
if($action==='recover_reset'&&$method==='POST'){
 if(login_rate())fail(429,'Too many tries; wait 15 minutes');$in=post_json();$email=strtolower(trim((string)($in['email']??'')));$code=preg_replace('/[^0-9]/','',(string)($in['code']??''));$pw=(string)($in['password']??'');
 if(strlen($pw)<4||strlen($pw)>20)fail(400,'The new password must be 4 to 20 characters');
 $ok=transact(function(&$db)use($email,$code,$pw){$r=$db['password_reset']??null;if(!$r||$email!==strtolower((string)($db['owner_email']??''))||$r['expires']<time()||$r['tries']>=5)return false;
  if(!password_verify($code,$r['hash'])){$db['password_reset']['tries']++;return false;}unset($db['password_reset']);$db['password_hash']=password_hash($pw,PASSWORD_DEFAULT);$db['owner_version']=(int)($db['owner_version']??0)+1;audit($db,'recovery-used','Owner password changed with an emailed code');return true;});
 if(!$ok){login_rate(false,true);fail(400,'That code is not right or has expired. Send a new code and try again.');}login_rate(true,false);reply(200,['ok'=>true]);
}
if($action==='logout'&&$method==='POST'){authed();csrf();$_SESSION=[];session_destroy();reply(200,['ok'=>true]);}
authed();
$ME=transact(fn($db)=>load_user($db));if(!$ME){$_SESSION=[];fail(401,'Your account is no longer active — sign in again');}
function need_role(string ...$roles):void{global $ME;if(!in_array($ME['role'],$roles,true))fail(403,'Your account ('.$ME['role'].') cannot do this — ask the owner');}
function has_perm(string $p):bool{global $ME;return in_array($p,$ME['perms'],true);}
function need_perm(string $p):void{static $label=['publish'=>'publish to TVs','schedule'=>'schedule TV updates','submit'=>'send photos for approval','approve'=>'approve photos','tvs'=>'add or rename TVs','history'=>'manage TV history and schedules','activity'=>'see activity','drafts'=>'save drafts','photos'=>'upload photos','delete_any'=>'delete other people’s work'];if(!has_perm($p))fail(403,'Your account is not allowed to '.($label[$p]??$p).' — ask the owner');}
function can_store(string $id):bool{global $ME;return $ME['role']==='owner'||in_array($id,$ME['stores'],true);}
function need_store(string $id):void{if(!can_store($id))fail(403,'Your account is not assigned to this store');}
function screen_store(string $screenId):?string{return transact(function($db)use($screenId){$i=find_index($db['screens'],$screenId);return $i<0?null:$db['screens'][$i]['store_id'];});}
function need_screen(string $screenId):void{$s=screen_store($screenId);if($s===null)fail(404,'TV not found');need_store($s);}
function send_file(string $path,string $type):never{if(!is_file($path))fail(404,'File not found');header('Content-Type: '.$type);header('Content-Length: '.filesize($path));header('Cache-Control: private, no-store');readfile($path);exit;}
function store_dir(string $sub):string{$d=priv_dir().'/'.$sub;if(!is_dir($d)&&!@mkdir($d,0700,true))fail(500,'Private storage is not writable');return $d;}
/** Chunked uploads (drafts and photos can exceed the host's upload limit): parts live in uploads/<id>/ until committed. */
function assemble_upload(string $uploadId,int $parts,int $maxBytes):string{
 if(!preg_match('/^[a-f0-9]{26}$/D',$uploadId)||empty($_SESSION['uploads'][$uploadId]))fail(400,'Upload not found — start again');
 if($parts<1||$parts>400)fail(400,'Invalid upload');$dir=store_dir('uploads').'/'.$uploadId;$out=$dir.'.bin';$fo=fopen($out,'wb');$total=0;
 for($i=0;$i<$parts;$i++){$p=$dir.'/'.$i.'.part';if(!is_file($p)){fclose($fo);fail(400,'Upload incomplete (part '.($i+1).' missing) — try again');}$total+=filesize($p);if($total>$maxBytes){fclose($fo);fail(413,'File is too large ('.round($total/1048576).' MB)');}$fi=fopen($p,'rb');stream_copy_to_stream($fi,$fo);fclose($fi);@unlink($p);}
 fclose($fo);@rmdir($dir);unset($_SESSION['uploads'][$uploadId]);return $out;}
function clean_uploads():void{foreach(glob(store_dir('uploads').'/*')?:[] as $p){if(filemtime($p)<time()-21600){if(is_dir($p)){array_map('unlink',glob($p.'/*')?:[]);@rmdir($p);}else @unlink($p);}}}
function save_thumb(string $dest):bool{if(empty($_FILES['thumb'])||($_FILES['thumb']['error']??99)!==UPLOAD_ERR_OK)return false;$f=$_FILES['thumb'];if($f['size']>600000)return false;$i=@getimagesize($f['tmp_name']);if(!$i||$i[2]!==IMAGETYPE_JPEG)return false;return move_uploaded_file($f['tmp_name'],$dest);}
function draft_row(array $d):array{return ['id'=>$d['id'],'name'=>$d['name'],'store_id'=>$d['store_id'],'kind'=>$d['kind'],'pages'=>$d['pages'],'bytes'=>$d['bytes'],'has_thumb'=>!empty($d['has_thumb']),'created_at'=>$d['created_at'],'updated_at'=>$d['updated_at'],'by'=>$d['by'],'by_name'=>$d['by_name']??null];}
if($action==='dashboard'&&$method==='GET'){
 $data=transact(function(&$db){global $ME;apply_due($db);$stores=array_values(array_filter($db['stores'],fn($s)=>can_store($s['id'])));$ids=array_column($stores,'id');
  $screens=array_values(array_filter($db['screens'],fn($s)=>in_array($s['store_id'],$ids,true)));$sids=array_column($screens,'id');$canLog=has_perm('activity');
  return ['stores'=>$stores,'screens'=>array_map(fn($s)=>screen_summary($db,$s),$screens),'schedules'=>array_values(array_filter($db['schedules'],fn($s)=>in_array($s['screen_id'],$sids,true))),'activity'=>$canLog?array_slice($db['activity'],0,10):[],'activity_total'=>$canLog?count($db['activity']):0,
  'users'=>$ME['role']==='owner'?array_map('public_user',$db['users']??[]):[],'approvals_pending'=>has_perm('approve')?count(array_filter($db['approvals']??[],fn($a)=>$a['status']==='pending'&&in_array($a['store_id'],$ids,true))):0,'me'=>['name'=>$ME['name'],'role'=>$ME['role'],'stores'=>$ME['stores'],'perms'=>$ME['perms']]];});
 reply(200,['ok'=>true,'version'=>FT_VERSION,'server_time'=>iso(),'max_upload'=>max_upload(),'data'=>$data,'csrf'=>$_SESSION['csrf']]);
}
if($action==='diagnostics'&&$method==='GET'){need_role('owner');
 $priv=priv_dir();$pub=dirname(__DIR__).'/tv-content';$checks=[];
 $add=function(string $label,string $state,string $detail)use(&$checks){$checks[]=['label'=>$label,'state'=>$state,'detail'=>$detail];};
 $add('PHP version',version_compare(PHP_VERSION,'8.1.0','>=')?'pass':'fail',PHP_VERSION.' (8.1 or newer required)');
 if(storage_mysql()){$dbOk=false;$dbDetail='';try{$dbOk=pdo()->query('SELECT COUNT(*) FROM ft_registry')->fetchColumn()!==false;$dbDetail='MySQL database '.FT_DB_NAME.' connected';}catch(Throwable $e){$dbDetail='Cannot reach MySQL database '.FT_DB_NAME.' — check api/config.php';}$add('Registry storage',$dbOk?'pass':'fail',$dbDetail);}else $add('Registry storage','info','File registry in the private folder (MySQL not configured)');
 foreach(['json','session'] as $ext)$add('PHP extension: '.$ext,extension_loaded($ext)?'pass':'fail',extension_loaded($ext)?'Loaded':'Missing');
 $add('File uploads',ini_get('file_uploads')?'pass':'fail',ini_get('file_uploads')?'Enabled':'Disabled in php.ini');
 $up=ini_bytes((string)ini_get('upload_max_filesize'));$post=ini_bytes((string)ini_get('post_max_size'));
 $add('upload_max_filesize',$up>=5*1048576?'pass':'fail',(string)ini_get('upload_max_filesize').' (needs 5M or more)');
 $add('post_max_size',$post===0||$post>=6*1048576?'pass':'fail',(string)ini_get('post_max_size').' (needs 6M or more)');
 $add('Private storage writable',is_writable($priv)?'pass':'fail',is_writable($priv)?'Registry folder is writable':'PHP cannot write the private registry folder');
 $doc=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));$rp=realpath($priv);
 $add('Private storage outside web root',$doc&&$rp&&str_starts_with($rp.'/',rtrim($doc,'/').'/')?'fail':($doc?'pass':'warn'),$doc?($rp&&str_starts_with($rp.'/',rtrim($doc,'/').'/')?'Registry is under the public web root — move it':'Registry is not under the public web root'):'DOCUMENT_ROOT unavailable; confirm manually');
 $add('TV content folder writable',(is_dir($pub)?is_writable($pub):is_writable(dirname($pub)))?'pass':'fail','tv-content/ must be writable by PHP');
 $add('tv-content protection file',is_file($pub.'/.htaccess')?'pass':'fail',is_file($pub.'/.htaccess')?'tv-content/.htaccess present':'tv-content/.htaccess missing — re-upload it');
 $add('Clean URL rules file',is_file(dirname(__DIR__).'/.htaccess')?'pass':'warn',is_file(dirname(__DIR__).'/.htaccess')?'.htaccess present (the browser test below confirms mod_rewrite)':'.htaccess missing — clean TV URLs will 404; the fallback player URL still works');
 $add('HTTPS',$https?'pass':'warn',$https?'Secure connection detected':'Not detected — required outside localhost');
 $free=@disk_free_space($priv);$add('Free disk space',$free===false?'warn':($free>200*1048576?'pass':'warn'),$free===false?'Unknown':round($free/1048576).' MB free');
 $cron=is_file($priv.'/cron-last-run.txt')?trim((string)file_get_contents($priv.'/cron-last-run.txt')):null;
 $add('Scheduled-publish cron (optional)',$cron&&strtotime($cron)>time()-600?'pass':'info',$cron?'Last run '.$cron:'Not configured — schedules apply when a TV or the owner dashboard checks in');
 $staged=glob($priv.'/staged/*.jpg')?:[];$public=glob($pub.'/*.jpg')?:[];
 $counts=transact(fn($db)=>['stores'=>count($db['stores']),'screens'=>count($db['screens']),'revisions'=>count($db['revisions']),'schedules_pending'=>count(array_filter($db['schedules'],fn($s)=>$s['status']==='pending'))]);
 $counts['staged_images']=count($staged);$counts['staged_mb']=round(array_sum(array_map('filesize',$staged))/1048576,1);$counts['public_images']=count($public);$counts['public_mb']=round(array_sum(array_map('filesize',$public))/1048576,1);
 reply(200,['ok'=>true,'version'=>FT_VERSION,'server_time'=>iso(),'checks'=>$checks,'counts'=>$counts]);
}
/* v0.0.15: activity log, newest first, 10 per page in TV Manager (limit up to 500 for Download). */
if($action==='activity'&&$method==='GET'){need_perm('activity');
 $offset=max(0,(int)($_GET['offset']??0));$limit=min(500,max(1,(int)($_GET['limit']??10)));
 $out=transact(fn($db)=>['items'=>array_slice($db['activity'],$offset,$limit),'total'=>count($db['activity'])]);
 reply(200,['ok'=>true,'offset'=>$offset,'limit'=>$limit]+$out);
}
/* v0.0.17: server drafts (editable editor projects) and the shared photo library, per store. */
if($action==='draft_list'&&$method==='GET'){
 $store=(string)($_GET['store']??'');$items=transact(fn($db)=>array_values(array_filter($db['drafts']??[],fn($d)=>can_store($d['store_id'])&&($store===''||$d['store_id']===$store))));
 usort($items,fn($a,$b)=>strcmp($b['updated_at'],$a['updated_at']));reply(200,['ok'=>true,'items'=>array_map('draft_row',$items)]);
}
if(in_array($action,['draft_get','draft_thumb'],true)&&$method==='GET'){
 $id=(string)($_GET['id']??'');if(!preg_match('/^[a-f0-9]{26}$/D',$id))fail(400,'Invalid draft');$d=transact(function($db)use($id){$i=find_index($db['drafts']??[],$id);return $i<0?null:$db['drafts'][$i];});
 if(!$d)fail(404,'Draft not found');need_store($d['store_id']);
 if($action==='draft_thumb')send_file(priv_dir().'/drafts/'.$id.'.jpg','image/jpeg');send_file(priv_dir().'/drafts/'.$id.'.json','application/json; charset=utf-8');
}
if($action==='media_list'&&$method==='GET'){
 $store=valid_id($_GET['store']??'');need_store($store);
 $items=transact(fn($db)=>array_values(array_filter($db['media']??[],fn($m)=>$m['store_id']===$store)));reply(200,['ok'=>true,'items'=>$items]);
}
/* v0.0.22: uploaded videos, per store. The files sit in tv-content/ under a random name because a TV plays them from there. */
if($action==='video_list'&&$method==='GET'){
 $store=valid_id($_GET['store']??'');need_store($store);
 $items=transact(fn($db)=>array_values(array_filter($db['videos']??[],fn($v)=>$v['store_id']===$store)));
 reply(200,['ok'=>true,'items'=>array_map(fn($v)=>$v+['src'=>content_url($v['file'])],array_reverse($items))]);
}
/* v0.0.22: Settings → Storage (owner only): what ShopSmoke keeps on the server, plus the hosting account's totals from cPanel's
   own uapi command when the host lets PHP run it. No API token: a cPanel token opens the whole hosting account, which is too much
   to keep on the server for a usage bar. Adding up the folders is slow on a big site, so the answer is kept for 10 minutes. */
function folder_bytes(string $dir,?callable $match=null):int{$bytes=0;if(!is_dir($dir))return 0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS)) as $f)if($f->isFile()&&(!$match||$match($f->getFilename())))$bytes+=$f->getSize();return $bytes;}
function cpanel_quota():?array{if(!function_exists('shell_exec'))return null;
 foreach(defined('FT_UAPI_BIN')?[FT_UAPI_BIN]:['/usr/bin/uapi','/usr/local/cpanel/bin/uapi'] as $bin){$raw=@shell_exec(escapeshellarg($bin).' --output=json Quota get_quota_info');$d=json_decode((string)$raw,true)['result']['data']??null;
  if(is_array($d)&&isset($d['megabytes_used']))return ['used_mb'=>(float)$d['megabytes_used'],'limit_mb'=>(float)($d['megabyte_limit']??0),'files_used'=>(int)($d['inodes_used']??0),'files_limit'=>(int)($d['inode_limit']??0)];}
 return null;}
if($action==='storage'&&$method==='GET'){need_role('owner');
 $cache=priv_dir().'/storage-cache.json';$out=empty($_GET['fresh'])&&is_file($cache)&&filemtime($cache)>time()-600?json_decode((string)file_get_contents($cache),true):null;
 if(!is_array($out)){$pub=dirname(__DIR__).'/tv-content';$isVideo=fn($n)=>(bool)preg_match('/\.(mp4|webm)$/',$n);
  $out=['checked_at'=>iso(),'photos'=>folder_bytes(priv_dir().'/media'),'videos'=>folder_bytes($pub,$isVideo),'slides'=>folder_bytes($pub,fn($n)=>!$isVideo($n))+folder_bytes(priv_dir().'/staged'),'drafts'=>folder_bytes(priv_dir().'/drafts'),'cpanel'=>cpanel_quota()];
  @file_put_contents($cache,json_encode($out),LOCK_EX);}
 $out['limit_gb']=transact(fn($db)=>(float)($db['storage_limit_gb']??0));reply(200,['ok'=>true]+$out);
}
/* v0.0.22: which Library photos are on a TV right now (photo id → TV names), for the ribbon on each photo. */
if($action==='media_usage'&&$method==='GET'){
 $used=transact(function($db){$out=[];foreach($db['screens'] as $s){if(empty($s['revision_id'])||!can_store($s['store_id']))continue;$ri=find_index($db['revisions'],$s['revision_id']);if($ri<0)continue;foreach($db['revisions'][$ri]['pages'] as $p)if(!empty($p['asset'])&&!in_array($s['name'],$out[$p['asset']]??[],true))$out[$p['asset']][]=$s['name'];}return $out;});
 reply(200,['ok'=>true,'items'=>(object)$used]);
}
if($action==='media_file'&&$method==='GET'){
 $id=(string)($_GET['id']??'');$m=transact(function($db)use($id){$i=find_index($db['media']??[],$id);return $i<0?null:$db['media'][$i];});if(!$m)fail(404,'Photo not found');need_store($m['store_id']);
 if(!empty($_GET['thumb'])&&!empty($m['has_thumb']))send_file(priv_dir().'/media/'.$m['file'].'.thumb.jpg','image/jpeg');send_file(priv_dir().'/media/'.$m['file'],$m['type']);
}
/* v0.0.21: photos sent for owner approval. Someone with "submit" uploads the photo (upload_page) and sends it with the
   TV and when it should show (approval_submit); someone with "approve" (always the owner) approves it, which publishes
   it now or at the requested time, or declines it (approval_decide). Approvers see every request for their stores;
   everyone else sees only their own. */
function approval_row(array $db,array $a):array{global $ME;$si=find_index($db['screens'],$a['screen_id']);$st=find_index($db['stores'],$a['store_id']);
 return ['id'=>$a['id'],'store_id'=>$a['store_id'],'store'=>$st>=0?$db['stores'][$st]['name']:$a['store_id'],'screen_id'=>$a['screen_id'],'screen'=>$si>=0?$db['screens'][$si]['name']:'(TV removed)','name'=>$a['name'],'mode'=>$a['mode'],'at'=>$a['at']??null,'note'=>$a['note']??'','status'=>$a['status'],'by_name'=>$a['by_name']??'','mine'=>($a['by']??'')===$ME['id'],'created_at'=>$a['created_at'],'decided_at'=>$a['decided_at']??null,'decided_by'=>$a['decided_by']??null,'reason'=>$a['reason']??''];}
function approval_visible(array $a):bool{global $ME;return can_store($a['store_id'])&&(has_perm('approve')||($a['by']??'')===$ME['id']);}
if($action==='approval_list'&&$method==='GET'){if(!has_perm('approve')&&!has_perm('submit'))need_perm('submit');
 $store=(string)($_GET['store']??'');
 $items=transact(function($db)use($store){$rows=[];foreach($db['approvals']??[] as $a)if(approval_visible($a)&&($store===''||$a['store_id']===$store))$rows[]=approval_row($db,$a);return $rows;});
 usort($items,fn($a,$b)=>[$a['status']!=='pending',$b['created_at']]<=>[$b['status']!=='pending',$a['created_at']]);
 reply(200,['ok'=>true,'can_approve'=>has_perm('approve'),'items'=>array_slice($items,0,60)]);
}
if($action==='approval_image'&&$method==='GET'){
 $id=(string)($_GET['id']??'');$a=transact(function($db)use($id){$i=find_index($db['approvals']??[],$id);return $i<0?null:$db['approvals'][$i];});
 if(!$a||!approval_visible($a))fail(404,'Photo not found');send_file(priv_dir().'/staged/'.basename($a['image']),'image/jpeg');
}
if($method!=='POST')fail(405,'POST required');csrf();
/* --- team accounts (owner only) --- */
if($action==='user_save'){
 need_role('owner');$in=post_json();$id=(string)($in['id']??'');$name=text_field($in['name']??'',60);$rawLogin=trim((string)($in['username']??''));$username=norm_login($rawLogin);$phone=preg_match('/^[0-9]{7,15}$/D',$username)?$rawLogin:'';$role=(string)($in['role']??'staff');$perms=is_array($in['perms']??null)?array_values(array_intersect(PERMS,array_map('strval',$in['perms']))):null;$pw=(string)($in['password']??'');$active=!array_key_exists('active',$in)||!empty($in['active']);
 if(strlen($name)<2)fail(400,'Name required');if(!preg_match('/^[a-z0-9][a-z0-9._-]{2,31}$/D',$username)||$username==='owner')fail(400,'Login: a phone number, or 3–32 letters, numbers, dot, dash or underscore (not "owner")');
 if(!in_array($role,['manager','staff','custom'],true))fail(400,'Role must be manager, staff or custom');if($perms===null)$perms=PRESETS[$role]??[];if($pw!==''&&(strlen($pw)<4||strlen($pw)>20))fail(400,'Password must be 4 to 20 characters');
 $stores=array_values(array_unique(array_map('strval',is_array($in['stores']??null)?$in['stores']:[])));
 $out=transact(function(&$db)use($id,$name,$username,$phone,$role,$perms,$pw,$active,$stores){$db['users']=$db['users']??[];$valid=array_column($db['stores'],'id');$stores=array_values(array_intersect($stores,$valid));
  if($username===strtolower((string)($db['owner_email']??'')))return 'taken'; /* v0.0.22: the owner's email is a login too */
  foreach($db['users'] as $u)if($u['username']===$username&&$u['id']!==$id)return 'taken';
  if($id===''){if($pw==='')return 'nopass';$u=['id'=>uid(),'name'=>$name,'username'=>$username,'phone'=>$phone,'role'=>$role,'perms'=>$perms,'perms_v'=>21,'stores'=>$stores,'active'=>$active,'password_hash'=>password_hash($pw,PASSWORD_DEFAULT),'created_at'=>iso(),'updated_at'=>iso()];$db['users'][]=$u;audit($db,'user-created',$username.' ('.$role.')');return public_user($u);}
  $i=find_index($db['users'],$id);if($i<0)return 'missing';$db['users'][$i]=array_merge($db['users'][$i],['name'=>$name,'username'=>$username,'phone'=>$phone,'role'=>$role,'perms'=>$perms,'perms_v'=>21,'stores'=>$stores,'active'=>$active,'updated_at'=>iso()]);if($pw!==''){$db['users'][$i]['password_hash']=password_hash($pw,PASSWORD_DEFAULT);$db['users'][$i]['pv']=(int)($db['users'][$i]['pv']??0)+1;}
  audit($db,'user-updated',$username.($pw!==''?' (password reset)':'').($active?'':' (disabled)'));return public_user($db['users'][$i]);});
 if($out==='taken')fail(409,'That phone number or username is already used');if($out==='nopass')fail(400,'Set a password for the new account');if($out==='missing')fail(404,'Account not found');reply(200,['ok'=>true,'user'=>$out]);
}
if($action==='user_delete'){
 need_role('owner');$id=(string)(post_json()['id']??'');$ok=transact(function(&$db)use($id){$i=find_index($db['users']??[],$id);if($i<0)return false;$n=$db['users'][$i]['username'];array_splice($db['users'],$i,1);audit($db,'user-deleted',$n);return true;});
 if(!$ok)fail(404,'Account not found');reply(200,['ok'=>true]);
}
/* --- chunked upload part --- */
if($action==='upload_chunk'){
 if(empty($_FILES['chunk'])||($_FILES['chunk']['error']??99)!==UPLOAD_ERR_OK)fail(400,'Upload part failed — check the connection and try again');
 $uploadId=(string)($_POST['upload_id']??'');$index=(int)($_POST['index']??-1);if($index<0||$index>399)fail(400,'Invalid upload part');
 if($uploadId===''){if($index!==0)fail(400,'Upload must start with part 1');clean_uploads();$uploadId=uid();$_SESSION['uploads'][$uploadId]=true;}
 elseif(!preg_match('/^[a-f0-9]{26}$/D',$uploadId)||empty($_SESSION['uploads'][$uploadId]))fail(400,'Upload not found — start again');
 $dir=store_dir('uploads').'/'.$uploadId;if(!is_dir($dir))@mkdir($dir,0700,true);if(!move_uploaded_file($_FILES['chunk']['tmp_name'],$dir.'/'.$index.'.part'))fail(500,'Could not store upload part');
 reply(200,['ok'=>true,'upload_id'=>$uploadId]);
}
/* --- drafts --- */
if($action==='draft_commit'){need_perm('drafts');
 $uploadId=(string)($_POST['upload_id']??'');$parts=(int)($_POST['parts']??0);$name=text_field($_POST['name']??'Draft',80);$store=valid_id($_POST['store_id']??'');need_store($store);
 $kind=($_POST['kind']??'draft')==='published'?'published':'draft';$pagesN=max(1,min(1000,(int)($_POST['pages']??1)));$existing=(string)($_POST['draft_id']??'');if($existing!==''&&!preg_match('/^[a-f0-9]{26}$/D',$existing))fail(400,'Invalid draft');
 if($existing!==''){$old=transact(function($db)use($existing){$i=find_index($db['drafts']??[],$existing);return $i<0?null:$db['drafts'][$i];});if(!$old)fail(404,'Draft not found');need_store($old['store_id']);if(!has_perm('delete_any')&&$old['by']!==$ME['id'])fail(403,'You can only update your own drafts');}
 $tmp=assemble_upload($uploadId,$parts,400*1048576); /* v0.0.22: was 80 MB; a timeline of hundreds of photos travels inside the draft */$fh=fopen($tmp,'rb');$head=fread($fh,64);fclose($fh);
 if(!str_starts_with($head,'{"app":"4Tier Marketing Editor"')){@unlink($tmp);fail(400,'That is not a ShopSmoke editor project');}
 $id=$existing!==''?$existing:uid();$dir=store_dir('drafts');if(!@rename($tmp,$dir.'/'.$id.'.json')){@unlink($tmp);fail(500,'Could not save the draft');}@chmod($dir.'/'.$id.'.json',0600);$thumb=save_thumb($dir.'/'.$id.'.jpg');$bytes=filesize($dir.'/'.$id.'.json');
 $row=transact(function(&$db)use($id,$name,$store,$kind,$pagesN,$bytes,$thumb){global $ME;$db['drafts']=$db['drafts']??[];$i=find_index($db['drafts'],$id);
  $d=['id'=>$id,'name'=>$name,'store_id'=>$store,'kind'=>$kind,'pages'=>$pagesN,'bytes'=>$bytes,'has_thumb'=>$thumb||($i>=0&&!empty($db['drafts'][$i]['has_thumb'])),'updated_at'=>iso(),'created_at'=>$i>=0?$db['drafts'][$i]['created_at']:iso(),'by'=>$i>=0?$db['drafts'][$i]['by']:$ME['id'],'by_name'=>$i>=0?($db['drafts'][$i]['by_name']??null):$ME['name']];
  if($i>=0){$d['revision_id']=$db['drafts'][$i]['revision_id']??null;$db['drafts'][$i]=$d;}else $db['drafts'][]=$d;if($kind==='draft')audit($db,$i>=0?'draft-updated':'draft-saved',$name.' ('.$store.')');return draft_row($d);});
 reply(200,['ok'=>true,'draft'=>$row]);
}
/* v0.0.22: the owner changes their email or password from Settings, after giving the current password. */
if($action==='account_update'){need_role('owner');if(login_rate())fail(429,'Too many tries; wait 15 minutes');
 $in=post_json();$email=strtolower(trim((string)($in['email']??'')));$new=(string)($in['new_password']??'');$cur=(string)($in['current_password']??'');
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>120)fail(400,'Enter a valid email address');if($new!==''&&(strlen($new)<4||strlen($new)>20))fail(400,'The new password must be 4 to 20 characters');
 $out=transact(function(&$db)use($email,$new,$cur){if(!password_verify($cur,(string)($db['password_hash']??'')))return 'wrong';foreach($db['users']??[] as $u)if($u['username']===$email)return 'taken';$changed=[];
  if($email!==strtolower((string)($db['owner_email']??''))){$db['owner_email']=$email;unset($db['password_reset']);$changed[]='email';}
  if($new!==''){$db['password_hash']=password_hash($new,PASSWORD_DEFAULT);$db['owner_version']=(int)($db['owner_version']??0)+1;$_SESSION['ov']=$db['owner_version'];unset($db['password_reset']);$changed[]='password';}
  if($changed)audit($db,'owner-updated','Owner changed '.implode(' and ',$changed));return $changed;});
 if($out==='wrong'){login_rate(false,true);fail(401,'Your current password is not correct');}if($out==='taken')fail(409,'A team login already uses that email');login_rate(true,false);reply(200,['ok'=>true,'changed'=>$out]);
}
if($action==='storage_limit'){need_role('owner');$gb=max(0,min(100000,(float)(post_json()['gb']??0)));transact(function(&$db)use($gb){$db['storage_limit_gb']=$gb;});reply(200,['ok'=>true,'limit_gb'=>$gb]);}
if($action==='video_commit'){need_perm('photos');
 $uploadId=(string)($_POST['upload_id']??'');$parts=(int)($_POST['parts']??0);$store=valid_id($_POST['store_id']??'');need_store($store);$name=text_field($_POST['name']??'Video',80);
 $tmp=assemble_upload($uploadId,$parts,300*1048576);$fh=fopen($tmp,'rb');$head=(string)fread($fh,16);fclose($fh);
 $ext=substr($head,4,4)==='ftyp'?'mp4':(str_starts_with($head,"\x1A\x45\xDF\xA3")?'webm':'');if($ext===''){@unlink($tmp);fail(400,'Use an MP4 or WebM video');}
 $pub=dirname(__DIR__).'/tv-content';if(!is_dir($pub)&&!@mkdir($pub,0755,true)){@unlink($tmp);fail(500,'Cannot create the TV content folder');}
 $id=uid();$file=$id.'.'.$ext;$dest=$pub.'/'.$file;if(!@rename($tmp,$dest)&&!(@copy($tmp,$dest)&&@unlink($tmp))){@unlink($tmp);fail(500,'Could not save the video');}@chmod($dest,0644);$bytes=filesize($dest);
 $row=transact(function(&$db)use($id,$file,$name,$store,$bytes){global $ME;$db['videos']=$db['videos']??[];$v=['id'=>$id,'file'=>$file,'name'=>$name,'store_id'=>$store,'bytes'=>$bytes,'created_at'=>iso(),'by'=>$ME['id'],'by_name'=>$ME['name']];$db['videos'][]=$v;audit($db,'video-uploaded',$name.' ('.$store.')');return $v;});
 reply(200,['ok'=>true,'video'=>$row+['src'=>content_url($file)]]);
}
if($action==='video_delete'){
 $in=post_json();$id=(string)($in['id']??'');if(!preg_match('/^[a-f0-9]{26}$/D',$id))fail(400,'Invalid video');
 $v=transact(function(&$db)use($id){global $ME;$i=find_index($db['videos']??[],$id);if($i<0)return 'missing';$v=$db['videos'][$i];if(!can_store($v['store_id']))return 'store';if(!has_perm('delete_any')&&$v['by']!==$ME['id'])return 'perm';
  foreach($db['screens'] as $s){$ri=$s['revision_id']?find_index($db['revisions'],$s['revision_id']):-1;if($ri>=0)foreach($db['revisions'][$ri]['pages'] as $p)if(($p['video']['file']??'')===$v['file'])return 'live';}
  array_splice($db['videos'],$i,1);audit($db,'video-deleted',$v['name']);return $v;});
 if($v==='missing')fail(404,'Video not found');if($v==='store')fail(403,'Your account is not assigned to this store');if($v==='perm')fail(403,'You can only delete your own videos');if($v==='live')fail(409,'A TV is showing this video. Publish something else to that TV first');
 @unlink(dirname(__DIR__).'/tv-content/'.basename($v['file']));reply(200,['ok'=>true]);
}
if($action==='draft_delete'){
 $id=(string)(post_json()['id']??'');$d=transact(function($db)use($id){$i=find_index($db['drafts']??[],$id);return $i<0?null:$db['drafts'][$i];});if(!$d)fail(404,'Draft not found');need_store($d['store_id']);
 if(!has_perm('delete_any')&&$d['by']!==$ME['id'])fail(403,'You can only delete your own drafts');
 transact(function(&$db)use($id){$i=find_index($db['drafts'],$id);if($i>=0){audit($db,'draft-deleted',$db['drafts'][$i]['name']);array_splice($db['drafts'],$i,1);}foreach($db['revisions'] as &$r)if(($r['draft_id']??'')===$id)unset($r['draft_id']);unset($r);});
 @unlink(priv_dir().'/drafts/'.$id.'.json');@unlink(priv_dir().'/drafts/'.$id.'.jpg');reply(200,['ok'=>true]);
}
/* --- shared photo library --- */
if($action==='media_commit'){need_perm('photos');
 $uploadId=(string)($_POST['upload_id']??'');$parts=(int)($_POST['parts']??0);$store=valid_id($_POST['store_id']??'');need_store($store);
 $id=(string)($_POST['id']??'');if(!preg_match('/^[A-Za-z0-9-]{4,100}$/D',$id))$id='m-'.uid();$name=text_field($_POST['name']??'Photo',100);$folder=text_field($_POST['folder']??'',60);
 $dup=transact(function($db)use($id){$i=find_index($db['media']??[],$id);return $i<0?null:$db['media'][$i];});if($dup){if(!empty($_SESSION['uploads'][$uploadId])){foreach(glob(priv_dir().'/uploads/'.$uploadId.'/*')?:[] as $p)@unlink($p);@rmdir(priv_dir().'/uploads/'.$uploadId);unset($_SESSION['uploads'][$uploadId]);}reply(200,['ok'=>true,'media'=>$dup,'duplicate'=>true]);}
 $tmp=assemble_upload($uploadId,$parts,20*1048576);$info=@getimagesize($tmp);$types=[IMAGETYPE_JPEG=>['image/jpeg','jpg'],IMAGETYPE_PNG=>['image/png','png'],IMAGETYPE_WEBP=>['image/webp','webp'],IMAGETYPE_GIF=>['image/gif','gif']];
 if(!$info||!isset($types[$info[2]])){@unlink($tmp);fail(400,'Only JPEG, PNG, WebP or GIF photos can be stored');}
 [$type,$ext]=$types[$info[2]];$fileName=hash('sha256',$id).'.'.$ext;$dir=store_dir('media');if(!@rename($tmp,$dir.'/'.$fileName)){@unlink($tmp);fail(500,'Could not save the photo');}@chmod($dir.'/'.$fileName,0600);$thumb=save_thumb($dir.'/'.$fileName.'.thumb.jpg');
 $row=transact(function(&$db)use($id,$store,$name,$folder,$type,$fileName,$info,$thumb,$dir){global $ME;$db['media']=$db['media']??[];$m=['id'=>$id,'store_id'=>$store,'name'=>$name,'folder'=>$folder,'type'=>$type,'file'=>$fileName,'size'=>filesize($dir.'/'.$fileName),'w'=>$info[0],'h'=>$info[1],'has_thumb'=>$thumb,'created_at'=>iso(),'by'=>$ME['id'],'by_name'=>$ME['name']];$db['media'][]=$m;return $m;});
 reply(200,['ok'=>true,'media'=>$row]);
}
if($action==='media_delete'){
 $id=(string)(post_json()['id']??'');$m=transact(function($db)use($id){$i=find_index($db['media']??[],$id);return $i<0?null:$db['media'][$i];});if(!$m)fail(404,'Photo not found');need_store($m['store_id']);
 if(!has_perm('delete_any')&&$m['by']!==$ME['id'])fail(403,'You can only delete your own photos');
 transact(function(&$db)use($id){$i=find_index($db['media'],$id);if($i>=0)array_splice($db['media'],$i,1);});@unlink(priv_dir().'/media/'.$m['file']);@unlink(priv_dir().'/media/'.$m['file'].'.thumb.jpg');reply(200,['ok'=>true]);
}
if($action==='activity_clear'){need_role('owner');
 $n=transact(function(&$db){$n=count($db['activity']);$db['activity']=[];audit($db,'activity-cleared',$n.' entries removed');return $n;});
 reply(200,['ok'=>true,'removed'=>$n]);
}
if($action==='store_add'){need_role('owner');
 $in=post_json();$name=text_field($in['name']??'',80);$slug=valid_id($in['slug']??'');$website='/'.$slug.'/'; /* v0.0.24: always made from the slug */
 if(strlen($name)<2)fail(400,'Store name required');
 $row=transact(function(&$db)use($name,$slug,$website){if(find_index($db['stores'],$slug)>=0)return null;$v=['id'=>$slug,'name'=>$name,'website'=>$website,'next_tv_number'=>1];$db['stores'][]=$v;audit($db,'store-created',$name);return $v;});
 if(!$row)fail(409,'That store URL slug is already used');reply(200,['ok'=>true,'store'=>$row]);
}
if($action==='store_update'){need_role('owner');
 $in=post_json();$id=valid_id($in['id']??'');$name=text_field($in['name']??'',80);
 if(strlen($name)<2)fail(400,'Store name required');
 $row=transact(function(&$db)use($id,$name){$i=find_index($db['stores'],$id);if($i<0)return null;$db['stores'][$i]['name']=$name;audit($db,'store-updated',$id);return $db['stores'][$i];});
 if(!$row)fail(404,'Store not found');reply(200,['ok'=>true,'store'=>$row]);
}
if($action==='screen_update'){need_perm('tvs');
 $in=post_json();$id=(string)($in['id']??'');need_screen($id);$name=text_field($in['name']??'',70);
 if(strlen($name)<2)fail(400,'TV name must have at least two characters');
 $row=transact(function(&$db)use($id,$name){$i=find_index($db['screens'],$id);if($i<0)return null;$db['screens'][$i]['name']=$name;audit($db,'screen-renamed',$db['screens'][$i]['slug']);return $db['screens'][$i];});
 if(!$row)fail(404,'TV not found');reply(200,['ok'=>true,'screen'=>$row]);
}
if($action==='store_delete'){need_role('owner');
 $id=valid_id(post_json()['id']??'');
 $done=transact(function(&$db)use($id){$si=find_index($db['stores'],$id);if($si<0)return 'missing';foreach($db['screens'] as $screen)if($screen['store_id']===$id)return 'screens';$name=$db['stores'][$si]['name'];array_splice($db['stores'],$si,1);audit($db,'store-deleted',$name);return 'ok';});
 if($done!=='ok')fail($done==='screens'?409:404,$done==='screens'?'Delete this store\'s TVs before deleting the store':'Store not found');reply(200,['ok'=>true]);
}
if($action==='screen_add'){need_perm('tvs');
 $in=post_json();$store=valid_id($in['store_id']??'');need_store($store);$name=text_field($in['name']??'',70);if(strlen($name)<2)fail(400,'TV name must have at least two characters');
 $row=transact(function(&$db)use($store,$name){$storeIndex=find_index($db['stores'],$store);if($storeIndex<0)return null;$n=max(1,(int)($db['stores'][$storeIndex]['next_tv_number']??1));foreach($db['screens'] as $sc)if(preg_match('/^'.preg_quote($store,'/').'-tv-(\d+)$/',$sc['slug'],$m))$n=max($n,(int)$m[1]+1);$db['stores'][$storeIndex]['next_tv_number']=$n+1;$r=['id'=>uid(),'store_id'=>$store,'name'=>$name,'slug'=>$store.'-tv-'.$n,'revision_id'=>null,'updated_at'=>null,'created_at'=>iso()];$db['screens'][]=$r;audit($db,'screen-created',$r['slug']);return $r;});
 if(!$row)fail(404,'Store does not exist');@unlink(heartbeat_file($row['slug'])); /* v0.0.24: a reused TV number mustn't inherit an old check-in */$row['url']=screen_url($row['slug']);$row['fallback_url']=fallback_url($row['slug']);reply(200,['ok'=>true,'screen'=>$row]);
}
if($action==='screen_delete'){need_role('owner');
 $id=(string)(post_json()['id']??'');
 $done=transact(function(&$db)use($id){$i=find_index($db['screens'],$id);if($i<0)return false;$slug=$db['screens'][$i]['slug'];array_splice($db['screens'],$i,1);foreach($db['schedules'] as &$s)if($s['screen_id']===$id&&$s['status']==='pending')$s['status']='cancelled';unset($s);audit($db,'screen-deleted',$slug);return $slug;});
 if(!$done)fail(404,'TV does not exist');@unlink(heartbeat_file($done));reply(200,['ok'=>true]);
}
if($action==='schedule_cancel'){need_perm('history');
 $id=(string)(post_json()['id']??'');$ss=transact(function($db)use($id){$i=find_index($db['schedules'],$id);return $i<0?null:$db['schedules'][$i]['screen_id'];});if($ss)need_screen($ss);
 $done=transact(function(&$db)use($id){$i=find_index($db['schedules'],$id);if($i<0||$db['schedules'][$i]['status']!=='pending')return false;$db['schedules'][$i]['status']='cancelled';audit($db,'schedule-cancelled',$id);return true;});
 if(!$done)fail(404,'Pending schedule not found');reply(200,['ok'=>true]);
}
/* v0.0.12: remove one old revision of THIS screen from history (never the one on air or a pending schedule).
   Its images are deleted from tv-content/ and private staging unless another revision still uses them. */
if($action==='revision_delete'){need_perm('history');
 $in=post_json();$screenId=(string)($in['screen_id']??'');$revisionId=(string)($in['revision_id']??'');need_screen($screenId);
 $out=transact(function(&$db)use($screenId,$revisionId){$si=find_index($db['screens'],$screenId);$ri=find_index($db['revisions'],$revisionId);
 if($si<0||$ri<0||($db['revisions'][$ri]['screen_id']??'')!==$screenId)return 'missing';
 if($db['screens'][$si]['revision_id']===$revisionId)return 'live';
 foreach($db['schedules'] as $s)if($s['revision_id']===$revisionId&&$s['status']==='pending')return 'scheduled';
 $files=array_map(fn($p)=>basename((string)$p['image']),$db['revisions'][$ri]['pages']);$linked=$db['revisions'][$ri]['draft_id']??null;array_splice($db['revisions'],$ri,1);
 if($linked){$di=find_index($db['drafts']??[],$linked);if($di>=0&&($db['drafts'][$di]['kind']??'')==='published'){array_splice($db['drafts'],$di,1);@unlink(priv_dir().'/drafts/'.$linked.'.json');@unlink(priv_dir().'/drafts/'.$linked.'.jpg');}}
 $db['schedules']=array_values(array_filter($db['schedules'],fn($s)=>$s['revision_id']!==$revisionId));
 $still=[];foreach($db['revisions'] as $r)foreach($r['pages'] as $p)$still[basename((string)$p['image'])]=true;
 audit($db,'revision-deleted',$db['screens'][$si]['slug'].' revision '.$revisionId);return array_values(array_filter($files,fn($f)=>!isset($still[$f])));});
 if($out==='missing')fail(404,'That revision does not belong to this TV');
 if($out==='live')fail(409,'This menu is on air now. Put another menu on the TV first, then delete this one.');
 if($out==='scheduled')fail(409,'This menu is scheduled. Cancel its schedule first, then delete it.');
 foreach($out as $f)if(preg_match('/^[a-f0-9]{26}\.jpg$/D',$f)){@unlink(dirname(__DIR__).'/tv-content/'.$f);@unlink(priv_dir().'/staged/'.$f);}
 reply(200,['ok'=>true,'deleted_images'=>count($out)]);
}
/* v0.0.10: put an earlier revision of THIS screen back on the air (rollback / re-run). */
if($action==='revision_activate'){need_perm('publish');
 $in=post_json();$screenId=(string)($in['screen_id']??'');$revisionId=(string)($in['revision_id']??'');need_screen($screenId);
 $out=transact(function(&$db)use($screenId,$revisionId){$si=find_index($db['screens'],$screenId);$ri=find_index($db['revisions'],$revisionId);if($si<0||$ri<0||($db['revisions'][$ri]['screen_id']??'')!==$screenId)return null;
 activate_revision($db,$screenId,$revisionId,'rolled-back');return $db['screens'][$si]['slug'];});
 if(!$out)fail(404,'That revision does not belong to this TV');reply(200,['ok'=>true,'slug'=>$out,'url'=>screen_url($out)]);
}
if($action==='upload_page'){if(!has_perm('publish')&&!has_perm('schedule')&&!has_perm('submit'))need_perm('publish');
 if(empty($_FILES['image'])||($_FILES['image']['error']??99)!==UPLOAD_ERR_OK)fail(400,'Image upload failed; verify PHP post_max_size/upload_max_filesize');
 $f=$_FILES['image'];if($f['size']>5*1024*1024||$f['size']<100)fail(413,'JPEG page must be between 100 bytes and 5 MB');
 $info=@getimagesize($f['tmp_name']);if(!$info||$info[2]!==IMAGETYPE_JPEG||$info[0]>4096||$info[1]>4096)fail(400,'Only valid JPEG pages up to 4096 pixels are supported');
 $dir=priv_dir().'/staged';if(!is_dir($dir)&&!mkdir($dir,0700,true))fail(500,'Staging directory not writable');
 $filename=uid().'.jpg';if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$filename))fail(500,'Could not save staged page');@chmod($dir.'/'.$filename,0600);
 reply(200,['ok'=>true,'image'=>$filename]);
}
/** Adds a revision for one TV and shows it now, or schedules it. Used by release and by approving a request. */
function make_release(array &$db,string $screenId,string $mode,string $at,array $pages,string $draftId=''):?array{$si=find_index($db['screens'],$screenId);if($si<0)return null;$id=uid();$r=['id'=>$id,'screen_id'=>$screenId,'pages'=>$pages,'created_at'=>iso(),'mode'=>$mode];if($draftId!==''){$di=find_index($db['drafts']??[],$draftId);if($di>=0){$r['draft_id']=$draftId;$db['drafts'][$di]['revision_id']=$id;}}$db['revisions'][]=$r;
 if($mode==='now'){activate_revision($db,$screenId,$id);return ['id'=>$id,'mode'=>'now','slug'=>$db['screens'][$si]['slug']];}
 $sched=['id'=>uid(),'screen_id'=>$screenId,'revision_id'=>$id,'at'=>$at,'status'=>'pending','created_at'=>iso()];$db['schedules'][]=$sched;audit($db,'scheduled',$db['screens'][$si]['slug'].' at '.$at);return ['id'=>$id,'mode'=>'schedule','slug'=>$db['screens'][$si]['slug'],'schedule'=>$sched];}
if($action==='release'){
 $in=post_json();$screenId=(string)($in['screen_id']??'');
 $draftId=(string)($in['draft_id']??'');if($draftId!==''&&!preg_match('/^[a-f0-9]{26}$/D',$draftId))$draftId='';$mode=$in['mode']??'now';if(!in_array($mode,['now','schedule'],true))fail(400,'Unknown publish mode');
 need_perm($mode==='schedule'?'schedule':'publish');need_screen($screenId);
 $inputPages=$in['pages']??[];if(!is_array($inputPages)||count($inputPages)<1||count($inputPages)>250)fail(400,'Slideshow requires 1–250 pages');
 $pages=[];foreach($inputPages as $page){if(!is_array($page)||!preg_match('/^[a-f0-9]{26}\.jpg$/',(string)($page['image']??'')))fail(400,'Invalid uploaded page');
 if(!is_file(priv_dir().'/staged/'.$page['image']))fail(400,'Page image was not uploaded to this server');
 $pages[]=['name'=>text_field($page['name']??'Page',80),'image'=>$page['image'],'duration'=>min(14400,max(1,(float)($page['duration']??10))),'transition'=>in_array($page['transition']??null,['fade','cut','none','slide-left','slide-up','zoom'],true)?$page['transition']:'fade','transitionDuration'=>min(10,max(0,(float)($page['transitionDuration']??.8))),'video'=>clean_video($page['video']??null),'asset'=>clean_asset($page['asset']??null)];}
 $at=(string)($in['at']??'');if($mode==='schedule'){
 if(!preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.\d+)?Z$/',$at))fail(400,'Scheduled time must be a UTC ISO timestamp');
 $when=strtotime($at);if(!$when||$when<=time()+20||$when>time()+366*86400)fail(400,'Pick a future time within 12 months');
 }
 $out=transact(fn(&$db)=>make_release($db,$screenId,$mode,$at,$pages,$draftId));
 if(!$out)fail(404,'TV no longer exists');$out['url']=screen_url($out['slug']);reply(200,['ok'=>true,'result'=>$out]);
}
if($action==='approval_submit'){need_perm('submit');
 $in=post_json();$screenId=(string)($in['screen_id']??'');need_screen($screenId);$image=(string)($in['image']??'');
 if(!preg_match('/^[a-f0-9]{26}\.jpg$/D',$image)||!is_file(priv_dir().'/staged/'.$image))fail(400,'Upload the photo first');
 $mode=($in['mode']??'now')==='schedule'?'schedule':'now';$at=null;
 if($mode==='schedule'){$at=(string)($in['at']??'');$when=strtotime($at);if(!preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.\d+)?Z$/',$at)||!$when||$when<=time()+20||$when>time()+366*86400)fail(400,'Pick a future time within 12 months');}
 $out=transact(function(&$db)use($screenId,$image,$mode,$at,$in){global $ME;$si=find_index($db['screens'],$screenId);if($si<0)return null;$db['approvals']=$db['approvals']??[];
  $a=['id'=>uid(),'screen_id'=>$screenId,'store_id'=>$db['screens'][$si]['store_id'],'image'=>$image,'name'=>text_field($in['name']??'Photo',70)?:'Photo','asset'=>clean_asset($in['asset']??null),'mode'=>$mode,'at'=>$at,'note'=>text_field($in['note']??'',140),'status'=>'pending','by'=>$ME['id'],'by_name'=>$ME['name'],'created_at'=>iso()];
  array_unshift($db['approvals'],$a);
  /* keep every pending request and the 200 most recent decided ones */
  $kept=0;$db['approvals']=array_values(array_filter($db['approvals'],function($x)use(&$kept){return $x['status']==='pending'||$kept++<200;}));
  audit($db,'approval-requested',$db['screens'][$si]['slug'].' · '.$a['name']);return approval_row($db,$a);});
 if(!$out)fail(404,'TV no longer exists');reply(200,['ok'=>true,'approval'=>$out]);
}
if($action==='approval_decide'){need_perm('approve');
 $in=post_json();$id=(string)($in['id']??'');$approve=($in['decision']??'')==='approve';$reason=text_field($in['reason']??'',140);
 $out=transact(function(&$db)use($id,$approve,$reason){global $ME;$i=find_index($db['approvals']??[],$id);if($i<0)return 'missing';$a=$db['approvals'][$i];
  if(!can_store($a['store_id']))return 'missing';if($a['status']!=='pending')return 'decided';
  $base=['decided_at'=>iso(),'decided_by'=>$ME['name'],'reason'=>$reason];$si=find_index($db['screens'],$a['screen_id']);
  if(!$approve||$si<0){$db['approvals'][$i]=array_merge($a,$base,['status'=>$si<0&&$approve?'expired':'declined']);audit($db,'approval-declined',$a['name'].($si<0?' (TV removed)':''));return ['status'=>$db['approvals'][$i]['status'],'image'=>$a['image']];}
  /* a requested time that has already passed publishes right away */
  $mode=$a['mode']==='schedule'&&strtotime((string)$a['at'])>time()+20?'schedule':'now';
  $r=make_release($db,$a['screen_id'],$mode,(string)$a['at'],[['name'=>$a['name'],'image'=>$a['image'],'asset'=>$a['asset']??null,'duration'=>10,'transition'=>'fade','transitionDuration'=>.7]]);
  $db['approvals'][$i]=array_merge($a,$base,['status'=>'approved','revision_id'=>$r['id'],'published_mode'=>$mode]);audit($db,'approval-approved',$a['name'].' · by '.($a['by_name']??''));
  return ['status'=>'approved','result'=>$r+['url'=>screen_url($r['slug'])]];});
 if($out==='missing')fail(404,'Request not found');if($out==='decided')fail(409,'Someone already decided this request');
 if(($out['status']??'')!=='approved'&&!empty($out['image'])){$used=transact(function($db)use($out){foreach($db['revisions'] as $r)foreach($r['pages'] as $p)if(($p['image']??'')===$out['image'])return true;return false;});if(!$used)@unlink(priv_dir().'/staged/'.basename($out['image']));unset($out['image']);}
 reply(200,['ok'=>true]+$out);
}
fail(404,'Unknown action');

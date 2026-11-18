<?php
/**
 * ShopSmoke Marketing Studio v0.0.40 — first-time setup (https://your-domain/setup/install.php).
 * The new owner types an email and a password and confirms in a popup. That creates the owner
 * account, writes api/config.php, signs the owner in, and this page deletes itself.
 * Stores, TVs and accounts are kept in the private folder outside public_html, so no database is
 * needed. A developer can still choose MySQL under "Developer: use a MySQL database".
 * Until it has run, whoever opens this page first becomes the owner: run it right after uploading.
 * Forgot the owner password? Use "Forgot password?" on the login box. If that email can't be reached: delete api/config.php,
 * upload setup/install.php from the ZIP again and open it (a MySQL site enters the same database details): stores, TVs and menus are kept.
 * Written in PHP 7-compatible syntax on purpose, so it can explain an old PHP version
 * instead of failing with a blank 500 page.
 */
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$appDir = dirname(__DIR__);
$apiDir = $appDir . '/api';
$configFile = $apiDir . '/config.php';
$scriptDir = str_replace('\\', '/', dirname(str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/setup/install.php'))));
$basePath = rtrim(preg_replace('#[^/a-zA-Z0-9_-]#', '', $scriptDir), '/');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('#[^a-zA-Z0-9.:-]#', '', $_SERVER['HTTP_HOST']) : 'localhost';
$local = in_array($host, array('localhost', '127.0.0.1', 'localhost:8765', '127.0.0.1:8765'), true);

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function page($title, $body) {
	echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . h($title) . ' · ShopSmoke Setup</title><style>'
	. ':root{font-family:system-ui,-apple-system,Segoe UI,Arial,sans-serif;color:#14223c;background:#eef2f9}body{margin:0}main{max-width:520px;margin:0 auto;padding:22px 16px 60px}'
	. 'header{background:#000;color:#fff;border-radius:16px;padding:20px 22px;margin-bottom:16px}header small{letter-spacing:1.6px;font-weight:800;color:#cfcfcf;font-size:11px}header h1{margin:6px 0 0;font-size:26px}'
	. 'section{background:#fff;border:1px solid #dce4ee;border-radius:14px;padding:18px 18px;margin:14px 0}h2{font-size:18px;margin:0 0 10px}p{line-height:1.5;color:#4a5b74}'
	. 'label{display:block;font-size:13px;font-weight:700;color:#34445e;margin:12px 0 4px}input{display:block;width:100%;box-sizing:border-box;font:16px system-ui;border:1px solid #c4d1e0;border-radius:9px;padding:11px}'
	. 'button,.btn{display:inline-block;background:#e02b47;color:#fff;border:0;border-radius:10px;padding:13px 18px;font-weight:800;font-size:15px;text-decoration:none;margin:6px 6px 0 0;cursor:pointer}.btn.alt{background:#213a5b}button:disabled{opacity:.6;cursor:default}form button[type=submit]{width:100%;margin:16px 0 0}'
	. '.check{display:flex;gap:10px;align-items:flex-start;border-bottom:1px solid #edf1f6;padding:7px 0;font-size:14px}.check b{min-width:20px;color:#b3142c}.check span{color:#55677f}'
	. '.err{background:#fff1f2;border:1px solid #f3b4bd;color:#9d1730;border-radius:10px;padding:11px 13px;font-weight:600}.good{background:#e8f8f1;border:1px solid #b7e4d0;color:#146546;border-radius:10px;padding:11px 13px;font-weight:600}'
	. 'code{background:#eef3ff;color:#23439a;padding:2px 6px;border-radius:5px;word-break:break-all}.fine{font-size:12px;color:#6b7c93}details{margin-top:14px;font-size:13px}summary{cursor:pointer;color:#6b7c93}'
	. '.shade{position:fixed;inset:0;background:#0c1228cc;display:flex;align-items:center;justify-content:center;padding:16px}.shade[hidden]{display:none}.box{background:#fff;border-radius:14px;padding:22px;width:min(420px,100%);box-shadow:0 16px 60px #0006}.box h2{margin:0 0 8px;font-size:20px}.box .who{font-weight:800;color:#14223c;word-break:break-all}.actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}.actions button{margin:0}'
	. '</style></head><body><main><header><small>SHOPSMOKE MARKETING STUDIO · v0.0.40</small><h1>' . h($title) . '</h1></header>' . $body . '</main></body></html>';
	exit;
}
/** Same rule as api/index.php: the private folder sits next to public_html, never inside it. */
function detect_priv_dir($appDir) {
	for ($d = $appDir; dirname($d) !== $d; $d = dirname($d)) { if (basename($d) === 'public_html') return dirname($d) . '/.4tier-marketing-private'; }
	$doc = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
	return ($doc ? dirname($doc) : dirname(dirname($appDir))) . '/.4tier-marketing-private';
}
function seed_registry() {
	$stores = array();
	foreach (array(array('galaxy-goulds', 'Galaxy Goulds'), array('galaxy-wynwood', 'Galaxy Wynwood'), array('the-farmacy', 'The Farmacy'), array('57th-ave', '57th Ave'), array('the-square', 'The Square Smoke Shop')) as $s)
		$stores[] = array('id' => $s[0], 'name' => $s[1], 'website' => '/' . $s[0] . '/', 'next_tv_number' => 1);
	return array('version' => 1, 'stores' => $stores, 'screens' => array(), 'revisions' => array(), 'schedules' => array(), 'activity' => array(), 'password_hash' => null);
}
/** Writes the owner into a registry that may already hold stores and TVs (password reset, or an older install). */
function apply_owner($data, $email, $password, $imported) {
	$note = $imported ? 'Imported the older registry; owner set by setup/install.php' : 'Owner account created by setup/install.php';
	if (!is_array($data)) $data = seed_registry();
	elseif (!empty($data['password_hash'])) $note = 'Owner login reset by setup/install.php (existing stores, TVs and menus kept)';
	$data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
	$data['owner_email'] = $email;
	$data['owner_version'] = (isset($data['owner_version']) ? (int)$data['owner_version'] : 0) + 1;
	unset($data['password_reset']);
	if (!isset($data['activity']) || !is_array($data['activity'])) $data['activity'] = array();
	array_unshift($data['activity'], array('at' => gmdate('Y-m-d\TH:i:s\Z'), 'action' => 'setup', 'info' => $note));
	return $data;
}
function registry_json($data) { return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE); }
/** The page deletes itself, then its folder if nothing else is in it. */
function remove_setup() {
	if (function_exists('opcache_invalidate')) @opcache_invalidate(__FILE__, true);
	if (!@unlink(__FILE__)) return false;
	@rmdir(__DIR__);
	return true;
}

/* 1. PHP version — explain instead of crashing. */
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
	page('PHP 8.1 or newer is required', '<section><p class="err">This hosting account is running PHP ' . h(PHP_VERSION) . '.</p><p>In cPanel open <b>Select PHP Version</b> (or <b>MultiPHP Manager</b>), choose <b>PHP 8.1, 8.2 or 8.3</b> for this domain, then reload this page.</p></section>');
}
/* 2. Already set up (an upgrade ZIP brings this file back): remove it again. */
if (is_file($configFile)) {
	$gone = remove_setup();
	page('Already installed', '<section><p class="good">✓ ShopSmoke Marketing Studio is already set up on this site.</p><p><a class="btn" href="' . h($basePath) . '/">Open ShopSmoke Studio</a></p>'
		. '<p class="fine">' . ($gone ? 'This setup page has been deleted.' : 'Delete the <code>setup/</code> folder in cPanel File Manager.') . ' Forgot the owner password? In cPanel File Manager delete <code>api/config.php</code>, upload <code>setup/install.php</code> from the ZIP again and open it. Stores, TVs and menus are kept.</p></section>');
}
/* 3. HTTPS. */
if (!$https && !$local) {
	page('Secure connection required', '<section><p class="err">Open this page with <b>https://</b>.</p><p><a class="btn" href="https://' . h($host . $basePath) . '/setup/install.php">Go to https://' . h($host . $basePath) . '/setup/install.php</a></p><p class="fine">If the https address shows a certificate warning, run cPanel → <b>SSL/TLS Status</b> → <b>Run AutoSSL</b> first.</p></section>');
}

/* 4. Requirements: only shown when something is wrong. TV Manager → Run deployment check covers the rest. */
$privDir = detect_priv_dir($appDir);
$privOk = is_dir($privDir) ? is_writable($privDir) : @mkdir($privDir, 0700, true);
if ($privOk) @chmod($privDir, 0700);
$tvContent = $appDir . '/tv-content';
$checks = array(
	array('json / session extensions', extension_loaded('json') && extension_loaded('session'), 'Enable them in cPanel → Select PHP Version → Extensions'),
	array('File uploads', (bool)ini_get('file_uploads'), 'Turn on file_uploads in cPanel → Select PHP Version → Options'),
	array('Private folder (outside the website)', $privOk, 'PHP cannot write ' . $privDir),
	array('Can write api/config.php', is_writable($apiDir), 'Check the folder permissions of ' . $apiDir),
	array('Can write tv-content/', is_dir($tvContent) ? is_writable($tvContent) : is_writable($appDir), 'Check the folder permissions of ' . $tvContent),
);
$checkHtml = '';
foreach ($checks as $c) if (!$c[1]) $checkHtml .= '<div class="check"><b>✕</b><div><strong>' . h($c[0]) . '</strong><br><span>' . h($c[2]) . '</span></div></div>';
$blocking = $checkHtml !== '';

/* Form protection: a random token in an HttpOnly cookie must match the hidden field. */
if (empty($_COOKIE['ft_install']) || !preg_match('/^[a-f0-9]{32}$/', $_COOKIE['ft_install'])) {
	$token = bin2hex(random_bytes(16));
	setcookie('ft_install', $token, array('expires' => 0, 'path' => $basePath . '/setup/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict'));
} else $token = $_COOKIE['ft_install'];

$error = '';
$email = '';
$v = array('db_host' => 'localhost', 'db_name' => '', 'db_user' => '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	foreach ($v as $k => $default) $v[$k] = trim((string)(isset($_POST[$k]) ? $_POST[$k] : $default));
	$dbPass = (string)(isset($_POST['db_pass']) ? $_POST['db_pass'] : '');
	$email = strtolower(trim((string)(isset($_POST['owner_email']) ? $_POST['owner_email'] : '')));
	$pw = (string)(isset($_POST['owner_password']) ? $_POST['owner_password'] : '');
	$pw2 = (string)(isset($_POST['owner_password2']) ? $_POST['owner_password2'] : '');
	$useMysql = $v['db_name'] !== '';
	$dbPort = null;
	if ($useMysql && preg_match('/^(.+):(\d{2,5})$/', $v['db_host'], $m)) { $v['db_host'] = $m[1]; $dbPort = (int)$m[2]; }
	if (!hash_equals($token, (string)(isset($_POST['token']) ? $_POST['token'] : ''))) $error = 'This form expired. Reload the page and try again.';
	elseif ($blocking) $error = 'Fix the ✕ items first.';
	elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) $error = 'Enter a valid email address.';
	elseif (strlen($pw) < 4 || strlen($pw) > 20) $error = 'The password must be 4 to 20 characters.';
	elseif ($pw !== $pw2) $error = 'The two passwords do not match.';
	elseif ((isset($_POST['agree']) ? $_POST['agree'] : '') !== '1') $error = 'Confirm your account in the popup to finish.';
	elseif ($useMysql && !extension_loaded('pdo_mysql')) $error = 'MySQL needs the pdo_mysql extension: enable it in cPanel → Select PHP Version → Extensions.';
	elseif ($useMysql && !preg_match('/^[A-Za-z0-9._-]{1,100}$/', $v['db_host'])) $error = 'Database host looks wrong (normally: localhost).';
	elseif ($useMysql && !preg_match('/^[A-Za-z0-9_]{1,64}$/', $v['db_name'])) $error = 'Database name should look like cpaneluser_databasename.';
	elseif ($useMysql && !preg_match('/^[A-Za-z0-9_]{1,80}$/', $v['db_user'])) $error = 'Database user should look like cpaneluser_username.';
	if (!$error && $useMysql) {
		try {
			$pdo = new PDO('mysql:host=' . $v['db_host'] . ($dbPort ? ';port=' . $dbPort : '') . ';dbname=' . $v['db_name'] . ';charset=utf8mb4', $v['db_user'], $dbPass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 5));
		} catch (Exception $e) {
			sleep(2);
			$error = 'MySQL refused the connection (' . h(isset($e->errorInfo[1]) ? 'error ' . $e->errorInfo[1] : get_class($e)) . '). Check the database name, user and password, and that the user is added to the database with ALL PRIVILEGES in cPanel → MySQL Databases.';
		}
	}
	if (!$error) {
		try {
			$registryFile = $privDir . '/v009-data.json';
			if ($useMysql) {
				$pdo->exec('CREATE TABLE IF NOT EXISTS ft_registry (id TINYINT UNSIGNED NOT NULL PRIMARY KEY, data LONGTEXT NOT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
				$pdo->beginTransaction();
				$raw = $pdo->query('SELECT data FROM ft_registry WHERE id=1 FOR UPDATE')->fetchColumn();
				$data = $raw ? json_decode($raw, true) : null;
				$imported = false;
				if (!is_array($data) && is_file($registryFile)) { $data = json_decode((string)file_get_contents($registryFile), true); $imported = is_array($data); }
				$data = apply_owner($data, $email, $pw, $imported);
				$json = registry_json($data);
				$pdo->prepare('INSERT INTO ft_registry (id,data,updated_at) VALUES (1,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE data=VALUES(data),updated_at=VALUES(updated_at)')->execute(array($json));
				$pdo->commit(); /* the registry is saved first; if config.php then fails, running this page again is safe */
			} else {
				$fp = fopen($registryFile, 'c+');
				if (!$fp || !flock($fp, LOCK_EX)) throw new RuntimeException('Could not open the private storage file');
				$raw = stream_get_contents($fp);
				$data = $raw ? json_decode($raw, true) : null;
				$data = apply_owner($data, $email, $pw, false);
				$json = registry_json($data);
				rewind($fp); ftruncate($fp, 0);
				if (fwrite($fp, $json) === false) throw new RuntimeException('Could not save the private storage file');
				fflush($fp); flock($fp, LOCK_UN); fclose($fp);
				@chmod($registryFile, 0600);
			}
			foreach (array('staged', 'heartbeats', 'login-rates') as $sub) if (!is_dir($privDir . '/' . $sub)) @mkdir($privDir . '/' . $sub, 0700, true);
			if (!is_dir($tvContent)) @mkdir($tvContent, 0755, true);
			$config = "<?php\n// Written by setup/install.php on " . gmdate('Y-m-d H:i') . " UTC. KEEP PRIVATE.\n"
				. "// Forgot the owner password? Delete this file, upload setup/install.php from the ZIP again and open it (stores, TVs and menus are kept).\n"
				. "define('FT_SETUP_KEY', " . var_export(bin2hex(random_bytes(32)), true) . ");\n"
				. "define('FT_STORAGE', " . var_export($useMysql ? 'mysql' : 'file', true) . ");\n"
				. ($useMysql ? "define('FT_DB_HOST', " . var_export($v['db_host'], true) . ");\n"
					. ($dbPort ? "define('FT_DB_PORT', " . (int)$dbPort . ");\n" : '')
					. "define('FT_DB_NAME', " . var_export($v['db_name'], true) . ");\n"
					. "define('FT_DB_USER', " . var_export($v['db_user'], true) . ");\n"
					. "define('FT_DB_PASS', " . var_export($dbPass, true) . ");\n" : '')
				. "define('FT_PRIVATE_DIR', " . var_export($privDir, true) . ");\n"
				. "define('FT_BASE_PATH', " . var_export($basePath, true) . ");\n";
			$tmp = $apiDir . '/config.php.' . bin2hex(random_bytes(4)) . '.tmp';
			if (file_put_contents($tmp, $config, LOCK_EX) === false || !@rename($tmp, $configFile)) { @unlink($tmp); throw new RuntimeException('Could not write api/config.php — check folder permissions'); }
			@chmod($configFile, 0600);
		} catch (Exception $e) {
			if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
			error_log('ShopSmoke install: ' . $e->getMessage());
			$error = $e instanceof PDOException ? 'The database user needs CREATE, SELECT, INSERT and UPDATE privileges on this database.' : $e->getMessage();
		}
	}
	if (!$error) {
		/* Sign the owner in with the same session settings as api/index.php. */
		ini_set('session.use_strict_mode', '1'); ini_set('session.use_only_cookies', '1'); ini_set('session.cookie_httponly', '1');
		session_name('FTMARKETING');
		session_set_cookie_params(array('lifetime' => 0, 'path' => $basePath . '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict'));
		@session_start(); session_regenerate_id(true);
		$_SESSION['owner'] = true; $_SESSION['ov'] = (int)$data['owner_version']; $_SESSION['last'] = time(); $_SESSION['csrf'] = bin2hex(random_bytes(16));
		setcookie('ft_install', '', array('expires' => time() - 3600, 'path' => $basePath . '/setup/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict'));
		$gone = remove_setup();
		page('You are all set', '<section><p class="good">✓ Your owner account is ready and you are logged in.</p><p>Log in any time with <b>' . h($email) . '</b> and your password.</p>'
			. '<p><a class="btn" href="' . h($basePath) . '/">Open ShopSmoke Studio</a></p>'
			. ($gone ? '<p class="fine">This setup page has been deleted from the server.</p>' : '<p class="err">Setup could not delete itself. In cPanel File Manager delete the <code>setup/</code> folder now.</p>') . '</section>');
	}
}

page('Create your owner account', ($error ? '<p class="err">' . $error . '</p>' : '')
	. ($blocking ? '<section><h2>Fix this first</h2>' . $checkHtml . '<p class="fine">Then reload this page.</p></section>' : '')
	. '<form id="setupForm" method="post" autocomplete="off"><input type="hidden" name="token" value="' . h($token) . '"><input type="hidden" name="agree" id="agree" value="">'
	. '<section><label for="owner_email">Email</label><input id="owner_email" name="owner_email" type="email" required autocomplete="username" autocapitalize="off" spellcheck="false" placeholder="you@example.com" value="' . h($email) . '">'
	. '<label for="owner_password">Password</label><input id="owner_password" name="owner_password" type="password" minlength="4" maxlength="20" required autocomplete="new-password">'
	. '<label for="owner_password2">Repeat password</label><input id="owner_password2" name="owner_password2" type="password" minlength="4" maxlength="20" required autocomplete="new-password">'
	. '<p class="fine">Use 4 to 20 characters. Numbers are fine. Keep it private.</p><p class="err" id="formError" hidden></p>'
	. '<button type="submit"' . ($blocking ? ' disabled' : '') . '>Continue</button>'
	. '<details' . ($v['db_name'] !== '' ? ' open' : '') . '><summary>Developer: use a MySQL database</summary><p class="fine">Leave this empty to keep everything in the private folder. On a site that already uses MySQL, enter the same details to keep its stores and TVs.</p>'
	. '<label for="db_name">Database name</label><input id="db_name" name="db_name" placeholder="cpaneluser_databasename" value="' . h($v['db_name']) . '">'
	. '<label for="db_user">Database user</label><input id="db_user" name="db_user" placeholder="cpaneluser_username" value="' . h($v['db_user']) . '">'
	. '<label for="db_pass">Database user password</label><input id="db_pass" name="db_pass" type="password" autocomplete="off">'
	. '<label for="db_host">Database host</label><input id="db_host" name="db_host" value="' . h($v['db_host']) . '"></details></section></form>'
	. '<div class="shade" id="confirmBox" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" hidden><div class="box"><h2 id="confirmTitle">Is this correct?</h2><p>Owner email:<br><span class="who" id="confirmEmail"></span></p><p>Once you agree, your account is created and this setup page is deleted from the server.</p><div class="actions"><button type="button" class="btn alt" id="confirmBack">Go back</button><button type="button" id="confirmYes">I agree</button></div></div></div>'
	. '<script>(function(){var f=document.getElementById("setupForm"),box=document.getElementById("confirmBox"),err=document.getElementById("formError"),yes=document.getElementById("confirmYes"),back=document.getElementById("confirmBack");'
	. 'f.addEventListener("submit",function(e){e.preventDefault();if(f.owner_password.value!==f.owner_password2.value){err.textContent="The two passwords do not match.";err.hidden=false;return}err.hidden=true;document.getElementById("confirmEmail").textContent=f.owner_email.value.trim();box.hidden=false;yes.focus()});'
	. 'back.onclick=function(){box.hidden=true};document.addEventListener("keydown",function(e){if(e.key==="Escape")box.hidden=true});'
	. 'yes.onclick=function(){yes.disabled=back.disabled=true;document.getElementById("agree").value="1";f.submit()}})();</script>');

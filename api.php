<?php
$CLASS = 'api'; class api {
	public $iscli = false;
	public function __construct( $iscli = true) { $this->iscli = $iscli; }
	// SECTION:  webkey for secured access
	public function make( $libdir = null, $stuffdir = null, $length = 10) { // { md5: stuffdir, ...} > libdir/webkeys.json
		if ( ! $libdir || ! is_dir( $libdir) || ! $stuffdir || ! is_dir( $stuffdir)) die( " ERROR! make() needs libdir[abspath] stuffdir[abspath] [length=10]\n");
		$h = array(); if ( is_file( "$libdir/webkeys.json")) $h = jsonload( "$libdir/webkeys.json");
		$md5 = md5( "$libdir  $stuffdir " . tsystem()); $md5p = substr( $md5, 0, $length);
		$h[ $md5p] = $stuffdir; jsondump( $h, "$libdir/webkeys.json");
		echo "OK, key/stuffdir map   " . htt( $h) . "   in $libdir/webkeys.json";
	}
	public function tag( $key = null, $tag = null, $cldir = null) { // { tag: md5, ...} > cldir/webtags.json -- cldir=/code/web when using 'phpweb' 
		global $CLDIR; if ( ! $cldir) $cldir = $CLDIR;
		if ( ! $key || ! $tag) die( " ERROR! tag() params:  key[output of makewebkey()]  tag[your string]\n");
		$h = array(); if ( is_file( "$cldir/webtags.json")) $h = jsonload( "$cldir/webtags.json");
		$h[ $tag] = $key; jsondump( $h, "$cldir/webtags.json");
		echo "OK, key/tag map   " . htt( $h) . "   in $cldir/webtags.json\n";
	}
	public function place( $name = null, $iport = null, $tag = null, $cldir = null) { // { ipport: tag, ...} > cldir/webplaces.json   -- cldir=/code/web when using 'phpweb'
		global $CLDIR; if ( ! $cldir) $cldir = $CLDIR;
		if ( ! $name || ! $iport || ! $tag) die( " ERROR! place() params:  place[your string]  iport[ip:port]  tag[from regwebkey()]\n");
		$h = array(); if ( is_file( "$cldir/webplaces.json")) $h = jsonload( "$cldir/webplaces.json");
		$h[ $name] = "$iport | $tag"; jsondump( $h, "$cldir/webplaces.json");
		echo "OK, iport map  " . htt( $h) . "   in $cldir/webplaces.json\n";
	}
	public function show( $cldir = null) { 
		global $CLDIR; if ( ! $cldir) $cldir = $CLDIR; 
		foreach ( ttl( 'keys,tags,places') as $k) { $f = $cldir . '/web' . $k; echo "$k: " . ( is_dir( $cldir) && is_file( $f) ? htt( jsonload( $f)) : '') . "\n"; }
	}
	private function load( $place = null, $tag = null, $cldir = null) { // returns [ iport, tag, webkey]  -- resolves name>iport>tag>key or tag>key
		global $CLDIR; if ( ! $cldir) $cldir = $CLDIR; $iport = null;
		if ( ! $place && ! $tag) die( " ERROR! load() cannot have both iport and tag empty, need one of them to resolve.");
		if ( $place && ! is_file( "$cldir/webplaces.json")) die( " ERROR! load() no file at $cldir/webplaces.json to lookup place[$place]\n");
		if ( $place) { $h = jsonload( "$cldir/webplaces.json"); if ( isset( $h[ $place])) extract( lth( ttl( $h[ $place], ' '), 'iport,tag')); }
		if ( ! is_file( "$cldir/webtags.json")) die( " ERROR! load() no file at $cldir/webtags.json to lookup for tag[$tag]\n");
		$h = jsonload( "$cldir/webtags.json"); $webkey = isset( $h[ $tag]) ? $h[ $tag] : null;
		return array( $iport, $tag, $webkey); 
	}
	// SECTION: primitive actions
	public function ping( $what) { die( jsonsend( jsonmsg( $what))); } // for checking if this machine is one
	public function exec( $where = null, $what = null, $how = null) { // what is base64( file content), how is base64( command)    cli is $how $tempfile
		if ( ! $where || ! $what || ! $how) return jsonsend( jsonerr( 'bad params'));
		$cwd = getcwd(); chdir( $where); 
		$file = ftempname(); $out = fopen( $file, 'w'); fwrite( $out, s642s( $what)); fclose( $out); 
		$H = procpipe( s642s( $how) . " $file"); @system( "rm -Rf $file"); chdir( $cwd); die( jsonsend( $H));
	}
	public function run( $where = null, $what = null) { // what is a base64( command) 
		if ( ! $what || ! $where) die( " ERROR! run() params: keytag where(remote dir) what(base64 of command)\n");
		//jsondump( compact( ttl( 'what,where')), '/startup/run.json');
		$cwd = getcwd(); chdir( $where); $H = procpipe( s642s( $what)); chdir( $cwd); die( jsonsend( $H)); 
	}
	public function get( $where = null, $what = null) { // returns abs dir pointing to what locally 
		if ( ! $what || ! $where) die( " ERROR! get() params: where(remote dir) what(file in remote dir)\n");
		if ( ! is_file( "$where/$what")) die();
		//jsondump( compact( ttl( 'where,what')), '/startup/get.json');
		die( jsonsendfile( "$where/$what"));
	}
	public function put( $what = null, $where = null, $name = null) { // one=base64( what), two=where, there=name   in POST
		//jsondump( compact( ttl( 'what,where,name')), '/startup/put.json');
		$out = fopen( "$where/$name", 'w'); fwrite( $out, s642s( $what)); fclose( $out); die( jsonsend( jsonmsg( 'ok')));
	}
	public function call( $where = null, $classfunction = null, $one = null, $two = null, $three = null, $four = null) { // classfunction: classname.function
		global $CLDIR, $JO; chdir( $where); extract( lth( ttl( $classfunction, '.'), ttl( 'c,f'))); // c, f
		require_once( "$c.php"); $C = new $c( false); // no output
		$p = 0; foreach ( ttl( 'one,two,three,four') as $k) if ( $$k) $p++;
		if ( ! $p) $JO[ 'status'] = $C->$f();
		if ( $p == 0) $JO[ 'status'] = $C->$f();
		if ( $p == 1) $JO[ 'status'] = $C->$f( $one);
		if ( $p == 2) $JO[ 'status'] = $C->$f( $one, $two);
		if ( $p == 3) $JO[ 'status'] = $C->$f( $one, $two, $three);
		if ( $p == 4) $JO[ 'status'] = $C->$f( $one, $two, $three, $four);
		die( jsonsend( jsonmsg( 'OK')));
	}
	public function at( $what, $where = null) { if ( $where) chdir( $where); procat( $what); die( jsonsend( jsonmsg( 'OK'))); }
	// SECTION: high-level interface
	public function server( $iport = '0.0.0.0:8001') { $c = "php -S $iport -t ."; echo "$c\n"; system( $c); } // starts server in current dir 
	public function rrun( $place = null, $remotewhat = null, $remotewhere = null, $cldir = null) { 
		if ( ! $place || ! $remotewhat) die( " ERROR! rrun() params: place remotewhat remotewhere [cldir]\n");
		list( $iport, $keytag, $webkey) = $this->load( $place, null, $cldir); 
		// step 1: remove old version remotely
		if ( $this->iscli) echo "step 1: run($remotewhat) by at  "; $action = 'at'; $one = urlencode( $remotewhat); 
		if ( $remotewhere) { $two = urlencode( $remotewhere); $h = compact( ttl( 'webkey,action,one,two')); if ( $this->iscli) echo jsonraw( $h) . "\n"; }
		else { $h = compact( ttl( 'webkey,action,one')); if ( $this->iscli) echo jsonraw( $h) . "\n"; }
		if ( $this->iscli) echo "step 2: wget..."; list( $s, $h) = procwget( "http://$iport", $h); if ( $this->iscli) echo " OK" . jsonraw( compact( ttl( 's,h'))) . "\n"; // no need to check the status
		if ( ! $s || ! $h) { if ( $this->iscli) die( " ERROR ($s/$h=" . jsonraw( compact( ttl( 's,h'))) . ")\n"); return false; } 
		return array( $s, $h);
	}
	public function rcall( $place = null,  $remotewhere = null, $classfunction = null, $params = array(), $cldir = null) { // params: [ param1, param2, ...]
		if ( ! $place || ! $remotewhere || ! $classfunction) die( " ERROR! rcall() params: place remotewhere classfunction params\n");
		list( $iport, $keytag, $webkey) = $this->load( $place); 
		$pnames = ttl( 'three,four,five,six'); if ( is_string( $params)) $params = ttl( $params);
		while ( count( $pnames) > count( $params)) lpop( $pnames); $params = lth( $params, $pnames);
		// step 1: remove old version remotely
		if ( $this->iscli) echo "step 1: call($classfunction at $remotewhere) by at  "; $action = 'call'; $one = $remotewhere; $two = $classfunction; 
		$h = hm( compact( ttl( 'webkey,action,one,two')), $params); foreach ( $h as $k => $v) $h[ $k] = urlencode( $v); if ( $this->iscli) echo jsonraw( $h) . "\n";
		if ( $this->iscli) echo "step 2: wget..."; list( $s, $h) = procwget( "http://$iport", $h); if ( $this->iscli) echo " OK" . jsonraw( compact( ttl( 's,h'))) . "\n"; // no need to check the status
		if ( ! $s || ! $h) { if ( $this->iscli) die( " ERROR ($s/$h=" . jsonraw( compact( ttl( 's,h'))) . ")\n"); return false; } 
		return array( $s, $h);
	}
	public function tellwhenon( $place, $cldir = null) { 
		if ( ! $place) die( " ERROR! tellwhenon()  $place\n");
		list( $iport, $keytag, $webkey) = $this->load( $place); $action = 'ping'; $one = 'myping'; $h = compact( ttl( 'webkey,action,one'));
 		$b = tsystem(); $e = echoeinit(); 
 		while ( 1) { list( $s, $h) = procwget( "http://$iport", $h); if ( $this->iscli) echoe( $e, tshinterval( tsystem(), $b) . '  ' . jsonraw( compact( ttl( 's,h')))); if ( $s && $h) break; sleep( 10); }
		if ( $this->iscli) echo " OK($iport is on)\n"; return true;
	}
	// SECTION: tasks  -- use it when you need to run a command on a non-reachable machine     poll/notify logic
	public function notify( $source = null, $task = null, $cldir = null) { // { source: [ commands], ...} > /cldirtask is base64() of a commandline  -- run at reachable machine
		global $CLDIR; if ( ! $cldir) $cldir = $CLDIR;
		if ( ! $source || ! $task) die( " ERROR! notify()  source[your string]  task[command > base64]");
		$h = is_file( "$cldir/webtasks.json") ? jsonload( "$cldir/webtasks.json") : array();
		htouch( $h, $source); lpush( $h[ $source], $task); jsondump( $h, "$cldir/webtasks.json");
		echo "notify()  next[" . lfirst( $h[ $source]) . "]   (" . count( $h[ $source]) . ") in stack\n";
	}
	public function shift( $source = null, $cldir = null) { // remote web-call, shifts the last notify-command for this source
		global $CLDIR; if ( ! $cldir) $cldir = $CLDIR;
		if ( ! $source) die( " ERROR! shift()  source[your string]   [cldir]");
		$h = is_file( "$cldir/webtasks.json") ? jsonload( "$cldir/webtasks.json") : array();
		$task = isset( $h[ $source]) && count( $h[ $source]) ? lshift( $h[ $source]) : null; jsondump( $h, "$cldir/webtasks.json");
		jsonsend( $task);
	}
	public function poll( $place = null, $source = null, $remotewhere = null, $cldir = null) { // run at non-reachable machine
		global $CLDIR; if ( ! $cldir) $cldir = $CLDIR;
		if ( ! $place || ! $source) die( " ERROR! poll()   place[name regged locally]  source[source regged and used remotely]   [remotewhere]\n");
		list( $iport, $keytag, $webkey) = $this->load( $place); $sleep = 3; $last = tsystem(); $e = echoeinit();
		while ( 1) { 
			if ( tsystem() - $last > 300) $sleep = 30; sleep( $sleep);
			$action = 'shift'; $one = $source; 
			$h = compact( ttl( 'webkey,action,one')); if ( $remotewhere) $h[ 'two'] = $remotewhere; foreach ( $h as $k => $v) $h[ $k] = urlencode( $v); 
			list( $s, $h) = procwget( "http://$iport", $h); if ( ! $h) { echoe( $e, tsystemstamp() . " (" . tshinterval( $last) . ")"); continue; }
			// there is a command!
			$e = echoeinit(); $b = tsystem(); $sleep = 3; 
			echo "  $h..."; echopipee( $h); echo " OK( " . tshinterval( tsystem(), $b) . ")\n";
		}
		
	}
	
}
// CLI forkjh
if ( isset( $argv) && count( $argv) && strpos( $argv[ 0], "$CLASS.php") !== false) { // direct CLI execution, redirect to one of the functions 
	// this is a standalone script, put the header
	set_time_limit( 0);
	ob_implicit_flush( 1);
	for ( $prefix = is_dir( 'ajaxkit') ? 'ajaxkit/' : ''; ! is_dir( $prefix) && count( explode( '/', $prefix)) < 4; $prefix .= '../'); if ( ! is_file( $prefix . "env.php")) $prefix = '/web/ajaxkit/'; 
	if ( ! is_file( $prefix . "env.php") && ! is_file( 'requireme.php')) die( "\nERROR! Cannot find env.php in [$prefix] or requireme.php in [.], check your environment! (maybe you need to go to ajaxkit first?)\n\n");
	if ( is_file( 'requireme.php')) require_once( 'requireme.php'); else foreach ( explode( ',', ".,$prefix,$BDIR") as $p) foreach ( array( 'functions', 'env') as $k) if ( is_dir( $p) && is_file( "$p/$k.php")) require_once( "$p/$k.php");
	chdir( clgetdir()); clparse(); $JSONENCODER = 'jsonencode'; // jsonraw | jsonencode    -- jump to lib dir
	// help
	clhelp( "FORMAT: php$CLASS WDIR COMMAND param1 param2 param3...     ($CLNAME)");
	foreach ( file( $CLNAME) as $line) if ( ( strpos( trim( $line), '// SECTION:') === 0 || strpos( trim( $line), 'public function') === 0) && strpos( $line, '__construct') === false) clhelp( trim( str_replace( 'public function', '', $line)));
	// parse command line
	lshift( $argv); if ( ! count( $argv)) die( clshowhelp()); 
	//$wdir = lshift( $argv); if ( ! is_dir( $wdir)) { echo "ERROR! wdir#$wdir is not a directory\n\n"; clshowhelp(); die( ''); }
	//echo "wdir#$wdir\n"; if ( ! count( $argv)) { echo "ERROR! no action after wdir!\n\n"; clshowhelp(); die( ''); }
	$f = lshift( $argv); $C = new $CLASS( true); chdir( $CWD); 
	switch ( count( $argv)) { case 0: $C->$f(); break; case 1: $C->$f( $argv[ 0]); break; case 2: $C->$f( $argv[ 0], $argv[ 1]); break; case 3: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2]); break; case 4: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3]); break; case 5: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4]); break; case 6: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4], $argv[ 5]); break; }
 	//switch ( count( $argv)) { case 0: $C->$f( $wdir); break; case 1: $C->$f( $wdir, $argv[ 0]); break; case 2: $C->$f( $wdir, $argv[ 0], $argv[ 1]); break; case 3: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2]); break; case 4: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3]); break; case 5: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4]); break; case 6: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4], $argv[ 5]); break; }
 	die();
}
if ( ! isset( $argv) && ( isset( $_GET) || isset( $_POST)) && ( $_GET || $_POST)) { // web API 
	set_time_limit( 0);
	ob_implicit_flush( 1);
	for ( $prefix = is_dir( 'ajaxkit') ? 'ajaxkit/' : ''; ! is_dir( $prefix) && count( explode( '/', $prefix)) < 4; $prefix .= '../'); if ( ! is_file( $prefix . "env.php")) $prefix = '/web/ajaxkit/'; 
	if ( ! is_file( $prefix . "env.php") && ! is_file( 'requireme.php')) die( "\nERROR! Cannot find env.php in [$prefix] or requireme.php in [.], check your environment! (maybe you need to go to ajaxkit first?)\n\n");
	if ( is_file( 'requireme.php')) require_once( 'requireme.php'); else foreach ( explode( ',', ".,$prefix,$BDIR") as $p) foreach ( array( 'functions', 'env') as $k) if ( is_dir( $p) && is_file( "$p/$k.php")) require_once( "$p/$k.php");
	htg( hm( $_GET, $_POST)); $JSONENCODER = 'jsonencode';
	// check for webkey.json and webkey parameter in request
	//if ( ! is_file( 'webkey.json') || ! isset( $webkey)) die( jsonsend( jsonerr( 'webkey env not set, run [phpwebkey make] first'))); 
	//$h = jsonload( 'webkey.json'); if ( ! isset( $h[ "$webkey"])) die( jsonsend( jsonerr( 'no such webkey in your current environment')));
	//$wdir = $h[ "$webkey"]; if ( ! is_dir( "$wdir")) die( jsonsend( jsonerr( "no dir $wdir in local filesystem, webkey env is wrong")));
	// actions: [wdir] is fixed/predefined  [action] is function name   others are [one,two,three,...]
	$O = new $CLASS( false); $O->iscli = false; // does not pass [types], expects the user to run init() once locally before using it remotely 
	$p = array(); foreach ( ttl( 'one,two,three,four,five') as $k) if ( isset( $$k)) lpush( $p, $$k); $R = array();
	if ( count( $p) == 0) $R = $O->$action();
	if ( count( $p) == 1) $R = $O->$action( $one);
	if ( count( $p) == 2) $R = $O->$action( $one, $two);
	if ( count( $p) == 3) $R = $O->$action( $one, $two, $three);
	if ( count( $p) == 4) $R = $O->$action( $one, $two, $three, $four);
	if ( count( $p) == 5) $R = $O->$action( $one, $two, $three, $four, $five);
	die( jsonsend( $R));
}
if ( isset( $argv) && count( $argv)) { $L = explode( '/', $argv[ 0]); array_pop( $L); if ( count( $L)) chdir( implode( '/', $L)); } // WARNING! Some external callers may not like you jumping to current directory
?>
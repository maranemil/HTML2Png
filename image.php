<?php

	error_reporting(0);
	ini_set("display_error",0);
	ini_set('memory_limit', '256M');
	set_time_limit(45);
	
	
	function compressHTML($input){
		$output = preg_replace(
			array(
				'/ {2,}/',
				'/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s'
			),
			array(
				' ',
				''
			),
			$input
		);
		return $output;
	}
	

	//$localDomain = "http://localhost/HTML2Png/"; // local path
	$localDomain = "http://".$_SERVER["HTTP_HOST"]."".str_replace(basename($_SERVER["SCRIPT_NAME"]), "",$_SERVER["SCRIPT_NAME"]); 

	/*print "<pre>"; print_r($_SERVER); print "</pre>"; die();*/
	
	// default
	$sDomainX = "http://www.wikipedia.de";
	
	// Usage: http://localhost/HTML2Png/image.php?sDomainX=domain_here
	if($_GET["sDomainX"]){
		if(!stristr($_GET["sDomainX"],"http")){
			$sDomainX = "http://".$_GET["sDomainX"]; 
		}
		else{
			$sDomainX = $_GET["sDomainX"]; 
		}
	}
	
	//$arUrl = parse_url($sDomain);
	$arUrl = parse_url($sDomainX);
	$sDomain = "http://".$arUrl['host'].""; // prints 'google.com'
	//$sDomainX = $sDomain."/";
	
	//die($sDomain);
	
		
	//////////////////////////////////////////////////////////
	//
	//	Clean Cache Temp
	//
	//////////////////////////////////////////////////////////
	
	// clear old tmp files from cache
	$imgfolder = 'tmp/';
	$foldercontent = scandir($imgfolder);

	foreach($foldercontent as $entry){
		if($entry != '.' && $entry != '..'){

			// older than ....?
			$filetime = filemtime($imgfolder.$entry);
			$date = 60; 			//60
			//$date = 259200; ;		//60*60*24*3;	--- 3 days
			//$date = 604800; ;		//60*60*24*7;	--- 1 weeks
			//$date = 1209600; ;	//60*60*24*14;	--- 2 weeks

			if($filetime !== false && $filetime > 0 && $filetime < time()-$date){
				//system("rm -r ".$backupfolder.$entry);
				unlink($imgfolder.$entry);
			}
		}
	} 

	//////////////////////////////////////////////////////////
	//
	//	Change String DOM Source
	//
	//////////////////////////////////////////////////////////
		
	// Get page code
	//$shtml = compressHTML(file_get_contents($sDomainX)); 
	$shtml = trim(file_get_contents($sDomainX)); 
	
	// replace main tags 
	$shtml = str_replace("script","noscript",$shtml);
	$shtml = str_replace("body","p",$shtml);
	$shtml = str_replace("html","p",$shtml);
	$shtml = str_replace("!DOCTYPE ","",$shtml);
	
	// replace image path
	//$shtml = str_replace("src='","src='".$sDomain."",$shtml);
	//$shtml = str_replace('src="','src="'.$sDomain.'',$shtml);
	
	// replace links path
	$shtml = str_replace('href="','href="'.$sDomain.'',$shtml);
	$shtml = str_replace("href='","href='".$sDomain."",$shtml);

	// tests *
	$shtml = str_replace("'/css/",$sDomain."'css/",$shtml);
	$shtml = preg_replace("/<\\/?mdoc(\\s+.*?>|>)/", "", $shtml);		// remove tags example - not in use

	// remove just the <style> tags:
	$shtmlx =  preg_replace('%<style.*?</style>%i', '', $shtml);		//  - not in use
	$shtmlx =  preg_replace('~<style .*?>(.*?)</style>~','', $shtml);	//  - not in use
	
	$shtml = preg_replace(
		array(
		// Remove invisible content
		'@<noscript[^>]*?.*?</noscript>@siu',
		'@<script[^>]*?.*?</script>@siu',
		'@<style[^>]*?.*?</style>@siu'
		),
		array(
		' ',
		' ',
		' '
		),
		$shtml
	);

	// remove html comments
	$shtml = preg_replace('/<!--(.|\s)*?-->/', '', $shtml);

	// remove new line and tabs
	$shtml =  str_replace('\t',' ', $shtml);
	$shtml =  str_replace('\n',' ', $shtml);		

	//////////////////////////////////////////////////////////
	//
	//	Change DOMDocument
	//
	//////////////////////////////////////////////////////////
			
	//$doc = new DOMDocument('1.0', 'UTF-8');
	$doc = new DOMDocument('1.0', 'iso-8859-1');
	//$doc = new DOMDocument();
	//$doc = $dom->loadHTMLFile($sDomain);
	//$doc = new DOMDocument();
	$doc->loadHTML($shtml);	
	
	// Grab css files as url
	$csslinks = $doc->getElementsByTagName('link');	
	foreach ($csslinks as $csslink) {
		if(!stristr($csslink->getAttribute('href'),"http")){
			$csslink->setAttribute('href', $sDomain."".$csslink->getAttribute('href'));	
			$cssArDom[] =  $sDomain."".$csslink->getAttribute('href');
		}
		else{
			$cssArDom[] =  $csslink->getAttribute('href');
		}
	}
	
	// Grab images as src
	$imgs = $doc->getElementsByTagName('img');	
	foreach ($imgs as $img) {
	
			$picurl = $img->getAttribute('src'); // real img path	- ex: http://www.example.com/pic.jpg			
			$picname = basename($picurl);		 // img name		- ex: pic.jpg
			
			if(! stristr($img->getAttribute('src'),$sDomain) && !stristr($img->getAttribute('src'),"http")  ){
				$arDomImg[] = $sDomain."".$img->getAttribute('src'); // LOCAL WITHOUT
				$img->setAttribute('src', $localDomain."tmp/".$picname);	
			}			
			else if (strstr($img->getAttribute('src'),$sDomain)  ){
				$arDomImg[] = $img->getAttribute('src'); // LOCAL NORMAL
				$img->setAttribute('src', $localDomain."tmp/".$picname);	
			}	
			else if (!strstr($img->getAttribute('src'),$sDomain) && stristr($img->getAttribute('src'),"http")  ){
				$tmpImgExtEnc = md5($img->getAttribute('src'));
				$arDomImgExt[] = array($img->getAttribute('src'),$tmpImgExtEnc); // ORIGINAL LINK
				$img->setAttribute('src', $localDomain."tmp/".$tmpImgExtEnc);	
			}	

	}
	
	//print "<pre>"; var_dump($arDomImg); print "</pre>"; die();
	
	// Write images that belongs to current domain on cache 
	$cntImg=0;
	foreach($arDomImg as $arDomImgSrc){	
		$picurl = $arDomImgSrc; 								// real img path	- ex: http://www.example.com/pic.jpg
		$picname = basename($arDomImgSrc);		 				// img name		- ex: pic.jpg
		
		if(preg_match("/.jpg|.png|.gif/",$picname )){
			$picdata = file_get_contents($arDomImgSrc);			// read image source
			$pictmp = "tmp/".$picname;							// new img path	- ex: tmp/pic.jpg
			//$pictmp = "tmp/".parse_url($sDomain,PHP_URL_HOST);
			$piclocal =  $localDomain."tmp/".$picname;			// new img absolute path - ex: http://localhost/tmp/pic.jpg
			
			$fp = fopen($pictmp, "w");							// save image
			fputs ($fp, $picdata);
			fclose ($fp);
		}		
		$cntImg++;	
	}
	
	// Write images that not belongs to current domain on cache 
	foreach($arDomImgExt as $arDomImgExtSrc){	
		$picurl = $arDomImgExtSrc[0]; 							// real img path	- ex: http://www.example.com/pic.jpg
		$picname = basename($arDomImgExtSrc[0]);		 		// img name		- ex: pic.jpg
		
		if(preg_match("/.jpg|.png|.gif/",$picname )){
			$picdata = file_get_contents($arDomImgExtSrc[0]);	// read image source
			$pictmp = "tmp/".$arDomImgExtSrc[1];				// new img path	- ex: tmp/pic.jpg
			
			$fp = fopen($pictmp, "w");							// save image
			fputs ($fp, $picdata);
			fclose ($fp);
		}		
		
	}

	$shtml = utf8_decode($doc->saveHTML($doc));					// Decode UTF8
	// Compress html into single line code
	$shtml =  compressHTML($shtml);

		
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Website Screenshots</title>
		<script type="text/javascript" src="<?php echo $localDomain?>js/jquery.js"></script>
		<script type="text/javascript" src="<?php echo $localDomain?>js/html2canvas.js"></script>
		<script type="text/javascript" src="<?php echo $localDomain?>js/jquery.plugin.html2canvas.js"></script>
		<script type="text/javascript" src="<?php echo $localDomain?>js/flashcanvas.min.js"></script>
		<script type="text/javascript" src="<?php echo $localDomain?>js/html2png_init.js"></script>

	<?php
	
		foreach($cssArDom as $cssDom){
			echo '<link rel="stylesheet" type="text/css" href="'.$cssDom.'">';
		}
	
		echo '
			<script> 
				var localDomain = "'.$localDomain.'"; 
				var sDomain = "'.$arUrl["host"].'";
			</script>		
		';
	?>	
	
	</head>
		<body>

		<?php
			echo $shtml;
			//echo trim($shtml);
			//unset($shtml);
			$shtml = null;
		?>

		<div>
			<canvas id="cvs1" height="0" width="0"></canvas>
			<canvas id="cvs2" height="0" width="0"></canvas>
			<canvas id="cvs3" height="0" width="0"></canvas>
		</div> 
		
		<style>
			body { background: #dddddd }
		</style>
		
	
	</body>
</html>

<?php
$end = microtime(true);
$elapsed = $end - $start;
echo "took $elapsed seconds\r\n";
?>










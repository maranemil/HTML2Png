<?php /** @noinspection SpellCheckingInspection */

error_reporting(0);
ini_set("display_error", 0);
ini_set('memory_limit', '256M');
set_time_limit(45);

$start = microtime(true);

/**
 * @param $input
 *
 * @return string|string[]|null
 */
function compressHTML($input)
{
    return preg_replace(
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
}

//$localDomain = "http://localhost/HTML2Png/"; // local path
$localDomain = "https://" . $_SERVER["HTTP_HOST"] . str_replace(basename($_SERVER["SCRIPT_NAME"]), "", $_SERVER["SCRIPT_NAME"]);

/*print "<pre>"; print_r($_SERVER); print "</pre>"; die();*/

// default
$sDomainX = "https://www.wikipedia.de";

// Usage: http://localhost/HTML2Png/image.php?sDomainX=domain_here
if ($_GET["sDomainX"]) {
    if (stripos($_GET["sDomainX"], "http") === false) {
        $sDomainX = "https://" . $_GET["sDomainX"];
    } else {
        $sDomainX = $_GET["sDomainX"];
    }
}

//$arUrl = parse_url($sDomain);
$arUrl = parse_url($sDomainX);
$sDomain = "https://" . $arUrl['host']; // prints 'google.com'
//$sDomainX = $sDomain."/";

//die($sDomain);

//////////////////////////////////////////////////////////
//
//	Clean Cache Temp
//
//////////////////////////////////////////////////////////

// clear old tmp files from cache
$imgFolder = 'tmp/';
$folderContent = scandir($imgFolder);

foreach ($folderContent as $entry) {
    if ($entry !== '.' && $entry !== '..') {
        // older than ....?
        $fileTime = filemtime($imgFolder . $entry);
        $date = 60;            //60

        //$date = 259200; ;		//60*60*24*3;	--- 3 days
        //$date = 604800; ;		//60*60*24*7;	--- 1 weeks
        //$date = 1209600; ;	//60*60*24*14;	--- 2 weeks

        if ($fileTime !== false && $fileTime > 0 && $fileTime < time() - $date) {
            //system("rm -r ".$backupFolder.$entry);
            unlink($imgFolder . $entry);
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
// replace image path
//$shtml = str_replace("src='","src='".$sDomain."",$shtml);
//$shtml = str_replace('src="','src="'.$sDomain.'',$shtml);

// replace links path
// tests *
$shtml = str_replace(array("script", "body", "html", "!DOCTYPE ", 'href="', "href='", "'/css/"), array("noscript", "p", "p", "", 'href="' . $sDomain, "href='" . $sDomain, $sDomain . "'css/"), $shtml);
$shtml = preg_replace("/<\\/?mdoc(\\s+.*?>|>)/", "", $shtml);        // remove tags example - not in use

// remove just the <style> tags:
$strHtmlx = preg_replace('%<style.*?</style>%i', '', $shtml);        //  - not in use
$strHtmlx = preg_replace('~<style .*?>(.*?)</style>~', '', $strHtmlx);    //  - not in use

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
$shtml = str_replace(array('\t', '\n'), ' ', $shtml);

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
$cssLinks = $doc->getElementsByTagName('link');
$cssArDom = array();
foreach ($cssLinks as $cssLink) {
    if (stripos($cssLink->getAttribute('href'), "http") === false) {
        $cssLink->setAttribute('href', $sDomain . $cssLink->getAttribute('href'));
        $cssArDom[] = $sDomain . $cssLink->getAttribute('href');
    } else {
        $cssArDom[] = $cssLink->getAttribute('href');
    }
}

// Grab images as src
$arrImages = $doc->getElementsByTagName('img');
$arDomImg = array();
$arDomImgExt = array();
foreach ($arrImages as $img) {
    $picUrl = $img->getAttribute('src'); // real img path	- ex: http://www.example.com/pic.jpg
    $picName = basename($picUrl);         // img name		- ex: pic.jpg

    if (stripos($img->getAttribute('src'), $sDomain) === false && stripos($img->getAttribute('src'), "http") === false) {
        $arDomImg[] = $sDomain . $img->getAttribute('src'); // LOCAL WITHOUT
        $img->setAttribute('src', $localDomain . "tmp/" . $picName);
    } else if (strpos($img->getAttribute('src'), $sDomain) !== false) {
        $arDomImg[] = $img->getAttribute('src'); // LOCAL NORMAL
        $img->setAttribute('src', $localDomain . "tmp/" . $picName);
    } else if (strpos($img->getAttribute('src'), $sDomain) === false && stripos($img->getAttribute('src'), "http") !== false) {
        $tmpImgExtEnc = md5($img->getAttribute('src'));
        $arDomImgExt[] = array($img->getAttribute('src'), $tmpImgExtEnc); // ORIGINAL LINK
        $img->setAttribute('src', $localDomain . "tmp/" . $tmpImgExtEnc);
    }
}

//print "<pre>"; var_dump($arDomImg); print "</pre>"; die();

// Write images that belong to current domain on cache
$cntImg = 0;
foreach ($arDomImg as $arDomImgSrc) {
    $picUrl = $arDomImgSrc;                                // real img path	- ex: http://www.example.com/pic.jpg
    $picName = basename($arDomImgSrc);                        // img name		- ex: pic.jpg

    if (preg_match("/.jpg|.png|.gif/", $picName)) {
        $picData = file_get_contents($arDomImgSrc);            // read image source
        $picTemp = "tmp/" . $picName;                            // new img path	- ex: tmp/pic.jpg
        //$picTemp = "tmp/".parse_url($sDomain,PHP_URL_HOST);
        $piclocal = $localDomain . "tmp/" . $picName;            // new img absolute path - ex: http://localhost/tmp/pic.jpg

        $fp = fopen($picTemp, 'wb');                            // save image
        fwrite($fp, $picData);
        fclose($fp);
    }
    $cntImg++;
}

// Write images that not belongs to current domain on cache
foreach ($arDomImgExt as $arDomImgExtSrc) {
    $picUrl = $arDomImgExtSrc[0];                            // real img path	- ex: http://www.example.com/pic.jpg
    $picName = basename($arDomImgExtSrc[0]);                // img name		- ex: pic.jpg

    if (preg_match("/.jpg|.png|.gif/", $picName)) {
        $picData = file_get_contents($arDomImgExtSrc[0]);    // read image source
        $picTemp = "tmp/" . $arDomImgExtSrc[1];                // new img path	- ex: tmp/pic.jpg

        $fp = fopen($picTemp, 'wb');                            // save image
        fwrite($fp, $picData);
        fclose($fp);
    }
}

$shtml = utf8_decode($doc->saveHTML($doc));                    // Decode UTF8
// Compress html into single line code
$shtml = compressHTML($shtml);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Website Screenshots</title>
    <script type="text/javascript" src="<?php echo $localDomain ?>js/jquery.js"></script>
    <script type="text/javascript" src="<?php echo $localDomain ?>js/html2canvas.js"></script>
    <script type="text/javascript" src="<?php echo $localDomain ?>js/jquery.plugin.html2canvas.js"></script>
    <script type="text/javascript" src="<?php echo $localDomain ?>js/flashcanvas.min.js"></script>
    <script type="text/javascript" src="<?php echo $localDomain ?>js/html2png_init.js"></script>

    <?php

    foreach ($cssArDom as $cssDom) {
        echo '<link rel="stylesheet" type="text/css" href="' . $cssDom . '">';
    }

    echo '
			<script> 
				const localDomain = "' . $localDomain . '"; 
				const sDomain = "' . $arUrl["host"] . '";
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
    body {
        background: #dddddd
    }
</style>

</body>
</html>

<?php
$end = microtime(true);
$elapsed = $end - $start;
echo "took $elapsed seconds\r\n";
?>










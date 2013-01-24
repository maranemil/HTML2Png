<?php

	error_reporting(0);
	ini_set("display_error",0);
	ini_set('memory_limit', '128M');

	//$localDomain = "http://localhost/HTML2Png/"; // local path
	$localDomain = "http://".$_SERVER["HTTP_HOST"]."".str_replace(basename($_SERVER["SCRIPT_NAME"]), "",$_SERVER["SCRIPT_NAME"]); 

	/*print "<pre>"; print_r($_SERVER); print "</pre>"; die();*/
	
	// default
	$sDomainX = "http://www.wikipedia.de";

	// Usage: http://localhost/HTML2Png/image.php?sDomainX=domain_here
	if($_GET["sDomainX"]){
		if(!strstr($_GET["sDomainX"],"http")){
			$sDomainX = "http://".$_GET["sDomainX"]; 
		}
		else{
			$sDomainX = $_GET["sDomainX"]; 
		}
	}

	$sDomain = $sDomainX."/";
	$arUrl = parse_url($sDomain);
?>

<?php
	$start = microtime(true);
?>

<?php

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

		// Get page code
		$shtml = file_get_contents($sDomain.""); 

		//sleep(1);
		// replace main tags 
		$shtml = str_replace("script","noscript",$shtml);
		$shtml = str_replace("body","p",$shtml);
		$shtml = str_replace("html","p",$shtml);
		$shtml = str_replace("!DOCTYPE ","",$shtml);
		
		// replace image path
		$shtml = str_replace("src='","src='".$sDomain."",$shtml);
		$shtml = str_replace('src="','src="'.$sDomain.'',$shtml);
		
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
			'@<style[^>]*?.*?</style>@siu'
			),
			array(
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
		
		// read all local images from DOM
		$doc = new DOMDocument();
		@$doc->loadHTML($shtml);
		$tags = $doc->getElementsByTagName('img');

		//$arUrl = parse_url($sDomain,PHP_URL_PATH);

		/*
		Array
		(
			[scheme] => http
			[host] => hostname
			[user] => benutzername
			[pass] => passwort
			[path] => /pfad
			[query] => argument=wert
			[fragment] => textanker
		)
		*/

		foreach ($tags as $tag) {

				$picurl = $tag->getAttribute('src'); // real img path	- ex: http://www.example.com/pic.jpg
				$picname = basename($picurl);		 // img name		- ex: pic.jpg

				if(strstr($picurl,$sDomain)){		// if image belongs to www.example.com and not other website than make a copy in local tmp

					$picdata = file_get_contents($picurl);			// read image source
					$pictmp = "tmp/".$picname;						// new img path	- ex: tmp/pic.jpg
					//$pictmp = "tmp/".parse_url($sDomain,PHP_URL_HOST);
					$piclocal =  $localDomain."tmp/".$picname;		// new img absolute path - ex: http://localhost/tmp/pic.jpg

					$fp = fopen($pictmp, "w");	// save image
					fputs ($fp, $picdata);
					fclose ($fp);

					$shtml = str_replace($picurl,$piclocal,$shtml); // replace the image path in DOM string if match
				}
		}
		
		?>
<!DOCTYPE html>
<html>
    <head>
        <title>Website Screenshots</title>

		<script type="text/javascript" src="<?php echo $localDomain?>js/jquery.js"></script>
		<script type="text/javascript" src="<?php echo $localDomain?>js/html2canvas.js"></script>
		<script type="text/javascript" src="<?php echo $localDomain?>js/jquery.plugin.html2canvas.js"></script>
		<script type="text/javascript" src="<?php echo $localDomain?>js/flashcanvas.min.js"></script>

	<?php

		echo '
			<script> 
			
				var localDomain = "'.$localDomain.'"; 
				var sDomain = "'.$arUrl["host"].'";
			
			</script>
		
		';
	?>



	<script>

		$(document).ready(function(){

			////////////////////////////////////////////////////////////////////////////
			//
			// Record Canvas and save in png file
			//
			////////////////////////////////////////////////////////////////////////////

			var bCopyPNG = 1;
			var bWritePNG = 1;

			// https://github.com/niklasvh/html2canvas
			var canvasRecord = $('body').html2canvas([ $(this).get(3) ]);
			// html2canvas([ $this.get(0) ], options);

			if(bCopyPNG){

				var draw_interval = setTimeout(function() { // setInterval setTimeout

				//var canvas = $('body').html2canvas([ $(this).get(3) ]);
					canvas = $("canvas")[0]
					canvas.width = canvas.width;
					canvas = $("canvas")[1]
					canvas.width = canvas.width;

					var img = new Image();
					img.src = $("canvas")[3].toDataURL('image/png');
					//img.width = 20;

					img.onload = function() {
						//canvas = $("canvas")[3]
						//new thumbnailer(canvas, img, 350, 1);
						//ctx = canvas.getContext('2d');
						//canvas.width = canvas.width;
						//ctx.clearRect(0, 0, canvas.width, canvas.height);
					}

					if(bWritePNG){
						
						var ajax = new XMLHttpRequest();
						ajax.onreadystatechange=function()
						{
							if (ajax.readyState==4 && ajax.status==200)
							{
								console.log(ajax.responseText)
							}
						}
						ajax.open("POST",localDomain+'makefile.php?domain='+sDomain,false);
						ajax.setRequestHeader('Content-Type', 'application/upload');
						//ajax.setRequestHeader("Content-type","application/x-www-form-urlencoded");
						ajax.send(img.src); 
					}
					
					// print screens on same page if necesary
					//document.body.appendChild(img);

				}, 5180)

			}

			//data:image/png;base64

			/////////////////////////////////////////////////////////////
			//
			// Resize functions
			//
			/////////////////////////////////////////////////////////////

			//returns a function that calculates lanczos weight
			function lanczosCreate(lobes){
			  return function(x){
				if (x > lobes) 
				  return 0;
				x *= Math.PI;
				if (Math.abs(x) < 1e-16) 
				  return 1
				var xx = x / lobes;
				return Math.sin(x) * Math.sin(xx) / x / xx;
			  }
			}

			//elem: canvas element, img: image element, sx: scaled width, lobes: kernel radius
			function thumbnailer(elem, img, sx, lobes){ 
				this.canvas = elem;
				elem.width = img.width;
				elem.height = img.height;
				elem.style.display = "none";
				this.ctx = elem.getContext("2d");
				this.ctx.drawImage(img, 0, 0);
				this.img = img;
				this.src = this.ctx.getImageData(0, 0, img.width, img.height);
				this.dest = {
					width: sx,
					height: Math.round(img.height * sx / img.width),
				};
				this.dest.data = new Array(this.dest.width * this.dest.height * 3);
				this.lanczos = lanczosCreate(lobes);
				this.ratio = img.width / sx;
				this.rcp_ratio = 2 / this.ratio;
				this.range2 = Math.ceil(this.ratio * lobes / 2);
				this.cacheLanc = {};
				this.center = {};
				this.icenter = {};
				setTimeout(this.process1, 0, this, 0);
			}

			thumbnailer.prototype.process1 = function(self, u){
				self.center.x = (u + 0.5) * self.ratio;
				self.icenter.x = Math.floor(self.center.x);
				for (var v = 0; v < self.dest.height; v++) {
					self.center.y = (v + 0.5) * self.ratio;
					self.icenter.y = Math.floor(self.center.y);
					var a, r, g, b;
					a = r = g = b = 0;
					for (var i = self.icenter.x - self.range2; i <= self.icenter.x + self.range2; i++) {
						if (i < 0 || i >= self.src.width) 
							continue;
						var f_x = Math.floor(1000 * Math.abs(i - self.center.x));
						if (!self.cacheLanc[f_x]) 
							self.cacheLanc[f_x] = {};
						for (var j = self.icenter.y - self.range2; j <= self.icenter.y + self.range2; j++) {
							if (j < 0 || j >= self.src.height) 
								continue;
							var f_y = Math.floor(1000 * Math.abs(j - self.center.y));
							if (self.cacheLanc[f_x][f_y] == undefined) 
								self.cacheLanc[f_x][f_y] = self.lanczos(Math.sqrt(Math.pow(f_x * self.rcp_ratio, 2) + Math.pow(f_y * self.rcp_ratio, 2)) / 1000);
							weight = self.cacheLanc[f_x][f_y];
							if (weight > 0) {
								var idx = (j * self.src.width + i) * 4;
								a += weight;
								r += weight * self.src.data[idx];
								g += weight * self.src.data[idx + 1];
								b += weight * self.src.data[idx + 2];
							}
						}
					}
					var idx = (v * self.dest.width + u) * 3;
					self.dest.data[idx] = r / a;
					self.dest.data[idx + 1] = g / a;
					self.dest.data[idx + 2] = b / a;
				}

				if (++u < self.dest.width) 
					setTimeout(self.process1, 0, self, u);
				else 
					setTimeout(self.process2, 0, self);
			};

			thumbnailer.prototype.process2 = function(self){
				self.canvas.width = self.dest.width;
				self.canvas.height = self.dest.height;
				self.ctx.drawImage(self.img, 0, 0);
				self.src = self.ctx.getImageData(0, 0, self.dest.width, self.dest.height);
				var idx, idx2;
				for (var i = 0; i < self.dest.width; i++) {
					for (var j = 0; j < self.dest.height; j++) {
						idx = (j * self.dest.width + i) * 3;
						idx2 = (j * self.dest.width + i) * 4;
						self.src.data[idx2] = self.dest.data[idx];
						self.src.data[idx2 + 1] = self.dest.data[idx + 1];
						self.src.data[idx2 + 2] = self.dest.data[idx + 2];
					}
				}
				self.ctx.putImageData(self.src, 0, 0);
				self.canvas.style.display = "block";
			}




		}); // end ready

	</script>
	
		<style type="text/css" media="screen">
		/* overwrite css styles */
		<!--
		  body {font: 75%/1.4 verdana,geneva,lucida,arial,sans-serif;
				background: #fff; color: #000;}
		  h1 {font-size: 160%; color: green; font-style: italic;}
		  h2 {font-size: 140%; color: purple;}
		  -->
		  </style>

		  <style type="text/css" media="print"><!--
		  body {font: 12pt georgia,serif;}
		  h1 {font-size: 18pt;}
		  h2 {font-size: 15pt;}
		  -->
		</style>

	</head>

		<body>

		<?php
		echo trim($shtml);
		//unset($shtml);
		$shtml = null;
		?>

		<div>
			<canvas id="cvs1" height="0" width="0"></canvas>
			<canvas id="cvs2" height="0" width="0"></canvas>
			<canvas id="cvs3" height="0" width="0"></canvas>
		</div> 
	
	</body>
</html>

<?php
$end = microtime(true);
$elapsed = $end - $start;
echo "took $elapsed seconds\r\n";
?>
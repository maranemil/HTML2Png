/*
  html2canvas @VERSION@ <http://html2canvas.hertzen.com>
  Copyright (c) 2011 Niklas von Hertzen. All rights reserved.
  http://www.twitter.com/niklasvh

  Released under MIT License
*/
let h2cSelector, h2cOptions;
(function(document, window) {

    /*var scrStart = '<script type="text/javascript" src="', scrEnd = '"></script>';
    document.write(scrStart + '../js/jquery-1.7.1.js' + scrEnd);
    var html2canvas = ['html2canvas', 'jquery.plugin.html2canvas'], i;
    for (i = 0; i < html2canvas.length; ++i) {
        document.write(scrStart + '../js/' + html2canvas[i] + '.js' + scrEnd);
    }*/

    window.onload = function() {
        
        h2cSelector = [document.body];
        
        if (window.setUp) {
            window.setUp();
        }

        setTimeout(function() {
           
			$(h2cSelector).html2canvas({
                flashcanvas: "flashcanvas.min.js",
                logging: true,
                profile: true,
                useCORS: true
            });

        }, 1100);
    };

}(document, window));
// noinspection SpellCheckingInspection

$(document).ready(function () {

    ////////////////////////////////////////////////////////////////////////////
    //
    // Record Canvas and save in png file
    //
    ////////////////////////////////////////////////////////////////////////////

    const bCopyPNG = 1;
    const bWritePNG = 1;

    // https://github.com/niklasvh/html2canvas
    const canvasRecord = $('body').html2canvas([$(this).get(3)]);
    // html2canvas([ $this.get(0) ], options);

    if (bCopyPNG) {

        const draw_interval = setTimeout(function () { // setInterval setTimeout

            //var canvas = $('body').html2canvas([ $(this).get(3) ]);
            let canvas = $("canvas")[0]
            canvas.width = canvas.width;

            canvas = $("canvas")[1]
            canvas.width = canvas.width;

            const img = new Image();
            img.src = $("canvas")[3].toDataURL('image/png');
            //img.width = 20;

            img.onload = function () {
                //canvas = $("canvas")[3]
                //new thumbnailer(canvas, img, 350, 1);
                //ctx = canvas.getContext('2d');
                //canvas.width = canvas.width;
                //ctx.clearRect(0, 0, canvas.width, canvas.height);
            }

            if (bWritePNG) {

                const ajax = new XMLHttpRequest();
                ajax.onreadystatechange = function () {
                    if (ajax.readyState === 4 && ajax.status === 200) {
                        console.log(ajax.responseText)
                    }
                }
                let sDomain;
                let localDomain;
                ajax.open("POST", localDomain + 'makefile.php?domain=' + sDomain, false);
                ajax.setRequestHeader('Content-Type', 'application/upload');
                //ajax.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                ajax.send(img.src);
            }

            // print screens on same page if necesary
            //document.body.appendChild(img);

        }, 5180);

    }

    //data:image/png;base64

    /////////////////////////////////////////////////////////////
    //
    // Resize functions
    //
    /////////////////////////////////////////////////////////////

    //returns a function that calculates lanczos weight
    function lanczosCreate(lobes) {
        return function (x) {
            if (x > lobes)
                return 0;
            x *= Math.PI;
            if (Math.abs(x) < 1e-16)
                return 1
            let xx = x / lobes;
            return Math.sin(x) * Math.sin(xx) / x / xx;
        }
    }

    //elem: canvas element, img: image element, sx: scaled width, lobes: kernel radius
    function thumbnailer(elem, img, sx, lobes) {
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

    thumbnailer.prototype.process1 = function (self, u) {
        let idx;
        self.center.x = (u + 0.5) * self.ratio;
        self.icenter.x = Math.floor(self.center.x);
        let weight;
        for (let v = 0; v < self.dest.height; v++) {
            self.center.y = (v + 0.5) * self.ratio;
            self.icenter.y = Math.floor(self.center.y);
            let a, r, g, b;
            a = r = g = b = 0;
            for (let i = self.icenter.x - self.range2; i <= self.icenter.x + self.range2; i++) {
                if (i < 0 || i >= self.src.width)
                    continue;
                const f_x = Math.floor(1000 * Math.abs(i - self.center.x));
                if (!self.cacheLanc[f_x])
                    self.cacheLanc[f_x] = {};
                for (let j = self.icenter.y - self.range2; j <= self.icenter.y + self.range2; j++) {
                    if (j < 0 || j >= self.src.height)
                        continue;
                    const f_y = Math.floor(1000 * Math.abs(j - self.center.y));
                    if (self.cacheLanc[f_x][f_y] == undefined)
                        self.cacheLanc[f_x][f_y] = self.lanczos(Math.sqrt(Math.pow(f_x * self.rcp_ratio, 2) + Math.pow(f_y * self.rcp_ratio, 2)) / 1000);
                    weight = self.cacheLanc[f_x][f_y];
                    if (weight > 0) {
                        idx = (j * self.src.width + i) * 4;
                        a += weight;
                        r += weight * self.src.data[idx];
                        g += weight * self.src.data[idx + 1];
                        b += weight * self.src.data[idx + 2];
                    }
                }
            }
            idx = (v * self.dest.width + u) * 3;
            self.dest.data[idx] = r / a;
            self.dest.data[idx + 1] = g / a;
            self.dest.data[idx + 2] = b / a;
        }

        if (++u < self.dest.width)
            setTimeout(self.process1, 0, self, u);
        else
            setTimeout(self.process2, 0, self);
    };

    thumbnailer.prototype.process2 = function (self) {
        self.canvas.width = self.dest.width;
        self.canvas.height = self.dest.height;
        self.ctx.drawImage(self.img, 0, 0);
        self.src = self.ctx.getImageData(0, 0, self.dest.width, self.dest.height);
        let idx, idx2;
        for (let i = 0; i < self.dest.width; i++) {
            for (let j = 0; j < self.dest.height; j++) {
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
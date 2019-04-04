////////////////////////////////////////////////////////////////
//
//  StillPlayer
//
//    2017/01/11 0.2.2
//      correct image erase logic
//    2017/02/09 0.3.0
//      add multi-cam-view panel process
//    2017/02/25 0.3.1
//      add getPictureDataURL
//      add MVC step
//    2017/03/04 0.3.2
//      apply refactoring 
//    
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

define(["Include", "Utility"], function (inc, util) {
    return {
        create: function (canvasID, glb, doc, undef) {
            var DEF_STACKSIZE = 30;
            var DEF_STACKSTRIDE = 1;
            var DEF_START_STRIDE = 30;
            var CLIENT_SIZE = 4;
            var CAPTION_Y = 12;
            var CAMNAME_Y = 28;
            var BLOB_TYPE = "image/jpeg"; //"image/png"; //"image/jpeg";
            var IMAGE_EXT = ".jpg"; //".png"; //".jpg";
            //
            var canvas = doc.getElementById(canvasID);
            var ctx = canvas.getContext("2d");
            var engine;
            var reportNotify;
            // media
            var caption = "";
            var cameras = null;
            var curAngle = 0;
            // draw
            var planeRatio = 1;
            var plane = {"x": 0, "y": 0, "w": 0, "h": 0}; // drawing plane
            // zoom
            var zoom = [0, 0, 1, 1];
            var zooming = false;
            // object
            var imageMan;
            var thumbnail;
            var stillFrame;
            var frameStack;
            //
            function report(s) {
                if (reportNotify) {
                    reportNotify("StillPlayer:" + s);
                }
            }
            function getNear(arr, ix) {
                var l = ix, r = ix;
                while ((l >= 0) || (r < arr.length)) {
                    if ((l >= 0) && (arr[l] !== null)) {
                        return arr[l];
                    }
                    if ((r < arr.length) && (arr[r] !== null)) {
                        return arr[r];
                    }
                    l--;
                    r++
                }
                return null;
            }
            function checkAngle(angle) {
                return (angle >= 0) && (angle < cameras.length);
            }
            function resetPlane(ratio) {
                var cw = canvas.width;
                var ch = canvas.height;
                plane.w = cw;
                plane.h = ch;
                if (ratio < (cw / ch)) {
                    plane.w = ratio * plane.h;
                } else {
                    plane.h = plane.w / ratio;
                }
                plane.x = (cw - plane.w) / 2;
                plane.y = (ch - plane.h) / 2;
                planeRatio = ratio;
            }
            function clear() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
            function draw() {
                var cw = canvas.width;
                var ch = canvas.height;
                var panel = null;
                //
                ctx.clearRect(0, 0, cw, ch);
                if (imageMan.isImageReady()) {
                    if (imageMan.isMcvMode()) {
                        ctx.fillStyle = "Black";
                        ctx.fillRect(plane.x, plane.y, plane.w, plane.h);
                        ctx.strokeStyle = "Gray";
                        var px = 0, py = 0, pw = 0, ph = 0;
                        for (var i = imageMan.getMcvSize() - 1; i >= 0; i--) {
                            panel = imageMan.getMcvPanel(i);
                            px = plane.x + panel.x * plane.w;
                            py = plane.y + panel.y * plane.h;
                            pw = panel.scale * plane.w;
                            ph = panel.scale * plane.h;
                            if (!panel.valid) {
                                ctx.strokeRect(px, py, pw, ph);
                            } else {
                                ctx.drawImage(panel.img, px, py, pw, ph);
                            }
                            // draw name
                            ctx.fillStyle = "Gray";
                            ctx.font = "bold 13px sans-serif";
                            ctx.fillText(cameras[i][inc.SV_NAME], px, py + CAPTION_Y);
                        }
                    } else {
                        // setup canvas-transform
                        ctx.setTransform(1, 0, 0, 1, 0, 0);
                        if (zooming) {
                            var ax = plane.w * zoom[0] + plane.x;
                            var ay = plane.h * zoom[1] + plane.y;
                            var aw = plane.w * (zoom[2] - zoom[0]);
                            var ah = plane.h * (zoom[3] - zoom[1]);
                            // get zoomed area
                            var nw = cw;
                            var nh = ch;
                            if ((cw / ch) > (aw / ah)) {
                                nw = nh * (aw / ah);
                            } else {
                                nh = nw * (ah / aw);
                            }
                            // get scale
                            var sx = nw / aw;
                            var sy = nh / ah;
                            // get translate
                            var dx = (cw - nw) / 2;
                            var dy = (ch - nh) / 2;
                            var tx = sx * ax - dx;
                            var ty = sy * ay - dy;
                            // set scale & translate
                            ctx.translate(-tx, -ty);
                            ctx.scale(sx, sy);
                        }
                        panel = imageMan.getMcvPanel(curAngle);
                        if (panel.valid) {
                            ctx.drawImage(panel.img, plane.x, plane.y, plane.w, plane.h);
                        }
                    }
                }
                // draw caption
                ctx.fillStyle = "lightGray";
                ctx.font = "bold 13px sans-serif";
                ctx.fillText(caption, 8, CAPTION_Y);
                if (cameras && !imageMan.isMcvMode()) {
                    // draw camera name
                    ctx.fillStyle = "lightGray";
                    ctx.font = "bold 13px sans-serif";
                    ctx.fillText(cameras[curAngle][inc.SV_NAME], 8, CAMNAME_Y);
                }
            }
            function getImageBlob(xhr, url, done) {
                if (xhr && (url !== "")) {
                    xhr.onload = function () {
                        if (done) {
                            if (200 === this.status) {
                                var buf = this.response;
                                if (buf) {
                                    done(new Blob([buf], {type: BLOB_TYPE}));
                                    return;
                                }
                            }
                            done(null);
                        }
                    };
                    xhr.open("GET", url, true);
                    xhr.responseType = "arraybuffer";
                    xhr.send();
                }
            }
            //
            imageMan = (function () {
                var xhr = new XMLHttpRequest();
                var arr = null;
                var prgsNotify;
                var lock = false;
                var mcvmode = false;
                var ready = false;
                //
                function paintBlob(blob, angle, done) {
                    var agl = (angle === undef) ? curAngle : angle;
                    if ((blob === null) || (arr[agl].img === null)) {
                        glb.setTimeout(function () {
                            if (done) {
                                done(agl);
                            }
                        }, 10);
                    } else {
                        arr[agl].img.onload = (function (a, df) {
                            return function () {
                                glb.URL.revokeObjectURL(arr[a].img.src);
                                arr[a].valid = true;
                                if (done) {
                                    done(a);
                                } else {
                                    ready = true;
                                    draw();
                                }
                            };
                        })(agl);
                        arr[agl].img.src = glb.URL.createObjectURL(blob);
                    }
                }
                //
                return {
                    init: function () {
                        lock = false;
                        ready = false;
                        var X = inc.SV_X, Y = inc.SV_Y, S = inc.SV_SCALE;
                        arr = new Array(cameras.length);
                        for (var i = 0; i < cameras.length; i++) {
                            arr[i] = {};
                            arr[i][X] = 0;
                            arr[i][Y] = 0;
                            arr[i].img = null;
                            arr[i].valid = false;
                            if ([inc.SV_PANEL] in cameras[i]) {
                                var cp = cameras[i][inc.SV_PANEL];
                                arr[i][X] = (X in cp) ? cp[X] : 0;
                                arr[i][Y] = (Y in cp) ? cp[Y] : 0;
                                arr[i][S] = (S in cp) ? cp[S] : 0;
                                arr[i].img = new Image();
                            }
                        }
                    },
                    setMcvMode: function (f) {
                        mcvmode = f;
                        lock = false;
                    },
                    isMcvMode: function () {
                        return mcvmode;
                    },
                    getMcvSize: function () {
                        return arr.length;
                    },
                    getMcvPanel: function (angle) {
                        var a = (angle !== undef) ? angle : curAngle;
                        return arr[a];
                    },
                    getHitNumber: function (x, y) {
                        if (mcvmode) {
                            for (var i = 0; i < arr.length; i++) {
                                if ((x >= arr[i].x)
                                        && (x <= (arr[i].x + arr[i].scale))
                                        && (y >= arr[i].y)
                                        && (y <= (arr[i].y + arr[i].scale))) {
                                    return i;
                                }
                            }
                        }
                        return -1;
                    },
                    abandon: function () {
                        xhr.abort();
                        ready = false;
                    },
                    isImageReady: function () {
                        return ready;
                    },
                    displayBlob: function (getBlob) { // getBlob(angle) return blob or null
                        function loadimg(agl) {
                            paintBlob(getBlob(agl), agl, function (a) {
                                a = (a + 1) % cameras.length;
                                if (a !== curAngle) {
                                    loadimg(a);
                                } else {
                                    lock = false;
                                    ready = true;
                                    draw();
                                }
                            });
                        }
                        //
                        if (getBlob) {
                            if (!mcvmode) {
                                paintBlob(getBlob(curAngle));
                            } else {
                                if (!lock) {
                                    lock = true;
                                    for (var i = 0; i < arr.length; i++) {
                                        arr[i].valid = false;
                                    }
                                    loadimg(curAngle);
                                }
                            }
                        }
                    },
                    displayURL: function (getURL) { // getURL(angle) return URL or ""
                        function loadimg(agl) {
                            var url = getURL(agl);
                            getImageBlob(xhr, url, function (blob) {
                                paintBlob(blob, agl, function (a) {
                                    lock = false;
                                    if (prgsNotify) {
                                        prgsNotify(a + 1, cameras.length);
                                    }
                                    a = (a + 1) % cameras.length;
                                    if (a !== curAngle) {
                                        loadimg(a);
                                    } else {
                                        ready = true;
                                        draw();
                                        report("imageMan:displayURL MCV complete!")
                                    }
                                });
                            });
                        }
                        //
                        if (getURL) {
                            if (!mcvmode) {
                                var url = getURL(curAngle);
                                getImageBlob(xhr, url, function (blob) {
                                    paintBlob(blob);
                                    report("imageMan:displayURL done!")
                                });
                            } else {
                                loadimg(curAngle);
                            }
                        }
                    },
                    setProgressEvent: function (callback) {
                        prgsNotify = callback;
                    }
                };
            })();
            ////////
            // Thumbnail
            thumbnail = (function (size) {
                var timeTable = null;
                var store = null;
                var prgsNotify;
                var xhrs = new Array(size);
                for (var i = 0; i < xhrs.length; i++) {
                    xhrs[i] = new XMLHttpRequest();
                }
                //
                function createNext(total) {
                    var agl = 0, tn = 0, count = 0;
                    var url = "";
                    var complete = false;
                    var cmpl = new Array(cameras.length);
                    for (var i = 0; i < cameras.length; i++) {
                        cmpl[i] = false;
                    }
                    //
                    return function () {
                        do {
                            if (complete) {
                                return null;
                            }
                            url = "";
                            if (agl >= cameras.length) {
                                count = count + agl;
                                agl = 0;
                                tn++;
                                if (prgsNotify) {
                                    prgsNotify(count, total);
                                }
                            }
                            if (!cmpl[agl]) {
                                // check complete or not
                                if (tn < timeTable[agl].length) {
                                    var pos = inc.getThumbnailTime(cameras[agl], tn);
                                    url = (!pos) ? ""
                                            : inc.getThumnailSrcURL(cameras[agl]) + pos + IMAGE_EXT;
                                    var tmp = agl;
                                } else {
                                    cmpl[agl] = true;
                                    complete = (cmpl.every(function (p) {
                                        return p;
                                    }));
                                }
                            }
                            agl++;
                        } while (url === "")
                        //
                        return {
                            url: url,
                            pos: pos,
                            tindex: tmp
                        };
                    };
                }
                //
                return {
                    clear: function () {
                        this.abort();
                        timeTable = null;
                        store = null;
                    },
                    abort: function () {
                        for (var i = 0; i < xhrs.length; i++) {
                            if (xhrs[i]) {
                                xhrs[i].abort();
                            }
                        }
                    },
                    init: function () {
                        var next;
                        var total = 0;
                        //
                        function getTnumbnail(xhr) {
                            var n = next();
                            if (n !== null) {
                                getImageBlob(xhr, n.url,
                                        (function (x, p, a) {
                                            return function (blob) {
                                                var tx = util.getCloseIndex(timeTable[a], p);
                                                if (tx) {
                                                    store[a][tx] = blob;
                                                }
                                                getTnumbnail(x);
                                            };
                                        })(xhr, n.pos, n.tindex));
                            }
                        }
                        //
                        try {
                            // buildup time-table
                            timeTable = new Array(cameras.length);
                            for (var i = 0; i < cameras.length; i++) {
                                var tl = inc.getThumbnailTimelist(cameras[i]);
                                timeTable[i] = [];
                                for (var j = 0; j < tl.length; j++) {
                                    timeTable[i].push(tl[j]);
                                }
                                timeTable[i].sort(function (a, b) {
                                    return a - b;
                                });
                                total = total + tl.length;
                            }
                            next = createNext(total);
                            // buildup store
                            store = new Array(cameras.length);
                            for (var i = 0; i < cameras.length; i++) {
                                store[i] = new Array(timeTable[i].length);
                                for (var j = 0; j < store[i].length; j++) {
                                    store[i][j] = null;
                                }
                            }
                            //
                            for (var i = 0; i < xhrs.length; i++) {
                                getTnumbnail(xhrs[i]);
                            }
                        } catch (e) {
                            alert("False at thumbnail.init() " + e.toString());
                        }
                    },
                    getTimelist: function (angle) {
                        var a = (angle === undef) ? curAngle : angle;
                        return timeTable[a];
                    },
                    getTimeIndex: function (time, angle) {
                        var a = (angle === undef) ? curAngle : angle;
                        return util.getCloseIndex(timeTable[a], time);
                    },
                    getBlob: function (index, angle) { // return blob or null
                        var a = (angle === undef) ? curAngle : angle;
                        if ((index < 0) || (index >= store[a].length)) {
                            return null;
                        } else {
                            var blob = store[a][index];
                            return (blob !== null) ? blob : getNear(store[a], index);
                        }
                    },
                    getBlobByTime: function (time, angle) {
                        var a = (angle === undef) ? curAngle : angle;
                        var tix = util.getCloseIndex(timeTable[a], time);
                        return store[a][tix];
                    },
                    setProgressEvent: function (callback) {
                        prgsNotify = callback;
                    }
                };
            })(CLIENT_SIZE);
            ////////
            // Still
            stillFrame = (function () {
                var cXHR = new XMLHttpRequest();
                var timeTable = null;
                var cache = null;
                var cachePrgsNotify = null;
                //
                function getTimes(n) {
                    return (!timeTable) ? null
                            : ((n < 0) || (n >= timeTable.length)) ? null
                            : timeTable[n];
                }
                //
                return {
                    clear: function () {
                        this.abort();
                        timeTable = null;
                        cache = null;
                    },
                    abort: function () {
                        cXHR.abort();
                    },
                    init: function () {
                        var txhr = new XMLHttpRequest();
                        cache = new Array(cameras.length);
                        timeTable = new Array(cameras.length);
                        // make still time-list
                        (function setStillTimes(n) {
                            do {
                                if (n >= cameras.length) {
                                    return;
                                }
                                var url = inc.getStillTimeURL(cameras[n]);
                                n = (url === "") ? n + 1 : n;
                            } while (url === "");
                            //
                            txhr.onload = function () {
                                if (200 === txhr.status) {
                                    timeTable[n] = JSON.parse(txhr.responseText);
                                } else {
                                    timeTable[n] = [];
                                }
                                setStillTimes(n + 1);
                            };
                            txhr.open("GET", url, true);
                            txhr.send();
                        })(0);
                    },
                    getTimeTable: function () {
                        return timeTable;
                    },
                    getTimelist: function (angle) {
                        var a = (angle === undef) ? curAngle : angle;
                        return getTimes(a);
                    },
                    getCloseTime: function (time, angle) {
                        var a = (angle === undef) ? curAngle : angle;
                        var tl = getTimes(a);
                        return util.getCloseValue(tl, time);
                    },
                    getImageURL: function (time, angle) {
                        var a = (angle === undef) ? curAngle : angle;
                        var tl = getTimes(a);
                        if (tl) {
                            var pos = util.getCloseValue(tl, time);
                            var url = inc.getStillSrcURL(cameras[a]);
                            if (url !== "") {
//                                report("stillFrame:Request angle=" + a + " pos=" + pos)
                                return url + pos + IMAGE_EXT;
                            }
                        }
                        return "";
                    },
                    setCacheProgressEvent: function (callback) {
                        cachePrgsNotify = callback;
                    },
                    clearCache: function () {
                        cXHR.abort();
                        if (cache) {
                            for (var i = 0; i < cache.length; i++) {
                                cache[i] = null;
                            }
                        }
                        if (cachePrgsNotify) {
                            cachePrgsNotify(0);
                        }
                    },
                    cacheAllAngle: function (time) {
                        (function cacheAngle(n) {
                            do {
                                if (cachePrgsNotify) {
                                    cachePrgsNotify(n / cameras.length);
                                }
                                if (n >= cameras.length) {
                                    return;
                                }
                                var url = inc.getStillSrcURL(cameras[n]);
                                n = (url === "") ? n + 1 : n;
                            } while (url === "");
                            var pos = util.getCloseValue(timeTable[n], time);
                            var url = url + pos + IMAGE_EXT;
                            getImageBlob(cXHR, url, function (blob) {
                                if (blob) {
                                    cache[n] = blob;
                                }
                                cacheAngle(n + 1);
                            });
                        })(0);
                    },
                    getCacheBlob: function (angle) {
                        var a = (angle === undef) ? curAngle : angle;
                        return cache[a];
                    }
                };
            })();
            ////////
            // Frame-Stack
            frameStack = (function (poolsize) {
                var STACKSIZE = DEF_STACKSIZE;
                var store = null;
                var progressNotify = null;
                var stride = 0;
                // setup http-request pool
                var xhrs = new Array(poolsize);
                for (var i = 0; i < xhrs.length; i++)
                {
                    xhrs[i] = new XMLHttpRequest();
                }
                function abort() {
                    for (var i = 0; i < xhrs.length; i++) {
                        xhrs[i].abort();
                    }
                }
                //
                return {
                    clear: function () {
                        abort();
                        if (store) {
                            for (var i = 0; i < store.length; i++) {
                                store[i] = null;
                            }
                        }
                        stride = 0;
                    },
                    init: function () {
                        store = new Array(STACKSIZE);
                        for (var i = 0; i < cameras.length; i++) {
                            store[i] = new Array(cameras.length);
                        }
                    },
                    build: function (pos, strd) {
                        var cue = new Array(store.length);
                        var size = store.length;
                        var ax = curAngle, tax = 0;
                        var cx = 0, tcx = 0, nx = 0;
                        var complete = false;
                        //
                        function getTnumb(xhr) {
                            var url = "";
                            if (!complete) {
                                nx = cue[cx];
                                tax = ax;
                                ax++;
                                ax = ax % cameras.length;
                                if (ax === curAngle) {
                                    cx++;
                                    if (cx >= size) {
                                        complete = true;
                                        report("frameStack:build complete")
                                    }
                                }
                                url = inc.getThumnailSrcURL(cameras[tax]);
                                url = url + store[tax][nx].time + IMAGE_EXT;
                                //
                                getImageBlob(xhr, url, (function (x, a, n) {
                                    return function (blob) {
                                        store[a][n].blob = blob;
                                        if ((a === curAngle) && (progressNotify)) {
                                            progressNotify(n);
                                        }
                                        getTnumb(x);
                                    };
                                })(xhr, tax, nx));
                            }
                        }
                        //
                        try {
                            abort();
                            // setup stride
                            stride = (stride > 0) ? Math.ceil(stride / 2)
                                    : (strd === undef) ? DEF_START_STRIDE : strd;
                            // setup store
                            for (var i = 0; i < store.length; i++) {
                                var tl = stillFrame.getTimelist(i);
                                var mid = util.getCloseIndex(tl, pos);
                                var ix = mid - stride * (Math.ceil(size / 2));
                                store[i] = new Array(size);
                                for (var k = 0; k < size; k++) {
                                    store[i][k] = {};
                                    store[i][k].time = 0;
                                    store[i][k].blob = null;
                                    if (mid) {
                                        var tmp = (ix < 0) ? 0
                                                : (ix >= tl.length) ? tl.length - 1 : ix;
                                        store[i][k].time = tl[tmp];
                                        ix = ix + stride;
                                    }
                                }
                            }
                            // make process cue
                            var cuev = Math.floor(size / 2);
                            for (var i = 0; i < size; i++) {
                                cuev = ((i % 2) === 0) ? cuev + i : cuev - i;
                                cue[i] = cuev;
                            }
                            // start!
                            complete = false;
                            for (var i = 0; i < xhrs.length; i++) {
                                getTnumb(xhrs[i]);
                            }
                            // return frame info
                            return {size: size, stride: stride, hit: Math.ceil(size / 2)};
                        } catch (e) {
                            report("Exception at build " + e.toString())
                        }
                    },
                    getSize: function () {
                        return store.length;
                    },
                    getTime: function (n, angle) {
                        var a = (angle === undef) ? curAngle : angle;
                        return ((n >= 0) && (n < store.length)) ? store[a][n].time : 0;
                    },
                    getBlob: function (n, angle) { // return blob or null
                        var a = (angle === undef) ? curAngle : angle;
                        return ((n >= 0) && (n < store.length)) ? (store[a][n].blob) : null;
                    },
                    setProgressEvent: function (callback) {
                        // callback(processed-count, total)
                        progressNotify = callback;
                    }
                };
            })(CLIENT_SIZE);
            ////////////////
            // module interface
            return {
                // binder
                setSmartEngine: function (smartEngine) {
                    engine = smartEngine;
                    if (smartEngine.setStillPlayer) {
                        smartEngine.setStillPlayer(this);
                    }
                },
                // API
                eject: function () {
                    clear();
                    frameStack.clear();
                    stillFrame.clear();
                    thumbnail.clear();
                    cameras = null;
                    zoom = [0, 0, 1, 1];
                    caption = "";
                    curAngle = 0;
                },
                loadSource: function (src) {
                    try {
                        if (cameras !== null) {
                            this.eject();
                        }
                        if (src && Array.isArray(src)) { // && src[0][inc.SV_STILL]) {
                            cameras = src;
                            imageMan.init();
                            thumbnail.init();
                            stillFrame.init();
                            frameStack.init();
                        }
                    } catch (e) {
                        report("at StillPlayer.loadSource:" + e);
                    }
                },
                eraseImage: function () {
                    imageMan.abandon();
                    draw();
                },
                showThumbnail: function (angle, tindex) {
                    curAngle = checkAngle(angle) ? angle : curAngle;
                    imageMan.displayBlob(function (a) {
                        return thumbnail.getBlob(tindex, a);
                    });
                },
                showStill: function (angle, time) {
                    curAngle = (checkAngle(angle)) ? angle : curAngle;
//                    if (!imageMan.isMcvMode()) {
                    imageMan.displayURL(function (a) {
                        return stillFrame.getImageURL(time, a);
                    });
//                    } else {
//                        var t = stillFrame.getCloseTime(time, angle);
//                        imageMan.displayURL(function (a) {
//                            return inc.getThumnailSrcURL(cameras[a]) + t + IMAGE_EXT; 
//                        });
//                    }
                },
                getPosition: function (angle, time, drc) {
                    // drc -1:reverse 0:current 1:forward
                    drc = (drc === undef) ? 0 : drc;
                    if (checkAngle(angle)) {
                        var tl = stillFrame.getTimelist(angle);
                        if (tl !== null) {
                            var ix = util.getCloseIndex(tl, time);
                            ix = ix + drc;
                            ix = (ix < 0) ? 0 : ix;
                            ix = (ix >= tl.length) ? tl.length - 1 : ix;
                            time = tl[ix];
                        }
                        return time;
                    }
                },
                zoomArea: function (area, sync) { // area[l, t, r, b]
                    zoom = area;
                    zooming = !((zoom[0] === 0) && (zoom[1] === 0) && (zoom[2] === 1) && (zoom[3] === 1));
                    if (!sync) {
                        draw();
                    }
                },
                // event registration
                setThumbProgressEvent: function (callback) {
                    thumbnail.setProgressEvent(callback);
                },
                ////////
                // auxiliary functions
                resize: function (left, top, width, height) {
                    canvas.style.left = left + "px";
                    canvas.style.top = top + "px";
                    canvas.width = width;
                    canvas.height = height;
                    //
                    resetPlane(planeRatio);
                    draw();
                },
                setRenderSize: function (size) { // size[0]:width size[1]:height
                    resetPlane(size[0] / size[1]);
                },
                displayCaption: function (cap, dsc) {
                    caption = cap + " (" + dsc + ")";
                    draw();
                },
                enable: function () {
                    return cameras !== null;
                },
                // cache
                clearCache: function () {
                    stillFrame.clearCache();
                },
                setCacheStateEvent: function (callback) {
                    stillFrame.setCacheProgressEvent(callback);
                },
                cacheStill: function (time) {
                    stillFrame.cacheAllAngle(time);
                },
                showCachedStill: function (angle) {
                    curAngle = (checkAngle(angle)) ? angle : curAngle;
                    imageMan.displayBlob(function (a) {
                        return stillFrame.getCacheBlob(a);
                    });
                },
                // frame stack navigation
                setFrameStackEvent: function (callback) {
                    frameStack.setProgressEvent(callback);
                },
                clearFrameStack: function () {
                    frameStack.clear();
                },
                getFrameStackTime: function (number) {
                    return frameStack.getTime(number);
                },
                getFrameStackIndex: function (pos) {
                    return frameStack.getNumber(pos);
                },
                buildFrameStack: function (pos, size) {
                    return frameStack.build(pos, size); // return FrameStack info
                },
                showFrameStack: function (number) {
                    var n = (number !== undef) ? number : Math.ceil(frameStack.getSize() / 2);
                    imageMan.displayBlob(function (a) {
                        return frameStack.getBlob(n, a);
                    });
                },
                // multi camera view
                isMcviewing: function () {
                    return imageMan.isMcvMode();
                },
                setMcview: function (flag, pos) {
                    imageMan.setMcvMode(flag);
                    if (flag && (pos !== undef)) {
                        imageMan.displayBlob(function (a) {
                            return thumbnail.getBlob(thumbnail.getTimeIndex(pos), a);
                        });
                    }
                    draw();
                },
                setMcvProgressEvent: function (callback) {
                    imageMan.setProgressEvent(callback);
                },
                getMcviewHitNumber: function (state, x, y) {
                    return (state === inc.PS_DOWN) ? imageMan.getHitNumber(x, y) : -1;
                },
                getPictureDataURL: function () {
                    return canvas.toDataURL();
                },
                test: function () {
                },
                //
                setReportEvent: function (callback) {
                    reportNotify = callback;
                }
            };
        }
    };
});


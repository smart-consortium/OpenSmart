////////////////////////////////////////////////////////////////
//
//  Html5VideoDevice
//  
//  2017/02/26 0.2.3
//    add , "Utility" into define
//  
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

define(["Include", "Utility"], function (inc, util) {
    var setupPlayer;

    return {
        createVideoPlayer: function (core, glb, undef) {
            var HAVE_FUTURE_DATA = 3;
            var HAVE_ENOUGH_DATA = 4;
            var AFTER_SEEKED_MARGIN = 1000;
            var MAX_DUR = Math.pow(2, 32);
            var videoElement = core;
            var hls = null;
            var engine;
            var mouseNotify;
            var bitrateNotify;
            var reportNotify;
            var srcUrl = "";
            var loadedData = false;
            var durvalid = false;
            var mediaLoarded = false;
            //
            var transformProps = ['transform', 'WebkitTransform', 'MozTransform', 'msTransform', 'OTransform'];
            var transform = transformProps[0];
            var scrnX = 0, scrnY = 0, scrnW = 0, scrnH = 0;
            var asc = false;
            var scale = 0, centerX = 0, centerY = 0;
            //
            function report(s) {
                if (reportNotify) {
                    reportNotify("VideoDevice:" + s);
                }
            }
            function freeHlsObject() {
                if (hls) {
                    hls.destroy();
                    if (hls.bufferTimer) {
                        clearInterval(hls.bufferTimer);
                        hls.bufferTimer = undef;
                    }
                    hls = null;
                }
            }
            //
            for (var i = 0, j = transformProps.length; i < j; i++) {
                if (typeof videoElement.style[transformProps[i]] !== 'undefined') {
                    transform = transformProps[i];
                    break;
                }
            }
            //
            videoElement.addEventListener("error", function (e) {
                report("error:" + e.massage);
            }, false);
            videoElement.addEventListener("abort", function () {
                report("abort");
            }, false);
            videoElement.addEventListener('stalled', function () {
                report("stalled");
            });
            videoElement.addEventListener("emptied", function () {
                report("emptied");
            }, false);
            videoElement.addEventListener("loadstart", function () {
                report("loadstart");
            }, false);
            videoElement.addEventListener("loadedmetadata", function () {
                report("loadedmetadata");
            }, false);
            videoElement.addEventListener("loadeddata", function () {
                report("loadeddata");
                loadedData = true;
                checkLoadCompleted();
            }, false);
            videoElement.addEventListener("durationchange", function () {
                var dur = videoElement.duration;
                report("durationchange " + dur);
                durvalid = (dur > 0) && (dur < MAX_DUR);
                if (durvalid) {
                    checkLoadCompleted();
                }
            }, false);
            videoElement.addEventListener("ended", function () {
                if (engine) {
                    engine.mediaEnded();
                }
            }, false);
            // detect load completed
            function checkLoadCompleted() {
                if (engine && loadedData && durvalid && !mediaLoarded) {
                    engine.mediaOpened();
                    mediaLoarded = true;
                    videoElement.controls = false;
                }
            }
            // detect seek completed
            function seekCompleted(e) {
                var msg = e ? "waited" : "";
                report("seekComplet:" + videoElement.currentTime + " " + msg);
                if (e) {
                    videoElement.removeEventListener("canplaythrough", seekCompleted);
                }
                engine.seekCompleted();
            }
            videoElement.addEventListener("seeked", function () {
                if (videoElement.readyState >= HAVE_FUTURE_DATA) {
                    seekCompleted(null);
                } else {
                    videoElement.addEventListener("canplaythrough", seekCompleted);
                }
            }, false);
            ////////////////
            // module interface
            return {
                // binder
                setSmartEngine: function (smartEngine) {
                    engine = smartEngine;
                    if (smartEngine.setVideoPlayer) {
                        smartEngine.setVideoPlayer(this);
                    }
                },
                // API
                eject: function () {
                    this.zoomArea([0, 0, 1, 1]);
                    loadedData = false;
                    mediaLoarded = false;
                    durvalid = false;
                    if (hls) {
                        hls.detachMedia();
                        freeHlsObject();
                    }
                    ;
                },
                loadVideo: function (src) {
                    srcUrl = "";
                    if ([inc.SV_HLS] in src) {
                        srcUrl = src[inc.SV_HLS];
                        if (!videoElement.canPlayType("application/vnd.apple.mpegurl")) {
                            if (glb.Hls.isSupported()) {
                                freeHlsObject();
                                hls = new glb.Hls();
                                hls.attachMedia(core);
                                hls.on(glb.Hls.Events.MEDIA_ATTACHED, function () {
                                    hls.loadSource(srcUrl);
                                    report("LOAD_VIDEO <" + srcUrl + ">");
                                    hls.on(glb.Hls.Events.MANIFEST_PARSED, function () {
                                        report("MANIFEST_PARSED");
                                    });
                                });
                                hls.on(glb.Hls.Events.ERROR, function (event, data) {
                                    if (!data.fatal) {
//                                        report("ERROR " + JSON.stringify(data));
                                    } else {
                                        switch (data.type) {
                                            case glb.Hls.ErrorTypes.NETWORK_ERROR:
                                                // try to recover network error
                                                report("NETWORK_ERROR, try to recover");
                                                hls.startLoad();
                                                break;
                                            case glb.Hls.ErrorTypes.MEDIA_ERROR:
                                                report("MEDIA_ERROR, try to recover");
                                                hls.recoverMediaError();
                                                break;
                                            default:
                                                // cannot recover
                                                report("FATAL_ERROR " + JSON.stringify(data));
                                                this.eject();
                                                break;
                                        }
                                    }
                                });
                                return;
                            }
                        }
                    }
                    //
                    if (srcUrl === "") {
                        if ([inc.SV_DEFAULT] in src) {
                            srcUrl = src[inc.SV_DEFAULT];
                        } else {
                            return;
                        }
                    }
                    report("loadVideo <" + srcUrl + ">");
                    videoElement.src = srcUrl;
                },
                play: function () {
                    videoElement.play();
                },
                pause: function () {
                    videoElement.pause();
                },
                getVideoSize: function () {
                    return [videoElement.videoWidth, videoElement.videoHeight];
                },
                getDuration: function () {
                    return 1000 * videoElement.duration;
                },
                getPosition: function () {
                    return 1000 * videoElement.currentTime;
                },
                setPosition: function (pos) {
                    videoElement.currentTime = pos / 1000;
                },
                zoomArea: function (area, sync) {
                    var lx = 0, ly = 0, zs = 1;
                    //
                    if ((area[0] !== 0) || (area[1] !== 0) || (area[2] !== 1) || (area[3] !== 1)) {
                        if (scrnH === 0) {
                            return;
                        }
                        var sa = scrnW / scrnH;
                        var va = videoElement.videoWidth / videoElement.videoHeight;
                        // get left-top(rx,ry) and width-height(rw,rh) of rendered video
                        var rw = scrnW, rh = scrnH;
                        var rx = 0, ry = 0;
                        if (sa <= va) {
                            rh = scrnW / va;
                            ry = (scrnH - rh) / 2;
                        } else {
                            rw = va * scrnH;
                            rx = (scrnW - rw) / 2;
                        }
                        // get coordinate of area's center
                        var cx = rx + rw * (area[2] + area[0]) / 2;
                        var cy = ry + rh * (area[3] + area[1]) / 2;
                        // get location from area's center to screen's center
                        lx = scrnW / 2 - cx, ly = scrnH / 2 - cy;
                        // get zoom scale
                        var aw = rw * (area[2] - area[0]), ah = rh * (area[3] - area[1]);
                        var zs = 1;
                        if (sa <= (aw / ah)) {
                            zs = scrnW / aw;
                        } else {
                            zs = scrnH / ah;
                        }
                    }
                    //
                    videoElement.style[transform] = "scale(" + zs + ")";
                    videoElement.style.left = zs * lx + "px";
                    videoElement.style.top = zs * ly + "px";
                },
                setShutter: function (flag) {

                },
                // auxilialy functions
                getRenderSize: function () {
                    return [videoElement.videoWidth, videoElement.videoHeight];
                },
                resize: function (x, y, w, h) {
                    scrnX = x;
                    scrnY = y;
                    scrnW = w;
                    scrnH = h;
                },
                displayCaption: function (s) {

                },
                getContext: function () {
                    return util.getExtention(srcUrl) + ((hls) ? "/Hls.js" : "");
                },
                setVolume: function (vl) {
                    videoElement.volume = vl;
                },
                setBitrateEvent: function (callback) {
                    bitrateNotify = callback;
                },
                setMouseEvent: function (callback) {
                    mouseNotify = callback;
                },
                setReportEvent: function (callback) {
                    reportNotify = callback;
                },
                test: function () {                   
                }
            };
        },
        ////////
        // construct HTML5 VideoElement Player
        createCoreElement: function (parentID, glb, doc) {
            var html = "";
            if (util.isMobileDevice(glb)) {
                html = '<video id="PlayerCore" controls></video>';
            } else {
                html = '<video id="PlayerCore"></video>';
            }
            doc.getElementById(parentID).innerHTML = html;
            //
            var coreElement = doc.getElementById("PlayerCore");
            coreElement.style.left = 0;
            coreElement.style.top = 0;
            glb.setTimeout(function () {
                onPlayerCoreLoad(coreElement);
            }, 10);
        },
        setCoreCreateEvent: function (callback) {
            setupPlayer = callback;
        },
        loadEvent: function (sender, args) {
            if (setupPlayer) {
                setupPlayer(sender);
            }
        },
        errorEvent: function (sender, args) {

        }
    };
});
    
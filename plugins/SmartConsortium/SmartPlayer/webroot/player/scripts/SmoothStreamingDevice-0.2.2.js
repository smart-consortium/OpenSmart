////////////////////////////////////////////////////////////////
//  SmoothStreamingDevice
//  
//  2017/02/26 0.2.2
//    add , "Utility" into define
//  
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

define (["Include", "Utility"], function(inc, util) {
    var setupPlayer;
    
    return {
        createVideoPlayer: function(core, glb, undef) {
            var NAME_SPACE = "sspc_";
            var player = core;
            var engine;
            var mouseNotify;
            var bitrateNotify;
            var reportNotify;
            var srcUrl = "";
            
            var asc = false; // need seek completed report 
            var scale = 0, centerX = 0, centerY = 0;

            function report(s) {
                if (reportNotify) {
                    reportNotify("VideoDevice:" + s);
                }
            }
            //
            glb.onSmoothStreamingErrorOccurred = function (e) {
                alert(e);
            };
            player.setNameSpace(NAME_SPACE);    
            glb[NAME_SPACE + "MediaOpened"] = function () {
                if (!engine) { 
                    return;
                }
                engine.mediaOpened();
            };
            glb[NAME_SPACE + "MediaEnded"] = function () {
                if (!engine) {
                    return;
                }
                engine.mediaEnded(); 
            };
            glb[NAME_SPACE + "SeekCompleted"] = function () {
                if (!engine) {
                    return;
                }
                if (scale !== 0) {
                    player.setVideoZoom(scale, 0, centerX, centerY); 
                    scale = 0;
                }
                engine.seekCompleted();
            };
            glb[NAME_SPACE + "MouseEvent"] = function (mb, x, y) {
                if (!mouseNotify) {
                    return;
                }
                mouseNotify(mb, x, y); // mb= 0:down 1:move 2:up  x,y:normalized for RenderSize
            };
            glb[NAME_SPACE + "BitrateChanged"] = function (br) {
                if (!bitrateNotify) {
                    return;
                }
                bitrateNotify(br);
            };
            glb[NAME_SPACE + "SizeChanged"] = function (w, h) {
            };
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
                    player.eject();
                    this.zoomArea([0, 0, 1, 1]);
                },
                loadVideo: function (src) {
                    var ss = [inc.SV_SS] in src;
                    var def = [inc.SV_DEFAULT] in src;
                    srcUrl = (ss)? src[inc.SV_SS]: (def)? src[inc.SV_DEFAULT]: "";
                    asc = (ss)? false: (def)? true: false;
                    report("loadVideo <" + srcUrl + ">");
                    if (ss) {
                        player.ssLoad(srcUrl);
                    } else if (def) {
                        player.load(srcUrl);
                    }
                },
                play: function () {
                    player.play();
                },
                pause: function () {
                    player.pause();
                },
                getVideoSize: function () {
                    return player.getVideoSize();
                },
                getDuration: function () {
                    return player.getDuration();
                },
                getPosition: function () {
                    return player.getPosition();
                },
                setPosition: function (pos) {
                    player.setPosition(pos);
                    if (asc) {
                        glb.setTimeout(function () {
                            glb[NAME_SPACE + "SeekCompleted"]();
                        }, 500);
                    }
                },
                zoomArea: function (area, sync) {
                    scale = 1; centerX = 0, centerY = 0;
                    if ((area[0] !== 0) || (area[1] !== 0) || (area[2] !== 1) || (area[3] !== 1)) {
                        var ss = player.getScreenSize();
                        var rs = player.getRenderSize();
                        // convert area to render coordinate
                        var l = area[0] * rs[0];
                        var t = area[1] * rs[1];
                        var r = area[2] * rs[0];
                        var b = area[3] * rs[1];
                        // get area center
                        centerX = (l + r - rs[0]) / 2;
                        centerY = (t + b - rs[1]) / 2;
                        // get scale
                        scale = 0;
                        if ((ss[0] / ss[1]) >= ((r - l) / (b - t))) {
                            scale = ss[1] / (b - t);
                        } else {
                            scale = ss[0] / (r - l);
                        }
                    }
                    if (!sync) {
                        player.setVideoZoom(scale, 0, centerX, centerY);
                        scale = 0;
                    }
                },
                setShutter: function (flag) {
                    player.setShutter(flag);
                },
                // auxilialy functions
                getRenderSize: function () {
                    return player.getRenderSize();
                },
                resize: function (l, t, w, h) {

                },
                displayCaption: function (s) {
                    player.displayCaption(s);
                },
                getContext: function() {
                    return util.getExtention(srcUrl) + "/Silverlight";
                },
                setVolume: function (vl) {
                    player.setVolume(vl);
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
        // construct SmoothStreamingPlayer
        createCoreElement: function(parentID, glb, doc) {
            var html =
                '<object id="PlayerCore" data="data:application/x-silverlight-2," type="application/x-silverlight-2">"' +
                '<param name="source" value="assembly/SSPlayer_2_008.xap"/>' +
                '<param name="onLoad" value="onPlayerCoreLoad" />' +
                '<param name="onError" value="onPlayerCoreError" />' +
                '<param name="background" value="white" />' +
                '<param name="minRuntimeVersion" value="5.0.61118.0" />' +
                '<param name="autoUpgrade" value="true" />' +
                '<param name="windowless" value="true" />' +
                '<a href="http://go.microsoft.com/fwlink/?LinkID=149156&amp;v=5.0.61118.0" style="text-decoration:none">' +
                '<img src="http://go.microsoft.com/fwlink/?LinkId=161376" alt="Get Microsoft Silverlight" style="border-style:none"/>' +
                '</a>' +
                '</object>';
            doc.getElementById(parentID).innerHTML = html;
        },
        setCoreCreateEvent: function(callback) {
            setupPlayer = callback;
        },
        loadEvent: function(sender, args) {
            if (setupPlayer) {
                setupPlayer(sender.getHost().Content.SSPlayerCore);
            }
        },
        errorEvent: function(sender, args) {
            var appSource = "";
            if (sender !== null && sender !== 0) {
                appSource = sender.getHost().Source;
            }
            //
            var errorType = args.ErrorType;
            var iErrorCode = args.ErrorCode;
            //
            if (errorType === "ImageError" || errorType === "MediaError") {
                return;
            }
            //
            var errMsg = "Unhandled Error in Silverlight Application " + appSource + "\n";
            errMsg += "Code: " + iErrorCode + "    \n";
            errMsg += "Category: " + errorType + "       \n";
            errMsg += "Message: " + args.ErrorMessage + "     \n";
            if (errorType === "ParserError") {
                errMsg += "File: " + args.xamlFile + "     \n";
                errMsg += "Line: " + args.lineNumber + "     \n";
                errMsg += "Position: " + args.charPosition + "     \n";
            }
            else if (errorType === "RuntimeError") {
                if (args.lineNumber !== 0) {
                    errMsg += "Line: " + args.lineNumber + "     \n";
                    errMsg += "Position: " + args.charPosition + "     \n";
                }
                errMsg += "MethodName: " + args.methodName + "     \n";
            }
            alert(errMsg);
        //    throw new Error(errMsg);
        }
    };
});

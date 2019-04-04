////////////////////////////////////////////////////////////////
//
//  SmartPlayer
//  
//  2015/08/18
//  2015/09/22
//    add variable frame-stack-stride
//  2015/10/02
//    add "ejectSource()" in return of makeSmartPlayer function
//  2015/12/12
//    change argument of "ui.resize" in resizeScreen()
//  2016/02/10
//    remove "testbenchMode" flag, change "resizeScreen"
//  2016/06/04 0.3.0
//    modulize for RequireJS
//  2017/01/22
//    add ui.Controller.setFullScreenEvnet
//  2017/02/25 0.3.1
//    apply refactoring around messaging
//  2017/03/07 0.3.2
//    change ui.LCD.setDblClickEvent(function () { ...
//    remove "still.showStill( ... " at ui.LCD.setNavigateEvent
//  2017/03/19 0.3.3
//    change resize function for work-around to iPad
//  
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

define(["Include", "Utility", "SmartEngine", "StillPlayer", "UserInterface", "SVGPlane"],
        function (inc, util, smartengine, stillplayer, userinterface, svgplane) {
            //
            return {
                create: function (videoplayer, glb, doc, undef) {
                    var videoPlayerElement = doc.getElementById("VideoPlayer");
                    var playerCoreElement = doc.getElementById("PlayerCore");
                    var pointerNotify = null;
                    var fullScrnNotify = null;
                    var reportNotify = null;
                    var testbenchmode = false;
                    var windowWidth = glb.innerWidth;
                    var windowHeight = glb.innerHeight;
                    //
                    function report(s) {
                        if (reportNotify) {
                            reportNotify(s);
                        }
                    }
                    function resizeScreen() {
                        var uh = ui.getHeight();
                        var w = windowWidth;
                        var h = windowHeight - uh;
                        report("resizeScreen: w=" + w + " h=" + h)
                        var vl = 0, vt = 0;
                        var sl = testbenchmode ? 0.2 * w : 0;
                        var st = testbenchmode ? 0.2 * h : 0;
                        var sw = testbenchmode ? 0.8 * w : w;
                        var sh = testbenchmode ? 0.8 * h : h;
                        //
                        videoPlayerElement.style.height = sh + "px";
                        playerCoreElement.style.width = sw.toFixed(0) + "px";
                        playerCoreElement.style.height = sh.toFixed(0) + "px";
                        still.resize(sl, st, sw, sh);
                        svg.resize(sl, st, sw, sh);
                        video.resize(vl, vt, sw, sh);
                        ui.resize(vl, vt + h, w, uh);
                    }
                    function zoom(flag) {
                        var a = svg.getArea();
                        var f = flag && svg.isAreaValid();
                        svg.showArea(!f);
                        engine.zoomArea((f) ? [a[0], a[1], a[0] + a[2], a[1] + a[3]] : [0, 0, 1, 1]);
                        ui.Controller.setZoomState(f ? inc.SS_DOWNED : inc.SS_ENABLE);
                    }
                    function mcView(flag) {
                        if ((flag && !still.isMcviewing()) || !flag && still.isMcviewing()) {
                            if (flag) {
                                engine.operate(inc.OP_PAUSE);
                                zoom(false);
                            }
                            still.setMcview(flag, engine.getPosition());
                            svg.showArea(!flag);
                            ui.Controller.setMCViewState(flag ? inc.SS_DOWNED : inc.SS_ENABLE);
                            ui.Controller.setZoomState(flag ? inc.SS_DISABLE : inc.SS_ENABLE);
                        }
                    }
                    function subSeek(flag) {
                        if (flag) {
                            engine.operate(inc.OP_PAUSE);
                            var stride = Math.ceil(engine.getDuration() / 15000);
                            var pos = engine.getPosition();
                            ui.LCD.showNavi(still.buildFrameStack(pos, stride));
                        } else {
                            ui.LCD.hideNavi();
                        }
                    }
                    // create modules
                    var video = videoplayer;
                    var still = stillplayer.create("CanvasOverlay", glb, doc);
                    var ui = userinterface.create("SmartPlayerUI", glb, doc);
                    var svg = svgplane.create("SvgOverlay", glb, doc);
                    var engine = smartengine.create(glb);
                    //
                    var startTime = 0;
                    var needPoster = false;
                    // bind player video player to smart-engine
                    video.setSmartEngine(engine);
                    still.setSmartEngine(engine);
                    // linkup event handlers
                    engine.setEjectEvent(function () {
                        svg.clear();
                        ui.clear();
                    });
                    engine.setMediaOpenEvent(function () {
                        video.setVolume(ui.Controller.getVolume());
                        still.displayCaption(engine.getCaption(), video.getContext());
                        ui.setMode((!engine.getMcvReady() && (engine.getAngleCount() > 1)) ? 2 : 1);
                        ui.Selector.setItemList(engine.getAngleList());
                        ui.LCD.setDuration(engine.getDuration());
                        needPoster = video.getContext() === "";
                        if (startTime > 0) {
                            engine.setPosition(startTime);
                        }
                        //
                        var lmt = 50;
                        var retrieveRenderSize = function () {
                            var rsz = video.getRenderSize();
                            if (rsz[1] === 0) {
                                lmt--;
                                if (lmt < 0) {
                                    alert("cann't retrieve video RenderSize");
                                } else {
                                    glb.setTimeout(retrieveRenderSize, 100);
                                }
                            } else {
                                still.setRenderSize(rsz);
                                svg.setRenderSize(rsz);
                                resizeScreen();
                            }
                        };
                        retrieveRenderSize();
                    });
                    engine.setPermissionEvent(function (permissions) {
                        ui.Controller.setPermission(permissions);
                    });
                    engine.setOperationEvent(function (code) {
                        ui.Controller.setToggleButton(code);
                        ui.Selector.setButtonState(0, inc.isTrickOp(code) ? 0 : 1);
                        if (code !== inc.OP_PAUSE) {
                            still.clearCache();
                            still.clearFrameStack();
                            subSeek(false);
                        }
                    });
                    engine.setPreOperationEvent(function (code) {
                        if (!inc.isStepOp(code) && (code !== inc.OP_PAUSE)) {
                            mcView(false);
                        }
                    });
                    engine.setTimeEvent(function (timeObject) {
                        ui.LCD.setTimeObject(timeObject);
                    });
                    ui.LCD.setSeekEvent(function (state, seekRatio) {
                        if (state === inc.PS_DOWN) {
                            still.clearCache();
                        } else if (state === inc.PS_MOVE) {
                        } else if (state === inc.PS_UP) {
                            ui.Selector.setButtonState(0, (engine.isPlaying()) ? 0 : 1);
                        }
                        return engine.setSeek(state, seekRatio);
                    });
                    ui.Controller.setButtonEvent(function (code) {
                        engine.operate(code);
                    });
                    ui.Controller.setVolumeEvent(function (level) {
                        video.setVolume(level);
                    });
                    ui.Selector.setSelectorEvent(function (state, angleIndex) {
                        engine.setAngle(state, angleIndex);
                        still.showCachedStill(angleIndex);
                        ui.LCD.hideNavi();
                    });
                    ui.Selector.setButtonEvent(function (state) {
                        if (state) {
                            still.cacheStill(engine.getPosition());
                        } else {
                            still.clearCache();
                        }
                    });
                    still.setThumbProgressEvent(function (prg, sz) {
                        ui.LCD.setProgress(prg, sz);
                        if (needPoster && (prg > 0)) {
                            needPoster = false;
                            still.showThumbnail(0, 0);
                        }
                    });
                    still.setMcvProgressEvent(function (prg, sz) {
                        ui.LCD.setProgress2(prg, sz);
                    });
                    svg.setMouseEvent(function (state, x, y) {
                        var n = still.getMcviewHitNumber(state, x, y);
                        if (n >= 0) {
                            engine.setAngle(inc.PS_UP, n);
                            mcView(false);
                        }
                        if (pointerNotify) {
                            pointerNotify(state, x, y);
                        }
                    });
                    video.setBitrateEvent(function (bitRate) {
                        ui.LCD.setBitrate(bitRate);
                    });
                    still.setCacheStateEvent(function (prg) {
                        ui.Selector.setButtonState(prg);
                    });
                    still.setFrameStackEvent(function (n) {
                        ui.LCD.setNaviProgress(n);
                    });
                    ui.LCD.setNavigateEvent(function (state, n) {
                        still.showFrameStack(n);
                        var pos = still.getFrameStackTime(n);
                        ui.LCD.setTime(pos);
                        if (state === inc.PS_UP) {
                            engine.setPosition(pos);
                        }
                    });
                    ui.LCD.setDblClickEvent(function () {
                        if (still.enable()) {
                            subSeek(true);
                        }
                    });
                    ui.Controller.setMCViewEvent(function () {
                        mcView(inc.SS_HITTED !== ui.Controller.getMCViewState());
                    });
                    ui.Controller.setZoomEvnet(function () {
                        zoom(inc.SS_HITTED !== ui.Controller.getZoomState());
                    });
                    ui.Controller.setFullScreenEvnet(function () {
                        if (fullScrnNotify) {
                            fullScrnNotify();
                        }
                    });
                    engine.setReportEvent(function (s) {
                        report(s);
                    });
                    video.setReportEvent(function (s) {
                        report(s);
                    });
                    still.setReportEvent(function (s) {
                        report(s);
                    });
                    ui.setReportEvent(function (s) {
                        report(s);
                    });
                    //
                    return {
                        resize: function (w, h) {
                            windowWidth = w;
                            windowHeight = h;
                            resizeScreen();
                        },
                        ejectSource: function () {
                            engine.eject();
                            ui.clear();
                        },
                        loadSource: function (src, apf, st) {
                            if (st) {
                                startTime = st;
                            }
                            engine.loadSource(src, apf);
                        },
                        operate: function (code) {
                            engine.operate(code);
                        },
                        getPosition: function () {
                            return engine.getPosition();
                        },
                        setPosition: function (pos) {
                            engine.setPosition(pos);
                        },
                        setZoom: function (f) {
                            zoom(f);
                        },
                        getArea: function () {
                            return svg.getArea();
                        },
                        setArea: function (area) {
                            svg.setArea(area);
                        },
                        getStillPicture: function () {
                            return still.getPictureDataURL();
                        },
                        setPointerEvent: function (callback) {
                            pointerNotify = callback;
                        },
                        setFullScreenEvent: function (callback) {
                            fullScrnNotify = callback;
                        },
                        setReportEvent: function (callback) {
                            reportNotify = callback;
                        },
                        setTestbenchMode: function (f) {
                            testbenchmode = f;
                            svg.showMarker(f);
                            resizeScreen();
                        },
                        test: function () {
                        }
                    };
                }
            };
        });

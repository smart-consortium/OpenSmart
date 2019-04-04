////////////////////////////////////////////////////////////////
//
//  UserInterface
//
//  2017/01/30 0.4.0
//    add Touch-Event, change handler name mouse* -> point*
//  2017/02/26 0.4.1
//    correct ui height management
//  2017/03/07 0.4.2
//    change LCD.showNavi
//  2017/03/18 0.4.3
//  
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

define(["Include", "Utility"], function (inc, util) {
    return {
        create: function (ownerID, glb, doc, undef) {
            var DEFAULT_HEIGHT = 28;
            var CONTROLLER_WIDTH = 334;
            var TOP_MARGIN = 1;
            var LONG_SPACE = 5;
            var SHORT_SPACE = 1;
            var DIM_GRAY = "#727272";
            var LCD_BACKGROUND = "khaki";
            var DBLCLICK_INTERVAL = 400;

            var box = doc.getElementById(ownerID);
            var reportNotify;
            var uiMode = 0;
            var isMobile = util.isMobileDevice(glb);
            var startPointEN = (isMobile) ? "touchstart" : "mousedown";
            var movePointEN = (isMobile) ? "touchmove" : "mousemove";
            var endPointEN = (isMobile) ? "touchend" : "mouseup";

            function report(s) {
                if (reportNotify) {
                    reportNotify("UI:" + s);
                }
            }
            ////////////////
            // Panel factory
            function createPanel(h) {
                function Panel(h) {
                    height = h;
                    this.canvas = doc.createElement("canvas");
                    this.showing = false;
                }
                function getPointParam(e) {
                    var x = (isMobile) ? e.touches[0].clientX : e.clientX;
                    var y = (isMobile) ? e.touches[0].clientY : e.clientY;
                    return {"clientX": x, "clientY": y};
                }
                function getEndPointParam(e) {
                    var x = (isMobile) ? e.changedTouches[0].clientX : e.clientX;
                    var y = (isMobile) ? e.changedTouches[0].clientY : e.clientY;
                    return {"clientX": x, "clientY": y};
                }
                //
                var context;
                var height = 0;
                var pointCaptured = false;
                var pointDown = function (e) {
                    e.preventDefault();
                    if (pointCaptured) {
                        pointUp(e);
                    }
                    if (e.target && e.target.setCapture) {
                        e.target.setCapture();
                    }
                    pointCaptured = true;
                    if (!isMobile && (e.button !== 0)) {
                        pointCaptured = false;
                        return;
                    }
                    doc.addEventListener(movePointEN, pointMove, true);
                    doc.addEventListener(endPointEN, pointUp, true);
                    //
                    context.pDown(getPointParam(e));
                };
                var pointMove = function (e) {
                    if (!pointCaptured) {
                        return;
                    }
                    //
                    context.pMove(getPointParam(e));
                };
                var pointUp = function (e) {
                    if (!pointCaptured) {
                        return;
                    }
                    pointCaptured = false;
                    if (e.target && e.target.releaseCapture) {
                        e.target.releaseCapture();
                    }
                    doc.removeEventListener(movePointEN, pointMove, true);
                    doc.removeEventListener(endPointEN, pointUp, true);
                    //
                    context.pUp(getEndPointParam(e));
                };
                Panel.prototype.setContext = function (ctx) {
                    context = ctx;
                    context.setCanvas(this.canvas);
                };
                Panel.prototype.show = function (f) {
                    if (!context) {
                        return;
                    }
                    if (!f) {
                        if (this.showing) {
                            this.canvas.removeEventListener(startPointEN, pointDown, false);
                            box.removeChild(this.canvas);
                        }
                        this.showing = false;
                    } else {
                        box.appendChild(this.canvas);
                        this.canvas.style.border = "none"; // 1px solid";
                        this.canvas.style.margin = "none";
                        this.canvas.style.padding = "none";
                        this.canvas.height = height;
                        this.canvas.addEventListener(startPointEN, pointDown, false);
                        this.showing = true;
                    }
                };
                //
                return new Panel(h);
            }

            var selectorPanel = createPanel(DEFAULT_HEIGHT - 6);
            var controllerPanel = createPanel(DEFAULT_HEIGHT);
            var lcdPanel = createPanel(DEFAULT_HEIGHT);

            ////////////////
            // Selector Context
            var selectorContext = (function () {
                var LCD_FONT = "bold 12px 'Arial'";
                var TEXT_BASE = 20;
                var NAMEBOX_WIDTH = 120;
                var BUTTON_WIDTH = 40;
                var TAB_FONT = "bold 11px 'Arial'";
                var TAB_WIDTH = 20;
                var TAB_ENABLE_COLOR = "darkkhaki";
                var TAB_ACTIVE_COLOR = "gold";

                var _cvs;
                var _bcr;
                var selectorNotify;
                var buttonNotify;

                var btn = {"l": 0, "r": 0, "state": 0, "prg": 0};
                var tabs = [];
                var curIndex = 0;
                var lcdText = "";

                function _draw() {
                    var ctx = _cvs.getContext("2d");
                    var x = SHORT_SPACE;
                    var y = 2;
                    var h = DEFAULT_HEIGHT - 4;
                    // drwa button
                    btn.l = x;
                    btn.r = x + BUTTON_WIDTH;
                    if (btn.state === inc.SS_ENABLE) {
                        ctx.fillStyle = "darkkhaki";
                    } else if (btn.state === inc.SS_HITTED) {
                        ctx.fillStyle = "gold";
                    } else {
                        ctx.fillStyle = "gray";
                    }
                    ctx.fillRect(x, y, BUTTON_WIDTH, h);
                    ctx.fillStyle = "lightgreen";
                    ctx.fillRect(x, y, btn.prg * BUTTON_WIDTH, h);
                    ctx.strokeStyle = "gray";
                    ctx.strokeRect(x, y, BUTTON_WIDTH, h);
                    //
                    ctx.font = LCD_FONT;
                    ctx.fillStyle = DIM_GRAY;
                    ctx.fillText("S.C", x + 8, TEXT_BASE);
                    x = x + BUTTON_WIDTH + LONG_SPACE;
                    // draw name-box
                    ctx.fillStyle = LCD_BACKGROUND;
                    ctx.fillRect(x, y, NAMEBOX_WIDTH, h);
                    ctx.strokeStyle = "gray";
                    ctx.strokeRect(x, y, NAMEBOX_WIDTH, h);
                    // draw text
                    ctx.font = LCD_FONT;
                    ctx.fillStyle = DIM_GRAY;
                    ctx.fillText(lcdText, x + 8, TEXT_BASE);
                    x = x + NAMEBOX_WIDTH + LONG_SPACE;
                    // draw select tab
                    ctx.font = TAB_FONT;
                    for (var i = 0; i < tabs.length; i++) {
                        if (i === curIndex) {
                            ctx.fillStyle = TAB_ACTIVE_COLOR;
                        } else {
                            ctx.fillStyle = TAB_ENABLE_COLOR;
                        }
                        ctx.fillRect(x, y, TAB_WIDTH, h);
                        ctx.strokeRect(x, y, TAB_WIDTH, h);
                        //
                        ctx.fillStyle = DIM_GRAY;
                        ctx.fillText(i + 1, x + 5, y + 14);
                        //
                        tabs[i].rngL = x;
                        tabs[i].rngR = x + TAB_WIDTH;
                        x = tabs[i].rngR + SHORT_SPACE;
                    }
                }
                function testHit(x) {
                    for (var i = 0; i < tabs.length; i++) {
                        if ((tabs[i].rngL <= x) && (tabs[i].rngR >= x)) {
                            return i;
                        }
                    }
                    return -1;
                }
                function processHit(state, selhit, btnhit) {
                    if ((btn.state > inc.SS_DISABLE) && btnhit && buttonNotify) {
                        btn.state = (btn.state === inc.SS_ENABLE) ? inc.SS_HITTED : inc.SS_ENABLE;
                        buttonNotify(btn.state === inc.SS_HITTED);
                    }
                    if (selhit >= 0) {
                        curIndex = selhit;
                        if (selectorNotify) {
                            selectorNotify(state, curIndex);
                        }
                        lcdText = tabs[curIndex].name;
                    }
                    if (btnhit || (selhit >= 0)) {
                        _draw();
                    }
                }
                ////////////////
                // module interface
                return {
                    setCanvas: function (cvs) {
                        _cvs = cvs;
                    },
                    pDown: function (e) {
                        _bcr = _cvs.getBoundingClientRect();
                        var x = e.clientX - _bcr.left;
                        // check button hit
                        processHit(0, testHit(x), (btn.l < x) && (x < btn.r));
                    },
                    pMove: function (e) {
                        var hit = testHit(e.clientX - _bcr.left);
                        if (hit === curIndex) {
                            return;
                        }
                        processHit(1, hit, false);
                    },
                    pHover: function (e) {
                    },
                    pOut: function (e) {
                    },
                    pUp: function (e) {
                        processHit(2, testHit(e.clientX - _bcr.left), false);
                    },
                    // API
                    draw: function () {
                        _draw();
                    },
                    clear: function () {
                        tabs = [];
                        curIndex = 0;
                        _draw();
                    },
                    setItemList: function (list) {
                        tabs.splice(0, tabs.length);
                        for (var i = 0; i < list.length; i++) {
                            tabs.push({"name": ""});
                            tabs[i].name = list[i];
                        }
                        if (tabs.length > 0) {
                            lcdText = tabs[0].name;
                        }
                        _draw();
                    },
                    setButtonState: function (prg, state) {
                        btn.prg = prg;
                        if (state !== undef) {
                            btn.state = state;
                        }
                        _draw();
                    },
                    getNumber: function () {
                        return curIndex;
                    },
                    // event registration
                    setSelectorEvent: function (callback) {
                        selectorNotify = callback;
                    },
                    setButtonEvent: function (callback) {
                        buttonNotify = callback;
                    }
                };
            })();

            ////////////////
            // Controller Context
            var controllerContext = (function () {
                var BUTTON_HEIGHT = DEFAULT_HEIGHT - 4;
                var BUTTON_WIDTH = 24;
                var BTNCAP_OFFSET_X = 8;
                var BTNCAP_OFFSET_Y = 16;
                var VOLUME_WIDTH = 12;

                var BUTTON_DISENABLE_COLOR = "lightgray";
                var BUTTON_ENABLE_COLOR = "darkkhaki";
                var BUTTON_ACTIVE_COLOR = "gold";
                var TEXT_FONT = "bold 14px 'Arial'";
                // [OP_STOP, OP_SLOWRVS, OP_RVS, OP_FWD, OP_SLOWFWD, OP_STEPRVS, OP_STEPFWD, OP_CHECK, OP_REPEAT, OP_MCVIEW, OP_ZOOM, OP_FULLSCRN]
                var BTN_CAPS = ["_", "<", "<", ">", ">", "-", "+", "v", "!", "M", "#", "::"];

                var _cvs; // cached canvas
                var _bcr; // cached BoundingClientRect
                var buttonEvent;
                var subSeekNotify;
                var mcViewNotify;
                var zoomNotify;
                var fullScrnNotify;
                var volumeNotify;

                var btns = [];
                var level = 0.4;
                var volL = 0, volR = 0;
                var volCapped = false;
                var btnHit = -1;

                for (var i = 0; i < BTN_CAPS.length; i++) {
                    btns.push({
                        status: inc.SS_DISABLE,
                        rngR: 0,
                        rngL: 0
                    });
                }
                function _clear() {
                    for (var i = 0; i < btns.length; i++) {
                        btns[i].status = inc.SS_DISABLE;
                    }
                    btns[inc.OP_MCVIEW].status = inc.SS_DISABLE;
                    btns[inc.OP_ZOOM].status = inc.SS_ENABLE;
                    btns[inc.OP_FULLSCRN].status = inc.SS_ENABLE;
                }
                function _draw() {
                    var ctx = _cvs.getContext("2d");
                    //
                    var x = SHORT_SPACE;
                    var y = 2;
                    var h = BUTTON_HEIGHT;
                    ctx.font = TEXT_FONT;
                    ctx.strokeStyle = "gray";
                    // draw button array
                    for (var i = 0; i < btns.length; i++) {
                        switch (btns[i].status) {
                            case inc.SS_DISABLE:
                                ctx.fillStyle = BUTTON_DISENABLE_COLOR;
                                break;
                            case inc.SS_ENABLE:
                                if (i === btnHit) {
                                    ctx.fillStyle = BUTTON_ACTIVE_COLOR;
                                    glb.setTimeout(function () {
                                        _draw();
                                    }, 100);
                                } else {
                                    ctx.fillStyle = BUTTON_ENABLE_COLOR;
                                }
                                break;
                            case inc.SS_DOWNED:
                                ctx.fillStyle = BUTTON_ACTIVE_COLOR;
                                break;
                            default:
                        }
                        ctx.fillRect(x, y, BUTTON_WIDTH, h);
                        ctx.strokeRect(x, y, BUTTON_WIDTH, h);
                        //
                        ctx.fillStyle = DIM_GRAY;
                        ctx.fillText(BTN_CAPS[i], x + BTNCAP_OFFSET_X, y + BTNCAP_OFFSET_Y);
                        //
                        btns[i].rngL = x;
                        btns[i].rngR = x + BUTTON_WIDTH;
                        if ((i === inc.OP_STOP) || (i === inc.OP_SLOWFWD) || (i === inc.OP_STEPFWD) || (i === inc.OP_REPEAT)) {
                            x = x + LONG_SPACE + BUTTON_WIDTH;
                        } else {
                            x = x + SHORT_SPACE + BUTTON_WIDTH;
                        }
                    }
                    btnHit = -1;
                    x = x + LONG_SPACE;
                    // draw volume
                    volL = x;
                    ctx.fillStyle = BUTTON_ENABLE_COLOR;
                    ctx.fillRect(x, y, VOLUME_WIDTH, h);
                    //
                    ctx.fillStyle = BUTTON_ACTIVE_COLOR;
                    var vh = level * h;
                    var vt = h - vh + 2;
                    ctx.fillRect(x, vt, VOLUME_WIDTH, vh);
                    volR = x + VOLUME_WIDTH;
                }
                function volumeLevel(h) {
                    var v = (BUTTON_HEIGHT - h + 1) / BUTTON_HEIGHT;
                    level = (v < 0) ? 0 : ((v > 1) ? 1 : v);
                    if (volumeNotify) {
                        volumeNotify(level);
                    }
                }
                _clear();
                ////////////////
                // module interface
                return {
                    setCanvas: function (cvs) {
                        _cvs = cvs;
                    },
                    pDown: function (e) {
                        _bcr = _cvs.getBoundingClientRect();
                        var x = e.clientX - _bcr.left;
                        volCapped = ((x >= volL) && (x <= volR));
                        if (volCapped) {
                            volumeLevel(e.clientY - _bcr.top);
                        } else {
                            // get hit
                            var hit = -1;
                            for (var i = 0; i < btns.length; i++) {
                                if ((btns[i].rngL <= x) && (btns[i].rngR >= x)) {
                                    hit = i;
                                    break;
                                }
                            }
                            if (btns[hit].status === inc.SS_DISABLE) {
                                btnHit = -1;
                                return;
                            }
                            // check toggle button groupe
                            // exclude "check" & "repert" & "ext*op_" button
                            if (hit < inc.OP_CHECK) {
                                for (var i = inc.OP_SLOWRVS; i <= inc.OP_SLOWFWD; i++) {
                                    if (btns[i].status !== inc.SS_DISABLE) {
                                        if (i !== hit) {
                                            btns[i].status = inc.SS_ENABLE;
                                        } else {
                                            if (btns[i].status === inc.SS_ENABLE) {
                                                btns[i].status = inc.SS_DOWNED;
                                            } else {
                                                btns[i].status = inc.SS_ENABLE;
                                                hit = -1;
                                            }
                                        }
                                    }
                                }
                            }
                            //
                            if (hit < inc.OP_EXT) {
                                buttonEvent(hit);
                                btnHit = hit;
                            } else if (hit === inc.OP_MCVIEW) {
                                if (mcViewNotify) {
                                    mcViewNotify();
                                }
                            } else if (hit === inc.OP_ZOOM) {
                                if (zoomNotify) {
                                    zoomNotify();
                                }
                            } else if (hit === inc.OP_FULLSCRN) {
                                if (fullScrnNotify) {
                                    fullScrnNotify();
                                }
                            }
                        }
                        _draw();
                    },
                    pMove: function (e) {
                        if (volCapped) {
                            volumeLevel(e.clientY - _bcr.top);
                            _draw();
                        }
                    },
                    pUp: function (e) {
                    },
                    // API
                    draw: function () {
                        _draw();
                    },
                    clear: function () {
                        _clear();
                        _draw();
                    },
                    setPermission: function (enables) {
                        for (var i = 0; i < enables.length; i++) {
                            if (i < btns.length) {
                                if (btns[i].status !== inc.SS_DOWNED) {
                                    btns[i].status = enables[i] ? inc.SS_ENABLE : inc.SS_DISABLE;
                                }
                            }
                        }
                        _draw();
                    },
                    setToggleButton: function (bn) {
                        for (var i = inc.OP_SLOWRVS; i <= inc.OP_SLOWFWD; i++) {
                            if (i === bn) {
                                btns[i].status = inc.SS_DOWNED;
                            } else if (inc.isForcePauseOp(bn)) {
                                if (btns[i].status === inc.SS_DOWNED) {
                                    btns[i].status = inc.SS_ENABLE;
                                }
                            }
                        }
                        _draw();
                    },
                    getMCViewState: function () {
                        return btns[inc.OP_MCVIEW].status;
                    },
                    setMCViewState: function (state) {
                        if (btns[inc.OP_MCVIEW].status !== inc.SS_DISABLE) {
                            btns[inc.OP_MCVIEW].status = state;
                            _draw();
                        }
                    },
                    getZoomState: function () {
                        return btns[inc.OP_ZOOM].status;
                    },
                    setZoomState: function (state) {
                        btns[inc.OP_ZOOM].status = state;
                        _draw();
                    },
                    getFullScreenState: function () {
                        return (btns[inc.OP_FULLSCRN].status === inc.SS_DOWNED);
                    },
                    setFullScreenState: function (f) {
                        btns[inc.OP_FULLSCRN].status = (f) ? inc.SS_DOWNED : inc.SS_ENABLE;
                        _draw();
                    },
                    getVolume: function () {
                        return level;
                    },
                    // event registration
                    setButtonEvent: function (callback) {
                        buttonEvent = callback;
                    },
                    setSubseekEvent: function (callback) {
                        subSeekNotify = callback;
                    },
                    setMCViewEvent: function (callback) {
                        mcViewNotify = callback;
                    },
                    setZoomEvnet: function (callback) {
                        zoomNotify = callback;
                    },
                    setFullScreenEvnet: function (callback) {
                        fullScrnNotify = callback;
                    },
                    setVolumeEvent: function (callback) {
                        volumeNotify = callback;
                    }
                };
            })();

            ////////////////
            // LCD Context
            var lcdContext = (function () {
                var LCD_FONT = "bold 12px 'Arial'";
                var TEXT_BASE = 22;
                var TEXT_1_X = 130;
                var TEXT_2_X = 186;
                var TEXT_3_X = 250;
                var SEEKBAR_WIDTH = 4;
                var SEEKBAR_TOP = 6;

                var _cvs;
                var _bcr;
                var seekNotify;
                var naviNotify;
                var clickNotify;

                var duration = 0;
                var time = 0;
                var seekRatio = 0;
                var timeText = "";
                var text1 = "";
                var text2 = "";
                var text3 = "";
                var naviSeeking = false;
                var naviValid = null;
                var naviSize = 0;
                var naviHit = 0;
                var backgroundColor = LCD_BACKGROUND;
                var doublClicked = false;

                function _draw() {
                    var ctx = _cvs.getContext("2d");
                    var x = 0;
                    var y = 0;
                    var h = DEFAULT_HEIGHT;
                    var w = _cvs.width - 2;
                    // draw lcd
                    ctx.fillStyle = backgroundColor;
                    ctx.fillRect(x, y, w, h);
                    ctx.strokeStyle = "gray";
                    ctx.strokeRect(x, y, w, h);
                    //
                    ctx.setLineDash([0]);
                    ctx.lineWidth = SEEKBAR_WIDTH;
                    if (naviSize === 0) {
                        // draw seekbar
                        ctx.strokeStyle = DIM_GRAY;
                        ctx.beginPath();
                        ctx.moveTo(x, SEEKBAR_TOP);
                        ctx.lineTo(x + seekRatio * w, SEEKBAR_TOP);
                        ctx.stroke();
                    } else {
                        // draw navi
                        var nbX = x + 2;
                        var nbW = (w - 2) / naviSize;
                        var col = "";
                        for (var i = 0; i < naviSize; i++) {
                            col = naviValid[i] ? "darkgray" : "lightgray";
                            ctx.strokeStyle = (i === naviHit) ? "dimgray" : col;
                            ctx.beginPath();
                            ctx.moveTo(nbX, SEEKBAR_TOP);
                            ctx.lineTo(nbX + nbW - 2, SEEKBAR_TOP);
                            ctx.stroke();
                            nbX = nbX + nbW;
                        }
                    }
                    // draw text
                    if (duration === 0) {
                        timeText = "";
                    } else {
                        timeText = (time / 1000).toFixed(3) + " / " + (duration / 1000).toFixed(3);
                    }
                    x = x + 8;
                    ctx.font = LCD_FONT;
                    ctx.fillStyle = DIM_GRAY;
                    ctx.fillText(timeText, x, TEXT_BASE);
                    // draw text
                    ctx.font = LCD_FONT;
                    ctx.fillStyle = DIM_GRAY;
                    ctx.fillText(text1, TEXT_1_X, TEXT_BASE);
                    ctx.fillText(text2, TEXT_2_X, TEXT_BASE);
                    ctx.fillText(text3, TEXT_3_X, TEXT_BASE);
                }
                function _setTime(timeObj) {
                    if (!timeObj) {
                        return;
                    }
                    time = timeObj.time;
                    if (timeObj.ratio !== undef) {
                        seekRatio = timeObj.ratio;
                    }
                    _draw();
                }
                function _getHitIndex(ratio) {
                    var nh = Math.floor(ratio * naviSize);
                    return (nh >= naviSize) ? naviSize - 1 : nh;
                }
                ////////////////
                // module interface
                return {
                    setCanvas: function (cvs) {
                        _cvs = cvs;
                    },
                    pDown: function (e) {
                        // check double-click
                        if (doublClicked) {
                            if (clickNotify) {
                                clickNotify();
                            }
                            doublClicked = false;
                            return;
                        }
                        doublClicked = true;
                        glb.setTimeout(function () {
                            doublClicked = false;
                        }, DBLCLICK_INTERVAL);
                        //
                        _bcr = _cvs.getBoundingClientRect();
                        var mbr = (e.clientX - _bcr.left) / _bcr.width;
                        mbr = (mbr < 0) ? 0 : (mbr >= 1) ? 1 : mbr;
                        if (naviSize > 0) {
                            naviSeeking = true;
                            naviHit = _getHitIndex(mbr);
                            if (naviHit >= 0) {
                                if (naviNotify) {
                                    naviNotify(inc.PS_DOWN, naviHit);
                                }
                            }
                        } else {
                            seekRatio = mbr;
                            if (seekNotify) {
                                _setTime(seekNotify(inc.PS_DOWN, seekRatio));
                            }
                        }
                        _draw();
                    },
                    pMove: function (e) {
                        var mbr = (e.clientX - _bcr.left) / _bcr.width;
                        mbr = (mbr < 0) ? 0 : (mbr >= 1) ? 1 : mbr;
                        if (naviSize > 0) {
                            var nh = _getHitIndex(mbr);
                            if (nh !== naviHit) {
                                naviHit = nh;
                                if (naviHit >= 0) {
                                    if (naviNotify) {
                                        naviNotify(inc.PS_MOVE, naviHit);
                                    }
                                }
                            }
                        } else {
                            seekRatio = mbr;
                            if (seekNotify) {
                                _setTime(seekNotify(inc.PS_MOVE, seekRatio));
                            }
                        }
                        _draw();
                    },
                    pUp: function (e) {
                        var mbr = (e.clientX - _bcr.left) / _bcr.width;
                        mbr = (mbr < 0) ? 0 : (mbr >= 1) ? 1 : mbr;
                        if (naviSize > 0) {
                            naviSeeking = false;
                            if (naviNotify) {
                                naviNotify(inc.PS_UP, naviHit);
                            }
                        } else {
                            seekRatio = mbr;
                            if (seekNotify) {
                                _setTime(seekNotify(inc.PS_UP, seekRatio));
                            }
                        }
                        _draw();
                    },
                    // API
                    draw: function () {
                        _draw();
                    },
                    clear: function () {
                        duration = 0;
                        time = 0;
                        seekRatio = 0;
                        timeText = "";
                        text1 = "";
                        text2 = "";
                        text3 = "";
                        naviSize = 0;
                    },
                    setDuration: function (dur) {
                        duration = dur;
                    },
                    getTime: function () {
                        return time;
                    },
                    setTime: function (pos) {
                        time = pos;
                    },
                    setTimeObject: function (timeObj) {
                        if (naviSeeking) {
                            return;
                        }
                        _setTime(timeObj);
                    },
                    setNaviProgress: function (n) {
                        if ((n < 0) || (n >= naviSize)) {
                            return;
                        }
                        naviValid[n] = true;
                        _draw();
                    },
                    setNaviHit: function (index) {
                        if (index >= naviSize) {
                            return;
                        }
                        naviHit = index;
                        _draw();
                    },
                    hideNavi: function () {
                        naviSize = 0;
                        text2 = "";
                        naviValid = null;
                        _draw();
                    },
                    showNavi: function (info) { // info {size:Number, stride:Number, hit:Boolean}
                        naviSize = info.size;
                        naviValid = new Array(naviSize);
                        for (var i = 0; i < naviSize; i++) {
                            naviValid[i] = false;
                        }
                        text2 = (!(info.stride) || (info.stride === 0)) ? ""
                                : "stride=" + info.stride;
                        if (info.hit) {
                            this.setNaviHit(info.hit);
                        }
                        _draw();
                    },
                    setBitrate: function (rate) {
                        text3 = (rate / 1000).toFixed(0) + " kbps";
                        _draw();
                    },
                    setProgress: function (prg, sz) {
                        text1 = prg + "/" + sz;
                        _draw();
                    },
                    setProgress2: function (prg, sz) {
                        text2 = prg + "/" + sz;
                        _draw();
                    },
                    // event registration
                    setSeekEvent: function (callback) {
                        seekNotify = callback;
                    },
                    setNavigateEvent: function (callback) {
                        naviNotify = callback;
                    },
                    setDblClickEvent: function (callback) {
                        clickNotify = callback;
                    }
                };
            })();

            // bind context to panel    
            selectorPanel.setContext(selectorContext);
            controllerPanel.setContext(controllerContext);
            lcdPanel.setContext(lcdContext);

            function _resize() {
                var w = box.clientWidth;
                if (selectorPanel.canvas !== null) {
                    selectorPanel.canvas.width = w - 4;
                }
                if (controllerPanel.canvas !== null) {
                    controllerPanel.canvas.width = CONTROLLER_WIDTH;
                }
                if (lcdPanel.canvas !== null) {
                    lcdPanel.canvas.width = w - CONTROLLER_WIDTH - 6;
                }
                //
                selectorContext.draw();
                controllerContext.draw();
                lcdContext.draw();
            }

            ////////////////
            // module interface
            return {
                // API
                resize: function (l, t, w, h) {
                    box.style.top = t + TOP_MARGIN + "px";
                    _resize();
                },
                setMode: function (mode) {
                    uiMode = mode;
                    lcdPanel.show(false);
                    controllerPanel.show(false);
                    selectorPanel.show(false);
                    //
                    if (uiMode >= 2) { // show selector
                        selectorPanel.show(true);
                    }
                    if (uiMode >= 1) {
                        controllerPanel.show(true);
                        controllerPanel.canvas.style.float = "left";
                        lcdPanel.show(true);
                        lcdPanel.canvas.style.float = "right";
                    }
                    //
                    _resize();
                },
                getHeight: function () {
                    return (uiMode > 1) ? 2 * DEFAULT_HEIGHT + 2 : DEFAULT_HEIGHT + 2;
                },
                clear: function () {
                    selectorContext.clear();
                    controllerContext.clear();
                    lcdContext.clear();
                    this.setMode(0);
                },
                setReportEvent: function (callback) {
                    reportNotify = callback;
                },
                Element: box,
                Selector: selectorContext,
                Controller: controllerContext,
                LCD: lcdContext
            };
        }
    };
});

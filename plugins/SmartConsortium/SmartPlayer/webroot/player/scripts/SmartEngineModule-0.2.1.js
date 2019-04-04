////////////////////////////////////////////////////////////////
//
//  SmartEngine
//
//  2015/05/03
//    correct "mediaOpened: function".
//  2015/05/03
//    add "seeking" check mechanism to prevent seek on previous uncompleted seek.
//  2015/05/03
//    add "isPlaying: function" API return "var playing" value.
//  2015/07/08
//    add "angleNumber = number" in processAngle function;    
//    add getAngle API
//  2015/07/15
//    "stillMode" can not changed at operation with "inc.OP_PAUSE"
//  2016/02/17
//    add (/svid.json$/i) logic into loadSource function
//  2016/06/02
//    add "svid[inc.SV_CAPTION] = src" into loadSource function
//  2017/01/20 0.1.8
//    add check videoStreaming logic into "_loadSource" function
//    & modify condition check for "stillPlayer.eraseImage();" in setTrick function
//  2017/02/09 0.2.0
//    add "panel" in "getStillSource" function for multi-camera-view process
//    add "name" in "getStillSource" function for multi-camera-view process
//    increase permissions array for multi-camera-view switch
//  2017/02/11 0.2.0 (SmartEngine -> SmartEngineModule)
//    modulized
//  2017/03/03 0.2.1
//    take off "autoPlay" flag
//  2017/03/10 0.2.2
//    remove still.showStill() from "case 2:" in setSeek 
//    move OP_CHECK & OP_REPEAT from "operate" function to "_operate" function
//    add setPreOperationEvent
//    
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

define(["Include"], function (inc) {
    return {
        create: function (glb, undef) {
            var VERSION = "0.2.1";
            // virtual-track key-name
            var VT_DURATION = 'duration';
            var VT_SLOWRVS = 'slowReverse';
            var VT_RVS = 'playReverse';
            var VT_FWD = 'playForward';
            var VT_SLOWFWD = 'slowForward';
            var VT_SRC_RANGE = 'srcRange';
            var VT_DST_RANGE = 'dstRange';
            var TRICK_NAMES = ["", VT_SLOWRVS, VT_RVS, VT_FWD, VT_SLOWFWD];
            var DEFAULT_ORDER = [VT_FWD, VT_SLOWFWD, VT_SLOWRVS, VT_RVS];
            // still mode
            var SM_NONE = 0;
            var SM_THUMB = 1;
            var SM_STILL = 2;
            // time const.
            var MAX_DURATION = 99999999;
            var NATIVE_END_MARGIN = 500;
            var TICK_INTERVAL = 200;
            var IN_POS_DISTANCE = 100;
            var SEEKCOMPLETE_MGN = 500;

            var videoPlayer;
            var stillPlayer;
            var ejectNotify;
            var mediaOpenNotify;
            var timeNotify;
            var permissionNotify;
            var operationNotify;
            var preOperationNotify;
            var reportNotify;
            // media context
            var svid = null;
            var videoStreaming = false;
            var tickInterval;
            var ntvDuration = 0;
            // angle_object "name":"<camera name>","refs":[{<index>,<size>}, ...]
            var angles = []; // array of angle_object
            var thumbTimes = null;
            // camera context
            var camIndex = 0;
            var angleNumber = 0;
            var thumbValid = false;
            var stillMode = SM_NONE; // 0:none 1:thumbnail 2:still
            var duration = 0;
            // track context
            var range = {};
            // permissions is array of boolean indexed by operation_code
            // [inc.OP_STOP, inc.OP_SLOWRVS, inc.OP_RVS, inc.OP_FWD, inc.OP_SLOWFWD, inc.OP_STEPRVS, inc.OP_STEPFWD, inc.OP_CHECK, inc.OP_REPEAT, inc.OP_MCVIEW]
            var permissions = [true, false, false, true, false, false, false, true, true, false];
            //
            var operation = inc.OP_STOP;
            var position = 0;
            var checkPosition = 0;
            var running = false;
            var playing = false;
            var seeking = false;
            // zoom
            var area = [0, 0, 1, 1];
            var partialZoom = (function () {
                var valid = false;
                var fw = 0, fh = 0, dx = 0, dy = 0;
                var max = 0;
                var angle;
                var zr = [0, 0, 1, 1];
                return {
                    initialize: function () {
                        valid = false;
                        var camMain = svid[inc.SV_CAMERA][angles[0].refs[0].index];
                        if (!videoPlayer || !([inc.SV_SIZE] in camMain)) {
                            return;
                        }
                        // calculate main camera size
                        var vsz = videoPlayer.getVideoSize();
                        fw = camMain[inc.SV_SIZE][0];
                        fh = camMain[inc.SV_SIZE][1];
                        if ((fh === 0) || (vsz[0] === 0) || (vsz[1] === 0)) {
                            return;
                        }
                        if ((fw / fh) > (vsz[0] / vsz[1])) {
                            fh = fw * vsz[1] / vsz[0];
                        } else {
                            fw = fh * vsz[0] / vsz[1];
                        }
                        dx = (fw - camMain[inc.SV_SIZE][0]) / 2;
                        dy = (fh - camMain[inc.SV_SIZE][1]) / 2;
                        max = fw * fh;
                        valid = true;
                    },
                    searchCamera: function (index) { // index: index of (camrera) angle
                        var sel = 0;
                        if (!valid) {
                            return {"select": 0, "zoom": [0, 0, 1, 1]};
                        }
                        angle = angles[index];
                        var cms = svid[inc.SV_CAMERA];
                        // check area for actual
                        var zf = (area[0] > 0) || (area[1] > 0) || (area[2] < 1) || (area[3] < 1);
                        if (!zf || !(inc.SV_SIZE in cms[0]) || (angle.refs.length === 0)) {
                            // zoom off
                            return {"select": 0, "zoom": [0, 0, 1, 1]};
                        }
                        // zoom on
                        // calculate area_rect
                        var ar = [0, 0, 0, 0];
                        ar[0] = fw * area[0] - dx;
                        ar[1] = fh * area[1] - dy;
                        ar[2] = fw * area[2] - dx;
                        ar[3] = fh * area[3] - dy;
                        //
                        var cam;
                        var pr;
                        var px = 0, py = 0, pw = 0, ph = 0;
                        var sq = max;
                        // search minimum size part for area_rect
                        for (var i = 0; i < angle.refs.length; i++) {
                            cam = cms[angle.refs[i].index];
                            if ([inc.SV_PART] in cam) {
                                pr = cam[inc.SV_PART];
                                if ((ar[0] > pr[0])
                                        && (ar[1] > pr[1])
                                        && (ar[2] < pr[2])
                                        && (ar[3] < pr[3])) {
                                    if (sq > angle.refs[i].size) {
                                        // found!
                                        sq = angle.refs[i].size;
                                        sel = i; // record angle_select index
                                        // record part geometry
                                        px = pr[0];
                                        py = pr[1];
                                        pw = pr[2] - pr[0];
                                        ph = pr[3] - pr[1];
                                    }
                                }
                            }
                        }
                        if ((pw === 0) || (ph === 0)) {
                            // not found
                            zr.copyFrom([0, 0, 1, 1]);
                        } else {
                            // found & calculate normalized rect for zoom
                            zr[0] = (ar[0] - px) / pw;
                            zr[1] = (ar[1] - py) / ph;
                            zr[2] = (ar[2] - px) / pw;
                            zr[3] = (ar[3] - py) / ph;
                        }
                        return {"select": sel, "zoom": zr};
                    }
                };
            })();
            // add swap into array
            Array.prototype.swap = function (x, y) {
                var tmp = this[x];
                this[x] = this[y];
                this[y] = tmp;
                return this;
            };
            Array.prototype.copyFrom = function (src) {
                for (var i = 0; (i < this.length) && (i < src.length); i++) {
                    this[i] = src[i];
                }
            };
            // utilities
            function report(s) {
                if (reportNotify) {
                    reportNotify("SmartEngine:" + s);
                }
            }
            function assert(test, env) {
                if (test) {
                    return true;
                } else {
                    alert("assertion! not " + JSON.stringify(test) + " at " + env);
                    return false;
                }
            }
            function getHttpDoc(url, ok, ng) {
                var httpReq = new XMLHttpRequest();
                httpReq.onload = function () {
                    if (200 === httpReq.status) {
                        ok(this.response);
                    } else {
                        ng("failed <getHttpDoc>:" + url);
                    }
                };
                httpReq.open("GET", url, true);
                httpReq.send();
            }
            function getCloseIndex(array, value) {
                if (!Array.isArray(array)) {
                    return false;
                } else {
                    var l = 0, s = 0;
                    var h = array.length - 1;
                    while ((h - l) > 1) {
                        s = Math.round((h + l) / 2);
                        if (value < array[s]) {
                            h = s;
                        } else {
                            l = s;
                        }
                    }
                    if (value >= array[h]) {
                        return h;
                    } else {
                        return l;
                    }
                }
            }
            //
            function _eject() {
                glb.clearInterval(tickInterval);
                duration = 0;
                position = 0;
                seeking = false;
                playing = false;
                range.ratio = 0;
                area.copyFrom([0, 0, 1, 1]);
                stillMode = SM_NONE;
                thumbTimes = null;
                angles = [];
                svid = null;
                //
                if (videoPlayer) {
                    videoPlayer.eject();
                }
                if (stillPlayer) {
                    stillPlayer.eject();
                }
                if (ejectNotify) {
                    ejectNotify();
                }
            }
            function _loadSource() {
                try {
                    if (!svid || !(svid[inc.SV_VERSION])) {
                        return;
                    }
                    if (svid[inc.SV_VERSION] < inc.MIN_VERSION) {
                        alert("SVID version need " + inc.MIN_VERSION + " or higher");
                        return;
                    }
                    // check videoStreaming
                    videoStreaming = false;
                    var prop;
                    for (prop in svid[inc.SV_VIDEO]) {
                        if ((prop !== inc.SV_DURATION) && (prop !== inc.SV_SIZE)) {
                            videoStreaming = true;
                            break;
                        }
                    }
                    //
                    makeDefaultSvid();
                    makeAngleArray();
                    makeThumbnailTimeList();
                    angleNumber = 0;
                    _setCamera(0, true);
                    if (videoPlayer && svid[inc.SV_VIDEO]) {
                        videoPlayer.loadVideo(svid[inc.SV_VIDEO]);
                    }
                    if (stillPlayer) {
                        stillPlayer.loadSource(getStillSource());
                    }
                } catch (e) {
                    alert(e);
                }
            }
            function makeDefaultSvid() {
                if (!([inc.SV_CAMERA] in svid)) {
                    svid[inc.SV_CAMERA] = [];
                }
                if (!([0] in svid[inc.SV_CAMERA])) {
                    svid[inc.SV_CAMERA][0] = {};
                }
                for (var i = 0; i < svid[inc.SV_CAMERA].length; i++) {
                    var cam = svid[inc.SV_CAMERA][i];
                    if (!([inc.SV_VTRACK] in cam)) {
                        cam[inc.SV_VTRACK] = {};
                    }
                    var vt = cam[inc.SV_VTRACK];
                    if (!([VT_DURATION] in vt)) {
                        vt[VT_DURATION] = -1;
                    }
                    if (!([VT_FWD] in vt)) {
                        vt[VT_FWD] = [];
                    }
                    if (!([0] in vt[VT_FWD])) {
                        vt[VT_FWD][0] = {};
                        vt[VT_FWD][0][VT_DST_RANGE] = [0, -1];
                        vt[VT_FWD][0][VT_SRC_RANGE] = [0, -1];
                    }
                }
            }
            function makeAngleArray() {
                angles.splice(0, angles.length);
                var cam, unmatch = false, an = "";
                var sz = 0, max = 0, main = 0;
                for (var i = 0; i < svid[inc.SV_CAMERA].length; i++) {
                    cam = svid[inc.SV_CAMERA][i];
                    if (!([inc.SV_NAME] in cam)) {
                        cam[inc.SV_NAME] = "";
                    }
                    // build angles
                    unmatch = true;
                    an = cam[inc.SV_NAME];
                    if ([inc.SV_PART] in cam) {
                        sz = (cam[inc.SV_PART][2] - cam[inc.SV_PART][0]) * (cam[inc.SV_PART][3] - cam[inc.SV_PART][1]);
                    } else {
                        sz = 0;
                    }
                    for (var j = 0; j < angles.length; j++) {
                        if (an === angles[j].name) {
                            angles[j].refs.push({"index": i, "size": sz});
                            unmatch = false;
                            break;
                        }
                    }
                    if (unmatch) {
                        angles.push({name: an, refs: [{"index": i, "size": sz}]});
                    }
                }
                // sort by size
                for (var i = 0; i < angles.length; i++) {
                    angles[i].refs.sort(function (a, b) {
                        return b.size - a.size;
                    });
                }
            }
            function makeThumbnailTimeList() {
                thumbTimes = new Array(svid[inc.SV_CAMERA].length);
                var cam, tl;
                for (var i = 0; i < svid[inc.SV_CAMERA].length; i++) {
                    cam = svid[inc.SV_CAMERA][i];
                    thumbTimes[i] = [];
                    if ([inc.SV_THUMB] in cam) {
                        tl = cam[inc.SV_THUMB][inc.SV_TIMELIST];
                        for (var j = 0; j < tl.length; j++) {
                            thumbTimes[i].push(tl[j]);
                        }
                        thumbTimes[i].sort(function (a, b) {
                            return a - b;
                        });
                    }
                }
            }
            function getStillSource() {
                var sd = null;
                if ((svid !== null) && svid[inc.SV_CAMERA]) {
                    sd = new Array(svid[inc.SV_CAMERA].length);
                    for (var i = 0; i < svid[inc.SV_CAMERA].length; i++) {
                        sd[i] = {};
                        var cam = svid[inc.SV_CAMERA][i];
                        if (([inc.SV_STILL] in cam) && ([inc.SV_THUMB] in cam)) {
                            sd[i][inc.SV_STILL] = cam[inc.SV_STILL];
                            sd[i][inc.SV_THUMB] = cam[inc.SV_THUMB];
                            sd[i][inc.SV_TIMELIST] = thumbTimes[i];
                        }
                        sd[i][inc.SV_PANEL] = ([inc.SV_PANEL] in cam) ? cam[inc.SV_PANEL] : {};
                        sd[i][inc.SV_NAME] = ([inc.SV_NAME] in cam) ? cam[inc.SV_NAME] : "";
                    }
                }
                return sd;
            }
            function addTrickTimeCue() {
                var vt, tt, rng;
                for (var i = 0; i < svid[inc.SV_CAMERA].length; i++) {
                    vt = svid[inc.SV_CAMERA][i][inc.SV_VTRACK];
                    tt = [];
                    TRICK_NAMES.forEach(function (kn) {
                        if ([kn] in vt) {
                            for (var j = 0; j < vt[kn].length; j++) {
                                rng = vt[kn][j][VT_DST_RANGE];
                                if (0 > tt.indexOf(rng[0])) {
                                    tt.push(rng[0]);
                                }
                                if (0 > tt.indexOf(rng[1])) {
                                    tt.push(rng[1]);
                                }
                            }
                        }
                    });
                    tt.sort(function (a, b) {
                        return a - b;
                    });
                    svid[inc.SV_CAMERA][i].cue = tt;
                }
            }
            function prepare(nd) {
                var vt, sr, dr;
                for (var i = 0; i < svid[inc.SV_CAMERA].length; i++) {
                    vt = svid[inc.SV_CAMERA][i][inc.SV_VTRACK];
                    vt[VT_DURATION] = (0 > vt[VT_DURATION]) ? nd : vt[VT_DURATION];
                    // replace unknown value in vtracks to nativeDuration
                    TRICK_NAMES.forEach(function (kn) {
                        if ([kn] in vt) {
                            for (var j = 0; j < vt[kn].length; j++) {
                                sr = vt[kn][j][VT_SRC_RANGE];
                                dr = vt[kn][j][VT_DST_RANGE];
                                for (var k = 0; k < 2; k++) {
                                    sr[k] = (sr[k] === MAX_DURATION) ? nd : sr[k];
                                    sr[k] = (0 > sr[k]) ? nd : sr[k];
                                    sr[k] = (nd < sr[k]) ? nd : sr[k];
                                    dr[k] = (dr[k] === MAX_DURATION) ? nd : dr[k];
                                    dr[k] = (0 > dr[k]) ? nd : dr[k];
                                    dr[k] = (nd < dr[k]) ? nd : dr[k];
                                }
                            }
                        }
                    });
                    // trim thumbnail time list
                    var tmp = thumbTimes[i].filter(function (elm) {
                        return elm < vt[VT_DURATION];
                    });
                    thumbTimes[i] = tmp;
                }
            }
            function applyZoomCamera(zoomObj) {
                if (videoPlayer) {
                    videoPlayer.zoomArea(zoomObj.zoom, zoomObj.index !== camIndex);
                }
                if (stillPlayer) {
                    stillPlayer.zoomArea(zoomObj.zoom, zoomObj.index !== camIndex);
                }
                if (zoomObj.index !== camIndex) {
                    // need change camera
                    _setCamera(zoomObj.index);
                }
            }
            function processAngle(number) {
                var cix = 0;
                var zr = [0, 0, 1, 1];
                var nz = (area[0] === 0) && (area[1] === 0) && (area[2] === 1) && (area[3] === 1);
                //
                if (nz) {
                    // zoom off & camera to main part
                    cix = angles[number].refs[0].index;
                } else {
                    // zoom on & search part camera
                    var pz = partialZoom.searchCamera(number);
                    if (pz.select > 0) {
                        // there is a pert camera & set zoom for part
                        zr.copyFrom(pz.zoom);
                    } else {
                        // no part camera & set zoom for main
                        zr.copyFrom(area);
                    }
                    // get camera index for current angle
                    cix = angles[number].refs[pz.select].index;
                }
                angleNumber = number;
                return {// zoomObj
                    index: cix,
                    zoom: zr
                };
            }
            function _setCamera(index, cameraOnly) {
                if (seeking || (svid === null) || !([inc.SV_CAMERA] in svid)) {
                    return;
                }
                if ((index < 0) || (index >= svid[inc.SV_CAMERA].length)) {
                    return;
                }
                pauseVideo();
                // setup camera spec.
                var cam = svid[inc.SV_CAMERA][index];
                duration = cam[inc.SV_VTRACK][VT_DURATION];
                thumbValid = ((thumbTimes !== null) && (0 < thumbTimes[index].length));
                // setup permission array
                var sv = stillPlayer && ([inc.SV_STILL] in cam)
                        && ([inc.SV_SOURCE] in cam[inc.SV_STILL])
                        && ([inc.SV_TIMELIST] in cam[inc.SV_STILL]);
                permissions[inc.OP_STEPFWD] = sv;
                permissions[inc.OP_STEPRVS] = sv;
                for (var i = 0; i < TRICK_NAMES.length; i++) {
                    permissions[i] = [TRICK_NAMES[i]] in cam[inc.SV_VTRACK];
                }
                //
                permissions[inc.OP_STOP] = true; // "stop" is always enabled
                permissions[inc.OP_MCVIEW] = ([inc.SV_PANEL] in cam);
                adjustPermission();
                //
                camIndex = index;
                if (!cameraOnly) {
                    setTrick(operation);
                }
                if (stillPlayer && !playing) {
                    stillPlayer.showStill(index, position);
                }
            }
            function setTrick(opCode) {
                if ((svid === null) || !videoPlayer) {
                    return false;
                }
                if ((opCode < 1) || (opCode > TRICK_NAMES.length)) {
                    return false;
                }
                //
                var vt = svid[inc.SV_CAMERA][camIndex][inc.SV_VTRACK];
                var trick = vt[[TRICK_NAMES[opCode]]];
                if (!trick) {
                    return false;
                }
                operation = opCode;
                // search trick segment for meet current position
                var drc = (opCode === inc.OP_FWD) || (opCode === inc.OP_SLOWFWD);
                var dr, dif = 0, min = vt[VT_DURATION], bs = 0, cl = 0;
                var sel = -1;
                for (var i = 0; i < trick.length; i++) {
                    if (trick[i][VT_SRC_RANGE][0] === trick[i][VT_SRC_RANGE][1]) {
                        continue;
                    }
                    dr = trick[i][VT_DST_RANGE];
                    if ((drc && (position < dr[1])) || (!drc && (position > dr[1]))) {
                        dif = Math.abs(position - dr[0]);
                        if (dif < min) {
                            sel = i;
                            min = dif;
                        }
                    }
                }
                if (sel < 0) {
                    _operate(inc.OP_PAUSE);
                    videoPlayer.setShutter(true);
                    return false;
                }
                setupRange(trick[sel]);
                // need set-position?
                var rpos = videoPlayer.getPosition();
                if (Math.abs(rpos - getRealPosition(position)) > IN_POS_DISTANCE) {
                    _setPosition(position);
                } else {
                    videoPlayer.setShutter(false);
                    if (playing) {
                        playVideo();
                        if (stillPlayer && videoStreaming) {
                            stillPlayer.eraseImage();
                        }
                    }
                }
                return true;
            }
            function setupRange(vtr) {
                var rep = ntvDuration - NATIVE_END_MARGIN;
                var sr = vtr[VT_SRC_RANGE], dr = vtr[VT_DST_RANGE];
                if (sr[0] < sr[1]) {
                    range.dstBase = dr[0];
                    range.dstCeil = dr[1];
                    range.srcBase = sr[0];
                    range.srcCeil = (sr[1] < rep) ? sr[1] : rep;
                    range.ratio = (dr[1] - dr[0]) / (sr[1] - sr[0]);
                }
                return sr[0] < sr[1];
            }
            // time conversion
            function getVirtualPosition(realPos) {
                if (range.ratio === 0) {
                    throw new Error("invalid range! at getVirtualPosition(" + realPos + ")");
                }
                return range.dstBase + range.ratio * (realPos - range.srcBase);
            }
            function getRealPosition(virtualPos) {
                if (range.ratio === 0) {
                    throw new Error("invalid range! at getRealPosition(" + virtualPos + ")");
                }
                return range.srcBase + (virtualPos - range.dstBase) / range.ratio;
            }
            function getPositionFromSeek(seekRatio) {
                seekRatio = (seekRatio < 0) ? 0 : seekRatio;
                seekRatio = (seekRatio > 1) ? 1 : seekRatio;
                if (!thumbValid) {
                    return seekRatio * duration;
                } else {
                    var len = thumbTimes[camIndex].length;
                    return thumbTimes[camIndex][Math.round(seekRatio * (len - 1))];
                }
            }
            function getSeekFromPosition(pos) {
                pos = (pos < 0) ? 0 : pos;
                pos = (pos > duration) ? duration : pos;
                var tts = (thumbValid) ? thumbTimes[camIndex] : [];
                if (tts.length === 0) {
                    return pos / duration;
                } else {
                    var ix = getCloseIndex(tts, pos);
                    return (ix < 0) ? 0 : ix / tts.length;
                }
            }
            // time process
            function _getPosition() {
                return position;
            }
            function _setPosition(pos) {
                position = pos;
                if (videoPlayer && (range.ratio !== 0)) {
                    seeking = true;
                    var rp = getRealPosition(pos);
                    var ep = ntvDuration - NATIVE_END_MARGIN;
                    if (rp > ep) {
                        rp = ep;
                        position = getVirtualPosition(rp);
                    }
                    videoPlayer.setPosition(rp);
                }
                if (stillPlayer && !playing) {
                    position = stillPlayer.getPosition(camIndex, position, 0);
                    stillPlayer.showStill(camIndex, position);
                    seeking = false;
                }
            }
            function processTick() {
                if (seeking) {
                    return;
                }
                //
                if (stillMode !== SM_STILL) {
                    if (videoPlayer && (stillMode !== SM_STILL) && (range.ratio !== 0)) {
                        var rpos = videoPlayer.getPosition();
                        if (running && rpos >= (range.srcCeil)) {
                            _operate(inc.OP_PAUSE);
                            rpos = range.srcCeil;
                            adjustPermission();
                        }
                        position = getVirtualPosition(rpos);
                    }
                }
                //
                if (timeNotify) {
                    timeNotify(
                            {"time": position, "ratio": getSeekFromPosition(position)}
                    );
                }
            }
            // operation
            function adjustPermission() {
                var pms = permissions.slice(0);
                //
                if (permissionNotify) {
                    permissionNotify(pms);
                }
            }
            function resetOperation() {
                if (svid === null) {
                    return;
                }
                var vt = svid[inc.SV_CAMERA][camIndex][inc.SV_VTRACK];
                var kn = "";
                for (var i = 0; i < DEFAULT_ORDER.length; i++) {
                    kn = DEFAULT_ORDER[i];
                    if ([kn] in vt) {
                        if (setupRange(vt[kn][0])) {
                            operation = TRICK_NAMES.indexOf(kn);
                            _setPosition(vt[kn][0][VT_DST_RANGE][0]);
                        }
                        break;
                    }
                }
            }
            function playVideo() {
                if (videoPlayer) {
                    videoPlayer.play();
                    running = true;
                }
            }
            function pauseVideo() {
                if (videoPlayer) {
                    videoPlayer.pause();
                    running = false;
                }
            }
            function _operate(opCode) {
                if (preOperationNotify) {
                    preOperationNotify(opCode);
                }
                if ((opCode !== inc.OP_CHECK) && (opCode !== inc.OP_REPEAT)) {
                    if (videoPlayer.setPlaybackRate) {
                        videoPlayer.setPlaybackRate(1);
                    }
                    pauseVideo();
                    playing = false;
                    if (opCode !== inc.OP_PAUSE) {
                        stillMode = SM_NONE;
                    }
                }
                switch (opCode) {
                    case inc.OP_PAUSE:
                        break;
                    case inc.OP_STOP:
                        resetOperation();
                        break;
                    case inc.OP_PLAY:
                        opCode = operation;
                    case inc.OP_SLOWRVS:
                    case inc.OP_RVS:
                    case inc.OP_FWD:
                    case inc.OP_SLOWFWD:
                        if (videoPlayer) {
                            playing = true;
                            if (!setTrick(opCode)) {
                                return;
                            }
                        }
                        break;
                    case inc.OP_STEPRVS:
                    case inc.OP_STEPFWD:
                        if (stillPlayer) {
                            stillMode = SM_STILL;
                            var drc = (opCode === inc.OP_STEPFWD) ? 1 : -1;
                            position = stillPlayer.getPosition(camIndex, position, drc);
                            stillPlayer.showStill(camIndex, position);
                        }
                        break;
                    case inc.OP_CHECK:
                        checkPosition = _getPosition();
                        break;
                    case inc.OP_REPEAT:
                        _setPosition(checkPosition);
                        break;
                }
                if (operationNotify) {
                    operationNotify(opCode);
                }
            }

            ////////////////
            // module interface
            return {
                // binder
                setVideoPlayer: function (player) {
                    videoPlayer = player;
                },
                setStillPlayer: function (player) {
                    stillPlayer = player;
                },
                // API
                eject: function () {
                    _eject();
                },
                loadSource: function (src, auto) {
                    if (svid !== null) {
                        _eject();
                    }
                    if (src === "") {
                        return;
                    }
                    playing = (auto) ? true : false;
                    //
                    try {
                        if (!((/^http/i).test(src))) {
                            // src is svid text
                            svid = JSON.parse(src);
                            _loadSource();
                        } else {
                            // src is web document url
                            if ((/svid$/i).test(src) || (/svid.json$/i).test(src)) {
                                // src is SVID file
                                getHttpDoc(src, function (r) {
                                    svid = JSON.parse(r);
                                    _loadSource();
                                }, function (e) {
                                    alert(e);
                                });
                            } else {
                                // src is not SVID file so make svid skelton
                                svid = {};
                                svid[inc.SV_CAPTION] = src;
                                svid[inc.SV_VERSION] = inc.MIN_VERSION;
                                svid[inc.SV_VIDEO] = {};
                                if ((/.csm$/i).test(src) || (/.ism\/Manifest$/).test(src)) {
                                    svid[inc.SV_VIDEO][inc.SV_SS] = src;
                                } else {
                                    svid[inc.SV_VIDEO][inc.SV_DEFAULT] = src;
                                }
                                _loadSource();
                            }
                        }
                    } catch (e) {
                        alert(e.name + ":" + e.massage + " at loadSource(" + src + ")");
                    }
                },
                getCaption: function () {
                    if (svid === null) {
                        return "";
                    } else {
                        return svid[inc.SV_CAPTION];
                    }
                },
                setCamera: function (index) {
                    _setCamera(index);
                },
                getAngleCount: function () {
                    return angles.length;
                },
                getAngleList: function () {
                    // make list of angle name
                    var al = [];
                    for (var i = 0; i < angles.length; i++) {
                        al.push(angles[i].name);
                    }
                    return al;
                },
                getAngle: function () {
                    return angleNumber;
                },
                setAngle: function (state, number) {
                    if ((svid === null) || (number < 0) || (number >= angles.length)) {
                        return;
                    }
                    //
                    var zc = processAngle(number);
                    var cix = zc.index;
                    switch (state) {
                        case 0:
                            pauseVideo();
                        case 1:
                            if (stillPlayer && thumbValid) {
                                var tix = getCloseIndex(thumbTimes[cix], position);
                                if (Math.abs(thumbTimes[cix][tix] - position) < 2) {
                                    stillPlayer.showThumbnail(cix, tix);
                                }
                            }
                            break;
                        case 2:
                            applyZoomCamera(zc);
                            break;
                    }
                },
                getDuration: function () {
                    return duration;
                },
                operate: function (opCode) {
                    if (svid === null) {
                        return;
                    }
                    _operate(opCode);
                },
                getPosition: function () {
                    if (svid === null) {
                        return 0;
                    }
                    return _getPosition();
                },
                setPosition: function (pos) {
                    if (svid === null) {
                        return;
                    }
                    _setPosition(pos);
                },
                setSeek: function (state, seekRatio) {
                    if (svid === null) {
                        return {"time": 0, "ratio": 0};
                    }
                    var pos = getPositionFromSeek(seekRatio);
                    switch (state) {
                        case 0:
                            seeking = true;
                            pauseVideo();
                        case 1:
                            if (stillPlayer && thumbValid) {
                                stillMode = SM_THUMB;
                                var tix = Math.round(seekRatio * (thumbTimes[camIndex].length - 1));
                                stillPlayer.showThumbnail(camIndex, tix);
                            }
                            break;
                        case 2:
                            if (playing || !stillPlayer) {
                                stillMode = SM_NONE;
                            } else {
                                stillMode = SM_STILL;
                            }
                            _setPosition(pos);
                            break;
                    }
                    return {"time": pos, "ratio": seekRatio};
                },
                zoomArea: function (normalArea) {
                    if (svid === null) {
                        return;
                    }
                    // prepare area[] by normalArea
                    area.copyFrom(normalArea);
                    if (area[0] > area[2]) {
                        area.swap(0, 2);
                    }
                    if (area[1] > area[3]) {
                        area.swap(1, 3);
                    }
                    if ((area[0] === area[2]) || (area[1] === area[3])) {
                        area = [0, 0, 1, 1];
                    }
                    //
                    applyZoomCamera(processAngle(angleNumber));
                },
                isPlaying: function () {
                    return playing;
                },
                // event handler from video player
                mediaOpened: function () {
                    ntvDuration = videoPlayer.getDuration();
                    prepare(ntvDuration);
                    addTrickTimeCue();
                    //
                    _setCamera(0, true);
                    resetOperation();
                    partialZoom.initialize();
                    // start tick process
                    tickInterval = glb.setInterval(processTick, TICK_INTERVAL);
                    if (mediaOpenNotify) {
                        mediaOpenNotify();
                    }
                    //
                    if (playing) {
                        _operate(operation);
                    } else {
                        _operate(inc.OP_PAUSE);
                    }
                },
                mediaEnded: function () {
                    _operate(inc.OP_STOP);
                },
                seekCompleted: function () {
                    seeking = false;
                    videoPlayer.setShutter(false);
                    if (playing) {
                        playVideo();
                        if (videoStreaming && stillPlayer) {
                            glb.setTimeout(function () {
                                stillPlayer.eraseImage();
                            }, SEEKCOMPLETE_MGN);
                        }
                    }
                },
                // event registration
                setEjectEvent: function (callback) {
                    ejectNotify = callback;
                },
                setMediaOpenEvent: function (callback) {
                    mediaOpenNotify = callback;
                },
                setPermissionEvent: function (callback) {
                    permissionNotify = callback;
                },
                setOperationEvent: function (callback) {
                    operationNotify = callback;
                },
                setPreOperationEvent: function (callback) {
                    preOperationNotify = callback;
                },
                setTimeEvent: function (callback) {
                    timeNotify = callback;
                },
                // auxiliary functions
                getVersion: function () {
                    return VERSION;
                },
                getMcvReady: function () {
                    return permissions[inc.OP_MCVIEW];
                },
                setReportEvent: function (callback) {
                    reportNotify = callback;
                },
                test: function () {
                }
            };
        }
    };
});
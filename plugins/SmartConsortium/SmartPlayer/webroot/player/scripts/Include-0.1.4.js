////////////////////////////////////////////////////////////////
//
//  Include
//
//    2017/01/11 0.1.2
//    2017/02/09 0.1.3    
//      add SV_PANEL
//    2017/03/10 0.1.4
//    
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

define({
    // SVID key-name
    MIN_VERSION: 7.0,
    SV_VERSION: "version",
    SV_CAPTION: "caption",
    SV_VIDEO: "video",
    SV_SS: "smooth",
    SV_HLS: "HLS",
    SV_MP4: "default",
    SV_DEFAULT: "default",
    SV_DURATION: "duration",
    SV_SIZE: "size",
    SV_CAMERA: "camera",
    SV_NAME: "name",
    SV_VTRACK: "vtrack",
    SV_STILL: "still",
    SV_THUMB: "thumbnail",
    SV_SOURCE: "source",
    SV_TIMELIST: "times",
    SV_PART: "part",
    SV_PANEL: "panel",
    SV_X: "x",
    SV_Y: "y",
    SV_SCALE: "scale",
    // operation code
    OP_PLAY: -2,
    OP_PAUSE: -1,
    OP_STOP: 0,
    OP_SLOWRVS: 1,
    OP_RVS: 2,
    OP_FWD: 3,
    OP_SLOWFWD: 4,
    //
    OP_POSITION: 5,
    OP_STEPRVS: 5,
    OP_STEPFWD: 6,
    //
    OP_CHECK: 7,
    OP_REPEAT: 8,
    //
    OP_EXT: 9,
    OP_MCVIEW: 9,
    OP_ZOOM: 10,
    OP_FULLSCRN: 11,
    // pointer state
    PS_DOWN: 0,
    PS_MOVE: 1,
    PS_UP: 2,
    PS_DBLCLICK: 3,
    // switch state
    SS_DISABLE: 0,
    SS_ENABLE: 1,
    SS_HITTED: 2,
    SS_DOWNED: 2,
    // window message const
    MSG_LOADED: "loaded",
    MSG_SVG_EVENT: "svgEvent",
    MSG_MOUSE_EVENT: "mouseEvent",
    MSG_REPORT: "report",
    //
    PLATFORM: {
        NONE: 0,
        WIN32: 1,
        MAC: 2,
        DESKTOP: 3,
        IPAD: 4, // mobile: platformLevel > 3 
        IPHONE: 5,
        ANDROID: 6
    },
    //
    getThumbnailTimelist: function (camera) {
        return (!([this.SV_THUMB] in camera)) ? []
                : (!([this.SV_TIMELIST] in camera[this.SV_THUMB])) ? []
                : camera[this.SV_THUMB][this.SV_TIMELIST];
    },
    getThumbnailTime: function (camera, tindex) {
        var tl = this.getThumbnailTimelist(camera);
        return (tindex < tl.length) ? tl[tindex] : false;
    },
    getThumnailSrcURL: function (camera) {
        return (!([this.SV_THUMB] in camera)) ? ""
                : (!([this.SV_SOURCE] in camera[this.SV_THUMB])) ? ""
                : (camera[this.SV_THUMB][this.SV_SOURCE]);//.replace(/\/$/, "");
    },
    getStillTimeURL: function (camera) {
        return (!([this.SV_STILL] in camera)) ? ""
                : (!([this.SV_TIMELIST] in camera[this.SV_STILL])) ? ""
                : (camera[this.SV_STILL][this.SV_TIMELIST]);//.replace(/\/$/, "");
    },
    getStillSrcURL: function (camera) {
        return (!([this.SV_STILL] in camera)) ? ""
                : (!([this.SV_SOURCE] in camera[this.SV_STILL])) ? ""
                : (camera[this.SV_STILL][this.SV_SOURCE]);//.replace(/\/$/, "");
    },
    isPlaybackOp: function (code) {
        return code < this.OP_POSITION;
    },
    isTrickOp: function (code) {
        return (code >= this.OP_SLOWRVS) && (code <= this.OP_SLOWFWD);
    },
    isStepOp: function (code) {
        return (code === this.OP_STEPRVS) || (code === this.OP_STEPFWD);
    },
    isForcePauseOp: function (code) {
        return (code === this.OP_STOP) || (code === this.OP_PAUSE) || this.isStepOp(code);
    },
    isExternalOp: function (code) {
        return code >= this.OP_EXT;
    }

});

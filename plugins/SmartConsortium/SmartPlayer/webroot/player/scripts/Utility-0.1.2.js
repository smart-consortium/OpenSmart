////////////////////////////////////////////////////////////////
//
//  Utility
//
//    2017/01/15 0.1.0
//    2017/02/24 0.1.1
//      add getPlatform, isMobileDevice, isSilverlightPlugin
//      add SVID related routine
//    2017/03/19 0.1.2
//      add isIosDevice function
//    
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

define(["Include"], function (inc) {

    return {
        // general
        isBetween: function (val, a, b) {
            return ((a <= val) && (val < b)) || ((b <= val) && (val < a));
        },
        getCloseIndex: function (array, value) {
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
        },
        getCloseValue: function (array, value) {
            var val = this.getCloseIndex(array, value);
            return (!val) ? false : array[val];
        },
        // player
        getExtention: function (fn) {
            return (fn.match(/\.[^.]*$/)[0]).replace(/\./, "");
        },
        getPlatform: function (glb) {
            var nv = glb.navigator;
            return (/win32/i).test(nv.platform) ? "WIN32"
                    : (/mac/i).test(nv.platform) ? "MAC"
                    : (/iPad/i).test(nv.platform) ? "IPAD"
                    : (/iPhone/i).test(nv.platform) ? "IPHONE"
                    : (/arm/i).test(nv.platform) ? "ANDROID"
                    : (/linux/i).test(nv.platform) ? "LINUX" : "UNKOWN";
        },
        isIosDevice: function (glb) {
            return ["IPAD", "IPHONE"].indexOf(this.getPlatform(glb)) >= 0;
        },
        isMobileDevice: function (glb) {
            return ["WIN32", "MAC", "LINUX"].indexOf(this.getPlatform(glb)) < 0;
        },
        isSilverlightPlugin: function (glb) {
            var nv = glb.navigator;
            if (nv.plugins) {
                for (var i = 0; i < nv.plugins.length; i++) {
                    if ((/silverlight/i).test(nv.plugins[i].name)) {
                        return true;
                    }
                }
            }
            return false;
        },
        getFullScreenCmdMap: function (doc) {
            var val;
            var fnMap = [
                [
                    'requestFullscreen',
                    'exitFullscreen',
                    'fullscreenElement',
                    'fullscreenEnabled',
                    'fullscreenchange',
                    'fullscreenerror'
                ],
                // new WebKit
                [
                    'webkitRequestFullscreen',
                    'webkitExitFullscreen',
                    'webkitFullscreenElement',
                    'webkitFullscreenEnabled',
                    'webkitfullscreenchange',
                    'webkitfullscreenerror'

                ],
                // old WebKit (Safari 5.1)
                [
                    'webkitRequestFullScreen',
                    'webkitCancelFullScreen',
                    'webkitCurrentFullScreenElement',
                    'webkitCancelFullScreen',
                    'webkitfullscreenchange',
                    'webkitfullscreenerror'

                ],
                [
                    'mozRequestFullScreen',
                    'mozCancelFullScreen',
                    'mozFullScreenElement',
                    'mozFullScreenEnabled',
                    'mozfullscreenchange',
                    'mozfullscreenerror'
                ],
                [
                    'msRequestFullscreen',
                    'msExitFullscreen',
                    'msFullscreenElement',
                    'msFullscreenEnabled',
                    'MSFullscreenChange',
                    'MSFullscreenError'
                ]
            ];
            var i = 0;
            var l = fnMap.length;
            var ret = {};
            //
            for (; i < l; i++) {
                val = fnMap[i];
                if (val && val[1] in doc) {
                    for (i = 0; i < val.length; i++) {
                        ret[fnMap[0][i]] = val[i];
                    }
                    return ret;
                }
            }
            return false;
        }
    };
});

////////////////////////////////////////////////////////////////
//  NullVideoDevice
//  
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

define (["Include"], function(inc) {
    var setupPlayer;
    
    return {
        createVideoPlayer: function(core, glb, undef) {
            var engine;
            var reportNotify;
            
            var duration = 0;
            var position = 0;
            var videoSize = [0, 0];
            // 
            function report(s) {
                if (reportNotify) {
                    reportNotify("VideoDevice:" + s);
                }
            }
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
                },
                loadVideo: function (src) {
                    position = 0;
                    duration =  (src && src.duration)? src.duration: 0;
                    videoSize[0] = (src && src.size && src.size.width)? src.size.width: 0;
                    videoSize[1] = (src && src.size && src.size.height)? src.size.height: 1;
                    glb.setTimeout(function (e) {
                        if (engine) {
                            engine.mediaOpened();
                        }    
                    }, 100);
                },
                play: function () {
                    glb.setTimeout(function (e) {
                        if (engine) {
                            engine.operate(inc.OP_PAUSE);
                        }    
                    }, 100);
                },
                pause: function () {
                },
                getVideoSize: function () {
                    return videoSize; //[100, 100];
                },
                getDuration: function () {
                    return duration;
                },
                getPosition: function () {
                    return position;
                },
                setPosition: function (pos) {
                    position = pos;
                },
                zoomArea: function (area, sync) {
                },
                setShutter: function (flag) {           
                },
                // auxilialy functions
                getRenderSize: function () {
                    return videoSize;            
                },
                resize: function (x, y, w, h) {
                },
                displayCaption: function (s) {            
                },
                getContext: function() {
                    return "";
                },
                setVolume: function (vl) {
                },
                setBitrateEvent: function (callback) {
                },
                setMouseEvent: function (callback) {
                },
                setReportEvent: function (callback) {
                    reportNotify = callback;
                },
                test: function () {
                }
            };    
        },
        ////////
        // construct HTML5 VideoElement
        createCoreElement: function(parentID, window, document) {
            var html = '<video id="PlayerCore"></video>';
            document.getElementById(parentID).innerHTML = html;
            //
            var coreElement = document.getElementById("PlayerCore");
            coreElement.style.left = 0;
            coreElement.style.top = 0;
            window.setTimeout(function () {
                onPlayerCoreLoad(coreElement);
            }, 10);
        },
        setCoreCreateEvent: function(callback) {
            setupPlayer = callback;
        },
        loadEvent: function(sender, args) {
            if (setupPlayer) {
                setupPlayer(sender);
            }
        },
        errorEvent: function(sender, args) {
        }
    };
});
    
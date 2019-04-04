////////////////////////////////////////////////////////////////
//
//  SmartPlayerConnect.js
//  
//  2015/08/18 0.1.0
//  2015/09/12 0.2.0
//      improve "runSvgFunction"
//  2017/01/22 0.2.1
//      add functions for Full-Screen
//  2017/02/25 0.2.2
//      add functions for getStillPicture
//  
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

////////
// "command":"load"
//    "source": url of contents source
//    "autoplay": true|false
//
// "command":"eject"
//
// "command":"control"
//    "code": -2=play -1=pause 0=stop
//            1=slowReverse 2=reverse 3=forward 4=slowForward
//            5=stepReverse 6=stepForward 7=check 8=repeat
//
// "command":"getPosition"
//    "response": name of response function(position)
//
// "command":"setPosition"
//    "value":position(msec)
//
// "command":"syncPosition"
//    "response": name of response function(position)
//
// "command":"getMouseEvent"
//    "response": name of response function(state, x, y)
//
// "command":"getArea"
//    "response": [x, y, width, height]
//
// "command":"setArea"
//    "area": [x, y, width, height]

function getSmartPlayerConnecter(frameId, glb, doc, undef) {
    var MSG_PING = "ping";
    var MSG_LOADED = "loaded";
    var MSG_SVG_EVENT = "svgEvent";
    var MSG_MOUSE_EVENT = "mouseEvent";
    var MSG_REPORT = "report";
    //
    var frame = null;//doc.getElementById(frameId);
    var smartResponse = {};
    var reportNotify;
    var linked = false;
    //
    function report(s) {
        if (reportNotify) {
            reportNotify("Connecter:" + s);
        }
    }
    function _sendCommand(cmd) {
        if (frame) {
            frame.contentWindow.postMessage(cmd, "*");
        }
    }
    function startFullScreen() {
        if (frame.requestFullscreen) {
            frame.requestFullscreen();
        } else if (frame.webkitRequestFullscreen) {
            frame.webkitRequestFullscreen(); //Chrome15+, Safari5.1+, Opera15+
        } else if (frame.mozRequestFullScreen) {
            frame.mozRequestFullScreen(); //FF10+
        } else if (frame.msRequestFullscreen) {
            frame.msRequestFullscreen(); //IE11+
        } else {
        }
    }
    function _loadSource(src, apf) {
        var limit = 20;
        (function chk() {
            if (linked) {
                _sendCommand({"command": "load","source":src,"autoplay":apf});
            } else {
                limit--;
                if (limit < 0) {
                    alert("failed at connectiong to SmartPlayer");
                } else {
                    glb.setTimeout(function () {
                        chk();
                    }, 200);
                }
            }
        })();
    }
    //
    var nv = glb.navigator;
    var mobile = (/win32/i).test(nv.platform) ? false :
            (/mac/i).test(nv.platform) ? false :
            (/iPad/i).test(nv.platform) ? true :
            (/iPhone/i).test(nv.platform) ? true :
            (/arm/i).test(nv.platform) ? true : false;
    //
    smartResponse[MSG_PING] = function () {
        frame = doc.getElementById(frameId);
        frame.contentWindow.postMessage({"command": "ping"}, "*");
        linked = true;
    };
    glb.addEventListener('message', function (m) {
        try {
            if (smartResponse[m.data.response]) {
                smartResponse[m.data.response].apply(smartResponse, m.data.param);
            }
        } catch (e) {
            report("error at handleMessageEvent():" + e);
        }
    }, false);
    //
    return {
        addResponseHandler: function (name, handler) {
            smartResponse[name] = handler;
        },
        sendCommand: function (cmd) {
            _sendCommand(cmd);
        },
        // pre defined command
        loadSource: function (src, autoplay) {
            if (mobile && linked) {
                linked = false;
                frame.name = "";
                frame.contentDocument.location.reload(true);
                report("reload iFrame");
            }
            _loadSource(src, autoplay);
        },
        ejectSource: function () {
            _sendCommand({"command": "eject"});
        },
        controlPlayer: function (code) { // "code" -2:Play -1:Pause 0:Stop 
            _sendCommand({"command": "control", "code": code});
        },
        getPosition: function (done) {
            if (!(["getPosCallback"] in smartResponse)) {
                smartResponse["getPosCallback"] = done;
            }
            _sendCommand({"command": "getPosition", "callback": "getPosCallback"});
        },
        setPosition: function (pos) {
            _sendCommand({"command": "setPosition", "position": pos});
        },
        getArea: function (done) {
            if (!(["getAreaCallback"] in smartResponse)) {
                smartResponse["getAreaCallback"] = done;
            }
            _sendCommand({"command": "getArea", "callback": "getAreaCallback"});
        },
        setArea: function (area) {
            _sendCommand({"command": "setArea", "area": area});
        },
        zoom: function (on) {
            _sendCommand({"command": "zoom", "code": on});
        },
        fullScreen: function () {
            startFullScreen();
        },
        loadSvgScript: function (url) {
            _sendCommand({"command": "loadSvgScript", "src": url});
        },
        runSvgFunction: function (name, param, done) {
            if (done) {
                var callback = name + "_callback";
                this.addResponseHandler(callback, function (s) {
                    if (done) {
                        done(s);
                    }
                });
            }
            _sendCommand({"command": "runSvgFunc", "name": name, "param": param, "callback": callback});
        },
        getStillPicture: function (done) {
            if (!(["getStillPictureCallback"] in smartResponse)) {
                smartResponse["getStillPictureCallback"] = done;
            }            
            _sendCommand({"command": "getStillPicture", "callback": "getStillPictureCallback"});            
        },
        setLoadedEvent: function (handler) {
            smartResponse[MSG_LOADED] = handler;
        },
        setSvgEvent: function (handler) {
            smartResponse[MSG_SVG_EVENT] = handler;
        },
        setMouseEvent: function (handler) {
            smartResponse[MSG_MOUSE_EVENT] = handler;
        },
        // auxiliary functions
        setReportEvent: function (handler) {
            reportNotify = handler;
            smartResponse[MSG_REPORT] = handler;
        },
        setTestbenchMode: function (on) {
            _sendCommand({"command": "setTestbench", "code": on});
        },
        test: function () {
            _sendCommand({"command": "test"});
        }
    };
}



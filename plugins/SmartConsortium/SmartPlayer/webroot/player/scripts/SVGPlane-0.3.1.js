////////////////////////////////////////////////////////////////
//
//  SVGPlane
//  
//  2017/01/28
//    change mouseEvent to pointEvent for touch device
//  2017/02/22
//    add remove all child element into clear function
//  
//  Copyright 2015 Chord-Works
////////////////////////////////////////////////////////////////

define(["Include", "Utility"], function(inc, util) {
    return {
        create: function(svgID, glb, doc) {
            var SVG_NS = "http://www.w3.org/2000/svg";
            var DBLCLICK_INTERVAL = 400;
            //
            var svgElement = doc.getElementById(svgID);
            //
            var mouseNotify; // stt=0:down stt=1:move stt=2:up stt=3:double-clicked
            var reportNotify;
            var resizeNotify;
            //
            var isMobile = util.isMobileDevice(glb);
            var svgL = 0, svgT = 0, svgW = 0, svgH = 0;
            var planeRatio = 1;
            var plane = {"x":0, "y":0, "w":0, "h":0}; // drawing plane
            var area = {"x":0, "y":0, "w":0, "h":0, "f":false};
            var showArea = true;
            var areaMarker = doc.createElementNS(SVG_NS, "rect");
            var minRespArea = (isMobile)? 50: 6;
            //
            var captured = false;
            var downPoint = {"x":0, "y":0};
            var dblClick = false;
            var startPointEN = (isMobile)? "touchstart": "mousedown";
            var movePointEN = (isMobile)? "touchmove": "mousemove";
            var endPointEN = (isMobile)? "touchend": "mouseup";
            //
            var testMarker = doc.createElementNS(SVG_NS, "rect");
            //
            function report(s) {
                if (reportNotify) {
                    reportNotify("SVGPlane:" + s);
                }
            }
            function resetPlane(ratio) {
                plane.w = svgW; plane.h = svgH; plane.x = 0; plane.y = 0;
                if (ratio !== 0) {
                    if (ratio < (svgW / svgH)) {
                        plane.w = ratio * plane.h;
                    } else {
                        plane.h = plane.w / ratio;
                    }
                    plane.x = (svgW - plane.w) / 2;
                    plane.y = (svgH - plane.h) / 2;  
                }
                planeRatio = ratio;      
            }
            function _clearArea() {
                area.f = false;
                area.x = 0; area.y = 0; area.w = 0; area.h = 0;
            }
            function setAreaMarker(view) {
                if (!view || !showArea) {
                    areaMarker.setAttribute("height", 0); 
                } else {
                    var ax = area.x * plane.w + plane.x;
                    var ay = area.y * plane.h + plane.y;
                    var aw = area.w * plane.w;
                    var ah = area.h * plane.h;
                    if (area.w < 0) {
                        aw = -aw; ax = ax - aw;
                    }
                    if (area.h < 0) {
                        ah = -ah; ay = ay - ah;
                    }
                    //    
                    areaMarker.setAttribute("x", ax);
                    areaMarker.setAttribute("y", ay);
                    areaMarker.setAttribute("width", aw);
                    areaMarker.setAttribute("height", ah);  
                }
            }
            function setTestMarker() {
                testMarker.setAttribute("x", 0);
                testMarker.setAttribute("y", 0);
                testMarker.setAttribute("width", svgW);
                testMarker.setAttribute("height", svgH);  
            }
            function getPointCoordinate(e) {
                var mx = 0, my = 0;
                if (isMobile) {  
                    mx = e.touches[0].clientX - svgL;
                    my = e.touches[0].clientY - svgT;
                } else {
                    mx = e.clientX - svgL;
                    my = e.clientY - svgT; 
                }  
                return {"x":mx, "y":my};
            }
            function pointDown(e) {
                e.preventDefault();
                if (e.target && e.target.setCapture) {
                    e.target.setCapture();
                }
                if (!isMobile && (e.button !== 0)) {
                    captured = false;
                    return;
                }
                captured = true;
                e.target.addEventListener(movePointEN, pointMove, false);       
                e.target.addEventListener(endPointEN, pointUp, false);
                //
                var p = getPointCoordinate(e);
                downPoint.x = p.x;
                downPoint.y = p.y;
                var stt = (dblClick)? inc.PS_DBLCLICK: inc.PS_DOWN;
                if (!dblClick) {
                    dblClick = true;
                    glb.setTimeout(function () {
                        dblClick = false;
                    }, DBLCLICK_INTERVAL);  
                    //
                    if (showArea) {
                        _clearArea();
                        setAreaMarker(false);        
                    }
                }
                if (mouseNotify) {
                    mouseNotify(stt, (p.x - plane.x) / plane.w, (p.y - plane.y) / plane.h);
                }
            }
            function pointMove(e) {
                if (!captured) { return; }
                //
                var p = getPointCoordinate(e);
                if (showArea && (plane.w > 0) && (plane.h > 0)) {
                    var w = p.x - downPoint.x;
                    var h = p.y - downPoint.y;
                    if (!area.f) {
                        area.f = Math.abs(w * h) > minRespArea;
                    } else {
                        area.x = (downPoint.x - plane.x) / plane.w;
                        area.y = (downPoint.y - plane.y) / plane.h;
                        area.w = w / plane.w;
                        area.h = h / plane.h;
                        setAreaMarker(true);
                    }
                }
                if (mouseNotify) {
                    mouseNotify(inc.PS_MOVE, (p.x - plane.x) / plane.w, (p.y - plane.y) / plane.h);
                }         
            }
            function pointUp(e) {
                if (!captured) { return; }
                if (e.target && e.target.releaseCapture) {
                    e.target.releaseCapture();
                }
                captured = false;
                //
                var p = getPointCoordinate(e);
                e.target.removeEventListener(movePointEN, pointMove, false); 
                e.target.removeEventListener(endPointEN, pointUp, false);
                p.x = e.clientX - svgL;
                p.y = e.clientY - svgT; 
                //
                if (mouseNotify) {
                    mouseNotify(inc.PS_UP, (p.x - plane.x) / plane.w, (p.y - plane.y) / plane.h);
                }         
            }
            function _resize() {
                svgElement.style.left = svgL + "px";
                svgElement.style.top = svgT + "px";
                svgElement.style.width = svgW + "px";
                svgElement.style.height = svgH + "px";
                //
                resetPlane(planeRatio);
                setAreaMarker(true);
                setTestMarker();
                if (resizeNotify) {
                    try {
                        resizeNotify(plane.x, plane.y, plane.w, plane.h);
                    } catch(e) {}
                }
            };
            //
            svgElement.setResizeEvent = function (callback) {
                resizeNotify = callback;            
            }; 
            _clearArea();
            areaMarker.setAttribute("stroke", "yellow");
            areaMarker.setAttribute("fill", "none");
            svgElement.appendChild(areaMarker);
            //
            testMarker.setAttribute("stroke", "white");
            testMarker.setAttribute("fill", "none");
            testMarker.style.strokeDasharray = "2,2";
            //
            svgElement.addEventListener(startPointEN, pointDown, false);                    
            return {
                resize: function (left, top, width, height) {
                    svgL = left;
                    svgT = top;
                    svgW = width - 2;
                    svgH = height - 2;
                    _resize();
                },
                clear: function () {
                    svgElement.removeEventListener(startPointEN, pointDown, false);
                    this.clearArea();
                    resetPlane(0);
                    // remove all child element
                    var pe = svgElement.parentElement;
                    var emptySvg = svgElement.cloneNode(false);
                    pe.removeChild(svgElement);
                    pe.appendChild(emptySvg);
                },
                setRenderSize: function (size) { // size[0]:width size[1]:height
                    if ((size[0] === 0) || (size[1] === 0)) {
                        resetPlane(0);
                    } else {
                        resetPlane(size[0] / size[1]);
                    }
                },
                clearArea: function () {
                    _clearArea();
                    setAreaMarker(false);
                },
                showArea: function (show) {
                    showArea = show;
                    setAreaMarker(true);
                },
                isAreaValid: function () {
                    return (area.x !== 0) || (area.y !== 0) || (area.w !== 0) || (area.h !== 0);
                },
                getArea: function () {
                    return (this.isAreaValid())? [area.x, area.y, area.w, area.h]: [0, 0, 1, 1];
                },
                setArea: function (areaArr) {
                    if (areaArr.length < 4) { return; }
                    area.x = areaArr[0];
                    area.y = areaArr[1];
                    area.w = areaArr[2];
                    area.h = areaArr[3];
                    this.showArea(true);
                },
                setMouseEvent: function (callback) {
                    mouseNotify = callback;
                },
                showMarker: function (show) {
                    if (show) {
                        svgElement.appendChild(testMarker);
                    } else {
                        svgElement.removeChild(testMarker);                
                    }
                },
                // auxiliary 
                setReportEvent: function (callback) {
                    reportNotify = callback;
                }
            };
        }
    };
});

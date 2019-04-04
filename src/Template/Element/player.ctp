<?php if($video->status == BUILD_STATUS_SUCCESS): ?>
    <div id="player_frame_container">
        <iframe id="SmartPlayer" class="player_frame" src="/smart_consortium/smart_player/player/smart_player.html" width="854px" height="480px"></iframe>
    </div>
<!--
    <div id="player_button_container">
        <button id="start"><?= __("Load Video") ?></button>
    </div>
    -->
<?php endif; ?>


<script>
    var SmartPlayer = (function(window, document, undefined) {
        var zooming = false;
        var player = document.getElementById('SmartPlayer');
        // message-sender function
        function sendPlayerCommand(cmd) {
            player.contentWindow.postMessage(cmd, "*");
        }
        // message-reciever function
        function handleMessageEvent(m) {
            try {
                if (smartResponse[m.data.response]) {
                    smartResponse[m.data.response].apply(null, m.data.param);
                }
            } catch (e) {
                alert('handleMessageEvent() of index.html:' + e);
            };
        }
        ////////
        // smartResponse function
        var smartResponse = {};
        smartResponse.displayPosition = function(pos) {
            postext.value = pos;
        };

        window.addEventListener('message', handleMessageEvent, false);
        return {
            playerPage: player,
            loadSource: function(src, autoplay) {
                 var cmd = {
                    "command": "load"
                };
                cmd.source = src;
                cmd.autoplay = autoplay;
                sendPlayerCommand(cmd);
            },
            ejectSource: function() {
                sendPlayerCommand({
                    "command": "eject"
                });
            },
            controlPlayer: function(code) { // "code" -2:Play -1:Pause 0:Stop
                sendPlayerCommand({
                    "command": "control",
                    "code": code
                });
            },
            getPosition: function(callback) {
                sendPlayerCommand({
                    "command": "getPosition",
                    "callback": callback
                });
            },
            setPosition: function(pos) {
                sendPlayerCommand({
                    "command": "setPosition",
                    "position": pos
                });
            },
            zoom: function() {
                zooming = !zooming;
                sendPlayerCommand({
                    "command": "zoom",
                    "code": zooming
                });
            },
        };
    })(window, document);
</script>
<script>
    var postext = document.getElementById('PositionText');
    //
    function LoadSource(url, apf) {
        SmartPlayer.loadSource(url, apf);
    }

    function LoadButton_onclick() {
        SmartPlayer.loadSource(document.getElementById("SourceText").value, true);
    }

    function EjectButton_onclick() {
        SmartPlayer.ejectSource();
    }

    function StopButton_onclick() {
        SmartPlayer.controlPlayer(0);
    }

    function PauseButton_onclick() {
        SmartPlayer.controlPlayer(-1);
    }

    function PlayButton_onclick() {
        SmartPlayer.controlPlayer(-2);
    }

    function GetPosButton_onclick() {
        SmartPlayer.getPosition("displayPosition");
    }

    function SetPosButton_onclick() {
        SmartPlayer.setPosition(postext.value);
    }

    function ZoomButton_onclick() {
        SmartPlayer.zoom();
    }

    function displayPosition(pos) {
        postext.value = pos;
    }
    // file drag'n drop
    window.addEventListener("dragenter", function(e) {
        e.preventDefault();
    });
    window.addEventListener("dragover", function(e) {
        e.preventDefault();
    });
    window.addEventListener("drop", function(e) {
        e.preventDefault();
        var files = e.dataTransfer.files;
        var file = files[0];
        if (!file) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function() {
            SmartPlayer.loadSource("text://" + reader.result, true);
        };
        reader.readAsText(file);
    });
</script>

<?php
/*
 * eavesdrop_listen.php
 * Renders a self-contained WebRTC page that connects to FreeSWITCH mod_verto
 * (wss://host:8082) and eavesdrops on the specified call channel via WebRTC audio.
 * Loaded inside an iframe injected by eavesdrop_web_intercept.php.
 */

// Require session + auth
require_once dirname(__DIR__, 2) . '/resources/require.php';
require_once 'resources/check_auth.php';

if (!permission_exists('call_active_eavesdrop')) {
    echo "access denied"; exit;
}

// Validate chan_uuid
$chan_uuid = preg_replace('/[^a-f0-9-]/', '', strtolower($_GET['chan_uuid'] ?? ''));
if (strlen($chan_uuid) !== 36) {
    echo "invalid channel"; exit;
}

// Look up extension credentials
$ext_number  = '';
$ext_pass    = '';
$domain_name = $_SESSION['domain_name'] ?? '';

if (!empty($_SESSION['user']['extension'][0]['extension_uuid'])) {
    $ext_uuid = $_SESSION['user']['extension'][0]['extension_uuid'];
    $sql = "SELECT extension, password FROM v_extensions
            WHERE extension_uuid = :extension_uuid
            AND domain_uuid = :domain_uuid
            LIMIT 1";
    $params = ['extension_uuid' => $ext_uuid, 'domain_uuid' => $_SESSION['domain_uuid']];
    $row = $database->select($sql, $params, 'row');
    if (!empty($row)) {
        $ext_number = $row['extension'];
        $ext_pass   = $row['password'];
    }
}

if (empty($ext_number) || empty($ext_pass)) {
    echo "<p style='color:#f66;font-family:sans-serif;'>No extension found for your account.</p>"; exit;
}

// Determine the Verto WSS URL — use the server's own hostname
$verto_host = $_SERVER['HTTP_HOST'] ?? $domain_name;
// Strip port if present
$verto_host = preg_replace('/:\d+$/', '', $verto_host);
$verto_wss  = 'wss://' . $verto_host . ':8082';

// Destination sent to FreeSWITCH dialplan
$verto_dest = 'eavesdrop_web_' . $chan_uuid;

// JSON-encode values safely for embedding in JS
$js_ext      = json_encode($ext_number . '@' . $domain_name);
$js_pass     = json_encode($ext_pass);
$js_wss      = json_encode($verto_wss);
$js_dest     = json_encode($verto_dest);
$js_chan_uuid = json_encode($chan_uuid);

?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: #1e1e2e;
    color: #cdd6f4;
    font-family: 'Segoe UI', sans-serif;
    font-size: 13px;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .title { font-size: 12px; color: #a6adc8; display: flex; align-items: center; gap: 6px; }
  .title .dot { width: 8px; height: 8px; border-radius: 50%; background: #6c7086; display: inline-block; }
  .title .dot.live { background: #a6e3a1; box-shadow: 0 0 4px #a6e3a1; animation: pulse 1.5s infinite; }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
  #status { font-size: 11px; color: #a6adc8; min-height: 14px; }
  .controls { display: flex; align-items: center; gap: 8px; }
  input[type=range] { flex: 1; accent-color: #89b4fa; }
  .btn {
    background: #313244; border: none; color: #cdd6f4; padding: 4px 10px;
    border-radius: 4px; cursor: pointer; font-size: 11px; flex-shrink: 0;
  }
  .btn:hover { background: #45475a; }
  .btn.danger { background: #f38ba8; color: #1e1e2e; }
  .btn.danger:hover { background: #eba0ac; }
  /* Mic button */
  #btn_mic { padding: 4px 8px; font-size: 15px; line-height: 1; }
  #btn_mic.muted   { color: #f38ba8; }  /* red = muted  */
  #btn_mic.talking { color: #a6e3a1; background: #2a3d2a; } /* green = live */
  #vol_icon { font-size: 14px; }
</style>
</head>
<body>
<div class="title">
  <span class="dot" id="status_dot"></span>
  <span>Web Eavesdrop</span>
  <span style="margin-left:auto;font-size:10px;color:#585b70;" id="chan_display"><?= htmlspecialchars(substr($chan_uuid, 0, 8)) ?>…</span>
</div>
<div id="status">Connecting…</div>
<audio id="remote_audio" autoplay></audio>
<div class="controls">
  <span id="vol_icon">🔊</span>
  <input type="range" id="volume" min="0" max="1" step="0.05" value="1"
         oninput="document.getElementById('remote_audio').volume=this.value">
  <!-- Mic muted by default; click to talk as participant -->
  <button class="btn muted" id="btn_mic" onclick="toggleMic()" title="Click to unmute and talk">🎤̶</button>
  <button class="btn danger" id="btn_stop" onclick="stopEavesdrop()">End</button>
</div>

<script>
(function() {
  'use strict';

  const VERTO_WSS  = <?= $js_wss ?>;
  const LOGIN      = <?= $js_ext ?>;
  const PASSWD     = <?= $js_pass ?>;
  const DEST       = <?= $js_dest ?>;
  const CHAN_UUID  = <?= $js_chan_uuid ?>;

  const sessId  = crypto.randomUUID ? crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
    const r = Math.random()*16|0; return (c==='x'?r:(r&0x3|0x8)).toString(16);
  });
  const callId  = crypto.randomUUID ? crypto.randomUUID() : sessId + '-call';

  let ws       = null;
  let pc       = null;
  let msgId    = 1;
  let micTrack = null;

  const statusEl = document.getElementById('status');
  const dotEl    = document.getElementById('status_dot');
  const audioEl  = document.getElementById('remote_audio');
  const micBtn   = document.getElementById('btn_mic');

  // Show the parent #cmd_response div (it defaults to display:none)
  try {
    const par = window.parent.document.getElementById('cmd_response');
    if (par) par.style.display = 'block';
  } catch(e) {}

  function setStatus(msg, live) {
    statusEl.textContent = msg;
    dotEl.className = 'dot' + (live ? ' live' : '');
  }

  function send(obj) {
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(obj));
    }
  }

  function rpc(method, params) {
    send({ jsonrpc: '2.0', method, params, id: msgId++ });
  }

  // Toggle microphone mute/unmute
  window.toggleMic = function() {
    if (!micTrack) return;
    micTrack.enabled = !micTrack.enabled;
    if (micTrack.enabled) {
      micBtn.className = 'btn talking';
      micBtn.title = 'Click to mute';
      micBtn.textContent = '🎤';
    } else {
      micBtn.className = 'btn muted';
      micBtn.title = 'Click to unmute and talk';
      micBtn.textContent = '🎤̶';
    }
  };

  function cleanup() {
    if (micTrack) { micTrack.stop(); micTrack = null; }
    if (pc) { pc.close(); pc = null; }
    if (ws) { ws.close(); ws = null; }
    audioEl.srcObject = null;
    try {
      const par = window.parent.document.getElementById('cmd_response');
      if (par) { par.style.display = 'none'; par.innerHTML = ''; }
    } catch(e) {}
  }

  window.stopEavesdrop = function() {
    setStatus('Disconnected', false);
    // Send verto.bye so FreeSWITCH tears down the channel, then clean up
    if (ws && ws.readyState === WebSocket.OPEN) {
      rpc('verto.bye', { callID: callId, dialogParams: { callID: callId } });
      setTimeout(cleanup, 300);
    } else {
      cleanup();
    }
  };

  async function startCall() {
    setStatus('Creating audio connection…', false);

    pc = new RTCPeerConnection({
      iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
    });

    // Get microphone — start muted so supervisor is silent until they choose to talk
    let localStream = null;
    try {
      localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
      micTrack = localStream.getAudioTracks()[0];
      micTrack.enabled = false; // muted until user clicks the mic button
      pc.addTrack(micTrack, localStream);
      // addTrack creates a sendrecv transceiver automatically
    } catch (e) {
      // No mic permission — fall back to listen-only
      pc.addTransceiver('audio', { direction: 'recvonly' });
      micBtn.style.display = 'none';
    }

    // Prefer PCMU/PCMA — same codec as the calls, no transcoding needed
    const audioTransceiver = pc.getTransceivers().find(t => t.receiver.track.kind === 'audio');
    if (audioTransceiver && RTCRtpReceiver.getCapabilities) {
      const caps = RTCRtpReceiver.getCapabilities('audio');
      if (caps) {
        const preferred = caps.codecs.filter(c =>
          c.mimeType === 'audio/PCMU' || c.mimeType === 'audio/PCMA'
        );
        const rest = caps.codecs.filter(c =>
          c.mimeType !== 'audio/PCMU' && c.mimeType !== 'audio/PCMA'
        );
        try { audioTransceiver.setCodecPreferences([...preferred, ...rest]); } catch(e) {}
      }
    }

    pc.ontrack = (ev) => {
      audioEl.srcObject = ev.streams[0];
      setStatus('Listening live', true);
    };

    pc.oniceconnectionstatechange = () => {
      if (pc && (pc.iceConnectionState === 'failed' || pc.iceConnectionState === 'disconnected')) {
        setStatus('Connection lost', false);
      }
    };

    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);

    // Wait for ICE gathering to complete before sending SDP
    await new Promise(resolve => {
      if (pc.iceGatheringState === 'complete') return resolve();
      pc.addEventListener('icegatheringstatechange', function handler() {
        if (pc.iceGatheringState === 'complete') {
          pc.removeEventListener('icegatheringstatechange', handler);
          resolve();
        }
      });
      setTimeout(resolve, 4000);
    });

    setStatus('Requesting eavesdrop…', false);

    rpc('verto.invite', {
      callID: callId,
      sdp: pc.localDescription.sdp,
      dialogParams: {
        callID:                callId,
        destination_number:    DEST,
        caller_id_name:        'WebEavesdrop',
        caller_id_number:      '0000',
        remote_caller_id_name: 'FreeSWITCH',
        remote_caller_id_number: '0000',
        useVideo:   false,
        useStereo:  false,
        dedEnc:     false,
        useCamera:  false,
        useMic:     true
      }
    });
  }

  function connect() {
    setStatus('Connecting to server…', false);
    ws = new WebSocket(VERTO_WSS);

    ws.onopen = () => {
      setStatus('Authenticating…', false);
      rpc('login', { login: LOGIN, passwd: PASSWD, sessid: sessId });
    };

    ws.onmessage = async (ev) => {
      let msg;
      try { msg = JSON.parse(ev.data); } catch(e) { return; }

      // Login response
      if (msg.id && msg.result && msg.result.message === 'logged in') {
        setStatus('Authenticated — starting audio…', false);
        await startCall();
        return;
      }

      // Auth error
      if (msg.id && msg.error && msg.error.message && msg.error.message.toLowerCase().includes('auth')) {
        setStatus('Auth error — check extension credentials', false);
        return;
      }

      const method = msg.method || '';

      // Answer from FreeSWITCH — set remote SDP
      if (method === 'verto.answer' && msg.params && msg.params.callID === callId) {
        try {
          await pc.setRemoteDescription({ type: 'answer', sdp: msg.params.sdp });
        } catch(e) {
          setStatus('SDP error: ' + e.message, false);
        }
        return;
      }

      // Hangup from FreeSWITCH side
      if (method === 'verto.bye' && msg.params && msg.params.callID === callId) {
        setStatus('Call ended by remote', false);
        if (pc) { pc.close(); pc = null; }
        setTimeout(cleanup, 5000);
        return;
      }

      // Respond to ping
      if (method === 'verto.ping') {
        send({ jsonrpc: '2.0', id: msg.id, result: { method: 'verto.pong' } });
        return;
      }
    };

    ws.onerror = () => setStatus('WebSocket error — is port 8082 open?', false);
    ws.onclose = () => {
      if (pc) setStatus('Connection closed', false);
    };
  }

  connect();
})();
</script>
</body>
</html>

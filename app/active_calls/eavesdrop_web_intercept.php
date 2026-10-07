<?php
/*
 * eavesdrop_web_intercept.php  (active_calls/ — new WebSocket-based page)
 *
 * Loaded via .user.ini auto_prepend_file. Runs before every PHP script in
 * app/active_calls/.
 *
 * Behaviour depends entirely on the call_active_eavesdrop_web permission:
 *
 *   PERMISSION OFF — this file does nothing. The page behaves exactly as
 *                    FusionPBX shipped it (click eavesdrop button → original
 *                    websocket request → phone originate).
 *
 *   PERMISSION ON  — only active_calls.php is patched: appends a <script>
 *                    that captures clicks on every btn_eavesdrop_* button
 *                    (before the page's own listener runs) and shows a
 *                    Web / Phone / Cancel modal.
 *
 *                    Web   → opens /app/calls_active/eavesdrop_listen.php
 *                            (the existing WebRTC listener from the old page)
 *                            inside a floating iframe in the bottom-right.
 *                    Phone → re-issues the same websocket request the page
 *                            would have sent: client.request('active.calls',
 *                            'eavesdrop', {...}) — resulting in the normal
 *                            FreeSWITCH phone originate.
 *
 * NO existing FusionPBX files are modified.  The WebRTC iframe, the
 * eavesdrop_web_<uuid> → three_way() dialplan, and the permission row in
 * v_permissions are all shared with the old calls_active page.
 */

$_script = basename($_SERVER['SCRIPT_FILENAME']);

// Only patch active_calls.php — everything else in this directory runs untouched.
if ($_script !== 'active_calls.php') return;

// Bootstrap FusionPBX so we can check the permission. require.php is idempotent.
if (!class_exists('database')) {
    require_once dirname(__DIR__, 2) . '/resources/require.php';
}

// Without the web eavesdrop permission, do nothing — page behaves as shipped.
if (!permission_exists('call_active_eavesdrop_web')) return;

// Pull the translated "Eavesdrop" label so our Phone fallback uses the same
// caller_id_name the page would have — otherwise the eavesdrop leg would
// show up in the active calls list (line 846 of active_calls.php filters
// rows by this exact string).
$eav_label = 'Eavesdrop';
if (class_exists('text')) {
    $_t = (new text())->get();
    if (!empty($_t['label-eavesdrop'])) {
        $eav_label = $_t['label-eavesdrop'];
    }
}
$js_eav_label = json_encode($eav_label, JSON_UNESCAPED_UNICODE);

$js_override = <<<JSOVERRIDE
<script>
(function() {
  'use strict';

  const EAV_LABEL = $js_eav_label;

  function ensureModal() {
    if (document.getElementById('eav_overlay')) return;

    const s = document.createElement('style');
    s.textContent =
      '#eav_overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;align-items:center;justify-content:center;}' +
      '#eav_overlay.show{display:flex;}' +
      '#eav_box{background:#1e1e2e;border-radius:10px;padding:22px 26px;color:#cdd6f4;font-family:"Segoe UI",sans-serif;font-size:14px;min-width:290px;box-shadow:0 4px 24px rgba(0,0,0,.7);}' +
      '#eav_box h3{margin:0 0 4px;font-size:15px;}' +
      '#eav_box p{margin:0 0 18px;font-size:12px;color:#a6adc8;}' +
      '#eav_btns{display:flex;gap:10px;}' +
      '#eav_btns button{flex:1;padding:8px 0;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;}' +
      '#btn_eav_web{background:#89b4fa;color:#1e1e2e;}' +
      '#btn_eav_web:hover{background:#74c7ec;}' +
      '#btn_eav_phone{background:#313244;color:#cdd6f4;}' +
      '#btn_eav_phone:hover,#btn_eav_cancel:hover{background:#45475a;}' +
      '#btn_eav_cancel{background:#313244;color:#a6adc8;}' +
      '#cmd_response{position:fixed;bottom:20px;right:20px;z-index:9999;width:360px;background:#1e1e2e;padding:0;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.4);overflow:hidden;}' +
      '#eav_drag_handle{cursor:move;user-select:none;padding:6px 10px;background:#11111b;color:#a6adc8;font:11px/1.3 "Segoe UI",sans-serif;display:flex;align-items:center;gap:6px;border-top-left-radius:8px;border-top-right-radius:8px;}' +
      '#eav_drag_handle .grip{color:#585b70;letter-spacing:-1px;}' +
      '#eav_drag_handle.dragging{cursor:grabbing;}';
    document.head.appendChild(s);

    const m = document.createElement('div');
    m.id = 'eav_overlay';
    m.innerHTML =
      '<div id="eav_box">' +
        '<h3>Eavesdrop on this call?</h3>' +
        '<p>Choose how you want to listen in.</p>' +
        '<div id="eav_btns">' +
          '<button id="btn_eav_web" type="button">Web</button>' +
          '<button id="btn_eav_phone" type="button">Phone</button>' +
          '<button id="btn_eav_cancel" type="button">Cancel</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(m);

    m.addEventListener('click', (e) => { if (e.target === m) closeEavModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeEavModal(); });
    document.getElementById('btn_eav_cancel').addEventListener('click', closeEavModal);

    document.getElementById('btn_eav_web').addEventListener('click', () => {
      const uuid = window._eav_uuid;
      closeEavModal();
      if (uuid) openWebEavesdrop(uuid);
    });

    document.getElementById('btn_eav_phone').addEventListener('click', () => {
      const uuid = window._eav_uuid;
      closeEavModal();
      if (uuid) doPhoneEavesdrop(uuid);
    });
  }

  function closeEavModal() {
    const ov = document.getElementById('eav_overlay');
    if (ov) ov.classList.remove('show');
    window._eav_uuid = '';
  }

  // Open the existing /app/calls_active/eavesdrop_listen.php iframe in a floating
  // container. eavesdrop_listen.php looks for an element with id "cmd_response"
  // in its parent and shows/hides/empties it on open/close — naming the container
  // the same id means End button and iframe cleanup work without modification.
  function openWebEavesdrop(uuid) {
    const existing = document.getElementById('cmd_response');
    if (existing) existing.remove();

    const container = document.createElement('div');
    container.id = 'cmd_response';
    container.style.display = 'block';

    const handle = document.createElement('div');
    handle.id = 'eav_drag_handle';
    handle.innerHTML = '<span class="grip">⋮⋮</span><span>Web Eavesdrop — drag to move</span>';

    const iframe = document.createElement('iframe');
    iframe.src = '/app/calls_active/eavesdrop_listen.php?chan_uuid=' + encodeURIComponent(uuid) + '&_t=' + Date.now();
    iframe.style.cssText = 'width:100%;height:180px;border:none;display:block;';
    iframe.setAttribute('allow', 'microphone;autoplay');

    container.appendChild(handle);
    container.appendChild(iframe);
    document.body.appendChild(container);

    makeDraggable(container, handle, iframe);
  }

  // Drag handler. Switches container from bottom/right anchoring to top/left on
  // first mousedown so absolute coordinates can be tracked. Disables pointer
  // events on the iframe while dragging — otherwise the iframe captures the
  // mouse the instant the pointer crosses it and the drag ends prematurely.
  function makeDraggable(el, handle, iframe) {
    let dragging = false, sx = 0, sy = 0, startLeft = 0, startTop = 0;

    const onMove = (e) => {
      if (!dragging) return;
      const rect = el.getBoundingClientRect();
      let newLeft = startLeft + (e.clientX - sx);
      let newTop  = startTop  + (e.clientY - sy);
      newLeft = Math.max(0, Math.min(window.innerWidth  - rect.width,  newLeft));
      newTop  = Math.max(0, Math.min(window.innerHeight - rect.height, newTop));
      el.style.left = newLeft + 'px';
      el.style.top  = newTop  + 'px';
    };

    const onUp = () => {
      if (!dragging) return;
      dragging = false;
      handle.classList.remove('dragging');
      if (iframe) iframe.style.pointerEvents = '';
      document.removeEventListener('mousemove', onMove);
      document.removeEventListener('mouseup',   onUp);
    };

    handle.addEventListener('mousedown', (e) => {
      if (e.button !== 0) return;
      const rect = el.getBoundingClientRect();
      el.style.left   = rect.left + 'px';
      el.style.top    = rect.top  + 'px';
      el.style.right  = 'auto';
      el.style.bottom = 'auto';
      startLeft = rect.left;
      startTop  = rect.top;
      sx = e.clientX;
      sy = e.clientY;
      dragging = true;
      handle.classList.add('dragging');
      if (iframe) iframe.style.pointerEvents = 'none';
      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup',   onUp);
      e.preventDefault();
    });
  }

  // Re-issue the same websocket request the page's own button handler would have
  // sent. Page globals (client, extension) are visible to this script because all
  // classic <script> tags share the same script-global scope.
  function doPhoneEavesdrop(uuid) {
    if (typeof client === 'undefined' || !client || typeof client.request !== 'function') {
      console.warn('eavesdrop_web_intercept: websocket client not ready');
      return;
    }
    if (typeof extension === 'undefined' || !extension || !extension.extension_destination) {
      console.warn('eavesdrop_web_intercept: no extension attached to this user');
      return;
    }
    const caller_id_num = (document.getElementById('caller_id_number_' + uuid) || {}).textContent || '';
    const dest_num      = (document.getElementById('destination_'       + uuid) || {}).textContent || '';
    client.request('active.calls', 'eavesdrop', {
      unique_id:                  uuid,
      origination_caller_id_name: EAV_LABEL,
      origination_caller_contact: extension.extension_destination,
      caller_caller_id_number:    caller_id_num,
      caller_destination_number:  dest_num
    });
  }

  // Capture-phase listener: fires BEFORE the page's own per-button click listener,
  // so stopImmediatePropagation prevents the original phone originate from firing.
  // Works for buttons added dynamically by new_call() too, since it listens on
  // document rather than on specific buttons.
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('button[id^="btn_eavesdrop_"]');
    if (!btn) return;
    const uuid = btn.id.substring('btn_eavesdrop_'.length);
    // Ignore the hidden template button (id is exactly "btn_eavesdrop", no uuid suffix).
    if (!uuid) return;

    e.stopImmediatePropagation();
    e.preventDefault();
    ensureModal();
    window._eav_uuid = uuid;
    document.getElementById('eav_overlay').classList.add('show');
    document.getElementById('btn_eav_web').focus();
  }, true);
})();
</script>
JSOVERRIDE;

ob_start(function($output) use ($js_override) {
    return $output . $js_override;
});

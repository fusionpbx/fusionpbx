<?php
/*
 * eavesdrop_web_intercept.php
 * Loaded via .user.ini auto_prepend_file — runs before every PHP script in
 * the calls_active/ directory.
 *
 * Behaviour depends entirely on the call_active_eavesdrop_web permission:
 *
 *   PERMISSION OFF — this file does nothing. Every script runs exactly as
 *                    FusionPBX shipped it (original confirm() dialog, phone
 *                    originate, etc.).
 *
 *   PERMISSION ON  — two interception cases:
 *
 *   1. calls_active_inc.php — appends a <script> that replaces eavesdrop_call()
 *      with a Web / Phone / Cancel modal, bypassing the original confirm() dialog.
 *      Web opens the browser WebRTC listener; Phone falls through to the original
 *      SIP phone originate; Cancel dismisses.
 *
 *   2. calls_exec.php?action=eavesdrop — returns the WebRTC iframe HTML instead
 *      of letting calls_exec.php do a phone originate.
 *      Passing &mode=phone bypasses the intercept and falls through to original.
 *
 * NO existing FusionPBX files were modified to enable this feature.
 */

$_script = basename($_SERVER['SCRIPT_FILENAME']);

// Only intercept the two relevant scripts — exit immediately for everything else.
if ($_script !== 'calls_active_inc.php' && $_script !== 'calls_exec.php') return;

// Bootstrap FusionPBX so we can check the permission.
// require.php is idempotent — safe to call before the main script runs.
if (!class_exists('database')) {
    require_once dirname(__DIR__, 2) . '/resources/require.php';
}

// If the web eavesdrop permission is not granted, do absolutely nothing —
// let FusionPBX behave exactly as it did before this feature existed.
if (!permission_exists('call_active_eavesdrop_web')) return;

// ── Case 1: replace eavesdrop_call() in calls_active_inc.php ─────────────────
if ($_script === 'calls_active_inc.php') {
    $js_override = <<<'JSOVERRIDE'
<script>
(function() {
  // Inject modal styles + DOM once (safe to re-run on AJAX refresh — guard by id)
  if (!document.getElementById('eav_overlay')) {
    var s = document.createElement('style');
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
      '#btn_eav_cancel{background:#313244;color:#a6adc8;}';
    document.head.appendChild(s);

    var m = document.createElement('div');
    m.id = 'eav_overlay';
    m.innerHTML =
      '<div id="eav_box">' +
        '<h3>Eavesdrop on this call?</h3>' +
        '<p>Choose how you want to listen in.</p>' +
        '<div id="eav_btns">' +
          '<button id="btn_eav_web">Web</button>' +
          '<button id="btn_eav_phone">Phone</button>' +
          '<button id="btn_eav_cancel">Cancel</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(m);

    m.addEventListener('click', function(e) { if (e.target === m) closeEavModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeEavModal(); });
    document.getElementById('btn_eav_cancel').addEventListener('click', closeEavModal);

    document.getElementById('btn_eav_web').addEventListener('click', function() {
      var ext = window._eav_ext, uuid = window._eav_uuid;
      closeEavModal();
      if (!ext || !uuid) return;
      send_cmd('calls_exec.php?action=eavesdrop&ext=' + encodeURIComponent(ext)
             + '&chan_uuid=' + encodeURIComponent(uuid) + '&destination=');
      var c = document.getElementById('cmd_response');
      if (c) {
        c.style.cssText = 'display:block;position:fixed;bottom:20px;right:20px;'
          + 'z-index:9999;width:360px;background:#fff;padding:10px;'
          + 'border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.4);';
      }
    });

    document.getElementById('btn_eav_phone').addEventListener('click', function() {
      var ext = window._eav_ext, uuid = window._eav_uuid;
      closeEavModal();
      if (!ext || !uuid) return;
      var dest = (document.getElementById('eavesdrop_dest') || {}).value || '';
      send_cmd('calls_exec.php?action=eavesdrop&mode=phone&ext=' + encodeURIComponent(ext)
             + '&chan_uuid=' + encodeURIComponent(uuid) + '&destination=' + encodeURIComponent(dest));
    });
  }

  function closeEavModal() {
    document.getElementById('eav_overlay').classList.remove('show');
    window._eav_ext = ''; window._eav_uuid = '';
  }

  // Replace eavesdrop_call — shows Web/Phone/Cancel instead of the browser confirm()
  window.eavesdrop_call = function(ext, chan_uuid) {
    if (!ext || !chan_uuid) return;
    window._eav_ext  = ext;
    window._eav_uuid = chan_uuid;
    document.getElementById('eav_overlay').classList.add('show');
    document.getElementById('btn_eav_web').focus();
  };

  // Patch every eavesdrop button to bypass the confirm() baked into its onclick.
  // This script runs after calls_active_inc.php HTML is inserted into the DOM,
  // so the buttons are present. We extract ext/uuid from the onclick string and
  // replace it with a direct call to our modal function.
  document.querySelectorAll('button[onclick*="eavesdrop_call"]').forEach(function(btn) {
    var m = btn.getAttribute('onclick').match(/eavesdrop_call\('([^']+)','([^']+)'\)/);
    if (!m) return;
    var ext = m[1], uuid = m[2];
    btn.setAttribute('onclick', '');
    btn.addEventListener('click', function(e) {
      e.stopImmediatePropagation();
      window.eavesdrop_call(ext, uuid);
    });
  });
})();
</script>
JSOVERRIDE;
    ob_start(function($output) use ($js_override) {
        return $output . $js_override;
    });
    return; // let calls_active_inc.php run normally
}

// ── Case 2: intercept eavesdrop action in calls_exec.php ─────────────────────
if (($_REQUEST['action'] ?? '') !== 'eavesdrop') return;

// mode=phone → fall through to original phone originate
if (($_GET['mode'] ?? '') === 'phone') return;

// Validate chan_uuid
$chan_uuid = preg_replace('/[^a-f0-9-]/', '', strtolower($_GET['chan_uuid'] ?? ''));
if (strlen($chan_uuid) !== 36) return;

// Look up the logged-in user's first extension + SIP password
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

// No extension credentials — fall through to original phone behaviour
if (empty($ext_number) || empty($ext_pass)) return;

$iframe_src = '/app/calls_active/eavesdrop_listen.php'
    . '?chan_uuid=' . urlencode($chan_uuid)
    . '&_t=' . time();

echo '<iframe id="eavesdrop_web_frame" src="' . htmlspecialchars($iframe_src) . '"'
    . ' style="width:340px;height:160px;border:none;"'
    . ' allow="microphone;autoplay"'
    . '></iframe>';

exit;

<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX
*/

/**
 * edit_nav
 *
 * Adds Previous/Next arrow buttons to record edit pages so the user can walk
 * from one record to the next without bouncing back to the list page. The
 * navigation walks the *exact* list the user last saw on the list page —
 * including search filters, sort order, pagination and (where applicable)
 * Show All cross-domain views.
 *
 * Mechanism:
 *
 *   - List pages call edit_nav::snapshot() once per request, immediately after
 *     fetching their rows. The ordered list of record uuids is stored in the
 *     session under a per-module key.
 *
 *   - Edit pages call edit_nav::render() inside the action bar. It looks up
 *     the snapshot, finds the current record's neighbours, and emits two
 *     icon-only chevron buttons plus a small "n / N" position indicator.
 *     Alt+Left / Alt+Right keyboard shortcuts are also wired up.
 *
 *   - If no fresh snapshot exists (deep link, expired session, etc.) the
 *     render call returns an empty string and the page is unchanged.
 *
 * Behaviour is therefore strictly additive — no change for users who never
 * visit the list page first.
 */
class edit_nav {

	/** How long a snapshot is considered fresh (seconds). */
	const TTL_SECONDS = 3600;

	/** Top-level session key under which all snapshots are stored. */
	const SESSION_KEY = 'edit_nav';

	/**
	 * Capture the ordered list of uuids rendered on a list page. Call once
	 * per request from the list script, right after the rows have been
	 * loaded.
	 *
	 * @param string $module_key  Unique key for this list view (e.g. 'extensions',
	 *                            'dialplans:<app_uuid>'). Edit pages must use the
	 *                            same key when calling render().
	 * @param array  $rows        Array of associative rows from the list query.
	 * @param string $uuid_column Name of the primary-key column on each row.
	 */
	public static function snapshot($module_key, $rows, $uuid_column) {
		if (empty($module_key) || empty($uuid_column) || !is_array($rows)) {
			return;
		}
		if (session_status() !== PHP_SESSION_ACTIVE) {
			return;
		}
		$order = [];
		$seen = [];
		foreach ($rows as $row) {
			if (!is_array($row) || empty($row[$uuid_column])) {
				continue;
			}
			$uuid = strtolower($row[$uuid_column]);
			if (!isset($seen[$uuid])) {
				$seen[$uuid] = true;
				$order[] = $uuid;
			}
		}
		if (empty($order)) {
			return;
		}
		$_SESSION[self::SESSION_KEY][$module_key] = [
			'order' => $order,
			'time'  => time(),
		];
	}

	/**
	 * Render the Previous / Next / position controls for an edit page's
	 * action bar. Returns '' (the empty string) when there is nothing to
	 * render so callers can safely 'echo' the result unconditionally.
	 *
	 * @param array $options Keys:
	 *   - module_key  (string) session key set by snapshot()
	 *   - script      (string) basename of the edit script (e.g. 'extension_edit.php')
	 *   - id          (string) uuid of the record currently being edited
	 *   - passthrough (array)  optional: GET param names to preserve in the
	 *                          generated prev/next URLs. Defaults to
	 *                          ['order_by', 'order', 'page', 'search'].
	 *
	 * @return string HTML, or '' if nothing should be rendered.
	 */
	public static function render($options) {
		$module_key  = $options['module_key'] ?? '';
		$script      = $options['script'] ?? '';
		$id          = strtolower((string)($options['id'] ?? ''));
		$passthrough = $options['passthrough'] ?? ['order_by', 'order', 'page', 'search'];

		if ($module_key === '' || $script === '' || $id === '') {
			return '';
		}

		$snap = $_SESSION[self::SESSION_KEY][$module_key] ?? null;
		if (!is_array($snap) || empty($snap['order']) || !is_array($snap['order'])) {
			return '';
		}
		if ((time() - ($snap['time'] ?? 0)) > self::TTL_SECONDS) {
			return '';
		}

		$order = $snap['order'];
		$total = count($order);
		if ($total < 2) {
			return '';
		}

		$idx = array_search($id, $order, true);
		if ($idx === false) {
			return '';
		}

		$position  = $idx + 1;
		$prev_uuid = $idx > 0          ? $order[$idx - 1] : '';
		$next_uuid = $idx < $total - 1 ? $order[$idx + 1] : '';

		//build the URL query string that preserves list-page filters
		$query = [];
		foreach ($passthrough as $key) {
			if (!empty($_REQUEST[$key])) {
				$query[$key] = $_REQUEST[$key];
			}
		}

		$build_url = function ($uuid) use ($script, $query) {
			return $script . '?' . http_build_query(array_merge(['id' => $uuid], $query));
		};

		$prev_url = $prev_uuid !== '' ? $build_url($prev_uuid) : '';
		$next_url = $next_uuid !== '' ? $build_url($next_uuid) : '';

		$prev_title = 'Previous record  ('.$position.' of '.$total.')';
		$next_title = 'Next record  ('.$position.' of '.$total.')';

		$html  = '';
		$html .= self::button('btn_edit_nav_prev', 'chevron-left',  $prev_url, $prev_title, '15px');
		$html .= self::button('btn_edit_nav_next', 'chevron-right', $next_url, $next_title, '2px');
		$html .= "<span id='edit_nav_position' style='margin-left: 10px; font-size: 12px; color: #888; vertical-align: middle;'>"
		      .  htmlspecialchars($position.' / '.$total, ENT_QUOTES)
		      .  "</span>";

		//keyboard shortcuts: Alt+Left / Alt+Right
		$payload = json_encode(['prev' => $prev_url, 'next' => $next_url], JSON_UNESCAPED_SLASHES);
		$html .= "<script>"
		      .  "(function(){var n=".$payload.";"
		      .  "document.addEventListener('keydown',function(e){"
		      .  "if(!e.altKey||e.ctrlKey||e.metaKey||e.shiftKey)return;"
		      .  "var t=(e.target&&e.target.tagName)||'';"
		      .  "if(t==='INPUT'||t==='TEXTAREA'||t==='SELECT')return;"
		      .  "if(e.key==='ArrowLeft'&&n.prev){e.preventDefault();window.location.href=n.prev;}"
		      .  "if(e.key==='ArrowRight'&&n.next){e.preventDefault();window.location.href=n.next;}"
		      .  "});})();"
		      .  "</script>";

		return $html;
	}

	/**
	 * Render a single icon-only navigation button.
	 *
	 * @param string $id          DOM id
	 * @param string $icon        Font Awesome icon name (without "fa-" prefix)
	 * @param string $url         Destination URL (empty = disabled state)
	 * @param string $title       Tooltip / aria-label
	 * @param string $margin_left CSS margin-left value
	 *
	 * @return string Button HTML.
	 */
	private static function button($id, $icon, $url, $title, $margin_left) {
		$icon_html = "<span class='fa-solid fa-".htmlspecialchars($icon, ENT_QUOTES)." fa-fw'></span>";
		$title_attr = htmlspecialchars($title, ENT_QUOTES);

		if ($url === '') {
			$style = "margin-left: ".$margin_left."; opacity: 0.4; cursor: not-allowed;";
			return "<button type='button' id='".$id."' class='btn btn-default disabled' "
			     . "disabled='disabled' title='".$title_attr."' aria-label='".$title_attr."' "
			     . "style='".$style."'>".$icon_html."</button>";
		}

		$style = "margin-left: ".$margin_left.";";
		return "<a href='".htmlspecialchars($url, ENT_QUOTES)."'>"
		     . "<button type='button' id='".$id."' class='btn btn-default' "
		     . "title='".$title_attr."' aria-label='".$title_attr."' "
		     . "style='".$style."'>".$icon_html."</button></a>";
	}
}

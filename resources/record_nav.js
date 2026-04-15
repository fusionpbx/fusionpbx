/**
 * FusionPBX Record Navigation
 *
 * Provides previous/next record navigation on edit pages.
 *
 * On list pages: captures the ordered set of record URLs from tr.list-row[href]
 * and saves them to sessionStorage (tab-scoped).
 *
 * On edit pages: reads the stored set, locates the current record by ?id=,
 * and injects a prev/counter/next widget into div#action_bar > div.actions.
 */
(function () {
	'use strict';

	/**
	 * Extract the value of a named query parameter from a URL string.
	 */
	function getParam(url, name) {
		var idx = url.indexOf('?');
		if (idx === -1) { return null; }
		var pairs = url.slice(idx + 1).split('&');
		for (var i = 0; i < pairs.length; i++) {
			var parts = pairs[i].split('=');
			if (decodeURIComponent(parts[0]) === name) {
				return parts.length > 1 ? decodeURIComponent(parts[1].replace(/\+/g, ' ')) : '';
			}
		}
		return null;
	}

	/**
	 * Extract the script filename from a relative URL (e.g. "extension_edit.php").
	 */
	function getBasename(url) {
		var path = url.split('?')[0];
		return path.substring(path.lastIndexOf('/') + 1);
	}

	/**
	 * Build the sessionStorage key for a given edit-page basename.
	 */
	function storageKey(basename) {
		return 'record_nav_' + basename;
	}

	/**
	 * LIST PAGE: collect all tr.list-row[href] entries and save to sessionStorage.
	 */
	function captureList() {
		var rows = document.querySelectorAll('tr.list-row[href]');
		if (!rows.length) { return; }

		var records = [];
		var editBase = null;

		for (var i = 0; i < rows.length; i++) {
			var href = rows[i].getAttribute('href');
			if (!href) { continue; }
			// Only capture rows that have an ?id= param (edit links, not void/empty hrefs)
			if (getParam(href, 'id') === null) { continue; }
			records.push(href);
			if (!editBase) {
				editBase = getBasename(href);
			}
		}

		if (!editBase || !records.length) { return; }

		var data = {
			editBase: editBase,
			listUrl: window.location.pathname.substring(window.location.pathname.lastIndexOf('/') + 1)
				+ (window.location.search || ''),
			records: records
		};

		try {
			sessionStorage.setItem(storageKey(editBase), JSON.stringify(data));
		} catch (e) {
			// sessionStorage unavailable (private mode quota, etc.) — silently skip
		}
	}

	/**
	 * EDIT PAGE: read sessionStorage and inject the nav widget into the action bar.
	 */
	function injectNav() {
		var currentId = getParam(window.location.search, 'id');
		if (!currentId) { return; }

		var currentBase = getBasename(window.location.pathname);
		var key = storageKey(currentBase);

		var raw;
		try {
			raw = sessionStorage.getItem(key);
		} catch (e) {
			return;
		}
		if (!raw) { return; }

		var data;
		try {
			data = JSON.parse(raw);
		} catch (e) {
			return;
		}
		if (!data.records || !data.records.length) { return; }

		// Find the current record index by matching the ?id= parameter
		var idx = -1;
		for (var i = 0; i < data.records.length; i++) {
			if (getParam(data.records[i], 'id') === currentId) {
				idx = i;
				break;
			}
		}
		if (idx === -1) { return; }

		var actionsDiv = document.querySelector('div#action_bar > div.actions');
		if (!actionsDiv) { return; }

		var total   = data.records.length;
		var prevUrl = idx > 0             ? data.records[idx - 1] : null;
		var nextUrl = idx < total - 1     ? data.records[idx + 1] : null;

		// Build the widget HTML using existing FusionPBX btn classes + Font Awesome icons
		var prevBtn = prevUrl
			? "<a href='" + escHtml(prevUrl) + "'>"
				+ "<button type='button' class='btn btn-default' title='Previous record'>"
				+ "<span class='fa-solid fa-chevron-left fa-fw'></span>"
				+ "</button></a>"
			: "<button type='button' class='btn btn-default disabled' disabled title='No previous record'>"
				+ "<span class='fa-solid fa-chevron-left fa-fw'></span>"
				+ "</button>";

		var nextBtn = nextUrl
			? "<a href='" + escHtml(nextUrl) + "'>"
				+ "<button type='button' class='btn btn-default' title='Next record'>"
				+ "<span class='fa-solid fa-chevron-right fa-fw'></span>"
				+ "</button></a>"
			: "<button type='button' class='btn btn-default disabled' disabled title='No next record'>"
				+ "<span class='fa-solid fa-chevron-right fa-fw'></span>"
				+ "</button>";

		var counter = "<span class='record-nav-pos'>" + (idx + 1) + " / " + total + "</span>";

		var widget = "<div class='record-nav'>" + prevBtn + counter + nextBtn + "</div>";

		// Insert as first child of div.actions so it appears at the far-right edge
		actionsDiv.insertAdjacentHTML('afterbegin', widget);
	}

	function escHtml(str) {
		return str
			.replace(/&/g, '&amp;')
			.replace(/'/g, '&#39;')
			.replace(/"/g, '&quot;');
	}

	// Run after the DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	function init() {
		captureList(); // no-op on non-list pages
		injectNav();   // no-op on non-edit pages (or when no sessionStorage data exists)
	}

}());

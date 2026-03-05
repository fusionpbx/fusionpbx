<?php

interface logout_event {

	/**
	 * Called from the logout.php page
	 *
	 * @param settings $settings The settings object containing configuration values
	 *
	 * @return never
	 */
	public static function on_logout(settings $settings);
}

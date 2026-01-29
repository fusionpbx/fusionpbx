<?php

/**
 * event_guard_interface class
 *
*/
interface event_guard_interface {
	public function block_add(string $ip_address, string $filter) : bool;
	public function block_delete(string $ip_address, string $filter) : bool;
	public function block_exists(string $ip_address, string $filter) : bool;
}

<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\inventory\transaction;

use pocketmine\inventory\Inventory;

interface TransactionQueue {

	const DEFAULT_ALLOWED_RETRIES = 5;

	/**
	 * @return Inventory[]
	 */
	public function getInventories();

	/**
	 * @return \SplQueue
	 */
	public function getTransactions();

	/**
	 * @return int
	 */
	public function getTransactionCount();

	/**
	 * @param Transaction $transaction
	 *
	 * Adds a transaction to the queue
	 */
	public function addTransaction(Transaction $transaction);

	/**
	 * Handles transaction queue execution
	 */
	public function execute();

}
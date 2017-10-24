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

namespace pocketmine\inventory;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\Player;

class SimpleTransactionQueue implements TransactionQueue {

	/** @var Player[] */
	protected $player = null;

	/** @var \SplQueue */
	protected $transactionQueue;

	/** @var \SplQueue */
	protected $transactionsToRetry;

	/** @var Inventory[] */
	protected $inventories;

	/** @var float */
	protected $lastUpdate = -1;

	/** @var int */
	protected $transactionCount = 0;

	/**
	 * @param Player $player
	 */
	public function __construct(Player $player = null) {
		$this->player = $player;
		$this->transactionQueue = new \SplQueue();
		$this->transactionsToRetry = new \SplQueue();
	}

	/**
	 * @return Player
	 */
	public function getPlayer() : Player {
		return $this->player;
	}

	/**
	 * @return Inventory[]
	 */
	public function getInventories() : array {
		return $this->inventories;
	}

	public function getTransactions() : \SplQueue {
		return $this->transactionQueue;
	}

	public function getTransactionCount() : int {
		return $this->transactionCount;
	}

	public function addTransaction(Transaction $transaction) {
		$this->transactionQueue->enqueue($transaction);

		if($transaction->getInventory() instanceof Inventory) {
			// For dropping items, the target inventory is open air, a.k.a. null.
			$this->inventories[spl_object_hash($transaction)] = $transaction->getInventory();
		}

		$this->lastUpdate = microtime(true);
		$this->transactionCount += 1;
	}

	public function execute() {
		/** @var Transaction[] */
		$failed = [];

		while(!$this->transactionsToRetry->isEmpty()) {
			// Some failed transactions are waiting from the previous execution to be retried
			$this->transactionQueue->enqueue($this->transactionsToRetry->dequeue());
		}

		if(!$this->transactionQueue->isEmpty()) {
			$this->player->getServer()->getPluginManager()->callEvent($ev = new InventoryTransactionEvent($this));
		} else {
			return;
		}

		while(!$this->transactionQueue->isEmpty()) {
			$transaction = $this->transactionQueue->dequeue();

			if($ev->isCancelled()) {
				$this->transactionCount -= 1;
				$transaction->sendSlotUpdate($this->player); //Send update back to client for cancelled transaction
				unset($this->inventories[spl_object_hash($transaction)]);
				continue;
			}elseif(!$transaction->execute($this->player)) {
				$transaction->addFailure();
				if($transaction->getFailures() >= self::DEFAULT_ALLOWED_RETRIES){
					/* Transaction failed completely after several retries, hold onto it to send a slot update */
					$this->transactionCount -= 1;
					$failed[] = $transaction;
				} else {
					/* Add the transaction to the back of the queue to be retried on the next tick */
					$this->transactionsToRetry->enqueue($transaction);
				}
				continue;
			}

			$this->transactionCount -= 1;
			$transaction->setSuccess();
			$transaction->sendSlotUpdate($this->player);
			unset($this->inventories[spl_object_hash($transaction)]);
		}

		foreach($failed as $f) {
			$f->sendSlotUpdate($this->player);
			unset($this->inventories[spl_object_hash($f)]);
		}
	}

}
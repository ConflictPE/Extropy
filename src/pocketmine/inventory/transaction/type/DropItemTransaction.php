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

namespace pocketmine\inventory\transaction\type;

use pocketmine\inventory\transaction\BaseTransaction;
use pocketmine\inventory\transaction\Transaction;
use pocketmine\item\Item;
use pocketmine\network\multiversion\inventory\PlayerInventoryAdapter;
use pocketmine\Player;

class DropItemTransaction extends BaseTransaction {

	const TRANSACTION_TYPE = Transaction::TYPE_DROP_ITEM;

	protected $inventory = null;

	protected $slot = null;

	protected $sourceItem = null;

	/**
	 * @param Item $droppedItem
	 */
	public function __construct(Item $droppedItem) {
		$this->targetItem = $droppedItem;
	}

	public function setSourceItem(Item $item) {
		//Nothing to update
	}

	public function getInventory() {
		return null;
	}

	public function getSlot() : int {
		return -1;
	}

	public function sendSlotUpdate(Player $source) {
		//Nothing to update
	}

	public function getChange() {
		return [
			"in" => $this->getTargetItem(),
			"out" => null
		];
	}

	public function execute(Player $source): bool {
		/** @var PlayerInventoryAdapter $adapter */
		$adapter = $source->getInventoryAdapter(); // this transaction will only be called for old pre 1.2 clients
		$droppedItem = $this->getTargetItem();
		if(!$source->isCreative()){
			if(!$adapter->getFloatingInventory()->contains($droppedItem)){
				return false;
			}
			$adapter->getFloatingInventory()->removeItem($droppedItem);
		}
		$source->dropItem($droppedItem);
		return true;
	}

}
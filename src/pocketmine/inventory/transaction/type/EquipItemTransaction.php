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

use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\BaseTransaction;
use pocketmine\inventory\transaction\Transaction;
use pocketmine\item\Item;
use pocketmine\Player;

class EquipItemTransaction extends BaseTransaction {

	const TRANSACTION_TYPE = Transaction::TYPE_EQUIP_ITEM;

	/** @var PlayerInventory */
	protected $inventory;

	/** @var int */
	protected $hotbarSlot;

	public function __construct(Inventory $inventory, int $slot, int $hotbarSlot, Item $sourceItem, Item $targetItem) {
		$this->hotbarSlot = $hotbarSlot;
		parent::__construct($inventory, $slot, $sourceItem, $targetItem);
	}

	/**
	 * @return PlayerInventory
	 */
	public function getInventory() {
		return $this->inventory;
	}

	public function getHotbarSlot() : int {
		return $this->hotbarSlot;
	}

	public function execute(Player $source): bool {
		if($this->getSlot() < 0) {
			$source->getServer()->getLogger()->debug("Tried to equip a slot that does not exist (index " . $this->getSlot() . ")");
			return false;
		}

		$this->sourceItem = $source->getInventory()->getItem($this->getSlot()); // make sure we have the most up to date item

		if(!$this->getSourceItem()->equals($this->getTargetItem())) {
			$source->getServer()->getLogger()->debug("Tried to equip " . $this->getTargetItem() . " but have " . $this->getSourceItem() . " in target slot");
			return false;
		}

		$this->getInventory()->equipItem($this->getHotbarSlot(), $this->getSlot());

		$source->setUsingItem(false);

		return true;
	}

}
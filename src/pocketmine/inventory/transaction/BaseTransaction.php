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

use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\TemporaryInventory;
use pocketmine\item\Item;
use pocketmine\network\multiversion\inventory\PlayerInventoryAdapter;
use pocketmine\Player;

class BaseTransaction implements Transaction {

	/** @var Inventory|BaseInventory */
	protected $inventory;

	/** @var int */
	protected $slot;

	/** @var Item */
	protected $sourceItem;

	/** @var Item */
	protected $targetItem;

	/** @var float */
	protected $creationTime;

	/** @var int */
	protected $transactionType = Transaction::TYPE_NORMAL;

	/** @var int */
	protected $failures = 0;

	/** @var bool */
	protected $wasSuccessful = false;

	/**
	 * @param Inventory $inventory
	 * @param int       $slot
	 * @param Item      $sourceItem
	 * @param Item      $targetItem
	 */
	public function __construct(Inventory $inventory, int $slot, Item $sourceItem, Item $targetItem) {
		$this->inventory = $inventory;
		$this->slot = $slot;
		$this->sourceItem = clone $sourceItem;
		$this->targetItem = clone $targetItem;
		$this->creationTime = microtime(true);
	}

	public function getCreationTime() : float {
		return $this->creationTime;
	}

	/**
	 * @return BaseInventory|Inventory
	 */
	public function getInventory() {
		return $this->inventory;
	}

	public function getSlot() : int {
		return $this->slot;
	}

	public function getSourceItem() : Item {
		return clone $this->sourceItem;
	}

	public function getTargetItem() : Item {
		return clone $this->targetItem;
	}

	public function getFailures() : int {
		return $this->failures;
	}

	public function addFailure() {
		$this->failures++;
	}

	public function succeeded() : bool {
		return $this->wasSuccessful;
	}

	public function setSuccessful(bool $value = true) {
		$this->wasSuccessful = $value;
	}

	/**
	 * @param Player $source
	 *
	 * Sends a slot update to inventory viewers
	 * For successful transactions, update non-source viewers (source does not need updating)
	 * For failed transactions, update the source (non-source viewers will see nothing anyway)
	 */
	public function sendSlotUpdate(Player $source){
		if($this->getInventory() instanceof TemporaryInventory) {
			return;
		}

		if($this->wasSuccessful) {
			$targets = $this->getInventory()->getViewers();
			unset($targets[spl_object_hash($source)]);
		} else {
			$targets = [$source];
		}

		$this->inventory->sendSlot($this->slot, $targets);
	}

	/**
	 * Returns the change in inventory resulting from this transaction
	 *
	 * @return array ("in" => items added to the inventory, "out" => items removed from the inventory)
	 *
	 */
	public function getChange() {
		$sourceItem = $this->getInventory()->getItem($this->slot);

		if($sourceItem->equalsExact($this->targetItem)) {
			return null; // This should never happen, somehow a change happened where nothing changed
		} elseif($sourceItem->equals($this->targetItem)) { // Same item, change of count
			$item = clone $sourceItem;
			$countDiff = $this->targetItem->getCount() - $sourceItem->getCount();
			$item->setCount(abs($countDiff));

			if($countDiff < 0) { // Count decreased
				return [
					"in" => null,
					"out" => $item,
				];
			} elseif($countDiff > 0) { // Count increased
				return [
					"in" => $item,
					"out" => null,
				];
			} else {
				// Should be impossible (identical items and no count change)
				// This should be caught by the first condition even if it was possible
				return null;
			}
		} elseif($sourceItem->getId() !== Item::AIR and $this->targetItem->getId() === Item::AIR) {
			//Slot emptied (item removed)
			return [
				"in" => null,
				"out" => clone $sourceItem,
			];
		} elseif($sourceItem->getId() === Item::AIR and $this->targetItem->getId() !== Item::AIR) {
			//Slot filled (item added)
			return [
				"in" => $this->getTargetItem(),
				"out" => null,
			];
		} else {
			//Some other slot change - an item swap (tool damage changes will be ignored as they are processed server-side before any change is sent by the client
			return [
				"in" => $this->getTargetItem(),
				"out" => clone $sourceItem,
			];
		}
	}

	/**
	 * @param Player $source
	 * @return bool
	 *
	 * Handles transaction execution. Returns whether transaction was successful or not.
	 */
	public function execute(Player $source): bool {
		$adapter = $source->getInventoryAdapter();
		if($adapter instanceof PlayerInventoryAdapter) {
			if($this->getInventory()->processSlotChange($this)) { //This means that the transaction should be handled the normal way
				if(!$source->isCreative()) {
					$change = $this->getChange();
					if($change === null) { // No changes to make, ignore this transaction
						return true;
					}
					/* Verify that we have the required items */
					if($change["out"] instanceof Item) {
						if(!$this->getInventory()->contains($change["out"])) {
							return false;
						}
					}
					if($change["in"] instanceof Item) {
						if(!$adapter->getFloatingInventory()->contains($change["in"])) {
							return false;
						}
					}
					/* All checks passed, make changes to floating inventory
					 * This will not be reached unless all requirements are met */
					if($change["out"] instanceof Item) {
						$adapter->getFloatingInventory()->addItem($change["out"]);
					}
					if($change["in"] instanceof Item) {
						$adapter->getFloatingInventory()->removeItem($change["in"]);
					}
				}
				$this->getInventory()->setItem($this->getSlot(), $this->getTargetItem(), false);
			}
		}

		return true;
	}

}
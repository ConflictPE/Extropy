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

use pocketmine\item\Item;
use pocketmine\tile\Furnace;

class FurnaceInventory extends ContainerInventory {

	const SLOT_SMELTING = 0;
	const SLOT_FUEL = 1;
	const SLOT_RESULT = 2;

	/** @var Furnace */
	protected $holder;

	public function __construct(Furnace $tile) {
		parent::__construct($tile, InventoryType::get(InventoryType::FURNACE));
	}

	/**
	 * @return Furnace
	 */
	public function getHolder() {
		return $this->holder;
	}

	/**
	 * @return Item
	 */
	public function getResult() {
		return $this->getItem(self::SLOT_RESULT);
	}

	/**
	 * @return Item
	 */
	public function getFuel() {
		return $this->getItem(self::SLOT_FUEL);
	}

	/**
	 * @return Item
	 */
	public function getSmelting() {
		return $this->getItem(self::SLOT_SMELTING);
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setResult(Item $item) {
		return $this->setItem(self::SLOT_RESULT, $item);
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setFuel(Item $item) {
		return $this->setItem(self::SLOT_FUEL, $item);
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setSmelting(Item $item) {
		return $this->setItem(self::SLOT_SMELTING, $item);
	}

	public function onSlotChange(int $index, Item $before, bool $send = true){
		parent::onSlotChange($index, $before, $send);

		$this->getHolder()->scheduleUpdate();
	}

}
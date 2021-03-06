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

use pocketmine\entity\Human;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\Enum;
use pocketmine\network\protocol\LevelSoundEventPacket;
use pocketmine\network\protocol\TileEventPacket;
use pocketmine\Player;
use pocketmine\Server;

class EnderChestInventory extends ChestInventory {

	/** @var Human|Player */
	private $owner;

	/** @var FakeBlockMenu */
	protected $holder;

	public function __construct(Human $owner, Enum $contents = null) {
		$this->owner = $owner;
		ContainerInventory::__construct(new FakeBlockMenu($this, $owner->getPosition()), InventoryType::get(InventoryType::ENDER_CHEST));

		if($contents !== null) {
			if($contents instanceof Enum) { //Saved data to be loaded into the inventory
				foreach($contents as $item) {
					$this->setItem($item["Slot"], Item::nbtDeserialize($item));
				}
			} else {
				throw new \InvalidArgumentException("Expecting Enum, received " . gettype($contents));
			}
		}
	}

	public function getOwner() : Human {
		return $this->owner;
	}

	/**
	 * Set the fake block menu's position to a valid tile position
	 * and send the inventory window to the owner
	 *
	 * @param Position $pos
	 */
	public function openAt(Position $pos) {
		$this->getHolder()->setComponents($pos->x, $pos->y, $pos->z);
		$this->getHolder()->setLevel($pos->getLevel());
		$this->owner->addWindow($this);
	}

	/**
	 * @return FakeBlockMenu
	 */
	public function getHolder() {
		return $this->holder;
	}

}
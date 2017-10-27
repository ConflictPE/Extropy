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
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\types\ContainerIds;
use pocketmine\Player;
use pocketmine\Server;

class PlayerInventory extends BaseInventory {

	const OFFHAND_CONTAINER_ID = 4;

	/** @var Human */
	protected $holder;

	/** @var int */
	protected $itemInHandIndex = 0;

	/** @var int[] */
	protected $hotbar;

	public function __construct(Human $player) {
		$this->hotbar = range(0, $this->getHotbarSize() - 1, 1);
		parent::__construct($player, InventoryType::get(InventoryType::PLAYER));
	}

	public function getSize() : int {
		return parent::getSize() - 5; // Remove armor slots
	}

	public function setSize(int $size) {
		parent::setSize($size + 5);
	}

	/**
	 * Called when a client equips a hotbar slot. This method should not be used by plugins.
	 *
	 * @param int $hotbarSlot
	 *
	 * @return bool    If the equipment change was successful, false if not.
	 */
	public function equipItem(int $hotbarSlot) : bool {
		if(!$this->isHotbarSlot($hotbarSlot)){
			$this->sendContents($this->getHolder());
			return false;
		}

		$this->getHolder()->getServer()->getPluginManager()->callEvent($ev = new PlayerItemHeldEvent($this->getHolder(), $this->getItem($hotbarSlot), $hotbarSlot));

		if($ev->isCancelled()) {
			$this->sendHeldItem($this->getHolder());
			return false;
		}

		$this->setHeldItemIndex($hotbarSlot, false);

		return true;
	}

	private function isHotbarSlot(int $slot) : bool {
		return $slot >= 0 and $slot <= $this->getHotbarSize();
	}

	/**
	 * @param int $slot
	 * @throws \InvalidArgumentException
	 */
	private function throwIfNotHotbarSlot(int $slot) {
		if(!$this->isHotbarSlot($slot)) {
			throw new \InvalidArgumentException("$slot is not a valid hotbar slot index (expected 0 - " . ($this->getHotbarSize() - 1) . ")");
		}
	}

	/**
	 * Returns the item in the specified hotbar slot.
	 *
	 * @param int $hotbarSlot
	 * @return Item
	 *
	 * @throws \InvalidArgumentException if the hotbar slot index is out of range
	 */
	public function getHotbarSlotItem(int $hotbarSlot) : Item {
		$this->throwIfNotHotbarSlot($hotbarSlot);
		return $this->getItem($hotbarSlot);
	}

	/**
	 * @param $index
	 *
	 * @return int
	 *
	 * Returns the index of the inventory slot linked to the specified hotbar slot
	 */
	public function getHotbarSlotIndex(int $index) : int {
		return ($index >= 0 and $index < $this->getHotbarSize()) ? $this->hotbar[$index] : -1;
	}

	/**
	 * Links a hotbar slot to the specified slot in the main inventory. -1 links to no slot and will clear the hotbar slot.
	 * This method is intended for use in network interaction with clients only.
	 *
	 * NOTE: Do not change hotbar slot mapping with plugins, this will cause myriad client-sided bugs, especially with desktop GUI clients.
	 *
	 * @param int $hotbarSlot
	 * @param int $inventorySlot
	 */
	public function setHotbarSlotIndex(int $hotbarSlot, int $inventorySlot) {
		if($hotbarSlot < 0 or $hotbarSlot >= $this->getHotbarSize()){
			throw new \InvalidArgumentException("Hotbar slot index \"$hotbarSlot\" is out of range");
		} elseif($inventorySlot < -1 or $inventorySlot >= $this->getSize()){
			throw new \InvalidArgumentException("Inventory slot index \"$inventorySlot\" is out of range");
		}

		if($inventorySlot !== -1 and ($alreadyEquippedIndex = array_search($inventorySlot, $this->hotbar)) !== false) {
			/* Swap the slots
			 * This assumes that the equipped slot can only be equipped in one other slot
			 * it will not account for ancient bugs where the same slot ended up linked to several hotbar slots.
			 * Such bugs will require a hotbar reset to default.
			 */
			$this->hotbar[$alreadyEquippedIndex] = $this->hotbar[$hotbarSlot];
		}

		$this->hotbar[$hotbarSlot] = $inventorySlot;
	}

	public function getHeldItemIndex() : int {
		return $this->itemInHandIndex;
	}

	/**
	 * Sets which hotbar slot the player is currently holding.
	 *
	 * @param int $hotbarSlot
	 * @param bool $send
	 *
	 */
	public function setHeldItemIndex(int $hotbarSlot, bool $send = true) {
		$this->throwIfNotHotbarSlot($hotbarSlot);

		$this->itemInHandIndex = $hotbarSlot;

		if($this->getHolder() instanceof Player and $send){
			$this->sendHeldItem($this->getHolder());
		}

		$this->sendHeldItem($this->getHolder()->getViewers());
	}

	/**
	 * @return Item
	 *
	 * Returns the item the player is currently holding
	 */
	public function getItemInHand() : Item {
		return $this->getHotbarSlotItem($this->itemInHandIndex);
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 *
	 * Sets the item in the inventory slot the player is currently holding.
	 */
	public function setItemInHand(Item $item) : bool {
		return $this->setItem($this->getHeldItemSlot(), $item);
	}

	/**
	 * @return int[]
	 *
	 * Returns an array of hotbar indices
	 */
	public function getHotbar() : array {
		return $this->hotbar;
	}

	/**
	 * @return int
	 *
	 * Returns the inventory slot index of the currently equipped slot
	 */
	public function getHeldItemSlot() : int {
		return $this->getHotbarSlotIndex($this->itemInHandIndex);
	}

	/**
	 * @deprecated
	 *
	 * @param int $slot
	 */
	public function setHeldItemSlot(int $slot) {
	}

	/**
	 * @param Player|Player[] $target
	 */
	public function sendHeldItem($target) {
		$item = $this->getItemInHand();

		$pk = new MobEquipmentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->item = $item;
		$pk->slot = $this->getHeldItemSlot();
		$pk->selectedSlot = $this->getHeldItemIndex();

		$level = $this->getHolder()->getLevel();
		if(!is_array($target)) {
			$target->dataPacket($pk);
		} else {
			foreach($target as $player) {
				if($level->mayAddPlayerHandItem($this->getHolder(), $player)) {
					$player->dataPacket($pk);
					if($player === $this->getHolder()) {
						$this->sendSlot($this->getHeldItemSlot(), $player);
					}
				}
			}
		}
	}

	public function onSlotChange(int $index, Item $before, bool $send = true) {
		if($send) {
			$holder = $this->getHolder();
			if(!$holder instanceof Player or !$holder->spawned) {
				return;
			}
			parent::onSlotChange($index, $before, $send);
		}

		if($index === $this->itemInHandIndex) {
			$this->sendHeldItem($this->getHolder()->getViewers());
		} elseif($index >= $this->getSize()) { // Armour equipment
			$this->sendArmorSlot($index, $this->getViewers());
			$this->sendArmorSlot($index, $this->getHolder()->getViewers());
		}
	}

	public function getHotbarSize() : int {
		return 9;
	}

	public function getArmorItem($index) : Item {
		return $this->getItem($this->getSize() + $index);
	}

	public function setArmorItem(int $index, Item $item) {
		return $this->setItem($this->getSize() + $index, $item);
	}

	public function getHelmet() : Item {
		return $this->getItem($this->getSize());
	}

	public function getChestplate() : Item {
		return $this->getItem($this->getSize() + 1);
	}

	public function getLeggings() : Item {
		return $this->getItem($this->getSize() + 2);
	}

	public function getBoots() : Item {
		return $this->getItem($this->getSize() + 3);
	}

	public function setHelmet(Item $helmet) : bool {
		return $this->setItem($this->getSize(), $helmet);
	}

	public function setChestplate(Item $chestplate) : bool {
		return $this->setItem($this->getSize() + 1, $chestplate);
	}

	public function setLeggings(Item $leggings) : bool {
		return $this->setItem($this->getSize() + 2, $leggings);
	}

	public function setBoots(Item $boots) : bool {
		return $this->setItem($this->getSize() + 3, $boots);
	}

	public function setItem(int $index, Item $item, bool $send = true) : bool {
		if($index < 0 or $index >= $this->size) {
			return false;
		} elseif($item->isNull()) {
			return $this->clear($index, $send);
		}

		if($index >= $this->getSize()) { // Armor change
			Server::getInstance()->getPluginManager()->callEvent($ev = new EntityArmorChangeEvent($this->getHolder(), $this->getItem($index), $item, $index));
			if($ev->isCancelled() and $this->getHolder() instanceof Human) {
				$this->sendArmorSlot($index, $this->getViewers());
				return false;
			}

			$item = $ev->getNewItem();
		} else {
			Server::getInstance()->getPluginManager()->callEvent($ev = new EntityInventoryChangeEvent($this->getHolder(), $this->getItem($index), $item, $index));
			if($ev->isCancelled()) {
				$this->sendSlot($index, $this->getViewers());
				return false;
			}

			$item = $ev->getNewItem();
		}


		$old = $this->getItem($index);
		$this->slots[$index] = clone $item;
		$this->onSlotChange($index, $old, $send);

		return true;
	}

	public function clear(int $index, bool $send = true) : bool {
		if(isset($this->slots[$index])) {
			$item = clone $this->air;
			$old = $this->slots[$index];

			if($index >= $this->getSize() and $index < $this->size) { //Armor change
				Server::getInstance()->getPluginManager()->callEvent($ev = new EntityArmorChangeEvent($this->getHolder(), $old, $item, $index));
				if($ev->isCancelled()) {
					if($index >= $this->size) {
						$this->sendArmorSlot($index, $this->getViewers());
					} else {
						$this->sendSlot($index, $this->getViewers());
					}

					return false;
				}
				$item = $ev->getNewItem();
			} else {
				Server::getInstance()->getPluginManager()->callEvent($ev = new EntityInventoryChangeEvent($this->getHolder(), $old, $item, $index));
				if($ev->isCancelled()) {
					if($index >= $this->size){
						$this->sendArmorSlot($index, $this->getViewers());
					} else {
						$this->sendSlot($index, $this->getViewers());
					}

					return false;
				}

				$item = $ev->getNewItem();
			}

			if($item->getId() !== Item::AIR) {
				$this->slots[$index] = clone $item;
			} else {
				unset($this->slots[$index]);
			}

			$this->onSlotChange($index, $old);
		}

		return true;
	}

	/**
	 * @return Item[]
	 */
	public function getArmorContents() : array {
		$armor = [];

		for($i = 0; $i < 4; ++$i) {
			$armor[$i] = $this->getItem($this->getSize() + $i);
		}

		return $armor;
	}

	public function clearAll() {
		for($limit = $this->getSize() + 5, $index = 0; $index < $limit; ++$index) {
			$this->clear($index, false);
		}

		$this->hotbar = range(0, $this->getHotbarSize() - 1, 1);
		$this->sendContents($this->getViewers());
	}

	/**
	 * @param Player|Player[] $target
	 */
	public function sendArmorContents($target) {
		if($target instanceof Player){
			$target = [$target];
		}

		$armor = $this->getArmorContents();

		$pk = new MobArmorEquipmentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->slots = $armor;

		foreach($target as $player) {
			if($player === $this->getHolder()){
				$player->getInventoryAdapter()->sendInventoryContents(ContainerIds::TYPE_ARMOR, $armor);
			} else {
				$player->dataPacket($pk);
			}
		}

		$this->sendOffHandContents($target);
	}

	/**
	 * @param Player|Player[] $target
	 */
	private function sendOffHandContents($target) {
		if($target instanceof Player){
			$target = [$target];
		}

		$pk = new MobEquipmentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->item = $this->getItem($this->getSize() + self::OFFHAND_CONTAINER_ID);
		$pk->slot = $this->getHeldItemSlot();
		$pk->selectedSlot = $this->getHeldItemIndex();
		$pk->windowId = MobEquipmentPacket::WINDOW_ID_PLAYER_OFFHAND;

		foreach($target as $player) {
			if($player->getPlayerProtocol() >= Info::PROTOCOL_110) {
				if($player === $this->getHolder()) {
					$player->getInventoryAdapter()->sendInventorySlot(ContainerIds::TYPE_OFFHAND, $this->getItem($this->getSize() + self::OFFHAND_CONTAINER_ID), 0);
				} else {
					$player->dataPacket($pk);
				}
			}
		}
	}

	/**
	 * @param Item[] $items
	 */
	public function setArmorContents(array $items) {
		for($i = 0; $i < 4; ++$i) {
			if(!isset($items[$i]) or !($items[$i] instanceof Item)) {
				$items[$i] = clone $this->air;
			}

			if($items[$i]->getId() === Item::AIR) {
				$this->clear($this->getSize() + $i, false);
			} else {
				$this->setItem($this->getSize() + $i, $items[$i], false);
			}
		}

		$this->sendArmorContents($this->getViewers());
	}

	/**
	 * @param int             $index
	 * @param Player|Player[] $target
	 */
	public function sendArmorSlot(int $index, $target) {
		if($target instanceof Player) {
			$target = [$target];
		}

		$armor = $this->getArmorContents();

		$pk = new MobArmorEquipmentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->slots = $armor;

		foreach($target as $player) {
			if($player === $this->getHolder()) {
				$player->getInventoryAdapter()->sendInventorySlot(ContainerIds::TYPE_ARMOR, $this->getItem($index), $index - $this->getSize());
			} else {
				$player->dataPacket($pk);
			}
		}
	}

	/**
	 * @param Player|Player[] $target
	 */
	public function sendContents($target) {
		if($target instanceof Player){
			$target = [$target];
		}

		$slots = [];

		for($i = 0; $i < $this->getSize(); ++$i) { // Do not send armor by error here
			$slots[$i] = $this->getItem($i);
		}

		$slots120 = $slots; // 1.2 players don't need the hack below

		//Because PE is stupid and shows 9 less slots than you send it, give it 9 dummy slots so it shows all the REAL slots.
		for($i = $this->getSize(); $i < $this->getSize() + 9; ++$i) {
			$slots[$i] = clone $this->air;
		}

		$hotbarSlots = [];

		foreach($target as $player) {
			if(($is120 = $player->getPlayerProtocol() < Info::PROTOCOL_120) and $player === $this->getHolder()) {
				$size = $this->getHotbarSize();
				for($i = 0; $i < $size; ++$i) {
					$index = $this->getHotbarSlotIndex($i);
					$hotbarSlots[] = $index <= -1 ? -1 : $index + 9;
				}
			}

			if(($id = $player->getWindowId($this)) === -1 or $player->spawned === false) {
				$this->close($player);
				continue;
			}

			$player->getInventoryAdapter()->sendInventoryContents($id, $is120 ? $slots120 : $slots, $hotbarSlots);
		}
	}

	/**
	 * @param int             $index
	 * @param Player|Player[] $target
	 */
	public function sendSlot(int $index, $target) {
		if($target instanceof Player){
			$target = [$target];
		}

		$item = clone $this->getItem($index);

		foreach($target as $player) {
			if($player === $this->getHolder()) {
				$player->getInventoryAdapter()->sendInventorySlot(0, $item, $index);
			} else {
				if(($id = $player->getWindowId($this)) === -1) {
					$this->close($player);
				}

				$player->getInventoryAdapter()->sendInventorySlot($id, $item, $index);
			}
		}
	}

	/**
	 * @return Human|Player
	 */
	public function getHolder() {
		return $this->holder;
	}

}
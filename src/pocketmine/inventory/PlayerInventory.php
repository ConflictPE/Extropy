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

class PlayerInventory extends BaseInventory{

	const OFFHAND_CONTAINER_ID = 4;

	protected $itemInHandIndex = 0;
	/** @var int[] */
	protected $hotbar;

	public function __construct(Human $player){
		$this->hotbar = range(0, $this->getHotbarSize() - 1, 1);
		parent::__construct($player, InventoryType::get(InventoryType::PLAYER));
	}

	public function getSize(){
		return parent::getSize() - 5; // Remove armor slots
	}

	public function setSize($size) {
		parent::setSize($size + 5);
	}

	/**
	 * @param $index
	 *
	 * @return int
	 *
	 * Returns the index of the inventory slot linked to the specified hotbar slot
	 */
	public function getHotbarSlotIndex($index){
		return ($index >= 0 and $index < $this->getHotbarSize()) ? $this->hotbar[$index] : -1;
	}

	/**
	 * @deprecated
	 *
	 * Changes the linkage of the specified hotbar slot. This should never be done unless it is requested by the client.
	 *
	 * @param $index
	 * @param $slot
	 */
	public function setHotbarSlotIndex($index, $slot){
		if($this->getHolder()->getServer()->getProperty("settings.deprecated-verbose") !== false){
			trigger_error("Do not attempt to change hotbar links in plugins!", E_USER_DEPRECATED);
		}
	}

	public function getHeldItemIndex(){
		return $this->itemInHandIndex;
	}

	/**
	 * @param int $hotbarSlotIndex
	 * @param bool $sendToHolder
	 * @param int|null $slotMapping
	 *
	 * Sets which hotbar slot the player is currently holding.
	 * Allows slot remapping as specified by a MobEquipmentPacket. DO NOT CHANGE SLOT MAPPING IN PLUGINS!
	 * This new implementation is fully compatible with older APIs.
	 * NOTE: Slot mapping is the raw slot index sent by MCPE, which will be between 9 and 44.
	 */
	public function setHeldItemIndex(int $hotbarSlotIndex, bool $sendToHolder = true, int $slotMapping = null){
		if($slotMapping !== null) {
			// Get the index of the slot in the actual inventory
			$slotMapping -= $this->getHotbarSize();
		}

		if(0 <= $hotbarSlotIndex and $hotbarSlotIndex < $this->getHotbarSize()) {
			$this->itemInHandIndex = $hotbarSlotIndex;
			if($slotMapping !== null) {
				/* Handle a hotbar slot mapping change. This allows PE to select different inventory slots.
				 * This is the only time slot mapping should ever be changed. */

				if($slotMapping < 0 or $slotMapping >= $this->getSize()) {
					//Mapping was not in range of the inventory, set it to -1
					//This happens if the client selected a blank slot (sends 255)
					$slotMapping = -1;
				}

				$item = $this->getItem($slotMapping);
				if($this->getHolder() instanceof Player) {
					Server::getInstance()->getPluginManager()->callEvent($ev = new PlayerItemHeldEvent($this->getHolder(), $item, $slotMapping, $hotbarSlotIndex));
					if($ev->isCancelled()) {
						$this->sendHeldItem($this->getHolder());
						$this->sendContents($this->getHolder());
						return;
					}
				}

				if(($key = array_search($slotMapping, $this->hotbar)) !== false and $slotMapping !== -1) {
					/* Do not do slot swaps if the slot was null
					 * Chosen slot is already linked to a hotbar slot, swap the two slots around.
					 * This will already have been done on the client-side so no changes need to be sent. */
					$this->hotbar[$key] = $this->hotbar[$this->itemInHandIndex];
				}

				$this->hotbar[$this->itemInHandIndex] = $slotMapping;
			}
			$this->sendHeldItem($this->getHolder()->getViewers());
			if($sendToHolder) {
				$this->sendHeldItem($this->getHolder());
			}
		}
	}

	/**
	 * @return Item
	 *
	 * Returns the item the player is currently holding
	 */
	public function getItemInHand(){
		$item = $this->getItem($this->getHeldItemSlot());
		if($item instanceof Item) {
			return $item;
		} else {
			return ItemFactory::get(Item::AIR, 0, 0);
		}
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 *
	 * Sets the item in the inventory slot the player is currently holding.
	 */
	public function setItemInHand(Item $item) {
		return $this->setItem($this->getHeldItemSlot(), $item);
	}

	/**
	 * @return int[]
	 *
	 * Returns an array of hotbar indices
	 */
	public function getHotbar() {
		return $this->hotbar;
	}

	/**
	 * @return int
	 *
	 * Returns the inventory slot index of the currently equipped slot
	 */
	public function getHeldItemSlot(){
		return $this->getHotbarSlotIndex($this->itemInHandIndex);
	}

	/**
	 * @deprecated
	 *
	 * @param $slot
	 */
	public function setHeldItemSlot($slot) {
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

	public function onSlotChange($index, $before, $send = true){
		if($send) {
			$holder = $this->getHolder();
			if(!$holder instanceof Player or !$holder->spawned) {
				return;
			}
			parent::onSlotChange($index, $before, $send);
		}

		if($index === $this->itemInHandIndex){
			$this->sendHeldItem($this->getHolder()->getViewers());
		} elseif($index >= $this->getSize()) { // Armour equipment
			$this->sendArmorSlot($index, $this->getViewers());
			$this->sendArmorSlot($index, $this->getHolder()->getViewers());
		}
	}

	public function getHotbarSize(){
		return 9;
	}

	public function getArmorItem($index){
		return $this->getItem($this->getSize() + $index);
	}

	/**
	 * Called when a client equips a hotbar slot. This method should not be used by plugins.
	 *
	 * @param int $hotbarSlot
	 * @param int|null $inventorySlot
	 *
	 * @return bool    If the equipment change was successful, false if not.
	 */
	public function equipItem(int $hotbarSlot, $inventorySlot = null) : bool {
		if($inventorySlot === null) {
			$inventorySlot = $this->getHotbarSlotIndex($hotbarSlot);
		}

		if($hotbarSlot < 0 or $hotbarSlot >= $this->getHotbarSize() or $inventorySlot < -1 or $inventorySlot >= $this->getSize()) {
			$this->sendContents($this->getHolder());
			return false;
		}

		if($inventorySlot === -1) {
			$item = ItemFactory::get(Item::AIR);
		} else {
			$item = $this->getItem($inventorySlot);
		}

		$this->getHolder()->getServer()->getPluginManager()->callEvent($ev = new PlayerItemHeldEvent($this->getHolder(), $item, $inventorySlot, $hotbarSlot));

		if($ev->isCancelled()) {
			$this->sendContents($this->getHolder());
			return false;
		}

		$this->setHotbarSlotIndex($hotbarSlot, $inventorySlot);
		$this->setHeldItemIndex($hotbarSlot, false);

		return true;
	}

	/**
	 *
	 * @param int $index
	 * @return Item
	 */
	public function getHotbatSlotItem($index) {
		$slot = $this->getHotbarSlotIndex($index);
		return $this->getItem($slot);
	}

	/**
	 * @impportant For win10 inventory only
	 * @param int $index
	 */
	public function justSetHeldItemIndex($index) {
		if($index >= 0 and $index < $this->getHotbarSize()){
			$this->itemInHandIndex = $index;
		}
	}

	public function setArmorItem($index, Item $item, $sendPacket = true){
		return $this->setItem($this->getSize() + $index, $item, $sendPacket);
	}

	public function getHelmet(){
		return $this->getItem($this->getSize());
	}

	public function getChestplate(){
		return $this->getItem($this->getSize() + 1);
	}

	public function getLeggings(){
		return $this->getItem($this->getSize() + 2);
	}

	public function getBoots(){
		return $this->getItem($this->getSize() + 3);
	}

	public function setHelmet(Item $helmet){
		return $this->setItem($this->getSize(), $helmet);
	}

	public function setChestplate(Item $chestplate){
		return $this->setItem($this->getSize() + 1, $chestplate);
	}

	public function setLeggings(Item $leggings){
		return $this->setItem($this->getSize() + 2, $leggings);
	}

	public function setBoots(Item $boots){
		return $this->setItem($this->getSize() + 3, $boots);
	}

	public function setItem($index, Item $item, $sendPacket = true){
		if($index < 0 or $index >= $this->size){
			return false;
		}elseif($item->getId() === 0 or $item->getCount() <= 0){
			return $this->clear($index);
		}

		if($index >= $this->getSize()){ //Armor change
			Server::getInstance()->getPluginManager()->callEvent($ev = new EntityArmorChangeEvent($this->getHolder(), $this->getItem($index), $item, $index));
			if($ev->isCancelled() and $this->getHolder() instanceof Human){
				$this->sendArmorSlot($index, $this->getHolder());
				return false;
			}
			$item = $ev->getNewItem();
		}else{
			Server::getInstance()->getPluginManager()->callEvent($ev = new EntityInventoryChangeEvent($this->getHolder(), $this->getItem($index), $item, $index));
			if($ev->isCancelled()){
				$this->sendSlot($index, $this->getHolder());
				return false;
			}
			$index = $ev->getSlot();
			$item = $ev->getNewItem();
		}


		$old = $this->getItem($index);
		$this->slots[$index] = clone $item;
		$this->onSlotChange($index, $old, $sendPacket);

		return true;
	}

	public function clear($index){
		if(isset($this->slots[$index])){
			$item = clone $this->air;
			$old = $this->slots[$index];
			if($index >= $this->getSize() and $index < $this->size){ //Armor change
				Server::getInstance()->getPluginManager()->callEvent($ev = new EntityArmorChangeEvent($this->getHolder(), $old, $item, $index));
				if($ev->isCancelled()){
					if($index >= $this->size){
						$this->sendArmorSlot($index, $this->getHolder());
					}else{
						$this->sendSlot($index, $this->getHolder());
					}
					return false;
				}
				$item = $ev->getNewItem();
			}else{
				Server::getInstance()->getPluginManager()->callEvent($ev = new EntityInventoryChangeEvent($this->getHolder(), $old, $item, $index));
				if($ev->isCancelled()){
					if($index >= $this->size){
						$this->sendArmorSlot($index, $this->getHolder());
					}else{
						$this->sendSlot($index, $this->getHolder());
					}
					return false;
				}
				$item = $ev->getNewItem();
			}
			if($item->getId() !== Item::AIR){
				$this->slots[$index] = clone $item;
			}else{
				unset($this->slots[$index]);
			}

			$this->onSlotChange($index, $old);
		}

		return true;
	}

	/**
	 * @return Item[]
	 */
	public function getArmorContents(){
		$armor = [];

		for($i = 0; $i < 4; ++$i){
			$armor[$i] = $this->getItem($this->getSize() + $i);
		}

		return $armor;
	}

	public function clearAll(){
		$limit = $this->getSize() + 5;
		for($index = 0; $index < $limit; ++$index){
			$this->clear($index);
		}
	}

	/**
	 * @param Player|Player[] $target
	 */
	public function sendArmorContents($target){
		if($target instanceof Player){
			$target = [$target];
		}

		$armor = $this->getArmorContents();

		$pk = new MobArmorEquipmentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->slots = $armor;

		foreach($target as $player){
			if($player === $this->getHolder()){
				$player->getInventoryAdapter()->sendInventoryContents(ContainerIds::TYPE_ARMOR, $armor);
			}else{
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
	public function setArmorContents(array $items, $sendPacket = true){
		for($i = 0; $i < 4; ++$i){
			if(!isset($items[$i]) or !($items[$i] instanceof Item)){
				$items[$i] = clone $this->air;
			}

			if($items[$i]->getId() === Item::AIR){
				$this->clear($this->getSize() + $i);
			}else{
				$this->setItem($this->getSize() + $i, $items[$i], $sendPacket);
			}
		}
	}


	/**
	 * @param int             $index
	 * @param Player|Player[] $target
	 */
	public function sendArmorSlot($index, $target){
		if (!is_array($target)) {
			if($target instanceof Player){
				$target = [$target];
			} else {
				return;
			}
		}

		if ($index - $this->getSize() == self::OFFHAND_CONTAINER_ID) {
			$this->sendOffHandContents($target);
			return;
		}

		$armor = $this->getArmorContents();

		$pk = new MobArmorEquipmentPacket();
		$pk->eid = $this->getHolder()->getId();
		$pk->slots = $armor;

		foreach($target as $player){
			if($player === $this->getHolder()){
				$player->getInventoryAdapter()->sendInventorySlot(ContainerIds::TYPE_ARMOR, $this->getItem($index), $index - $this->getSize());
			}else{
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
	public function sendSlot($index, $target){
		if (!($this->getHolder() instanceof Player)) {
			return;
		}
		$pk = new ContainerSetSlotPacket();
		$pk->slot = $index;
		$pk->item = clone $this->getItem($index);
		$pk->windowid = ContainerIds::TYPE_INVENTORY;
		$this->getHolder()->dataPacket($pk);
	}

	/**
	 * @return Human|Player
	 */
	public function getHolder(){
		return parent::getHolder();
	}

	public function removeItemWithCheckOffHand($searchItem) {
		$offhandSlotId = $this->getSize() + self::OFFHAND_CONTAINER_ID;
		$item = $this->getItem($offhandSlotId);
		if ($item->getId() !== Item::AIR && $item->getCount() > 0) {
			if ($searchItem->equals($item, $searchItem->getDamage() === null ? false : true, $searchItem->getCompoundTag() === null ? false : true)) {
				$amount = min($item->getCount(), $searchItem->getCount());
				$searchItem->setCount($searchItem->getCount() - $amount);
				$item->setCount($item->getCount() - $amount);
				$this->setItem($offhandSlotId, $item);
				return;
			}
		}
		parent::removeItem($searchItem);
	}

}
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

namespace pocketmine\network\multiversion\inventory;

use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\SimpleTransactionGroup;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\types\ContainerIds;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\Player;

/**
 * Class to assist with inventory transactions
 */
class PlayerInventoryAdapter {

	/** @var Player */
	private $player;

	/** @var SimpleTransactionGroup */
	protected $currentTransaction = null;

	public function __construct(Player $player) {
		$this->player = $player;
	}

	public function getPlayer() : Player {
		return $this->player;
	}

	/**
	 * Handle an incoming mob equipment update
	 *
	 * @param int $slot
	 * @param Item $item
	 * @param int $inventorySlot
	 */
	public function handleMobEquipment(int $slot, Item $item, int $inventorySlot) {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if($slot === 255) {
			$slot = -1; // Cleared slot
		} else {
			if($slot < 9) {
				$player->getServer()->getLogger()->debug($player->getName() . " tried to equip a slot that does not exist (index " . $slot . ")");
				$inventory->sendContents($player);
				return;
			}

			$slot -= 9; // Get real inventory slot

			$handItem = $inventory->getItem($slot);

			if(!$handItem->equals($item)) {
				$player->getServer()->getLogger()->debug($player->getName() . " tried to equip " . $item . " but has " . $handItem . " in target slot");
				$inventory->sendContents($player);
				return;
			}
		}

		$inventory->equipItem($inventorySlot, $slot);

		$player->setUsingItem(false);
	}

	//public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool {
	//
	//}

	/**
	 * Handle an incoming use item request
	 *
	 * @param Item $item
	 * @param int $slot
	 * @param int $face
	 * @param Vector3 $blockPosition
	 * @param Vector3 $clickPosition
	 */
	public function handleUseItem(Item $item, int $slot, int $face, Vector3 $blockPosition, Vector3 $clickPosition) {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();
		$handItem = $inventory->getItemInHand();

		switch($face) {
			// Use item, block place
			case 0:
			case 1:
			case 2:
			case 3:
			case 4:
			case 5:
				$player->setUsingItem(false);

				if(!$player->canInteract($blockPosition->add(0.5, 0.5, 0.5), 13) or $player->isSpectator()) {

				} elseif($player->isCreative()) {
					if($player->getLevel()->useItemOn($blockPosition, $item, $face, $clickPosition, $this, true) === true){
						return;
					}
				} elseif(!$handItem->equals($item)) {
					$inventory->sendHeldItem($player);
				} else {
					$oldItem = clone $handItem;
					if($player->getLevel()->useItemOn($blockPosition, $item, $face, $clickPosition, $player, true)){
						if(!$item->equalsExact($oldItem)){
							$inventory->setItemInHand($item);
							$inventory->sendHeldItem($player->getViewers());
						}

						return;
					}
				}

				$inventory->sendHeldItem($player);

				if($blockPosition->distanceSquared($player) > 10000){
					return;
				}

				$target = $player->getLevel()->getBlock($blockPosition);
				$block = $target->getSide($face);

				$player->getLevel()->sendBlocks([$player], [$target, $block], UpdateBlockPacket::FLAG_ALL_PRIORITY);

			break;

			case 0xff:
			case -1:  // -1 for 0.16
				$directionVector = $player->getDirectionVector();

				if($player->isCreative()) {
					$item = $inventory->getItemInHand();
				} elseif(!$handItem->equals($item)) {
					$inventory->sendHeldItem($player);
					return;
				} else {
					$item = $inventory->getItemInHand();
				}

				$player->getServer()->getPluginManager()->callEvent($ev = new PlayerInteractEvent($player, $item, $directionVector, $face, PlayerInteractEvent::RIGHT_CLICK_AIR));
				if($ev->isCancelled()) {
					$inventory->sendHeldItem($player);
					return;
				}

				if($item->onClickAir($player, $directionVector) and $player->isSurvival()){
					$inventory->setItemInHand($item);
				}

				if($item->getId() === Item::BOW and !$inventory->contains(ItemFactory::get(Item::ARROW, 0, 1))) {
					$player->setUsingItem(false); // attempting to draw a bow with no arrows
				} else {
					$player->setUsingItem(true);
				}

			break;
		}
	}

	/**
	 * Handle an incoming drop item request
	 *
	 * @param Item $item
	 */
	public function handleDropItem(Item $item) {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if($inventory->contains($item)) {
			$player->getServer()->getPluginManager()->callEvent($ev = new PlayerDropItemEvent($player, $item));
			if($ev->isCancelled()) {
				$inventory->sendContents($player);
				return;
			}

			$inventory->remove($item);

			$motion = $player->getDirectionVector()->multiply(0.4);
			$player->getLevel()->dropItem($player->asVector3()->add(0, 1.3, 0), $item, $motion, 40);
			$player->setUsingItem(false);
		} else {
			$inventory->sendContents($player);
		}
	}

	/**
	 * Handle an incoming container close request
	 *
	 * @param int $windowId
	 */
	public function handleContainerClose(int $windowId) {
		$player = $this->getPlayer();

		$player->craftingType = 0;
		$this->currentTransaction = null;

		if($player->getCurrentWindowId() === $windowId) {
			$player->getServer()->getPluginManager()->callEvent(new InventoryCloseEvent($window = $player->getCurrentWindow(), $player));
			$player->removeWindow($window);
		}
	}

	/**
	 * Handle an incoming container set slot request
	 *
	 * @param int $slot
	 * @param int $windowId
	 * @param Item $item
	 * @param int $hotbarSlot
	 */
	public function handleContainerSetSlot(int $slot, int $windowId, Item $item, int $hotbarSlot) {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if($slot < 0) {
			return;
		}

		switch($windowId) {
			case ContainerIds::TYPE_INVENTORY: // Normal inventory change
				if($slot >= $inventory->getSize()) {
					return;
				}

				$transaction = new BaseTransaction($inventory, $slot, $inventory->getItem($slot), $item);
				break;
			case ContainerIds::TYPE_ARMOR: // Armour change
				if($slot >= 4) {
					return;
				}

				$transaction = new BaseTransaction($inventory, $slot + $inventory->getSize(), $inventory->getArmorItem($slot), $item);
				break;

			case ContainerIds::TYPE_HOTBAR: // Hotbar link update
				// hotbarSlot 0-8, slot 9-44
				$inventory->setHotbarSlotIndex($hotbarSlot, $slot - 9);
				return;
			default:
				if($player->getCurrentWindowId() !== $windowId) {
					$player->getServer()->getLogger()->debug($player->getName() . " tried to set slot " . $slot . " on unknown window to " . $item . "");
					return; // unknown windowID and/or not matching any open windows
				}

				$player->craftingType = 0;
				$transaction = new BaseTransaction($inv = $player->getCurrentWindow(), $slot, $inv->getItem($slot), $item);
				break;
		}

		if($this->currentTransaction === null or $this->currentTransaction->getCreationTime() < (microtime(true) - 8)) {
			if($this->currentTransaction !== null) {
				foreach($this->currentTransaction->getInventories() as $inventory) {
					if($inventory instanceof PlayerInventory) {
						$inventory->sendArmorContents($player);
					}

					$inventory->sendContents($player);
				}
			}
			$this->currentTransaction = new SimpleTransactionGroup($player);
		}

		$this->currentTransaction->addTransaction($transaction);

		if($this->currentTransaction->canExecute()) {
			$this->currentTransaction->execute();

			$this->currentTransaction = null;
		}
	}

	/**
	 * Send a packet to open a container inventory
	 *
	 * @param ContainerInventory $inventory
	 */
	public function sendContainerOpen(ContainerInventory $inventory) {
		$player = $this->getPlayer();

		$pk = new ContainerOpenPacket();
		$pk->windowid = $player->getWindowId($inventory);
		$pk->type = $inventory->getType()->getNetworkType();
		$pk->slots = $inventory->getSize();
		$pk->entityId = $player->getId();
		$holder = $inventory->getHolder();
		if($holder instanceof Vector3){
			$pk->x = $holder->getX();
			$pk->y = $holder->getY();
			$pk->z = $holder->getZ();
		}else{
			$pk->x = $pk->y = $pk->z = 0;
		}

		$player->dataPacket($pk);
		$inventory->sendContents($player);
	}

	/**
	 * Send a packet to set an inventory's contents
	 *
	 * @param int $windowId
	 * @param Item[] $items
	 * @param Item[] $hotbarItems
	 */
	public function sendInventoryContents(int $windowId, array $items, array $hotbarItems = []) {
		$player = $this->getPlayer();

		$pk = new ContainerSetContentPacket();
		$pk->windowid = $windowId;
		$pk->slots = $items;
		$pk->hotbar = $hotbarItems;
		$pk->eid = $player->getId();

		$this->getPlayer()->dataPacket($pk);
	}

	/**
	 * Send a packet to set a slot in an inventory
	 *
	 * @param int $windowId
	 * @param Item $item
	 * @param int $slot
	 */
	public function sendInventorySlot(int $windowId, Item $item, int $slot) {
		$pk = new ContainerSetSlotPacket();
		$pk->windowid = $windowId;
		$pk->item = $item;
		$pk->slot = $slot;

		$this->getPlayer()->dataPacket($pk);
	}

	/**
	 * Send a packet to close a container inventory
	 *
	 * @param ContainerInventory $inventory
	 */
	public function sendContainerClose(ContainerInventory $inventory) {
		$player = $this->getPlayer();

		$pk = new ContainerClosePacket();
		$pk->windowid = $player->getWindowId($inventory);

		$player->dataPacket($pk);
	}

}
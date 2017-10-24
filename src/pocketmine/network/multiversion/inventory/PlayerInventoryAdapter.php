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
use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\DropItemPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\types\ContainerIds;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\Player;

/**
 * Class to assist with inventory transactions
 */
class PlayerInventoryAdapter {

	/** @var Player */
	private $player;

	public function __construct(Player $player) {
		$this->player = $player;
	}

	public function getPlayer() : Player {
		return $this->player;
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if($player->spawned == false or !$player->isAlive() or !($inventory instanceof PlayerInventory)) {
			return true;
		}

		if($packet->slot === 255) {
			$packet->slot = -1; // Cleared slot
		} else {
			if($packet->slot < 9) {
				$player->getServer()->getLogger()->debug($player->getName() . " tried to equip a slot that does not exist (index " . $packet->slot . ")");
				$inventory->sendContents($player);
				return false;
			}

			$packet->slot -= 9; // Get real inventory slot

			$item = $inventory->getItem($packet->slot);

			if(!$item->equals($packet->item)) {
				$player->getServer()->getLogger()->debug($player->getName() . " tried to equip " . $packet->item . " but has " . $item . " in target slot");
				$inventory->sendContents($player);
				return false;
			}
		}

		$inventory->equipItem($packet->selectedSlot, $packet->slot);

		$player->setUsingItem(false);

		return true;
	}

	public function handleMobArmorEquipment(MobArmorEquipmentPacket $packet) : bool {
		return true;
	}

	//public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool {
	//
	//}

	public function handleUseItem(UseItemPacket $packet) : bool {
		$player = $this->getPlayer();

		if($player->spawned == false or !$player->isAlive()) {
			return true;
		}

		$player->useItem($packet->item, $packet->hotbarSlot, $packet->face, new Vector3($packet->x, $packet->y, $packet->z), new Vector3($packet->fx, $packet->fy, $packet->fz));

		return true;
	}

	public function handleDropItem(DropItemPacket $packet) : bool {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if($player->spawned == false or !$player->isAlive() or !($inventory instanceof PlayerInventory)) {
			return true;
		}

		if($packet->item->getId() === Item::AIR) {
			// Windows 10 Edition drops the contents of the crafting grid on container close - including air.
			return true;
		}

		$item = $inventory->getItemInHand();
		$player->getServer()->getPluginManager()->callEvent($ev = new PlayerDropItemEvent($player, $item));
		if($ev->isCancelled()) {
			$inventory->sendContents($player);
			return true;
		}

		$inventory->setItemInHand(ItemFactory::get(Item::AIR, 0, 1));
		$motion = $player->getDirectionVector()->multiply(0.4);

		$player->getLevel()->dropItem($player->asVector3()->add(0, 1.3, 0), $item, $motion, 40);

		$player->setUsingItem(false);

		return true;
	}

	public function handleContainerClose(ContainerClosePacket $packet) : bool {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if($player->spawned == false or !$player->isAlive() or !($inventory instanceof PlayerInventory)) {
			return true;
		}

		$player->craftingType = 0;
		$player->currentTransaction = null;

		if($packet->windowid === $player->getCurrentWindowId()) {
			$player->getServer()->getPluginManager()->callEvent(new InventoryCloseEvent($window = $player->getCurrentWindow(), $player));
			$player->removeWindow($window);
		}

		return true;
	}

	public function handleContainerSetSlot(ContainerSetSlotPacket $packet) : bool {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if($player->spawned == false or !$player->isAlive() or !($inventory instanceof PlayerInventory)) {
			return true;
		}

		if($packet->slot < 0) {
			return false;
		}

		switch($packet->windowid) {
			case ContainerIds::TYPE_INVENTORY: // Normal inventory change
				if($packet->slot >= $inventory->getSize()) {
					return false;
				}

				$transaction = new BaseTransaction($inventory, $packet->slot, $inventory->getItem($packet->slot), $packet->item);
				break;
			case ContainerIds::TYPE_ARMOR: // Armour change
				if($packet->slot >= 4) {
					return false;
				}

				$transaction = new BaseTransaction($inventory, $packet->slot + $inventory->getSize(), $inventory->getArmorItem($packet->slot), $packet->item);
				break;

			case ContainerIds::TYPE_HOTBAR: // Hotbar link update
				// hotbarSlot 0-8, slot 9-44
				$inventory->setHotbarSlotIndex($packet->hotbarSlot, $packet->slot - 9);
				return true;
			default:
				if($packet->windowid !== $player->getCurrentWindowId()) {
					$player->getServer()->getLogger()->debug($player->getName() . " tried to set slot " . $packet->slot . " on unknown window to " . $packet->item . "");
					return false; // unknown windowID and/or not matching any open windows
				}

				$player->craftingType = 0;
				$transaction = new BaseTransaction($inv = $player->getCurrentWindow(), $packet->slot, $inv->getItem($packet->slot), $packet->item);
				break;
		}

		if($transaction->getSourceItem()->equals($transaction->getTargetItem()) and $transaction->getTargetItem()->getCount() === $transaction->getSourceItem()->getCount()) {
			// No changes, just a local inventory update sent by the client
			return true;
		}

		$player->addTransaction($transaction);

		return true;
	}

}
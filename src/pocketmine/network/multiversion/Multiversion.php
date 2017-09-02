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

declare(strict_types=1);

namespace pocketmine\network\multiversion;

use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerInventory120;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\v120\InventoryContentPacket;
use pocketmine\network\protocol\v120\InventorySlotPacket;

abstract class Multiversion {

	/**
	 *
	 * Create player inventory object base on player protocol
	 *
	 * @param Player $player
	 * @return PlayerInventory
	 */
	public static function getPlayerInventory(Player $player) {
		switch($player->protocol) {
			case ProtocolInfo::PROTOCOL_120:
				return new PlayerInventory120($player);
			default:
				return new PlayerInventory($player);
		}
	}

	/**
	 * Send all container's content
	 *
	 * @param Player $player
	 * @param int $windowId
	 * @param Item[] $items
	 */
	public static function sendContainer(Player $player, int $windowId, array $items) {
		$protocol = $player->getPlayerProtocol();
		if($protocol >= ProtocolInfo::PROTOCOL_120) {
			$pk = new InventoryContentPacket();
			$pk->inventoryID = $windowId;
			$pk->items = $items;
		} else {
			$pk = new ContainerSetContentPacket();
			$pk->windowid = $windowId;
			$pk->slots = $items;
			$pk->eid = $player->getId();
		}
		$player->dataPacket($pk);
	}

	/**
	 * Send one container's slot
	 *
	 * @param Player $player
	 * @param int $windowId
	 * @param Item $item
	 * @param int $slot
	 */
	public static function sendContainerSlot(Player $player, int $windowId, Item $item, int $slot) {
		$protocol = $player->getPlayerProtocol();
		if($protocol >= ProtocolInfo::PROTOCOL_120) {
			$pk = new InventorySlotPacket();
			$pk->containerId = $windowId;
			$pk->item = $item;
			$pk->slot = $slot;
		} else {
			$pk = new ContainerSetSlotPacket();
			$pk->windowid = $windowId;
			$pk->item = $item;
			$pk->slot = $slot;
		}
		$player->dataPacket($pk);
	}

}
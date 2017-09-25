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

namespace pocketmine\network\protocol\v120;

namespace pocketmine\network\protocol\v120;

abstract class Protocol120 {

	const CONTAINER_ID_NONE = -1;
	const CONTAINER_ID_INVENTORY = 0;
	const CONTAINER_ID_FIRST = 1;
	const CONTAINER_ID_LAST = 100;
	const CONTAINER_ID_OFFHAND = 119;
	const CONTAINER_ID_ARMOR = 120;
	const CONTAINER_ID_CREATIVE = 121;
	const CONTAINER_ID_SELECTION_SLOTS = 122;
	const CONTAINER_ID_FIXED_INVENTORY = 123;
	const CONTAINER_ID_CURSOR_SELECTED = 124;

	private static $disallowedPackets = [
		'pocketmine\network\protocol\AddItemPacket',
		'pocketmine\network\protocol\ContainerSetContentPacket',
		'pocketmine\network\protocol\ContainerSetSlotpacket',
		'pocketmine\network\protocol\DropItemPacket',
		'pocketmine\network\protocol\InventoryActionPacket',
		'pocketmine\network\protocol\ReplaceSelectedItemPacket',
		'pocketmine\network\protocol\RemoveBlockPacket',
		'pocketmine\network\protocol\UseItemPacket',
	];

	public static function getDisallowedPackets() : array {
		return self::$disallowedPackets;
	}

}
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

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>


class MobEquipmentPacket extends PEPacket {

	const NETWORK_ID = Info::MOB_EQUIPMENT_PACKET;
	const PACKET_NAME = "MOB_EQUIPMENT_PACKET";

	public $eid;
	public $item;
	public $inventorySlot;
	public $hotbarSlot;
	public $windowId = 0;

	const WINDOW_ID_PLAYER_OFFHAND = 0x77;

	public function decode(int $playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->eid = $this->getVarInt();
		$this->item = $this->getSlot($playerProtocol);
		$this->inventorySlot = $this->getByte();
		$this->hotbarSlot = $this->getByte();
		$this->windowId = $this->getByte();
	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->eid);
		$this->putSlot($this->item, $playerProtocol);
		$this->putByte($this->inventorySlot);
		$this->putByte($this->hotbarSlot);
		$this->putByte($this->windowId);
	}

}
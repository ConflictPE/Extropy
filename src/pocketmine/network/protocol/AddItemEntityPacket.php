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


class AddItemEntityPacket extends PEPacket {

	const NETWORK_ID = Info::ADD_ITEM_ENTITY_PACKET;
	const PACKET_NAME = "ADD_ITEM_ENTITY_PACKET";

	public $eid;
	public $item;
	public $x;
	public $y;
	public $z;
	public $speedX;
	public $speedY;
	public $speedZ;

	public function decode(int $playerProtocol) {

	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putEntityUniqueId($this->eid);
		$this->putEntityRuntimeId($this->eid);
		$this->putSlot($this->item, $playerProtocol);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVector3f($this->speedX, $this->speedY, $this->speedZ);
		$this->putVarInt(0); // metadata counts
	}

}
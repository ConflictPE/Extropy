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


class MoveEntityPacket extends PEPacket {

	const NETWORK_ID = Info::MOVE_ENTITY_PACKET;
	const PACKET_NAME = "MOVE_ENTITY_PACKET";

	// eid, x, y, z, yaw, pitch
	/** @var array[] */
	public $entities = [];

	public function clean() {
		$this->entities = [];
		return parent::clean();
	}

	public function decode(int $playerProtocol) {

	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		foreach($this->entities as $d){
			$this->putEntityRuntimeId($d[0]); //eid
			$this->putVector3f($d[1], $d[2], $d[3]);
			$this->putByte($d[6] * 0.71111); //pitch
			$this->putByte($d[5] * 0.71111); //headYaw
			$this->putByte($d[4] * 0.71111); //yaw
			/** @todo do it right */
			$this->putByte(true); // on ground
			$this->putByte(false); // has teleported
		}
	}

}
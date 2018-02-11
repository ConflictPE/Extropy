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


class StartGamePacket extends PEPacket {

	const NETWORK_ID = Info::START_GAME_PACKET;
	const PACKET_NAME = "START_GAME_PACKET";

	public $seed;
	public $dimension;
	public $generator = 1;
	public $gamemode;
	public $eid;
	public $spawnX;
	public $spawnY;
	public $spawnZ;
	public $x;
	public $y;
	public $z;
	public $yaw = 0;
	public $pitch = 0;
	public $currentTick = 0;
	public $dayCycleStopTime = 6000;

	public function decode(int $playerProtocol) {

	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->eid);
		$this->putVarInt($this->eid);

		if($playerProtocol >= Info::PROTOCOL_110) {
 			$this->putSignedVarInt($this->gamemode);
 		}

		$this->putVector3f($this->x, $this->y, $this->z);

		$this->putLFloat($this->yaw);
		$this->putLFloat($this->pitch);

		$this->putSignedVarInt($this->seed);
		$this->putSignedVarInt($this->dimension);
		$this->putSignedVarInt($this->generator);
		$this->putSignedVarInt($this->gamemode);
		$this->putSignedVarInt(0); // Difficulty

		$this->putBlockPosition($this->spawnX, $this->spawnY, $this->spawnZ);

		$this->putBool(true); // disable achievements?
		$this->putSignedVarInt($this->dayCycleStopTime); // DayCycleStopTime (-1 = not stopped, any other value = stopped at that time)
		$this->putBool(false); // edu mode?
		$this->putLFloat(0); // rain level
		$this->putLFloat(0); // lightning level

		if($playerProtocol >= Info::PROTOCOL_120) {
			$this->putBool(true); // is multiplayer game
			$this->putBool(true); // Broadcast to LAN?
			$this->putBool(true); // Broadcast to XBL?
		}

		$this->putBool(true); // enable commands?
		$this->putBool(false); // force texture packs?

		if($playerProtocol >= Info::PROTOCOL_120) {
			$this->putVarInt(0); // rules count
			$this->putBool(false); // is bonus chest enabled
			$this->putBool(false); // is start with map enabled
			$this->putBool(false); // has trust players enabled
			$this->putSignedVarInt(1); // permission level
			$this->putSignedVarInt(4); // game publish setting
			if($playerProtocol >= Info::PROTOCOL_201) {
				$this->putLInt(4); // server chunk tick radius
			}
			$this->putString('3138ee93-4a4a-479b-8dca-65ca5399e075'); // level id (random UUID)
			$this->putString(''); // level name
			$this->putString(''); // template pack id
			$this->putBool(false); // is trial?
			$this->putLong(0); // current level time
			$this->putSignedVarInt(0); // enchantment seed
		}
	}

}
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


#ifndef COMPILE
use pocketmine\utils\Binary;

#endif

class AddPlayerPacket extends PEPacket {

	const NETWORK_ID = Info::ADD_PLAYER_PACKET;
	const PACKET_NAME = "ADD_PLAYER_PACKET";

	public $uuid;
	public $username = "";
	public $eid;
	public $x;
	public $y;
	public $z;
	public $speedX = 0.0;
	public $speedY = 0.0;
	public $speedZ = 0.0;
	public $pitch;
	public $yaw;
	public $item;
	public $metadata;
	public $links = [];
	public $flags = 0;
	public $commandPermission = 0;
	public $actionPermissions = AdventureSettingsPacket::ACTION_FLAG_DEFAULT_LEVEL_PERMISSIONS;
	public $permissionLevel = AdventureSettingsPacket::PERMISSION_LEVEL_MEMBER;
	public $storedCustomPermissions = 0;

	public function decode(int $playerProtocol) {

	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putUUID($this->uuid);
		$this->putString($this->username);
		$this->putVarInt($this->eid); // TODO: correct eid and runtimeId's
		$this->putVarInt($this->eid);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVector3f($this->speedX, $this->speedY, $this->speedZ);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		$this->putLFloat($this->yaw);// TODO: Correct head rotation
		$this->putSignedVarInt(0); // TODO: Fix held item

		$this->put(Binary::writeMetadata($this->metadata, $playerProtocol));

		if($playerProtocol >= Info::PROTOCOL_120) {
			$this->putVarInt($this->flags);
			$this->putVarInt($this->commandPermission);
			$this->putVarInt($this->actionPermissions);
			$this->putVarInt($this->permissionLevel);
			$this->putVarInt($this->storedCustomPermissions);
			// we should put eid as long but in signed varint format
			// maybe i'm wrong but it works
			if($this->eid & 1) { // userId is odd
				$this->putLLong(-1 * (($this->eid + 1) >> 1));
			} else { // userId is even
				$this->putLLong($this->eid >> 1);
			}
			$this->putVarInt(count($this->links));
			foreach($this->links as $link) {
				$this->putVarInt($link['from']);
				$this->putVarInt($link['to']);
				$this->putByte($link['type']);
				$this->putByte(0);
			}
		}
	}

}
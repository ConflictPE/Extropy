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


class MovePlayerPacket extends PEPacket {

	const NETWORK_ID = Info::MOVE_PLAYER_PACKET;
	const PACKET_NAME = "MOVE_PLAYER_PACKET";

	const MODE_NORMAL = 0;
	const MODE_RESET = 1;
	const MODE_TELEPORT = 2;
	const MODE_ROTATION = 3;

	const TELEPORTATION_CAUSE_UNKNOWN = 0;
	const TELEPORTATION_CAUSE_PROJECTILE = 1;
	const TELEPORTATION_CAUSE_CHORUS_FRUIT = 2;
	const TELEPORTATION_CAUSE_COMMAND = 3;
	const TELEPORTATION_CAUSE_BEHAVIOR = 4;
	const TELEPORTATION_CAUSE_COUNT = 5; // ???

	public $eid;
	public $x;
	public $y;
	public $z;
	public $yaw;
	public $bodyYaw;
	public $pitch;
	public $mode = self::MODE_NORMAL;
	public $onGround;

	public function clean() {
		$this->teleport = false;
		return parent::clean();
	}

	public function decode(int $playerProtocol) {
		$this->eid = $this->getEntityRuntimeId();

		$this->getVector3f($this->x, $this->y, $this->z);

		$this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();

		$this->bodyYaw = $this->getLFloat();
		$this->mode = $this->getByte();
		$this->onGround = $this->getByte() > 0;
	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putEntityRuntimeId($this->eid);

		$this->putVector3f($this->x, $this->y, $this->z);

		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);

		$this->putLFloat($this->bodyYaw);
		$this->putByte($this->mode);
		$this->putByte($this->onGround > 0);

		$this->putEntityRuntimeId(0); // riding runtime ID
		if($this->mode === self::MODE_TELEPORT) {
			$this->putLInt(self::TELEPORTATION_CAUSE_UNKNOWN);
			$this->putLInt(0);
		}
	}

}

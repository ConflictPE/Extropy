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


class MobEffectPacket extends PEPacket {

	const NETWORK_ID = Info::MOB_EFFECT_PACKET;
	const PACKET_NAME = "MOB_EFFECT_PACKET";

	const EVENT_ADD = 1;
	const EVENT_MODIFY = 2;
	const EVENT_REMOVE = 3;

	public $eid;
	public $eventId;
	public $effectId;
	public $amplifier = 0;
	public $particles = true;
	public $duration = 0;

	public function decode(int $playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->eid = $this->getVarInt();
		$this->eventId = $this->getByte();
		$this->effectId = $this->getSignedVarInt();
		$this->amplifier = $this->getSignedVarInt();
		$this->particles = $this->getBool();
		$this->duration = $this->getSignedVarInt();
	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->eid);
		$this->putByte($this->eventId);
		$this->putSignedVarInt($this->effectId);
		$this->putSignedVarInt($this->amplifier);
		$this->putBool($this->particles);
		$this->putSignedVarInt($this->duration);
	}

}
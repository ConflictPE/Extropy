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


class UpdateAttributesPacket extends PEPacket {

	const NETWORK_ID = Info::UPDATE_ATTRIBUTES_PACKET;
	const PACKET_NAME = "UPDATE_ATTRIBUTES_PACKET";

	const ABSORPTION = "minecraft:absorption";
	const HEALTH = "minecraft:health";
	const HUNGER = "minecraft:player.hunger";
	const EXPERIENCE = "minecraft:player.experience";
	const EXPERIENCE_LEVEL = "minecraft:player.level";
	const SPEED = "minecraft:movement";

	public $entityId;

	public $minValue;
	public $maxValue;
	public $value;
	public $name;
	public $defaultValue;

	public function decode(int $playerProtocol) {

	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->entityId);
		$this->putVarInt(1);
		$this->putLFloat($this->minValue);
		$this->putLFloat($this->maxValue);
		$this->putLFloat($this->value);
		$this->putLFloat($this->defaultValue);
		$this->putString($this->name);
	}

}
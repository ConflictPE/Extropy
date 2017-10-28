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


class ContainerSetDataPacket extends PEPacket {

	const NETWORK_ID = Info::CONTAINER_SET_DATA_PACKET;
	const PACKET_NAME = "CONTAINER_SET_DATA_PACKET";

	public $windowId;
	public $property;
	public $value;

	public function decode(int $playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->windowId = $this->getByte();
		$this->property = $this->getSignedVarInt();
		$this->value = $this->getSignedVarInt();
	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putByte($this->windowId);
		$this->putSignedVarInt($this->property);
		$this->putSignedVarInt($this->value);
	}

}
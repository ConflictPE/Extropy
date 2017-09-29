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

#include <rules/DataPacket.h>


use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class PlayerHotbarPacket extends PEPacket {

	const NETWORK_ID = Info120::PLAYER_HOTBAR_PACKET;
	const PACKET_NAME = "PLAYER_HOTBAR_PACKET";

	public $selectedSlot;
	public $slotsLink;

	public function decode(int $playerProtocol) {

	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->selectedSlot);
		$this->putByte(0); // container ID, 0 - player inventory
		$slotsNum = count($this->slotsLink);
		$this->putVarInt($slotsNum);
		for($i = 0; $i < $slotsNum; $i++) {
			$this->putVarInt($this->slotsLink[$i]);
		}
		$this->putByte(false); // Should select slot (don't know how it works)
	}

}
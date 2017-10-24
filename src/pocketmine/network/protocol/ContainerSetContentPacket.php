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


use pocketmine\network\protocol\types\ContainerIds;

class ContainerSetContentPacket extends PEPacket {

	const NETWORK_ID = Info::CONTAINER_SET_CONTENT_PACKET;
	const PACKET_NAME = "CONTAINER_SET_CONTENT_PACKET";

	public $windowid;
	public $eid = 0;
	public $slots = [];
	public $hotbar = [];

	public function clean() {
		$this->slots = [];
		$this->hotbar = [];
		return parent::clean();
	}

	public function decode(int $playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->windowid = $this->getVarInt();

		$count = $this->getVarInt();
		for($s = 0; $s < $count and !$this->feof(); ++$s) {
			$this->slots[$s] = $this->getSlot($playerProtocol);
		}

		$hotbarCount = $this->getVarInt(); //MCPE always sends this, even when it's not a player inventory
		for($s = 0; $s < $hotbarCount and !$this->feof(); ++$s) {
			$this->hotbar[$s] = $this->getSignedVarInt();
		}
	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putByte($this->windowid);

		if($playerProtocol >= Info::PROTOCOL_110) {
			$this->putVarInt($this->eid);
		}

		$this->putVarInt(count($this->slots));
		foreach($this->slots as $slot) {
			$this->putSlot($slot, $playerProtocol);
		}
		if($this->windowid === ContainerIds::TYPE_INVENTORY and count($this->hotbar) > 0) {
			$this->putVarInt(count($this->hotbar));
			foreach($this->hotbar as $slot) {
				$this->putSignedVarInt($slot);
			}
		} else {
			$this->putVarInt(0);
		}
	}

}
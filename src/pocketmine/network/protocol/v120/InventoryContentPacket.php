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


use pocketmine\inventory\InventoryType;
use pocketmine\item\Item;
use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class InventoryContentPacket extends PEPacket {

	const NETWORK_ID = Info120::INVENTORY_CONTENT_PACKET;
	const PACKET_NAME = "INVENTORY_CONTENT_PACKET";

	/** @var int */
	public $windowId;

	/** @var Item[] */
	public $items = [];

	public function decode(int $playerProtocol) {
		$this->getHeader();
		$this->windowId = $this->getVarInt();
		$count = $this->getVarInt();
		for($i = 0; $i < $count; ++$i){
			$this->items[] = $this->getSlot($playerProtocol);
		}
	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);

		$this->putVarInt($this->windowId);
		$this->putVarInt(count($this->items));
		foreach($this->items as $item) {
			$this->putSlot($item, $playerProtocol);
		}
	}

}
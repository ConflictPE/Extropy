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


use pocketmine\item\Item;
use pocketmine\utils\UUID;

class CraftingEventPacket extends PEPacket {

	const NETWORK_ID = Info::CRAFTING_EVENT_PACKET;
	const PACKET_NAME = "CRAFTING_EVENT_PACKET";

	public $windowId;
	public $type;

	/** @var UUID */
	public $id;

	/** @var Item[] */
	public $input = [];

	/** @var Item[] */
	public $output = [];

	public function clean() {
		$this->input = [];
		$this->output = [];
		return parent::clean();
	}

	public function decode(int $playerProtocol) {
		$this->windowId = $this->getByte();
		$this->type = $this->getSignedVarInt();
		$this->id = $this->getUUID();

		$size = $this->getVarInt();
		for($i = 0; $i < $size and $i < 128; ++$i) {
			$this->input[] = $this->getSlot($playerProtocol);
		}

		$size = $this->getVarInt();
		for($i = 0; $i < $size and $i < 128; ++$i) {
			$this->output[] = $this->getSlot($playerProtocol);
		}
	}

	public function encode(int $playerProtocol) {

	}

}
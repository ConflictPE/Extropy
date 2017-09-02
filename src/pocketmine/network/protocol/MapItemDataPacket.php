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


class MapItemDataPacket extends PEPacket {

	const NETWORK_ID = Info::CLIENTBOUND_MAP_ITEM_DATA_PACKET;
	const PACKET_NAME = "CLIENTBOUND_MAP_ITEM_DATA_PACKET";

	public $mapId;
	public $flags;
	public $scale;
	public $width;
	public $height;
	public $data;
	public $pointners = [];

	public function decode(int $playerProtocol) {

	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		if($playerProtocol >= Info::PROTOCOL_120) {
			$this->putByte(0); // dimension
		}
		$this->putSignedVarInt($this->mapId);
		$this->putVarInt($this->flags);
		switch($this->flags) {
			case 2:
				$this->putByte($this->scale);
				$this->putSignedVarInt($this->width);
				$this->putSignedVarInt($this->height);
				$this->putSignedVarInt(0);
				$this->putSignedVarInt(0);
				if ($playerProtocol >= Info::PROTOCOL_120) {
					$this->putVarInt($this->width * $this->height);
				}
				$this->put($this->data);
				break;
			case 4:
				$this->putByte($this->scale);
				$this->putVarInt(count($this->pointners));
				foreach($this->pointners as $pointner) {
					if($playerProtocol >= Info::PROTOCOL_120) {
						$this->putByte($pointner['type']);
						$this->putByte($pointner['rotate']);
					} else {
						$this->putSignedVarInt($pointner['type'] << 4 | $pointner['rotate']);
					}
					if($pointner['x'] > 0x7f) {
						$pointner['x'] = 0x7f;
					}
					if($pointner['x'] < -0x7f) {
						$pointner['x'] = -0x7f;
					}
					if($pointner['z'] > 0x7f) {
						$pointner['z'] = 0x7f;
					}
					if($pointner['z'] < -0x7f) {
						$pointner['z'] = -0x7f;
					}
					$this->putByte($pointner['x']);
					$this->putByte($pointner['z']);
					$this->putString('');
					if($playerProtocol >= Info::PROTOCOL_120) {
						$this->putVarInt(hexdec($pointner['color']));
					} else {
						$this->putLInt(hexdec($pointner['color']));
					}
				}
				break;
		}
	}

}
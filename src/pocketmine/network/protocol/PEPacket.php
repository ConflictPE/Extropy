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


abstract class PEPacket extends DataPacket {

	const CLIENT_ID_MAIN_PLAYER = 0;
	const CLIENT_ID_SERVER = 0;

	public $senderSubClientID = self::CLIENT_ID_SERVER;

	public $targetSubClientID = self::CLIENT_ID_MAIN_PLAYER;

	abstract public function encode(int $playerProtocol);

	abstract public function decode(int $playerProtocol);

	/**
	 * !IMPORTANT! Should be called at first line in decode
	 * @param integer $playerProtocol
	 */
	protected function getHeader(int $playerProtocol = 0) {
		if($playerProtocol >= Info::PROTOCOL_120) {
			$this->senderSubClientID = $this->getByte();
			$this->targetSubClientID = $this->getByte();
		}
	}

	/**
	 * !IMPORTANT! Should be called at first line in encode
	 * @param integer $playerProtocol
	 */
	public function reset(int $playerProtocol = 0) {
		$this->buffer = chr(self::$packetsIds[$playerProtocol][$this::PACKET_NAME]);
		$this->offset = 0;
		if($playerProtocol >= Info::PROTOCOL_120) {
			$this->putByte($this->senderSubClientID);
			$this->putByte($this->targetSubClientID);
			$this->offset = 2;
		}
	}

	public final static function convertProtocol(int $protocol) : int {
		switch ($protocol) {
			case Info::PROTOCOL_201:
				return Info::PROTOCOL_201;
			case Info::PROTOCOL_134:
			case Info::PROTOCOL_135:
			case Info::PROTOCOL_136:
			case Info::PROTOCOL_137:
			case Info::PROTOCOL_140:
			case Info::PROTOCOL_141:
			case Info::PROTOCOL_150:
			case Info::PROTOCOL_160:
				return Info::PROTOCOL_120;
			case Info::PROTOCOL_110:
			case Info::PROTOCOL_111:
			case Info::PROTOCOL_112:
			case Info::PROTOCOL_113:
				return Info::PROTOCOL_110;
			case Info::PROTOCOL_105:
			case Info::PROTOCOL_106:
			case Info::PROTOCOL_107:
				return Info::PROTOCOL_105;
			default:
				return Info::BASE_PROTOCOL;
		}
	}

}
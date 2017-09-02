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

class CommandRequestPacket extends PEPacket {

	const NETWORK_ID = Info120::COMMAND_REQUEST_PACKET;
	const PACKET_NAME = "COMMAND_REQUEST_PACKET";

	const TYPE_PLAYER = 0;
	const TYPE_COMMAND_BLOCK = 1;
	const TYPE_MINECART_COMMAND_BLOCK = 2;
	const TYPE_DEV_CONSOLE = 3;
	const TYPE_AUTOMATION_PLAYER = 4;
	const TYPE_CLIENT_AUTOMATION = 5;
	const TYPE_DEDICATED_SERVER = 6;
	const TYPE_ENTITY = 7;
	const TYPE_VIRTUAL = 8;
	const TYPE_GAME_ARGUMENT = 9;
	const TYPE_INTERNAL = 10;

	/** @var string */
	public $command = "";

	/** @var int */
	public $commandType = self::TYPE_PLAYER;

	/** @var string */
	public $requestId = "";

	/** @var integer */
	public $playerId = "";

	public function decode(int $playerProtocol) {
		$this->command = $this->getString();
		$this->commandType = $this->getVarInt();
		$this->requestId = $this->getString();
		if($this->commandType == self::TYPE_DEV_CONSOLE) {
			$this->playerId = $this->getSignedVarInt();
		}
	}

	public function encode(int $playerProtocol) {

	}

}
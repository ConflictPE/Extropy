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


class SetTitlePacket extends PEPacket {

	const NETWORK_ID = Info105::SET_TITLE_PACKET;
	const PACKET_NAME = "SET_TITLE_PACKET";

	const TYPE_CLEAR_TITLE = 0;
	const TYPE_RESET_TITLE = 1;
	const TYPE_SET_TITLE = 2;
	const TYPE_SET_SUBTITLE = 3;
	const TYPE_SET_ACTIONBAR_MESSAGE = 4;
	const TYPE_SET_ANIMATION_TIMES = 5;

	/** @var int */
	public $type;

	/** @var string */
	public $text = "";

	/** @var int */
	public $fadeInTime = 0;

	/** @var int */
	public $stayTime = 0;

	/** @var int */
	public $fadeOutTime = 0;

	public function decode(int $playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->type = $this->getSignedVarInt();
		$this->text = $this->getString();
		$this->fadeInTime = $this->getSignedVarInt();
		$this->stayTime = $this->getSignedVarInt();
		$this->fadeOutTime = $this->getSignedVarInt();
	}

	public function encode(int $playerProtocol) {
		$this->reset($playerProtocol);
		$this->putSignedVarInt($this->type);
		$this->putString($this->text);
		$this->putSignedVarInt($this->fadeInTime);
		$this->putSignedVarInt($this->stayTime);
		$this->putSignedVarInt($this->fadeOutTime);
	}

}
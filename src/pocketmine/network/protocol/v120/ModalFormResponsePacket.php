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

class ModalFormResponsePacket extends PEPacket {

	const NETWORK_ID = Info120::MODAL_FORM_RESPONSE_PACKET;
	const PACKET_NAME = "MODAL_FORM_RESPONSE_PACKET";

	public $formId;
	public $data;

	public function encode(int $playerProtocol) {

	}

	/**
	 * Data will be null if player close form without submit
	 * (by cross button or ESC)
	 *
	 * @param integer $playerProtocol
	 */
	public function decode(int $playerProtocol) {
		$this->formId = $this->getVarInt();
		$this->data = $this->getString();
	}

}
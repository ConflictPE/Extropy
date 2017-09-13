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

namespace pocketmine\player;

use pocketmine\network\protocol\TextPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

/**
 * Class to handle chat message scheduling
 */
class MessageQueue {

	/** @var Player */
	private $owner;

	/** @var string[] */
	private $queue = [];

	public function __construct(Player $player) {
		$this->owner = $player;
	}

	public function getOwner() : Player {
		return $this->owner;
	}

	/**
	 * @param string $message
	 */
	public function addItem(string $message) {
		$mes = explode("\n", $message);
		foreach($mes as $m){
			if($m !== ""){
				$this->queue[] = $m;
			}
		}
	}

	/**
	 * @return bool
	 */
	public function hasItemsInQueue() : bool {
		return count($this->queue) > 0;
	}

	/**
	 * @return string
	 */
	public function getNextMessage() : string {
		return array_shift($this->queue);
	}

	/**
	 * Loop over the current message queue and combine everything into one long message and send it to the player
	 */
	public function doTick() {
		if($this->hasItemsInQueue()) {
			$message = "";
			while($this->hasItemsInQueue()) {
				$message .= TextFormat::RESET . "\n" . $this->getNextMessage();
			}
			$this->owner->sendDirectMessage(rtrim($message, "\n"));
		}
	}

}
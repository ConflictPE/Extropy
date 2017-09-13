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

use pocketmine\Player;

class TipQueue {

	const BASE_TIP_DURATION = 8; // 8 ticks

	/** @var Player */
	private $owner;

	/** @var array[] */
	private $queue = [];

	/** @var array */
	private $currentItem = []; // information about the current tip being processed/being displayed [message, duration, ticksDisplayed]

	public function __construct(Player $player) {
		$this->owner = $player;
	}

	public function getOwner() : Player {
		return $this->owner;
	}

	/**
	 * @param string $message
	 * @param int $duration     How long the popup should last for in ticks
	 */
	public function addItem(string $message, int $duration) {
		$this->queue[] = ["message" => $message, "duration" => $duration];
	}

	/**
	 * @return bool
	 */
	public function hasItemsInQueue() : bool {
		return count($this->queue) > 0;
	}

	/**
	 * @return array
	 */
	public function getNextTip() : array {
		return array_shift($this->queue);
	}

	/**
	 * Loop over the current message queue and combine everything into one long message and send it to the player
	 */
	public function doTick() {
		if(!empty($this->currentItem) and $this->currentItem["ticksDisplayed"] < $this->currentItem["duration"]) { // process current tip
			if($this->currentItem["ticksDisplayed"] % self::BASE_TIP_DURATION == 0) { // if the tip has been displayed for as long as it is displayed to the client
				$this->owner->sendDirectTip($this->currentItem["message"]);
			}
			$this->currentItem["ticksDisplayed"]++;
		} else {
			if($this->hasItemsInQueue()) { // process new popup
				if(empty($this->currentItem) or $this->currentItem["ticksDisplayed"] >= $this->currentItem["duration"]) { // process next tip in queue
					$item = $this->getNextTip();
					$this->currentItem = ["message" => $item["message"], "duration" => $item["duration"], "ticksDisplayed" => 1];
					$this->owner->sendDirectTip($this->currentItem["message"]);
				}
			}
		}
	}

}
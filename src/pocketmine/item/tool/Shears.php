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

namespace pocketmine\item\tool;

use pocketmine\block\Block;
use pocketmine\Player;

class Shears extends Tool {

	public function __construct(int $meta = 0){
		parent::__construct(self::SHEARS, $meta, "Shears");
	}

	public function getMaxDurability() : int {
		return 238;
	}

	public function onBlockBreak(Player $player, Block $block) : bool {
		if($this->isUnbreakable() or $block->willDamageTools() or ($block->getId() !== Block::COBWEB and $block->getId() !== Block::LEAVES and $block->getId() !== Block::WOOL and $block->getId() !== Block::VINE)) {
			return false;
		}
		$this->meta++;

		return true;
	}

}
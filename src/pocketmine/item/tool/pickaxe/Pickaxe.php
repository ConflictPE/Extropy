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

namespace pocketmine\item\tool\pickaxe;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\tool\Tool;
use pocketmine\item\tool\ToolTier;
use pocketmine\Player;

abstract class Pickaxe extends Tool implements ToolTier {

	public function onBlockBreak(Player $player, Block $block) : bool {
		if($this->isUnbreakable() or !$block->willDamageTools()) {
			return false;
		}
		$this->meta++;

		return true;
	}

	public function onEntityAttack(Player $player, Entity $target) : bool {
		if($this->isUnbreakable()){
			return false;
		}
		$this->meta += 2;

		return true;
	}

}
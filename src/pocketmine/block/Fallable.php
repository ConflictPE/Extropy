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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;

abstract class Fallable extends Solid {

	public function onUpdate(int $type) {
		if($type === Level::BLOCK_UPDATE_NORMAL) {
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if($down->getId() === self::AIR or ($down instanceof Liquid)) {
				$this->level->setBlock($this, BlockFactory::get(Block::AIR), true, true);
				$fall = Entity::createEntity("FallingSand", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), new Compound("", [
					new Enum("Pos", [
						new DoubleTag("", $this->x + 0.5),
						new DoubleTag("", $this->y),
						new DoubleTag("", $this->z + 0.5),
					]),
					new Enum("Motion", [
						new DoubleTag("", 0),
						new DoubleTag("", 0),
						new DoubleTag("", 0),
					]),
					new Enum("Rotation", [
						new FloatTag("", 0),
						new FloatTag("", 0),
					]),
					new IntTag("TileID", $this->getId()),
					new ByteTag("Data", $this->getDamage()),
				]));
				$fall->spawnToAll();
			}
		}
	}

}
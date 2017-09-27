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

namespace pocketmine\item\projectile;

use pocketmine\item\food\Potion;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\Player;

class SplashPotion extends ProjectileItem {

	public function __construct($meta = 0){
		parent::__construct(self::SPLASH_POTION, 0, $this->getNameByMeta($meta));
	}

	public function getName() : string {
		return self::getNameByMeta($this->meta);
	}

	public function getNameByMeta(int $meta) {
		return "Splash " . Potion::getNameByMeta($meta);
	}

	public function getMaxStackSize() : int {
		return 1;
	}

	/**
	 * Default projectile spawn compound
	 *
	 * @param Player $player
	 * @param Vector3 $direction
	 *
	 * @return Compound
	 */
	public function getProjectileSpawnCompound(Player $player, Vector3 $direction) : Compound {
		return new Compound("", [
			new Enum("Pos", [
				new DoubleTag("", $player->x),
				new DoubleTag("", $player->y + $player->getEyeHeight()),
				new DoubleTag("", $player->z)
			]),
			new Enum("Motion", [
				new DoubleTag("", $direction->x),
				new DoubleTag("", $direction->y),
				new DoubleTag("", $direction->z)
			]),
			new Enum("Rotation", [
				new FloatTag("", $player->yaw),
				new FloatTag("", $player->pitch)
			]),
			new ShortTag("PotionId", $this->meta),
		]);
	}

	public function getProjectileEntityType() : string {
		return "ThrownPotion";
	}

	public function getThrowForce() : float {
		return 1.1;
	}

}
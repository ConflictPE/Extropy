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

use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\Player;

abstract class ProjectileItem extends Item {

	/**
	 * Entity type to be created
	 *
	 * @return string
	 */
	abstract public function getProjectileEntityType() : string;

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
		]);
	}

	/**
	 * Force to be applied to entity motion
	 *
	 * @return float
	 */
	abstract public function getThrowForce() : float;

	/**
	 * Spawn the projectile when a player clicks/interacts with air
	 *
	 * @param Player $player
	 * @param Vector3 $directionVector
	 *
	 * @return bool
	 */
	public function onClickAir(Player $player, Vector3 $directionVector) : bool {
		$projectile = Entity::createEntity($this->getProjectileEntityType(), $player->chunk, $this->getProjectileSpawnCompound($player, $directionVector), $player);
		$projectile->setMotion($projectile->getMotion()->multiply($this->getThrowForce()));

		$this->count--;

		if($projectile instanceof Projectile){
			$player->getServer()->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($projectile));
			if($projectileEv->isCancelled()){
				$projectile->kill();
			}else{
				$projectile->spawnToAll();
				$player->getLevel()->addSound(new LaunchSound($player), $player->getViewers());
			}
		}else{
			$projectile->spawnToAll();
		}

		return true;
	}

}
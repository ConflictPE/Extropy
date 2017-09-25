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

use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\fuel\FuelSource;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\Player;

class Bow extends Tool implements FuelSource {

	public function __construct(int $meta = 0) {
		parent::__construct(self::BOW, $meta, "Bow");
	}

	public function getMaxDurability() : int {
		return 385;
	}

	public function getFuelTime() : int {
		return 200;
	}

	public function onReleaseUsing(Player $player) : bool {
		if($player->isSurvival() and !$player->getInventory()->contains(ItemFactory::get(Item::ARROW, 0, 1))) {
			$player->getInventory()->sendContents($player);
			return false;
		}

		$directionVector = $player->getDirectionVector();

		$nbt = new Compound("", [
			new Enum("Pos", [
				new DoubleTag("", $player->x),
				new DoubleTag("", $player->y + $player->getEyeHeight()),
				new DoubleTag("", $player->z)
			]),
			new Enum("Motion", [
				new DoubleTag("", $directionVector->x),
				new DoubleTag("", $directionVector->y),
				new DoubleTag("", $directionVector->z)
			]),
			new Enum("Rotation", [
				//yaw/pitch for arrows taken crosswise, not along the arrow shaft.
				new FloatTag("", ($player->yaw > 180 ? 360 : 0) - $player->yaw), //arrow yaw must range from -180 to +180
				new FloatTag("", -$player->pitch)
			]),
			new ShortTag("Fire", $player->isOnFire() ? 45 * 60 : 0)
		]);

		$diff = $player->getItemUseDuration();
		$p = $diff / 20;
		$force = min((($p ** 2) + $p * 2) / 3, 1) * 2;


		$entity = Entity::createEntity("Arrow", $player->chunk, $nbt, $player, $force == 2);
		if($entity instanceof Projectile) {
			$ev = new EntityShootBowEvent($player, $this, $entity, $force);

			if($force < 0.1 or $diff < 5) {
				$ev->setCancelled();
			}

			$player->getServer()->getPluginManager()->callEvent($ev);

			$entity = $ev->getProjectile(); //This might have been changed by plugins

			if($ev->isCancelled()) {
				$entity->kill();
				$player->getInventory()->sendContents($player);
			} else {
				$entity->setMotion($entity->getMotion()->multiply($ev->getForce()));
				if($player->isSurvival()) {
					$player->getInventory()->removeItem(ItemFactory::get(Item::ARROW, 0, 1));
					if(!$this->isUnbreakable()) {
						$this->setDamage($this->getDamage() + 1);
						if($this->getDamage() >= $this->getMaxDurability()) {
							$player->getInventory()->setItemInHand(ItemFactory::get(Item::AIR, 0, 0));
						} else {
							$player->getInventory()->setItemInHand($this);
						}
					}
				}

				if($entity instanceof Projectile) {
					$player->getServer()->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($entity));
					if($projectileEv->isCancelled()) {
						$ev->getProjectile()->kill();
					} else {
						$ev->getProjectile()->spawnToAll();
						$player->level->addSound(new LaunchSound($player), $player->getViewers());
					}
				} else {
					$entity->spawnToAll();
				}
			}
		} else {
			$entity->spawnToAll();
		}

		return true;
	}

}
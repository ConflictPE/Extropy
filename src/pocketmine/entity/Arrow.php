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

namespace pocketmine\entity;

use pocketmine\level\format\FullChunk;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class Arrow extends Projectile{

	const NETWORK_ID = 80;
	public $width = 0.5;
	public $length = 0.5;
	public $height = 0.5;
	protected $gravity = 0.03;
	protected $drag = 0.01;
	protected $damage = 2;

	public function __construct(FullChunk $chunk, Compound $nbt, Entity $shootingEntity = null, $critical = false){
		$this->isCritical = (bool) $critical;
		parent::__construct($chunk, $nbt, $shootingEntity);
		$this->setCritical($critical);
	}

	public function isCritical() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_CRITICAL);
	}

	public function setCritical(bool $value = true){
		$this->setGenericFlag(self::DATA_FLAG_CRITICAL, $value);
	}

	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::onUpdate($currentTick);

		if($this->hadCollision and $this->isCritical()){
			$this->setCritical(false);
			$hasUpdate = true; // send the changed data flag to players
		}

		if($this->age > 1200){
			$this->kill();
			$hasUpdate = true;
		}

		return $hasUpdate;
	}

	/**
	 * Add extra damage to fully drawn bow shots
	 *
	 * @param Entity $with
	 * @param int $damage
	 */
	public function onEntityCollide(Entity $with, int $damage) {
		if($this->isCritical()) {
			$damage += mt_rand(0, (int) ($damage / 2) + 1);
		}

		parent::onEntityCollide($with, $damage);
	}

	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->type = Arrow::NETWORK_ID;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
//		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);
		parent::spawnTo($player);
	}

}
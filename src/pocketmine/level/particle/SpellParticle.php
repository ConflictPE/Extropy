<?php

/**
 *
 *  _____      _
 * | ____|_  _| |_ _ __ ___  _ __  _   _
 * |  _| \ \/ / __| '__/ _ \| '_ \| | | |
 * | |___ >  <| |_| | | (_) | |_) | |_| |
 * |_____/_/\_\\__|_|  \___/| .__/ \__, |
 *                          |_|    |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Jack Noordhuis
 * @link   https://github.com/CrazedCraft/Extropy
 *
 *
 */

namespace pocketmine\level\particle;

use pocketmine\math\Vector3;
use pocketmine\network\protocol\LevelEventPacket;

class SpellParticle extends GenericParticle {

	public function __construct(Vector3 $pos, $r = 0, $g = 0, $b = 0, $a = 255) {
		parent::__construct($pos, LevelEventPacket::EVENT_PARTICLE_SPLASH, (($a & 0xff) << 24) | (($r & 0xff) << 16) | (($g & 0xff) << 8) | ($b & 0xff));
	}

	public function encode() {
		$pk = new LevelEventPacket();
		$pk->evid = LevelEventPacket::EVENT_PARTICLE_SPLASH;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->data = $this->data;
		return $pk;
	}

}
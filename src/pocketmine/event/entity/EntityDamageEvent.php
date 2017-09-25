<?php

/**
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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\entity;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\Player;

class EntityDamageEvent extends EntityEvent implements Cancellable{
	public static $handlerList = null;

	const MODIFIER_BASE = 0;
	const MODIFIER_ARMOR = 1;
	const MODIFIER_STRENGTH = 2;
	const MODIFIER_WEAKNESS = 3;
	const MODIFIER_RESISTANCE = 4;
	// attack effect modifiers
	const MODIFIER_EFFECT_SHARPNESS = 5;
	const MODIFIER_EFFECT_SMITE = 6;
	const MODIFIER_EFFECT_ARTHROPODOS = 7;
	const MODIFIER_EFFECT_KNOCKBACK = 8;
	// defence effect modifiers
	const MODIFIER_EFFECT_PROTECTION = 9;
	const MODIFIER_EFFECT_FIRE_PROTECTION = 10;
	const MODIFIER_EFFECT_BLAST_PROTECTION = 11;
	const MODIFIER_EFFECT_PROJECTILE_PROTECTION = 12;
	const MODIFIER_EFFECT_FALL_PROTECTION = 13;
	// enchantment effect modifiers
	const MODIFIER_ARMOR_ENCHANTMENTS = 14;


	const CAUSE_ENTITY_ATTACK = 1;
	const CAUSE_PROJECTILE = 2;
	const CAUSE_SUFFOCATION = 3;
	const CAUSE_FALL = 4;
	const CAUSE_FIRE = 5;
	const CAUSE_FIRE_TICK = 6;
	const CAUSE_LAVA = 7;
	const CAUSE_DROWNING = 8;
	const CAUSE_BLOCK_EXPLOSION = 9;
	const CAUSE_ENTITY_EXPLOSION = 10;
	const CAUSE_VOID = 11;
	const CAUSE_SUICIDE = 12;
	const CAUSE_MAGIC = 13;
	const CAUSE_CUSTOM = 14;
	const CAUSE_CONTACT = 15;
	const CAUSE_STARVATION = 15;


	private $cause;
	/** @var array */
	private $modifiers;
	private $originals;

	/** @var int */
	private $fireTickReduction = 0;

	/** @var int */
	private $blastKnockbackReduction = 0;


	/**
	 * @param Entity    $entity
	 * @param int       $cause
	 * @param int|int[] $damage
	 *
	 * @throws \Exception
	 */
	public function __construct(Entity $entity, $cause, $damage){
		$this->entity = $entity;
		$this->cause = $cause;
		if(is_array($damage)){
			$this->modifiers = $damage;
		}else{
			$this->modifiers = [
				self::MODIFIER_BASE => $damage
			];
		}

		$this->originals = $this->modifiers;

		if(!isset($this->modifiers[self::MODIFIER_BASE])){
			throw new \InvalidArgumentException("BASE Damage modifier missing");
		}

		if($entity instanceof Player) {
			$this->calculateArmorEnchantmentModifiers($entity);
		}
	}

	private function calculateArmorEnchantmentModifiers(Player $entity) {
		$enchantments = $entity->getProtectionEnchantments();
		if(!empty($enchantments)) {
			$epf = 0;
			$fireTickReduction = [];
			$blastKnockbackReduction = [];
			/** @var Enchantment $enchant */
			foreach($enchantments as $enchant) {
				switch($enchant->getId()) {
					case Enchantment::TYPE_ARMOR_PROTECTION:
						if(!in_array($this->cause, [self::CAUSE_VOID, self::CAUSE_SUICIDE])) {
							$epf += $enchant->getLevel();
						}
						break;
					case Enchantment::TYPE_ARMOR_FIRE_PROTECTION:
						if(in_array($this->cause, [self::CAUSE_FIRE, self::CAUSE_FIRE_TICK, self::CAUSE_LAVA])) {
							$epf += $enchant->getLevel() * 2;
							if($entity->isOnFire()) {
								$fireTickReduction[] = ($entity->fireTicks * (15 * $enchant->getLevel())) / 100;
							}
						}
						break;
					case Enchantment::TYPE_ARMOR_FALL_PROTECTION:
						if($this->cause === self::CAUSE_FALL) {
							$epf += $enchant->getLevel() * 3;
						}
						break;
					case Enchantment::TYPE_ARMOR_EXPLOSION_PROTECTION:
						if($this->cause === self::CAUSE_BLOCK_EXPLOSION) {
							$epf += $enchant->getLevel() * 2;
							if($this instanceof EntityDamageByEntityEvent) {
								$blastKnockbackReduction[] = ($this->getKnockBack() * (15 * $enchant->getLevel())) / 100;
							}
						}
						break;
					case Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION:
						if($this->cause === self::CAUSE_PROJECTILE) {
							$epf += $enchant->getLevel() * 2;
						}
						break;
				}
			}
			if($epf > 0) {
				$this->setDamage(-floor($this->getFinalDamage() * 1 - (min($epf, 20) / 25)), self::MODIFIER_ARMOR_ENCHANTMENTS);
			}
			if(!empty($fireTickReduction)) { // make sure there is at least one enchantment that reduced the fire ticks
				$this->fireTickReduction -= max($fireTickReduction); // get the highest reduction and store it
			}
			if(!empty($blastKnockbackReduction) and $this instanceof EntityDamageByEntityEvent) { // make sure there was at least on enchantment that reduced the knockback
				$this->blastKnockbackReduction = $this->getKnockBack() - max($fireTickReduction); // get the highest reduction and store it
			}
		}
	}

	/**
	 * @return int
	 */
	public function getCause(){
		return $this->cause;
	}

	/**
	 * @param int $type
	 *
	 * @return int
	 */
	public function getOriginalDamage($type = self::MODIFIER_BASE){
		if(isset($this->originals[$type])){
			return $this->originals[$type];
		}

		return 0;
	}

	/**
	 * @param int $type
	 *
	 * @return int
	 */
	public function getDamage($type = self::MODIFIER_BASE){
		if(isset($this->modifiers[$type])){
			return $this->modifiers[$type];
		}

		return 0;
	}

	/**
	 * @param float $damage
	 * @param int   $type
	 *
	 * @throws \UnexpectedValueException
	 */
	public function setDamage($damage, $type = self::MODIFIER_BASE){
		$this->modifiers[$type] = $damage;
	}

	/**
	 * @param int $type
	 *
	 * @return bool
	 */
	public function isApplicable($type){
		return isset($this->modifiers[$type]);
	}

	/**
	 * @return int
	 */
	public function getFinalDamage(){
		$damage = 0;
		foreach($this->modifiers as $type => $d){
			$damage += $d;
		}

		return max($damage, 0);
	}

	/**
	 * Applies the enchantment effects to the entity and event
	 * after verifying the event wasn't cancelled
	 */
	public function applyEnchantmentEffects() {
		if($this->fireTickReduction > 0) {
			$this->entity->fireTicks -= $this->fireTickReduction;
		}
		if($this->blastKnockbackReduction > 0 and $this instanceof EntityDamageByEntityEvent) {
			$this->setKnockBack($this->getKnockBack() - $this->blastKnockbackReduction);
		}
	}

}
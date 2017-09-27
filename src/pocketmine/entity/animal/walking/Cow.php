<?php

namespace pocketmine\entity\animal\walking;

use pocketmine\entity\animal\WalkingAnimal;
use pocketmine\entity\Creature;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\Player;

class Cow extends WalkingAnimal{
	const NETWORK_ID = 11;

	public $width = 1.45;
	public $height = 1.12;

	public function getName(){
		return "Cow";
	}

	public function initEntity(){
		parent::initEntity();

		$this->setMaxHealth(10);
	}

	public function targetOption(Creature $creature, float $distance){
		if($creature instanceof Player){
			return $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::WHEAT && $distance <= 49;
		}
		return false;
	}

	public function getDrops(){
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
			switch(mt_rand(0, 1)){
				case 0:
					return [ItemFactory::get(Item::RAW_BEEF, 0, 1)];
				case 1:
					return [ItemFactory::get(Item::LEATHER, 0, 1)];
			}
		}
		return [];
	}

}
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

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\inventory\EnderChestInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item as ItemItem;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\multiversion\Multiversion;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\Player;
use pocketmine\utils\UUID;

class Human extends Creature implements ProjectileSource, InventoryHolder{

	const DATA_PLAYER_FLAG_SLEEP = 1;
	const DATA_PLAYER_FLAG_DEAD = 2; //TODO: CHECK

	const DATA_PLAYER_FLAGS = 27;

	const DATA_PLAYER_BED_POSITION = 29;


	const MAX_FOOD = 20.0;
	const MIN_FOOD = 0.0;

	const MAX_SATURATION = 20.0;
	const MIN_SATURATION = 0.0;

	const MAX_EXHAUSTION = 5.0;
	const MIN_EXHAUSTION = 0.0;

	/** @var PlayerInventory */
	protected $inventory;

	/** @var EnderChestInventory */
	protected $enderChestInventory;

	/** @var UUID */
	protected $uuid;
	protected $rawUUID;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;
	public $eyeHeight = 1.62;

	protected $skin;
	protected $skinName = 'Standard_Custom';
	protected $skinGeometryName = "geometry.humanoid.custom";
	protected $skinGeometryData = "";
	protected $capeData = "";

	/** @var float */
	protected $food = self::MAX_FOOD;

	/** @var int */
	protected $foodTickTimer = 0;

	/** @var float */
	protected $saturation = self::MAX_SATURATION;

	/** @var float */
	protected $exhaustion = self::MIN_EXHAUSTION;

	public function __construct(FullChunk $chunk, Compound $nbt){
		if($this->skin === "" and (!isset($nbt->Skin) or !isset($nbt->Skin->Data) or !Player::isValidSkin($nbt->Skin->Data->getValue()))) {
			throw new \InvalidStateException((new \ReflectionClass($this))->getShortName() . " must have a valid skin set");
		}

		parent::__construct($chunk, $nbt);
	}

	public function getSkinData() {
		return $this->skin;
	}

	public function getSkinName() {
		return $this->skinName;
	}

	public function getSkinGeometryName() {
		return $this->skinGeometryName;
	}

	public function getSkinGeometryData() {
		return $this->skinGeometryData;
	}

	public function getCapeData() {
		return $this->capeData;
	}

	/**
	 * @return UUID|null
	 */
	public function getUniqueId() {
		return $this->uuid;
	}

	/**
	 * @return string
	 */
	public function getRawUniqueId() {
		return $this->rawUUID;
	}

	/**
	 * @param $str
	 * @param $skinName
	 * @param string $skinGeometryName
	 * @param string $skinGeometryData
	 * @param string $capeData
	 */
	public function setSkin(string $str, string $skinName, $skinGeometryName = "", $skinGeometryData = "", $capeData = "") {
		if(!Player::isValidSkin($str)) {
			throw new \InvalidStateException("Specified skin is not valid, must be 8KiB or 16KiB");
		}

		$this->skin = $str;
		$this->skinName = $skinName;

		if($skinGeometryName !== "") {
			$this->skinGeometryName = $skinGeometryName;
		}
		if($skinGeometryData !== "") {
			$this->skinGeometryData = $skinGeometryData;
		}
		if($capeData !== "") {
			$this->capeData = $capeData;
		}
	}

	public function jump() {
		if($this->isSprinting()) {
			$this->exhaust(0.8, PlayerExhaustEvent::CAUSE_SPRINT_JUMPING);
		} else {
			$this->exhaust(0.2, PlayerExhaustEvent::CAUSE_JUMPING);
		}
	}

	public function getFood() : float {
		return $this->food;
	}

	/**
	 * WARNING: This method does not check if full and may may crash the client if out of bounds.
	 * Use {@link Human::addFood()} for this purpose
	 *
	 * @param float $new
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setFood(float $new) {
		$old = $this->food;
		$this->food = $new;

		$reset = false;
		// ranges: 18-20 (regen), 7-17 (none), 1-6 (no sprint), 0 (health depletion)
		foreach([17, 6, 0] as $bound) {
			if(($old > $bound) !== ($new > $bound)) {
				$reset = true;
				break;
			}
		}
		if($reset) {
			$this->foodTickTimer = 0;
		}
	}

	public function addFood(float $amount) {
		$amount += $this->food;
		$this->food = max(min($amount, self::MAX_FOOD), self::MIN_FOOD);
	}

	public function getSaturation() : float {
		return $this->saturation;
	}

	/**
	 * WARNING: This method does not check if saturated and may crash the client if out of bounds.
	 * Use {@link Human::addSaturation()} for this purpose
	 *
	 * @param float $saturation
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setSaturation(float $saturation) {
		$this->saturation = $saturation;
	}

	public function addSaturation(float $amount) {
		$amount += $this->saturation;
		$this->saturation += max(min($amount, self::MAX_SATURATION), self::MIN_SATURATION);
	}

	public function getExhaustion() : float {
		return $this->exhaustion;
	}

	/**
	 * WARNING: This method does not check if exhausted and does not consume saturation/food.
	 * Use {@link Human::exhaust()} for this purpose.
	 *
	 * @param float $exhaustion
	 */
	public function setExhaustion(float $exhaustion){
		$this->exhaustion = $exhaustion;
	}

	/**
	 * Increases a human's exhaustion level.
	 *
	 * @param float $amount
	 * @param int   $cause
	 *
	 * @return float the amount of exhaustion level increased
	 */
	public function exhaust(float $amount, int $cause = PlayerExhaustEvent::CAUSE_CUSTOM) : float{
		$this->server->getPluginManager()->callEvent($ev = new PlayerExhaustEvent($this, $amount, $cause));
		if($ev->isCancelled()){
			return 0.0;
		}

		$exhaustion = $this->getExhaustion();
		$exhaustion += $ev->getAmount();

		while($exhaustion >= 4.0) {
			$exhaustion -= 4.0;

			if($this->saturation > 0) {
				$this->saturation = max(0, $this->saturation - 1.0);
			} else {
				if($this->food > 0) {
					$this->food;
				}
			}
		}
		$this->setExhaustion($exhaustion);

		return $ev->getAmount();
	}

	public function getInventory() {
		return $this->inventory;
	}

	public function getEnderChestInventory() {
		return $this->enderChestInventory;
	}

	/**
	 * For Human entities which are not players, sets their properties such as nametag, skin and UUID from NBT.
	 */
	protected function initHumanData(){
		if(isset($this->namedtag->NameTag)){
			$this->setNameTag($this->namedtag["NameTag"]);
		}

		if(isset($this->namedtag->Skin) and $this->namedtag->Skin instanceof Compound){
			$this->setSkin($this->namedtag->Skin["Data"], $this->namedtag->Skin["Name"]);
		}

		$this->uuid = UUID::fromData((string) $this->getId(), $this->getSkinData(), $this->getNameTag());
	}

	protected function initEntity(){

		$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, false);
		$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [0, 0, 0]);
		$this->setNameTagVisible(true);
		$this->setNameTagAlwaysVisible(true);

		if($this instanceof Player){
			$this->inventory = Multiversion::getPlayerInventory($this);
		} else {
			$this->inventory = new PlayerInventory($this);
		}

		$this->enderChestInventory = new EnderChestInventory($this, ($this->namedtag->EnderChestInventory ?? null));

		$this->initHumanData();

		if(isset($this->namedtag->Inventory) and $this->namedtag->Inventory instanceof Enum) {
			foreach($this->namedtag->Inventory as $i => $item) {
				if($item["Slot"] >= 0 and $item["Slot"] < 9){ //Hotbar
					//Old hotbar saving stuff, remove it (useless now)
					unset($this->namedtag->Inventory->{$i});
				} elseif($item["Slot"] >= 100 and $item["Slot"] < 104) { //Armor
					$this->inventory->setItem($this->inventory->getSize() + $item["Slot"] - 100, ItemItem::nbtDeserialize($item));
				} else {
					$this->inventory->setItem($item["Slot"] - 9, ItemItem::nbtDeserialize($item));
				}
			}
		}

		if(isset($this->namedtag->SelectedInventorySlot) and $this->namedtag->SelectedInventorySlot instanceof IntTag) {
			$this->inventory->setHeldItemIndex($this->namedtag->SelectedInventorySlot->getValue(), false);
		} else {
			$this->inventory->setHeldItemIndex(0, false);
		}

		parent::initEntity();

		if(!isset($this->namedtag->foodLevel) or !($this->namedtag->foodLevel instanceof IntTag)) {
			$this->namedtag->foodLevel = new IntTag("foodLevel", (int) $this->getFood());
		} else {
			$this->food = (float) $this->namedtag["foodLevel"];
		}

		if(!isset($this->namedtag->foodExhaustionLevel) or !($this->namedtag->foodExhaustionLevel instanceof FloatTag)) {
			$this->namedtag->foodExhaustionLevel = new FloatTag("foodExhaustionLevel", $this->getExhaustion());
		} else {
			$this->exhaustion =(float) $this->namedtag["foodExhaustionLevel"];
		}

		if(!isset($this->namedtag->foodSaturationLevel) or !($this->namedtag->foodSaturationLevel instanceof FloatTag)) {
			$this->namedtag->foodSaturationLevel = new FloatTag("foodSaturationLevel", $this->getSaturation());
		} else {
			$this->saturation = (float) $this->namedtag["foodSaturationLevel"];
		}
	}

	public function entityBaseTick($tickDiff = 1) : bool {
		$hasUpdate = parent::entityBaseTick($tickDiff);

		$this->doFoodTick($tickDiff);

		return $hasUpdate;
	}

	public function doFoodTick(int $tickDiff = 1) {
		if($this->isAlive()){
			$health = $this->getHealth();
			$difficulty = $this->server->getDifficulty();

			$this->foodTickTimer += $tickDiff;
			if($this->foodTickTimer >= 80) {
				$this->foodTickTimer = 0;
			}

			if($difficulty === 0 and $this->foodTickTimer % 10 === 0) { //Peaceful
				if($this->food < 20) {
					$this->addFood(1.0);
				}
				if($this->foodTickTimer % 20 === 0 and $health < $this->getMaxHealth()) {
					$this->heal(1, new EntityRegainHealthEvent($this, 1, EntityRegainHealthEvent::CAUSE_SATURATION));
				}
			}

			if($this->foodTickTimer === 0) {
				if($this->food >= 18) {
					if($health < $this->getMaxHealth()) {
						$this->heal(1, new EntityRegainHealthEvent($this, 1, EntityRegainHealthEvent::CAUSE_SATURATION));
						$this->exhaust(3.0, PlayerExhaustEvent::CAUSE_HEALTH_REGEN);
					}
				} elseif($this->food <= 0) {
					if(($difficulty === 1 and $health > 10) or ($difficulty === 2 and $health > 1) or $difficulty === 3) {
						$this->attack(1, new EntityDamageEvent($this, EntityDamageEvent::CAUSE_STARVATION, 1));
					}
				}
			}

			if($this->food <= 6) {
				if($this->isSprinting()){
					$this->setSprinting(false);
				}
			}
		}
	}

	public function getName(){
		return $this->getNameTag();
	}

	public function getDrops(){
		$drops = [];
		if($this->inventory !== null){
			foreach($this->inventory->getContents() as $item){
				$drops[] = $item;
			}
		}

		return $drops;
	}

	public function saveNBT(){
		parent::saveNBT();
		$this->namedtag->Inventory = new Enum("Inventory", []);
		$this->namedtag->Inventory->setTagType(NBT::TAG_Compound);
		if($this->inventory !== null){
			//Normal inventory
			$slotCount = $this->inventory->getSize() + $this->inventory->getHotbarSize();
			for($slot = $this->inventory->getHotbarSize(); $slot < $slotCount; ++$slot) {
				$item = $this->inventory->getItem($slot - 9);
				if($item->getId() !== ItemItem::AIR) {
					$this->namedtag->Inventory[$slot] = $item->nbtSerialize($slot);
				}
			}

			//Armor
			for($slot = 100; $slot < 104; ++$slot) {
				$item = $this->inventory->getItem($this->inventory->getSize() + $slot - 100);
				if($item instanceof ItemItem and $item->getId() !== ItemItem::AIR) {
					$this->namedtag->Inventory[$slot] = $item->nbtSerialize($slot);
				}
			}

			$this->namedtag->SelectedInventorySlot = new IntTag("SelectedInventorySlot", $this->inventory->getHeldItemIndex());
		}

		$this->namedtag->EnderChestInventory = new Enum("EnderChestInventory", []);
		$this->namedtag->Inventory->setTagType(NBT::TAG_Compound);
		if($this->enderChestInventory !== null){
			for($slot = 0; $slot < $this->enderChestInventory->getSize(); $slot++){
				if(($item = $this->enderChestInventory->getItem($slot)) instanceof ItemItem){
					$this->namedtag->EnderChestInventory[$slot] = $item->nbtSerialize($slot);
				}
			}
		}
	}

	public function spawnTo(Player $player){
		if($player !== $this and !isset($this->hasSpawned[$player->getId()])  and isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])){
			$this->hasSpawned[$player->getId()] = $player;

			$xuid = ($this instanceof Player) ? $this->getXUID() : "";
			$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getName(), $this->skinName, $this->skin, $this->skinGeometryName, $this->skinGeometryData, $this->capeData, $xuid, [$player]);

			$pk = new AddPlayerPacket();
			$pk->uuid = $this->getUniqueId();
			$pk->username = $this->getName();
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->item = $this->getInventory()->getItemInHand();
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);

			$this->inventory->sendArmorContents($player);
			$this->level->addPlayerHandItem($this, $player);

			if(!($this instanceof Player)){
				$this->server->removePlayerListData($this->getUniqueId(), [$player]);
			}
		}
	}

	public function despawnFrom(Player $player){
		if(isset($this->hasSpawned[$player->getId()])){
			$pk = new RemoveEntityPacket();
			$pk->eid = $this->getId();
			$player->dataPacket($pk);
			unset($this->hasSpawned[$player->getId()]);
			if ($this instanceof Player){
				$this->server->removePlayerListData($this->getUniqueId(), [$player]);
			}
		}
	}

	public function close(){
		if(!$this->closed){
			if(!($this instanceof Player) or $this->loggedIn){
				foreach($this->inventory->getViewers() as $viewer){
					$viewer->removeWindow($this->inventory);
				}
			}
			parent::close();
		}
	}

	/**
	 * Wrapper around {@link Entity#getDataFlag} for player-specific data flag reading.
	 *
	 * @param int $flagId
	 * @return bool
	 */
	public function getPlayerFlag(int $flagId) : bool{
		return $this->getDataFlag(self::DATA_PLAYER_FLAGS, $flagId);
	}

	/**
	 * Wrapper around {@link Entity#setDataFlag} for player-specific data flag setting.
	 *
	 * @param int  $flagId
	 * @param bool $value
	 */
	public function setPlayerFlag(int $flagId, bool $value = true){
		$this->setDataFlag(self::DATA_PLAYER_FLAGS, $flagId, $value, self::DATA_TYPE_BYTE);
	}

}
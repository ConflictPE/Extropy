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

/**
 * All the entity classes
 */
namespace pocketmine\entity;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Water;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Timings;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\metadata\Metadatable;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\MobEffectPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Binary;
use pocketmine\utils\ChunkException;
use pocketmine\block\Liquid;

use pocketmine\block\Cobweb;
use pocketmine\block\Fire;
use pocketmine\block\Ladder;
use pocketmine\block\Vine;

abstract class Entity extends Location implements Metadatable{


	const NETWORK_ID = -1;

	const DIRECTION_SOUTH = 0;
	const DIRECTION_WEST = 1;
	const DIRECTION_NORTH = 2;
	const DIRECTION_EAST = 3;

	const DATA_TYPE_BYTE = 0;
	const DATA_TYPE_SHORT = 1;
	const DATA_TYPE_INT = 2;
	const DATA_TYPE_FLOAT = 3;
	const DATA_TYPE_STRING = 4;
	const DATA_TYPE_SLOT = 5;
	const DATA_TYPE_POS = 6;
	const DATA_TYPE_LONG = 7;
	const DATA_TYPE_VECTOR3 = 8;

	const DATA_FLAGS = "DATA_FLAGS";
	const DATA_HEALTH = "DATA_HEALTH"; //int (minecart/boat)
	const DATA_VARIANT = "DATA_VARIANT"; //int
	const DATA_COLOR = "DATA_COLOR", DATA_COLOUR = "DATA_COLOR"; //byte
	const DATA_NAMETAG = "DATA_NAMETAG"; //string
	const DATA_OWNER_EID = "DATA_OWNER_EID"; //long
	const DATA_TARGET_EID = "DATA_TARGET_EID"; //long
	const DATA_AIR = "DATA_AIR"; //short
	const DATA_POTION_COLOR = "DATA_POTION_COLOR"; //int (ARGB!)
	const DATA_POTION_AMBIENT = "DATA_POTION_AMBIENT"; //byte
	/* 10 (byte) */
	const DATA_HURT_TIME = "DATA_HURT_TIME"; //int (minecart/boat)
	const DATA_HURT_DIRECTION = "DATA_HURT_DIRECTION"; //int (minecart/boat)
	const DATA_PADDLE_TIME_LEFT = "DATA_PADDLE_TIME_LEFT"; //float
	const DATA_PADDLE_TIME_RIGHT = "DATA_PADDLE_TIME_RIGHT"; //float
	const DATA_EXPERIENCE_VALUE = "DATA_EXPERIENCE_VALUE"; //int (xp orb)
	const DATA_MINECART_DISPLAY_BLOCK = "DATA_MINECART_DISPLAY_BLOCK"; //int (id | (data << 16))
	const DATA_MINECART_DISPLAY_OFFSET = "DATA_MINECART_DISPLAY_OFFSET"; //int
	const DATA_MINECART_HAS_DISPLAY = "DATA_MINECART_HAS_DISPLAY"; //byte (must be 1 for minecart to show block inside)

	//TODO: add more properties

	const DATA_ENDERMAN_HELD_ITEM_ID = "DATA_ENDERMAN_HELD_ITEM"; //short
	const DATA_ENDERMAN_HELD_ITEM_DAMAGE = "DATA_ENDERMAN_HELD_ITEM_DAMAGE"; //short
	const DATA_ENTITY_AGE = "DATA_ENTITY_AGE"; //short

	/* 27 (byte) player-specific flags
	 * 28 (int) player "index"?
	 * 29 (block coords) bed position */
	const DATA_FIREBALL_POWER_X = "DATA_FIREBALL_POWER_X"; //float
	const DATA_FIREBALL_POWER_Y = "DATA_FIREBALL_POWER_Y";
	const DATA_FIREBALL_POWER_Z = "DATA_FIREBALL_POWER_Z";
	/* 33 (unknown)
	 * 34 (float) fishing bobber
	 * 35 (float) fishing bobber
	 * 36 (float) fishing bobber */
	const DATA_POTION_AUX_VALUE = "DATA_POTION_AUX_VALUE"; //short
	const DATA_LEAD_HOLDER_EID = "DATA_LEAD_OLDER_EID"; //long
	const DATA_SCALE = "DATA_SCALE"; //float
	const DATA_INTERACTIVE_TAG = "DATA_INTERACTIVE_TAG"; //string (button text)
	const DATA_NPC_SKIN_ID = "DATA_NPC_SKIN_ID"; //string
	const DATA_URL_TAG = "DATA_URL_TAG"; //string
	const DATA_MAX_AIR = "DATA_MAX_AIR"; //short
	const DATA_MARK_VARIANT = "DATA_MARK_VARIANT"; //int
	/* 45 (byte) container stuff
	 * 46 (int) container stuff
	 * 47 (int) container stuff */
	const DATA_BLOCK_TARGET = "DATA_BLOCK_TARGET"; //block coords (ender crystal)
	const DATA_WITHER_INVULNERABLE_TICKS = "DATA_WITHER_INVULNERABLE_TICKS"; //int
	const DATA_WITHER_TARGET_1 = "DATA_WITHER_TARGET_1"; //long
	const DATA_WITHER_TARGET_2 = "DATA_WITHER_TARGET_2"; //long
	const DATA_WITHER_TARGET_3 = "DATA_WITHER_TARGET_3"; //long
	/* 53 (short) */
	const DATA_BOUNDING_BOX_WIDTH = "DATA_BOUNDING_BOX_WIDTH"; //float
	const DATA_BOUNDING_BOX_HEIGHT = "DATA_BOUNDING_BOX_HEIGHT"; //float
	const DATA_FUSE_LENGTH = "DATA_FUSE_LENGTH"; //int
	const DATA_RIDER_SEAT_POSITION = "DATA_RIDER_SEAT_POSITION"; //vector3f
	const DATA_RIDER_ROTATION_LOCKED = "DATA_RIDER_ROTATION_LOCKED"; //byte
	const DATA_RIDER_MAX_ROTATION = "DATA_RIDER_MAX_ROTATION"; //float
	const DATA_RIDER_MIN_ROTATION = "DATA_RIDER_MIN_ROTATION"; //float
	const DATA_AREA_EFFECT_CLOUD_RADIUS = "DATA_AREA_EFFECT_CLOUD_RADIUS"; //float
	const DATA_AREA_EFFECT_CLOUD_WAITING = "DATA_AREA_EFFECT_CLOUD_RADIUS"; //int
	const DATA_AREA_EFFECT_CLOUD_PARTICLE_ID = "DATA_AREA_EFFECT_CLOUD_PARTICLE_ID"; //int
	/* 64 (int) shulker-related */
	const DATA_SHULKER_ATTACH_FACE = "DATA_SHULKER_ATTACH_FACE"; //byte
	/* 66 (short) shulker-related */
	const DATA_SHULKER_ATTACH_POS = "DATA_SHULKER_ATTACH_POS"; //block coords
	const DATA_TRADING_PLAYER_EID = "DATA_TRADING_PLAYER_EID"; //long

	/* 70 (byte) command-block */
	const DATA_COMMAND_BLOCK_COMMAND = "DATA_COMMAND_BLOCK_COMMAND"; //string
	const DATA_COMMAND_BLOCK_LAST_OUTPUT = "DATA_COMMAND_BLOCK_LAST_OUTPUT"; //string
	const DATA_COMMAND_BLOCK_TRACK_OUTPUT = "DATA_COMMAND_BLOCK_TRACK_OUTPUT"; //byte
	const DATA_CONTROLLING_RIDER_SEAT_NUMBER = "DATA_CONTROLLING_RIDER_SEAT_NUMBER"; //byte
	const DATA_STRENGTH = "DATA_STRENGTH"; //int
	const DATA_MAX_STRENGTH = "DATA_MAX_STRENGTH"; //int
	/* 77 (int)
	 * 78 (int) */


	const DATA_FLAG_ONFIRE = "DATA_FLAG_ONFIRE";
	const DATA_FLAG_SNEAKING = "DATA_FLAG_SNEAKING";
	const DATA_FLAG_RIDING = "DATA_FLAG_RIDING";
	const DATA_FLAG_SPRINTING = "DATA_FLAG_SPRINTING";
	const DATA_FLAG_ACTION = "DATA_FLAG_ACTION";
	const DATA_FLAG_INVISIBLE = "DATA_FLAG_INVISIBLE";
	const DATA_FLAG_TEMPTED = "DATA_FLAG_TEMPTED";
	const DATA_FLAG_INLOVE = "DATA_FLAG_INLOVE";
	const DATA_FLAG_SADDLED = "DATA_FLAG_SADDLED";
	const DATA_FLAG_POWERED = "DATA_FLAG_POWERED";
	const DATA_FLAG_IGNITED = "DATA_FLAG_IGNITED";
	const DATA_FLAG_BABY = "DATA_FLAG_BABY";
	const DATA_FLAG_CONVERTING = "DATA_FLAG_CONVERTING";
	const DATA_FLAG_CRITICAL = "DATA_FLAG_CRITICAL";
	const DATA_FLAG_CAN_SHOW_NAMETAG = "DATA_FLAG_CAN_SHOW_NAMETAG";
	const DATA_FLAG_ALWAYS_SHOW_NAMETAG = "DATA_FLAG_ALWAYS_SHOW_NAMETAG";
	const DATA_FLAG_IMMOBILE = "DATA_FLAG_IMMOBILE", DATA_FLAG_NO_AI = "DATA_FLAG_IMMOBILE";
	const DATA_FLAG_SILENT = "DATA_FLAG_SILENT";
	const DATA_FLAG_WALLCLIMBING = "DATA_FLAG_WALLCLIMBING";
	const DATA_FLAG_CAN_CLIMB = "DATA_FLAG_CAN_CLIMB";
	const DATA_FLAG_SWIMMER = "DATA_FLAG_SWIMMER";
	const DATA_FLAG_CAN_FLY = "DATA_FLAG_CAN_FLY";
	const DATA_FLAG_RESTING = "DATA_FLAG_RESTING";
	const DATA_FLAG_SITTING = "DATA_FLAG_SITTING";
	const DATA_FLAG_ANGRY = "DATA_FLAG_ANGRY";
	const DATA_FLAG_INTERESTED = "DATA_FLAG_INTERESTED";
	const DATA_FLAG_CHARGED = "DATA_FLAG_CHARGED";
	const DATA_FLAG_TAMED = "DATA_FLAG_TAMED";
	const DATA_FLAG_LEASHED = "DATA_FLAG_LEASHED";
	const DATA_FLAG_SHEARED = "DATA_FLAG_SHEARED";
	const DATA_FLAG_GLIDING = "DATA_FLAG_GLIDING";
	const DATA_FLAG_ELDER = "DATA_FLAG_ELDER";
	const DATA_FLAG_MOVING = "DATA_FLAG_MOVING";
	const DATA_FLAG_BREATHING = "DATA_FLAG_BREATHING";
	const DATA_FLAG_CHESTED = "DATA_FLAG_CHESTED";
	const DATA_FLAG_STACKABLE = "DATA_FLAG_STACKABLE";
	const DATA_FLAG_SHOWBASE = "DATA_FLAG_SHOWBASE";
	const DATA_FLAG_REARING = "DATA_FLAG_REARING";
	const DATA_FLAG_VIBRATING = "DATA_FLAG_VIBRATING";
	const DATA_FLAG_IDLING = "DATA_FLAG_IDLING";
	const DATA_FLAG_EVOKER_SPELL = "DATA_FLAG_EVOKER_SPELL";
	const DATA_FLAG_CHARGE_ATTACK = "DATA_FLAG_CHARGE_ATTACK";
	const DATA_FLAG_WASD_CONTROLLED = "DATA_FLAG_WASD_CONTROLLED";
	const DATA_FLAG_CAN_POWER_JUMP = "DATA_FLAG_CAN_POWER_JUMP";
	const DATA_FLAG_LINGER = "DATA_FLAG_LINGER";
	const DATA_FLAG_HAS_COLLISION = "DATA_FLAG_HAS_COLLISION";
	const DATA_FLAG_AFFECTED_BY_GRAVITY = "DATA_FLAG_AFFECTED_BY_GRAVITY";
	const DATA_FLAG_FIRE_IMMUNE = "DATA_FLAG_FIRE_IMMUNE";
	const DATA_FLAG_DANCING = "DATA_FLAG_DANCING";

	const DATA_PLAYER_FLAG_SLEEP = 1;
	const DATA_PLAYER_FLAG_DEAD = 2;

	public static $entityCount = 2;
	/** @var Entity[] */
	private static $knownEntities = [];
	private static $shortNames = [];

	/**
	 * @var Player[]
	 */
	protected $hasSpawned = [];

	/** @var Effect[] */
	protected $effects = [];

	protected $id;

	protected $dataFlags = 0;
	protected $dataProperties = [
		self::DATA_FLAGS => [self::DATA_TYPE_LONG, []],
		self::DATA_AIR => [self::DATA_TYPE_SHORT, 400],
		self::DATA_MAX_AIR => [self::DATA_TYPE_SHORT, 400],
		self::DATA_NAMETAG => [self::DATA_TYPE_STRING, ""],
		self::DATA_LEAD_HOLDER_EID => [self::DATA_TYPE_LONG, -1]
	];

	protected $changedDataProperties = [];

	public $passenger = null;
	public $vehicle = null;

	/** @var int */
	public $chunkX;
	/** @var int */
	public $chunkZ;

	/** @var Chunk */
	public $chunk;

	protected $lastDamageCause = null;

	public $lastX = null;
	public $lastY = null;
	public $lastZ = null;

	public $motionX;
	public $motionY;
	public $motionZ;
	public $lastMotionX;
	public $lastMotionY;
	public $lastMotionZ;

	public $lastYaw;
	public $lastPitch;

	/** @var AxisAlignedBB */
	public $boundingBox;
	public $onGround;
	public $inBlock = false;
	public $positionChanged;
	public $motionChanged;
	public $dead;
	public $deadTicks = 0;
	protected $age = 0;

	public $height;

	public $eyeHeight = null;

	public $width;
	public $length;

	/** @var int */
	private $health = 20;
	private $maxHealth = 20;

	protected $ySize = 0;
	protected $stepHeight = 0;
	public $keepMovement = false;

	public $fallDistance = 0;
	public $ticksLived = 0;
	public $lastUpdate;
	public $maxFireTicks;
	public $fireTicks;
	public $airTicks;
	public $namedtag;
	public $canCollide = true;

	protected $isStatic = false;

	public $isCollided = false;
	public $isCollidedHorizontally = false;
	public $isCollidedVertically = false;

	public $noDamageTicks;
	protected $justCreated;
	protected $fireProof;
	private $invulnerable;

	protected $gravity;
	protected $drag;

	/** @var Server */
	protected $server;

	public $closed = false;

	/** @var \pocketmine\event\TimingsHandler */
	protected $timings;

	protected $fireDamage = 1;

	public $temporalVector;


	public function __construct(FullChunk $chunk, Compound $nbt){
		if($chunk === null or $chunk->getProvider() === null){
			throw new ChunkException("Invalid garbage Chunk given to Entity");
		}

		$this->timings = Timings::getEntityTimings($this);

		$this->temporalVector = new Vector3();

		if($this->eyeHeight === null){
			$this->eyeHeight = $this->height / 2 + 0.1;
		}
		$this->id = Entity::$entityCount++;
		$this->justCreated = true;
		$this->namedtag = $nbt;

		$this->chunk = $chunk;
		$this->setLevel($chunk->getProvider()->getLevel());
		$this->server = $chunk->getProvider()->getLevel()->getServer();
		$this->server->addSpawnedEntity($this);

		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);
		$this->setPositionAndRotation(
			$this->temporalVector->setComponents(
				$this->namedtag["Pos"][0],
				$this->namedtag["Pos"][1],
				$this->namedtag["Pos"][2]
			),
			$this->namedtag["Rotation"][0],
			$this->namedtag["Rotation"][1]
		);

		if(isset($this->namedtag->Motion)){
			$this->setMotion($this->temporalVector->setComponents($this->namedtag["Motion"][0], $this->namedtag["Motion"][1], $this->namedtag["Motion"][2]));
		}else{
			$this->setMotion($this->temporalVector->setComponents(0, 0, 0));
		}

		if(!isset($this->namedtag->FallDistance)){
			$this->namedtag->FallDistance = new FloatTag("FallDistance", 0);
		}
		$this->fallDistance = $this->namedtag["FallDistance"];

		if(!isset($this->namedtag->Fire)){
			$this->namedtag->Fire = new ShortTag("Fire", 0);
		}
		$this->fireTicks = $this->namedtag["Fire"];

		if(!isset($this->namedtag->Air)){
			$this->namedtag->Air = new ShortTag("Air", 300);
		}

		$this->setAirTick($this->namedtag["Air"]);

		$this->setGenericFlag(self::DATA_FLAG_AFFECTED_BY_GRAVITY, true);
		$this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, true);

		if(!isset($this->namedtag->OnGround)){
			$this->namedtag->OnGround = new ByteTag("OnGround", 0);
		}
		$this->onGround = $this->namedtag["OnGround"] > 0 ? true : false;

		if(!isset($this->namedtag->Invulnerable)){
			$this->namedtag->Invulnerable = new ByteTag("Invulnerable", 0);
		}
		$this->invulnerable = $this->namedtag["Invulnerable"] > 0 ? true : false;

		$this->chunk->addEntity($this);
		$this->level->addEntity($this);
		$this->initEntity();
		$this->lastUpdate = $this->server->getTick();
		$this->server->getPluginManager()->callEvent(new EntitySpawnEvent($this));

		$this->checkBlockCollisionTicks = (int) $this->server->getAdvancedProperty("main.check-block-collision", 1);

		$this->scheduleUpdate();

	}

	/**
	* @return string
	*/
	public function getNameTag(){
		return $this->getDataProperty(self::DATA_NAMETAG);
	}

	/**
	 * @return bool
	 */
	public function isNameTagVisible(){
		return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_CAN_SHOW_NAMETAG);
	}

	/**
	 * @return bool
	 */
	public function isNameTagAlwaysVisible(){
		return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ALWAYS_SHOW_NAMETAG);
	}

	/**
	 * @param string $name
	 */
	public function setNameTag($name){
		$this->setDataProperty(self::DATA_NAMETAG, self::DATA_TYPE_STRING, $name);
	}

	/**
	 * @param bool $value
	 */
	public function setNameTagVisible($value = true){
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_CAN_SHOW_NAMETAG, $value);
	}

	/**
	 * @param bool $value
	 */
	public function setNameTagAlwaysVisible($value = true){
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ALWAYS_SHOW_NAMETAG, $value);
	}

	/**
	 * @return float
	 */
	public function getScale() : float{
		return $this->getDataProperty(self::DATA_SCALE);
	}

	/**
	 * @param float $value
	 */
	public function setScale(float $value){
		$multiplier = $value / $this->getScale();
		$this->width *= $multiplier;
		$this->height *= $multiplier;
		$this->eyeHeight *= $multiplier;
		$halfWidth = $this->width / 2;
		$this->boundingBox->setBounds(
			$this->x - $halfWidth,
			$this->y,
			$this->z - $halfWidth,
			$this->x + $halfWidth,
			$this->y + $this->height,
			$this->z + $halfWidth
		);
		$this->setDataProperty(self::DATA_SCALE, self::DATA_TYPE_FLOAT, $value);
	}

	public function isSneaking(){
		return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SNEAKING);
	}

	public function setSneaking($value = true){
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SNEAKING, (bool) $value);
	}

	public function isSprinting(){
		return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SPRINTING);
	}

	public function setSprinting($value = true){
		if($value !== $this->isSprinting()){
			$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SPRINTING, (bool) $value);
			//$attr = $this->attributeMap->getAttribute(Attribute::MOVEMENT_SPEED);
			//$attr->setValue($value ? ($attr->getValue() * 1.3) : ($attr->getValue() / 1.3), false, true);
		}
	}

	public function isImmobile() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_IMMOBILE);
	}

	public function setImmobile($value = true){
		$this->setGenericFlag(self::DATA_FLAG_IMMOBILE, $value);
	}

	/**
	 * Returns whether the entity is able to climb blocks such as ladders or vines.
	 * @return bool
	 */
	public function canClimb() : bool{
		return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_CAN_CLIMB);
	}

	/**
	 * Sets whether the entity is able to climb climbable blocks.
	 * @param bool $value
	 */
	public function setCanClimb(bool $value){
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_CAN_CLIMB, $value);
	}

	/**
	 * Returns whether this entity is climbing a block. By default this is only true if the entity is climbing a ladder or vine or similar block.
	 *
	 * @return bool
	 */
	public function canClimbWalls() : bool{
		return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_WALLCLIMBING);
	}

	/**
	 * Sets whether the entity is climbing a block. If true, the entity can climb anything.
	 *
	 * @param bool $value
	 */
	public function setCanClimbWalls(bool $value = true){
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_WALLCLIMBING, $value);
	}

	/**
	 * @return Effect[]
	 */
	public function getEffects(){
		return $this->effects;
	}

	public function removeAllEffects() {
		foreach ($this->effects as $effectId => $effect) {
			unset($this->effects[$effectId]);
			$effect->remove($this);
		}
		$this->recalculateEffectColor();
	}

	public function removeEffect($effectId){
		if (isset($this->effects[$effectId])) {
			$effect = $this->effects[$effectId];
			unset($this->effects[$effectId]);
			$effect->remove($this);

			$this->recalculateEffectColor();
		}
	}

	public function getEffect($effectId){
		return isset($this->effects[$effectId]) ? $this->effects[$effectId] : null;
	}

	public function hasEffect($effectId){
		return isset($this->effects[$effectId]);
	}

	public function addEffect(Effect $effect){
		$effectId = $effect->getId();
		if (isset($this->effects[$effectId])) {
			if (abs($effect->getAmplifier()) < abs($this->effects[$effectId]->getAmplifier()) || (
					abs($effect->getAmplifier()) === abs($this->effects[$effectId]->getAmplifier()) &&
					$effect->getDuration() <= $this->effects[$effectId]->getDuration())){

				return;
			}
			$effect->add($this, true);
		} else {
			$effect->add($this, false);
		}

		$this->effects[$effectId] = $effect;

		$this->recalculateEffectColor();

		if ($effectId === Effect::HEALTH_BOOST) {
			$this->setHealth($this->getHealth() + 4 * ($effect->getAmplifier() + 1));
		}
	}

	protected function recalculateEffectColor(){
		$color = [0, 0, 0]; //RGB
		$count = 0;
		$ambient = true;
		foreach($this->effects as $effect){
			if($effect->isVisible()){
				$c = $effect->getColor();
				$amplifier = $effect->getAmplifier() + 1;
				$color[0] += $c[0] * $amplifier;
				$color[1] += $c[1] * $amplifier;
				$color[2] += $c[2] * $amplifier;
				$count += $amplifier;
				if ($ambient === true && !$effect->isAmbient()) {
					$ambient = false;
				}
			}
		}

		if ($count > 0) {
			$r = ($color[0] / $count) & 0xff;
			$g = ($color[1] / $count) & 0xff;
			$b = ($color[2] / $count) & 0xff;

			$this->setDataProperty(Entity::DATA_POTION_COLOR, Entity::DATA_TYPE_INT, ($r << 16) + ($g << 8) + $b);
			$this->setDataProperty(Entity::DATA_POTION_AMBIENT, Entity::DATA_TYPE_BYTE, $ambient ? 1 : 0);
		}else{
			$this->setDataProperty(Entity::DATA_POTION_COLOR, Entity::DATA_TYPE_INT, 0);
			$this->setDataProperty(Entity::DATA_POTION_AMBIENT, Entity::DATA_TYPE_BYTE, 0);
		}
	}

	/**
	 * @param int|string $type
	 * @param FullChunk  $chunk
	 * @param Compound   $nbt
	 * @param            $args
	 *
	 * @return Entity
	 */
	public static function createEntity($type, FullChunk $chunk, Compound $nbt, ...$args){
		if(isset(self::$knownEntities[$type])){
			$class = self::$knownEntities[$type];
			return new $class($chunk, $nbt, ...$args);
		}

		return null;
	}

	public static function registerEntity($className, $force = false){
		$class = new \ReflectionClass($className);
		if (is_a($className, Entity::class, true) && !$class->isAbstract()) {
			if ($className::NETWORK_ID !== -1) {
				self::$knownEntities[$className::NETWORK_ID] = $className;
			} else if (!$force) {
				return false;
			}

			self::$knownEntities[$class->getShortName()] = $className;
			self::$shortNames[$className] = $class->getShortName();
			return true;
		}

		return false;
	}

	/**
	 * Returns the short save name
	 *
	 * @return string
	 */
	public function getSaveId(){
		return self::$shortNames[static::class];
	}

	public function saveNBT(){
		if (!($this instanceof Player)) {
			$this->namedtag->id = new StringTag("id", $this->getSaveId());
			if ($this->getNameTag() !== "") {
				$this->namedtag->CustomName = new StringTag("CustomName", $this->getNameTag());
				$this->namedtag->CustomNameVisible = new StringTag("CustomNameVisible", $this->isNameTagVisible());
			} else {
				unset($this->namedtag->CustomName);
				unset($this->namedtag->CustomNameVisible);
			}
		}

		$this->namedtag->Pos = new Enum("Pos", [
			new DoubleTag(0, $this->x),
			new DoubleTag(1, $this->y),
			new DoubleTag(2, $this->z)
		]);

		$this->namedtag->Motion = new Enum("Motion", [
			new DoubleTag(0, $this->motionX),
			new DoubleTag(1, $this->motionY),
			new DoubleTag(2, $this->motionZ)
		]);

		$this->namedtag->Rotation = new Enum("Rotation", [
			new FloatTag(0, $this->yaw),
			new FloatTag(1, $this->pitch)
		]);

		$this->namedtag->FallDistance = new FloatTag("FallDistance", $this->fallDistance);
		$this->namedtag->Fire = new ShortTag("Fire", $this->fireTicks);
		$this->namedtag->Air = new ShortTag("Air", $this->getDataProperty(self::DATA_AIR));
		$this->namedtag->OnGround = new ByteTag("OnGround", $this->onGround == true ? 1 : 0);
		$this->namedtag->Invulnerable = new ByteTag("Invulnerable", $this->invulnerable == true ? 1 : 0);

		if(count($this->effects) > 0) {
			$effects = [];
			foreach($this->effects as $effectId => $effect) {
				$effects[$effectId] = new Compound($effectId, [
					new ByteTag("Id", $effectId),
					new ByteTag("Amplifier", Binary::signByte($effect->getAmplifier())),
					new IntTag("Duration", $effect->getDuration()),
					new ByteTag("Ambient", 0),
					new ByteTag("ShowParticles", $effect->isVisible() ? 1 : 0)
				]);
			}

			$this->namedtag->ActiveEffects = new Enum("ActiveEffects", $effects);
		} else {
			unset($this->namedtag->ActiveEffects);
		}
	}

	protected function initEntity() {
		//if(isset($this->namedtag->ActiveEffects)) {
		//	foreach($this->namedtag->ActiveEffects->getValue() as $e) {
		//		$effect = Effect::getEffect($e["Id"]);
		//		if($effect === null) {
		//			continue;
		//		}
		//		$amplifier = Binary::unsignByte($e->Amplifier->getValue()); //0-255 only
		//		$effect->setAmplifier($amplifier)->setDuration($e["Duration"])->setVisible($e["ShowParticles"] > 0);
		//		$this->addEffect($effect, false);
		//	}
		//}

		if(isset($this->namedtag->CustomName)) {
			$this->setNameTag($this->namedtag["CustomName"]);
			if(isset($this->namedtag->CustomNameVisible)) {
				$this->setNameTagVisible($this->namedtag["CustomNameVisible"] > 0);
			}
		}

		$this->scheduleUpdate();
	}

	/**
	 * @return Player[]
	 */
	public function getViewers() {
		return $this->hasSpawned;
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
		}
	}


	public function isSpawned(Player $player) {
		if (isset($this->hasSpawned[$player->getId()])) {
			return true;
		}
		return false;
	}

	public function sendPotionEffects(Player $player) {
		foreach ($this->effects as $effect) {
			$pk = new MobEffectPacket();
			$pk->eid = $player->getId();
			$pk->effectId = $effect->getId();
			$pk->amplifier = $effect->getAmplifier();
			$pk->particles = $effect->isVisible();
			$pk->duration = $effect->getDuration();
			$pk->eventId = MobEffectPacket::EVENT_ADD;

			$player->dataPacket($pk);
		}
	}

	/**
	 * @param Player[]|Player $player
	 * @param array $data Properly formatted entity data, defaults to everything
	 */
	public function sendData($player, array $data = null) {
		if(!is_array($player)) {
			$player = [$player];
		}
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->getId();
		$pk->metadata = $data ?? $this->dataProperties;

		foreach($player as $p) {
			if($p === $this) {
				continue;
			}
			$p->dataPacket(clone $pk);
		}

		if($this instanceof Player) {
			$this->dataPacket($pk);
		}
	}

	/**
	 * @param Player $player
	 */
	public function despawnFrom(Player $player) {
		if (isset($this->hasSpawned[$player->getId()])) {
			$pk = new RemoveEntityPacket();
			$pk->eid = $this->getId();
			$player->dataPacket($pk);
			unset($this->hasSpawned[$player->getId()]);
		}
	}

	/**
	 * @param float             $damage
	 * @param EntityDamageEvent $source
	 *
	 */
	public function attack($damage, EntityDamageEvent $source) {
		$this->server->getPluginManager()->callEvent($source);
		if($source->isCancelled()){
			return;
		}

		$source->applyEnchantmentEffects();

		if($source instanceof EntityDamageByEntityEvent) {
			$damager = $source->getDamager();
			if($damager instanceof Player) {
				$weapon = $damager->getInventory()->getItemInHand();
				if($weapon->getId() !== \pocketmine\item\Item::AIR) {
					$enchantment = $weapon->getEnchantment(Enchantment::TYPE_WEAPON_FIRE_ASPECT);
					if(!is_null($enchantment)) {
						$fireDamage = max(($enchantment->getLevel() * 4) - 1, 1);
						$this->setOnFire(4, $fireDamage);
					}
				}
			}
		}

		$this->setLastDamageCause($source);
		$this->setHealth($this->getHealth() - $source->getFinalDamage());
	}

	/**
	 * @param float                   $amount
	 * @param EntityRegainHealthEvent $source
	 *
	 */
	public function heal($amount, EntityRegainHealthEvent $source) {
		$this->server->getPluginManager()->callEvent($source);
		if ($source->isCancelled()) {
			return;
		}
		$this->setHealth($this->getHealth() + $source->getAmount());
	}

	/**
	 * @return int
	 */
	public function getHealth() {
		return $this->health;
	}

	public function isAlive() {
		return $this->health > 0;
	}

	/**
	 * Sets the health of the Entity. This won't send any update to the players
	 *
	 * @param int $amount
	 */
	public function setHealth($amount) {
		$amount = (int) round($amount);
		if($amount === $this->health) {
			return;
		}

		if($amount <= 0) {
			$this->health = 0;
			if(!$this->dead) {
				$this->kill();
			}
		} elseif($amount <= $this->getMaxHealth() or $amount < $this->health) {
			$this->health = (int) $amount;
		} else {
			$this->health = $this->getMaxHealth();
		}
	}

	/**
	 * @param EntityDamageEvent $type
	 */
	public function setLastDamageCause(EntityDamageEvent $type) {
		$this->lastDamageCause = $type;
	}

	/**
	 * @return EntityDamageEvent|null
	 */
	public function getLastDamageCause() {
		return $this->lastDamageCause;
	}

	/**
	 * @return int
	 */
	public function getMaxHealth() {
		$effect = $this->getEffect(Effect::HEALTH_BOOST);
		return $this->maxHealth + ($effect !== null ? 4 * $effect->getAmplifier() + 1 : 0);
	}

	/**
	 * @param int $amount
	 */
	public function setMaxHealth($amount) {
		$this->maxHealth = (int) $amount;
	}

	public function canCollideWith(Entity $entity) {
		return !$this->justCreated && $entity !== $this;
	}

	protected function checkObstruction($x, $y, $z) {
		$i = Math::floorFloat($x);
		$j = Math::floorFloat($y);
		$k = Math::floorFloat($z);

		if (BlockFactory::$solid[$this->level->getBlockIdAt($i, $j, $k)]) {
			$direction = -1;
			$limit = 9999;
			$diffX = $x - $i;
			$diffY = $y - $j;
			$diffZ = $z - $k;

			if (!BlockFactory::$solid[$this->level->getBlockIdAt($i - 1, $j, $k)]) {
				$limit = $diffX;
				$direction = 0;
			}
			if (1 - $diffX < $limit && !BlockFactory::$solid[$this->level->getBlockIdAt($i + 1, $j, $k)]) {
				$limit = 1 - $diffX;
				$direction = 1;
			}
			if ($diffY < $limit && !BlockFactory::$solid[$this->level->getBlockIdAt($i, $j - 1, $k)]) {
				$limit = $diffY;
				$direction = 2;
			}
			if (1 - $diffY < $limit && !BlockFactory::$solid[$this->level->getBlockIdAt($i, $j + 1, $k)]) {
				$limit = 1 - $diffY;
				$direction = 3;
			}
			if ($diffZ < $limit && !BlockFactory::$solid[$this->level->getBlockIdAt($i, $j, $k - 1)]) {
				$limit = $diffZ;
				$direction = 4;
			}
			if (1 - $diffZ < $limit && !BlockFactory::$solid[$this->level->getBlockIdAt($i, $j, $k + 1)]) {
				$direction = 5;
			}

			$force = lcg_value() * 0.2 + 0.1;

			switch ($direction) {
				case 0:
					$this->motionX = -$force;
					return true;
				case 1:
					$this->motionX = $force;
					return true;
				case 2:
					$this->motionY = -$force;
					return true;
				case 3:
					$this->motionY = $force;
					return true;
				case 4:
					$this->motionZ = -$force;
					return true;
				case 5:
					$this->motionZ= $force;
					return true;
			}
		}

		return false;
	}

	public function entityBaseTick($tickDiff = 1){

		//Timings::$tickEntityTimer->startTiming();
		//TODO: check vehicles

		$this->justCreated = false;
		$isPlayer = $this instanceof Player;

		if($this->dead === true){
			$this->removeAllEffects();
			$this->despawnFromAll();
			if (!$isPlayer) {
				$this->close();
			}
			//Timings::$tickEntityTimer->stopTiming();
			return false;
		}

		if(count($this->changedDataProperties) > 0){
			$this->sendData($this->hasSpawned, $this->changedDataProperties);
			$this->changedDataProperties = [];
		}


		foreach($this->effects as $effect) {
			if($effect->canTick()) {
				$effect->applyEffect($this);
			}
			$newDuration = $effect->getDuration() - $tickDiff;
			if($newDuration <= 0) {
				$this->removeEffect($effect->getId());
			} else {
				$effect->setDuration($newDuration);
			}
		}

		$hasUpdate = false;
		$block = $this->isCollideWithLiquid();
		if($block !== false) {
			$block->onEntityCollide($this);
		}
		$block = $this->isCollideWithTransparent();
		if($block !== false) {
			$block->onEntityCollide($this);
		}

		if($this->y < 0 and $this->isAlive()) {
			$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_VOID, 10);
			$this->attack($ev->getFinalDamage(), $ev);
			$hasUpdate = true;
		}

		if($this->fireTicks > 0) {
			if($this->fireProof) {
				$this->fireTicks -= 4 * $tickDiff;
			} else {
				if(!$this->hasEffect(Effect::FIRE_RESISTANCE) && ($this->fireTicks % 20) === 0 || $tickDiff > 20) {
					$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_FIRE_TICK, $this->fireDamage);
					$this->attack($ev->getFinalDamage(), $ev);
				}
				$this->fireTicks -= $tickDiff;
			}

			if($this->fireTicks <= 0) {
				$this->extinguish();
			} else {
				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ONFIRE, true);
				$hasUpdate = true;
			}
		}

		if($this->noDamageTicks > 0) {
			$this->noDamageTicks -= $tickDiff;
			if ($this->noDamageTicks < 0) {
				$this->noDamageTicks = 0;
			}
		}

		$this->age += $tickDiff;
		$this->ticksLived += $tickDiff;

		//Timings::$tickEntityTimer->stopTiming();

		return $hasUpdate;
	}

	protected function updateMovement(){
		$diffPosition = ($this->x - $this->lastX) ** 2 + ($this->y - $this->lastY) ** 2 + ($this->z - $this->lastZ) ** 2;
		$diffRotation = ($this->yaw - $this->lastYaw) ** 2 + ($this->pitch - $this->lastPitch) ** 2;

		$diffMotion = ($this->motionX - $this->lastMotionX) ** 2 + ($this->motionY - $this->lastMotionY) ** 2 + ($this->motionZ - $this->lastMotionZ) ** 2;

		if($diffPosition > 0.04 or $diffRotation > 2.25 and ($diffMotion > 0.0001 and $this->getMotion()->lengthSquared() <= 0.00001)){ //0.2 ** 2, 1.5 ** 2
			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;

			$this->lastYaw = $this->yaw;
			$this->lastPitch = $this->pitch;

			$this->level->addEntityMovement($this->getViewers(), $this->id, $this->x, $this->y + $this->getEyeHeight(), $this->z, $this->yaw, $this->pitch, $this->yaw, ($this instanceof Player));
		}

		if($diffMotion > 0.0025 or ($diffMotion > 0.0001 and $this->getMotion()->lengthSquared() <= 0.0001)){ //0.05 ** 2
			$this->lastMotionX = $this->motionX;
			$this->lastMotionY = $this->motionY;
			$this->lastMotionZ = $this->motionZ;

			$this->level->addEntityMotion($this->getViewers(), $this->id, $this->motionX, $this->motionY, $this->motionZ);
		}
	}

	/**
	 * @return Vector3
	 */
	public function getDirectionVector() : Vector3 {
		$y = -sin(deg2rad($this->pitch));
		$xz = cos(deg2rad($this->pitch));
		$x = -$xz * sin(deg2rad($this->yaw));
		$z = $xz * cos(deg2rad($this->yaw));

		return $this->temporalVector->setComponents($x, $y, $z)->normalize();
	}

	public function getDirectionPlane() : Vector2 {
		return (new Vector2(-cos(deg2rad($this->yaw) - M_PI_2), -sin(deg2rad($this->yaw) - M_PI_2)))->normalize();
	}

	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		$tickDiff = max(1, $currentTick - $this->lastUpdate);
		$this->lastUpdate = $currentTick;

		//$this->timings->startTiming();

		$hasUpdate = $this->entityBaseTick($tickDiff);

		$this->updateMovement();

		//$this->timings->stopTiming();

		//if($this->isStatic())
		return $hasUpdate;
		//return !($this instanceof Player);
	}

	public final function scheduleUpdate(){
		$this->level->updateEntities[$this->id] = $this;
	}

	public function isOnFire(){
		return $this->fireTicks > 0;
	}

	public function setOnFire($seconds, $damage = 1){
		$ticks = $seconds * 20;
		if($ticks > $this->fireTicks){
			$this->fireTicks = $ticks;
		}
		$this->fireDamage = $damage;
	}

	public function getDirection(){
		$rotation = ($this->yaw - 90) % 360;
		if($rotation < 0){
			$rotation += 360;
		}
		if ($rotation < 45) {
			return self::DIRECTION_NORTH;
		} else if ($rotation < 135) {
			return self::DIRECTION_EAST;
		} else if ($rotation < 225) {
			return self::DIRECTION_SOUTH;
		} else if ($rotation < 315) {
			return self::DIRECTION_WEST;
		}
		return self::DIRECTION_NORTH;
	}

	public function extinguish(){
		$this->fireTicks = 0;
		$this->fireDamage = 1;
		$this->setGenericFlag(self::DATA_FLAG_ONFIRE, false);
	}

	public function canTriggerWalking(){
		return true;
	}

	public function resetFallDistance(){
		$this->fallDistance = 0;
	}

	protected function updateFallState($distanceThisTick, $onGround) {
		if ($onGround === true) {
			if($this->fallDistance > 0) {
				if ($this instanceof Living) {
					//TODO
				}

				if (!$this->isCollideWithWater()) {
					$this->fall($this->fallDistance);
				}
				$this->resetFallDistance();
			}
		} else if ($distanceThisTick < 0) {
			$this->fallDistance -= $distanceThisTick;
		}
	}

	public function getBoundingBox(){
		return $this->boundingBox;
	}

	public function fall($fallDistance){
		$damage = floor($fallDistance - 3);
		if($damage > 0){
			$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_FALL, $damage);
			$this->attack($ev->getFinalDamage(), $ev);
		}
	}

	public function handleLavaMovement(){ //TODO

	}

	public function getEyeHeight(){
		return $this->eyeHeight;
	}

	public function moveFlying(){ //TODO

	}

	public function onCollideWithPlayer(Human $entityPlayer){

	}

	protected function switchLevel(Level $targetLevel){
		if ($this->isValid()) {
			$this->server->getPluginManager()->callEvent($ev = new EntityLevelChangeEvent($this, $this->level, $targetLevel));
			if ($ev->isCancelled()) {
				return false;
			}

			$this->level->removeEntity($this);
			if ($this->chunk !== null) {
				$this->chunk->removeEntity($this);
			}
			$this->despawnFromAll();
			if ($this instanceof Player) {
				$X = $Z = null;
				foreach ($this->usedChunks as $index => $d) {
					Level::getXZ($index, $X, $Z);
					$this->unloadChunk($X, $Z);
				}
			}
		}
		$this->setLevel($targetLevel);
		$this->level->addEntity($this);
		if ($this instanceof Player) {
			$this->usedChunks = [];
			$pk = new SetTimePacket();
			$pk->time = $this->level->getTime();
			$pk->started = $this->level->stopTime == false;
			$this->dataPacket($pk);
		}
		$this->chunk = null;

		return true;
	}

	public function getPosition(){
		return new Position($this->x, $this->y, $this->z, $this->level);
	}

	public function getLocation(){
		return new Location($this->x, $this->y, $this->z, $this->yaw, $this->pitch, $this->level);
	}

	public function isInsideOfWater() {
		$y = $this->y + $this->eyeHeight;
		$block = $this->level->getBlock(new Vector3(floor($this->x), floor($y), floor($this->z)));
		if ($block instanceof Water) {
			$f = ($block->y + 1) - ($block->getFluidHeightPercent() - 0.1111111);
			return $y < $f;
		}
		return false;
	}

	public function isCollideWithWater() {
		$x = Math::floorFloat($this->x);
		$z = Math::floorFloat($this->z);
		// checking block under feet
		$block = $this->level->getBlock(new Vector3($x, Math::floorFloat($y = $this->y), $z));
		if(!($block instanceof Water)) {
			$block = $this->level->getBlock(new Vector3($x, Math::floorFloat($y = ($this->y + $this->eyeHeight)), $z));
		}
		if($block instanceof Water) {
			$f = ($block->y + 1) - ($block->getFluidHeightPercent() - 0.1111111);
			return $y < $f;
		}
		return false;
	}

	public function isCollideWithLiquid() {
		$x = Math::floorFloat($this->x);
		$y = Math::floorFloat($this->y);
		$z = Math::floorFloat($this->z);

		$block = $this->level->getBlock(new Vector3($x, $y, $z));
		$isLiquid = $block instanceof Liquid;

		if (!$isLiquid) {
			$y = Math::floorFloat($this->y + $this->eyeHeight);
			$block = $this->level->getBlock(new Vector3($x, $y, $z));
			$isLiquid = $block instanceof Liquid;

			if (!$isLiquid) {
				$block = $this->level->getBlock(new Vector3(Math::floorFloat($this->x + $this->width), $y, $z));
				$isLiquid = $block instanceof Liquid;

				if (!$isLiquid) {
					$block = $this->level->getBlock(new Vector3(Math::floorFloat($this->x - $this->width), $y, $z));
					$isLiquid = $block instanceof Liquid;

					if (!$isLiquid) {
						$block = $this->level->getBlock(new Vector3($x, $y, Math::floorFloat($this->z + $this->width)));
						$isLiquid = $block instanceof Liquid;

						if (!$isLiquid) {
							$block = $this->level->getBlock(new Vector3($x, $y, Math::floorFloat($this->z - $this->width)));
							$isLiquid = $block instanceof Liquid;
						}
					}
				}
			}
		}
		if ($isLiquid) {
			$f = ($block->y + 1) - ($block->getFluidHeightPercent() - 0.1111111);
			return $y < $f ? $block : false;
		}
		return false;
	}

	public function isCollideWithTransparent() {
		$x = Math::floorFloat($this->x);
		$z = Math::floorFloat($this->z);

		$block = $this->level->getBlock(new Vector3($x, Math::floorFloat($this->y), $z));
		$isTransparent = $block instanceof Ladder || $block instanceof Fire || $block instanceof Vine || $block instanceof Cobweb;

		if(!$isTransparent) {
			$block = $this->level->getBlock(new Vector3($x, Math::floorFloat($this->y + $this->getEyeHeight()), $z));
			$isTransparent = $block instanceof Ladder || $block instanceof Fire || $block instanceof Vine || $block instanceof Cobweb;
		}

		if($isTransparent) {
			return $block;
		}
		return false;
	}

	public function isInsideOfSolid(){
		$block = $this->level->getBlock(new Vector3(Math::floorFloat($this->x), Math::floorFloat($y = ($this->y + $this->getEyeHeight())), Math::floorFloat($this->z)));

		$bb = $block->getBoundingBox();

		if($bb !== null and $block->isSolid() and !$block->isTransparent() and $bb->intersectsWith($this->getBoundingBox())){
			return true;
		}
		return false;
	}

	public function fastMove($dx, $dy, $dz){
		if($dx == 0 and $dz == 0 and $dy == 0){
			return true;
		}

		//Timings::$entityMoveTimer->startTiming();

		$newBB = $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz);

		$list = $this->level->getCollisionCubes($this, $newBB, false);

		if(count($list) === 0){
			$this->boundingBox = $newBB;
		}

		$this->x = ($this->boundingBox->minX + $this->boundingBox->maxX) / 2;
		$this->y = $this->boundingBox->minY - $this->ySize;
		$this->z = ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2;

		if (!($this instanceof Player)) {
			$this->checkChunks();
		}

		if(!$this->onGround or $dy != 0){
			$bb = clone $this->boundingBox;
			$bb->minY -= 0.75;
			$this->onGround = false;

			if(count($this->level->getCollisionBlocks($bb)) > 0){
				$this->onGround = true;
			}
		}
		$this->isCollided = $this->onGround;

		$notInAir = $this->onGround || $this->isCollideWithWater();
		$this->updateFallState($dy, $notInAir);


		//Timings::$entityMoveTimer->stopTiming();

		return true;
	}

	public function move($dx, $dy, $dz){

		if($dx == 0 and $dz == 0 and $dy == 0){
			return true;
		}

		if($this->keepMovement){
			$this->boundingBox->offset($dx, $dy, $dz);
			$this->setPosition(new Vector3(($this->boundingBox->minX + $this->boundingBox->maxX) / 2, $this->boundingBox->minY, ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2));
			$this->onGround = $this instanceof Player ? true : false;
			return true;
		}else{

			//Timings::$entityMoveTimer->startTiming();

			$this->ySize *= 0.4;

			/*
			if($this->isColliding){ //With cobweb?
				$this->isColliding = false;
				$dx *= 0.25;
				$dy *= 0.05;
				$dz *= 0.25;
				$this->motionX = 0;
				$this->motionY = 0;
				$this->motionZ = 0;
			}
			*/

			$movX = $dx;
			$movY = $dy;
			$movZ = $dz;

			$axisalignedbb = clone $this->boundingBox;

			/*$sneakFlag = $this->onGround and $this instanceof Player;

			if($sneakFlag){
				for($mov = 0.05; $dx != 0.0 and count($this->level->getCollisionCubes($this, $this->boundingBox->getOffsetBoundingBox($dx, -1, 0))) === 0; $movX = $dx){
					if($dx < $mov and $dx >= -$mov){
						$dx = 0;
					}elseif($dx > 0){
						$dx -= $mov;
					}else{
						$dx += $mov;
					}
				}

				for(; $dz != 0.0 and count($this->level->getCollisionCubes($this, $this->boundingBox->getOffsetBoundingBox(0, -1, $dz))) === 0; $movZ = $dz){
					if($dz < $mov and $dz >= -$mov){
						$dz = 0;
					}elseif($dz > 0){
						$dz -= $mov;
					}else{
						$dz += $mov;
					}
				}

				//TODO: big messy loop
			}*/

			$list = $this->level->getCollisionCubes($this, $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz));


			foreach($list as $bb){
				$dy = $bb->calculateYOffset($this->boundingBox, $dy);
			}

			$this->boundingBox->offset(0, $dy, 0);

			if($movY != $dy){
				$dx = 0;
				$dy = 0;
				$dz = 0;
			}

			$fallingFlag = ($this->onGround or ($dy != $movY and $movY < 0));

			foreach($list as $bb){
				$dx = $bb->calculateXOffset($this->boundingBox, $dx);
			}

			$this->boundingBox->offset($dx, 0, 0);

			if($movX != $dx){
				$dx = 0;
				$dy = 0;
				$dz = 0;
			}

			foreach($list as $bb){
				$dz = $bb->calculateZOffset($this->boundingBox, $dz);
			}

			$this->boundingBox->offset(0, 0, $dz);

			if($movZ != $dz){
				$dx = 0;
				$dy = 0;
				$dz = 0;
			}


			if($this->stepHeight > 0 and $fallingFlag and $this->ySize < 0.05 and ($movX != $dx or $movZ != $dz)){
				$cx = $dx;
				$cy = $dy;
				$cz = $dz;
				$dx = $movX;
				$dy = $this->stepHeight;
				$dz = $movZ;

				$axisalignedbb1 = clone $this->boundingBox;

				$this->boundingBox->setBB($axisalignedbb);

				$list = $this->level->getCollisionCubes($this, $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz), false);

				foreach($list as $bb){
					$dy = $bb->calculateYOffset($this->boundingBox, $dy);
				}

				$this->boundingBox->offset(0, $dy, 0);

				foreach($list as $bb){
					$dx = $bb->calculateXOffset($this->boundingBox, $dx);
				}

				$this->boundingBox->offset($dx, 0, 0);
				if($movX != $dx){
					$dx = 0;
					$dy = 0;
					$dz = 0;
				}

				foreach($list as $bb){
					$dz = $bb->calculateZOffset($this->boundingBox, $dz);
				}

				$this->boundingBox->offset(0, 0, $dz);
				if($movZ != $dz){
					$dx = 0;
					$dy = 0;
					$dz = 0;
				}

				if($dy == 0){
					$dx = 0;
					$dy = 0;
					$dz = 0;
				}else{
					$dy = -$this->stepHeight;
					foreach($list as $bb){
						$dy = $bb->calculateYOffset($this->boundingBox, $dy);
					}
					$this->boundingBox->offset(0, $dy, 0);
				}

				if(($cx ** 2 + $cz ** 2) >= ($dx ** 2 + $dz ** 2)){
					$dx = $cx;
					$dy = $cy;
					$dz = $cz;
					$this->boundingBox->setBB($axisalignedbb1);
				}else{
					$diff = $this->boundingBox->minY - (int) $this->boundingBox->minY;

					if($diff > 0){
						$this->ySize += $diff + 0.01;
					}
				}

			}

			$pos = new Vector3(
				($this->boundingBox->minX + $this->boundingBox->maxX) / 2,
				$this->boundingBox->minY + $this->ySize,
				($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2
			);

			$result = true;

			if(!$this->setPosition($pos)){
				$this->boundingBox->setBB($axisalignedbb);
				$result = false;
			}else{

				if($this instanceof Player){
					$bb = clone $this->boundingBox;
					$bb->maxY = $bb->minY + 0.5;
					$bb->minY -= 1;
					if(count($this->level->getCollisionBlocks($bb, true)) > 0){
						$this->onGround = true;
					}else{
						$this->onGround = false;
					}
//
//					$bb = clone $this->boundingBox;
//					$bb->minY -= 1;
//					if(count($this->level->getCollisionBlocks($bb)) > 0){
//						$this->onGround = true;
//					}else{
//						$this->onGround = false;
//					}
					$this->isCollided = $this->onGround;
				}else{
					$this->isCollidedVertically = $movY != $dy;
					$this->isCollidedHorizontally = ($movX != $dx or $movZ != $dz);
					$this->isCollided = ($this->isCollidedHorizontally or $this->isCollidedVertically);
					$this->onGround = ($movY != $dy and $movY < 0);
				}
				$notInAir = $this->onGround || $this->isCollideWithWater();
				$this->updateFallState($dy, $notInAir);

				if($movX != $dx){
					$this->motionX = 0;
				}

				if($movY != $dy){
					$this->motionY = 0;
				}

				if($movZ != $dz){
					$this->motionZ = 0;
				}
			}

			//TODO: vehicle collision events (first we need to spawn them!)

			//Timings::$entityMoveTimer->stopTiming();

			return $result;
		}
	}



	public function setPositionAndRotation(Vector3 $pos, $yaw, $pitch){
		if($this->setPosition($pos) === true){
			$this->setRotation($yaw, $pitch);

			return true;
		}

		return false;
	}

	public function setRotation($yaw, $pitch){
		$this->yaw = $yaw;
		$this->pitch = $pitch;
		$this->scheduleUpdate();
	}

	protected function checkChunks(){
		if($this->chunk === null or ($this->chunk->getX() !== ($this->x >> 4) or $this->chunk->getZ() !== ($this->z >> 4))){
			if($this->chunk !== null){
				$this->chunk->removeEntity($this);
			}
			$this->chunk = $this->level->getChunk($this->x >> 4, $this->z >> 4, true);

			if(!$this->justCreated){
				$newChunk = $this->level->getUsingChunk($this->x >> 4, $this->z >> 4);
				foreach($this->hasSpawned as $player){
					if(!isset($newChunk[$player->getId()])){
						$this->despawnFrom($player);
					}else{
						unset($newChunk[$player->getId()]);
					}
				}
				foreach($newChunk as $player){
					if ($player->canSeeEntity($this)) {
						$this->spawnTo($player);
					}
				}
			}

			if($this->chunk === null){
				return;
			}

			$this->chunk->addEntity($this);
		}
	}

	public function setPosition(Vector3 $pos){
		if($this->closed){
			return false;
		}

		if($pos instanceof Position and $pos->level !== null and $pos->level !== $this->level){
			if($this->switchLevel($pos->getLevel()) === false){
				return false;
			}
		}

		$this->x = $pos->x;
		$this->y = $pos->y;
		$this->z = $pos->z;

		$radius = $this->width / 2;
		$this->boundingBox->setBounds($pos->x - $radius, $pos->y, $pos->z - $radius, $pos->x + $radius, $pos->y + $this->height, $pos->z + $radius);

		if (!($this instanceof Player)) {
			$this->checkChunks();
		}

		return true;
	}

	public function getMotion(){
		return new Vector3($this->motionX, $this->motionY, $this->motionZ);
	}

	public function setMotion(Vector3 $motion){
		if(!$this->justCreated){
			$this->server->getPluginManager()->callEvent($ev = new EntityMotionEvent($this, $motion));
			if($ev->isCancelled()){
				return false;
			}
		}

		$this->motionX = $motion->x;
		$this->motionY = $motion->y;
		$this->motionZ = $motion->z;

		if(!$this->justCreated){
			$this->updateMovement();
		}

		return true;
	}

	public function isOnGround(){
		return $this->onGround === true;
	}

	public function kill(){
		if($this->dead){
			return;
		}
		$this->dead = true;
		$this->setHealth(0);
		$this->scheduleUpdate();
	}

	/**
	 * @param Vector3|Position|Location $pos
	 * @param float                     $yaw
	 * @param float                     $pitch
	 *
	 * @return bool
	 */
	public function teleport(Vector3 $pos, $yaw = null, $pitch = null){
		if($pos instanceof Location){
			$yaw = $yaw === null ? $pos->yaw : $yaw;
			$pitch = $pitch === null ? $pos->pitch : $pitch;
		}
		$from = Position::fromObject($this, $this->level);
		$to = Position::fromObject($pos, $pos instanceof Position ? $pos->getLevel() : $this->level);
		$this->server->getPluginManager()->callEvent($ev = new EntityTeleportEvent($this, $from, $to));
		if($ev->isCancelled()){
			return false;
		}
		$this->ySize = 0;
		$pos = $ev->getTo();

		$this->setMotion(new Vector3(0, 0, 0));
		if($this->setPositionAndRotation($pos, $yaw === null ? $this->yaw : $yaw, $pitch === null ? $this->pitch : $pitch, true) !== false){
			$this->resetFallDistance();
			$this->onGround = true;

			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;

			$this->lastYaw = $this->yaw;
			$this->lastPitch = $this->pitch;

			$this->updateMovement();

			return true;
		}

		return false;
	}

	public function getId(){
		return $this->id;
	}

	public function respawnToAll(){
		foreach($this->hasSpawned as $key => $player){
			unset($this->hasSpawned[$key]);
			if ($player->canSeeEntity($this)) {
				$this->spawnTo($player);
			}
		}
	}

	public function spawnToAll(){
		if($this->chunk === null or $this->closed){
			return false;
		}
		foreach($this->level->getUsingChunk($this->chunk->getX(), $this->chunk->getZ()) as $player){
			if($player->loggedIn === true && $player->canSeeEntity($this)){
				$this->spawnTo($player);
			}
		}
	}

	public function despawnFromAll(){
		foreach($this->hasSpawned as $player){
			$this->despawnFrom($player);
		}
	}

	public function close(){
		if(!$this->closed){
			$this->server->removeSpawnedEntity($this);
			$this->server->getPluginManager()->callEvent(new EntityDespawnEvent($this));
			$this->closed = true;
			$this->despawnFromAll();
			if($this->chunk !== null){
				$this->chunk->removeEntity($this);
			}
			if($this->level !== null){
				$this->level->removeEntity($this);
			}
		}
	}

	/**
	 * @param string   $name
	 * @param int   $type
	 * @param mixed $value
	 * @param bool  $send
	 *
	 * @return bool
	 */
	public function setDataProperty(string $name, int $type, $value, bool $send = true) : bool{
		if($this->getDataProperty($name) !== $value){
			$this->dataProperties[$name] = [$type, $value];
			if($send){
				$this->changedDataProperties[$name] = $this->dataProperties[$name]; //This will be sent on the next tick
			}

			return true;
		}

		return false;
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function getDataProperty(string $name){
		return isset($this->dataProperties[$name]) ? $this->dataProperties[$name][1] : null;
	}

	public function removeDataProperty(string $name){
		unset($this->dataProperties[$name]);
	}

	/**
	 * @param string $name
	 *
	 * @return int|null
	 */
	public function getDataPropertyType(string $name){
		return isset($this->dataProperties[$name]) ? $this->dataProperties[$name][0] : null;
	}

	/**
	 * @param string  $propertyName
	 * @param string  $flagName
	 * @param bool $value
	 * @param int  $propertyType
	 */
	public function setDataFlag(string $propertyName, string $flagName, bool $value = true, int $propertyType = self::DATA_TYPE_LONG){
		if($this->getDataFlag($propertyName, $flagName) !== $value) {
			$flags = $this->getDataProperty($propertyName);
			if(!$value and $this->getDataProperty($propertyName)[$flagName]) {
				unset($flags[$flagName]);
			} else {
				$flags[$flagName] = $value;
			}
			$this->setDataProperty($propertyName, $propertyType, $flags);
		}
	}

	/**
	 * @param string $propertyName
	 * @param string $flagName
	 *
	 * @return bool
	 */
	public function getDataFlag(string $propertyName, string $flagName) : bool{
		return $this->getDataProperty($propertyName)[$flagName] ?? false;
	}

	/**
	 * Wrapper around {@link Entity#getDataFlag} for generic data flag reading.
	 *
	 * @param string $flagName
	 * @return bool
	 */
	public function getGenericFlag(string $flagName) : bool {
		return $this->getDataFlag(self::DATA_FLAGS, $flagName);
	}

	/**
	 * Wrapper around {@link Entity#setDataFlag} for generic data flag setting.
	 *
	 * @param string  $flagName
	 * @param bool $value
	 */
	public function setGenericFlag(string $flagName, bool $value = true) {
		$this->setDataFlag(self::DATA_FLAGS, $flagName, $value, self::DATA_TYPE_LONG);
	}

	public function __destruct() {
		$this->close();
	}

	public function setMetadata(string $metadataKey, MetadataValue $newMetadataValue) {
		$this->server->getEntityMetadata()->setMetadata($this, $metadataKey, $newMetadataValue);
	}

	public function getMetadata(string $metadataKey) {
		return $this->server->getEntityMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata(string $metadataKey) : bool {
		return $this->server->getEntityMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata(string $metadataKey, Plugin $owningPlugin) {
		$this->server->getEntityMetadata()->removeMetadata($this, $metadataKey, $owningPlugin);
	}

	public function __toString() {
		return (new \ReflectionClass($this))->getShortName() . "(" . $this->getId() . ")";
	}

	public function setAirTick($val) {
		$this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, $val, true);
	}

}
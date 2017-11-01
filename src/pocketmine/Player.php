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

namespace pocketmine;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\customUI\CustomUI;
use pocketmine\entity\Arrow;
use pocketmine\entity\Egg;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Item as DroppedItem;
use pocketmine\entity\Living;
use pocketmine\entity\Snowball;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPostprocessEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerReceiptsReceivedEvent;
use pocketmine\event\player\PlayerRespawnAfterEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\BigShapedRecipe;
use pocketmine\inventory\BigShapelessRecipe;
use pocketmine\inventory\EnchantInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\armor\Armor;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\food\Edible;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\tool\Tool;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\multiversion\inventory\InventoryAdapter;
use pocketmine\network\multiversion\inventory\PlayerInventoryAdapter;
use pocketmine\network\multiversion\inventory\PlayerInventoryAdapter120;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\AvailableCommandsPacket;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\network\protocol\ChunkRadiusUpdatePacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\DisconnectPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\protocol\LevelSoundEventPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\network\protocol\PlayStatusPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\protocol\ResourcePacksInfoPacket;
use pocketmine\network\protocol\ResourcePackStackPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\protocol\SetDifficultyPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\SetPlayerGameTypePacket;
use pocketmine\network\protocol\SetSpawnPositionPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\protocol\SetTitlePacket;
use pocketmine\network\protocol\StartGamePacket;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\network\protocol\TextPacket;
use pocketmine\network\protocol\TransferPacket;
use pocketmine\network\protocol\types\ContainerIds;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\v120\PlayerSkinPacket;
use pocketmine\network\protocol\v120\Protocol120;
use pocketmine\network\protocol\v120\ServerSettingsResponsetPacket;
use pocketmine\network\protocol\v120\ShowModalFormPacket;
use pocketmine\network\protocol\v120\SubClientLoginPacket;
use pocketmine\network\SourceInterface;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\MessageQueue;
use pocketmine\player\PopupQueue;
use pocketmine\player\TipQueue;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\CallbackTask;
use pocketmine\tile\Sign;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

/**
 * Main class that handles networking, recovery, and packet sending to the server part
 */
class Player extends Human implements CommandSender, InventoryHolder, IPlayer{

	const OS_ANDROID = 1;
	const OS_IOS = 2;
	const OS_OSX = 3;
	const OS_FIREOS = 4;
	const OS_GEARVR = 5;
	const OS_HOLOLENS = 6;
	const OS_WIN10 = 7;
	const OS_WIN32 = 8;
	const OS_DEDICATED = 9;
	const OS_ORBIS = 10;
	const OS_NX = 11;

	const INVENTORY_CLASSIC = 0;
	const INVENTORY_POCKET = 1;

	const SURVIVAL = 0;
	const CREATIVE = 1;
	const ADVENTURE = 2;
	const SPECTATOR = 3;
	const VIEW = Player::SPECTATOR;

	const CRAFTING_DEFAULT = 0;
	const CRAFTING_WORKBENCH = 1;
	const CRAFTING_ANVIL = 2;
	const CRAFTING_ENCHANT = 3;

	const SURVIVAL_SLOTS = 36;
	const CREATIVE_SLOTS = 112;

	const DEFAULT_SPEED = 0.10;
	const MAXIMUM_SPEED = 0.5;

	/**
	 * Checks the length of a supplied skin bitmap and returns whether the length is valid.
	 *
	 * @param string $skin
	 *
	 * @return bool
	 */
	public static function isValidSkin(string $skin) : bool {
		return strlen($skin) === 64 * 64 * 4 or strlen($skin) === 64 * 32 * 4;
	}

	/** @var array */
	private static $defaultCommandData = null;

	/** @var SourceInterface */
	protected $interface;

	public $spawned = false;
	public $loggedIn = false;
	public $dead = false;
	public $gamemode;
	public $lastBreak;

	protected $windowCnt = 2;
	/** @var \SplObjectStorage<Inventory> */
	protected $windows;
	/** @var Inventory[] */
	protected $windowIndex = [];
	/** @var bool[] */
	protected $permanentWindows = [];

	protected $messageCounter = 2;

	protected $sendIndex = 0;

	private $clientSecret;

	/** @var Vector3 */
	public $speed = null;

	public $blocked = false;
	public $lastCorrect;

	public $craftingType = self::CRAFTING_DEFAULT;

	protected $isCrafting = false;

	/**
	 * @deprecated
	 * @var array
	 */
	public $loginData = [];

	public $creationTime = 0;

	protected $randomClientId;

	protected $lastMovement = 0;
	/** @var Vector3 */
	protected $forceMovement = null;
	protected $connected = true;
	protected $ip;
	protected $removeFormat = true;
	protected $port;
	protected $username = '';
	protected $iusername = '';
	protected $displayName = '';
	protected $startAction = -1;
	public $protocol = ProtocolInfo::BASE_PROTOCOL;
	/** @var Vector3 */
	protected $sleeping = null;
	protected $clientID = null;

	protected $stepHeight = 0.6;

	public $usedChunks = [];
	protected $chunkLoadCount = 0;
	protected $loadQueue = [];
	protected $nextChunkOrderRun = 5;

	/** @var Player[] */
	protected $hiddenPlayers = [];
	protected $hiddenEntity = [];

	/** @var Vector3 */
	public $newPosition;

	protected $spawnThreshold;
	/** @var null|Position */
	private $spawnPosition = null;

	protected $inAirTicks = 0;
	protected $startAirTicks = 5;

	protected $autoJump = true;
	protected $lastJumpTime = 0;

	protected $allowInstaBreak = false;

	private $checkMovement;
	protected $allowFlight = false;

	/**
	 * @var \pocketmine\scheduler\TaskHandler[]
	 */
	protected $tasks = [];

	/** @var PermissibleBase */
	private $perm = null;

	/** @var string*/
	protected $lastMessageReceivedFrom = "";

	protected $identifier;

	protected static $availableCommands = [];

	protected $movementSpeed = self::DEFAULT_SPEED;

	protected $lastTeleportTime = 0;

	protected $isTeleportedForMoveEvent = false;

	private $isFirstConnect = true;

	const MAX_EXPERIENCE = 2147483648;
	const MAX_EXPERIENCE_LEVEL = 21863;
	private $exp = 0;
	private $expLevel = 0;

	/** @IMPORTANT don't change the scope */
	private $inventoryType = self::INVENTORY_CLASSIC;
	private $languageCode = false;

	/** @IMPORTANT don't change the scope */
	private $deviceType = self::OS_DEDICATED;

	/** @var MessageQueue */
	private $messageQueue = null;

	/** @var PopupQueue */
	private $popupQueue = null;

	/** @var TipQueue */
	private $tipQueue = null;

	private $noteSoundQueue = [];

	private $xuid = '';

	private $ping = 0;

	protected $xblName = '';

	protected $viewDistance = 4;
	protected $chunksPerTick = 10;

	private $actionsNum = [];

	private $isMayMove = false;

	protected $serverAddress = '';

	protected $clientVersion = '';

	protected $originalProtocol;

	protected $lastModalId = 1;

	/** @var CustomUI[] */
	protected $activeModalWindows = [];

	protected $isTeleporting = false;

	/** @var Player[] */
	protected $subClients = [];

	/** @var integer */
	protected $subClientId = 0;

	/** @var Player */
	protected $parent = null;

	/** @var InventoryAdapter */
	private $inventoryAdapter;

	public function getLeaveMessage(){
		return "";
	}

	/**
	 * This might disappear in the future.
	 * Please use getUniqueId() instead (IP + clientId + name combo, in the future it'll change to real UUID for online auth)
	 *
	 * @deprecated
	 *
	 */
	public function getClientId(){
		return $this->randomClientId;
	}

	public function getClientSecret(){
		return $this->clientSecret;
	}

	public function isBanned(){
		return $this->server->getNameBans()->isBanned(strtolower($this->getName()));
	}

	public function setBanned($value){
		if($value === true){
			$this->server->getNameBans()->addBan($this->getName(), null, null, null);
			$this->kick("You have been banned");
		}else{
			$this->server->getNameBans()->remove($this->getName());
		}
	}

	public function isWhitelisted(){
		return $this->server->isWhitelisted(strtolower($this->getName()));
	}

	public function setWhitelisted($value){
		if($value === true){
			$this->server->addWhitelist(strtolower($this->getName()));
		}else{
			$this->server->removeWhitelist(strtolower($this->getName()));
		}
	}

	public function getPlayer(){
		return $this;
	}

	public function getFirstPlayed(){
		return $this->namedtag instanceof Compound ? $this->namedtag["firstPlayed"] : null;
	}

	public function getLastPlayed(){
		return $this->namedtag instanceof Compound ? $this->namedtag["lastPlayed"] : null;
	}

	public function hasPlayedBefore(){
		return $this->namedtag instanceof Compound;
	}

	public function setAllowFlight($value){
		$this->allowFlight = (bool) $value;
		$this->sendSettings();
	}

	public function getAllowFlight(){
		return $this->allowFlight;
	}

	public function setAutoJump($value){
		$this->autoJump = $value;
		$this->sendSettings();
	}

	public function hasAutoJump(){
		return $this->autoJump;
	}

	public function allowInstaBreak() : bool{
		return $this->allowInstaBreak;
	}

	public function setAllowInstaBreak(bool $value = false){
		$this->allowInstaBreak = $value;
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		if($this->spawned === true and $player->spawned === true and $this->dead !== true and $player->dead !== true and $player->getLevel() === $this->level and $player->canSee($this) and !$this->isSpectator()){
			parent::spawnTo($player);
		}
	}

	/**
	 * @return Server
	 */
	public function getServer(){
		return $this->server;
	}

	/**
	 * @return bool
	 */
	public function getRemoveFormat(){
		return $this->removeFormat;
	}

	/**
	 * @param bool $remove
	 */
	public function setRemoveFormat($remove = true){
		$this->removeFormat = (bool) $remove;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function canSee(Player $player){
		return !isset($this->hiddenPlayers[$player->getName()]);
	}

	/**
	 * @param Player $player
	 */
	public function hidePlayer(Player $player){
		if($player === $this){
			return;
		}
		$this->hiddenPlayers[$player->getName()] = $player;
		$player->despawnFrom($this);
	}

	/**
	 * @param Player $player
	 */
	public function showPlayer(Player $player){
		if($player === $this){
			return;
		}
		unset($this->hiddenPlayers[$player->getName()]);
		if($player->isOnline()){
			$player->spawnTo($this);
		}
	}

	public function canCollideWith(Entity $entity){
		return false;
	}

	public function resetFallDistance(){
		parent::resetFallDistance();
		if($this->inAirTicks !== 0){
			$this->startAirTicks = 5;
		}
		$this->inAirTicks = 0;
	}

	/**
	 * @return bool
	 */
	public function isOnline(){
		return $this->connected === true and $this->loggedIn === true;
	}

	/**
	 * @return bool
	 */
	public function isOp(){
		return $this->server->isOp($this->getName());
	}

	/**
	 * @param bool $value
	 */
	public function setOp($value){
		if($value === $this->isOp()){
			return;
		}

		if($value === true){
			$this->server->addOp($this->getName());
		}else{
			$this->server->removeOp($this->getName());
		}

		$this->recalculatePermissions();
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @return bool
	 */
	public function isPermissionSet($name){
		return $this->perm->isPermissionSet($name);
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @return bool
	 */
	public function hasPermission($name){
		return $this->perm->hasPermission($name);
	}

	/**
	 * @param Plugin $plugin
	 * @param string $name
	 * @param bool   $value
	 *
	 * @return permission\PermissionAttachment
	 */
	public function addAttachment(Plugin $plugin, $name = null, $value = null){
		return $this->perm->addAttachment($plugin, $name, $value);
	}

	/**
	 * @param PermissionAttachment $attachment
	 */
	public function removeAttachment(PermissionAttachment $attachment){
		$this->perm->removeAttachment($attachment);
	}

	public function recalculatePermissions(){
		$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

		if($this->perm === null){
			return;
		}

		$this->perm->recalculatePermissions();

		if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		}
		if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
		}
	}

	/**
	 * @return permission\PermissionAttachmentInfo[]
	 */
	public function getEffectivePermissions(){
		return $this->perm->getEffectivePermissions();
	}

	public function getInventoryAdapter() : InventoryAdapter {
		return $this->inventoryAdapter;
	}

	/**
	 * @param SourceInterface $interface
	 * @param null            $clientID
	 * @param string          $ip
	 * @param integer         $port
	 */
	public function __construct(SourceInterface $interface, $clientID, $ip, $port){
		$this->interface = $interface;
		$this->windows = new \SplObjectStorage();
		$this->perm = new PermissibleBase($this);
		$this->namedtag = new Compound();
		$this->server = Server::getInstance();
		$this->lastBreak = PHP_INT_MAX;
		$this->ip = $ip;
		$this->port = $port;
		$this->clientID = $clientID;
		$this->chunksPerTick = (int) $this->server->getProperty("chunk-sending.per-tick", 4);
		$this->spawnThreshold = $this->server->getSpawnThreshold();
		$this->spawnPosition = null;
		$this->gamemode = $this->server->getGamemode();
		$this->setLevel($this->server->getDefaultLevel(), true);
		$this->newPosition = new Vector3(0, 0, 0);
		$this->checkMovement = (bool) $this->server->getAdvancedProperty("main.check-movement", true);
		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

		$this->uuid = null;
		$this->rawUUID = null;

		$this->creationTime = microtime(true);

		$this->inventory = new PlayerInventory($this); // hack for not null getInventory
		$this->inventoryAdapter = new PlayerInventoryAdapter($this);

		$this->messageQueue = new MessageQueue($this);
		$this->popupQueue = new PopupQueue($this);
		$this->tipQueue = new TipQueue($this);
	}

	public function sendCommandData() {
		if(self::$defaultCommandData === null) {
			$data = [];
			$default = Command::generateDefaultData();
			foreach($this->server->getCommandMap()->getCommands() as $command) {
				$version = $default;
				$version["aliases"] = $command->getAliases();
				$version["description"] = $command->getDescription();
				$data[strtolower($command->getLabel())]["versions"][] = $version;
			}
			self::$defaultCommandData = $data = json_encode($data);
		} else {
			$data = self::$defaultCommandData;
		}
		if($data !== "") {
			//TODO: structure checking
			$pk = new AvailableCommandsPacket();
			$pk->commands = $data;
			$this->dataPacket($pk);
		}
	}

	public function getViewDistance() : int {
		return $this->viewDistance;
	}

	public function setViewDistance(int $distance) {
		$this->viewDistance = $this->server->getAllowedViewDistance($distance);

		$this->spawnThreshold = (int) (min($this->viewDistance, $this->server->getSpawnThreshold()) ** 2 * M_PI);

		$pk = new ChunkRadiusUpdatePacket();
		$pk->radius = $this->viewDistance;
		$this->dataPacket($pk);

		$this->server->getLogger()->debug("Setting view distance for " . $this->getName() . " to " . $this->viewDistance . " (requested " . $distance . ")");
	}

	/**
	 * @return bool
	 */
	public function isConnected(){
		return $this->connected === true;
	}

	/**
	 * Gets the "friendly" name to display of this player to use in the chat.
	 *
	 * @return string
	 */
	public function getDisplayName(){
		return $this->displayName;
	}

	/**
	 * @param string $name
	 */
	public function setDisplayName($name){
		$this->displayName = $name;
	}

	/**
	 * @return string
	 */
	public function getNameTag(){
		return $this->nameTag;
	}

	/**
	 * Gets the player IP address
	 *
	 * @return string
	 */
	public function getAddress(){
		return $this->ip;
	}

	/**
	 * @return int
	 */
	public function getPort(){
		return $this->port;
	}

	/**
	 * @return Position
	 */
	public function getNextPosition() : Position{
		return $this->newPosition !== null ? Position::fromObject($this->newPosition, $this->level) : $this->getPosition();
	}

	/**
	 * @return bool
	 */
	public function isSleeping(){
		return $this->sleeping !== null;
	}

	/**
	 * Returns whether the player is currently using an item (right-click and hold).
	 * @return bool
	 */
	public function isUsingItem() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_ACTION) and $this->startAction > -1;
	}

	public function setUsingItem(bool $value){
		$this->startAction = $value ? $this->server->getTick() : -1;
		$this->setGenericFlag(self::DATA_FLAG_ACTION, $value);
	}

	/**
	 * Returns how long the player has been using their currently-held item for. Used for determining arrow shoot force
	 * for bows.
	 *
	 * @return int
	 */
	public function getItemUseDuration() : int{
		return $this->startAction === -1 ? -1 : ($this->server->getTick() - $this->startAction);
	}

	public function unloadChunk($x, $z){
		$index = Level::chunkHash($x, $z);
		if(isset($this->usedChunks[$index])){
			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this){
					$entity->despawnFrom($this);
				}
			}

			unset($this->usedChunks[$index]);
		}
		$this->level->freeChunk($x, $z, $this);
		unset($this->loadQueue[$index]);
	}

	/**
	 * @return Position
	 */
	public function getSpawn(){
		if($this->spawnPosition instanceof Position and $this->spawnPosition->getLevel() instanceof Level){
			return $this->spawnPosition;
		}else{
			$level = $this->server->getDefaultLevel();

			return $level->getSafeSpawn();
		}
	}

	public function sendChunk($x, $z, $data){
		if($this->connected === false){
			return;
		}

		$this->usedChunks[Level::chunkHash($x, $z)] = true;
		$this->chunkLoadCount++;

		$pk = new BatchPacket();
		$pk->payload = $data;
//		$pk->encode();
//		$pk->isEncoded = true;
		$this->dataPacket($pk);

		$this->getServer()->getDefaultLevel()->useChunk($x, $z, $this);

		if($this->spawned){
			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this and !$entity->closed and $entity->isAlive()){
					$entity->spawnTo($this);
				}
			}
			foreach($this->level->getChunkTiles($x, $z) as $tile) {
				if(!$tile->closed and ($tile instanceof Spawnable)) {
					$tile->spawnTo($this);
				}
			}
		}

		if($this->chunkLoadCount >= $this->spawnThreshold and $this->spawned === false) {
			$this->doFirstSpawn();
		}
	}

	protected function sendNextChunk(){
		if($this->connected === false){
			return;
		}

		$count = 0;
		foreach($this->loadQueue as $index => $distance){
			if($count >= $this->chunksPerTick) {
				break;
			}

			$X = null;
			$Z = null;
			Level::getXZ($index, $X, $Z);

			++$count;

			unset($this->loadQueue[$index]);
			$this->usedChunks[$index] = false;

			$this->level->useChunk($X, $Z, $this);
			$this->level->requestChunk($X, $Z, $this);

			if($this->server->getAutoGenerate()){
				if(!$this->level->populateChunk($X, $Z, true)){
					if($this->spawned){
						continue;
					}else{
						break;
					}
				}
			}
		}
	}

	protected function doFirstSpawn() {
		$this->server->getPluginManager()->callEvent($ev = new PlayerLoginEvent($this, "Plugin reason"));
		if($ev->isCancelled()) {
			$this->close(TextFormat::YELLOW . $this->username . " has left the game", $ev->getKickMessage());
			return;
		}

		$this->spawned = true;

		$pk = new PlayStatusPacket();
		$pk->status = PlayStatusPacket::PLAYER_SPAWN;
		$this->dataPacket($pk);

		$this->sendSettings();
		$this->sendPotionEffects($this);
		$this->sendData($this);

		$this->addDefaultWindows();

		$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getName(), $this->skinName, $this->skin, $this->skinGeometryName, $this->skinGeometryData, $this->capeData, $this->getXUID(), [$this]);

		$this->server->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($this, $this->level->getSafeSpawn($this)));

		$this->teleport($ev->getRespawnPosition()->add(0, 0.5, 0));

		$this->noDamageTicks = 60;

		$chunkX = $chunkZ = null;
		foreach($this->usedChunks as $index => $c) {
			Level::getXZ($index, $chunkX, $chunkZ);
			foreach($this->level->getChunkEntities($chunkX, $chunkZ) as $entity) {
				if($entity !== $this and !$entity->closed and $entity->isAlive()) {
					$entity->spawnTo($this);
				}
			}
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerJoinEvent($this, ""));
	}

	protected function orderChunks() {
		if(!$this->isConnected() or $this->viewDistance === -1) {
			return false;
		}

		$this->nextChunkOrderRun = 200;

		$radius = $this->server->getAllowedViewDistance($this->viewDistance);
		$radiusSquared = $radius ** 2;

		$newOrder = [];
		$unloadChunks = $this->usedChunks;

		$centerX = $this->x >> 4;
		$centerZ = $this->z >> 4;

		for($x = 0; $x < $radius; ++$x) {
			for($z = 0; $z <= $x; ++$z) {
				if(($x ** 2 + $z ** 2) > $radiusSquared) {
					break; //skip to next band
				}

				//If the chunk is in the radius, others at the same offsets in different quadrants are also guaranteed to be.

				/* Top right quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $x, $centerZ + $z)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				/* Top left quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $x - 1, $centerZ + $z)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				/* Bottom right quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $x, $centerZ - $z - 1)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);


				/* Bottom left quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $x - 1, $centerZ - $z - 1)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				if($x !== $z){
					/* Top right quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $z, $centerZ + $x)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);

					/* Top left quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $z - 1, $centerZ + $x)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);

					/* Bottom right quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $z, $centerZ - $x - 1)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);

					/* Bottom left quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $z - 1, $centerZ - $x - 1)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);
				}

			}
		}

		foreach($unloadChunks as $index => $bool){
			Level::getXZ($index, $X, $Z);
			$this->unloadChunk($X, $Z);
		}

		$this->loadQueue = $newOrder;

		return true;
	}

	/**
	 * Sends an ordered DataPacket to the send buffer
	 *
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 *
	 * @return int|bool
	 */
	public function dataPacket(DataPacket $packet, $needACK = false){
		if($this->connected === false){
			return false;
		}

		if($this->subClientId > 0 && $this->parent != null) {
			$packet->senderSubClientID = $this->subClientId;
			return $this->parent->dataPacket($packet, $needACK);
		}

		if($this->getPlayerProtocol() >= ProtocolInfo::PROTOCOL_120) {
			$disallowedPackets = Protocol120::getDisallowedPackets();
			if (in_array(get_class($packet), $disallowedPackets)) {
				$packet->senderSubClientID = 0;
				return true;
			}
		}

		$this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
		if($ev->isCancelled()){
			return false;
		}

		$this->interface->putPacket($this, $packet, $needACK, false);
		$packet->senderSubClientID = 0;
		return true;
	}

	/**
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 *
	 * @return bool|int
	 */
	public function directDataPacket(DataPacket $packet, $needACK = false){
		if($this->connected === false){
			return false;
		}

		if($this->getPlayerProtocol() >= ProtocolInfo::PROTOCOL_120) {
			$disallowedPackets = Protocol120::getDisallowedPackets();
			if (in_array(get_class($packet), $disallowedPackets)) {
				return false;
			}
		}

		$this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
		if($ev->isCancelled()){
			return false;
		}

		$this->interface->putPacket($this, $packet, $needACK, true);

		return true;
	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return boolean
	 */
	public function sleepOn(Vector3 $pos){
		foreach($this->level->getNearbyEntities($this->boundingBox->grow(2, 1, 2), $this) as $p){
			if($p instanceof Player){
				if($p->sleeping !== null and $pos->distance($p->sleeping) <= 0.1){
					return false;
				}
			}
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerBedEnterEvent($this, $this->level->getBlock($pos)));
		if($ev->isCancelled()){
			return false;
		}

		$this->sleeping = clone $pos;
		$this->teleport(new Position($pos->x + 0.5, $pos->y - 0.5, $pos->z + 0.5, $this->level));

		$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [$pos->x, $pos->y, $pos->z]);
		$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, true);

		$this->setSpawn($pos);
		$this->tasks[] = $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, "checkSleep"]), 60);

		return true;
	}

	/**
	 * Sets the spawnpoint of the player (and the compass direction) to a Vector3, or set it on another world with a Position object
	 *
	 * @param Vector3|Position $pos
	 */
	public function setSpawn(Vector3 $pos){
		if(!($pos instanceof Position)){
			$level = $this->level;
		}else{
			$level = $pos->getLevel();
		}
		$this->spawnPosition = new Position($pos->x, $pos->y, $pos->z, $level);
		$pk = new SetSpawnPositionPacket();
		$pk->x = (int) $this->spawnPosition->x;
		$pk->y = (int) $this->spawnPosition->y;
		$pk->z = (int) $this->spawnPosition->z;
		$this->dataPacket($pk);
	}

	public function stopSleep(){
		if($this->sleeping instanceof Vector3){
			$this->server->getPluginManager()->callEvent($ev = new PlayerBedLeaveEvent($this, $this->level->getBlock($this->sleeping)));

			$this->sleeping = null;
			$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, false);
			$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [0, 0, 0]);

			$this->level->sleepTicks = 0;

			$pk = new AnimatePacket();
			$pk->eid = $this->id;
			$pk->action = 3; //Wake up
			$this->dataPacket($pk);
		}

	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 */
	public function checkSleep(){
		if($this->sleeping instanceof Vector3){
			//TODO: Move to Level

			$time = $this->level->getTime() % Level::TIME_FULL;

			if($time >= Level::TIME_NIGHT and $time < Level::TIME_SUNRISE){
				foreach($this->level->getPlayers() as $p){
					if($p->sleeping === null){
						return;
					}
				}

				$this->level->setTime($this->level->getTime() + Level::TIME_FULL - $time);

				foreach($this->level->getPlayers() as $p){
					$p->stopSleep();
				}
			}
		}
	}

	/**
	 * @return int
	 */
	public function getGamemode(){
		return $this->gamemode;
	}

	/**
	 * Sets the gamemode, and if needed, kicks the Player.
	 *
	 * @param int $gm
	 *
	 * @return bool
	 */
	public function setGamemode($gm){
		if($gm < 0 or $gm > 3 or $this->gamemode === $gm){
			return false;
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerGameModeChangeEvent($this, (int) $gm));
		if($ev->isCancelled()){
			return false;
		}


		$this->gamemode = $gm;

		$this->allowFlight = $this->isCreative();

		if($this->isSpectator()){
			$this->despawnFromAll();
		}

		$this->namedtag->playerGameType = new IntTag("playerGameType", $this->gamemode);
		$pk = new SetPlayerGameTypePacket();
		$pk->gamemode = $this->gamemode & 0x01;
		$this->dataPacket($pk);
		$this->sendSettings();

		$this->inventory->sendContents($this);
		$this->inventory->sendContents($this->getViewers());
		$this->inventory->sendHeldItem($this->hasSpawned);

		return true;
	}

	/**
	 * Sends all the option flags
	 */
	public function sendSettings(){
		/*
		 bit mask | flag name
		0x00000001 world_inmutable
		0x00000002 no_pvp
		0x00000004 no_pvm
		0x00000008 no_mvp
		0x00000010 static_time
		0x00000020 nametags_visible
		0x00000040 auto_jump
		0x00000080 allow_fly
		0x00000100 noclip
		0x00000200 ?
		0x00000400 ?
		0x00000800 ?
		0x00001000 ?
		0x00002000 ?
		0x00004000 ?
		0x00008000 ?
		0x00010000 ?
		0x00020000 ?
		0x00040000 ?
		0x00080000 ?
		0x00100000 ?
		0x00200000 ?
		0x00400000 ?
		0x00800000 ?
		0x01000000 ?
		0x02000000 ?
		0x04000000 ?
		0x08000000 ?
		0x10000000 ?
		0x20000000 ?
		0x40000000 ?
		0x80000000 ?
		*/
		$flags = 0;
		if($this->isAdventure()){
			$flags |= 0x01; //Do not allow placing/breaking blocks, adventure mode
		}

		/*if($nametags !== false){
			$flags |= 0x20; //Show Nametags
		}*/

		if($this->autoJump){
			$flags |= 0x20;
		}

		if($this->allowFlight){
			$flags |= 0x40;
		}

		if($this->isSpectator()){
			$flags |= 0x80;
		}

		$flags |= 0x02;
		$flags |= 0x04;

		$pk = new AdventureSettingsPacket();
		$pk->flags = $flags;
		$pk->userId = $this->getId();
		$this->dataPacket($pk);
	}

	/**
	 * NOTE: Because Survival and Adventure Mode share some similar behaviour, this method will also return true if the player is
	 * in Adventure Mode. Supply the $literal parameter as true to force a literal Survival Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 *
	 * @return bool
	 */
	public function isSurvival(bool $literal = false) : bool{
		if($literal){
			return $this->gamemode === Player::SURVIVAL;
		}else{
			return ($this->gamemode & 0x01) === 0;
		}
	}

	/**
	 * NOTE: Because Creative and Spectator Mode share some similar behaviour, this method will also return true if the player is
	 * in Spectator Mode. Supply the $literal parameter as true to force a literal Creative Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 *
	 * @return bool
	 */
	public function isCreative(bool $literal = false) : bool{
		if($literal){
			return $this->gamemode === Player::CREATIVE;
		}else{
			return ($this->gamemode & 0x01) === 1;
		}
	}

	/**
	 * NOTE: Because Adventure and Spectator Mode share some similar behaviour, this method will also return true if the player is
	 * in Spectator Mode. Supply the $literal parameter as true to force a literal Adventure Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 *
	 * @return bool
	 */
	public function isAdventure(bool $literal = false) : bool{
		if($literal){
			return $this->gamemode === Player::ADVENTURE;
		}else{
			return ($this->gamemode & 0x02) > 0;
		}
	}

	/**
	 * @return bool
	 */
	public function isSpectator() : bool{
		return $this->gamemode === Player::SPECTATOR;
	}

	public function getDrops(){
		if(!$this->isCreative()){
			return parent::getDrops();
		}

		return [];
	}

	/**
	 * @deprecated
	 */
	public function addEntityMotion($entityId, $x, $y, $z){

	}

	/**
	 * @deprecated
	 */
	public function addEntityMovement($entityId, $x, $y, $z, $yaw, $pitch, $headYaw = null){

	}

	protected function checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz){
		/*
		if(!$this->onGround or $movY != 0){
			$bb = clone $this->boundingBox;
			$bb->maxY = $bb->minY + 0.5;
			$bb->minY -= 1;
			if(count($this->level->getCollisionBlocks($bb, true)) > 0){
				$this->onGround = true;
			}else{
				$this->onGround = false;
			}
		}
		$this->isCollided = $this->onGround;
		*/
	}

	protected function checkBlockCollision(){

	}

	protected function checkNearEntities($tickDiff){
		foreach($this->level->getNearbyEntities($this->boundingBox->grow(1, 0.5, 1), $this) as $entity){
			$entity->scheduleUpdate();

			if(!$entity->isAlive()){
				continue;
			}

			if($entity instanceof Arrow and $entity->hadCollision){
				$item = ItemFactory::get(Item::ARROW, 0, 1);
				if($this->isSurvival() and !$this->inventory->canAddItem($item)){
					continue;
				}

				$this->server->getPluginManager()->callEvent($ev = new InventoryPickupArrowEvent($this->inventory, $entity));
				if($ev->isCancelled()){
					continue;
				}

				$pk = new TakeItemEntityPacket();
				$pk->eid = $this->getId();
				$pk->target = $entity->getId();
				Server::broadcastPacket($entity->getViewers(), $pk);

				$this->inventory->addItem(clone $item);
				$entity->kill();
			}elseif($entity instanceof DroppedItem){
				if($entity->getPickupDelay() <= 0){
					$item = $entity->getItem();

					if($item instanceof Item){
						if($this->isSurvival() and !$this->inventory->canAddItem($item)){
							continue;
						}

						$this->server->getPluginManager()->callEvent($ev = new InventoryPickupItemEvent($this->inventory, $entity));
						if($ev->isCancelled()){
							continue;
						}

						$pk = new TakeItemEntityPacket();
						$pk->eid = $this->getId();
						$pk->target = $entity->getId();
						Server::broadcastPacket($entity->getViewers(), $pk);

						$this->inventory->addItem(clone $item);
						$entity->kill();
					}
				}
			}
		}
	}

	protected function processMovement($tickDiff){
		if(!$this->isAlive() or !$this->spawned or $this->newPosition === null){
			$this->setMoving(false);
			return;
		}

		$newPos = $this->newPosition;
		$distanceSquared = $newPos->distanceSquared($this);

		$revert = false;

		if($this->chunk === null or !$this->chunk->isGenerated()){
			$chunk = $this->level->getChunk($newPos->x >> 4, $newPos->z >> 4, false);
			if($chunk === null or !$chunk->isGenerated()){
				$revert = true;
				$this->nextChunkOrderRun = 0;
			}else{
				if($this->chunk !== null){
					$this->chunk->removeEntity($this);
				}
				$this->chunk = $chunk;
			}
		}

		if(!$revert and $distanceSquared != 0){
			$dx = $newPos->x - $this->x;
			$dy = $newPos->y - $this->y;
			$dz = $newPos->z - $this->z;

			$this->move($dx, $dy, $dz);

			$this->x = $newPos->x;
			$this->y = $newPos->y;
			$this->z = $newPos->z;
		}

		$from = new Location($this->lastX, $this->lastY, $this->lastZ, $this->lastYaw, $this->lastPitch, $this->level);
		$to = $this->getLocation();

		$delta = pow($this->lastX - $to->x, 2) + pow($this->lastY - $to->y, 2) + pow($this->lastZ - $to->z, 2);
		$deltaAngle = abs($this->lastYaw - $to->yaw) + abs($this->lastPitch - $to->pitch);

		if(!$revert and ($delta > (1 / 16) or $deltaAngle > 10)){

			$isFirst = ($this->lastX === null or $this->lastY === null or $this->lastZ === null);

			$this->lastX = $to->x;
			$this->lastY = $to->y;
			$this->lastZ = $to->z;

			$this->lastYaw = $to->yaw;
			$this->lastPitch = $to->pitch;

			if(!$isFirst) {
				$this->isTeleportedForMoveEvent = false;
				$ev = new PlayerMoveEvent($this, $from, $to);
				$this->setMoving(true);
				$this->server->getPluginManager()->callEvent($ev);
				if($this->isTeleportedForMoveEvent) {
					return;
				}
				if(!($revert = $ev->isCancelled())) { //Yes, this is intended
					if($to->distanceSquared($ev->getTo()) > 0.01) { //If plugins modify the destination
						$this->teleport($ev->getTo());
					} else {
						$this->level->addEntityMovement($this->getViewers(), $this->getId(), $this->x, $this->y + $this->getVisibleEyeHeight(), $this->z, $this->yaw, $this->pitch, $this->yaw, true);
					}
				}
			}

			$this->speed = $from->subtract($to);

		}elseif($distanceSquared == 0){
			$this->speed = new Vector3(0, 0, 0);
			$this->setMoving(false);
		}

		if($revert){
			$this->lastX = $from->x;
			$this->lastY = $from->y;
			$this->lastZ = $from->z;

			$this->lastYaw = $from->yaw;
			$this->lastPitch = $from->pitch;

			$this->sendPosition($from, $from->yaw, $from->pitch, MovePlayerPacket::MODE_RESET);
			$this->forceMovement = new Vector3($from->x, $from->y, $from->z);
		}else{
			$this->forceMovement = null;
			if($distanceSquared != 0 and $this->nextChunkOrderRun > 20){
				$this->nextChunkOrderRun = 20;
			}
		}

		$this->newPosition = null;
	}

	protected $moving = false;

	public function setMoving($moving) {
		$this->moving = $moving;
	}

	public function isMoving(){
		return $this->moving;
	}

	public function setMotion(Vector3 $mot){
		if(parent::setMotion($mot)){
			if($this->chunk !== null){
				$this->level->addEntityMotion($this->getViewers(), $this->getId(), $this->motionX, $this->motionY, $this->motionZ);
				$pk = new SetEntityMotionPacket();
				$pk->entities[] = [$this->id, $mot->x, $mot->y, $mot->z];
				$this->dataPacket($pk);
			}

			if($this->motionY > 0){
				$this->startAirTicks = (-(log($this->gravity / ($this->gravity + $this->drag * $this->motionY))) / $this->drag) * 2 + 5;
			}

			return true;
		}
		return false;
	}

	public function onUpdate($currentTick){
		if(!$this->loggedIn){
			return false;
		}

		$tickDiff = $currentTick - $this->lastUpdate;

		if($tickDiff <= 0){
			return true;
		}

		$this->messageCounter = 2;

		$this->lastUpdate = $currentTick;

		if($this->nextChunkOrderRun-- <= 0 or $this->chunk === null){
			$this->orderChunks();
		}

		if(count($this->loadQueue) > 0 or !$this->spawned){
			$this->sendNextChunk();
		}

		if(!$this->isAlive() and $this->spawned){
			$this->deadTicks += $tickDiff;
			if($this->deadTicks >= 10){
				$this->despawnFromAll();
			}
			return true;
		}

		if($this->spawned){
			$this->processMovement($tickDiff);

			$this->entityBaseTick($tickDiff);

			if(!$this->isSpectator() and $this->isAlive()){
				$this->checkNearEntities($tickDiff);

				if($this->speed !== null) {
					if($this->onGround or $this->isCollideWithLiquid()) {
						if($this->inAirTicks !== 0) {
							$this->startAirTicks = 5;
						}
						$this->inAirTicks = 0;
					}else{
						//if(!$this->allowFlight and $this->inAirTicks > 10 and !$this->isSleeping() and !$this->isImmobile()){
						//	$expectedVelocity = (-$this->gravity) / $this->drag - ((-$this->gravity) / $this->drag) * exp(-$this->drag * ($this->inAirTicks - $this->startAirTicks));
						//	$diff = ($this->speed->y - $expectedVelocity) ** 2;
						//
						//	if(!$this->hasEffect(Effect::JUMP) and $diff > 0.6 and $expectedVelocity < $this->speed->y and !$this->server->getAllowFlight()){
						//		if($this->inAirTicks < 301){
						//			$this->setMotion(new Vector3(0, $expectedVelocity, 0));
						//		}elseif($this->kick("Flying is not enabled on this server")){
						//			$this->timings->stopTiming();
						//			return false;
						//		}
						//	}
						//}
						$this->inAirTicks += $tickDiff;
					}
				}
			}

			$this->checkChunks();

			$this->inventoryAdapter->doTick($currentTick);

			$this->messageQueue->doTick();
			$this->popupQueue->doTick();
			$this->tipQueue->doTick();
		}

		if(count($this->noteSoundQueue) > 0) {
			$noteId = array_shift($this->noteSoundQueue);
			$this->sendNoteSound($noteId);
		}

		return true;
	}

	public function eatFoodInHand() {
		if(!$this->isAlive() or !$this->spawned) {
			return;
		}

		$slot = $this->inventory->getItemInHand();
		if($slot instanceof Edible and $slot->canBeConsumedBy($this)){
			$slot->onConsume($this);
		}
	}

	/**
	 * Handles a Minecraft packet
	 * TODO: Separate all of this in handlers
	 *
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 *
	 * @param DataPacket $packet
	 *
	 * @return mixed
	 */
	public function handleDataPacket(DataPacket $packet){
		if(!$this->connected) {
			return true;
		}

		if($packet->pname() === 'BATCH_PACKET') {
			/** @var BatchPacket $packet */
			$this->server->getNetwork()->processBatch($packet, $this);
			return true;
		}

		$this->server->getPluginManager()->callEvent($ev = new DataPacketReceiveEvent($this, $packet));
		if($ev->isCancelled()) {
			return true;
		}

		$beforeLoginAvailablePackets = ['LOGIN_PACKET', 'REQUEST_CHUNK_RADIUS_PACKET', 'RESOURCE_PACKS_CLIENT_RESPONSE_PACKET', 'CLIENT_TO_SERVER_HANDSHAKE_PACKET'];

		if(!$this->isOnline() and !in_array($packet->pname(), $beforeLoginAvailablePackets)) {
			return true;
		}

		if ($packet->targetSubClientID > 0 && isset($this->subClients[$packet->targetSubClientID])) {
			$this->subClients[$packet->targetSubClientID]->handleDataPacket($packet);
			return;
		}

		switch($packet->pname()) {
			case 'SET_PLAYER_GAMETYPE_PACKET':
				$this->kick(TextFormat::RED . "Sorry, mods are not permitted on this server!");
				return true;
			case 'UPDATE_ATTRIBUTES_PACKET':
				$this->kick(TextFormat::RED . "Sorry, mods are not permitted on this server!");
				return true;
			case 'ADVENTURE_SETTINGS_PACKET':
				if((!$this->allowFlight and ($packet->flags >> 9) & 0x01 === 1) or (!$this->isCreative() and ($packet->flags >> 7) & 0x01 === 1)) {
					$this->kick(TextFormat::RED . "Sorry, mods are not permitted on this server!");
				}
				return true;
			case 'LOGIN_PACKET':
				if($this->loggedIn) {
					return true;
				}
				if(!$packet->isValidProtocol) {
					$this->protocol = $packet->protocol1; // we need protocol for correct encoding DisconnectPacket
					$this->close("", TextFormat::RED . "Please switch to Minecraft: PE " . TextFormat::GREEN . $this->getServer()->getVersion() . TextFormat::RED . " to join.");
					return true;
				}

				$this->username = TextFormat::clean($packet->username);
				$this->xblName = $this->username;
				$this->displayName = $this->username;
				$this->setNameTag($this->username);
				$this->iusername = strtolower($this->username);
				$this->randomClientId = $packet->clientId;
				$this->loginData = ["clientId" => $packet->clientId, "loginData" => null];
				$this->uuid = $packet->clientUUID;
				$this->subClientId = $packet->targetSubClientID;
				if (is_null($this->uuid)) {
					$this->close("", "Sorry, your client is broken.");
					break;
				}
				$this->rawUUID = $this->uuid->toBinary();
				$this->clientSecret = $packet->clientSecret;
				$this->protocol = $packet->protocol1;
				$this->setSkin($packet->skin, $packet->skinName, $packet->skinGeometryName, $packet->skinGeometryData, $packet->capeData);
				if($packet->osType > 0) {
					$this->deviceType = $packet->osType;
				}
				if($packet->inventoryType >= 0) {
					$this->inventoryType = $packet->inventoryType;
				}
				$this->xuid = $packet->xuid;
				$this->languageCode = $packet->languageCode;

				$this->serverAddress = $packet->serverAddress;
				$this->clientVersion = $packet->clientVersion;
				$this->originalProtocol = $packet->originalProtocol;

				$this->processLogin();
				return true;
			case 'MOVE_PLAYER_PACKET':
				if(!$this->isAlive() or !$this->spawned) {
					$this->forceMovement = new Vector3($this->x, $this->y, $this->z);
					$this->sendPosition($this->forceMovement, $packet->yaw, $packet->pitch, MovePlayerPacket::MODE_RESET);
				} else {
					$newPos = new Vector3($packet->x, $packet->y - $this->getEyeHeight(), $packet->z);
					if(!($this->forceMovement instanceof Vector3) or $newPos->distanceSquared($this->forceMovement) <= 0.1) {
						$packet->yaw %= 360;
						$packet->pitch %= 360;

						if($packet->yaw < 0) {
							$packet->yaw += 360;
						}

						if(!$this->isMayMove) {
							if($this->yaw != $packet->yaw or $this->pitch != $packet->pitch or abs($this->x - $packet->x) >= 0.05 or abs($this->z - $packet->z) >= 0.05) {
								$this->setMayMove(true);
								$spawn = $this->getSpawn();
								$spawn->y += 0.1;
								$this->teleport($spawn);
							}
						}

						$this->setRotation($packet->yaw, $packet->pitch);
						$this->newPosition = $newPos;
						$this->forceMovement = null;
					} elseif(microtime(true) - $this->lastTeleportTime > 2) {
						$this->forceMovement = new Vector3($this->x, $this->y, $this->z);
						$this->sendPosition($this->forceMovement, $packet->yaw, $packet->pitch, MovePlayerPacket::MODE_RESET);
						$this->lastTeleportTime = microtime(true);
					}
				}
				return true;
			case 'MOB_EQUIPMENT_PACKET':
				if($this->spawned == false or !$this->isAlive()) {
					break;
				}

				$this->getInventoryAdapter()->handleMobEquipment($packet->hotbarSlot, $packet->item, $packet->inventorySlot);
				break;
			case 'USE_ITEM_PACKET':
				if($this->spawned == false or !$this->isAlive()) {
					break;
				}

				$this->getInventoryAdapter()->handleUseItem($packet->item, $packet->hotbarSlot, $packet->face, new Vector3($packet->x, $packet->y, $packet->z), new Vector3($packet->fx, $packet->fy, $packet->fz));
				break;
			case 'PLAYER_ACTION_PACKET':
				if(!$this->spawned or (!$this->isAlive() and !in_array($packet->action, [PlayerActionPacket::ACTION_RESPAWN, PlayerActionPacket::ACTION_DIMENSION_CHANGE]))){
					break;
				}

				$packet->eid = $this->id;
				$pos = new Vector3($packet->x, $packet->y, $packet->z);

				switch($packet->action) {
					case PlayerActionPacket::ACTION_START_BREAK:
						if($this->lastBreak !== PHP_INT_MAX or $pos->distanceSquared($this) > 10000) {
							break;
						}

						$target = $this->level->getBlock($pos);
						//$this->getServer()->getPluginManager()->callEvent($ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $target, $packet->face, $target->getId() === 0 ? PlayerInteractEvent::LEFT_CLICK_AIR : PlayerInteractEvent::LEFT_CLICK_BLOCK));
						//if($ev->isCancelled()) {
						//	$this->inventory->sendHeldItem($this);
						//	break;
						//}

						$block = $target->getSide($packet->face);
						if($block->getId() === Block::FIRE) {
							$this->level->setBlock($block, BlockFactory::get(Block::AIR));
							break;
						}

						if(!$this->isCreative()) {
							$breakTime = ceil($target->getBreakTime($this->inventory->getItemInHand()) * 20);
							if($breakTime > 0) {
								$pk = new LevelEventPacket();
								$pk->evid = LevelEventPacket::EVENT_BLOCK_START_BREAK;
								$pk->x = $pos->x;
								$pk->y = $pos->y;
								$pk->z = $pos->z;
								$pk->data = (int) (65535 / $breakTime);
								/** @var Player $recipient */
								foreach(array_merge($this->getViewers(), [$this]) as $recipient) {
									$recipient->dataPacket($pk);
								}
							}
						}
						$this->actionsNum["CRACK_BLOCK"] = 0;
						$this->lastBreak = microtime(true);
						break;
					/** @noinspection PhpMissingBreakStatementInspection */
					case PlayerActionPacket::ACTION_ABORT_BREAK:
						$this->lastBreak = PHP_INT_MAX;
					case PlayerActionPacket::ACTION_STOP_BREAK:
						$pk = new LevelEventPacket();
						$pk->evid = LevelEventPacket::EVENT_BLOCK_STOP_BREAK;
						$pk->x = $packet->x;
						$pk->y = $packet->y;
						$pk->z = $packet->z;
						/** @var Player $recipient */
						foreach(array_merge($this->getViewers(), [$this]) as $recipient) {
							$recipient->dataPacket($pk);
						}
						break;
					case PlayerActionPacket::ACTION_RELEASE_ITEM:
						$this->releaseUseItem();
						break;
					case PlayerActionPacket::ACTION_START_SLEEPING:
						$this->sleepOn($pos);
						break;
					case PlayerActionPacket::ACTION_STOP_SLEEPING:
						$this->stopSleep();
						break;
					case PlayerActionPacket::ACTION_RESPAWN:
						if(!$this->spawned or $this->isAlive() or !$this->isOnline()) {
							break;
						}

						if($this->server->isHardcore()) {
							$this->setBanned(true);
							break;
						}

						$this->craftingType = self::CRAFTING_DEFAULT;

						$this->server->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($this, $this->getSpawn()));

						$this->teleport($ev->getRespawnPosition()->add(0.5, 0.5, 0.5));

						$this->setSprinting(false, true);
						$this->setSneaking(false);

						$this->extinguish();
						$this->setAirTick(400);
						$this->deadTicks = 0;
						$this->dead = false;
						$this->noDamageTicks = 60;

						$this->removeAllEffects();

						$this->setHealth($this->getMaxHealth());
						$this->setFood(self::MAX_FOOD);

						$this->setSaturation(self::MAX_SATURATION);
						$this->setExhaustion(self::MIN_EXHAUSTION);
						$this->foodTickTimer = 0;
						$this->lastSentVitals = 10;

						$this->sendSettings();
						$this->inventory->sendContents($this);
						$this->inventory->sendArmorContents($this);

						$this->blocked = false;

						$this->scheduleUpdate();

						$this->server->getPluginManager()->callEvent(new PlayerRespawnAfterEvent($this));
						break;
					case PlayerActionPacket::ACTION_JUMP:
						$this->onJump();
						return true;
					case PlayerActionPacket::ACTION_START_SPRINT:
						$this->server->getPluginManager()->callEvent($ev = new PlayerToggleSprintEvent($this, true));
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSprinting(true);
						}
						return true;
					case PlayerActionPacket::ACTION_STOP_SPRINT:
						$this->server->getPluginManager()->callEvent($ev = new PlayerToggleSprintEvent($this, false));
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSprinting(false);
						}
						return true;
					case PlayerActionPacket::ACTION_START_SNEAK:
						$this->server->getPluginManager()->callEvent($ev = new PlayerToggleSneakEvent($this, true));
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSneaking(true);
						}
						return true;
					case PlayerActionPacket::ACTION_STOP_SNEAK:
						$this->server->getPluginManager()->callEvent($ev = new PlayerToggleSneakEvent($this, false));
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSneaking(false);
						}
						return true;
					case PlayerActionPacket::ACTION_START_GLIDE:
					case PlayerActionPacket::ACTION_STOP_GLIDE:
						break; //TODO
					case PlayerActionPacket::ACTION_CONTINUE_BREAK:
						if($this->actionsNum["CRACK_BLOCK"]++ % 4 == 0) { // send block crack every 4 ticks
							$target = $this->level->getBlock($pos);
							$pk = new LevelEventPacket();
							$pk->evid = LevelEventPacket::EVENT_PARTICLE_PUNCH_BLOCK;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->data = $target->getId() | ($target->getDamage() << 8);
							/** @var Player $recipient */
							foreach(array_merge($this->getViewers(), [$this]) as $recipient) {
								$recipient->dataPacket($pk);
							}
						}
						break;
					default:
						$this->server->getLogger()->debug("Unhandled/unknown player action type " . $packet->action . " from " . $this->getName());
						return false;
				}

				$this->setUsingItem(false);

				return true;
			case 'REMOVE_BLOCK_PACKET':
				//Timings::$timerRemoveBlockPacket->startTiming();
				$this->breakBlock(new Vector3($packet->x, $packet->y, $packet->z));
				//Timings::$timerRemoveBlockPacket->stopTiming();
				break;
			case 'MOB_ARMOR_EQUIPMENT_PACKET':
				return $this->inventoryAdapter->handleMobArmorEquipment($packet);
			case 'INTERACT_PACKET':
				if($packet->action === InteractPacket::ACTION_DAMAGE) {
					$target = $this->getLevel()->getEntity($packet->target);
					if($target === null) {
						return false;
					}
					$this->attackEntity($target);
				} else {
					$this->customInteract($packet);
				}
				break;
			case 'ANIMATE_PACKET':
				//Timings::$timerAnimatePacket->startTiming();
				if($this->spawned === false or $this->dead === true){
					//Timings::$timerAnimatePacket->stopTiming();
					break;
				}

				$this->server->getPluginManager()->callEvent($ev = new PlayerAnimationEvent($this, $packet->action));
				if($ev->isCancelled()){
					//Timings::$timerAnimatePacket->stopTiming();
					break;
				}

				$pk = new AnimatePacket();
				$pk->eid = $this->id;
				$pk->action = $ev->getAnimationType();
				Server::broadcastPacket($this->getViewers(), $pk);
				//Timings::$timerAnimatePacket->stopTiming();
				break;
			case 'SET_HEALTH_PACKET': //Not used
				break;
			case 'ENTITY_EVENT_PACKET':
				//Timings::$timerEntityEventPacket->startTiming();
				if($this->spawned === false or $this->blocked === true or $this->dead === true){
					//Timings::$timerEntityEventPacket->stopTiming();
					break;
				}
//				$this->craftingType = self::CRAFTING_DEFAULT;

				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false); //TODO: check if this should be true

				switch($packet->event){
					case EntityEventPacket::USE_ITEM: //Eating
						$this->eatFoodInHand();
						break;
					case EntityEventPacket::ENCHANT:
						if ($this->currentWindow instanceof EnchantInventory) {
							if ($this->expLevel > 0) {
								$enchantLevel = abs($packet->theThing);
								if ($this->protocol >= ProtocolInfo::PROTOCOL_120) {
									$this->currentWindow->setEnchantingLevel($enchantLevel);
									return;
								}
								$items = $this->inventory->getContents();
								foreach ($items as $slot => $item) {
									if ($item->getId() === Item::DYE && $item->getDamage() === 4 && $item->getCount() >= $enchantLevel) {

										break 2;
									}
								}
							}
							$this->currentWindow->setItem(0, ItemFactory::get(Item::AIR));
							$this->currentWindow->setEnchantingLevel(0);
							$this->currentWindow->sendContents($this);
							$this->inventory->sendContents($this);
						}
						break;
					case EntityEventPacket::FEED:
						$position = [ 'x' => $this->x, 'y' => $this->y, 'z' => $this->z ];
						$this->sendSound(LevelSoundEventPacket::SOUND_EAT, $position, 63);
						break;
				}
				//Timings::$timerEntityEventPacket->stopTiming();
				break;
			case 'DROP_ITEM_PACKET':
				$this->getInventoryAdapter()->handleDropItem($packet->item);
				break;
			case 'TEXT_PACKET':
				//Timings::$timerTextPacket->startTiming();
				if($this->spawned === false or $this->dead === true){
					//Timings::$timerTextPacket->stopTiming();
					break;
				}
//				$this->craftingType = self::CRAFTING_DEFAULT;
				if($packet->type === TextPacket::TYPE_CHAT){
					$packet->message = TextFormat::clean($packet->message, $this->removeFormat);
					foreach(explode("\n", $packet->message) as $message){
						if(trim($message) != "" and $this->messageCounter-- > 0){
							$this->server->getPluginManager()->callEvent($ev = new PlayerChatEvent($this, $message));
							if(!$ev->isCancelled()){
								$baseMessage = $ev->getFormat();
								$params = [$ev->getPlayer()->getDisplayName(), $ev->getMessage()];
								foreach($params as $i => $p){
									$baseMessage = str_replace("{%$i}", (string) $p, $baseMessage);
								}
								$baseMessage = str_replace("%0", "", $baseMessage); //fixes a client bug where %0 in translation will cause freeze
								$this->server->broadcastMessage($baseMessage, $ev->getRecipients());
							}
						}
					}
				} else {
					echo "Recive message with type ".$packet->type.PHP_EOL;
				}
				//Timings::$timerTextPacket->stopTiming();
				break;
			case 'CONTAINER_CLOSE_PACKET':
				if($this->spawned === false or $packet->windowId === 0){
					return true;
				}

				$this->inventoryAdapter->handleContainerClose($packet->windowId);
				break;
			case 'CRAFTING_EVENT_PACKET':
				if($this->spawned === false or !$this->isAlive()) {
					break;
				}

				$recipe = $this->server->getCraftingManager()->getRecipe($packet->id);

				if($recipe === null or (($recipe instanceof BigShapelessRecipe or $recipe instanceof BigShapedRecipe) and $this->craftingType === 0) or count($packet->input) === 0) {
					$this->inventory->sendContents($this);
					return false;
				}

				$this->inventoryAdapter->handleCraftingEvent($recipe, $packet->input, $packet->output);

				break;

			case 'CONTAINER_SET_SLOT_PACKET':
				$this->getInventoryAdapter()->handleContainerSetSlot($packet->slot, $packet->windowId, $packet->item, $packet->hotbarSlot);
				break;
			case 'TILE_ENTITY_DATA_PACKET':
				//Timings::$timerTileEntityPacket->startTiming();
				if($this->spawned === false or $this->blocked === true or $this->dead === true){
					//Timings::$timerTileEntityPacket->stopTiming();
					break;
				}
//				$this->craftingType = self::CRAFTING_DEFAULT;

				$pos = new Vector3($packet->x, $packet->y, $packet->z);
				if($pos->distanceSquared($this) > 10000){
					//Timings::$timerTileEntityPacket->stopTiming();
					break;
				}

				$t = $this->level->getTile($pos);
				if ($t instanceof Sign) {
					// prepare NBT data
					$nbt = new NBT(NBT::LITTLE_ENDIAN);
					$nbt->read($packet->namedtag, false, true);
					$nbtData = $nbt->getData();
					$isNotCreator = !isset($t->namedtag->Creator) || $t->namedtag["Creator"] !== $this->username;
					// check tile id
					if ($nbtData["id"] !== Tile::SIGN || $isNotCreator) {
						$t->spawnTo($this);
						break;
					}
					// collect sign text lines
					$signText = [];
					if ($this->protocol >= Info::PROTOCOL_120) {
						$signText = explode("\n", $nbtData['Text']);
						for ($i = 0; $i < 4; $i++) {
							$signText[$i] = isset($signText[$i]) ? TextFormat::clean($signText[$i], $this->removeFormat) : '';
						}
						unset($nbtData['Text']);
					} else {
						for ($i = 0; $i < 4; $i++) {
							$signText[$i] = TextFormat::clean($nbtData["Text" . ($i + 1)], $this->removeFormat);
						}
					}
					// event part
					$ev = new SignChangeEvent($t->getBlock(), $this, $signText);
					$this->server->getPluginManager()->callEvent($ev);
					if ($ev->isCancelled()) {
						$t->spawnTo($this);
					} else {
						$t->setText($ev->getLine(0), $ev->getLine(1), $ev->getLine(2), $ev->getLine(3));
					}
				}
				//Timings::$timerTileEntityPacket->stopTiming();
				break;
			case 'REQUEST_CHUNK_RADIUS_PACKET':
				//Timings::$timerChunkRudiusPacket->startTiming();
				$this->setViewDistance($packet->radius);
				$this->loggedIn = true;
				$this->scheduleUpdate();
				//Timings::$timerChunkRudiusPacket->stopTiming();
				break;
			case 'COMMAND_STEP_PACKET':
				$name = $packet->name;
				$params = json_decode($packet->outputFormat, true);
				$command = "/" . $name;
				if(is_array($params)) {
					foreach($params as $param => $data) {
						if(is_array($data)) { // Target argument type
							if(isset($data["selector"])) {
								$selector = $data["selector"];
								switch($selector) {
									case "nearestPlayer":
										if(isset($data["rules"])) { // Player has been specified
											$player = $data["rules"][0]["value"]; // Player name
											break;
										}
										$nearest = null;
										$distance = PHP_INT_MAX;
										foreach($this->getViewers() as $p) {
											if($p instanceof Player) {
												$dist = $this->distance($p->getPosition());
												if($dist < $distance) {
													$nearest = $p;
													$distance = $dist;
												}
											}
										}
										if($nearest instanceof Player) {
											$player = $nearest->getName();
										} else {
											$player = "@p";
										}
										break;
									case "allPlayers":
										// no handling here yet
										$player = "@a";
										break;
									case "randomPlayer":
										$players = $this->getServer()->getOnlinePlayers();
										$player = $players[array_rand($players)]->getName();
										break;
									case "allEntities":
										// no handling here yet
										$player = "@e";
										break;
									default:
										$this->getServer()->getLogger()->warning("Unhandled selector for target argument!");
										var_dump($selector);
										$player = " ";
										break;
								}
								$command .= " " . $player;
							} else { // Another argument type?
								$this->getServer()->getLogger()->warning("No selector set for target argument!");
								var_dump($data);
							}
						} elseif(is_string($data)) { // Normal string argument
							$command .= " " . $data;
						} else { // Unhandled argument type
							$this->getServer()->getLogger()->warning("Unhandled command data type!");
							var_dump($data);
						}
					}
				}
				$ev = new PlayerCommandPreprocessEvent($this, $command);
				$this->getServer()->getPluginManager()->callEvent($ev);
				if($ev->isCancelled()) {
					return;
				}
				$this->getServer()->dispatchCommand($this, substr($ev->getMessage(), 1));
				break;
			case 'RESOURCE_PACKS_CLIENT_RESPONSE_PACKET':
				switch ($packet->status) {
					case ResourcePackClientResponsePacket::STATUS_REFUSED:
					case ResourcePackClientResponsePacket::STATUS_SEND_PACKS:
					case ResourcePackClientResponsePacket::STATUS_HAVE_ALL_PACKS:
						$pk = new ResourcePackStackPacket();
						$this->dataPacket($pk);
						break;
					case ResourcePackClientResponsePacket::STATUS_COMPLETED:
						$this->completeLogin();
						break;
					default:
						return false;
				}
				break;
			/** @minProtocol 120 */
			case 'INVENTORY_TRANSACTION_PACKET':
				$this->inventoryAdapter->handleInventoryTransaction($packet->actions, $packet->isCraftingPart, $packet->transactionType, $packet->trData);
				break;
			/** @minProtocol 120 */
			case 'COMMAND_REQUEST_PACKET':
				if ($packet->command[0] != '/') {
					$this->sendMessage('Invalid command data.');
					break;
				}
				$commandLine = substr($packet->command, 1);
				$commandPreprocessEvent = new PlayerCommandPreprocessEvent($this, $commandLine);
				$this->server->getPluginManager()->callEvent($commandPreprocessEvent);
				if ($commandPreprocessEvent->isCancelled()) {
					break;
				}

				$this->server->dispatchCommand($this, $commandLine);

				$commandPostprocessEvent = new PlayerCommandPostprocessEvent($this, $commandLine);
				$this->server->getPluginManager()->callEvent($commandPostprocessEvent);
				break;
			/** @minProtocol 120 */
			case 'PLAYER_SKIN_PACKET':
				$this->setSkin($packet->newSkinByteData, $packet->newSkinId, $packet->newSkinGeometryName, $packet->newSkinGeometryData, $packet->newCapeByteData);
				// Send new skin to viewers and to self
				$this->updatePlayerSkin($packet->oldSkinName, $packet->newSkinName);
				break;
			/** @minProtocol 120 */
			case 'MODAL_FORM_RESPONSE_PACKET':
				$this->checkModal($packet->formId, json_decode($packet->data, true));
				break;
			/** @minProtocol 120 */
			case 'PURCHASE_RECEIPT_PACKET':
				$event = new PlayerReceiptsReceivedEvent($this, $packet->receipts);
				$this->server->getPluginManager()->callEvent($event);
				break;
			case 'SERVER_SETTINGS_REQUEST_PACKET':
				$this->sendServerSettings();
				break;
			case 'CLIENT_TO_SERVER_HANDSHAKE_PACKET':
				$this->continueLoginProcess();
				break;
			case 'SUB_CLIENT_LOGIN_PACKET':
				$subPlayer = new static($this->interface, null, $this->ip, $this->port);
				if($subPlayer->subAuth($packet, $this)) {
					$this->subClients[$packet->targetSubClientID] = $subPlayer;
				}
				//$this->kick("COOP play is not allowed");
				break;
			case 'DISCONNECT_PACKET':
				if ($this->subClientId > 0) {
					$this->close('', 'client disconnect');
				}
				break;
			default:
				break;
		}
	}

	/**
	 * Kicks a player from the server
	 *
	 * @param string $reason
	 *
	 * @return bool
	 */
	public function kick($reason = "Disconnected from server."){
		$this->server->getPluginManager()->callEvent($ev = new PlayerKickEvent($this, $reason, TextFormat::YELLOW . $this->username . " has left the game"));
		if(!$ev->isCancelled()){
			$this->close($ev->getQuitMessage(), $reason);
			return true;
		}

		return false;
	}

	/**
	 * Drops the specified item in front of the player.
	 *
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function dropItem(Item $item) : bool {
		if($this->spawned === false or !$this->isAlive()) {
			return false;
		}

		if($item->isNull()){
			$this->server->getLogger()->debug($this->getName() . " attempted to drop a null item (" . $item . ")");
			return true;
		}

		$ev = new PlayerDropItemEvent($this, $item);
		$this->server->getPluginManager()->callEvent($ev);
		if($ev->isCancelled()) {
			if($this->inventoryAdapter instanceof PlayerInventoryAdapter) {
				$this->inventoryAdapter->getFloatingInventory()->removeItem($item);
				$this->getInventory()->addItem($item);
			}
			return false;
		}

		$motion = $this->getDirectionVector()->multiply(0.4);

		$this->level->dropItem($this->add(0, 1.3, 0), $item, $motion, 40);

		$this->setUsingItem(false);

		return true;
	}

	/**
	 * Schedules a chat message to be sent to a player
	 *
	 * @param string $message
	 */
	public function sendMessage($message){
		$this->messageQueue->addItem((string) $message);
	}

	/**
	 * @param string $message    Message to be displayed in popup
	 * @param int $duration      Ticks to display popup for (default of 4 ticks)
	 */
	public function sendPopup(string $message, int $duration = PopupQueue::BASE_POPUP_DURATION){
		$this->popupQueue->addItem($message, $duration);
	}

	/**
	 * @param string $message    Message to be displayed in popup
	 * @param int $duration      Ticks to display tip for (default of 8 ticks)
	 */
	public function sendTip(string $message, int $duration = TipQueue::BASE_TIP_DURATION){
		$this->tipQueue->addItem($message, $duration);
	}

	/**
	 * Sends a message directly to the player, skipping the queue
	 *
	 * @param string $message
	 */
	public function sendDirectMessage(string $message) {
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_RAW;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	/**
	 * Sends a popup directly to the player, skipping the queue
	 *
	 * @param string $message
	 */
	public function sendDirectPopup(string $message) {
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_POPUP;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	/**
	 * Sends a tip directly to the player, skipping the queue
	 *
	 * @param string $message
	 */
	public function sendDirectTip(string $message) {
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_TIP;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	public function sendChatMessage($senderName, $message) {
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_CHAT;
		$pk->message = $message;
		$pk->source = $senderName;
		$sender = $this->server->getPlayer($senderName);
		if ($sender !== null) {
			$pk->xuid = $sender->getXUID();
		}
		$this->dataPacket($pk);
	}

	public function sendTranslation($message, array $parameters = []){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_RAW;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	/**
	 * @param string $message Message to be broadcasted
	 * @param string $reason  Reason showed in console
	 */
	public function close($message = "", $reason = "generic reason"){
		if ($this->parent !== null) {
			$this->parent->removeSubClient($this->subClientId);
		} else {
			foreach ($this->subClients as $subClient) {
				$subClient->close($message, $reason);
			}
		}
		foreach($this->tasks as $task){
			$task->cancel();
		}
		$this->tasks = [];
		if($this->connected and !$this->closed){
			$pk = new DisconnectPacket;
			$pk->message = $reason;
			$this->directDataPacket($pk);
			$this->connected = false;
			if($this->username != ""){
				$this->server->getPluginManager()->callEvent($ev = new PlayerQuitEvent($this, $message, $reason));
				if($this->server->getSavePlayerData()){
					$this->save();
				}
			}

			foreach($this->server->getOnlinePlayers() as $player){
				if(!$player->canSee($this)){
					$player->showPlayer($this);
				}
				$player->despawnFrom($this);
			}
			$this->hiddenPlayers = [];
			$this->hiddenEntity = [];

			$this->removeAllWindows(true);
			$this->windows = null;
			$this->windowIndex = [];

			$this->interface->close($this, $reason);

			$chunkX = $chunkZ = null;
			foreach($this->usedChunks as $index => $d){
				Level::getXZ($index, $chunkX, $chunkZ);
				$this->level->freeChunk($chunkX, $chunkZ, $this);
				unset($this->usedChunks[$index]);
			}

			parent::close();

			$this->server->removeOnlinePlayer($this);

			$this->loggedIn = false;

//			if(isset($ev) and $this->username != "" and $this->spawned !== false and $ev->getQuitMessage() != ""){
//				$this->server->broadcastMessage($ev->getQuitMessage());
//			}

			$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
			$this->spawned = false;
			$this->server->getLogger()->info(TextFormat::AQUA . $this->username . TextFormat::WHITE . "/" . $this->ip . " logged out due to " . str_replace(["\n", "\r"], [" ", ""], $reason));
			$this->usedChunks = [];
			$this->loadQueue = [];
			$this->hasSpawned = [];
			$this->spawnPosition = null;
			unset($this->buffer);
		}

			$this->perm->clearPermissions();
			$this->server->removePlayer($this);
	}

	public function __debugInfo(){
		return [];
	}

	/**
	 * Handles player data saving
	 */
	public function save(){
		if($this->closed){
			throw new \InvalidStateException("Tried to save closed player");
		}

		if($this->spawned) {
			parent::saveNBT();
			if($this->level instanceof Level) {
				$this->namedtag->Level = new StringTag("Level", $this->level->getName());
				if($this->spawnPosition instanceof Position and $this->spawnPosition->getLevel() instanceof Level) {
					$this->namedtag["SpawnLevel"] = $this->spawnPosition->getLevel()->getName();
					$this->namedtag["SpawnX"] = (int) $this->spawnPosition->x;
					$this->namedtag["SpawnY"] = (int) $this->spawnPosition->y;
					$this->namedtag["SpawnZ"] = (int) $this->spawnPosition->z;
				}
				$this->namedtag["playerGameType"] = $this->gamemode;
				$this->namedtag["lastPlayed"] = floor(microtime(true) * 1000);
				if($this->username != "" and $this->namedtag instanceof Compound) {
					$this->server->saveOfflinePlayerData($this->username, $this->namedtag, true);
				}
			}
		}
	}

	/**
	 * Gets the username
	 *
	 * @return string
	 */
	public function getName(){
		return $this->username;
	}

    public function getXBLName() {
        return $this->xblName;
    }

	public function freeChunks(){
		$x = $z = null;
		foreach ($this->usedChunks as $index => $chunk) {
			Level::getXZ($index, $x, $z);
			$this->level->freeChunk($x, $z, $this);
			unset($this->usedChunks[$index]);
			unset($this->loadQueue[$index]);
		}
	}

	public function kill(){
		if(!$this->spawned) {
			return;
		}

		parent::kill();

		$this->freeChunks();

		if($this->server->isHardcore()) {
			$this->setBanned(true);
		} else {
			$pk = new RespawnPacket();
			$pos = $this->getSpawn();
			$pk->x = $pos->x;
			$pk->y = $pos->y + $this->eyeHeight;
			$pk->z = $pos->z;
			$this->dataPacket($pk);

			$this->setMayMove(false);
		}
	}

	protected function callDeathEvent() {
		$message = $this->getName() . " died";

		$cause = $this->getLastDamageCause();

		switch($cause === null ? EntityDamageEvent::CAUSE_CUSTOM : $cause->getCause()) {
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				if($cause instanceof EntityDamageByEntityEvent) {
					$e = $cause->getDamager();
					if($e instanceof Player) {
						$message = $this->getName() . " was killed by " . $e->getName();
						break;
					} elseif($e instanceof Living) {
						$message = $this->getName() . " was slain by " . $e->getName();
						break;
					}
				}
				$message = $this->getName() . " was killed";
				break;
			case EntityDamageEvent::CAUSE_PROJECTILE:
				if($cause instanceof EntityDamageByEntityEvent) {
					$e = $cause->getDamager();
					if($e instanceof Living) {
						$message = $this->getName() . " was shot by " . $e->getName();
						break;
					}
				}
				$message = $this->getName() . " was shot by arrow";
				break;
			case EntityDamageEvent::CAUSE_SUICIDE:
				$message = $this->getName() . " died";
				break;
			case EntityDamageEvent::CAUSE_VOID:
				$message = $this->getName() . " fell out of the world";
				break;
			case EntityDamageEvent::CAUSE_FALL:
				if($cause instanceof EntityDamageEvent) {
					if($cause->getFinalDamage() > 2){
						$message = $this->getName() . " fell from a high place";
						break;
					}
				}
				$message = $this->getName() . " hit the ground too hard";
				break;

			case EntityDamageEvent::CAUSE_SUFFOCATION:
				$message = $this->getName() . " suffocated in a wall";
				break;

			case EntityDamageEvent::CAUSE_LAVA:
				$message = $this->getName() . " tried to swim in lava";
				break;

			case EntityDamageEvent::CAUSE_FIRE:
				$message = $this->getName() . " went up in flames";
				break;

			case EntityDamageEvent::CAUSE_FIRE_TICK:
				$message = $this->getName() . " burned to death";
				break;

			case EntityDamageEvent::CAUSE_DROWNING:
				$message = $this->getName() . " drowned";
				break;

			case EntityDamageEvent::CAUSE_CONTACT:
				$message = $this->getName() . " was pricked to death";
				break;

			case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
			case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
				$message = $this->getName() . " blew up";
				break;

			case EntityDamageEvent::CAUSE_MAGIC:
				$message = $this->getName() . " was slain by magic";
				break;

			case EntityDamageEvent::CAUSE_CUSTOM:
				break;

			default:
				break;
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerDeathEvent($this, $this->getDrops(), $message));

		if(!$ev->getKeepInventory()){
			foreach($ev->getDrops() as $item){
				$this->level->dropItem($this, $item);
			}

			if($this->inventory !== null){
				$this->inventory->clearAll();
				$this->inventory->setHeldItemIndex(0);
			}
		}

		if($ev->getDeathMessage() != ""){
			$this->server->broadcast($ev->getDeathMessage(), Server::BROADCAST_CHANNEL_USERS);
		}
	}

	public function setHealth($amount){
		parent::setHealth($amount);
		if($this->spawned){
			$pk = new UpdateAttributesPacket();
			$pk->entityId = $this->id;
			$this->foodTickTimer = 0;
			$pk->minValue = 0;
			$pk->maxValue = $this->getMaxHealth();
			$pk->value = $this->getHealth();
			$pk->defaultValue = $pk->maxValue;
			$pk->name = UpdateAttributesPacket::HEALTH;
			$this->dataPacket($pk);
		}
	}

	/** @var int */
	protected $absorption;

	const ABSORPTION_MAX = 340282346638528859811704183484516925440.00;

	public function getAbsorption() {
		return $this->absorption;
	}

	public function setAbsorption($amount) {
		if($this->spawned){
			$this->absorption = $amount;
			$pk = new UpdateAttributesPacket();
			$pk->entityId = $this->getId();
			$pk->minValue = 0.00;
			$pk->maxValue = self::ABSORPTION_MAX;
			$pk->value = $this->absorption;
			$pk->defaultValue = 0.00;
			$pk->name = UpdateAttributesPacket::ABSORPTION;
			$this->dataPacket($pk);
		}
	}

	public function addAbsorption($amount) {
		if($this->absorption + $amount > self::ABSORPTION_MAX) $amount = self::ABSORPTION_MAX;
		if($amount > $this->absorption) {
			$this->setAbsorption($amount);
		}
	}

	public function subtractAbsorption($amount) {
		if($this->absorption - $amount < 0) $amount = 0;
		$this->setAbsorption($this->getAbsorption() - $amount);
	}

	public function attack($damage, EntityDamageEvent $source){
		if($this->dead){
			return;
		}

		if($this->isCreative()
			and $source->getCause() !== EntityDamageEvent::CAUSE_MAGIC
			and $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE
			and $source->getCause() !== EntityDamageEvent::CAUSE_VOID
		){
			$source->setCancelled();
		}

		$absorption = $this->absorption;
		if($absorption > 0){
			$damage = $source->getFinalDamage();
			if($absorption > $damage){
				//Use absorption health before normal health.
				$this->setAbsorption($absorption - $damage);
				$source->setDamage(0);
			}else{
				$this->setAbsorption(0);
				$source->setDamage($damage - $absorption);
			}
		}

		parent::attack($damage, $source);

		if($source->isCancelled()){
			return;
		}elseif($this->getLastDamageCause() === $source and $this->spawned){
			$pk = new EntityEventPacket();
			$pk->eid = $this->id;
			$pk->event = EntityEventPacket::HURT_ANIMATION;
			$this->dataPacket($pk);
		}
	}

	public function sendPosition(Vector3 $pos, $yaw = null, $pitch = null, $mode = MovePlayerPacket::MODE_RESET, array $targets = null) {
		$yaw = $yaw === null ? $this->yaw : $yaw;
		$pitch = $pitch === null ? $this->pitch : $pitch;

		$pk = new MovePlayerPacket();
		$pk->eid = $this->getId();
		$pk->x = $pos->x;
		$pk->y = $pos->y + $this->getEyeHeight();
		$pk->z = $pos->z;
		$pk->bodyYaw = $yaw;
		$pk->pitch = $pitch;
		$pk->yaw = $yaw;
		$pk->mode = $mode;

		if($targets !== null) {
			Server::broadcastPacket($targets, $pk);
		} else {
			$this->dataPacket($pk);
		}
	}

	protected function checkChunks(){
		if($this->chunk === null or ($this->chunk->getX() !== ($this->x >> 4) or $this->chunk->getZ() !== ($this->z >> 4))){
			if($this->chunk !== null){
				$this->chunk->removeEntity($this);
			}
			$this->chunk = $this->level->getChunk($this->x >> 4, $this->z >> 4, true);
			if($this->chunk !== null){
				$this->chunk->addEntity($this);
			}
		}

		if(!$this->justCreated){
			$newChunk = $this->level->getUsingChunk($this->x >> 4, $this->z >> 4);
			unset($newChunk[$this->getId()]);

			/** @var Player[] $reload */
			//$reload = [];
			foreach($this->hasSpawned as $player){
				if(!isset($newChunk[$player->getId()])){
					$this->despawnFrom($player);
				}else{
					unset($newChunk[$player->getId()]);
					//$reload[] = $player;
				}
			}

			foreach($newChunk as $player){
				$this->spawnTo($player);
			}
		}
	}

	public function teleport(Vector3 $pos, $yaw = null, $pitch = null){
		if(!$this->isOnline()){
			return;
		}

		if(parent::teleport($pos, $yaw, $pitch)){
			$this->forceMovement = new Vector3($this->x, $this->y, $this->z);
			$this->sendPosition($this, $this->pitch, $this->yaw, MovePlayerPacket::MODE_RESET);

			$this->resetFallDistance();
			$this->nextChunkOrderRun = 0;
			$this->newPosition = null;
			$this->lastTeleportTime = microtime(true);
			$this->isTeleportedForMoveEvent = true;
		}
	}

	protected function addDefaultWindows() {
		$this->addWindow($this->getInventory(), ContainerIds::TYPE_INVENTORY, true);

		$this->inventoryAdapter->addDefaultWindows(); // let the inventory handler add the correct windows for the client
	}

	/**
	 * Returns the window ID which the inventory has for this player, or -1 if the window is not open to the player.
	 *
	 * @param Inventory $inventory
	 *
	 * @return int
	 */
	public function getWindowId(Inventory $inventory) : int {
		if($this->windows->contains($inventory)) {
			/** @var int $id */
			$id = $this->windows[$inventory];
			return $id;
		}

		return ContainerIds::TYPE_NONE;
	}

	/**
	 * Returns the inventory window open to the player with the specified window ID, or null if no window is open with
	 * that ID.
	 *
	 * @param int $windowId
	 *
	 * @return Inventory|null
	 */
	public function getWindow(int $windowId) {
		return $this->windowIndex[$windowId] ?? null;
	}

	/**
	 * Opens an inventory window to the player. Returns the ID of the created window, or the existing window ID if the
	 * player is already viewing the specified inventory.
	 *
	 * @param Inventory $inventory
	 * @param int|null  $forceId Forces a special ID for the window
	 * @param bool      $isPermanent Prevents the window being removed if true.
	 *
	 * @return int
	 */
	public function addWindow(Inventory $inventory, int $forceId = null, bool $isPermanent = false) : int {
		if(($id = $this->getWindowId($inventory)) !== ContainerIds::TYPE_NONE) {
			return $id;
		}

		if($forceId === null) {
			$this->windowCnt = $cnt = max(ContainerIds::TYPE_FIRST, ++$this->windowCnt % ContainerIds::TYPE_LAST);
		} else {
			$cnt = $forceId;
		}
		$this->windowIndex[$cnt] = $inventory;
		$this->windows->attach($inventory, $cnt);
		if($inventory->open($this)) {
			if($isPermanent) {
				$this->permanentWindows[$cnt] = true;
			}
			return $cnt;
		} else {
			$this->removeWindow($inventory);

			return -1;
		}
	}

	/**
	 * Removes an inventory window from the player.
	 *
	 * @param Inventory $inventory
	 * @param bool      $force Forces removal of permanent windows such as normal inventory, cursor
	 *
	 * @throws \BadMethodCallException if trying to remove a fixed inventory window without the `force` parameter as true
	 */
	public function removeWindow(Inventory $inventory, bool $force = false) {
		if($this->windows->contains($inventory)) {
			/** @var int $id */
			$id = $this->windows[$inventory];
			if(!$force and isset($this->permanentWindows[$id])) {
				throw new \BadMethodCallException("Cannot remove fixed window $id (" . get_class($inventory) . ") from " . $this->getName());
			}
			$this->windows->detach($this->windowIndex[$id]);
			unset($this->windowIndex[$id]);
			unset($this->permanentWindows[$id]);
		}

		$inventory->close($this);
	}

	/**
	 * Removes all inventory windows from the player. By default this WILL NOT remove permanent windows.
	 *
	 * @param bool $removePermanentWindows Whether to remove permanent windows.
	 */
	public function removeAllWindows(bool $removePermanentWindows = false) {
		foreach($this->windowIndex as $id => $window) {
			if(!$removePermanentWindows and isset($this->permanentWindows[$id])) {
				continue;
			}

			$this->removeWindow($window, $removePermanentWindows);
		}
	}

	public function sendAllInventories() {
		foreach($this->windowIndex as $id => $inventory) {
			$inventory->sendContents($this);
			if($inventory instanceof PlayerInventory) {
				$inventory->sendArmorContents($this);
			}
		}
	}

	public function setMetadata(string $metadataKey, MetadataValue $metadataValue) {
		$this->server->getPlayerMetadata()->setMetadata($this, $metadataKey, $metadataValue);
	}

	public function getMetadata(string $metadataKey) {
		return $this->server->getPlayerMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata(string $metadataKey) : bool {
		return $this->server->getPlayerMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata(string $metadataKey, Plugin $plugin) {
		$this->server->getPlayerMetadata()->removeMetadata($this, $metadataKey, $plugin);
	}

	public function setLastMessageFrom($name) {
		$this->lastMessageReceivedFrom = (string)$name;
	}

	public function getLastMessageFrom() {
		return $this->lastMessageReceivedFrom;
	}

	public function setIdentifier($identifier){
		$this->identifier = $identifier;
	}

	public function getIdentifier(){
		return $this->identifier;
	}

	public function getVisibleEyeHeight() {
		return $this->eyeHeight;
	}

	public function kickOnFullServer() {
		return true;
	}

	public function processLogin() {
		$pk = new PlayStatusPacket();
		$pk->status = PlayStatusPacket::LOGIN_SUCCESS;
		$this->dataPacket($pk);

		$pk = new ResourcePacksInfoPacket();
		$this->dataPacket($pk);

		if($this->protocol >= ProtocolInfo::PROTOCOL_120) {
			$this->inventoryAdapter = new PlayerInventoryAdapter120($this); // override default adapter
		}
	}

	public function completeLogin() {
		$valid = true;
		$len = strlen($this->username);
		if ($len > 16 or $len < 3) {
			$valid = false;
		}
		for ($i = 0; $i < $len and $valid; ++$i) {
			$c = ord($this->username{$i});
			if (($c >= ord("a") and $c <= ord("z")) or ( $c >= ord("A") and $c <= ord("Z")) or ( $c >= ord("0") and $c <= ord("9")) or $c === ord("_") or $c === ord(" ")
			) {
				continue;
			}
			$valid = false;
			break;
		}
		if (!$valid or $this->iusername === "rcon" or $this->iusername === "console") {
			$this->close("", "Please choose a valid username.");
			return;
		}

		if (strlen($this->skin) !== 64 * 32 * 4 && strlen($this->skin) !== 64 * 64 * 4) {
			$this->close("", "Invalid skin.", false);
			return;
		}

		if (count($this->server->getOnlinePlayers()) >= $this->server->getMaxPlayers() && $this->kickOnFullServer()) {
			$this->close("", "Server is Full", false);
			return;
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerPreLoginEvent($this, "Plugin reason"));
		if ($ev->isCancelled()) {
			$this->close("", $ev->getKickMessage());
			return;
		}

		if (!$this->server->isWhitelisted(strtolower($this->getName()))) {
			$this->close(TextFormat::YELLOW . $this->username . " has left the game", "Server is private.");
			return;
		} elseif ($this->server->getNameBans()->isBanned(strtolower($this->getName())) or $this->server->getIPBans()->isBanned($this->getAddress())) {
			$this->close(TextFormat::YELLOW . $this->username . " has left the game", "You have been banned.");
			return;
		}

		if ($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)) {
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		}
		if ($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)) {
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
		}

		foreach ($this->server->getOnlinePlayers() as $p) {
			if ($p !== $this and strtolower($p->getName()) === strtolower($this->getName())) {
				if ($p->kick("You connected from somewhere else.") === false) {
					$this->close(TextFormat::YELLOW . $this->getName() . " has left the game", "You connected from somewhere else.");
					return;
				}
			}
		}

		$nbt = $this->server->getOfflinePlayerData($this->username);

		if(!($nbt instanceof Compound)) {
			$this->close(TextFormat::YELLOW . $this->username . " has left the game", "Corrupt joining data, check your connection.");
			return;
		}

		if(!isset($nbt->NameTag)) {
			$nbt->NameTag = new StringTag("NameTag", $this->username);
		} else {
			$nbt["NameTag"] = $this->username;
		}
		$this->gamemode = $nbt["playerGameType"] & 0x03;
		if($this->server->getForceGamemode()) {
			$this->gamemode = $this->server->getGamemode();
			$nbt->playerGameType = new IntTag("playerGameType", $this->gamemode);
		}

		$this->allowFlight = $this->isCreative();

		if(($level = $this->server->getLevelByName($nbt["Level"])) === null) {
			$this->setLevel($this->server->getDefaultLevel(), true);
			$nbt["Level"] = $this->level->getName();
			$nbt["Pos"][0] = $this->level->getSpawnLocation()->x;
			$nbt["Pos"][1] = $this->level->getSpawnLocation()->y + 5;
			$nbt["Pos"][2] = $this->level->getSpawnLocation()->z;
		} else {
			$this->setLevel($level, true);
		}

		$nbt->lastPlayed = new LongTag("lastPlayed", floor(microtime(true) * 1000));
		parent::__construct($this->level->getChunk($nbt["Pos"][0] >> 4, $nbt["Pos"][2] >> 4, true), $nbt);
//		$this->loggedIn = true;
		$this->server->addOnlinePlayer($this);

		if($this->isCreative()) {
			$this->inventory->setHeldItemIndex(0);
		} else {
			if(isset($this->namedtag->SelectedInventorySlot)) {
				$this->inventory->setHeldItemIndex($this->inventory->getHotbarSlotIndex($nbt["SelectedInventorySlot"]));
			} else {
				$this->inventory->setHeldItemIndex($this->inventory->getHotbarSlotIndex(0));
			}
		}

		if($this->spawnPosition === null and isset($this->namedtag->SpawnLevel) and ( $level = $this->server->getLevelByName($this->namedtag["SpawnLevel"])) instanceof Level) {
			$this->spawnPosition = new Position($this->namedtag["SpawnX"], $this->namedtag["SpawnY"], $this->namedtag["SpawnZ"], $level);
		}

		$spawnPosition = $this->getSpawn();

		$compassPosition = $this->server->getGlobalCompassPosition();

		$pk = new StartGamePacket();
		$pk->seed = -1;
		$pk->dimension = 0;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
//		$pk->spawnX = (int) $spawnPosition->x;
//		$pk->spawnY = (int) $spawnPosition->y;
//		$pk->spawnZ = (int) $spawnPosition->z;
		/* hack for compass */
		$pk->spawnX = $compassPosition['x'];
		$pk->spawnY = $compassPosition['y'];
		$pk->spawnZ = $compassPosition['z'];
		$pk->generator = 1; //0 old, 1 infinite, 2 flat
		$pk->gamemode = $this->gamemode & 0x01;
		$pk->eid = $this->id;
		$pk->currentTick = $this->server->getTick();
		$pk->dayCycleStopTime = $this->server->getDoTimeTimeCycle() ? -1 : $this->server->getTimeCycleStopTime();
		$this->dataPacket($pk);

		$pk = new SetTimePacket();
		$pk->time = $this->level->getTime();
		$pk->started = true;
		$this->dataPacket($pk);

		$pk = new SetSpawnPositionPacket();
		$pk->x = (int) $spawnPosition->x;
		$pk->y = (int) $spawnPosition->y;
		$pk->z = (int) $spawnPosition->z;
		$this->dataPacket($pk);

		$this->sendCommandData();

		$pk = new SetDifficultyPacket();
		$pk->difficulty = $this->server->getDifficulty();
		$this->dataPacket($pk);

		$this->server->getLogger()->info(TextFormat::AQUA . $this->username . TextFormat::WHITE . "/" . TextFormat::AQUA . $this->ip . " connected");

		if($this->isCreative()) {
			$slots = [];
			foreach(Item::getCreativeItems() as $item) {
				$slots[] = clone $item;
			}

			$this->inventoryAdapter->sendInventoryContents(Protocol120::CONTAINER_ID_CREATIVE, $slots);
		}

		$this->server->sendRecipeList($this);

		$this->updateSpeed(self::DEFAULT_SPEED);

		if($this->getHealth() <= 0) {
			$this->dead = true;
		}
	}

	public function getInterface() {
		return $this->interface;
	}

	public function transfer($address, $port = false) {
		$pk = new TransferPacket();
		$pk->ip = $address;
		$pk->port = ($port === false ? 19132 : $port);
		$this->dataPacket($pk);
	}

	protected function updateAttribute($name, $value, $minValue, $maxValue, $defaultValue) {
		$pk = new UpdateAttributesPacket();
		$pk->entityId = $this->id;
		$pk->name = $name;
		$pk->value = $value;
		$pk->minValue = $minValue;
		$pk->maxValue = $maxValue;
		$pk->defaultValue = $defaultValue;
		$this->dataPacket($pk);
	}

	public function updateSpeed($value) {
		$this->movementSpeed = $value;
		$this->updateAttribute(UpdateAttributesPacket::SPEED, $this->movementSpeed, 0, self::MAXIMUM_SPEED, $this->movementSpeed);
	}

	public function getMovementSpeed() : float {
		return $this->movementSpeed;
	}

	public function setSprinting($value = true, $setDefault = false) {
		if(!$setDefault && $this->isSprinting() == $value) {
			return;
		}
		parent::setSprinting($value);
		if ($setDefault) {
			$this->movementSpeed = self::DEFAULT_SPEED;
		} else {
			$sprintSpeedChange = self::DEFAULT_SPEED * 0.3;
			if ($value === false) {
				$sprintSpeedChange *= -1;
			}
			$this->movementSpeed += $sprintSpeedChange;
		}
		$this->updateSpeed($this->movementSpeed);
	}

	public function checkVersion() {
		if(!$this->loggedIn) {
			$this->close("", TextFormat::RED . "Please switch to Minecraft: PE " . TextFormat::GREEN . $this->getServer()->getVersion() . TextFormat::RED . " to join.");
		} else {
			var_dump('zlib_decode error');
		}
	}

	public function getProtectionEnchantments() {
		$result = [];
		foreach($this->getInventory()->getArmorContents() as $item) {
			if($item->getId() === Item::AIR) {
				continue;
			}
			$enchantments = $item->getEnchantments();
			foreach($enchantments as $enchantment) {
				if(in_array($enchantment->getId(), [Enchantment::TYPE_ARMOR_PROTECTION, Enchantment::TYPE_ARMOR_FIRE_PROTECTION, Enchantment::TYPE_ARMOR_EXPLOSION_PROTECTION, Enchantment::TYPE_ARMOR_FALL_PROTECTION, Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION])) {
					$result[] = $enchantment;
				}
			}
		}
		return $result;
	}


	public function getExperience() {
		return $this->exp;
	}

	public function getExperienceLevel() {
		return $this->expLevel;
	}

	public function updateExperience($exp = 0, $level = 0, $checkNextLevel = true) {
		$this->exp = $exp;
		$this->expLevel = $level;

		$this->updateAttribute(UpdateAttributesPacket::EXPERIENCE, $exp, 0, self::MAX_EXPERIENCE, 100);
		$this->updateAttribute(UpdateAttributesPacket::EXPERIENCE_LEVEL, $level, 0, self::MAX_EXPERIENCE_LEVEL, 100);

		if($this->hasEnoughExperience() && $checkNextLevel){
			$exp = 0; // TODO - Calculate the amount of XP for the next level
			$level = $this->getExperienceLevel() + 1;
			$this->updateExperience($exp, $level, false);
		}
	}

	public function addExperience($exp = 0, $level = 0, $checkNextLevel = true) {
		$this->updateExperience($this->getExperience() + $exp, $this->getExperienceLevel() + $level, $checkNextLevel);
	}

	public function removeExperience($exp = 0, $level = 0, $checkNextLevel = true) {
		$this->updateExperience($this->getExperience() - $exp, $this->getExperienceLevel() - $level, $checkNextLevel);
	}

	// http://minecraft.gamepedia.com/Experience
	public function getExperienceNeeded() {
		$level = $this->getExperienceLevel();
		if ($level <= 16) {
			return (2 * $level) + 7;
		} elseif ($level <= 31) {
			return (5 * $level) - 38;
		} elseif ($level <= 21863) {
			return (9 * $level) - 158;
		}
		return PHP_INT_MAX;
	}

	public function hasEnoughExperience() {
		return $this->getExperienceNeeded() - $this->getRealExperience() <= 0;
	}

	public function getRealExperience(){
		return $this->getExperienceNeeded() * $this->getExperience();
	}

	public function getPlayerProtocol() {
		return $this->protocol;
	}

	public function getDeviceOS() {
		return $this->deviceType;
	}

	public function getInventoryType() {
		return $this->inventoryType;
	}

	public function setPing($ping) {
		$this->ping = $ping;
	}

	public function getPing() {
		return $this->ping;
	}

	public function sendPing() {
		if ($this->ping <= 150) {
			$color = TextFormat::GREEN;
			$text = "Good";
		} elseif ($this->ping <= 250) {
			$color = TextFormat::YELLOW;
			$text = "Okay";
		} else {
			$color = TextFormat::RED;
			$text = "Bad";
		}
		$this->sendMessage($color . "Connection: {$text}({$this->ping}ms)");
	}

	public function getXUID() {
		return $this->xuid;
	}

	public function setTitle($text, $subtext = '', $time = 36000) {
		if ($this->protocol >= Info::PROTOCOL_105) {
			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TITLE_TYPE_TIMES;
			$pk->text = "";
			$pk->fadeInTime = 5;
			$pk->fadeOutTime = 5;
			$pk->stayTime = 20 * $time;
			$this->dataPacket($pk);

			if (!empty($subtext)) {
				$pk = new SetTitlePacket();
				$pk->type = SetTitlePacket::TITLE_TYPE_SUBTITLE;
				$pk->text = $subtext;
				$this->dataPacket($pk);
			}

			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TITLE_TYPE_TITLE;
			$pk->text = $text;
			$this->dataPacket($pk);
		}
	}

	public function clearTitle() {
		if ($this->protocol >= Info::PROTOCOL_105) {
			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TITLE_TYPE_CLEAR;
			$pk->text = "";
			$this->dataPacket($pk);
		}
	}

	public function sendNoteSound(int $noteId, $queue = false) {
		if($queue) {
			$this->noteSoundQueue[] = $noteId;
			return;
		}
		$pk = new LevelSoundEventPacket();
		$pk->sound = LevelSoundEventPacket::SOUND_NOTE;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->pitch = $noteId;
		$this->directDataPacket($pk);
	}

	public function canSeeEntity(Entity $entity){
		return !isset($this->hiddenEntity[$entity->getId()]);
	}

	public function hideEntity(Entity $entity){
		if($entity instanceof Player){
			return;
		}
		$this->hiddenEntity[$entity->getId()] = $entity;
		$entity->despawnFrom($this);
	}

	public function showEntity(Entity $entity){
		if($entity instanceof Player){
			return;
		}
		unset($this->hiddenEntity[$entity->getId()]);
		if($entity !== $this && !$entity->closed && !$entity->dead){
			$entity->spawnTo($this);
		}
	}

	public function setOnFire($seconds, $damage = 1){
 		if($this->isSpectator()) {
 			return;
 		}
 		parent::setOnFire($seconds, $damage);
 	}

	public function attackEntity(Entity $target) {
		if($this->spawned === false or $this->isSpectator() or !$this->isAlive()) {
			return;
		}

		if($target instanceof DroppedItem or $target instanceof Arrow or $target instanceof Snowball or $target instanceof Egg) {
			$this->kick("Attempting to attack an invalid entity");
			$this->server->getLogger()->warning("Player " . $this->getName() . " tried to attack an invalid entity");
			return;
		}

		$heldItem = $this->inventory->getItemInHand();

		$baseDamage = $heldItem->getAttackPoints();

		if($target instanceof Player) {
			if(($target->getGamemode() & 0x01) > 0) {
				return;
			}

			$points = 0;
			$toughness = 0;
			foreach($target->getInventory()->getArmorContents() as $armorItem){
				if($armorItem instanceof Armor) {
					$points += $armorItem->getDefensePoints();
					$toughness += $armorItem->getToughnessPoints();
				}
			}

			$armorReduction = -($baseDamage - ($baseDamage * (1 - min(20, max($points / 5, $points - $baseDamage /(2 + $toughness / 4))) / 25)));
		}

		$ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, [
			EntityDamageEvent::MODIFIER_BASE => $baseDamage,
			EntityDamageEvent::MODIFIER_ARMOR => ($armorReduction ?? 0)
		]);
		$target->attack($ev->getFinalDamage(), $ev);

		if($ev->isCancelled()) {
			if($heldItem instanceof Tool and $this->isSurvival()) {
				$this->inventory->sendContents($this);
			}
			return;
		}

		if($target instanceof Living and $this->isSprinting() and microtime(true) - $this->lastJumpTime < 1.5) {
			$target->setMotion($target->getMotion()->add(0, 0.1, 0));
			$this->motionX *= 0.6;
			$this->motionZ *= 0.6;
		}

		if($this->isSurvival() and $heldItem->onEntityAttack($this, $target) and $heldItem->getDamage() >= $heldItem->getMaxDurability()) {
			$this->inventory->setItemInHand(ItemFactory::get(Item::AIR, 0, 1));
		} else {
			$this->inventory->setItemInHand($heldItem);
		}
	}

	/**
	 * Returns whether the player can interact with the specified position. This checks distance and direction.
	 *
	 * @param Vector3 $pos
	 * @param         $maxDistance
	 * @param float   $maxDiff
	 *
	 * @return bool
	 */
	public function canInteract(Vector3 $pos, $maxDistance, float $maxDiff = 0.5) : bool{
		$eyePos = $this->getPosition()->add(0, $this->getEyeHeight(), 0);
		if($eyePos->distanceSquared($pos) > $maxDistance ** 2){
			return false;
		}

		$dV = $this->getDirectionPlane();
		$dot = $dV->dot(new Vector2($eyePos->x, $eyePos->z));
		$dot1 = $dV->dot(new Vector2($pos->x, $pos->z));
		return ($dot1 - $dot) >= -$maxDiff;
	}

	protected function initHumanData(){
		$this->setNameTag($this->username);
	}

	protected function initEntity() {
		parent::initEntity();
	}

	/**
	 * @param Vector3 $blockPosition
	 */
	public function breakBlock(Vector3 $blockPosition) {
		if($this->spawned === false or !$this->isAlive()){
			return;
		}

		$item = $this->inventory->getItemInHand();

		$oldItem = clone $item;

		if($this->canInteract($blockPosition->add(0.5, 0.5, 0.5), $this->isCreative() ? 13 : 6) and $this->level->useBreakOn($blockPosition, $item, $this, true)){
			if(!$item->equalsExact($oldItem)){
				$this->inventory->setItemInHand($item);
				$this->inventory->sendHeldItem($this->hasSpawned);
			}
			//Timings::$timerRemoveBlockPacket->stopTiming();
			return;
		}

		$this->inventory->sendContents($this);
		$target = $this->level->getBlock($blockPosition);
		$tile = $this->level->getTile($blockPosition);

		$this->level->sendBlocks([$this], [$target], UpdateBlockPacket::FLAG_ALL_PRIORITY);

		$this->inventory->sendHeldItem($this);

		if($tile instanceof Spawnable){
			$tile->spawnTo($this);
		}
	}

	/**
	 * @param int $soundId
	 * @param float[] $position
	 * @param int $pitch
	 * @param int $extraData
	 */
	public function sendSound(int $soundId, array $position, int $pitch = 1, $extraData = -1) {
		$pk = new LevelSoundEventPacket();
		$pk->sound = $soundId;
		$pk->x = $position["x"];
		$pk->y = $position["y"];
		$pk->z = $position["z"];
		$pk->extraData = $extraData;
		$pk->pitch = $pitch;
		$this->dataPacket($pk);
	}

	private function setMayMove(bool $state) {
		if($this->protocol >= ProtocolInfo::PROTOCOL_120) {
			$this->setImmobile(!$state);
			$this->isMayMove = $state;
		} else {
			$this->isMayMove = true;
		}
	}

	public function customInteract($packet) {

	}

	public function fall($fallDistance) {
		if (!$this->allowFlight) {
			parent::fall($fallDistance);
		}
	}

	protected function onJump() {
		$this->lastJumpTime = microtime(true);
 	}

	public function releaseUseItem() {
		if($this->isUsingItem()) {
			$item = $this->inventory->getItemInHand();
			if($item->onReleaseUsing($this)) {
				$this->inventory->setItemInHand($item);
			}
		} else {
			$this->inventory->sendContents($this);
		}

		$this->setUsingItem(false);
	}

	public function getServerAddress() {
		return $this->serverAddress;
	}

	public function getClientlanguageCode() {
		return $this->languageCode;
	}

	public function getClientVersion() {
		return $this->clientVersion;
	}

	public function getOriginalProtocol() {
		return $this->originalProtocol;
	}

	/**
	 *
	 * @param CustomUI $modalWindow
	 * @return boolean
	 */
	public function showModal($modalWindow) {

		if ($this->protocol >= Info::PROTOCOL_120) {
			$pk = new ShowModalFormPacket();
			$pk->formId = $this->lastModalId++;
			$pk->data = $modalWindow->toJSON();
			$this->dataPacket($pk);
			$this->activeModalWindows[$pk->formId] = $modalWindow;
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param integer $formId
	 * @param string|null $data Sting in JSON format or null
	 */
	public function checkModal($formId, $data) {
		if (isset($this->activeModalWindows[$formId])) {
			if ($data === null) { // The modal window was closed manually
				$this->activeModalWindows[$formId]->close($this);
			} else { // Player send some data
				$this->activeModalWindows[$formId]->handle($data, $this);
			}
			unset($this->activeModalWindows[$formId]);
		}

	}

	protected function sendServerSettingsModal($modalWindow) {
		if ($this->protocol >= Info::PROTOCOL_120) {
			$pk = new ServerSettingsResponsetPacket();
			$pk->formId = $this->lastModalId++;
			$pk->data = $modalWindow->toJSON();
			$this->dataPacket($pk);
			$this->activeModalWindows[$pk->formId] = $modalWindow;
		}
	}

	protected function sendServerSettings() {
	}

	public function updatePlayerSkin($oldSkinName, $newSkinName) {
		$pk = new RemoveEntityPacket();
		$pk->eid = $this->getId();

		$pk2 = new PlayerListPacket();
		$pk2->type = PlayerListPacket::TYPE_REMOVE;
		$pk2->entries[] = [$this->getUniqueId()];

		$pk3 = new PlayerListPacket();
		$pk3->type = PlayerListPacket::TYPE_ADD;
		$pk3->entries[] = [$this->getUniqueId(), $this->getId(), $this->getName(), $this->skinName, $this->skin, $this->capeData, $this->skinGeometryName, $this->skinGeometryData, $this->getXUID()];

		$pk4 = new AddPlayerPacket();
		$pk4->uuid = $this->getUniqueId();
		$pk4->username = $this->getName();
		$pk4->eid = $this->getId();
		$pk4->x = $this->x;
		$pk4->y = $this->y;
		$pk4->z = $this->z;
		$pk4->speedX = $this->motionX;
		$pk4->speedY = $this->motionY;
		$pk4->speedZ = $this->motionZ;
		$pk4->yaw = $this->yaw;
		$pk4->pitch = $this->pitch;
		$pk4->metadata = $this->dataProperties;

		$pk120 = new PlayerSkinPacket();
		$pk120->uuid = $this->getUniqueId();
		$pk120->newSkinId = $this->skinName;
		$pk120->newSkinName = $newSkinName;
		$pk120->oldSkinName = $oldSkinName;
		$pk120->newSkinByteData = $this->skin;
		$pk120->newCapeByteData = $this->capeData;
		$pk120->newSkinGeometryName = $this->skinGeometryName;
		$pk120->newSkinGeometryData = $this->skinGeometryData;

		$viewers120 = [];
		$oldViewers = [];
		$recipients = $this->getViewers();
		$recipients[] = $this;

		foreach($recipients as $viewer) {
			if($viewer->getPlayerProtocol() >= ProtocolInfo::PROTOCOL_120) {
				$viewers120[] = $viewer;
			} else {
				$oldViewers[] = $viewer;
			}
		}

		if(!empty($viewers120)) {
			$this->server->batchPackets($viewers120, [$pk120]);
		}

		if(!empty($oldViewers)) {
			$this->server->batchPackets($oldViewers, [$pk, $pk2, $pk3, $pk4]);
		}
	}

	/**
	 * @return integer
	 */
	public function getSubClientId() {
		return $this->subClientId;
	}

	/**
	 *
	 * @return Player|null
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 *
	 * @param integer $subClientId
	 */
	public function removeSubClient($subClientId) {
		if (isset($this->subClients[$subClientId])) {
			unset($this->subClients[$subClientId]);
		}
	}

	/**
	 * @minprotocol 120
	 *
	 * @param SubClientLoginPacket $packet
	 * @param Player $parent
	 * @return bool
	 */
	public function subAuth($packet, $parent) {
		$this->username = TextFormat::clean($packet->username);
		$this->xblName = $this->username;
		$this->displayName = $this->username;
		$this->setNameTag($this->username);
		$this->iusername = strtolower($this->username);

		$this->randomClientId = $packet->clientId;
		$this->loginData = ["clientId" => $packet->clientId, "loginData" => null];
		$this->uuid = $packet->clientUUID;
		if (is_null($this->uuid)) {
			$this->close("", "Sorry, your client is broken.");
			return false;
		}

		$this->parent = $parent;
		$this->xuid = $packet->xuid;
		$this->rawUUID = $this->uuid->toBinary();
		$this->clientSecret = $packet->clientSecret;
		$this->protocol = $parent->getPlayerProtocol();
		$this->setSkin($packet->skin, $packet->skinName, $packet->skinGeometryName, $packet->skinGeometryData, $packet->capeData);
		$this->subClientId = $packet->targetSubClientID;

		// some statistics information
		$this->deviceType = $parent->getDeviceOS();
		$this->inventoryType = $parent->getInventoryType();
		$this->languageCode = $parent->languageCode;
		$this->serverAddress = $parent->serverAddress;
		$this->clientVersion = $parent->clientVersion;
		$this->originalProtocol = $parent->originalProtocol;

		$this->identityPublicKey = $packet->identityPublicKey;

		$pk = new PlayStatusPacket();
		$pk->status = PlayStatusPacket::LOGIN_SUCCESS;
		$this->dataPacket($pk);

		$this->loggedIn = true;
		$this->completeLogin();

		return $this->loggedIn;
	}

}
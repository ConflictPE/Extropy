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

namespace pocketmine\network\multiversion\inventory;

use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\BigCraftingGrid;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\CraftingGrid;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\Recipe;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\CraftingTransaction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\types\ContainerIds;
use pocketmine\network\protocol\types\NetworkInventoryAction;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\v120\InventoryContentPacket;
use pocketmine\network\protocol\v120\InventorySlotPacket;
use pocketmine\network\protocol\v120\InventoryTransactionPacket;
use pocketmine\Player;

/**
 * Class to assist with MCPE 1.2 inventory transactions
 */
class PlayerInventoryAdapter120 implements InventoryAdapter {

	/** @var Player */
	private $player;

	/** @var PlayerCursorInventory */
	protected $cursorInventory;

	/** @var CraftingGrid */
	protected $craftingGrid = null;

	/** @var CraftingTransaction|null */
	protected $craftingTransaction = null;

	public function __construct(Player $player) {
		$this->player = $player;
	}

	public function addDefaultWindows() {
		$player = $this->getPlayer();

		$this->cursorInventory = new PlayerCursorInventory($player);
		$player->addWindow($this->cursorInventory, ContainerIds::TYPE_CURSOR, true);

		$this->craftingGrid = new CraftingGrid($player);
	}

	public function getPlayer() : Player {
		return $this->player;
	}

	public function getCursorInventory() : PlayerCursorInventory {
		return $this->cursorInventory;
	}

	public function getCraftingGrid() : CraftingGrid {
		return $this->craftingGrid;
	}

	/**
	 * @param CraftingGrid $grid
	 */
	public function setCraftingGrid(CraftingGrid $grid) {
		$this->craftingGrid = $grid;
	}

	public function resetCraftingGridType() {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		$contents = $this->craftingGrid->getContents();
		if(count($contents) > 0){
			$drops = $inventory->addItem(...$contents);
			foreach($drops as $drop){
				$player->dropItem($drop);
			}

			$this->craftingGrid->clearAll();
		}

		if($this->craftingGrid instanceof BigCraftingGrid){
			$this->craftingGrid = new CraftingGrid($player);
			$player->craftingType = 0;
		}
	}

	public function doTick(int $currentTick) {

	}

	/**
	 * Handle an incoming mob equipment update
	 *
	 * @param int $slot
	 * @param Item $item
	 * @param int $inventorySlot
	 */
	public function handleMobEquipment(int $slot, Item $item, int $inventorySlot) {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		$inventorySlot -= 9; // get the real inventory slot

		$handItem = $inventory->getItem($inventorySlot);

		if(!$handItem->equals($item)){
			$player->getServer()->getLogger()->debug("Tried to equip " . $item . " but have " . $handItem . " in target slot");
			$inventory->sendContents($player);
			return;
		}

		$inventory->equipItem($slot);

		$player->setUsingItem(false);
	}

	//public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool {
	//
	//}

	/**
	 * Handle an incoming use item request
	 *
	 * @param Item $item
	 * @param int $slot
	 * @param int $face
	 * @param Vector3 $blockPosition
	 * @param Vector3 $clickPosition
	 */
	public function handleUseItem(Item $item, int $slot, int $face, Vector3 $blockPosition, Vector3 $clickPosition) {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();
		$handItem = $inventory->getItemInHand();

		switch($face) {
			// Use item, block place
			case 0:
			case 1:
			case 2:
			case 3:
			case 4:
			case 5:
				$player->setUsingItem(false);

				if(!$player->canInteract($blockPosition->add(0.5, 0.5, 0.5), 13) or $player->isSpectator()) {

				} elseif($player->isCreative()) {
					if($player->getLevel()->useItemOn($blockPosition, $item, $face, $clickPosition, $this, true) === true){
						return;
					}
				} elseif(!$handItem->equals($item)) {
					$inventory->sendHeldItem($player);
				} else {
					$oldItem = clone $handItem;
					if($player->getLevel()->useItemOn($blockPosition, $item, $face, $clickPosition, $player, true)){
						if(!$item->equalsExact($oldItem)){
							$inventory->setItemInHand($item);
							$inventory->sendHeldItem($player->getViewers());
						}

						return;
					}
				}

				$inventory->sendHeldItem($player);

				if($blockPosition->distanceSquared($player) > 10000){
					return;
				}

				$target = $player->getLevel()->getBlock($blockPosition);
				$block = $target->getSide($face);

				$player->getLevel()->sendBlocks([$player], [$target, $block], UpdateBlockPacket::FLAG_ALL_PRIORITY);

			break;

			case 0xff:
			case -1:  // -1 for 0.16
				$directionVector = $player->getDirectionVector();

				if($player->isCreative()) {
					$item = $inventory->getItemInHand();
				} elseif(!$handItem->equals($item)) {
					$inventory->sendHeldItem($player);
					return;
				} else {
					$item = $inventory->getItemInHand();
				}

				$player->getServer()->getPluginManager()->callEvent($ev = new PlayerInteractEvent($player, $item, $directionVector, $face, PlayerInteractEvent::RIGHT_CLICK_AIR));
				if($ev->isCancelled()) {
					$inventory->sendHeldItem($player);
					return;
				}

				if($item->onClickAir($player, $directionVector) and $player->isSurvival()){
					$inventory->setItemInHand($item);
				}

				if($item->getId() === Item::BOW and !$inventory->contains(ItemFactory::get(Item::ARROW, 0, 1))) {
					$player->setUsingItem(false); // attempting to draw a bow with no arrows
				} else {
					$player->setUsingItem(true);
				}

			break;
		}
	}

	/**
	 * Handle an incoming drop item request
	 *
	 * @param Item $item
	 */
	public function handleDropItem(Item $item) {
		// item drops are handled via transactions
	}

	/**
	 * Handle an incoming container close request
	 *
	 * @param int $windowId
	 */
	public function handleContainerClose(int $windowId) {
		$player = $this->getPlayer();

		$this->resetCraftingGridType();

		if(($inv = $player->getWindow($windowId)) instanceof Inventory) {
			$player->getServer()->getPluginManager()->callEvent(new InventoryCloseEvent($inv, $player));
			$player->removeWindow($inv);
		}
	}

	public function handleContainerSetSlot(int $slot, int $windowId, Item $item, int $hotbarSlot) {
		// slot setting is handled by transactions
	}

	public function handleCraftingEvent(Recipe $recipe, array $input, array $output) {
		// crafting is handled by transactions
	}

	/**
	 * Handle an incoming inventory action
	 *
	 * @param NetworkInventoryAction[] $actions
	 * @param bool $isCraftingPart
	 * @param int $type
	 * @param \stdClass $data
	 */
	public function handleInventoryTransaction(array $actions, bool $isCraftingPart, int $type, \stdClass $data) {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if($player->isSpectator()) {
			$player->sendAllInventories();
			return;
		}

		$invActions = [];
		foreach($actions as $networkInventoryAction) {
			$action = $networkInventoryAction->createInventoryAction($player);

			if($action === null) {
				$player->getServer()->getLogger()->debug("Unmatched inventory action from " . $player->getName() . ": " . json_encode($networkInventoryAction));
				$player->sendAllInventories();
				return;
			}

			$invActions[] = $action;
		}

		if($isCraftingPart) {
			if($this->craftingTransaction === null) {
				$this->craftingTransaction = new CraftingTransaction($player, $invActions);
			} else {
				foreach($invActions as $action) {
					$this->craftingTransaction->addAction($action);
				}
			}

			if($this->craftingTransaction->getPrimaryOutput() !== null) {
				// we get the actions for this in several packets, so we can't execute it until we get the result

				$this->craftingTransaction->execute();
				$this->craftingTransaction = null;
			}

			return;
		} elseif($this->craftingTransaction !== null) {
			$player->getServer()->getLogger()->debug("Got unexpected normal inventory action with incomplete crafting transaction from " . $player->getName() . ", refusing to execute crafting");
			$this->craftingTransaction = null;
		}

		switch($type) {
			case InventoryTransactionPacket::TYPE_NORMAL:
				$transaction = new InventoryTransaction($player, $invActions);

				if(!$transaction->execute()) {
					$player->getServer()->getLogger()->debug("Failed to execute inventory transaction from " . $player->getName() . " with actions: " /*. json_encode($invActions)*/);
					/** @var InventoryAction $ac */
					foreach($invActions as $ac) {
						echo (new \ReflectionObject($ac))->getShortName() . ": " . json_encode($ac) . PHP_EOL;
					}
					$player->sendAllInventories();
				}

				return;
			case InventoryTransactionPacket::TYPE_MISMATCH:
				if(count($invActions) > 0) {
					$player->getServer()->getLogger()->debug("Expected 0 actions for mismatch, got " . count($invActions) . ", " . json_encode($invActions));
				}
				$player->sendAllInventories();
				return;
			case InventoryTransactionPacket::TYPE_USE_ITEM:
				$blockVector = new Vector3($data->x, $data->y, $data->z);
				$face = $data->face;

				switch($type = $data->actionType) {
					case InventoryTransactionPacket::USE_ITEM_ACTION_CLICK_BLOCK:
						case InventoryTransactionPacket::USE_ITEM_ACTION_CLICK_AIR:
						$this->handleUseItem($data->itemInHand, $data->hotbarSlot, $face, $blockVector, $player->getDirectionVector());
						break;
					case InventoryTransactionPacket::USE_ITEM_ACTION_BREAK_BLOCK:
						$player->breakBlock($blockVector);
						break;
					default:
						$player->getServer()->getLogger()->debug("Failed to execute use item transaction from " . $player->getName() . " with data: " . json_encode($data));
						break;
				}
				return;
			case InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY:
				$target = $player->getLevel()->getEntity($data->entityRuntimeId);
				if($target === null) {
					return;
				}

				switch($type = $data->actionType) {
					case InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT:
						break; //TODO
					case InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK:
						$player->attackEntity($target);
						break;
					default:
						$player->getServer()->getLogger()->debug("Failed to execute use item on entity transaction from " . $player->getName() . " with data: " . json_encode($data));
						break;
				}
				return;
				case InventoryTransactionPacket::TYPE_RELEASE_ITEM:
					try {
						switch($type = $data->actionType) {
							case InventoryTransactionPacket::RELEASE_ITEM_ACTION_RELEASE:
								$player->releaseUseItem();
								break;
							case InventoryTransactionPacket::RELEASE_ITEM_ACTION_CONSUME:
								$player->eatFoodInHand();
								break;
							default:
								$player->getServer()->getLogger()->debug("Failed to execute release item transaction from " . $player->getName() . " with data: " . json_encode($data));
								break;
						}
					} finally {
						$player->setUsingItem(false);
					}
					return;
			default:
				$inventory->sendContents($player);
				break;
		}
	}

	/**
	 * Send a packet to open a container inventory
	 *
	 * @param ContainerInventory $inventory
	 */
	public function sendContainerOpen(ContainerInventory $inventory) {
		$player = $this->getPlayer();

		$pk = new ContainerOpenPacket();
		$pk->windowId = $player->getWindowId($inventory);
		$pk->type = $inventory->getType()->getNetworkType();
		$pk->slots = $inventory->getSize();
		$pk->entityId = $player->getId();
		$holder = $inventory->getHolder();
		if($holder instanceof Vector3){
			$pk->x = $holder->getX();
			$pk->y = $holder->getY();
			$pk->z = $holder->getZ();
		}else{
			$pk->x = $pk->y = $pk->z = 0;
		}

		$player->dataPacket($pk);
		$inventory->sendContents($player);
	}

	/**
	 * Send a packet to set an inventory's contents
	 *
	 * @param int $windowId
	 * @param Item[] $items
	 * @param Item[] $hotbarItems
	 */
	public function sendInventoryContents(int $windowId, array $items, array $hotbarItems = []) {
		$pk = new InventoryContentPacket();
		$pk->items = $items;
		$pk->windowId = $windowId;

		$this->getPlayer()->dataPacket($pk);
	}

	/**
	 * Send a packet to set a slot in an inventory
	 *
	 * @param int $windowId
	 * @param Item $item
	 * @param int $slot
	 */
	public function sendInventorySlot(int $windowId, Item $item, int $slot) {
		$pk = new InventorySlotPacket();
		$pk->inventorySlot = $slot;
		$pk->item = $item;
		$pk->windowId = $windowId;

		$this->getPlayer()->dataPacket($pk);
	}

	/**
	 * Send a packet to close a container inventory
	 *
	 * @param ContainerInventory $inventory
	 */
	public function sendContainerClose(ContainerInventory $inventory) {
		$player = $this->getPlayer();

		$pk = new ContainerClosePacket();
		$pk->windowId = $player->getWindowId($inventory);

		$player->dataPacket($pk);
	}

}
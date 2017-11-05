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

use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\BigShapedRecipe;
use pocketmine\inventory\BigShapelessRecipe;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\FloatingInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\Recipe;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\inventory\transaction\BaseTransaction;
use pocketmine\inventory\transaction\SimpleTransactionQueue;
use pocketmine\inventory\transaction\type\DropItemTransaction;
use pocketmine\inventory\transaction\type\EquipItemTransaction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\types\ContainerIds;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\Player;

/**
 * Class to assist with inventory transactions
 */
class PlayerInventoryAdapter implements InventoryAdapter {

	/** @var Player */
	private $player;

	/** @var FloatingInventory */
	protected $floatingInventory;

	/** @var SimpleTransactionQueue */
	protected $transactionQueue = null;

	public function __construct(Player $player) {
		$this->player = $player;
	}

	public function addDefaultWindows() {
		// Virtual inventory for desktop GUI crafting and anti-cheat transaction processing
		$this->floatingInventory = new FloatingInventory($this->player);
	}

	public function getPlayer() : Player {
		return $this->player;
	}

	public function getFloatingInventory() {
		return $this->floatingInventory;
	}

	public function getTransactionQueue() {
		// Is creating the transaction queue on demand a good idea? I think only if it's destroyed afterwards. hmm...
		if($this->transactionQueue === null){
			//Potential for crashes here if a plugin attempts to use this, say for an NPC plugin or something...
			$this->transactionQueue = new SimpleTransactionQueue($this->getPlayer());
		}
		return $this->transactionQueue;
	}

	public function doTick(int $currentTick) {
		if($this->getTransactionQueue() !== null) {
			$this->getTransactionQueue()->execute();
		}
	}

	/**
	 * Handle an incoming mob equipment update
	 *
	 * @param int $hotbarSlot
	 * @param Item $item
	 * @param int $inventorySlot
	 */
	public function handleMobEquipment(int $hotbarSlot, Item $item, int $inventorySlot) {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if($hotbarSlot < 0 or $hotbarSlot >= $inventory->getHotbarSize() or $inventorySlot < -1 or $inventorySlot >= $inventory->getSize()) {
			$inventory->sendContents($player);
			return;
		}

		$this->getTransactionQueue()->addTransaction(new EquipItemTransaction($inv = $player->getInventory(), $inventorySlot - 9, $hotbarSlot, $player->getInventory()->getItem($inventorySlot - 9), $item));
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
					if($player->getLevel()->useItemOn($blockPosition, $item, $face, $clickPosition, $player, true) === true){
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
		$player = $this->getPlayer();
		if($player->spawned === false or !$player->isAlive()) { // w10 drops the contents of the crafting grid when the inventory closes - including air.
			return;
		}

		if($item->getId() === Item::AIR) {
			return;
		}

		$this->getTransactionQueue()->addTransaction(new DropItemTransaction($item));
	}

	/**
	 * Handle an incoming container close request
	 *
	 * @param int $windowId
	 */
	public function handleContainerClose(int $windowId) {
		$player = $this->getPlayer();

		$player->craftingType = 0;

		if(($inv = $player->getWindow($windowId)) instanceof Inventory) {
			$player->getServer()->getPluginManager()->callEvent(new InventoryCloseEvent($inv, $player));
			$player->removeWindow($inv);
		}

		/**
		 * Drop anything still left in the crafting inventory
		 * This will usually never be needed since Windows 10 clients will send DropItemPackets
		 * which will cause this to happen anyway, but this is here for when transactions
		 * fail and items end up stuck in the crafting inventory.
		 */
		foreach($this->getFloatingInventory()->getContents() as $item){
			$this->getTransactionQueue()->addTransaction(new DropItemTransaction($item));
		}
	}

	/**
	 * Handle an incoming container set slot request
	 *
	 * @param int $slot
	 * @param int $windowId
	 * @param Item $item
	 * @param int $hotbarSlot
	 */
	public function handleContainerSetSlot(int $slot, int $windowId, Item $item, int $hotbarSlot) {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if($slot < 0) {
			return;
		}

		switch($windowId) {
			case ContainerIds::TYPE_INVENTORY: // Normal inventory change
				if($slot >= $inventory->getSize()) {
					return;
				}

				$transaction = new BaseTransaction($inventory, $slot, $inventory->getItem($slot), $item);
				break;
			case ContainerIds::TYPE_ARMOR: // Armour change
				if($slot >= 4) {
					return;
				}

				$transaction = new BaseTransaction($inventory, $slot + $inventory->getSize(), $inventory->getArmorItem($slot), $item);
				break;

			case ContainerIds::TYPE_HOTBAR: // Hotbar link update
				// hotbarSlot 0-8, slot 9-44
				$inventory->setHotbarSlotIndex($hotbarSlot, $slot - 9);
				return;
			default:
				if(!($inv = $player->getWindow($windowId)) instanceof Inventory) {
					$player->getServer()->getLogger()->debug($player->getName() . " tried to set slot " . $slot . " on unknown window to " . $item . "");
					return; // unknown windowID and/or not matching any open windows
				}

				$player->craftingType = 0;
				$transaction = new BaseTransaction($inv, $slot, $inv->getItem($slot), $item);
				break;
		}

		$this->getTransactionQueue()->addTransaction($transaction);
	}

	/**
	 * Handle an incoming crafting request
	 *
	 * @param Recipe $recipe
	 * @param Item[] $input
	 * @param Item[] $output
	 */
	public function handleCraftingEvent(Recipe $recipe, array $input, array $output) {
		$player = $this->getPlayer();
		$inventory = $player->getInventory();

		if(($recipe instanceof BigShapelessRecipe or $recipe instanceof BigShapedRecipe) and $player->craftingType === 0) {
			$player->getServer()->getLogger()->debug("Received big crafting recipe from " . $player->getName() . " with no crafting table open");
			$inventory->sendContents($player);
			return;
		} elseif($recipe === null) {
			$player->getServer()->getLogger()->debug("Null (unknown) crafting recipe received from " . $player->getName() . " for " . $output[0]);
			$inventory->sendContents($player);
			return;
		}

		$canCraft = true;
		if(count($input) === 0) {
			/* If the packet "input" field is empty this needs to be handled differently.
			 * "input" is used to tell the server what items to remove from the client's inventory
			 * Because crafting takes the materials in the crafting grid, nothing needs to be taken from the inventory
			 * Instead, we take the materials from the crafting inventory
			 * To know what materials we need to take, we have to guess the crafting recipe used based on the
			 * output item and the materials stored in the crafting items
			 * The reason we have to guess is because Win10 sometimes sends a different recipe UUID
			 * say, if you put the wood for a door in the right hand side of the crafting grid instead of the left
			 * it will send the recipe UUID for a wooden pressure plate. Unknown currently whether this is a client
			 * bug or if there is something wrong with the way the server handles recipes.
			 */
			if(!$output[0]->equals($recipe->getResult())) {
				$player->getServer()->getLogger()->debug("Mismatched desktop recipe received from player " . $player->getName() . ", expected " . $recipe->getResult() . ", got ". $output[0]);
			}

			// Make a copy of the floating inventory that we can make changes to.
			$floatingInventory = clone $this->floatingInventory;

			$ingredients = $recipe->getIngredientList();

			// Check we have all the necessary ingredients.
			foreach($ingredients as $ingredient) {
				if(!$floatingInventory->contains($ingredient)) {
					// We don't have the ingredients
					return;
				}
				// This will only be reached if we have the item to take away.
				$floatingInventory->removeItem($ingredient);
			}

			$player->getServer()->getPluginManager()->callEvent($ev = new CraftItemEvent($input, $recipe, $player));

			if($ev->isCancelled()) {
				$inventory->sendContents($player);
				return;
			}

			$floatingInventory->addItem(clone $recipe->getResult()); // Add the result to our version of the crafting inventory
			$this->floatingInventory = $floatingInventory; // Set player crafting inv to the idea one created in this process
		} else {
			if($recipe instanceof ShapedRecipe) {
				for($x = 0; $x < 3 and $canCraft; ++$x) {
					for($y = 0; $y < 3; ++$y) {
						$item = $input[$y * 3 + $x];
						$ingredient = $recipe->getIngredient($x, $y);
						if($item->getCount() > 0 and $item->getId() > 0) {
							if($ingredient == null) {
								$canCraft = false;
								break;
							}
							if($ingredient->getId() != 0 and !$ingredient->equals($item, !$ingredient->hasAnyDamageValue(), $ingredient->hasCompoundTag())) {
								$canCraft = false;
								break;
							}

						} elseif($ingredient !== null and $item->getId() !== 0) {
							$canCraft = false;
							break;
						}
					}
				}
			} elseif($recipe instanceof ShapelessRecipe) {
				$needed = $recipe->getIngredientList();

				for($x = 0; $x < 3 and $canCraft; ++$x) {
					for($y = 0; $y < 3; ++$y) {
						$item = clone $input[$y * 3 + $x];

						foreach($needed as $k => $n) {
							if($n->equals($item, !$n->hasAnyDamageValue(), $n->hasCompoundTag())) {
								$remove = min($n->getCount(), $item->getCount());
								$n->setCount($n->getCount() - $remove);
								$item->setCount($item->getCount() - $remove);

								if($n->getCount() === 0){
									unset($needed[$k]);
								}
							}
						}

						if($item->getCount() > 0) {
							$canCraft = false;
							break;
						}
					}
				}
				if(count($needed) > 0) {
					$canCraft = false;
				}
			} else {
				$canCraft = false;
			}

			/** @var Item[] $ingredients */
			$ingredients = $input;
			$result = $output[0];

			if(!$canCraft or !$recipe->getResult()->equalsExact($result)) {
				$player->getServer()->getLogger()->debug("Unmatched recipe " . $recipe->getId() . " from player " . $player->getName() . ": expected " . $recipe->getResult() . ", got " . $result . ", using: " . implode(", ", $ingredients));
				$inventory->sendContents($player);
				return;
			}

			$used = array_fill(0, $inventory->getSize(), 0);

			foreach($ingredients as $ingredient) {
				$slot = -1;
				foreach($inventory->getContents() as $index => $item) {
					if($ingredient->getId() !== 0 and $ingredient->equals($item, !$ingredient->hasAnyDamageValue(), $ingredient->hasCompoundTag()) and ($item->getCount() - $used[$index]) >= 1) {
						$slot = $index;
						$used[$index]++;
						break;
					}
				}

				if($ingredient->getId() !== 0 and $slot === -1) {
					$canCraft = false;
					break;
				}
			}

			if(!$canCraft) {
				$player->getServer()->getLogger()->debug("Unmatched recipe " . $recipe->getId() . " from player " . $player->getName() . ": client does not have enough items, using: " . implode(", ", $ingredients));
				$inventory->sendContents($player);
				return;
			}

			$player->getServer()->getPluginManager()->callEvent($ev = new CraftItemEvent($input, $recipe, $player));

			if($ev->isCancelled()){
				$inventory->sendContents($player);
				return;
			}

			foreach($used as $slot => $count) {
				if($count === 0) {
					continue;
				}

				$item = $inventory->getItem($slot);

				if($item->getCount() > $count) {
					$newItem = clone $item;
					$newItem->setCount($item->getCount() - $count);
				} else {
					$newItem = Item::get(Item::AIR, 0, 0);
				}

				$inventory->setItem($slot, $newItem);
			}

			$extraItem = $inventory->addItem($recipe->getResult());
			if(count($extraItem) > 0 and !$player->isCreative()) { // Could not add all the items to our inventory (not enough space)
				foreach($extraItem as $item){
					$player->dropItem($item);
				}
			}
		}
	}

	public function handleInventoryTransaction(array $actions, bool $isCraftingPart, int $type, \stdClass $data) {
		// only for 1.2+
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
	 * @param int[] $hotbarMap
	 */
	public function sendInventoryContents(int $windowId, array $items, array $hotbarMap = []) {
		$player = $this->getPlayer();

		$pk = new ContainerSetContentPacket();
		$pk->windowId = $windowId;
		$pk->slots = $items;
		$pk->hotbar = $hotbarMap;
		$pk->eid = $player->getId();

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
		$pk = new ContainerSetSlotPacket();
		$pk->windowId = $windowId;
		$pk->item = $item;
		$pk->slot = $slot;

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
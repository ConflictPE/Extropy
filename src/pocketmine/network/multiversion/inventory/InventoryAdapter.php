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

use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\Recipe;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\types\NetworkInventoryAction;

interface InventoryAdapter {

	/**
	 * Tick the adapter
	 *
	 * @param int $currentTick
	 *
	 * @return mixed
	 */
	public function doTick(int $currentTick);

	/**
	 * Add the default windows for the player
	 */
	public function addDefaultWindows();

	/**
	 * Handle an incoming mob equipment update
	 *
	 * @param int $hotbarSlot
	 * @param Item $item
	 * @param int $inventorySlot
	 */
	public function handleMobEquipment(int $hotbarSlot, Item $item, int $inventorySlot);

	/**
	 * Handle an incoming use item request
	 *
	 * @param Item $item
	 * @param int $slot
	 * @param int $face
	 * @param Vector3 $blockPosition
	 * @param Vector3 $clickPosition
	 */
	public function handleUseItem(Item $item, int $slot, int $face, Vector3 $blockPosition, Vector3 $clickPosition);

	/**
	 * Handle an incoming drop item request
	 *
	 * @param Item $item
	 */
	public function handleDropItem(Item $item);

	/**
	 * Handle an incoming container close request
	 *
	 * @param int $windowId
	 */
	public function handleContainerClose(int $windowId);

	/**
	 * Handle an incoming container set slot request
	 *
	 * @param int $slot
	 * @param int $windowId
	 * @param Item $item
	 * @param int $hotbarSlot
	 */
	public function handleContainerSetSlot(int $slot, int $windowId, Item $item, int $hotbarSlot);

	/**
	 * Handle an incoming crafting request
	 *
	 * @param Recipe $recipe
	 * @param Item[] $input
	 * @param Item[] $output
	 */
	public function handleCraftingEvent(Recipe $recipe, array $input, array $output);

	/**
	 * Handle an incoming inventory action
	 *
	 * @param NetworkInventoryAction[] $actions
	 * @param bool $isCraftingPart
	 * @param int $type
	 * @param \stdClass $data
	 */
	public function handleInventoryTransaction(array $actions, bool $isCraftingPart, int $type, \stdClass $data);

	/**
	 * Send a packet to open a container inventory
	 *
	 * @param ContainerInventory $inventory
	 */
	public function sendContainerOpen(ContainerInventory $inventory);

	/**
	 * Send a packet to set an inventory's contents
	 *
	 * @param int $windowId
	 * @param Item[] $items
	 * @param Item[] $hotbarItems
	 */
	public function sendInventoryContents(int $windowId, array $items, array $hotbarItems = []);

	/**
	 * Send a packet to set a slot in an inventory
	 *
	 * @param int $windowId
	 * @param Item $item
	 * @param int $slot
	 */
	public function sendInventorySlot(int $windowId, Item $item, int $slot);

	/**
	 * Send a packet to close a container inventory
	 *
	 * @param ContainerInventory $inventory
	 */
	public function sendContainerClose(ContainerInventory $inventory);

}
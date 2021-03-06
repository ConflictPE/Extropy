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

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\tool\pickaxe\Pickaxe;
use pocketmine\item\tool\Tool;
use pocketmine\item\tool\ToolTier;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Furnace as TileFurnace;
use pocketmine\tile\Tile;

class BurningFurnace extends Solid {

	protected $id = self::BURNING_FURNACE;

	public function __construct(int $meta = 0) {
		$this->meta = $meta;
	}

	public function getName() : string {
		return "Burning Furnace";
	}

	public function getHardness() : float {
		return 3.5;
	}

	public function getToolType() : int {
		return Tool::TYPE_PICKAXE;
	}

	public function getLightLevel() : int {
		return 13;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $facePos, Player $player = null) : bool {
		$faces = [
			0 => 4,
			1 => 2,
			2 => 5,
			3 => 3,
		];
		$this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];
		$this->getLevel()->setBlock($blockReplace, $this, true, true);
		$nbt = new Compound("", [
			new Enum("Items", []),
			new StringTag("id", Tile::FURNACE),
			new IntTag("x", $this->x),
			new IntTag("y", $this->y),
			new IntTag("z", $this->z),
		]);
		$nbt->Items->setTagType(NBT::TAG_Compound);
		if($item->hasCustomName()) {
			$nbt->CustomName = new StringTag("CustomName", $item->getCustomName());
		}
		if($item->hasCustomBlockData()) {
			foreach($item->getCustomBlockData() as $key => $v) {
				$nbt->{$key} = $v;
			}
		}
		Tile::createTile("Furnace", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
		return true;
	}

	public function onActivate(Item $item, Player $player = null) : bool {
		if($player instanceof Player) {
			$furnace = $this->getLevel()->getTile($this);
			if(!($furnace instanceof TileFurnace)) {
				$nbt = new Compound("", [
					new Enum("Items", []),
					new StringTag("id", Tile::FURNACE),
					new IntTag("x", $this->x),
					new IntTag("y", $this->y),
					new IntTag("z", $this->z),
				]);
				$nbt->Items->setTagType(NBT::TAG_Compound);
				$furnace = Tile::createTile("Furnace", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
			}
			if(isset($furnace->namedtag->Lock) and $furnace->namedtag->Lock instanceof StringTag) {
				if($furnace->namedtag->Lock->getValue() !== $item->getCustomName()) {
					return true;
				}
			}
			$player->addWindow($furnace->getInventory());
		}
		return true;
	}

	public function getVariantBitmask() : int {
		return 0;
	}

	public function getDrops(Item $item) : array {
		if($item instanceof Pickaxe and $item->getTier() >= ToolTier::TIER_WOODEN) {
			return parent::getDrops($item);
		}
		return [];
	}

}
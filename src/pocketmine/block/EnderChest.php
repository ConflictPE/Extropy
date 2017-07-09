<?php

namespace pocketmine\block;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\Player;

use pocketmine\tile\Tile;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;

class EnderChest extends Transparent {

    protected $id = self::ENDER_CHEST;

    public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName() {
		return 'Ender Chest';
	}

	public function canBeActivated(){
		return true;
	}

	public function getToolType() {
		return Tool::TYPE_PICKAXE;
	}

	public function getHardness() {
		return 22.5;
	}

    public function getLightLevel(){
		return 7;
	}

    public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$faces = [
			0 => 4,
			1 => 2,
			2 => 5,
			3 => 3,
		];
		$this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];
		$this->getLevel()->setBlock($block, $this, true, true);
		$nbt = new Compound("", [
			new StringTag("id", Tile::ENDER_CHEST),
			new IntTag("x", $this->x),
			new IntTag("y", $this->y),
			new IntTag("z", $this->z)
		]);
		if($item->hasCustomName()){
			$nbt->CustomName = new StringTag("CustomName", $item->getCustomName());
		}
		Tile::createTile("EnderChest", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
		return true;
	}

	public function onActivate(Item $item, Player $player = null){
		if($player instanceof Player){
			$top = $this->getSide(1);
			if($top->isTransparent() !== true){
				return true;
			}
			if(!($this->getLevel()->getTile($this) instanceof \pocketmine\tile\EnderChest)) {
				$nbt = new Compound("", [
					new StringTag("id", Tile::ENDER_CHEST),
					new IntTag("x", $this->x),
					new IntTag("y", $this->y),
					new IntTag("z", $this->z)
				]);
				Tile::createTile("EnderChest", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
			}
			if($player->isCreative()){
				return true;
			}
			$player->getEnderChestInventory()->openAt($this);
		}
		return true;
	}

	public function getDrops(Item $item){
		//if($item->hasEnchantment(Enchantment::TYPE_MINING_SILK_TOUCH)){
		//	return [
		//		[$this->id, 0, 1],
		//	];
		//}
		return [
			[Item::OBSIDIAN, 0, 8],
		];
	}

}

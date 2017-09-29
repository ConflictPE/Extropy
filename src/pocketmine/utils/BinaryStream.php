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

namespace pocketmine\utils;

#include <rules/DataPacket.h>

#ifndef COMPILE

#endif

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\protocol\Info;

class BinaryStream extends \stdClass{

	/** @var int */
	public $offset;

	/** @var string */
	public $buffer;

	public function __construct(string $buffer = "", int $offset = 0) {
		$this->setBuffer($buffer, $offset);
	}

	public function reset() {
		$this->buffer = "";
		$this->offset = 0;
	}

	public function setBuffer(string $buffer = "", int $offset = 0) {
		$this->buffer = $buffer;
		$this->offset = $offset;
	}

	public function getOffset() : int {
		return $this->offset;
	}

	public function getBuffer() : string {
		return $this->buffer;
	}

	public function get($len) : string {
		if($len === true){
			return $this->getRemaining();
		}elseif($len < 0){
			$this->offset = strlen($this->buffer) - 1;
			return "";
		}elseif($len === 0){
			return "";
		}
		return $len === 1 ? $this->buffer{$this->offset++} : substr($this->buffer, ($this->offset += $len) - $len, $len);
	}

	public function getRemaining() : string {
		$str = substr($this->buffer, $this->offset);
		$this->offset = strlen($this->buffer);
		return $str;
	}

	public function put(string $str) {
		$this->buffer .= $str;
	}

	public function getBool() : bool {
		return $this->get(1) !== "\x00";
	}

	public function putBool(bool $value) {
		$this->put($value ? "\x01" : "\x00");
	}

	public function getLong() : int {
		return Binary::readLong($this->get(8));
	}

	public function putLong(int $value) {
		$this->put(Binary::writeLong($value));
	}

	public function getInt() : int {
		return Binary::readInt($this->get(4));
	}

	public function putInt(int $value) {
		$this->put(Binary::writeInt($value));
	}

	public function getLLong() : int {
		return Binary::readLLong($this->get(8));
	}

	public function putLLong(int $value) {
		$this->put(Binary::writeLLong($value));
	}

	public function getLInt() {
		return Binary::readLInt($this->get(4));
	}

	public function putLInt(int $value) {
		$this->put(Binary::writeLInt($value));
	}

	public function getShort(bool $signed = true) {
		return $signed ? Binary::readSignedShort($this->get(2)) : Binary::readShort($this->get(2));
	}

	public function putShort(int $value) {
		$this->put(Binary::writeShort($value));
	}

	public function getFloat() : float {
		return Binary::readFloat($this->get(4));
	}

	public function getRoundedFloat(int $accuracy) : float {
		return Binary::readRoundedFloat($this->get(4), $accuracy);
	}

	public function putFloat(float $value) {
		$this->put(Binary::writeFloat($value));
	}

	public function getLShort(bool $signed = true) : int {
		return $signed ? Binary::readSignedLShort($this->get(2)) : Binary::readLShort($this->get(2));
	}

	public function putLShort(int $value) {
		$this->put(Binary::writeLShort($value));
	}

	public function getLFloat() : float {
		return Binary::readLFloat($this->get(4));
	}

	public function getRoundedLFloat(int $accuracy) : float {
		return Binary::readRoundedLFloat($this->get(4), $accuracy);
	}

	public function putLFloat(float $value) {
		$this->put(Binary::writeLFloat($value));
	}

	public function getTriad() : int {
		return Binary::readTriad($this->get(3));
	}

	public function putTriad(int $value) {
		$this->put(Binary::writeTriad($value));
	}

	public function getLTriad() : int {
		return Binary::readLTriad($this->get(3));
	}

	public function putLTriad(int $value) {
		$this->put(Binary::writeLTriad($value));
	}

	public function getByte() : int {
		return ord($this->buffer{$this->offset++});
	}

	public function putByte(int $value) {
		$this->put(chr($value));
	}

	public function getDataArray($len = 10) {
		$data = [];
		for($i = 1; $i <= $len and !$this->feof(); ++$i){
			$data[] = $this->get($this->getTriad());
		}

		return $data;
	}

	public function putDataArray(array $data = []) {
		foreach($data as $v) {
			$this->putTriad(strlen($v));
			$this->put($v);
		}
	}

	public function getUUID() {
		$part1 = $this->getLInt();
		$part0 = $this->getLInt();
		$part3 = $this->getLInt();
		$part2 = $this->getLInt();
		return new UUID($part0, $part1, $part2, $part3);
	}

	public function putUUID(UUID $uuid) {
		$this->putLInt($uuid->getPart(1));
		$this->putLInt($uuid->getPart(0));
		$this->putLInt($uuid->getPart(3));
		$this->putLInt($uuid->getPart(2));
	}

	public function getSlot(int $playerProtocol) {
		$id = $this->getSignedVarInt();
		if($id <= 0) {
			return ItemFactory::get(Item::AIR, 0, 0);
		}

		$aux = $this->getSignedVarInt();
		$meta = $aux >> 8;
		$count = $aux & 0xff;

		$nbtLen = $this->getLShort();
		$nbt = "";
		if($nbtLen > 0) {
			$nbt = $this->get($nbtLen);
		}

		if($playerProtocol >= Info::PROTOCOL_110) {
			//TODO
			$canPlaceOn = $this->getSignedVarInt();
			if($canPlaceOn > 0){
				for($i = 0; $i < $canPlaceOn; ++$i){
					$this->getString();
				}
			}

			//TODO
			$canDestroy = $this->getSignedVarInt();
			if($canDestroy > 0){
				for($i = 0; $i < $canDestroy; ++$i){
					$this->getString();
				}
			}
		}

		return ItemFactory::get(
			$id,
			$meta,
			$count,
			$nbt
		);
	}

	public function putSlot(Item $item, int $playerProtocol) {
		if($item->getId() === 0) {
			$this->putSignedVarInt(0);
			return;
		}
		$this->putSignedVarInt($item->getId());
		$this->putSignedVarInt(($item->getDamage() === null ? 0  : ($item->getDamage() << 8)) + $item->getCount());
		$nbt = $item->getCompoundTag();
		$this->putLShort(strlen($nbt));
		$this->put($nbt);
		if($playerProtocol >= Info::PROTOCOL_110) {
			$this->putByte(0); // TODO: CanPlaceOn entry count
			$this->putByte(0); // TODO: CanDestroy entry count
		}
	}

	public function feof() {
		return !isset($this->buffer{$this->offset});
	}

	public function getSignedVarInt() : int {
		return Binary::readSignedVarInt($this);
	}

	public function getVarInt() : int {
		return Binary::readVarInt($this);
	}

	public function putSignedVarInt(int $value) {
		$this->put(Binary::writeSignedVarInt($value));
	}

	public function putVarInt(int $value) {
		$this->put(Binary::writeVarInt($value));
	}

	public function getString() : string {
		return $this->get($this->getVarInt());
	}

	public function putString(string $value) {
		$this->putVarInt(strlen($value));
		$this->put($value);
	}

	public function getVarLong() : int {
		return Binary::readVarLong($this->buffer, $this->offset);
	}

	public function putVarLong(int $value) {
		$this->put(Binary::writeVarLong($value));
	}

	public function getSignedVarLong() : int {
		return Binary::readSignedVarLong($this->buffer, $this->offset);
	}

	public function putSignedVarLong(int $value) {
		$this->put(Binary::writeSignedVarLong($value));
	}

}
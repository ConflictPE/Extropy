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

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>


use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;

abstract class DataPacket extends BinaryStream {

	const NETWORK_ID = 0;
	const PACKET_NAME = "";

	public $isEncoded = false;
	private $channel = 0;

	protected static $packetsIds = [];

	public function pid() {
		return $this::NETWORK_ID;
	}

	public function pname() {
		return $this::PACKET_NAME;
	}

	/**
	 * @deprecated This adds extra overhead on the network, so its usage is now discouraged. It was a test for the viability of this.
	 */
	public function setChannel($channel) {
		$this->channel = (int) $channel;
		return $this;
	}

	public function getChannel() {
		return $this->channel;
	}

	public function clean() {
		$this->buffer = null;
		$this->isEncoded = false;
		$this->offset = 0;
		return $this;
	}

	public function __debugInfo() {
		$data = [];
		foreach($this as $k => $v) {
			if($k === "buffer") {
				$data[$k] = bin2hex($v);
			} elseif(is_string($v) or (is_object($v) and method_exists($v, "__toString"))) {
				$data[$k] = Utils::printable((string) $v);
			} else {
				$data[$k] = $v;
			}
		}

		return $data;
	}

	public static function initPackets() {
		$oClass = new \ReflectionClass ('pocketmine\network\protocol\Info');
		self::$packetsIds[Info::BASE_PROTOCOL] = $oClass->getConstants();
		$oClass = new \ReflectionClass ('pocketmine\network\protocol\Info105');
		self::$packetsIds[Info::PROTOCOL_105] = $oClass->getConstants();
		$oClass = new \ReflectionClass ('pocketmine\network\protocol\Info110');
		self::$packetsIds[Info::PROTOCOL_110] = $oClass->getConstants();
		$oClass = new \ReflectionClass ('pocketmine\network\protocol\Info120');
		self::$packetsIds[Info::PROTOCOL_120] = $oClass->getConstants();
		self::$packetsIds[Info::PROTOCOL_201] = $oClass->getConstants();
	}

	/**
	 * Decodes entity metadata from the stream.
	 *
	 * @param int $playerProtocol Protocol of the player who sent the packet
	 * @param bool $types Whether to include metadata types along with values in the returned array
	 *
	 * @return array
	 */
	public function getEntityMetadata(int $playerProtocol, bool $types = true) : array {
		$count = $this->getVarInt();
		$data = [];
		for($i = 0; $i < $count; ++$i) {
			$key = $this->getVarInt();
			$type = $this->getVarInt();
			$value = null;
			switch($type) {
				case Entity::DATA_TYPE_BYTE:
					$value = $this->getByte();
					break;
				case Entity::DATA_TYPE_SHORT:
					$value = $this->getLShort();
					break;
				case Entity::DATA_TYPE_INT:
					$value = $this->getVarInt();
					break;
				case Entity::DATA_TYPE_FLOAT:
					$value = $this->getLFloat();
					break;
				case Entity::DATA_TYPE_STRING:
					$value = $this->getString();
					break;
				case Entity::DATA_TYPE_SLOT:
					//TODO: use objects directly
					$value = [];
					/** @var Item $item */
					$item = $this->getSlot($playerProtocol);
					$value[0] = $item->getId();
					$value[1] = $item->getCount();
					$value[2] = $item->getDamage();
					break;
				case Entity::DATA_TYPE_POS:
					$value = [0, 0, 0];
					$this->getSignedBlockPosition(...$value);
					break;
				case Entity::DATA_TYPE_LONG:
					$value = $this->getSignedVarLong();
					break;
				//case Entity::DATA_TYPE_VECTOR3F:
				//	$value = [0.0, 0.0, 0.0];
				//	$this->getVector3f(...$value);
				//	break;
				default:
					$value = [];
			}
			if($types === true) {
				$data[$key] = [$type, $value];
			} else {
				$data[$key] = $value;
			}
		}

		return $data;
	}

	/**
	 * Writes entity metadata to the packet buffer.
	 *
	 * @param int $playerProtocol Protocol of the player receiving the packet
	 * @param array $metadata
	 */
	public function putEntityMetadata(int $playerProtocol, array $metadata) {
		$this->putVarInt(count($metadata));
		foreach($metadata as $key => $d) {
			$this->putVarInt($key); //data key
			$this->putVarInt($d[0]); //data type
			switch($d[0]) {
				case Entity::DATA_TYPE_BYTE:
					$this->putByte($d[1]);
					break;
				case Entity::DATA_TYPE_SHORT:
					$this->putLShort($d[1]); //SIGNED short!
					break;
				case Entity::DATA_TYPE_INT:
					$this->putVarInt($d[1]);
					break;
				case Entity::DATA_TYPE_FLOAT:
					$this->putLFloat($d[1]);
					break;
				case Entity::DATA_TYPE_STRING:
					$this->putString($d[1]);
					break;
				case Entity::DATA_TYPE_SLOT:
					//TODO: change this implementation (use objects)
					$this->putSlot(ItemFactory::get($d[1][0], $d[1][2], $d[1][1]), $playerProtocol); //ID, damage, count
					break;
				case Entity::DATA_TYPE_POS:
					//TODO: change this implementation (use objects)
					$this->putSignedBlockPosition(...$d[1]);
					break;
				case Entity::DATA_TYPE_LONG:
					$this->putSignedVarLong($d[1]);
					break;
				//case Entity::DATA_TYPE_VECTOR3F:
				//	//TODO: change this implementation (use objects)
				//	$this->putVector3f(...$d[1]); //x, y, z
			}
		}
	}


	/**
	 * Reads and returns an EntityUniqueID
	 * @return int
	 */
	public function getEntityUniqueId() : int {
		return $this->getSignedVarLong();
	}

	/**
	 * Writes an EntityUniqueID
	 * @param int $eid
	 */
	public function putEntityUniqueId(int $eid) {
		$this->putSignedVarLong($eid);
	}

	/**
	 * Reads and returns an EntityRuntimeID
	 * @return int
	 */
	public function getEntityRuntimeId() : int {
		return $this->getVarLong();
	}

	/**
	 * Writes an EntityUniqueID
	 * @param int $eid
	 */
	public function putEntityRuntimeId(int $eid) {
		$this->putVarLong($eid);
	}

	/**
	 * Reads an block position with unsigned Y coordinate.
	 * @param int &$x
	 * @param int &$y
	 * @param int &$z
	 */
	public function getBlockPosition(&$x, &$y, &$z) {
		$x = $this->getSignedVarInt();
		$y = $this->getVarInt();
		$z = $this->getSignedVarInt();
	}

	/**
	 * Writes a block position with unsigned Y coordinate.
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 */
	public function putBlockPosition(int $x, int $y, int $z) {
		$this->putSignedVarInt($x);
		$this->putVarInt($y);
		$this->putSignedVarInt($z);
	}

	/**
	 * Reads a block position with a signed Y coordinate.
	 * @param int &$x
	 * @param int &$y
	 * @param int &$z
	 */
	public function getSignedBlockPosition(&$x, &$y, &$z) {
		$x = $this->getSignedVarInt();
		$y = $this->getSignedVarInt();
		$z = $this->getSignedVarInt();
	}

	/**
	 * Writes a block position with a signed Y coordinate.
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 */
	public function putSignedBlockPosition(int $x, int $y, int $z) {
		$this->putSignedVarInt($x);
		$this->putSignedVarInt($y);
		$this->putSignedVarInt($z);
	}

	/**
	 * Reads a floating-point vector3 rounded to 4dp.
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 */
	public function getVector3f(&$x, &$y, &$z) {
		$x = $this->getRoundedLFloat(4);
		$y = $this->getRoundedLFloat(4);
		$z = $this->getRoundedLFloat(4);
	}

	/**
	 * Writes a floating-point vector3
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 */
	public function putVector3f(float $x, float $y, float $z) {
		$this->putLFloat($x);
		$this->putLFloat($y);
		$this->putLFloat($z);
	}

	public function getByteRotation() : float {
		return (float) ($this->getByte() * (360 / 256));
	}

	public function putByteRotation(float $rotation) {
		$this->putByte((int) ($rotation / (360 / 256)));
	}

	/**
	 * Reads gamerules
	 * TODO: implement this properly
	 *
	 * @return array
	 */
	public function getGameRules() : array {
		$count = $this->getVarInt();
		$rules = [];
		for($i = 0; $i < $count; ++$i) {
			$name = $this->getString();
			$type = $this->getVarInt();
			$value = null;
			switch($type) {
				case 1:
					$value = $this->getBool();
					break;
				case 2:
					$value = $this->getVarInt();
					break;
				case 3:
					$value = $this->getLFloat();
					break;
			}

			$rules[$name] = [$type, $value];
		}

		return $rules;
	}

	/**
	 * Writes a gamerule array
	 * TODO: implement this properly
	 *
	 * @param array $rules
	 */
	public function putGameRules(array $rules) {
		$this->putVarInt(count($rules));
		foreach($rules as $name => $rule){
			$this->putString($name);
			$this->putVarInt($rule[0]);
			switch($rule[0]) {
				case 1:
					$this->putBool($rule[1]);
					break;
				case 2:
					$this->putVarInt($rule[1]);
					break;
				case 3:
					$this->putLFloat($rule[1]);
					break;
			}
		}
	}

	/**
	 * Reads a floating-point Vector3 object
	 * TODO: get rid of primitive methods and replace with this
	 *
	 * @return Vector3
	 */
	public function getVector3Obj() : Vector3 {
		return new Vector3(
			$this->getRoundedLFloat(4),
			$this->getRoundedLFloat(4),
			$this->getRoundedLFloat(4)
		);
	}

	/**
	 * Writes a floating-point Vector3 object, or 3x zero if null is given.
	 *
	 * Note: ONLY use this where it is reasonable to allow not specifying the vector.
	 * For all other purposes, use {@link DataPacket#putVector3Obj}
	 *
	 * @param Vector3|null $vector
	 */
	public function putVector3ObjNullable(Vector3 $vector = null) {
		if($vector) {
			$this->putVector3Obj($vector);
		} else {
			$this->putLFloat(0.0);
			$this->putLFloat(0.0);
			$this->putLFloat(0.0);
		}
	}

	/**
	 * Writes a floating-point Vector3 object
	 * TODO: get rid of primitive methods and replace with this
	 *
	 * @param Vector3 $vector
	 */
	public function putVector3Obj(Vector3 $vector) {
		$this->putLFloat($vector->x);
		$this->putLFloat($vector->y);
		$this->putLFloat($vector->z);
	}

}
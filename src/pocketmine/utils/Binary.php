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

/**
 * Various Utilities used around the code
 */
namespace pocketmine\utils;

use pocketmine\entity\Entity;
use pocketmine\nbt\NBT;

class Binary {

	const BIG_ENDIAN = 0x00;
	const LITTLE_ENDIAN = 0x01;

	private static function checkLength(string $str, int $expect) {
		assert(($len = strlen($str)) === $expect, "Expected $expect bytes, got $len");
	}

	public static function signByte(int $value) : int {
		return $value << 56 >> 56;
	}

	public static function unsignByte(int $value) : int {
		return $value & 0xff;
	}

	public static function signShort(int $value) : int {
		return $value << 48 >> 48;
	}

	public function unsignShort(int $value) : int {
		return $value & 0xffff;
	}

	public static function signInt(int $value) : int {
		return $value << 32 >> 32;
	}

	public static function unsignInt(int $value) : int {
		return $value & 0xffffffff;
	}

	public static function writeMetadata(array $data, $playerProtocol){
		$data = MetadataConvertor::updateMeta($data, $playerProtocol);
		$m = "";
		$m .= self::writeVarInt(count($data));
		foreach($data as $bottom => $d){
			$m .= self::writeVarInt($bottom);
			$m .= self::writeVarInt($d[0]);
			switch($d[0]){
				case Entity::DATA_TYPE_BYTE:
					$m .= self::writeByte($d[1]);
					break;
				case Entity::DATA_TYPE_SHORT:
					$m .= self::writeLShort($d[1]);
					break;
				case Entity::DATA_TYPE_INT:
					$m .= self::writeSignedVarInt($d[1]);
					break;
				case Entity::DATA_TYPE_FLOAT:
					$m .= self::writeLFloat($d[1]);
					break;
				case Entity::DATA_TYPE_STRING:
					$m .= self::writeVarInt(strlen($d[1])) . $d[1];
					break;
				case Entity::DATA_TYPE_SLOT:
					$m .= "\x7f";
//					$m .= self::writeLShort($d[1][0]);
//					$m .= self::writeByte($d[1][1]);
//					$m .= self::writeLShort($d[1][2]);
					break;
				case Entity::DATA_TYPE_POS:
					$m .= self::writeSignedVarInt($d[1][0]);
					$m .= self::writeSignedVarInt($d[1][1]);
					$m .= self::writeSignedVarInt($d[1][2]);
					break;
				case Entity::DATA_TYPE_LONG:
					$m .= self::writeSignedVarInt($d[1]);
					break;
				case Entity::DATA_TYPE_VECTOR3:
					$m .= self::writeLFloat($d[1][0]);
					$m .= self::writeLFloat($d[1][1]);
					$m .= self::writeLFloat($d[1][2]);
					break;
			}
		}
		return $m;
	}

	///**
	// * Reads a metadata coded string
	// *
	// * @param      $value
	// * @param bool $types
	// *
	// * @return array
	// */
	//public static function readMetadata($value, $types = false){
	//	$offset = 0;
	//	$m = [];
	//	$b = ord($value{$offset});
	//	++$offset;
	//	while($b !== 127 and isset($value{$offset})){
	//		$bottom = $b & 0x1F;
	//		$type = $b >> 5;
	//		switch($type){
	//			case Entity::DATA_TYPE_BYTE:
	//				$r = self::readByte($value{$offset});
	//				++$offset;
	//				break;
	//			case Entity::DATA_TYPE_SHORT:
	//				$r = self::readLShort(substr($value, $offset, 2));
	//				$offset += 2;
	//				break;
	//			case Entity::DATA_TYPE_INT:
	//				$r = self::readLInt(substr($value, $offset, 4));
	//				$offset += 4;
	//				break;
	//			case Entity::DATA_TYPE_FLOAT:
	//				$r = self::readLFloat(substr($value, $offset, 4));
	//				$offset += 4;
	//				break;
	//			case Entity::DATA_TYPE_STRING:
	//				$len = self::readLShort(substr($value, $offset, 2));
	//				$offset += 2;
	//				$r = substr($value, $offset, $len);
	//				$offset += $len;
	//				break;
	//			case Entity::DATA_TYPE_SLOT:
	//				$r = [];
	//				$r[] = self::readLShort(substr($value, $offset, 2));
	//				$offset += 2;
	//				$r[] = ord($value{$offset});
	//				++$offset;
	//				$r[] = self::readLShort(substr($value, $offset, 2));
	//				$offset += 2;
	//				break;
	//			case Entity::DATA_TYPE_POS:
	//				$r = [];
	//				for($i = 0; $i < 3; ++$i){
	//					$r[] = self::readLInt(substr($value, $offset, 4));
	//					$offset += 4;
	//				}
	//				break;
	//			case Entity::DATA_TYPE_LONG:
	//				$r = self::readLLong(substr($value, $offset, 4));
	//				$offset += 8;
	//				break;
	//			default:
	//				return [];
	//
	//		}
	//		if($types === true){
	//			$m[$bottom] = [$r, $type];
	//		}else{
	//			$m[$bottom] = $r;
	//		}
	//		$b = ord($value{$offset});
	//		++$offset;
	//	}
	//
	//	return $m;
	//}

	public static function readBool(string $b) : bool {
		return $b !== "\x00";
	}

	public static function writeBool(bool $b) : string {
		return $b ? "\x01" : "\x00";
	}

	public static function readByte($c, $signed = true){
		self::checkLength($c, 1);
		$b = ord($c{0});
		return $signed ? self::signByte($b) : $b;
	}

	public static function writeByte(int $c) : string {
		return chr($c);
	}

	public static function readShort(string $str) : int {
		self::checkLength($str, 2);
		return unpack("n", $str)[1];
	}

	public static function readSignedShort(string $str) : int {
		self::checkLength($str, 2);
		return self::signShort(unpack("n", $str)[1]);
	}

	public static function readLShort(string $str) : int{
		self::checkLength($str, 2);
		return unpack("v", $str)[1];
	}

	public static function readSignedLShort(string $str) : int {
		self::checkLength($str, 2);
		return self::signShort(unpack("v", $str)[1]);
	}

	public static function writeLShort(int $value) : string {
		return pack("v", $value);
	}

	public static function writeShort(int $value) : string {
		return pack("n", $value);
	}

	public static function readTriad(string $str) : int {
		self::checkLength($str, 3);
		return unpack("N", "\x00" . $str)[1];
	}

	public static function writeTriad(int $value) : string{
		return substr(pack("N", $value), 1);
	}

	public static function readLTriad(string $str) : int {
		self::checkLength($str, 3);
		return unpack("V", $str . "\x00")[1];
	}

	public static function writeLTriad(int $value) : string {
		return substr(pack("V", $value), 0, -1);
	}

	public static function readInt(string $str) : int {
		self::checkLength($str, 4);
		return self::signInt(unpack("N", $str)[1]);
	}

	public static function writeInt(int $value) : string {
		return pack("N", $value);
	}

	public static function readLInt(string $str) : int {
		self::checkLength($str, 4);
		return self::signInt(unpack("V", $str)[1]);
	}

	public static function writeLInt(int $value) : string {
		return pack("V", $value);
	}

	public static function readFloat(string $str) : float {
		self::checkLength($str, 4);
		return ENDIANNESS === self::BIG_ENDIAN ? unpack("f", $str)[1] : unpack("f", strrev($str))[1];
	}

	public static function readRoundedFloat(string $str, int $accuracy) : float {
		return round(self::readFloat($str), $accuracy);
	}

	public static function writeFloat(float $value) : string {
		return ENDIANNESS === self::BIG_ENDIAN ? pack("f", $value) : strrev(pack("f", $value));
	}

	public static function readLFloat(string $str) : float {
		self::checkLength($str, 4);
		return ENDIANNESS === self::BIG_ENDIAN ? unpack("f", strrev($str))[1] : unpack("f", $str)[1];
	}

	public static function readRoundedLFloat(string $str, int $accuracy) : float {
		return round(self::readLFloat($str), $accuracy);
	}

	public static function writeLFloat(float $value) : string {
		return ENDIANNESS === self::BIG_ENDIAN ? strrev(pack("f", $value)) : pack("f", $value);
	}

	public static function printFloat(float $value) : string {
		return preg_replace("/(\\.\\d+?)0+$/", "$1", sprintf("%F", $value));
	}

	public static function readDouble(string $str) : float{
		self::checkLength($str, 8);
		return ENDIANNESS === self::BIG_ENDIAN ? unpack("d", $str)[1] : unpack("d", strrev($str))[1];
	}

	public static function writeDouble(float $value) : string {
		return ENDIANNESS === self::BIG_ENDIAN ? pack("d", $value) : strrev(pack("d", $value));
	}

	public static function readLDouble(string $str) : float {
		self::checkLength($str, 8);
		return ENDIANNESS === self::BIG_ENDIAN ? unpack("d", strrev($str))[1] : unpack("d", $str)[1];
	}

	public static function writeLDouble(float $value) : string {
		return ENDIANNESS === self::BIG_ENDIAN ? strrev(pack("d", $value)) : pack("d", $value);
	}

	public static function readLong(string $x) : int {
		self::checkLength($x, 8);
		$int = unpack("N*", $x);
		return ($int[1] << 32) | $int[2];
	}

	public static function writeLong(int $value) : string {
		return pack("NN", $value >> 32, $value & 0xFFFFFFFF);
	}

	public static function readLLong(string $str) : int {
		return self::readLong(strrev($str));
	}

	public static function writeLLong(int $value) : string {
		return strrev(self::writeLong($value));
	}

	public static function writeSignedVarInt(int $value) : string {
		return self::writeVarInt(($value << 1) ^ ($value >> (PHP_INT_SIZE === 8 ? 63 : 31)));
	}

	/**
	 * @param BinaryStream|NBT $stream
	 *
	 * @return int
	 */
	public static function readSignedVarInt($stream) : int {
		$shift = PHP_INT_SIZE === 8 ? 63 : 31;
		$raw = self::readVarInt($stream);
		$temp = ((($raw << $shift) >> $shift) ^ $raw) >> 1;
		return $temp ^ ($raw & (1 << $shift));
	}

	public static function writeVarInt(int $value) : string {
		$buf = "";
		for($i = 0; $i < 10; ++$i) {
			if(($value >> 7) !== 0) {
				$buf .= chr($value | 0x80); //Let chr() take the last byte of this, it's faster than adding another & 0x7f.
			} else {
				$buf .= chr($value & 0x7f);
				return $buf;
			}
			$value = (($value >> 7) & (PHP_INT_MAX >> 6)); //PHP really needs a logical right-shift operator
		}
		throw new \InvalidArgumentException("Value too large to be encoded as a varint");
	}

	/**
	 * @param BinaryStream|NBT $stream
	 *
	 * @return int
	 */
	public static function readVarInt($stream) : int {
		$value = 0;
		$i = 0;
		do {
			if($i > 63) {
				throw new \InvalidArgumentException("Varint did not terminate after 10 bytes!");
			}
			$value |= ((($b = $stream->getByte()) & 0x7f) << $i);
			$i += 7;
		} while($b & 0x80);
		return $value;
	}

	public static function readSignedVarLong(string $buffer, int &$offset) : int {
		$raw = self::readVarLong($buffer, $offset);
		$temp = ((($raw << 63) >> 63) ^ $raw) >> 1;
		return $temp ^ ($raw & (1 << 63));
	}

	public static function readVarLong(string $buffer, int &$offset) : int {
		$value = 0;
		for($i = 0; $i <= 63; $i += 7) {
			$b = ord($buffer{$offset++});
			$value |= (($b & 0x7f) << $i);

			if(($b & 0x80) === 0) {
				return $value;
			} elseif(!isset($buffer{$offset})) {
				throw new \UnexpectedValueException("Expected more bytes, none left to read");
			}
		}

		throw new \InvalidArgumentException("VarLong did not terminate after 10 bytes!");
	}

	public static function writeSignedVarLong(int $v) : string {
		return self::writeVarLong(($v << 1) ^ ($v >> 63));
	}

	public static function writeVarLong(int $value) : string {
		$buf = "";
		for($i = 0; $i < 10; ++$i) {
			if(($value >> 7) !== 0) {
				$buf .= chr($value | 0x80); //Let chr() take the last byte of this, it's faster than adding another & 0x7f.
			} else {
				$buf .= chr($value & 0x7f);
				return $buf;
			}

			$value = (($value >> 7) & (PHP_INT_MAX >> 6)); //PHP really needs a logical right-shift operator
		}

		throw new \InvalidArgumentException("Value too large to be encoded as a VarLong");
	}

}
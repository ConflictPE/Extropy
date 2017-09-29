<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | "_ \ / _ \_____| |\/| | |_) |
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

namespace pocketmine\network\multiversion;

use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\TextPacket;

abstract class MultiversionEnums {

	private static $playerActionType = [
		"default" => [
			-1 => "UNKNOWN",
			0 => PlayerActionPacket::ACTION_START_BREAK,
			1 => PlayerActionPacket::ACTION_ABORT_BREAK,
			2 => PlayerActionPacket::ACTION_STOP_BREAK,
			3 => PlayerActionPacket::ACTION_UPDATE_BLOCK,
			4 => PlayerActionPacket::ACTION_DROP_ITEM,
			5 => PlayerActionPacket::ACTION_RELEASE_ITEM,
			6 => PlayerActionPacket::ACTION_STOP_SLEEPING,
			7 => PlayerActionPacket::ACTION_RESPAWN,
			8 => PlayerActionPacket::ACTION_JUMP,
			9 => PlayerActionPacket::ACTION_START_SPRINT,
			10 => PlayerActionPacket::ACTION_STOP_SPRINT,
			11 => PlayerActionPacket::ACTION_START_SNEAK,
			12 => PlayerActionPacket::ACTION_STOP_SNEAK,
			13 => PlayerActionPacket::ACTION_DIMENSION_CHANGE,
			14 => PlayerActionPacket::ACTION_DIMENSION_CHANGE_ACK,
			15 => PlayerActionPacket::ACTION_START_GLIDE,
			16 => PlayerActionPacket::ACTION_STOP_GLIDE,
			17 => PlayerActionPacket::ACTION_BUILD_DENIED,
			18 => PlayerActionPacket::ACTION_CONTINUE_BREAK,
		],
		120 => [
			-1 => "UNKNOWN",
			0 => PlayerActionPacket::ACTION_START_BREAK,
			1 => PlayerActionPacket::ACTION_ABORT_BREAK,
			2 => PlayerActionPacket::ACTION_STOP_BREAK,
			3 => PlayerActionPacket::ACTION_UPDATE_BLOCK,
			4 => PlayerActionPacket::ACTION_DROP_ITEM,
			5 => PlayerActionPacket::ACTION_START_SLEEPING,
			6 => PlayerActionPacket::ACTION_STOP_SLEEPING,
			7 => PlayerActionPacket::ACTION_RESPAWN,
			8 => PlayerActionPacket::ACTION_JUMP,
			9 => PlayerActionPacket::ACTION_START_SPRINT,
			10 => PlayerActionPacket::ACTION_STOP_SPRINT,
			11 => PlayerActionPacket::ACTION_START_SNEAK,
			12 => PlayerActionPacket::ACTION_STOP_SNEAK,
			13 => PlayerActionPacket::ACTION_DIMENSION_CHANGE,
			14 => PlayerActionPacket::ACTION_DIMENSION_CHANGE_ACK,
			15 => PlayerActionPacket::ACTION_START_GLIDE,
			16 => PlayerActionPacket::ACTION_STOP_GLIDE,
			17 => PlayerActionPacket::ACTION_BUILD_DENIED,
			18 => PlayerActionPacket::ACTION_CONTINUE_BREAK,
			19 => PlayerActionPacket::ACTION_CHANGE_SKIN,
		],
	];

	private static $textPacketType = [
		"default" => [
			0 => TextPacket::TYPE_RAW,
			1 => TextPacket::TYPE_CHAT,
			2 => TextPacket::TYPE_TRANSLATION,
			3 => TextPacket::TYPE_POPUP,
			4 => TextPacket::TYPE_TIP,
			5 => TextPacket::TYPE_SYSTEM,
			6 => TextPacket::TYPE_WHISPER,
			7 => TextPacket::TYPE_ANNOUNCEMENT,
		],
		120 => [
			0 => TextPacket::TYPE_RAW,
			1 => TextPacket::TYPE_CHAT,
			2 => TextPacket::TYPE_TRANSLATION,
			3 => TextPacket::TYPE_POPUP,
			4 => TextPacket::TYPE_JUKEBOX_POPUP,
			5 => TextPacket::TYPE_TIP,
			6 => TextPacket::TYPE_SYSTEM,
			7 => TextPacket::TYPE_WHISPER,
			8 => TextPacket::TYPE_ANNOUNCEMENT,
		],
	];

	public static function getPlayerAction(int $playerProtocol, int $actionId) {
		if(!isset(self::$playerActionType[$playerProtocol])) {
			$playerProtocol = "default";
		}
		if(!isset(self::$playerActionType[$playerProtocol][$actionId])) {
			return self::$playerActionType[$playerProtocol][-1];
		}
		return self::$playerActionType[$playerProtocol][$actionId];
	}

	public static function getPlayerActionId(int $playerProtocol, string $actionName) {
		if(!isset(self::$playerActionType[$playerProtocol])) {
			$playerProtocol = "default";
		}
		foreach(self::$playerActionType[$playerProtocol] as $key => $value) {
			if($actionName === $value) {
				return $key;
			}
		}
		return -1;
	}

	public static function getMessageType(int $playerProtocol, int $typeId) {
		if(!isset(self::$textPacketType[$playerProtocol])) {
			$playerProtocol = "default";
		}
		if(!isset(self::$textPacketType[$playerProtocol][$typeId])) {
			return self::$textPacketType[$playerProtocol][0];
		}
		return self::$textPacketType[$playerProtocol][$typeId];
	}

	public static function getMessageTypeId(int $playerProtocol, string $typeName) {
		if(!isset(self::$textPacketType[$playerProtocol])) {
			$playerProtocol = "default";
		}
		foreach(self::$textPacketType[$playerProtocol] as $key => $value) {
			if($typeName === $value) {
				return $key;
			}
		}

		return 0;
	}

}
<?php

namespace pocketmine\network\multiversion;

use pocketmine\network\protocol\PlayerActionPacket;

abstract class MultiversionEnums {

	private static $playerActionType = [
		'default' => [
			-1 => 'UNKNOWN',
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
		'120' => [
			-1 => 'UNKNOWN',
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
		'default' => [
			0 => 'TYPE_RAW',
			1 => 'TYPE_CHAT',
			2 => 'TYPE_TRANSLATION',
			3 => 'TYPE_POPUP',
			4 => 'TYPE_TIP',
			5 => 'TYPE_SYSTEM',
			6 => 'TYPE_WHISPER',
			7 => 'TYPE_ANNOUNCEMENT',
		],
		'120' => [
			0 => 'TYPE_RAW',
			1 => 'TYPE_CHAT',
			2 => 'TYPE_TRANSLATION',
			3 => 'TYPE_POPUP',
			4 => 'JUKEBOX_POPUP',
			5 => 'TYPE_TIP',
			6 => 'TYPE_SYSTEM',
			7 => 'TYPE_WHISPER',
			8 => 'TYPE_ANNOUNCEMENT',
		],
	];

	public static function getPlayerAction($playerProtocol, $actionId) {
		if (!isset(self::$playerActionType[$playerProtocol])) {
			$playerProtocol = 'default';
		}
		if (!isset(self::$playerActionType[$playerProtocol][$actionId])) {
			return self::$playerActionType[$playerProtocol][-1];
		}
		return self::$playerActionType[$playerProtocol][$actionId];
	}

	public static function getPlayerActionId($playerProtocol, $actionName) {
		if (!isset(self::$playerActionType[$playerProtocol])) {
			$playerProtocol = "default";
		}
		foreach (self::$playerActionType[$playerProtocol] as $key => $value) {
			if ($value == $actionName) {
				return $key;
			}
		}
		return -1;
	}

	public static function getMessageType($playerProtocol, $typeId) {
		if (!isset(self::$textPacketType[$playerProtocol])) {
			$playerProtocol = 'default';
		}
		if (!isset(self::$textPacketType[$playerProtocol][$typeId])) {
			return self::$textPacketType[$playerProtocol][0];
		}
		return self::$textPacketType[$playerProtocol][$typeId];
	}

	public static function getMessageTypeId($playerProtocol, $typeName) {
		if (!isset(self::$textPacketType[$playerProtocol])) {
			$playerProtocol = "default";
		}
		foreach (self::$textPacketType[$playerProtocol] as $key => $value) {
			if ($value == $typeName) {
				return $key;
			}
		}
		return 0;
	}

}

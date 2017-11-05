<?php

namespace pocketmine\utils;

use pocketmine\entity\Entity;

class MetadataConverter {

	private static $entityFlags = [
		"default" => [
			"UNKNOWN" => -1,
			Entity::DATA_FLAG_ONFIRE => 0,
			Entity::DATA_FLAG_SNEAKING => 1,
			Entity::DATA_FLAG_RIDING => 2,
			Entity::DATA_FLAG_SPRINTING => 3,
			Entity::DATA_FLAG_ACTION => 4,
			Entity::DATA_FLAG_INVISIBLE => 5,
			Entity::DATA_FLAG_TEMPTED => 6,
			Entity::DATA_FLAG_INLOVE => 7,
			Entity::DATA_FLAG_SADDLED => 8,
			Entity::DATA_FLAG_POWERED => 9,
			Entity::DATA_FLAG_IGNITED => 10,
			Entity::DATA_FLAG_BABY => 11,
			Entity::DATA_FLAG_CONVERTING => 12,
			Entity::DATA_FLAG_CRITICAL => 13,
			Entity::DATA_FLAG_CAN_SHOW_NAMETAG => 14,
			Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG => 15,
			Entity::DATA_FLAG_IMMOBILE => 16,
			Entity::DATA_FLAG_SILENT => 17,
			Entity::DATA_FLAG_WALLCLIMBING => 18,
			Entity::DATA_FLAG_RESTING => 19,
			Entity::DATA_FLAG_SITTING => 20,
			Entity::DATA_FLAG_ANGRY => 21,
			Entity::DATA_FLAG_INTERESTED => 22,
			Entity::DATA_FLAG_CHARGED => 23,
			Entity::DATA_FLAG_TAMED => 24,
			Entity::DATA_FLAG_LEASHED => 25,
			Entity::DATA_FLAG_SHEARED => 26,
			Entity::DATA_FLAG_GLIDING => 27,
			Entity::DATA_FLAG_ELDER => 28,
			Entity::DATA_FLAG_MOVING => 29,
			Entity::DATA_FLAG_BREATHING => 30,
			Entity::DATA_FLAG_CHESTED => 31,
			Entity::DATA_FLAG_STACKABLE => 32,
			// 33 ?
			// 34 ?
			// 35 ?
			Entity::DATA_FLAG_IDLING => 36,
		],
		110 => [
			"UNKNOWN" => -1,
			Entity::DATA_FLAG_ONFIRE => 0,
			Entity::DATA_FLAG_SNEAKING => 1,
			Entity::DATA_FLAG_RIDING => 2,
			Entity::DATA_FLAG_SPRINTING => 3,
			Entity::DATA_FLAG_ACTION => 4,
			Entity::DATA_FLAG_INVISIBLE => 5,
			Entity::DATA_FLAG_TEMPTED => 6,
			Entity::DATA_FLAG_INLOVE => 7,
			Entity::DATA_FLAG_SADDLED => 8,
			Entity::DATA_FLAG_POWERED => 9,
			Entity::DATA_FLAG_IGNITED => 10,
			Entity::DATA_FLAG_BABY => 11,
			Entity::DATA_FLAG_CONVERTING => 12,
			Entity::DATA_FLAG_CRITICAL => 13,
			Entity::DATA_FLAG_CAN_SHOW_NAMETAG => 14,
			Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG => 15,
			Entity::DATA_FLAG_IMMOBILE => 16,
			Entity::DATA_FLAG_SILENT => 17,
			Entity::DATA_FLAG_WALLCLIMBING => 18,
			Entity::DATA_FLAG_CAN_CLIMB => 19,
			Entity::DATA_FLAG_SWIMMER => 20,
			Entity::DATA_FLAG_CAN_FLY => 21,
			Entity::DATA_FLAG_RESTING => 22,
			Entity::DATA_FLAG_SITTING => 23,
			Entity::DATA_FLAG_ANGRY => 24,
			Entity::DATA_FLAG_INTERESTED => 25,
			Entity::DATA_FLAG_CHARGED => 26,
			Entity::DATA_FLAG_TAMED => 27,
			Entity::DATA_FLAG_LEASHED => 28,
			Entity::DATA_FLAG_SHEARED => 29,
			Entity::DATA_FLAG_GLIDING => 30,
			Entity::DATA_FLAG_ELDER => 31,
			Entity::DATA_FLAG_MOVING => 32,
			Entity::DATA_FLAG_BREATHING => 33,
			Entity::DATA_FLAG_CHESTED => 34,
			Entity::DATA_FLAG_STACKABLE => 35,
			Entity::DATA_FLAG_SHOWBASE => 36,
			Entity::DATA_FLAG_REARING => 37,
			Entity::DATA_FLAG_VIBRATING => 38,
			Entity::DATA_FLAG_IDLING => 39,
			Entity::DATA_FLAG_EVOKER_SPELL => 40,
			Entity::DATA_FLAG_CHARGED => 41,
			// 42 ?
			// 43 ?
			// 44 ?
			Entity::DATA_FLAG_LINGER => 45,
		],
		120 => [
			"UNKNOWN" => -1,
			Entity::DATA_FLAG_ONFIRE => 0,
			Entity::DATA_FLAG_SNEAKING => 1,
			Entity::DATA_FLAG_RIDING => 2,
			Entity::DATA_FLAG_SPRINTING => 3,
			Entity::DATA_FLAG_ACTION => 4,
			Entity::DATA_FLAG_INVISIBLE => 5,
			Entity::DATA_FLAG_TEMPTED => 6,
			Entity::DATA_FLAG_INLOVE => 7,
			Entity::DATA_FLAG_SADDLED => 8,
			Entity::DATA_FLAG_POWERED => 9,
			Entity::DATA_FLAG_IGNITED => 10,
			Entity::DATA_FLAG_BABY => 11,
			Entity::DATA_FLAG_CONVERTING => 12,
			Entity::DATA_FLAG_CRITICAL => 13,
			Entity::DATA_FLAG_CAN_SHOW_NAMETAG => 14,
			Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG => 15,
			Entity::DATA_FLAG_IMMOBILE => 16,
			Entity::DATA_FLAG_SILENT => 17,
			Entity::DATA_FLAG_WALLCLIMBING => 18,
			Entity::DATA_FLAG_CAN_CLIMB => 19,
			Entity::DATA_FLAG_SWIMMER => 20,
			Entity::DATA_FLAG_CAN_FLY => 21,
			Entity::DATA_FLAG_RESTING => 22,
			Entity::DATA_FLAG_SITTING => 23,
			Entity::DATA_FLAG_ANGRY => 24,
			Entity::DATA_FLAG_INTERESTED => 25,
			Entity::DATA_FLAG_CHARGED => 26,
			Entity::DATA_FLAG_TAMED => 27,
			Entity::DATA_FLAG_LEASHED => 28,
			Entity::DATA_FLAG_SHEARED => 29,
			Entity::DATA_FLAG_GLIDING => 30,
			Entity::DATA_FLAG_ELDER => 31,
			Entity::DATA_FLAG_MOVING => 32,
			Entity::DATA_FLAG_BREATHING => 33,
			Entity::DATA_FLAG_CHESTED => 34,
			Entity::DATA_FLAG_STACKABLE => 35,
			Entity::DATA_FLAG_SHOWBASE => 36,
			Entity::DATA_FLAG_REARING => 37,
			Entity::DATA_FLAG_VIBRATING => 38,
			Entity::DATA_FLAG_IDLING => 39,
			Entity::DATA_FLAG_EVOKER_SPELL => 40,
			Entity::DATA_FLAG_CHARGED => 41,
			Entity::DATA_FLAG_WASD_CONTROLLED => 42,
			Entity::DATA_FLAG_CAN_POWER_JUMP => 43,
			Entity::DATA_FLAG_LINGER => 44,
			Entity::DATA_FLAG_HAS_COLLISION => 45,
			Entity::DATA_FLAG_AFFECTED_BY_GRAVITY => 46,
			Entity::DATA_FLAG_FIRE_IMMUNE => 47,
			Entity::DATA_FLAG_DANCING => 48,
		]
	]; // list of data flags for all major supported protocols

	private static $entityData = [
		"default" => [
			"UNKNOWN" => -1,
			Entity::DATA_FLAGS => 0,
			Entity::DATA_HEALTH => 1,
			Entity::DATA_VARIANT => 2,
			Entity::DATA_COLOR => 3,
			Entity::DATA_NAMETAG => 4,
			Entity::DATA_OWNER_EID => 5,
			// 6 ?
			Entity::DATA_AIR => 7,
			Entity::DATA_POTION_COLOR => 8,
			Entity::DATA_POTION_AMBIENT => 9,
			// 10 ?
			// 11 ?
			// 12 ?
			// 13 ?
			// 14 ?
			// 15 ?
			// 16 ?
			// 17 ?
			// 18 ?
			// 19 ?
			// 20 ?
			// 21 ?
			// 22 ?
			Entity::DATA_ENDERMAN_HELD_ITEM_ID => 23,
			Entity::DATA_ENDERMAN_HELD_ITEM_DAMAGE => 24,
			Entity::DATA_ENTITY_AGE => 25,
			// 26 ?
			// 27 ?
			// 28 ?
			// 29 ?
			// 30 ?
			// 31 ?
			// 32 ?
			// 33 ?
			// 34 ?
			// 35 ?
			// 36 ?
			Entity::DATA_POTION_AUX_VALUE => 37,
			Entity::DATA_LEAD_HOLDER_EID => 38,
			Entity::DATA_SCALE => 39,
			Entity::DATA_INTERACTIVE_TAG => 40,
			// 41 ?
			// 42 ?
			Entity::DATA_URL_TAG => 43,
			Entity::DATA_MAX_AIR => 44,
			Entity::DATA_MARK_VARIANT => 45,
			// 46 ?
			// 47 ?
			// 48 ?
			// 49 ?
			// 50 ?
			// 51 ?
			// 52 ?
			// 53 ?
			Entity::DATA_BOUNDING_BOX_WIDTH => 54,
			Entity::DATA_BOUNDING_BOX_HEIGHT => 55,
			Entity::DATA_FUSE_LENGTH => 56,
			Entity::DATA_RIDER_SEAT_POSITION => 57,
			Entity::DATA_RIDER_ROTATION_LOCKED => 58,
			Entity::DATA_RIDER_MAX_ROTATION => 59,
			Entity::DATA_RIDER_MIN_ROTATION => 60,
		],
		110 => [
			"UNKNOWN" => -1,
			Entity::DATA_FLAGS => 0,
			Entity::DATA_HEALTH => 1,
			Entity::DATA_VARIANT => 2,
			Entity::DATA_COLOR => 3,
			Entity::DATA_NAMETAG => 4,
			Entity::DATA_OWNER_EID => 5,
			Entity::DATA_TARGET_EID => 6,
			Entity::DATA_AIR => 7,
			Entity::DATA_POTION_COLOR => 8,
			Entity::DATA_POTION_AMBIENT => 9,
			// 10 ?
			Entity::DATA_HURT_TIME => 11,
			Entity::DATA_HURT_DIRECTION => 12,
			Entity::DATA_PADDLE_TIME_LEFT => 13,
			Entity::DATA_PADDLE_TIME_RIGHT => 14,
			Entity::DATA_EXPERIENCE_VALUE => 15,
			Entity::DATA_MINECART_DISPLAY_BLOCK => 16,
			Entity::DATA_MINECART_DISPLAY_OFFSET => 17,
			Entity::DATA_MINECART_HAS_DISPLAY => 18,
			// 19 ?
			// 20 ?
			// 21 ?
			// 22 ?
			Entity::DATA_ENDERMAN_HELD_ITEM_ID => 23,
			Entity::DATA_ENDERMAN_HELD_ITEM_DAMAGE => 24,
			Entity::DATA_ENTITY_AGE => 25,
			// 26 ?
			// 27 ?
			// 28 ?
			// 29 ?
			Entity::DATA_FIREBALL_POWER_X => 30,
			Entity::DATA_FIREBALL_POWER_Y => 31,
			Entity::DATA_FIREBALL_POWER_Z => 32,
			// 33 ?
			// 34 ?
			// 35 ?
			// 36 ?
			Entity::DATA_POTION_AUX_VALUE => 37,
			Entity::DATA_LEAD_HOLDER_EID => 38,
			Entity::DATA_SCALE => 39,
			Entity::DATA_INTERACTIVE_TAG => 40,
			Entity::DATA_NPC_SKIN_ID => 41,
			Entity::DATA_URL_TAG => 42,
			Entity::DATA_MAX_AIR => 43,
			Entity::DATA_MARK_VARIANT => 44,
			// 45 ?
			// 46 ?
			// 47 ?
			Entity::DATA_BLOCK_TARGET => 48,
			Entity::DATA_WITHER_INVULNERABLE_TICKS => 49,
			Entity::DATA_WITHER_TARGET_1 => 50,
			Entity::DATA_WITHER_TARGET_2 => 51,
			Entity::DATA_WITHER_TARGET_3 => 52,
			// 53 ?
			Entity::DATA_BOUNDING_BOX_WIDTH => 54,
			Entity::DATA_BOUNDING_BOX_HEIGHT => 55,
			Entity::DATA_FUSE_LENGTH => 56,
			Entity::DATA_RIDER_SEAT_POSITION => 57,
			Entity::DATA_RIDER_ROTATION_LOCKED => 58,
			Entity::DATA_RIDER_MAX_ROTATION => 59,
			Entity::DATA_RIDER_MIN_ROTATION => 60,
			Entity::DATA_AREA_EFFECT_CLOUD_RADIUS => 61,
			Entity::DATA_AREA_EFFECT_CLOUD_WAITING => 62,
			Entity::DATA_AREA_EFFECT_CLOUD_PARTICLE_ID => 63,
			// 64 ?
			Entity::DATA_SHULKER_ATTACH_FACE => 65,
			// 66 ?
			Entity::DATA_SHULKER_ATTACH_POS => 67,
			Entity::DATA_TRADING_PLAYER_EID => 68,
			// 69 ?
			// 70 ?
			Entity::DATA_COMMAND_BLOCK_COMMAND => 71,
			Entity::DATA_COMMAND_BLOCK_LAST_OUTPUT => 72,
			Entity::DATA_COMMAND_BLOCK_TRACK_OUTPUT => 73,
			Entity::DATA_CONTROLLING_RIDER_SEAT_NUMBER => 74,
			Entity::DATA_STRENGTH => 75,
			Entity::DATA_MAX_STRENGTH => 76,
			// 77 ?
			// 78 ?
		],
		120 => [
			"UNKNOWN" => -1,
			Entity::DATA_FLAGS => 0,
			Entity::DATA_HEALTH => 1,
			Entity::DATA_VARIANT => 2,
			Entity::DATA_COLOR => 3,
			Entity::DATA_NAMETAG => 4,
			Entity::DATA_OWNER_EID => 5,
			Entity::DATA_TARGET_EID => 6,
			Entity::DATA_AIR => 7,
			Entity::DATA_POTION_COLOR => 8,
			Entity::DATA_POTION_AMBIENT => 9,
			// 10 ?
			Entity::DATA_HURT_TIME => 11,
			Entity::DATA_HURT_DIRECTION => 12,
			Entity::DATA_PADDLE_TIME_LEFT => 13,
			Entity::DATA_PADDLE_TIME_RIGHT => 14,
			Entity::DATA_EXPERIENCE_VALUE => 15,
			Entity::DATA_MINECART_DISPLAY_BLOCK => 16,
			Entity::DATA_MINECART_DISPLAY_OFFSET => 17,
			Entity::DATA_MINECART_HAS_DISPLAY => 18,
			// 19 ?
			// 20 ?
			// 21 ?
			// 22 ?
			Entity::DATA_ENDERMAN_HELD_ITEM_ID => 23,
			Entity::DATA_ENDERMAN_HELD_ITEM_DAMAGE => 24,
			Entity::DATA_ENTITY_AGE => 25,
			// 26 ?
			// 27 ?
			// 28 ?
			// 29 ?
			Entity::DATA_FIREBALL_POWER_X => 30,
			Entity::DATA_FIREBALL_POWER_Y => 31,
			Entity::DATA_FIREBALL_POWER_Z => 32,
			// 33 ?
			// 34 ?
			// 35 ?
			// 36 ?
			Entity::DATA_POTION_AUX_VALUE => 37,
			Entity::DATA_LEAD_HOLDER_EID => 38,
			Entity::DATA_SCALE => 39,
			Entity::DATA_INTERACTIVE_TAG => 40,
			Entity::DATA_NPC_SKIN_ID => 41,
			Entity::DATA_URL_TAG => 42,
			Entity::DATA_MAX_AIR => 43,
			Entity::DATA_MARK_VARIANT => 44,
			// 45 ?
			// 46 ?
			// 47 ?
			Entity::DATA_BLOCK_TARGET => 48,
			Entity::DATA_WITHER_INVULNERABLE_TICKS => 49,
			Entity::DATA_WITHER_TARGET_1 => 50,
			Entity::DATA_WITHER_TARGET_2 => 51,
			Entity::DATA_WITHER_TARGET_3 => 52,
			// 53 ?
			Entity::DATA_BOUNDING_BOX_WIDTH => 54,
			Entity::DATA_BOUNDING_BOX_HEIGHT => 55,
			Entity::DATA_FUSE_LENGTH => 56,
			Entity::DATA_RIDER_SEAT_POSITION => 57,
			Entity::DATA_RIDER_ROTATION_LOCKED => 58,
			Entity::DATA_RIDER_MAX_ROTATION => 59,
			Entity::DATA_RIDER_MIN_ROTATION => 60,
			Entity::DATA_AREA_EFFECT_CLOUD_RADIUS => 61,
			Entity::DATA_AREA_EFFECT_CLOUD_WAITING => 62,
			Entity::DATA_AREA_EFFECT_CLOUD_PARTICLE_ID => 63,
			// 64 ?
			Entity::DATA_SHULKER_ATTACH_FACE => 65,
			// 66 ?
			Entity::DATA_SHULKER_ATTACH_POS => 67,
			Entity::DATA_TRADING_PLAYER_EID => 68,
			// 69 ?
			// 70 ?
			Entity::DATA_COMMAND_BLOCK_COMMAND => 71,
			Entity::DATA_COMMAND_BLOCK_LAST_OUTPUT => 72,
			Entity::DATA_COMMAND_BLOCK_TRACK_OUTPUT => 73,
			Entity::DATA_CONTROLLING_RIDER_SEAT_NUMBER => 74,
			Entity::DATA_STRENGTH => 75,
			Entity::DATA_MAX_STRENGTH => 76,
			// 77 ?
			// 78 ?
		]
	]; // list of entity data for all major supported protocols

	/**
	 * Convert server-side entity data to client readable data
	 *
	 * @param array $data
	 * @param int $protocol
	 *
	 * @return array
	 */
	public static function writeMetadata(array $data, int $protocol) : array {
		if(!isset(self::$entityFlags[$protocol])) {
			$protocol = "default";
		}

		$metadata = [];
		foreach($data as $metaName => $metaValue) {
			if($metaName === Entity::DATA_FLAGS and is_array($metaValue[1])) {
				$metaValue[1] = self::writeDataFlags($metaValue[1], $protocol);
			}
			if(isset(self::$entityData[$protocol][$metaName])) { // only send the metadata to clients which are compatible
				$metadata[self::$entityData[$protocol][$metaName]] = $metaValue;
			}
		}
		return $metadata;
	}

	/**
	 * Convert server-side entity data flags to client readable data
	 *
	 * @param array $flagData
	 * @param int|string $protocol
	 *
	 * @return int
	 */
	private static function writeDataFlags(array $flagData, $protocol) : int {
		$flags = 0;
		foreach($flagData as $flagName => $flagValue) {
			if(isset(self::$entityFlags[$protocol][$flagName])) { // only send the flag to clients which are compatible
				$flags = ((int) $flags) ^ (1 << self::$entityFlags[$protocol][$flagName]);
			}
		}
		return $flags;
	}

}
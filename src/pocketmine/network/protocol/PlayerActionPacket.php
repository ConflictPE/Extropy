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

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>


class PlayerActionPacket extends PEPacket{
	const NETWORK_ID = Info::PLAYER_ACTION_PACKET;
	const PACKET_NAME = "PLAYER_ACTION_PACKET";

	const ACTION_START_BREAK = "START_DESTROY_BLOCK";
	const ACTION_ABORT_BREAK = "ABORT_DESTROY_BLOCK";
	const ACTION_STOP_BREAK = "STOP_DESTROY_BLOCK";
	const ACTION_UPDATE_BLOCK = "GET_UPDATED_BLOCK";
	const ACTION_DROP_ITEM = "DROP_ITEM";
	const ACTION_RELEASE_ITEM = "RELEASE_USE_ITEM";
	const ACTION_START_SLEEPING = "START_SLEEPING";
	const ACTION_STOP_SLEEPING = "STOP_SLEEPING";
	const ACTION_RESPAWN = "RESPAWN";
	const ACTION_JUMP = "JUMP";
	const ACTION_START_SPRINT = "START_SPRINTING";
	const ACTION_STOP_SPRINT = "STOP_SPRINTING";
	const ACTION_START_SNEAK = "START_SNEAKING";
	const ACTION_STOP_SNEAK = "STOP_SNEAKING";
	const ACTION_DIMENSION_CHANGE = "CHANGE_DIMENSION";
	const ACTION_DIMENSION_CHANGE_ACK = "CHANGE_DIMENSION_ACK";
	const ACTION_START_GLIDE = "START_GLIDING";
	const ACTION_STOP_GLIDE = "STOP_GLIDING";
	const ACTION_BUILD_DENIED = "DENY_DESTROY_BLOCK";
	const ACTION_CONTINUE_BREAK = "CRACK_BLOCK";
	const ACTION_CHANGE_SKIN = "CHANGE_SKIN";

	public $eid;
	public $action;
	public $x;
	public $y;
	public $z;
	public $face;

	public function decode($playerProtocol){
		$this->eid = $this->getEntityRuntimeId();
		$this->action = $this->getSignedVarInt();
		$this->x = $this->getSignedVarInt();
		$this->y = $this->getVarInt();
		$this->z = $this->getSignedVarInt();
		$this->face = $this->getVarInt();
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putEntityRuntimeId($this->eid);
		$this->putSignedVarInt($this->action);
		$this->putSignedVarInt($this->x);
		$this->putVarInt($this->y);
		$this->putSignedVarInt($this->z);
		$this->putVarInt($this->face);
	}

}

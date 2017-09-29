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

namespace pocketmine\network\protocol\v120;

#include <rules/DataPacket.h>


use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;
use pocketmine\utils\Binary;
use pocketmine\utils\UUID;

class SubClientLoginPacket extends PEPacket {

	const NETWORK_ID = Info120::SUB_CLIENT_LOGIN_PACKET;
	const PACKET_NAME = "SUB_CLIENT_LOGIN_PACKET";

	public $username;
	public $clientId;
	public $clientUUID;
	public $clientSecret;
	public $skinName;
	public $chainsDataLength;
	public $chains;
	public $playerDataLength;
	public $playerData;
	public $inventoryType = -1;
	public $xuid = "";
	public $skinGeometryName = "";
	public $skinGeometryData = "";
	public $capeData = "";

	private function getFromString(&$body, $len) {
		$res = substr($body, 0, $len);
		$body = substr($body, $len);
		return $res;
	}

	public function decode(int $playerProtocol) {
		$this->getHeader($playerProtocol);
		$body = $this->getString();
		$this->chainsDataLength = Binary::readLInt($this->getFromString($body, 4));
		$this->chains = json_decode($this->getFromString($body, $this->chainsDataLength), true);

		$this->playerDataLength = Binary::readLInt($this->getFromString($body, 4));
		$this->playerData = $this->getFromString($body, $this->playerDataLength);

		$this->chains["data"] = array();
		$index = 0;
		foreach($this->chains["chain"] as $key => $jwt) {
			$data = self::load($jwt);
			if(isset($data["extraData"])) {
				$dataIndex = $index;
			}
			$this->chains["data"][$index] = $data;
			$index++;
		}

		$this->playerData = self::load($this->playerData);
		$this->username = $this->chains["data"][$dataIndex]["extraData"]["displayName"];
		$this->clientId = $this->chains["data"][$dataIndex]["extraData"]["identity"];
		$this->clientUUID = UUID::fromString($this->chains["data"][$dataIndex]["extraData"]["identity"]);
		$this->identityPublicKey = $this->chains["data"][$dataIndex]["identityPublicKey"];
		if(isset($this->chains["data"][$dataIndex]["extraData"]["XUID"])) {
			$this->xuid = $this->chains["data"][$dataIndex]["extraData"]["XUID"];
		}

		$this->skinName = $this->playerData["SkinId"];
		$this->skin = base64_decode($this->playerData["SkinData"]);
		if(isset($this->playerData["SkinGeometryName"])) {
			$this->skinGeometryName = $this->playerData["SkinGeometryName"];
		}
		if(isset($this->playerData["SkinGeometry"])) {
			$this->skinGeometryData = base64_decode($this->playerData["SkinGeometry"]);
		}
		$this->clientSecret = $this->playerData["ClientRandomId"];
		if(isset($this->playerData["UIProfile"])) {
			$this->inventoryType = $this->playerData["UIProfile"];
		}
		if(isset($this->playerData["CapeData"])) {
			$this->capeData = base64_decode($this->playerData["CapeData"]);
		}
	}

	public function encode(int $playerProtocol) {

	}

	public static function load($jwsTokenString) {
		$parts = explode(".", $jwsTokenString);
		if (isset($parts[1])) {
			$payload = json_decode(base64_decode(strtr($parts[1], "-_", "+/")), true);
			return $payload;
		}
		return "";
	}

}
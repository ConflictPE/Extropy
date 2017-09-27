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


use pocketmine\utils\Binary;
use pocketmine\utils\UUID;

class LoginPacket extends PEPacket {

	const NETWORK_ID = Info::LOGIN_PACKET;
	const PACKET_NAME = "LOGIN_PACKET";

	public $username;
	public $protocol1;
	public $protocol2;
	public $gameEdition;
	public $clientId;
	public $clientUUID;
	public $serverAddress;
	public $clientSecret;
	public $slim = false;
	public $skinName;
	public $chains;
	public $playerData;
	public $isValidProtocol = true;
	public $inventoryType = -1;
	public $osType = -1;
	public $xuid = "";
	public $languageCode = "unknown";
	public $clientVersion = "unknown";
	public $originalProtocol;
	public $skinGeometryName = "";
	public $skinGeometryData = "";
	public $capeData = "";

	private function getFromString(&$body, $len) {
		$res = substr($body, 0, $len);
		$body = substr($body, $len);
		return $res;
	}

	public function decode(int $playerProtocol) {
		if(Binary::readInt(substr($this->buffer, 1, 4)) === 0) {
			$this->getShort(); // ???
		}

		$this->protocol1 = $this->getInt();
		if(!in_array($this->protocol1, Info::ACCEPTED_PROTOCOLS)) {
			$this->isValidProtocol = false;
			return;
		}
		if($this->protocol1 < Info::PROTOCOL_120) {
			$this->gameEdition = $this->getByte();
		}
		$data = $this->getString();
		if($this->protocol1 >= Info::PROTOCOL_110) {
			if (ord($data{0}) != 120 || (($decodedData = @zlib_decode($data)) === false)) {
				$body = $data;
			} else {
				$body = $decodedData;
			}
		} else {
			$body = \zlib_decode($data);
		}

		$this->chains = json_decode($this->getFromString($body, Binary::readLInt($this->getFromString($body, 4))), true);

		$this->playerData = $this->getFromString($body, Binary::readLInt($this->getFromString($body, 4)));

		$this->chains["data"] = [];
		$index = 0;
		foreach($this->chains["chain"] as $key => $jwt) {
			$data = self::load($jwt);
			if(isset($data["extraData"])) {
				$dataIndex = $index;
			}
			$this->chains["data"][$index] = $data;
			$index++;
		}

		if(!isset($dataIndex)) {
			$this->isValidProtocol = false;
			return;
		}

		$this->playerData = self::load($this->playerData);
		$this->username = $this->chains["data"][$dataIndex]["extraData"]["displayName"];
		$this->clientId = $this->chains["data"][$dataIndex]["extraData"]["identity"];
		$this->clientUUID = UUID::fromString($this->chains["data"][$dataIndex]["extraData"]["identity"]);
		$this->identityPublicKey = $this->chains["data"][$dataIndex]["identityPublicKey"];
		if(isset($this->chains["data"][$dataIndex]["extraData"]["XUID"])) {
			$this->xuid = $this->chains["data"][$dataIndex]["extraData"]["XUID"];
		}

		$this->serverAddress = $this->playerData["ServerAddress"];
		$this->skinName = $this->playerData["SkinId"];
		$this->skin = base64_decode($this->playerData["SkinData"]);

		if(isset($this->playerData["SkinGeometryName"])) {
			$this->skinGeometryName = $this->playerData["SkinGeometryName"];
		}

		if(isset($this->playerData["SkinGeometry"])) {
			$this->skinGeometryData = base64_decode($this->playerData["SkinGeometry"]);
		}

		$this->clientSecret = $this->playerData["ClientRandomId"];
		if(isset($this->playerData["DeviceOS"])) {
			$this->osType = $this->playerData["DeviceOS"];
		}

		if(isset($this->playerData["UIProfile"])) {
			$this->inventoryType = $this->playerData["UIProfile"];
		}

		if(isset($this->playerData["LanguageCode"])) {
			$this->languageCode = $this->playerData["LanguageCode"];
		}

		if(isset($this->playerData["GameVersion"])) {
			$this->clientVersion = $this->playerData["GameVersion"];
		}

		if(isset($this->playerData["CapeData"])) {
			$this->capeData = base64_decode($this->playerData["CapeData"]);
		}

		$this->originalProtocol = $this->protocol1;
		$this->protocol1 = self::convertProtocol($this->protocol1);
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
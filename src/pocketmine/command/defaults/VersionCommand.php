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

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\network\protocol\Info;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class VersionCommand extends VanillaCommand{

	public function __construct($name){
		parent::__construct(
			$name,
			"Gets the version of this server including any plugins in use",
			"/version",
			["ver", "about"]
		);
		$this->setPermission("pocketmine.command.version");
	}

	public function execute(CommandSender $sender, $currentAlias, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) === 0){
			$output = TextFormat::YELLOW . "This server is running " . TextFormat::GREEN . $sender->getServer()->getName() . TextFormat::YELLOW . " version " . TextFormat::DARK_GREEN . $sender->getServer()->getPocketMineVersion() . TextFormat::GRAY . " 「" . TextFormat::AQUA . $sender->getServer()->getCodename() . TextFormat::GRAY ."」 " . TextFormat::YELLOW . "for Minecraft: PE " . TextFormat::GOLD . $sender->getServer()->getVersion();
			if(\pocketmine\GIT_COMMIT !== str_repeat("00", 20)){
				$output .= TextFormat::GRAY . " [git " . \pocketmine\GIT_COMMIT . "]";
			}
			$sender->sendMessage($output);
		}else{
			$pluginName = implode(" ", $args);
			$exactPlugin = $sender->getServer()->getPluginManager()->getPlugin($pluginName);

			if($exactPlugin instanceof Plugin){
				$this->describeToSender($exactPlugin, $sender);
				return true;
			}

			$found = false;
			$pluginName = strtolower($pluginName);
			foreach($sender->getServer()->getPluginManager()->getPlugins() as $plugin){
				if(stripos($plugin->getName(), $pluginName) !== false){
					$this->describeToSender($plugin, $sender);
					$found = true;
				}
			}

			if(!$found){
				$sender->sendMessage(TextFormat::RED . "This server is not running any plugin by that name.\nUse /plugins to get a list of plugins.");
			}
		}

		return true;
	}

	private function describeToSender(Plugin $plugin, CommandSender $sender){
		$desc = $plugin->getDescription();
		$lineBreak = $sender instanceof ConsoleCommandSender ? PHP_EOL : "\n";
		$message = TextFormat::GREEN . $desc->getName() . TextFormat::YELLOW . " version " . TextFormat::DARK_GREEN . $desc->getVersion() . TextFormat::RESET . $lineBreak;

		if($desc->getDescription() != null){
			$message .= $desc->getDescription() . TextFormat::RESET . "\n";
		}

		if($desc->getWebsite() != null){
			$message .= TextFormat::YELLOW . "Website" . TextFormat::GRAY . ": " . TextFormat::LIGHT_PURPLE . $desc->getWebsite() . TextFormat::RESET . $lineBreak;
		}

		if(count($authors = $desc->getAuthors()) > 0){
			$message .= TextFormat::YELLOW . "Author" . (count($authors) > 1 ? "s" : "") . TextFormat::GRAY . ": " . TextFormat::AQUA . implode(TextFormat::DARK_AQUA . ", " . TextFormat::AQUA, $authors) . TextFormat::RESET;
		}

		$sender->sendMessage($message);
	}

}
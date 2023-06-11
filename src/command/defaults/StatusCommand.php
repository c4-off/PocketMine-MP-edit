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

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\utils\Process;
use pocketmine\utils\TextFormat;
use function count;
use function floor;
use function microtime;
use function number_format;
use function round;

class StatusCommand extends VanillaCommand{

	public function __construct(){
		parent::__construct(
			"status",
			KnownTranslationFactory::pocketmine_command_status_description()
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_STATUS);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		$mUsage = Process::getAdvancedMemoryUsage();

		$server = $sender->getServer();
		$sender->sendMessage(TextFormat::GREEN . "---- " . TextFormat::RESET . "Статус сервер" . TextFormat::GREEN . " ----");

		$time = (int) (microtime(true) - $server->getStartTime());

		$seconds = $time % 60;
		$minutes = null;
		$hours = null;
		$days = null;

		if($time >= 60){
			$minutes = floor(($time % 3600) / 60);
			if($time >= 3600){
				$hours = floor(($time % (3600 * 24)) / 3600);
				if($time >= 3600 * 24){
					$days = floor($time / (3600 * 24));
				}
			}
		}

		$uptime = ($minutes !== null ?
				($hours !== null ?
					($days !== null ?
						"$days days "
					: "") . "$hours hours "
					: "") . "$minutes minutes "
			: "") . "$seconds seconds";

		$sender->sendMessage(TextFormat::RESET . "Работает » " . TextFormat::GREEN . $uptime);

		$tpsColor = TextFormat::GREEN;
		if($server->getTicksPerSecond() < 12){
			$tpsColor = TextFormat::GREEN;
		}elseif($server->getTicksPerSecond() < 17){
			$tpsColor = TextFormat::RED;
		}

		$sender->sendMessage(TextFormat::RESET . "Current TPS » {$tpsColor}{$server->getTicksPerSecond()} ({$server->getTickUsage()}%)");
		$sender->sendMessage(TextFormat::RESET . "Average TPS » {$tpsColor}{$server->getTicksPerSecondAverage()} ({$server->getTickUsageAverage()}%)");

		$bandwidth = $server->getNetwork()->getBandwidthTracker();
		$sender->sendMessage(TextFormat::RESET . "Network upload » " . TextFormat::GREEN . round($bandwidth->getSend()->getAverageBytes() / 1024, 2) . " kB/s");
		$sender->sendMessage(TextFormat::RESET . "Network download » " . TextFormat::GREEN . round($bandwidth->getReceive()->getAverageBytes() / 1024, 2) . " kB/s");

		$sender->sendMessage(TextFormat::RESET . "Thread count » " . TextFormat::GREEN . Process::getThreadCount());

		$sender->sendMessage(TextFormat::RESET . "Main thread memory » " . TextFormat::GREEN . number_format(round(($mUsage[0] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage(TextFormat::RESET . "Total memory » " . TextFormat::GREEN . number_format(round(($mUsage[1] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage(TextFormat::RESET . "Total virtual memory » " . TextFormat::GREEN . number_format(round(($mUsage[2] / 1024) / 1024, 2), 2) . " MB.");

		$globalLimit = $server->getMemoryManager()->getGlobalMemoryLimit();
		if($globalLimit > 0){
			$sender->sendMessage(TextFormat::RESET . "Maximum memory (manager) » " . TextFormat::GREEN . number_format(round(($globalLimit / 1024) / 1024, 2), 2) . " MB.");
		}

		foreach($server->getWorldManager()->getWorlds() as $world){
			$worldName = $world->getFolderName() !== $world->getDisplayName() ? " (" . $world->getDisplayName() . ")" : "";
			$timeColor = $world->getTickRateTime() > 40 ? TextFormat::GREEN : TextFormat::YELLOW;
			$sender->sendMessage(TextFormat::RESET . "World \"{$world->getFolderName()}\"$worldName » " .
				TextFormat::GREEN . number_format(count($world->getLoadedChunks())) . TextFormat::GREEN . " loaded chunks, " .
				TextFormat::GREEN . number_format(count($world->getTickingChunks())) . TextFormat::GREEN . " ticking chunks, " .
				TextFormat::GREEN . number_format(count($world->getEntities())) . TextFormat::GREEN . " entities. " .
				"Time $timeColor" . round($world->getTickRateTime(), 2) . "ms"
			);
		}

		return true;
	}
}

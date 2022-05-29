<?php

declare(strict_types=1);

namespace alvin0319\AFK;

use OnixUtils\OnixUtils;
use pocketmine\entity\Location;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;

class AFK extends PluginBase implements Listener{
	use SingletonTrait;

	/** @var bool[] */
	protected array $afkQueue = [];

	/** @var int[] */
	protected array $noMoveTick = [];

	/** @var Location[] */
	protected array $lastPositionQueue = [];

	public function onLoad() : void{
		self::$instance = $this;
	}

	protected function onEnable() : void{
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
			foreach($this->getServer()->getOnlinePlayers() as $player){
				if(!isset($this->lastPositionQueue[$player->getName()]) || !isset($this->noMoveTick[$player->getName()]) || !isset($this->afkQueue[$player->getName()])){
					continue;
				}
				$lastPosition = $this->lastPositionQueue[$player->getName()] ?? clone $player->getLocation();
				$currentPosition = $player->getLocation();

				if($lastPosition->equals($currentPosition)){
					$this->noMoveTick[$player->getName()] += 1;
					if($this->noMoveTick[$player->getName()] >= 300){
						if(!$this->afkQueue[$player->getName()]){
							$this->afkQueue[$player->getName()] = true;
							OnixUtils::message($player, "잠수 모드가 활성화되었습니다.");
							$player->sendTitle("§c§l[ §f! §c]", "잠수 모드가 활성화되었습니다.");
						}
					}
				}else{
					if(($this->afkQueue[$player->getName()] ?? false) !== false){
						$this->afkQueue[$player->getName()] = false;
						$player->sendTitle("§c§l[ §f! §c]", "움직여서 잠수 모드가 해제됐습니다.");
						OnixUtils::message($player, "총 잠수 시간은 " . OnixUtils::convertTimeToString($this->noMoveTick[$player->getName()] - 300) . " 입니다.");
					}
					if(($this->noMoveTick[$player->getName()] ?? 0) !== 0){
						$this->noMoveTick[$player->getName()] = 0;
					}
					$this->lastPositionQueue[$player->getName()] = clone $player->getLocation();
				}
			}
		}), 20);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$this->afkQueue[$player->getName()] = false;
		$this->noMoveTick[$player->getName()] = 0;
		$this->lastPositionQueue[$player->getName()] = clone $player->getLocation();
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if(isset($this->afkQueue[$player->getName()])){
			unset($this->afkQueue[$player->getName()]);
		}
		if(isset($this->noMoveTick[$player->getName()])){
			unset($this->noMoveTick[$player->getName()]);
		}
		if(isset($this->lastPositionQueue[$player->getName()])){
			unset($this->lastPositionQueue[$player->getName()]);
		}
	}

	public function isAFK(Player $player) : bool{
		if(!isset($this->afkQueue[$player->getName()]) || !isset($this->noMoveTick[$player->getName()])){
			return false;
		}
		return ($this->afkQueue[$player->getName()] && $this->noMoveTick[$player->getName()] >= 300) || $this->noMoveTick[$player->getName()] >= 300;
	}
}
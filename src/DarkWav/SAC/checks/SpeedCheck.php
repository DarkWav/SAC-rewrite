<?php
declare(strict_types=1);
namespace DarkWav\SAC\checks;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2019 DarkWav
 */

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\event\Cancellable;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;
use DarkWav\SAC\Analyzer;

class SpeedCheck
{
  public $Analyzer;
  public $MaxSpeed;
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer   = $ana;
    $this->MaxSpeed   = $this->Analyzer->Main->Config->get("MaxSpeed");
  }
  public function run($event) : void
  {
    $name = $this->Analyzer->PlayerName;
    $speed = $this->Analyzer->XZSpeed;
    #$this->Analyzer->Player->sendMessage(TextFormat::ESCAPE.$this->Analyzer->Colorized."[SAC] > $name, you are being checked for Speed!");
    $this->Analyzer->Logger->info(TextFormat::ESCAPE.$this->Analyzer->Colorized."[SAC] > $name is running at $speed blocks per second!");
    if($speed > $this->MaxSpeed)
    {
      $event->setCancelled(true);
      $this->Analyzer->kickPlayer("[SAC] > Speed!");
    }
    #TODO
  }
}

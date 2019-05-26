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
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer   = $ana;
  }
  public function run($event) : void
  {
    $name = $this->Analyzer->PlayerName;
    $this->Analyzer->Player->sendMessage(TextFormat::ESCAPE.$this->Analyzer->Colorized."[SAC] > $name, you are being checked for Speed!");
    $event->setCancelled(true);
    $this->Analyzer->kickPlayer("[SAC] > Test Kick Message!");
    #TODO
  }
}

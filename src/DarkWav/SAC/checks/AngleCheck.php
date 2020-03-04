<?php
declare(strict_types=1);
namespace DarkWav\SAC\checks;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2020 DarkWav and others.
 */

use pocketmine\Player;
use pocketmine\utils\TextFormat;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;
use DarkWav\SAC\Analyzer;

class AngleCheck
{
  public $Analyzer;
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer    = $ana;
    $this->Counter     = 0;
    $this->Threshold   = $this->Analyzer->Main->Config->get("Angle.Threshold");
    $this->Limit       = $this->Analyzer->Main->Config->get("Angle.Limit");
    $this->MinDistance = $this->Analyzer->Main->Config->get("Angle.MinDistance");
  }
  public function run($event) : void
  {
    if (!$this->Analyzer->Main->Config->get("Angle")) return;
    $name = $this->Analyzer->PlayerName;
    if (($this->Analyzer->AngleXZ > $this->Limit) and ($this->Analyzer->hitDistanceXZ >= $this->MinDistance))
    {
      $event->setCancelled(true);
      $this->Counter+=3;
    }
    else
    {
      $this->Counter--;
    }
    if(($this->Counter >= ($this->Threshold)*3) and ($this->Analyzer->Main->Config->get("Angle.Punishment") == "kick"))
    {
      $this->Analyzer->kickPlayer($this->Analyzer->Main->Config->get("Angle.KickMessage"));
      $this->Counter = 0;
    }
  }
}

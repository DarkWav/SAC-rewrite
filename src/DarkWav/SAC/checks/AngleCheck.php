<?php
declare(strict_types=1);
namespace DarkWav\SAC\checks;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;
use DarkWav\SAC\Analyzer;

class AngleCheck
{
  /** @var Analyzer */
  public Analyzer $Analyzer;
  /** @var int */
  public int $Counter;
  /** @var int */
  public int $Threshold;
  /** @var float */
  public float $Limit;
  /** @var float */
  public float $MinDistance;

  /**
   * AngleCheck constructor.
   * @param Analyzer $ana
   */
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer    = $ana;
    $this->Counter     = 0;
    $this->Threshold   = $this->Analyzer->Main->Config->get("Angle.Threshold");
    $this->Limit       = $this->Analyzer->Main->Config->get("Angle.Limit");
    $this->MinDistance = $this->Analyzer->Main->Config->get("Angle.MinDistance");
  }

  /**
   * @param EntityDamageByEntityEvent $event
   */
  public function run(EntityDamageByEntityEvent $event) : void
  {
    if (!$this->Analyzer->Main->Config->get("Angle")) return;
    $name = $this->Analyzer->PlayerName;
    if (($this->Analyzer->hitAngleXZ > $this->Limit) and ($this->Analyzer->hitDistanceXZ >= $this->MinDistance))
    {
      $event->cancel();
      $this->Counter+=3;
    }
    elseif($this->Counter > 0)
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

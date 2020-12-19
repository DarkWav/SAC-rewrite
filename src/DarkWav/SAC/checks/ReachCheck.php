<?php
declare(strict_types=1);
namespace DarkWav\SAC\checks;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use DarkWav\SAC\Analyzer;

use pocketmine\event\entity\EntityDamageByEntityEvent;

class ReachCheck
{
  /** @var Analyzer */
  public Analyzer $Analyzer;
  /** @var float */
  public float $Range;
  /** @var float */
  public float $MaxRange;
  /** @var int */
  public int $Counter;
  /** @var int */
  public int $Threshold;

  /**
   * ReachCheck constructor.
   * @param Analyzer $ana
   */
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer   = $ana;
    $this->Counter    = 0;
    $this->MaxRange   = $this->Analyzer->Main->Config->get("Reach.Limit");
    $this->Threshold  = $this->Analyzer->Main->Config->get("Reach.Threshold");
  }

  /**
   * @param EntityDamageByEntityEvent $event
   */
  public function run(EntityDamageByEntityEvent $event) : void
  {
    if (!$this->Analyzer->Main->Config->get("Reach")) return;
    $name        = $this->Analyzer->PlayerName;
    $this->Range = $this->Analyzer->hitDistance;
    if($this->Range > $this->MaxRange)
    {
      $event->setCancelled(true);
      $this->Counter+=3;
    }
    elseif($this->Counter > 0)
    {
      $this->Counter--;
    }
    if(($this->Counter >= ($this->Threshold)*3) and ($this->Analyzer->Main->Config->get("Reach.Punishment") == "kick"))
    {
      $this->Analyzer->kickPlayer($this->Analyzer->Main->Config->get("Reach.KickMessage"));
      $this->Counter = 0;
    }
  }
}

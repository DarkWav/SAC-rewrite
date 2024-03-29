<?php
declare(strict_types=1);
namespace DarkWav\SAC\checks;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;
use pocketmine\player\GameMode;
use pocketmine\entity\effect\VanillaEffects;

use DarkWav\SAC\Analyzer;

class SpeedCheck
{
  /** @var Analyzer */
  public Analyzer $Analyzer;
  /** @var float */
  public float $MaxSpeed;
  /** @var float */
  public float $ConfiguredSpeed;
  /** @var int */
  public int $Threshold;
  /** @var int */
  public int $Counter;
  /** @var float */
  public float $Leniency;
  /** @var int */
  public int $MotionSeconds;

  /**
   * SpeedCheck constructor.
   * @param Analyzer $ana
   */
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer        = $ana;
    $this->MaxSpeed        = 0.0;
    $this->ConfiguredSpeed = $this->Analyzer->Main->Config->get("Speed.MaxMove");
    $this->Threshold       = $this->Analyzer->Main->Config->get("Speed.Threshold");
    $this->Counter         = 0;
    $this->Leniency        = 0.2;
    $this->MotionSeconds   = $this->Analyzer->Main->advancedConfig->get("MOVE_MOTION_BYPASS_SECONDS");
  }

  /**
   * @param PlayerMoveEvent $event
   */
  public function run(PlayerMoveEvent $event) : void
  {
    if ($this->Analyzer->Player->getAllowFlight()) return;
    if (!$this->Analyzer->Main->Config->get("Speed")) return;
    if ($this->Analyzer->Player->getGamemode()->equals(GameMode::CREATIVE())) return;
    if ($this->Analyzer->Player->getGamemode()->equals(GameMode::SPECTATOR())) return;
    # check if all blocks above the player are air
    # if they aren't, adjust speed limit
    if (!$this->Analyzer->areAllBlocksAboveAir())
    {
      $this->MaxSpeed = ($this->ConfiguredSpeed)*1.25;
    }
    else
    {
      $this->MaxSpeed = $this->ConfiguredSpeed;
    }
    # adapt MaxSpeed to friction factor of block below, don't change anything when on normal blocks
    $this->MaxSpeed = ($this->MaxSpeed)/(0.6);
    $this->MaxSpeed = ($this->MaxSpeed)*($this->Analyzer->getCurrentFrictionFactor());
    $name = $this->Analyzer->PlayerName;
    $speed = $this->Analyzer->XZSpeed;
    $currentTick = (float)$this->Analyzer->Server->getTick();
    if((($currentTick) - ($this->Analyzer->lastMotionTick)) <= (($this->MotionSeconds)*20)) return; #check if enough time since the last Motion has elapsed
    
    if($this->Analyzer->ignoredMove)
    {
      if($this->Analyzer->Player->getEffects()->has(VanillaEffects::SPEED()))
      {
        $amp        = $this->Analyzer->Player->getEffects()->get(VanillaEffects::SPEED())->getEffectLevel();
        $speedlimit = ($this->MaxSpeed)*(1+(($this->Leniency)*($amp)));
        $maxdistance = $speedlimit * $this->Analyzer->TimeDiff; #calculate maximum distance for ingored move
        if($this->Analyzer->XZDistance > $maxdistance)
        {
          $event->cancel(); #cancel move event if travelled distance is too high nevertheless, but do not raise counter.
        }
      }
      else
      {
        $maxdistance = $this->MaxSpeed * $this->Analyzer->TimeDiff; #calculate maximum distance for ingored move
        if($this->Analyzer->XZDistance > $maxdistance)
        {
          $event->cancel(); #same applies without speed effect.
        }
      }
    }
    elseif($this->Analyzer->Player->getEffects()->has(VanillaEffects::SPEED()))
    {
      $amp        = $this->Analyzer->Player->getEffects()->get(VanillaEffects::SPEED())->getEffectLevel();
      $speedlimit = ($this->MaxSpeed)*(1+(($this->Leniency)*($amp)));
      if($speed > $speedlimit)
      {
        $this->Counter += 2; #increase counter if player travels with unlegit speed
      }
      elseif($this->Counter > 0)
      {
        $this->Counter--; #decrease counter if player travels with legit speed
      }
    }
    elseif($speed > $this->MaxSpeed)
    {
      $this->Counter += 2; #increase counter if player travels with unlegit speed
    }
    elseif($this->Counter > 0)
    {
      $this->Counter--; #decrease counter if player travels with legit speed
    }
    
    if($this->Counter >= ($this->Threshold * 2))
    {
      $event->cancel();
      if($this->Analyzer->Main->Config->get("Speed.Punishment") == "kick")
      {
        $this->Analyzer->kickPlayer($this->Analyzer->Main->Config->get("Speed.KickMessage"));
        $this->Counter = 0;
      }
    }
  }
}

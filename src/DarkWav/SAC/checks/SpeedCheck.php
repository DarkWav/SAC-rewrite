<?php
declare(strict_types=1);
namespace DarkWav\SAC\checks;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\Player;
use pocketmine\entity\Effect;

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
  /** @var int */
  public int $IceSeconds;

  /**
   * SpeedCheck constructor.
   * @param Analyzer $ana
   */
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer        = $ana;
    $this->ConfiguredSpeed = $this->Analyzer->Main->Config->get("Speed.MaxMove");
    $this->Threshold       = $this->Analyzer->Main->Config->get("Speed.Threshold");
    $this->Counter         = 0;
    $this->Leniency        = 0.2;
    $this->MotionSeconds   = $this->Analyzer->Main->advancedConfig->get("MOVE_MOTION_BYPASS_SECONDS");
    $this->IceSeconds      = $this->Analyzer->Main->advancedConfig->get("MOVE_ICE_BYPASS_SECONDS"); #TODO: properly account changes for ice blocks
  }

  /**
   * @param PlayerMoveEvent $event
   */
  public function run(PlayerMoveEvent $event) : void
  {
    if ($this->Analyzer->Player->getAllowFlight()) return;
    if (!$this->Analyzer->Main->Config->get("Speed")) return;
    if ($this->Analyzer->Player->getGamemode() == Player::CREATIVE) return;
    if ($this->Analyzer->Player->getGamemode() == Player::SPECTATOR) return;
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
    $name = $this->Analyzer->PlayerName;
    $speed = $this->Analyzer->XZSpeed;
    $currentTick = (double)$this->Analyzer->Server->getTick();
    if((($currentTick) - ($this->Analyzer->lastMotionTick)) <= (($this->MotionSeconds)*20)) return; #check if enough time since the last Motion has elapsed
    
    if($this->Analyzer->ignoredMove)
    {
      if($this->Analyzer->Player->hasEffect(Effect::SPEED))
      {
        $amp        = $this->Analyzer->Player->getEffect(Effect::SPEED)->getEffectLevel();
        $speedlimit = ($this->MaxSpeed)*(1+(($this->Leniency)*($amp)));
        $maxdistance = $speedlimit * $this->Analyzer->TimeDiff; #calculate maximum distance for ingored move
        if($this->Analyzer->XZDistance > $maxdistance)
        {
          $event->setCancelled(true); #cancel move event if travelled distance is too high nevertheless, but do not raise counter.
        }
      }
      else
      {
        $maxdistance = $this->MaxSpeed * $this->Analyzer->TimeDiff; #calculate maximum distance for ingored move
        if($this->Analyzer->XZDistance > $maxdistance)
        {
          $event->setCancelled(true); #same applies without speed effect.
        }
      }
    }
    elseif($this->Analyzer->Player->hasEffect(Effect::SPEED))
    {
      $amp        = $this->Analyzer->Player->getEffect(Effect::SPEED)->getEffectLevel();
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
      $event->setCancelled(true);
      if($this->Analyzer->Main->Config->get("Speed.Punishment") == "kick")
      {
        $this->Analyzer->kickPlayer($this->Analyzer->Main->Config->get("Speed.KickMessage"));
        $this->Counter = 0;
      }
    }
  }
}

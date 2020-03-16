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
use pocketmine\event\Cancellable;
use pocketmine\entity\Effect;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;
use DarkWav\SAC\Analyzer;

class SpeedCheck
{
  public $Analyzer;
  public $MaxSpeed;
  public $Threshold;
  public $Counter;
  
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer      = $ana;
    $this->MaxSpeed      = $this->Analyzer->Main->Config->get("Speed.MaxMove");
    $this->Threshold     = $this->Analyzer->Main->Config->get("Speed.Threshold");
    $this->Counter       = 0;
    $this->Leniency      = 0.2;
  }
  
  public function run($event) : void
  {
    if ($this->Analyzer->Player->getAllowFlight()) return;
    if (!$this->Analyzer->Main->Config->get("Speed")) return;
    if ($this->Analyzer->Player->getGamemode() == Player::CREATIVE) return;
    if ($this->Analyzer->Player->getGamemode() == Player::SPECTATOR) return;
    $name = $this->Analyzer->PlayerName;
    $speed = $this->Analyzer->XZSpeed;
    $this->Analyzer->Logger->debug(TextFormat::ESCAPE.$this->Analyzer->Colorized."[SAC] > $name is running at $speed blocks per second!");
    
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

<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

#import Analzyer

use DarkWav\SAC\Analyzer;

#import checks

use DarkWav\SAC\checks\AngleCheck;
use DarkWav\SAC\checks\CombatHeuristics;
use DarkWav\SAC\checks\CriticalsCheck;
use DarkWav\SAC\checks\FastBowCheck;
use DarkWav\SAC\checks\FastBreakCheck;
use DarkWav\SAC\checks\FastPlaceCheck;
use DarkWav\SAC\checks\FlyCheck;
use DarkWav\SAC\checks\GlideCheck;
use DarkWav\SAC\checks\NoClipCheck;
use DarkWav\SAC\checks\ReachCheck;
use DarkWav\SAC\checks\RegenCheck;
use DarkWav\SAC\checks\SpeedCheck;
use DarkWav\SAC\checks\SpiderCheck;
use DarkWav\SAC\checks\VClipCheck;

class CheckRegister
{
  #checks

  public $AngleCheck;
  public $CombatHeuristics;
  public $CriticalsCheck;
  public $FastBowCheck;
  public $FastBreakCheck;
  public $FastPlaceCheck;
  public $FlyCheck;
  public $GlideCheck;
  public $NoClipCheck;
  public $ReachCheck;
  public $RegenCheck;
  public $SpeedCheck;
  public $SpiderCheck;
  public $VClipCheck;
  
  public function __construct(Analyzer $ana)
  {
    #initialize checks

    $this->AngleCheck       = new AngleCheck($ana);
    $this->CombatHeuristics = new CombatHeuristics($ana);
    $this->CriticalsCheck   = new CriticalsCheck($ana);
    $this->FastBowCheck     = new FastBowCheck($ana);
    $this->FastBreakCheck   = new FastBreakCheck($ana);
    $this->FastPlaceCheck   = new FastPlaceCheck($ana);
    $this->FlyCheck         = new FlyCheck($ana);
    $this->GlideCheck       = new GlideCheck($ana);
    $this->NoClipCheck      = new NoClipCheck($ana);
    $this->ReachCheck       = new ReachCheck($ana);
    $this->RegenCheck       = new RegenCheck($ana);
    $this->SpeedCheck       = new SpeedCheck($ana);
    $this->SpiderCheck      = new SpiderCheck($ana);
    $this->VClipCheck       = new VClipCheck($ana);
  }
  
  public function runChecksOnPlayerPerformsHit($event)
  {
    #run regular checks
    $this->AngleCheck->run($event);
    $this->CriticalsCheck->run();
    $this->ReachCheck->run();
    #run heuristics
    $this->CombatHeuristics->run();
  }
  
  public function runChecksOnPlayerMoveEvent($event)
  {
    $this->SpeedCheck->run($event);
  }
}

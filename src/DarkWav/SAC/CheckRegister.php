<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

#import checks

use DarkWav\SAC\checks\AngleCheck;
use DarkWav\SAC\checks\CombatHeuristics;
use DarkWav\SAC\checks\FastBowCheck;
use DarkWav\SAC\checks\FlyCheck;
use DarkWav\SAC\checks\GlideCheck;
use DarkWav\SAC\checks\NoClipCheck;
use DarkWav\SAC\checks\ReachCheck;
use DarkWav\SAC\checks\RegenCheck;
use DarkWav\SAC\checks\SpeedCheck;
use DarkWav\SAC\checks\SpiderCheck;
use DarkWav\SAC\checks\VClipCheck;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;

class CheckRegister
{
  #checks

  /** @var AngleCheck */
  public AngleCheck $AngleCheck;
  /** @var CombatHeuristics */
  public CombatHeuristics $CombatHeuristics;
  /** @var FastBowCheck */
  public FastBowCheck $FastBowCheck;
  /** @var FlyCheck */
  public FlyCheck $FlyCheck;
  /** @var GlideCheck */
  public GlideCheck $GlideCheck;
  /** @var NoClipCheck */
  public NoClipCheck $NoClipCheck;
  /** @var ReachCheck */
  public ReachCheck $ReachCheck;
  /** @var RegenCheck */
  public RegenCheck $RegenCheck;
  /** @var SpeedCheck */
  public SpeedCheck $SpeedCheck;
  /** @var SpiderCheck */
  public SpiderCheck $SpiderCheck;
  /** @var VClipCheck */
  public VClipCheck $VClipCheck;

  /**
   * CheckRegister constructor.
   * @param Analyzer $ana
   */
  public function __construct(Analyzer $ana)
  {
    #initialize checks

    $this->AngleCheck       = new AngleCheck($ana);
    $this->CombatHeuristics = new CombatHeuristics($ana);
    $this->FastBowCheck     = new FastBowCheck($ana);
    $this->FlyCheck         = new FlyCheck($ana);
    $this->GlideCheck       = new GlideCheck($ana);
    $this->NoClipCheck      = new NoClipCheck($ana);
    $this->ReachCheck       = new ReachCheck($ana);
    $this->RegenCheck       = new RegenCheck($ana);
    $this->SpeedCheck       = new SpeedCheck($ana);
    $this->SpiderCheck      = new SpiderCheck($ana);
    $this->VClipCheck       = new VClipCheck($ana);
  }

  /**
   * @param EntityDamageByEntityEvent $event
   */
  public function runChecksOnPlayerPerformsHit(EntityDamageByEntityEvent $event) : void
  {
    #run regular checks
    $this->AngleCheck->run($event);
    $this->ReachCheck->run($event);
    #run heuristics
    $this->CombatHeuristics->run();
  }

  /**
   * @param PlayerMoveEvent $event
   */
  public function runChecksOnPlayerMoveEvent(PlayerMoveEvent $event) : void
  {
    $this->SpeedCheck->run($event);
    $this->VClipCheck->run();
    $this->SpiderCheck->run();
    $this->FlyCheck->run();
    $this->NoClipCheck->run();
    $this->GlideCheck->run();
  }
  
  /**
   * @param EntityShootBowEvent $event
   */
  public function runChecksOnEntityShootBowEvent(EntityShootBowEvent $event) : void
  {
    $this->FastBowCheck->run();
  }
  
  /**
   * @param EntityRegainHealthEvent $event
   */
  public function runChecksOnEntityRegainHealthEvent(EntityRegainHealthEvent $event) : void
  {
    $this->RegenCheck->run();
  }
}

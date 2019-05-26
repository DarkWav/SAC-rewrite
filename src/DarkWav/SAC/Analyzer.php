<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2019 DarkWav
 */

use pocketmine\Player;
use pocketmine\utils\TextFormat;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;

#import checks

use DarkWav\SAC\checks\AngleCheck;
use DarkWav\SAC\checks\AutoClickerCheck;
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

class Analyzer
{
  #golobal variables
  public $Main;
  public $Player;
  public $PlayerName;
  public $Server;
  public $Logger;
  public $Colorized;

  #checks

  public $AngleCheck;
  public $AutoClickerCheck;
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

  public function __construct($plr, Main $sac)
  {

    #initialize basic variables

    $this->Main       = $sac;
    $this->Player     = $plr;
    $this->PlayerName = $this->Player->getName();
    $this->Server     = $this->Main->server;
    $this->Logger     = $this->Main->logger;
    $this->Colorized  = $this->Main->Colorized;

    #initialize checks

    $this->AngleCheck       = new AngleCheck($this);
    $this->AutoClickerCheck = new AutoClickerCheck($this);
    $this->CombatHeuristics = new CombatHeuristics($this);
    $this->CriticalsCheck   = new CriticalsCheck($this);
    $this->FastBowCheck     = new FastBowCheck($this);
    $this->FastBreakCheck   = new FastBreakCheck($this);
    $this->FastPlaceCheck   = new FastPlaceCheck($this);
    $this->FlyCheck         = new FlyCheck($this);
    $this->GlideCheck       = new GlideCheck($this);
    $this->NoClipCheck      = new NoClipCheck($this);
    $this->ReachCheck       = new ReachCheck($this);
    $this->RegenCheck       = new RegenCheck($this);
    $this->SpeedCheck       = new SpeedCheck($this);
    $this->SpiderCheck      = new SpiderCheck($this);
    $this->VClipCheck       = new VClipCheck($this);

  }

  public function onPlayerJoin() : void
  {
    $this->Player->sendMessage(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am watching you ...");
    $this->Logger->info(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am watching you ...");
  }

  public function onPlayerRejoin() : void
  {
    $this->Player->sendMessage(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am still watching you ...");
    $this->Logger->info(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am still watching you ...");
  }

  public function onPlayerQuit() : void
  {
    $this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > $this->PlayerName is no longer watched...");
  }

  public function onPlayerMoveEvent($event) : void
  {
    #process event first
    $this->processPlayerMoveEvent($event);
    #then run checks
    $this->SpeedCheck->run($event);
  }

  public function processPlayerMoveEvent($event) : void
  {
    #TODO
  }

  #util functions

  public function kickPlayer($message) : void
  {
    $this->Main->getScheduler()->scheduleDelayedTask(new KickTask($this->Main, $this->Player, $message), 1);
  }

}

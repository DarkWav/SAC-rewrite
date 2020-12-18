<?php
declare(strict_types=1);
namespace DarkWav\SAC\checks;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use pocketmine\Player;
use pocketmine\utils\TextFormat;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;
use DarkWav\SAC\Analyzer;

class FastBowCheck
{
  /** @var Analyzer */
  public Analyzer $Analyzer;

  /**
   * FastBowCheck constructor.
   * @param Analyzer $ana
   */
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer   = $ana;
  }

  public function run() : void
  {
    $name = $this->Analyzer->PlayerName;
  }
} 

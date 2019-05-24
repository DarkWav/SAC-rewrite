<?php
declare(strict_types=1);
namespace DarkWav\SAC\checks;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2019 DarkWav
 */

use pocketmine\Player;
use pocketmine\utils\TextFormat;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;
use DarkWav\SAC\Analyzer;

class FastBowCheck
{
  public $Analyzer;
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer   = $ana;
  }
  public function run() : void
  {
    $name = $this->Analyzer->PlayerName;
  }
} 

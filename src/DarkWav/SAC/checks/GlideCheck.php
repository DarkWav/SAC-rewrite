<?php
declare(strict_types=1);
namespace DarkWav\SAC\checks;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use DarkWav\SAC\Analyzer;

class GlideCheck
{
  /** @var Analyzer */
  public $Analyzer;

  /**
   * GlideCheck constructor.
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

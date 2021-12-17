<?php
declare(strict_types=1);
namespace DarkWav\SAC\checks;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;
use DarkWav\SAC\Analyzer;

class CombatHeuristics
{
  /** @var Analyzer */
  public Analyzer $Analyzer;
  /** @var int */
  public int $Threshold;
  /** @var Config */
  public Config $Config;
  /** @var Config */
  public Config $AConfig;
  /** @var float */
  public float $Counter; #The CombatHeuristics counter must be float to be able to work with weights
  /** @var float */
  public float $DistanceWeight;
  /** @var float */
  public float $AccuracyWeight;
  /** @var float */
  public float $ConsistencyWeight;
  /** @var float */
  public float $ClickSpeedWeight;
  /** @var float */
  public float $MaxDistance;
  /** @var float */
  public float $MaxAccuracy;
  /** @var float */
  public float $MaxConsistency;
  /** @var int */
  public float $MaxCPS;
  /** @var float */
  public float $MaxDistanceWhileWalking;
  /** @var float */
  public float $MaxDistance30Deg;
  /** @var float */
  public float $MaxDistance60Deg;

  /**
   * CombatHeuristics constructor.
   * @param Analyzer $ana
   */
  public function __construct(Analyzer $ana)
  {
    $this->Analyzer          = $ana;
    $this->Counter           = 0.0;
    $this->Threshold         = $this->Analyzer->Main->Config->get("CombatHeuristics.Threshold");
    $this->Config            = $this->Analyzer->Main->Config;
    $this->AConfig           = $this->Analyzer->Main->advancedConfig;
    #load custom preset if enabled
    if($this->Config->get("CombatHeuristics.Sensitivity") == 0)
    {
      $this->MaxDistance             = $this->AConfig->get("HEUR_DISTANCE_MAX");
      $this->MaxAccuracy             = $this->AConfig->get("HEUR_ACCURACY_MAX");
      $this->MaxConsistency          = $this->AConfig->get("HEUR_CONSISTENCY_MAX");
      $this->MaxCPS                  = $this->AConfig->get("HEUR_CLICKSPEED_MAX");
      $this->MaxDistanceWhileWalking = $this->AConfig->get("HEUR_SPEED_DISTANCE_RELATION_MAX");
      $this->MaxDistance30Deg        = $this->AConfig->get("HEUR_ANGLE_DISTANCE_RELATION_30DEG_MAX");
      $this->MaxDistance60Deg        = $this->AConfig->get("HEUR_ANGLE_DISTANCE_RELATION_60DEG_MAX");
      $this->DistanceWeight          = $this->AConfig->get("HEUR_DISTANCE_WEIGHT");
      $this->AccuracyWeight          = $this->AConfig->get("HEUR_ACCURACY_WEIGHT");
      $this->ConsistencyWeight       = $this->AConfig->get("HEUR_CONSISTENCY_WEIGHT");
      $this->ClickSpeedWeight        = $this->AConfig->get("HEUR_CLICKSPEED_WEIGHT");
    }
    #load normal preset if enabled
    elseif($this->Config->get("CombatHeuristics.Sensitivity") == 2)
    {
      $this->MaxDistance             = 3.875; # Maximum Distance in Combat
      $this->MaxAccuracy             = 1.0  ; # Maximum Aim Accuracy
      $this->MaxConsistency          = 1.5  ; # Maximum Aim Consistency
      $this->MaxCPS                  = 15   ; # Maximum Clicks per second
      $this->MaxDistanceWhileWalking = 3.75 ; # Maximum Distance in Combat while Walking
      $this->MaxDistance30Deg        = 3.625; # Maximum Distance in Combat while hitting target with over 30 degrees Angle
      $this->MaxDistance60Deg        = 3.375; # Maximum Distance in Combat while hitting target with over 60 degrees Angle
      $this->DistanceWeight          = 4.0  ; # Multiplier for threshold increase if detected by Distance module
      $this->AccuracyWeight          = 2.5  ; # Multiplier for threshold increase if detected by Accuracy module
      $this->ConsistencyWeight       = 1.5  ; # Multiplier for threshold increase if detected by Consistency module
      $this->ClickSpeedWeight        = 1.0  ; # Multiplier for threshold increase if detected by ClickSpeed module
    }
    #load high preset if enabled
    elseif($this->Config->get("CombatHeuristics.Sensitivity") == 3)
    {
      $this->MaxDistance             = 3.75;  # Maximum Distance in Combat
      $this->MaxAccuracy             = 1.5  ; # Maximum Aim Accuracy
      $this->MaxConsistency          = 2.0  ; # Maximum Aim Consistency
      $this->MaxCPS                  = 12   ; # Maximum Clicks per second
      $this->MaxDistanceWhileWalking = 3.625; # Maximum Distance in Combat while Walking
      $this->MaxDistance30Deg        = 3.5  ; # Maximum Distance in Combat while hitting target with over 30 degrees Angle
      $this->MaxDistance60Deg        = 3.25 ; # Maximum Distance in Combat while hitting target with over 60 degrees Angle
      $this->DistanceWeight          = 3.0  ; # Multiplier for threshold increase if detected by Distance module
      $this->AccuracyWeight          = 3.0  ; # Multiplier for threshold increase if detected by Accuracy module
      $this->ConsistencyWeight       = 2.5  ; # Multiplier for threshold increase if detected by Consistency module
      $this->ClickSpeedWeight        = 1.5  ; # Multiplier for threshold increase if detected by ClickSpeed module
    }
    #load low preset in any other case
    else
    {
       $this->MaxDistance             = 4.0  ; # Maximum Distance in Combat
       $this->MaxAccuracy             = 0.5  ; # Maximum Aim Accuracy
       $this->MaxConsistency          = 1.0  ; # Maximum Aim Consistency
       $this->MaxCPS                  = 20   ; # Maximum Clicks per second
       $this->MaxDistanceWhileWalking = 3.875; # Maximum Distance in Combat while Walking
       $this->MaxDistance30Deg        = 3.75 ; # Maximum Distance in Combat while hitting target with over 30 degrees Angle
       $this->MaxDistance60Deg        = 3.5  ; # Maximum Distance in Combat while hitting target with over 60 degrees Angle
       $this->DistanceWeight          = 2.0  ; # Multiplier for threshold increase if detected by Distance module
       $this->AccuracyWeight          = 1.5  ; # Multiplier for threshold increase if detected by Accuracy module
       $this->ConsistencyWeight       = 1.0  ; # Multiplier for threshold increase if detected by Consistency module
       $this->ClickSpeedWeight        = 0.75 ; # Multiplier for threshold increase if detected by ClickSpeed module
    }
  }

  public function run() : void
  {
    $name     = $this->Analyzer->PlayerName;
    $detected = false;
    #check if Heuristics are enabled in config
    if(!$this->Config->get("CombatHeuristics"))
    {
      return;
    }
    #now for the checks
    #Distance module
    if($this->Config->get("CombatHeuristics.Modules.Distance"))
    {
      if($this->Analyzer->averageHitDistanceXZ > $this->MaxDistance)
      {
        $this->Counter+=(1*$this->DistanceWeight);
      }
      elseif
      (
        $this->Config->get("CombatHeuristics.Modules.Distance.AngleDistanceRelation") &&
        $this->Analyzer->averageHitDistanceXZ > $this->MaxDistance30Deg &&
        $this->Analyzer->averageHitAngleXZ    > 30.0
      )
      {
        $this->Counter+=(1*$this->DistanceWeight);
        $detected = true;
      }
      elseif
      (
        $this->Config->get("CombatHeuristics.Modules.Distance.AngleDistanceRelation") &&
        $this->Analyzer->averageHitDistanceXZ > $this->MaxDistance60Deg &&
        $this->Analyzer->averageHitAngleXZ    > 60.0
      )
      {
        $this->Counter+=(1*$this->DistanceWeight);
        $detected = true;
      }
      elseif
      (
        $this->Config->get("CombatHeuristics.Modules.Distance.SpeedDistanceRelation") &&
        $this->Analyzer->averageHitDistanceXZ > $this->MaxDistanceWhileWalking &&
        $this->Analyzer->XZSpeed              > 3.0
      )
      {
        $this->Counter+=(1*$this->DistanceWeight);
        $detected = true;
      }
    }
    #Accuracy module
    if
    (
      $this->Config->get("CombatHeuristics.Modules.Accuracy") &&
      $this->Analyzer->alreadyAnalyzedHitAngleXZHits >= $this->Analyzer->analyzedHits #only activate when ringbuffer is properly filled
    )
    {
      if($this->Analyzer->averageHitAngleXZ < $this->MaxAccuracy)
      {
        $this->Counter+=(1*$this->AccuracyWeight);
        $detected = true;
      }
    }
    #Consistency module
    if
    (
      $this->Config->get("CombatHeuristics.Modules.Consistency") &&
      $this->Analyzer->alreadyAnalyzedHitAngleXZDifferenceHits >= $this->Analyzer->analyzedHits #only activate when ringbuffer is properly filled
    )
    {
      if($this->Analyzer->averageHitAngleXZDifference < $this->MaxConsistency)
      {
        $this->Counter+=(1*$this->ConsistencyWeight);
        $detected = true;
      }
    }
    #ClickSpeed module
    if
    (
      $this->Config->get("CombatHeuristics.Modules.ClickSpeed") &&
      $this->Analyzer->alreadyAnalyzedHits >= $this->Analyzer->analyzedHits #only activate when ringbuffer is properly filled
    )
    {
      if($this->Analyzer->averageCPS > $this->MaxCPS)
      {
        $this->Counter+=(1*$this->ClickSpeedWeight);
        $detected = true;
      }
    }

    if(!$detected)
    {
      $this->Counter-=2;
    }
    if($this->Counter < 0)
    {
      $this->Counter = 0;
    }

    if($this->Counter > $this->Threshold)
    {
      $this->Analyzer->kickPlayer($this->Config->get("CombatHeuristics.KickMessage"));
      $this->Counter = 0;
    }
  }
}

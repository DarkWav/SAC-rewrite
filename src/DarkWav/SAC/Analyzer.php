<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2020 DarkWav and others.
 */

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

use DarkWav\SAC\Main;
use DarkWav\SAC\KickTask;
use DarkWav\SAC\CheckRegister;

class Analyzer
{
  #golobal variables
  public $Main;
  public $Player;
  public $PlayerName;
  public $Server;
  public $Logger;
  public $Colorized;
  public $CheckRegister;

  #data

  #combat

  public $isPvp; #indicator wether hit was performed in a PVP scenario or not
  public $lastHitTick; #Tick where player was last hit
  public $hitTickDifference; #Tick difference between 2 hits
  public $hitTimeDifference; #Time difference between 2 hits
  public $hitTimeDifferenceSum;
  public $hitTimeDifferenceRingBuffer;
  public $hitTimeDifferenceRingBufferSize;
  public $hitTimeDifferenceRingBufferIndex;
  public $analyzedHits; #amount of hits used for heuristic analysis
  public $damagedEntityPosition;
  public $damagedEntityPositionXZ;
  public $playerPosition;
  public $playerPositionXZ;
  public $playerFacingDirection;
  public $playerFacingDirectionXZ;
  public $lastPlayerFacingDirectionXZ;
  public $directionToTarget;
  public $directionToTargetXZ;
  public $hitDistance; #Reach Distance
  public $hitDistanceXZ; #Reach Distance (only X and Z axis)
  public $hitDistanceXZSum;
  public $hitDistanceXZRingBuffer;
  public $hitDistanceXZRingBufferIndex;
  public $hitDistanceXZRingBufferSize;
  public $averageHitDistanceXZ; #average Reach distance across a set amount of hits
  public $directionDotProduct;
  public $directionDotProductXZ;
  public $hitAngle; #Hit Angle
  public $hitAngleXZ; #Hit Angle (only X and Z axis)
  public $lastHitAngleXZ;
  public $hitAngleXZSum;
  public $hitAngleXZRingBuffer;
  public $hitAngleXZRingBufferIndex;
  public $hitAngleXZRingBufferSize;
  public $alreadyAnalyzedHitAngleXZHits;
  public $averageHitAngleXZ; #Average Hit Angle across a set amount of hits
  public $headMove; #Head movement since last hit
  public $hitAngleXZDifference;
  public $hitAngleXZDifferenceSum;
  public $hitAngleXZDifferenceRingBuffer;
  public $hitAngleXZDifferenceRingBufferIndex;
  public $hitAngleXZDifferenceRingBufferSize;
  public $alreadyAnalyzedHitAngleXZDifferenceHits;
  public $averageHitAngleXZDifference; #Average hit angle difference among a set amount of hits where the head has been moved before
  public $averageCPS; #Average Clicks Per Seconds
  public $alreadyAnalyzedHits;

  #movement

  public $FromXZPos;
  public $ToXZPos;
  public $XZDistance;
  public $PreviousTick;
  public $XZTimeRingBuffer;
  public $XZDistanceRingBuffer;
  public $YTimeRingBuffer;
  public $YDistanceRingBuffer;
  public $XZRingBufferSize;
  public $YRingBufferSize;
  public $XZRingBufferIndex;
  public $YRingBufferIndex;
  public $XZTimeSum;
  public $XZDistanceSum;
  public $YTimeSum;
  public $YDistanceSum;
  public $XZSpeed; #Average Travel Speed (XZ-Axis)
  public $YSpeed; #Average Travel Speed (Y-Axis)
  public $ignoredMove;
  public $TimeDiff;

  public function __construct($plr, Main $sac)
  {

    #initialize basic variables

    $this->Main              = $sac;
    $this->Player            = $plr;
    $this->PlayerName        = $this->Player->getName();
    $this->Server            = $this->Main->server;
    $this->Logger            = $this->Main->logger;
    $this->Colorized         = $this->Main->Colorized;
    $this->CheckRegister     = new CheckRegister($this);
    
    #initialize data variables

    #processPlayerPerformsHit() data

    $this->isPvp                               = false;
    $this->analyzedHits                        = $this->Main->Config->get("CombatHeuristics.AnalyzedHits");
    $this->lastHitTick                         = -1;
    $this->hitTickDifference                   = 0;
    $this->hitTimeDifference                   = 0.0;
    $this->hitTimeDifferenceSum                = 0.0;
    $this->hitTimeDifferenceRingBufferSize     = $this->analyzedHits;
    $this->hitTimeDifferenceRingBuffer         = array_fill(0, $this->hitTimeDifferenceRingBufferSize, 0.0);
    $this->hitTimeDifferenceRingBufferIndex    = 0;
    
    $this->damagedEntityPosition               = new Vector3(0.0, 0.0, 0.0);
    $this->damagedEntityPositionXZ             = new Vector3(0.0, 0.0, 0.0);
    $this->playerPosition                      = new Vector3(0.0, 0.0, 0.0);
    $this->playerPositionXZ                    = new Vector3(0.0, 0.0, 0.0);
    
    $this->playerFacingDirection               = new Vector3(0.0, 0.0, 0.0);
    $this->playerFacingDirectionXZ             = new Vector3(0.0, 0.0, 0.0);
    $this->lastPlayerFacingDirectionXZ         = new Vector3(0.0, 0.0, 0.0);
    $this->directionToTarget                   = new Vector3(0.0, 0.0, 0.0);
    $this->directionToTargetXZ                 = new Vector3(0.0, 0.0, 0.0);
    
    $this->hitDistance                         = 0.0;
    $this->hitDistanceXZ                       = 0.0;
    $this->hitDistanceXZSum                    = 0.0;
    $this->hitDistanceXZRingBufferSize         = $this->analyzedHits;
    $this->hitDistanceXZRingBuffer             = array_fill(0, $this->hitDistanceXZRingBufferSize, 0.0);
    $this->hitDistanceXZRingBufferIndex        = 0;
    
    $this->directionDotProduct                 = 0.0;
    $this->directionDotProductXZ               = 0.0;
    
    $this->hitAngle                            = 0.0; 
    $this->hitAngleXZ                          = 0.0;
    $this->lastHitAngleXZ                      = 0.0;
    $this->hitAngleXZSum                       = 0.0;
    $this->hitAngleXZRingBufferSize            = $this->analyzedHits;
    $this->hitAngleXZRingBuffer                = array_fill(0, $this->hitAngleXZRingBufferSize, 0.0);
    $this->hitAngleXZRingBufferIndex           = 0;
    
    $this->headMove                            = 0.0;
    
    $this->hitAngleXZDifference                = 0.0;
    $this->hitAngleXZDifferenceSum             = 0.0;
    $this->hitAngleXZDifferenceRingBufferSize  = $this->analyzedHits;
    $this->hitAngleXZDifferenceRingBuffer      = array_fill(0, $this->hitAngleXZDifferenceRingBufferSize, 0.0);
    $this->hitAngleXZDifferenceRingBufferIndex = 0;
    
    $this->averageHitDistanceXZ                    = 0.0;
    $this->averageHitAngleXZ                       = 0.0;
    $this->averageHitAngleXZDifference             = 0.0;
    $this->averagehitTimeDifference                = 0.0;
    $this->averageCPS                              = 0.0;

    $this->alreadyAnalyzedHitAngleXZHits           = 0;
    $this->alreadyAnalyzedHitAngleXZDifferenceHits = 0;
    $this->alreadyAnalyzedHits                     = 0;
    
    #processPlayerMoveEvent() data
    
    $this->FromXZPos               = new Vector3(0.0, 0.0, 0.0);
    $this->ToXZPos                 = new Vector3(0.0, 0.0, 0.0);
    $this->FromYPos                = new Vector3(0.0, 0.0, 0.0);
    $this->ToYPos                  = new Vector3(0.0, 0.0, 0.0);
    $this->XZDistance              = 0.0;
    $this->YDistance               = 0.0;
    $this->PreviousTick            = -1.0;
    $this->XZRingBufferSize        = 8;
    $this->YRingBufferSize         = 8;
    $this->XZRingBufferIndex       = 0;
    $this->YRingBufferIndex        = 0;
    $this->XZTimeRingBuffer        = array_fill(0, $this->XZRingBufferSize, 0.0);
    $this->XZDistanceRingBuffer    = array_fill(0, $this->XZRingBufferSize, 0.0);
    $this->YTimeRingBuffer         = array_fill(0, $this->YRingBufferSize , 0.0);
    $this->YDistanceRingBuffer     = array_fill(0, $this->YRingBufferSize , 0.0);
    $this->XZTimeSum               = 0.0;
    $this->XZDistanceSum           = 0.0;
    $this->YTimeSum                = 0.0;
    $this->YDistanceSum            = 0.0;
    $this->XZSpeed                 = 0.0;
    $this->YSpeed                  = 0.0;
    $this->ignoredMove             = false;
    $this->TimeDiff                = 0;
  }

  # Event handlers
  # these will work with events redirected from EventListener
  
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
    $this->CheckRegister->runChecksOnPlayerMoveEvent($event);
  }
  
  public function onPlayerGetsHit($event): void
  {
  
  }

  public function onPlayerPerformsHit($event): void
  {
    #process data
    $this->processPlayerPerformsHit($event);
    #then run checks
    $this->CheckRegister->runChecksOnPlayerPerformsHit($event);
  }

  # processing functions
  # these will process data retrieved from events
  
  public function processPlayerMoveEvent($event) : void
  {
    #calculate distance travelled in the event itself in XZ and Y axis.
    $this->FromXZPos    = new Vector3($event->getFrom()->getX(), 0.0, $event->getFrom()->getZ());
    $this->ToXZPos      = new Vector3($event->getTo()->getX()  , 0.0, $event->getTo()->getZ()  );
    $this->XZDistance   = $this->FromXZPos->distance($this->ToXZPos);
    $this->FromYPos     = new Vector3(0.0, $event->getFrom()->getY(), 0.0);
    $this->ToYPos       = new Vector3(0.0, $event->getTo()->getY()  , 0.0);
    $this->YDistance    = $this->FromYPos->distance($this->ToYPos);

    $Tick               = (double)$this->Server->getTick();
    $TPS                = (double)$this->Server->getTicksPerSecond();
    $TickCount          = (double)($Tick - $this->PreviousTick);
    $this->TimeDiff           = (double)($TickCount) / (double)$TPS;
    if ($TPS > 0.0 and $this->PreviousTick != -1.0)
    {
      if($this->TimeDiff < 2.0) #if move events are divided too far apart each other, ignore the move
      {
        $this->ignoredMove = false;
        #write distances and times into a a ringbuffer
        $this->XZTimeSum                                      = $this->XZTimeSum     - $this->XZTimeRingBuffer    [$this->XZRingBufferIndex] + $this->TimeDiff; #ringbuffer time sum (remove oldest, add new)
        $this->XZDistanceSum                                  = $this->XZDistanceSum - $this->XZDistanceRingBuffer[$this->XZRingBufferIndex] + $this->XZDistance; #ringbuffer distance sum (remove oldest, add new) 
        $this->XZTimeRingBuffer    [$this->XZRingBufferIndex] = $this->TimeDiff; #overwrite oldest delta_t  with the new one
        $this->XZDistanceRingBuffer[$this->XZRingBufferIndex] = $this->XZDistance; #overwrite oldest distance with the new one          
        $this->XZRingBufferIndex++; #Update ringbuffer position
        if ($this->XZRingBufferIndex >= $this->XZRingBufferSize)
        {
          $this->XZRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
        }
        $this->YTimeSum                                      = $this->YTimeSum     - $this->YTimeRingBuffer    [$this->YRingBufferIndex] + $this->TimeDiff; #ringbuffer time sum (remove oldest, add new)
        $this->YDistanceSum                                  = $this->YDistanceSum - $this->YDistanceRingBuffer[$this->YRingBufferIndex] + $this->YDistance; #ringbuffer distance sum (remove oldest, add new) 
        $this->YTimeRingBuffer    [$this->YRingBufferIndex]  = $this->TimeDiff; #overwrite oldest delta_t  with the new one
        $this->YDistanceRingBuffer[$this->YRingBufferIndex]  = $this->YDistance; #overwrite oldest distance with the new one          
        $this->YRingBufferIndex++; #Update ringbuffer position
        if ($this->YRingBufferIndex >= $this->YRingBufferSize)
        {
          $this->YRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
        }
      }
      else
      {
        $this->ignoredMove = true;
      }
      #calculate actual average movement speed
      if ($this->XZTimeSum > 0)
      {
        $this->XZSpeed = (double)$this->XZDistanceSum / (double)$this->XZTimeSum; #speed = distance / time difference:
      }
      else
      {
        $this->XZSpeed = 0.0;
      }
      if ($this->YTimeSum > 0)
      {
        $this->YSpeed = (double)$this->YDistanceSum  / (double)$this->YTimeSum; #speed = distance / time difference:
      }
      else
      {
        $this->YSpeed = 0.0;
      }
    }
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: ".$this->PlayerName."] [Debug: Movement] > XZSpeed: ".$this->XZSpeed);
    $this->PreviousTick = $Tick;
  }

  public function processPlayerPerformsHit($event) : void
  {
    $damagedEntity = $event->getEntity();
    $TPS           = (double)$this->Server->getTicksPerSecond();
    if ($damagedEntity instanceof Player)
    {
      $this->isPvp = true;
    }
    else
    {
      $this->isPvp = false;
    }
    $name = $this->PlayerName;
    $this->damagedEntityPosition      = new Vector3($damagedEntity->getX(), $damagedEntity->getY(), $damagedEntity->getZ());
    $this->damagedEntityPositionXZ    = new Vector3($damagedEntity->getX(), 0                     , $damagedEntity->getZ());
    $this->playerPosition             = new Vector3($this->Player->getX() , $this->Player->getY() , $this->Player->getZ() );
    $this->playerPositionXZ           = new Vector3($this->Player->getX() , 0                     , $this->Player->getZ() );
    $this->playerFacingDirection      = $this->Player->getDirectionVector()->normalize();
    $this->playerFacingDirectionXZ    = $this->Player->getDirectionVector();
    $this->playerFacingDirectionXZ->y = 0;
    $this->playerFacingDirectionXZ    = $this->playerFacingDirectionXZ->normalize();
    $this->directionToTarget          = $this->damagedEntityPosition->subtract($this->playerPosition)->normalize();
    $this->directionToTargetXZ        = $this->damagedEntityPositionXZ->subtract($this->playerPositionXZ)->normalize();
    $this->headMove                   = $this->playerFacingDirectionXZ->distance($this->lastPlayerFacingDirectionXZ);
    $this->hitDistance                = $this->playerPosition->distance($this->damagedEntityPosition);
    $this->hitDistanceXZ              = $this->playerPositionXZ->distance($this->damagedEntityPositionXZ);
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > DistanceXZ: ".$this->hitDistanceXZ);
    #here comes the maths bois!
    $this->directionDotProduct        = $this->playerFacingDirection->dot($this->directionToTarget);
    $this->directionDotProductXZ      = $this->playerFacingDirectionXZ->dot($this->directionToTargetXZ);
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > DotProductXZ: ".$this->directionDotProductXZ);
    $this->hitAngle                   = rad2deg(acos($this->directionDotProduct));
    $this->hitAngleXZ                 = rad2deg(acos($this->directionDotProductXZ));
    if($this->lastHitTick > 0)
    {
      $this->hitTickDifference = ($this->Server->getTick() - $this->lastHitTick);
      $this->hitTimeDifference = ($this->hitTickDifference / $TPS);
    }
    $orient = ($this->playerFacingDirectionXZ->x * $this->directionToTargetXZ->z) - ($this->playerFacingDirectionXZ->z * $this->directionToTargetXZ->x);
    if($orient >= 0)
    {
      $this->hitAngleXZDifference       = abs($this->hitAngleXZ - $this->lastHitAngleXZ);
    }
    else
    {
      $this->hitAngleXZDifference       = abs((-$this->hitAngleXZ) - $this->lastHitAngleXZ); //invert AngleXZ if orient is negative
    }
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AngleXZDifference: ".$this->hitAngleXZDifference);
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AngleXZ: ".$this->hitAngleXZ);
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > HeadMove: ".$this->headMove);
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > TimeDiff: ".$this->hitTimeDifference);

    if ($TPS > 0.0)
    {
      $this->alreadyAnalyzedHits++;
      #compute heuristics-relevant data
      #average reach
      $this->hitDistanceXZSum                                             = $this->hitDistanceXZSum - $this->hitDistanceXZRingBuffer[$this->hitDistanceXZRingBufferIndex] + $this->hitDistanceXZ; #add distance to total distance sum
      $this->hitDistanceXZRingBuffer[$this->hitDistanceXZRingBufferIndex] = $this->hitDistanceXZ; #then write it into ringbuffer
      $this->hitDistanceXZRingBufferIndex++;
      if ($this->hitDistanceXZRingBufferIndex >= $this->hitDistanceXZRingBufferSize)
      {
        $this->hitDistanceXZRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
      }
      $this->averageHitDistanceXZ = $this->hitDistanceXZSum / $this->hitDistanceXZRingBufferSize;
      $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AVERAGE DistanceXZ: ".$this->averageHitDistanceXZ);

      #average hit speed
      $this->hitTimeDifferenceSum                                                 = $this->hitTimeDifferenceSum - $this->hitTimeDifferenceRingBuffer[$this->hitTimeDifferenceRingBufferIndex] + $this->hitTimeDifference; #add time difference to total difference sum
      $this->hitTimeDifferenceRingBuffer[$this->hitTimeDifferenceRingBufferIndex] = $this->hitTimeDifference; #then write it into ringbuffer
      $this->hitTimeDifferenceRingBufferIndex++;
      if ($this->hitTimeDifferenceRingBufferIndex >= $this->hitTimeDifferenceRingBufferSize)
      {
        $this->hitTimeDifferenceRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
      }
      $this->averageHitTimeDifference = $this->hitTimeDifferenceSum / $this->hitTimeDifferenceRingBufferSize;
      $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AVERAGE TimeDiff: ".$this->averagehitTimeDifference);
      if($this->averageHitTimeDifference > 0)
      {
        $this->averageCPS = (1 / $this->averagehitTimeDifference);
      }
      
      
      if ($this->headMove >= 0.05) #only re-calculate angle-based data when player actually moved head
      {
        #average angle
        $this->alreadyAnalyzedHitAngleXZHits++;
        $this->hitAngleXZSum                                          = $this->hitAngleXZSum - $this->hitAngleXZRingBuffer[$this->hitAngleXZRingBufferIndex] + $this->hitAngleXZ; #add angle to total angle sum
        $this->hitAngleXZRingBuffer[$this->hitAngleXZRingBufferIndex] = $this->hitAngleXZ; #then write it into ringbuffer
        $this->hitAngleXZRingBufferIndex++;
        if ($this->hitAngleXZRingBufferIndex >= $this->hitAngleXZRingBufferSize)
        {
          $this->hitAngleXZRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
        }
        $this->averageHitAngleXZ = $this->hitAngleXZSum / $this->hitAngleXZRingBufferSize;

        #average angle difference
        $this->alreadyAnalyzedHitAngleXZDifferenceHits++;
        $this->hitAngleXZDifferenceSum                                                    = $this->hitAngleXZDifferenceSum - $this->hitAngleXZDifferenceRingBuffer[$this->hitAngleXZDifferenceRingBufferIndex] + $this->hitAngleXZDifference; #add angle to total angle sum
        $this->hitAngleXZDifferenceRingBuffer[$this->hitAngleXZDifferenceRingBufferIndex] = $this->hitAngleXZDifference; #then write it into ringbuffer
        $this->hitAngleXZDifferenceRingBufferIndex++;
        if ($this->hitAngleXZDifferenceRingBufferIndex >= $this->hitAngleXZDifferenceRingBufferSize)
        {
          $this->hitAngleXZDifferenceRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
        }
        $this->averageHitAngleXZDifference = $this->hitAngleXZDifferenceSum / $this->hitAngleXZDifferenceRingBufferSize;
      }
      $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AVERAGE AngleXZ: ".$this->averageHitAngleXZ);
      $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AVERAGE AngleXZDifference: ".$this->averageHitAngleXZDifference);
    }
    #update the "previous-value" variables
    $this->lastPlayerFacingDirectionXZ = $this->playerFacingDirectionXZ;
    $this->lastHitAngleXZ              = $this->hitAngleXZ;
    $this->lastHitTick                 = $this->Server->getTick();
  }
  
  #util functions

  public function kickPlayer($message) : void
  {
    $this->Main->getScheduler()->scheduleDelayedTask(new KickTask($this->Main, $this->Player, $message), 1);
  }

}

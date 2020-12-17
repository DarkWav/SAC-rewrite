<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

class Analyzer
{
  #golobal variables
  /** @var Main */
  public $Main;
  /** @var Player */
  public $Player;
  /** @var String */
  public $PlayerName;
  /** @var Server */
  public $Server;
  /** @var MainLogger */
  public $Logger;
  /** @var int */
  public $Colorized;
  /** @var CheckRegister */
  public $CheckRegister;

  #data

  #combat

  /** @var bool */
  public $isPvp; #indicator whether hit was performed in a PVP scenario or not
  /** @var int */
  public $lastHitTick; #Tick where player was last hit
  /** @var int */
  public $hitTickDifference; #Tick difference between 2 hits
  /** @var double */
  public $hitTimeDifference; #Time difference between 2 hits
  /** @var double */
  public $hitTimeDifferenceSum;
  /** @var double[] */
  public $hitTimeDifferenceRingBuffer;
  /** @var int */
  public $hitTimeDifferenceRingBufferSize;
  /** @var int */
  public $hitTimeDifferenceRingBufferIndex;
  /** @var int */
  public $analyzedHits; #amount of hits used for heuristic analysis
  /** @var Vector3 */
  public $damagedEntityPosition;
  /** @var Vector3 */
  public $damagedEntityPositionXZ;
  /** @var Vector3 */
  public $playerPosition;
  /** @var Vector3 */
  public $playerPositionXZ;
  /** @var Vector3 */
  public $playerFacingDirection;
  /** @var Vector3 */
  public $playerFacingDirectionXZ;
  /** @var Vector3 */
  public $lastPlayerFacingDirectionXZ;
  /** @var Vector3 */
  public $directionToTarget;
  /** @var Vector3 */
  public $directionToTargetXZ;
  /** @var double */
  public $hitDistance; #Reach Distance
  /** @var double */
  public $hitDistanceXZ; #Reach Distance (only X and Z axis)
  /** @var double */
  public $hitDistanceXZSum;
  /** @var double[] */
  public $hitDistanceXZRingBuffer;
  /** @var int */
  public $hitDistanceXZRingBufferIndex;
  /** @var int */
  public $hitDistanceXZRingBufferSize;
  /** @var double */
  public $averageHitDistanceXZ; #average Reach distance across a set amount of hits
  /** @var double */
  public $directionDotProduct;
  /** @var double */
  public $directionDotProductXZ;
  /** @var double */
  public $hitAngle; #Hit Angle
  /** @var double */
  public $hitAngleXZ; #Hit Angle (only X and Z axis)
  /** @var double */
  public $lastHitAngleXZ;
  /** @var double */
  public $hitAngleXZSum;
  /** @var double[] */
  public $hitAngleXZRingBuffer;
  /** @var int */
  public $hitAngleXZRingBufferIndex;
  /** @var int */
  public $hitAngleXZRingBufferSize;
  /** @var int */
  public $alreadyAnalyzedHitAngleXZHits;
  /** @var double */
  public $averageHitAngleXZ; #Average Hit Angle across a set amount of hits
  /** @var double */
  public $headMove; #Head movement since last hit
  /** @var double */
  public $hitAngleXZDifference;
  /** @var double */
  public $hitAngleXZDifferenceSum;
  /** @var double[] */
  public $hitAngleXZDifferenceRingBuffer;
  /** @var int */
  public $hitAngleXZDifferenceRingBufferIndex;
  /** @var int */
  public $hitAngleXZDifferenceRingBufferSize;
  /** @var int */
  public $alreadyAnalyzedHitAngleXZDifferenceHits;
  /** @var double */
  public $averageHitAngleXZDifference; #Average hit angle difference among a set amount of hits where the head has been moved before
  /** @var double */
  public $averageHitTimeDifference;
  /** @var double */
  public $averageCPS; #Average Clicks Per Seconds
  /** @var int */
  public $alreadyAnalyzedHits;

  #movement

  /** @var Vector3 */
  public $FromXZPos;
  /** @var Vector3 */
  public $ToXZPos;
  /** @var Vector3 */
  public $FromYPos;
  /** @var Vector3 */
  public $ToYPos;
  /** @var double */
  public $XZDistance;
  /** @var double */
  public $YDistance;
  /** @var int */
  public $PreviousTick;
  /** @var double[] */
  public $XZTimeRingBuffer;
  /** @var double[] */
  public $XZDistanceRingBuffer;
  /** @var double[] */
  public $YTimeRingBuffer;
  /** @var double[] */
  public $YDistanceRingBuffer;
  /** @var int */
  public $XZRingBufferSize;
  /** @var int */
  public $YRingBufferSize;
  /** @var int */
  public $XZRingBufferIndex;
  /** @var int */
  public $YRingBufferIndex;
  /** @var double */
  public $XZTimeSum;
  /** @var double */
  public $XZDistanceSum;
  /** @var double */
  public $YTimeSum;
  /** @var double */
  public $YDistanceSum;
  /** @var double */
  public $XZSpeed; #Average Travel Speed (XZ-Axis)
  /** @var double */
  public $YSpeed; #Average Travel Speed (Y-Axis)
  /** @var bool */
  public $ignoredMove;
  /** @var double */
  public $TimeDiff;
  /** @var int */
  public $lastMotionTick;

  /**
   * Analyzer constructor.
   * @param Player $plr
   * @param Main $sac
   */
  public function __construct(Player $plr, Main $sac)
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
    $this->averageHitTimeDifference                = 0.0;
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
    $this->lastMotionTick          = -1.0;
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

  /**
   * @param PlayerMoveEvent $event
   */
  public function onPlayerMoveEvent(PlayerMoveEvent $event) : void
  {
    #process event first
    $this->processPlayerMoveEvent($event);
    #then run checks
    $this->CheckRegister->runChecksOnPlayerMoveEvent($event);
  }

  /**
   * @param EntityMotionEvent $event
   */
  public function onPlayerReceivesMotion(EntityMotionEvent $event): void
  {
    if(($event->getVector()->getX() != 0) || ($event->getVector()->getY() != 0) || ($event->getVector()->getZ() != 0)) #ignore motions that don't actually move the player
    {
      $this->lastMotionTick = (double)$this->Server->getTick();
      $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: ".$this->PlayerName."] [Debug: Movement] > Motion Received!");
    }
  }

  /**
   * @param EntityDamageEvent $event
   */
  public function onPlayerGetsHit(EntityDamageEvent $event): void
  {
  
  }

  /**
   * @param EntityDamageByEntityEvent $event
   */
  public function onPlayerPerformsHit(EntityDamageByEntityEvent $event): void
  {
    #process data
    $this->processPlayerPerformsHit($event);
    #then run checks
    $this->CheckRegister->runChecksOnPlayerPerformsHit($event);
  }

  # processing functions
  # these will process data retrieved from events

  /**
   * @param PlayerMoveEvent $event
   */
  public function processPlayerMoveEvent(PlayerMoveEvent $event) : void
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

  /**
   * @param EntityDamageByEntityEvent $event
   */
  public function processPlayerPerformsHit(EntityDamageByEntityEvent $event) : void
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
        $this->averageCPS = (1 / $this->averageHitTimeDifference);
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

  /**
   * @param string $message
   */
  public function kickPlayer(string $message) : void
  {
    $this->Main->getScheduler()->scheduleDelayedTask(new KickTask($this->Main, $this->Player, $message), 1);
  }

}

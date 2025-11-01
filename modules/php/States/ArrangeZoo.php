<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * babylonia implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2025 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 */

declare(strict_types=1);

namespace Bga\Games\zooloretto\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\zooloretto\Decoder;
use Bga\Games\zooloretto\Game;

class ArrangeZoo extends AbstractState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 5,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must arrange tiles in his Zoo.'),
            descriptionMyTurn: clienttranslate('${you} must arrange tiles in your Zoo.'),
        );
    }

    public function getArgs(int $active_player_id): array
    {
        return [];
    }

    #[PossibleAction]
    public function actAutoArrangeTiles(int $active_player_id, string $wagonid, string $tileid1, string $posid1, string $tileid2, string $posid2, string $tileid3, string $posid3, string $x1, string $y1, string $x2, string $y2, string $x3, string $y3): mixed {

		$player_id = $active_player_id;
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val1 = "";
		$val2 = "";
		$val3 = "";
		if ($tileid1!="")
		{
			$val1 = $this->game->getUniqueValueFromDB("select val from animals where id ='$tileid1'" );

			$sql = "update animals set x = $x1, y = $y1, status = 'THINKING', player_id = '$player_id' where id = '$tileid1'";
			$this->game->DbQuery( $sql );

			$sql = "update wagons set val$posid1 = '' where id = '$wagonid'";
			$this->game->DbQuery( $sql );
		}
		if ($tileid2!="")
		{
			$val2 = $this->game->getUniqueValueFromDB("select val from animals where id ='$tileid2'" );

			$sql = "update animals set x = $x2, y = $y2, status = 'THINKING', player_id = '$player_id' where id = '$tileid2'";
			$this->game->DbQuery( $sql );

			$sql = "update wagons set val$posid2 = '' where id = '$wagonid'";
			$this->game->DbQuery( $sql );
		}
		if ($tileid3!="")
		{
			$val3 = $this->game->getUniqueValueFromDB("select val from animals where id ='$tileid3'" );

			$sql = "update animals set x = $x3, y = $y3, status = 'THINKING', player_id = '$player_id' where id = '$tileid3'";
			$this->game->DbQuery( $sql );

			$sql = "update wagons set val$posid3 = '' where id = '$wagonid'";
			$this->game->DbQuery( $sql );
		}

		$this->game->notifyAllPlayers( "AutoArrangeTiles", clienttranslate( '${player_name} decided to auto arrange his tiles from the wagon.'),
		array(
			'player_id' => $player_id,
			'wagonid' => $wagonid,
			'tileid1' => $tileid1,
			'posid1' => $posid1,
			'tileid2' => $tileid2,
			'posid2' => $posid2,
			'tileid3' => $tileid3,
			'posid3' => $posid3,
			'val1' => $val1,
			'val2' => $val2,
			'val3' => $val3,
			'x1' => $x1,
			'y1' => $y1,
			'x2' => $x2,
			'y2' => $y2,
			'x3' => $x3,
			'y3' => $y3,
		) );
        return null;
    }

    #[PossibleAction]
    public function actConfirmArrangement(int $active_player_id): mixed {
		$player_id = $active_player_id;
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$wagonid = $this->game->getUniqueValueFromDB("select id from wagons where status = 'TAKEN'" );

		$coins = $this->game->getUniqueValueFromDB("select count(*) from animals where val = 'Coin' and status = 'WAGON' and x=$wagonid" );

		if (intval($coins)>0)
		{
			$cointiles = $this->game->getObjectListFromDB( "SELECT concat('tile_0_',id,'_Coin_',x,'_',y) cointiles, id FROM `animals` WHERE val='Coin' and status = 'WAGON' and x=$wagonid");

			$sql = "update player set money = money + " . intval($coins) . " where player_id = '$player_id'";
			$this->game->DbQuery( $sql );

			$this->game->incStat( intval($coins), "coinsreceived", $player_id);

			$sql = "update animals set status = 'DISCARDED' where val = 'Coin' and status = 'WAGON' and x=$wagonid";
			$this->game->DbQuery( $sql );

			$this->game->notifyAllPlayers( "GetMoney", clienttranslate( '${player_name} collected ${coins} money.'),
			array(
				'player_id' => $player_id,
				'coins' => $coins,
				'cointiles' => $cointiles,
			) );
		}


		$stall = $this->game->getUniqueValueFromDB("select count(*) from animals where status = 'WAGON' and x=$wagonid" );

		if (intval($stall)>0)
		{
			$stalltiles = $this->game->getObjectListFromDB( "SELECT concat('tile_0_',id,'_',val,'_',x,'_',y) stalltiles, id, val,x,y FROM `animals` WHERE status = 'WAGON' and x=$wagonid");

			$sql = "update animals set player_id= '$player_id', status = 'STALL', x=0, y=0 where status = 'WAGON' and x=$wagonid";
			$this->game->DbQuery( $sql );

			$this->game->notifyAllPlayers( "GotoStall", clienttranslate( '${player_name} put ${stall} tiles in his Barn.'),
			array(
				'player_id' => $player_id,
				'stall' => $stall,
				'stalltiles' => $stalltiles,
			) );
		}

		////////////////////////////
		// KIDS
		////////////////////////////

		$enclosures = $this->game->getObjectListFromDB( "SELECT distinct x as x from animals where status = 'THINKING'" );
		foreach( $enclosures as $index => $enclosure)
		{
			$males = intval($this->game->getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
			$females = intval($this->game->getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
			$kids = intval($this->game->getUniqueValueFromDB("select count(*) from animals where val like '_K' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
			$parents = min($males,$females);
			$maxid = intval($this->game->getUniqueValueFromDB("select MAX(id) from animals where id < 300" ));
			$spacesoccupied = intval($this->game->getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
			$animal = $this->game->getUniqueValueFromDB("select distinct LEFT(val , 1) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'");
			$spacesleft = 0;
			if ($enclosure['x']=="1") $spacesleft = 5 - $spacesoccupied;
			else if ($enclosure['x']=="2") $spacesleft = 4 - $spacesoccupied;
			else if ($enclosure['x']=="3") $spacesleft = 6 - $spacesoccupied;
			else if ($enclosure['x']=="4") $spacesleft = 5 - $spacesoccupied;
			else if ($enclosure['x']=="5") $spacesleft = 5 - $spacesoccupied;
			$totalkids = $parents; //-$kids;

			$sql = "update animals set id = id + 300 where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
			$this->game->DbQuery( $sql );
			$sql = "update animals set id = id + 300 where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
			$this->game->DbQuery( $sql );

				$newparents = $this->game->getObjectListFromDB( "SELECT concat('tile_',b.player_no,'_',a.id-300,'_',a.val,'_',a.x,'_',a.y) oldparenttile, concat('tile_',b.player_no,'_',a.id,'_',a.val,'_',a.x,'_',a.y) parenttile, a.id, a.val,a.x,a.y, b.player_no FROM `animals` a, player b WHERE a.player_id = b.player_id and a.id > 300");

			for( $x=1; $x<= $totalkids; $x++ )
			{
				if ($spacesleft>0)
				{
					$maxid = $maxid + 1;
					$spacesleft = $spacesleft - 1;
					$spacesoccupied = $spacesoccupied + 1;

					$found = false;
					$spaceid = 0;
					while (!$found)
					{
						$spaceid = $spaceid + 1;
						$check = intval($this->game->getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "' and y='$spaceid'" ));
						if ($check==0)
						{
							$found = true;
						}
					}

					$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKID',".$enclosure['x'].", $spaceid)";
					$this->game->DbQuery( $sql );
					$kids = $this->game->getObjectListFromDB( "SELECT concat('tile_',$player_no,'_',id,'_',val,'_',x,'_',y) kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKID'");
					$kidsstall = "";

					$this->game->notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his enclosure.'),
					array(
						'player_id' => $player_id,
						'kids' => $kids,
						'kidsstall' => $kidsstall,
						'translatedval' => Decoder::Animal($animal."K"),
						'newparents'=>$newparents,
						'i18n' => array( 'translatedval' )
					) );

					$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
					$this->game->DbQuery( $sql );
					$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
					$this->game->DbQuery( $sql );
				}
				else
				{
					$maxid = $maxid + 1;
					$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKIDSTALL',0,0)";
					$this->game->DbQuery( $sql );
					$kids = "";
					$kidsstall = $this->game->getObjectListFromDB( "SELECT concat('tile_',".$player_no.",'_',id,'_',val,'_',x,'_',y) as kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKIDSTALL'");

					$this->game->notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his Barn.'),
					array(
						'player_id' => $player_id,
						'kids' => $kids,
						'kidsstall' => $kidsstall,
						'translatedval' => Decoder::Animal($animal."K"),
						'newparents'=>$newparents,
						'i18n' => array( 'translatedval' )
					) );

					$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
					$this->game->DbQuery( $sql );
					$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
					$this->game->DbQuery( $sql );
				}
			}

			if ($spacesleft==0)
			{
				$coinsgained = 0;
				if ($enclosure['x']=="1") $coinsgained=2;
				else if ($enclosure['x']=="2") $coinsgained=1;
				else if ($enclosure['x']=="3") $coinsgained=0;
				else if ($enclosure['x']=="4") $coinsgained=1;
				else if ($enclosure['x']=="5") $coinsgained=1;

				if ($coinsgained>0)
				{
					$coinsbefore = intval($this->game->getUniqueValueFromDB("select money from player where player_id = '$player_id'" ));

					$sql = "update player set money = money + " . intval($coinsgained) . " where player_id = '$player_id'";
					$this->game->DbQuery( $sql );

					$this->game->incStat( intval($coinsgained), "coinsreceived", $player_id);

					$this->game->notifyAllPlayers( "CoinsGained", clienttranslate( '${player_name} gained ${coinsgained} money for completing his ${pos} enclosure.'),
					array(
						'player_id' => $player_id,
						'coinsgained' => $coinsgained,
						'coinsbefore' => $coinsbefore,
						'enclosure' => $enclosure['x'],
						'pos' => Decoder::Pos($enclosure['x']),
						'i18n' => array( 'pos' )
					) );
				}
			}
		}

		$sql = "update animals set status = 'PLAYED' where status = 'THINKING'";
		$this->game->DbQuery( $sql );
		$sql = "update wagons set status = 'PLAYED' where status = 'TAKEN'";
		$this->game->DbQuery( $sql );
		$sql = "update player set skipped='Y' where player_id = '$player_id'";
		$this->game->DbQuery( $sql );

		$this->game->notifyAllPlayers( "ConfirmArrangement", clienttranslate( '${player_name} confirmed the arrangement of his zoo.'),
		array(
			'player_id' => $player_id,
			'wagonid' => $wagonid,
		) );
        return NextPlayer::class;
    }

    #[PossibleAction]
    public function actReset(int $active_player_id): mixed {
		$player_id = $active_player_id;
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$wagonid = $this->game->getUniqueValueFromDB("select id from wagons where status = 'TAKEN'" );
		$wagonsize = $this->game->getUniqueValueFromDB("select size from wagons where status = 'TAKEN'" );
		$val1 = $this->game->getUniqueValueFromDB("select val1 from wagons where status = 'TAKEN'" );
		$val2 = $this->game->getUniqueValueFromDB("select val2 from wagons where status = 'TAKEN'" );
		$val3 = $this->game->getUniqueValueFromDB("select val3 from wagons where status = 'TAKEN'" );
		$thinkings = $this->game->getObjectListFromDB( "SELECT concat('tile_',$player_no,'_',id,'_',val,'_',x,'_',y) as thinking,id,val,x,y from animals where status = 'THINKING'" );
		$found = false;
		$pos1="";
		$pos2="";
		$pos3="";
		foreach( $thinkings as $index => $thinking)
		{
			$found = true;
			if ($val1==null || $val1=="")
			{
				$sql = "update animals set status = 'WAGON', player_id = 0, x = $wagonid, y = 1 where id = '".$thinking['id']."'";
				$this->game->DbQuery( $sql );
				$sql = "update wagons set val1 = '".$thinking['id']."' where status = 'TAKEN'";
				$this->game->DbQuery( $sql );
				$val1 = $thinking['id'];
				if ($pos1=="") $pos1="1";
				else if ($pos2=="") $pos2="1";
				else if ($pos3=="") $pos3="1";
			}
			else if ($val2==null || $val2=="")
			{
				$sql = "update animals set status = 'WAGON', player_id = 0, x = $wagonid, y = 2 where id = '".$thinking['id']."'";
				$this->game->DbQuery( $sql );
				$sql = "update wagons set val2 = '".$thinking['id']."' where status = 'TAKEN'";
				$this->game->DbQuery( $sql );
				$val2 = $thinking['id'];
				if ($pos1=="") $pos1="2";
				else if ($pos2=="") $pos2="2";
				else if ($pos3=="") $pos3="2";
			}
			else if ($val3==null || $val3=="")
			{
				$sql = "update animals set status = 'WAGON', player_id = 0, x = $wagonid, y = 3 where id = '".$thinking['id']."'";
				$this->game->DbQuery( $sql );
				$sql = "update wagons set val3 = '".$thinking['id']."' where status = 'TAKEN'";
				$this->game->DbQuery( $sql );
				$val3 = $thinking['id'];
				if ($pos1=="") $pos1="3";
				else if ($pos2=="") $pos2="3";
				else if ($pos3=="") $pos3="3";
			}
		}

		$this->notify->all( "Reset", clienttranslate( '${player_name} reset his wagon during the zoo arrangement.'),
		array(
			'player_id' => $player_id,
			'thinkings' => $thinkings,
			'wagonid' => $wagonid,
			'pos1'=>$pos1,
			'pos2'=>$pos2,
			'pos3'=>$pos3,
		) );
        return null;
    }

    #[PossibleAction]
    public function actGoBack(int $active_player_id, string $x): mixed {
        // FIXME: probably shouldn't directly call other action.
        $this->actReset($active_player_id);

        $player_id = $active_player_id;
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );

		$sql = "update player set skipped='N' where player_id = '$player_id'";
		$this->game->DbQuery( $sql );

		$sql = "update wagons set status = 'AVAILABLE' where id = '$x'";
		$this->game->DbQuery( $sql );

		$wagontiles = $this->game->getObjectListFromDB( "SELECT concat('tile_0_',id,'_',val,'_',x,'_',y) wagontile, id FROM `animals` WHERE status = 'WAGON' and x=$x");

		$this->game->notifyAllPlayers( "GoBackWagon", clienttranslate( '${player_name} decided to lay down the wagon taken.'),
		array(
			'player_id' => $player_id,
			'x' => $x,
			'wagontiles' => $wagontiles,
			'i18n' => array( 'wag' )
		) );
        return PlayerTurn::class;
    }

    #[PossibleAction]
    public function actArrangeTiles(int $active_player_id,
									string $tileid,
                                    string $wagonid,
                                    string $posid,
                                    string $x,
                                    string $y,
                                    string $pid): mixed {
		$player_id = $active_player_id;
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = $this->game->getUniqueValueFromDB("select val from animals where id ='$tileid'" );

		$sql = "update animals set x = $x, y = $y, status = 'THINKING', player_id = '$player_id' where id = '$tileid'";
		$this->game->DbQuery( $sql );
		if ($pid=="0")
		{
			$sql = "update wagons set val$posid = '' where id = '$wagonid'";
			$this->game->DbQuery( $sql );
		}

		$this->game->notifyAllPlayers( "ArrangeTiles", clienttranslate( '${player_name} placed the ${translatedval} in his ${pos} enclosure.'),
		array(
			'player_id' => $player_id,
			'tileid' => $tileid,
			'wagonid' => $wagonid,
			'posid' => $posid,
			'val' => $val,
			'x' => $x,
			'y' => $y,
			'pid' => $pid,
			'translatedval' => Decoder::Animal($val),
			'pos' => Decoder::Pos($x),
			'i18n' => array( 'translatedval', 'pos' )
		) );
		return null;
    }

    function zombie(int $playerId): mixed {
        // FIXME
        return "";
    }
}

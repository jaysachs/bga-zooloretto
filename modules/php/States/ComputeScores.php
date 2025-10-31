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
use Bga\GameFramework\States\GameState;
use Bga\Games\zooloretto\Decoder;
use Bga\Games\zooloretto\Game;
use Bga\Games\zooloretto\Model\Model;

class ComputeScores extends GameState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 90,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(): mixed
    {
        //////////////////////////////
        /// FULL ENCLOSURES
        //////////////////////////////
        $fullenclosures = $this->game->getObjectListFromDB( "select player_id, x,
                                                                                                      case
                                                                                                        when x = 1 then 8
                                                                                                        when x = 2 then 5
                                                                                                        when x = 3 then 10
                                                                                                        when x = 4 then 9
                                                                                                        when x = 5 then 9
                                                                                                        else 0
                                                                                                      end as score, count(*) from animals where status = 'PLAYED' and val not like 'Stall_' group by player_id, x having (x,count(*)) in ((1,5),(2,4),(3,6),(4,5),(5,5)) order by player_id" );
        foreach( $fullenclosures as $index => $fullenclosure)
        {
            $sql = "update player set player_score =  player_score + ".$fullenclosure['score']." where player_id = '" . $fullenclosure['player_id']. "'";
            $this->game->DbQuery( $sql );

            $pname = $this->game->getUniqueValueFromDB("select player_name from player where player_id='".$fullenclosure['player_id']."'" );
            $player_score = $this->game->getUniqueValueFromDB("select player_score from player where player_id='".$fullenclosure['player_id']."'" );
            $player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id='".$fullenclosure['player_id']."'" );

            $this->game->incStat( $fullenclosure['score'], "full" . $fullenclosure['x'], $fullenclosure['player_id']);

            $this->game->notifyAllPlayers( "Score", clienttranslate( '${player_name} scored ${points} points for his fully completed ${pos} enclosure.'),
                                    array(
                                        'player_id' => $fullenclosure['player_id'],
                                        'player_no' => $player_no,
                                        'points' => $fullenclosure['score'],
                                        'pos' => Decoder::Pos($fullenclosure['x']),
                                        'type' => 1,
                                        'enc' => $fullenclosure['x'],
                                        'player_name' => $pname,
                                        'player_score' => $player_score,
                                        'i18n' => array( 'pos' )
                                    ) );
        }

        //////////////////////////////
        /// PART FULL ENCLOSURES
        //////////////////////////////
        $partfullenclosures = $this->game->getObjectListFromDB( "select player_id, x,
                                                                                                      case                                                                                                         when x = 1 then 5
                                                                                                        when x = 2 then 4
                                                                                                        when x = 3 then 6
                                                                                                        when x = 4 then 5
                                                                                                        when x = 5 then 5
                                                                                                        else 0
                                                                                                      end as score, count(*) from animals where status = 'PLAYED' and val not like 'Stall_' group by player_id, x having (x,count(*)) in ((1,4),(2,3),(3,5),(4,4),(5,4)) order by player_id" );
        foreach( $partfullenclosures as $index => $partfullenclosure)
        {
            $sql = "update player set player_score =  player_score + ".$partfullenclosure['score']." where player_id = '" . $partfullenclosure['player_id']. "'";
            $this->game->DbQuery( $sql );

            $pname = $this->game->getUniqueValueFromDB("select player_name from player where player_id='".$partfullenclosure['player_id']."'" );
            $player_score = $this->game->getUniqueValueFromDB("select player_score from player where player_id='".$partfullenclosure['player_id']."'" );
            $player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id='".$partfullenclosure['player_id']."'" );

            $this->game->incStat( $partfullenclosure['score'], "part" . $partfullenclosure['x'], $partfullenclosure['player_id']);

            $this->game->notifyAllPlayers( "Score", clienttranslate( '${player_name} scored ${points} points for his ${pos} enclosure with one single space empty.'),
                                    array(
                                        'player_id' => $partfullenclosure['player_id'],
                                        'player_no' => $player_no,
                                        'points' => $partfullenclosure['score'],
                                        'pos' => Decoder::Pos($partfullenclosure['x']),
                                        'type' => 1,
                                        'enc' => $partfullenclosure['x'],
                                        'player_name' => $pname,
                                        'player_score' => $player_score,
                                        'i18n' => array( 'pos' )
                                    ) );
        }

        //////////////////////////////
        /// ENCLOSURES WITH STALLS
        //////////////////////////////
        $stallenclosures = $this->game->getObjectListFromDB( "select player_id, x,
                                                                                                                      count(*) as score, count(*) from animals a where status = 'PLAYED' and val not like 'Stall_'
                                                                                                                      and exists (select 1 from animals b where b.status = 'PLAYED' and b.val like 'Stall_' and a.player_id = b.player_id
                                                                                                                      and case when b.y=1 then 1 when b.y=2 then 2 when b.y=3 then 2 when b.y=4 then 3 when b.y=5 then 4 when b.y=6 then 5 end = a.x)
                                                                                                                      group by player_id, x
                                                                                                                      having (x,count(*)) not in ((1
,5),(2,4),(3,6),(4,5),(5,5),(1,4),(2,3),(3,5),(4,4),(5,4))
                                                                                                                      order by player_id" );
        foreach( $stallenclosures as $index => $stallenclosure)
        {
            $sql = "update player set player_score =  player_score + ".$stallenclosure['score']." where player_id = '" . $stallenclosure['
player_id']. "'";
            $this->game->DbQuery( $sql );

            $pname = $this->game->getUniqueValueFromDB("select player_name from player where player_id='".$stallenclosure['player_id']."'" );
            $player_score = $this->game->getUniqueValueFromDB("select player_score from player where player_id='".$stallenclosure['player_id']."'
" );
            $player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id='".$stallenclosure['player_id']."'" );

            $this->game->incStat( $stallenclosure['score'], "encstall" . $stallenclosure['x'], $stallenclosure['player_id']);

            $this->game->notifyAllPlayers( "Score", clienttranslate( '${player_name} scored ${points} points for his ${pos} enclosure with a Stal
l.'),
                                    array(
                                        'player_id' => $stallenclosure['player_id'],
                                        'player_no' => $player_no,
                                        'points' => $stallenclosure['score'],
                                        'pos' => Decoder::Pos($stallenclosure['x']),
                                        'type' => 1,
                                        'enc' => $stallenclosure['x'],
                                        'player_name' => $pname,
                                        'player_score' => $player_score,
                                        'i18n' => array( 'pos' )
                                    ) );
        }

        //////////////////////////////
        /// DIFFERENT STALLS
        //////////////////////////////
        $differentstalls = $this->game->getObjectListFromDB( "select player_id, count(distinct val) diffstalls, count(distinct val)*2 score from anim
als
                                                                                                                      where status = 'PLAYED'
                                                                                                                      and val like 'Stall_'
                                                                                                                      group by player_id
                                                                                                                      order by player_id" );
        foreach( $differentstalls as $index => $differentstall)
        {
            $sql = "update player set player_score =  player_score + ".$differentstall['score']." where player_id = '" . $differentstall['
player_id']. "'";
            $this->game->DbQuery( $sql );

            $pname = $this->game->getUniqueValueFromDB("select player_name from player where player_id='".$differentstall['player_id']."'" );
            $player_score = $this->game->getUniqueValueFromDB("select player_score from player where player_id='".$differentstall['player_id']."'
" );
            $player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id='".$differentstall['player_id']."'" );

            $this->game->incStat( $differentstall['score'], "stalls", $differentstall['player_id']);

            $this->game->notifyAllPlayers( "Score", clienttranslate( '${player_name} scored ${points} points his ${diffstalls} different Stalls.'
            ),
                                    array(
                                        'player_id' => $differentstall['player_id'],
                                        'player_no' => $player_no,
                                        'points' => $differentstall['score'],
                                        'diffstalls' => $differentstall['diffstalls'],
                                        'type' => 2,
                                        'player_name' => $pname,
                                        'player_score' => $player_score,
                                    ) );
        }

        //////////////////////////////
        /// LEFT IN STALLS
        //////////////////////////////
        $leftstalls = $this->game->getObjectListFromDB( "select player_id, case when val like 'Stall_' then val else left(val,1) end val, 2 score fro
m animals
                                                                                                                      where status = 'STALL'
                                                                                                                      group by player_id, case when
val like 'Stall_' then val else left(val,1) end
                                                                                                                      order by player_id" );
        foreach( $leftstalls as $index => $leftstall)
        {
            $sql = "update player set player_score =  player_score - ".$leftstall['score']." where player_id = '" . $leftstall['player_id'
            ]. "'";
            $this->game->DbQuery( $sql );

            $pname = $this->game->getUniqueValueFromDB("select player_name from player where player_id='".$leftstall['player_id']."'" );
            $player_score = $this->game->getUniqueValueFromDB("select player_score from player where player_id='".$leftstall['player_id']."'" );
            $player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id='".$leftstall['player_id']."'" );

            $this->game->incStat( $leftstall['score'], "leftinbarn", $leftstall['player_id']);

            $this->game->notifyAllPlayers( "Score", clienttranslate( '${player_name} lost -${points} points his ${translatedval} in his Barn.'),
                                    array(
                                        'player_id' => $leftstall['player_id'],
                                        'player_no' => $player_no,
                                        'points' => $leftstall['score'],
                                        'type' => 3,
                                        'key' => $leftstall['val'],
                                        'player_name' => $pname,
                                        'player_score' => $player_score,
                                        'translatedval' => Decoder::Animal($leftstall['val']),
                                        'i18n' => array( 'translatedval' )
                                    ) );
        }


        $finalscore = $this->game->getObjectListFromDB( "select player_id, player_score, money from player" );
        foreach( $finalscore as $index => $fs)
        {
            $this->game->incStat( $fs['player_score'], "totalpoints", $fs['player_id']);
            $this->game->incStat( $fs['money'], "totalcoins", $fs['player_id']);
        }

        $sql = "UPDATE player set player_score_aux = money";
        $this->game->DbQuery( $sql );

        return 99;
    }

}

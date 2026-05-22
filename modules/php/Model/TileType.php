<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
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

namespace Bga\Games\zooloretto\Model;

// FIXME: do I want an EMPTY type? then won't need null checks ....

enum TileType: string {
    // Animals
    case CAMEL = 'C';
    case CAMEL_MALE = 'CM';
    case CAMEL_FEMALE = 'CF';
    case CAMEL_KID = 'CK';

    case ELEPHANT = 'E';
    case ELEPHANT_MALE = 'EM';
    case ELEPHANT_FEMALE = 'EF';
    case ELEPHANT_KID = 'EK';

    case FLAMINGO = 'F';
    case FLAMINGO_MALE = 'FM';
    case FLAMINGO_FEMALE = 'FF';
    case FLAMINGO_KID = 'FK';

    case KANGAROO = 'K';
    case KANGAROO_MALE = 'KM';
    case KANGAROO_FEMALE = 'KF';
    case KANGAROO_KID = 'KK';

    case LEOPARD = 'L';
    case LEOPARD_MALE = 'LM';
    case LEOPARD_FEMALE = 'LF';
    case LEOPARD_KID = 'LK';

    case MONKEY = 'M';
    case MONKEY_MALE = 'MM';
    case MONKEY_FEMALE = 'MF';
    case MONKEY_KID = 'MK';

    case PANDA = 'P';
    case PANDA_MALE = 'PM';
    case PANDA_FEMALE = 'PF';
    case PANDA_KID = 'PK';

    case ZEBRA = 'Z';
    case ZEBRA_MALE = 'ZM';
    case ZEBRA_FEMALE = 'ZF';
    case ZEBRA_KID = 'ZK';

    // Stalls

    case KIOSK = 'StallA';
    case BARROW = 'StallB';
    case SNACKS = 'StallC';
    case POPCORN = 'StallD';

    // Others

    case COIN = 'Coin';
    // 2p unused flipped tile to block spaces
    case BLOCK = 'block';
    case EMPTY = '';

    public function isSameSpecies(TileType $other): bool {
        return $this != self::EMPTY
            && $other != self::EMPTY
            && $this->isAnimal()
            && $other->isAnimal()
            && strval($this->value)[0] == strval($other->value)[0];
    }

    public function isPlaceable(): bool {
        return $this->isAnimal() || $this->isStall();
    }

    public function isEmpty(): bool {
        return $this == TileType::EMPTY;
    }

    public function isBlock(): bool {
        return $this == TileType::BLOCK;
    }

    /** @return TileType[] */
    public static function allCanonicalAnimals() : array {
        return [
            TileType::CAMEL,
            TileType::ELEPHANT,
            TileType::FLAMINGO,
            TileType::KANGAROO,
            TileType::LEOPARD,
            TileType::MONKEY,
            TileType::PANDA,
            TileType::ZEBRA
        ];
    }

    public function canonicalType(): TileType {
        return match ($this) {
            TileType::CAMEL_MALE,
            TileType::CAMEL_FEMALE,
            TileType::CAMEL_KID,
            TileType::CAMEL => TileType::CAMEL,

            TileType::ELEPHANT_MALE,
            TileType::ELEPHANT_FEMALE,
            TileType::ELEPHANT_KID,
            TileType::ELEPHANT => TileType::ELEPHANT,

            TileType::FLAMINGO_MALE,
            TileType::FLAMINGO_FEMALE,
            TileType::FLAMINGO_KID,
            TileType::FLAMINGO => TileType::FLAMINGO,

            TileType::KANGAROO_MALE,
            TileType::KANGAROO_FEMALE,
            TileType::KANGAROO_KID,
            TileType::KANGAROO => TileType::KANGAROO,

            TileType::LEOPARD_MALE,
            TileType::LEOPARD_FEMALE,
            TileType::LEOPARD_KID,
            TileType::LEOPARD => TileType::LEOPARD,

            TileType::MONKEY_MALE,
            TileType::MONKEY_FEMALE,
            TileType::MONKEY_KID,
            TileType::MONKEY => TileType::MONKEY,

            TileType::PANDA_MALE,
            TileType::PANDA_FEMALE,
            TileType::PANDA_KID,
            TileType::PANDA => TileType::PANDA,

            TileType::ZEBRA_MALE,
            TileType::ZEBRA_FEMALE,
            TileType::ZEBRA_KID,
            TileType::ZEBRA => TileType::ZEBRA,

            default => $this,
        };
    }

    public function isOffspring(): bool {
        return match ($this) {
            TileType::CAMEL_KID,
            TileType::ELEPHANT_KID,
            TileType::FLAMINGO_KID,
            TileType::KANGAROO_KID,
            TileType::LEOPARD_KID,
            TileType::MONKEY_KID,
            TileType::PANDA_KID,
            TileType::ZEBRA_KID => true,
            default => false
        };
    }

    public function isMale(): bool {
        return match ($this) {
            TileType::CAMEL_MALE,
            TileType::ELEPHANT_MALE,
            TileType::FLAMINGO_MALE,
            TileType::KANGAROO_MALE,
            TileType::LEOPARD_MALE,
            TileType::MONKEY_MALE,
            TileType::PANDA_MALE,
            TileType::ZEBRA_MALE => true,
            default => false,
        };
    }

    public function childType(): TileType {
        return match ($this) {
            self::CAMEL_FEMALE,    self::CAMEL_MALE    => self::CAMEL_KID,
            self::ELEPHANT_FEMALE, self::ELEPHANT_MALE => self::ELEPHANT_KID,
            self::FLAMINGO_FEMALE, self::FLAMINGO_MALE => self::FLAMINGO_KID,
            self::KANGAROO_FEMALE, self::KANGAROO_MALE => self::KANGAROO_KID,
            self::LEOPARD_FEMALE,  self::LEOPARD_MALE  => self::LEOPARD_KID,
            self::MONKEY_FEMALE,   self::MONKEY_MALE   => self::MONKEY_KID,
            self::PANDA_FEMALE,    self::PANDA_MALE    => self::PANDA_KID,
            self::ZEBRA_FEMALE,    self::ZEBRA_MALE    => self::ZEBRA_KID,
            default => throw new ModelException("No child type for {$this->value}"),
        };
    }

    public function isFemale(): bool {
        return match ($this) {
            TileType::CAMEL_FEMALE,
            TileType::ELEPHANT_FEMALE,
            TileType::FLAMINGO_FEMALE,
            TileType::KANGAROO_FEMALE,
            TileType::LEOPARD_FEMALE,
            TileType::MONKEY_FEMALE,
            TileType::PANDA_FEMALE,
            TileType::ZEBRA_FEMALE => true,
            default => false,
        };
    }

    public function isAnimal(): bool {
        return match ($this) {
            TileType::BLOCK,
            TileType::EMPTY,
            TileType::COIN => false,
            default => !$this->isStall(),
        };
    }

    public function isStall(): bool {
        return match ($this) {
            TileType::KIOSK,
            TileType::BARROW,
            TileType::SNACKS,
            TileType::POPCORN => true,
            default => false,
        };
    }

    public function translated(): string {
        return match ($this) {
            self::CAMEL => clienttranslate('Camel'),
            self::CAMEL_FEMALE => clienttranslate('Female Camel'),
            self::CAMEL_MALE => clienttranslate('Male Camel'),
		    self::CAMEL_KID => clienttranslate('Pup Camel'),
            self::ELEPHANT => clienttranslate('Elephant'),
		    self::ELEPHANT_FEMALE => clienttranslate('Female Elephant'),
            self::ELEPHANT_MALE => clienttranslate('Male Elephant'),
            self::ELEPHANT_KID => clienttranslate('Pup Elephant'),
            self::FLAMINGO => clienttranslate('Flamingo'),
            self::FLAMINGO_FEMALE => clienttranslate('Female Flamingo'),
            self::FLAMINGO_MALE => clienttranslate('Male Flamingo'),
            self::FLAMINGO_KID => clienttranslate('Pup Flamingo'),
            self::KANGAROO => clienttranslate('Kangaroo'),
            self::KANGAROO_FEMALE => clienttranslate('Female Kangaroo'),
            self::KANGAROO_MALE => clienttranslate('Male Kangaroo'),
            self::KANGAROO_KID => clienttranslate('Pup Kangaroo'),
            self::LEOPARD => clienttranslate('Leopard'),
            self::LEOPARD_FEMALE => clienttranslate('Female Leopard'),
            self::LEOPARD_MALE => clienttranslate('Male Leopard'),
            self::LEOPARD_KID => clienttranslate('Pup Leopard'),
            self::MONKEY => clienttranslate('Monkey'),
            self::MONKEY_FEMALE  => clienttranslate('Female Monkey'),
            self::MONKEY_MALE => clienttranslate('Male Monkey'),
            self::MONKEY_KID => clienttranslate('Pup Monkey'),
            self::PANDA => clienttranslate('Panda'),
            self::PANDA_FEMALE => clienttranslate('Female Panda'),
            self::PANDA_MALE => clienttranslate('Male Panda'),
            self::PANDA_KID => clienttranslate('Pup Panda'),
            self::ZEBRA => clienttranslate('Zebra'),
            self::ZEBRA_FEMALE => clienttranslate('Female Zebra'),
            self::ZEBRA_MALE => clienttranslate('Male Zebra'),
            self::ZEBRA_KID => clienttranslate('Pup Zebra'),
            self::KIOSK => clienttranslate('Kiosk Stall'),
            self::BARROW => clienttranslate('Barrow Stall'),
            self::SNACKS => clienttranslate('Snacks Stall'),
            self::POPCORN => clienttranslate('Popcorn Stall'),
            self::COIN => clienttranslate('Coin'),
            default => ""
        };
    }
}

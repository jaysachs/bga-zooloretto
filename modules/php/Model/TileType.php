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

enum TileType: string {
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

    case COIN = 'Coin';

    case KIOSK = 'StallA';
    case BARROW = 'StallB';
    case SNACKS = 'StallC';
    case POPCORN = 'StallD';

    public function isSameSpecies(TileType $other): bool {
        return $this->isAnimal()
            && $other->isAnimal()
            && $this->value[0] == $other->value[0];
    }

    public function isKid(): bool {
        return match ($this) {
            TileType::CAMEL_KID,
            TileType::ELEPHANT_KID,
            TileType::FLAMINGO_KID,
            TileType::KANGAROO_KID,
            TileType::LEOPARD_KID,
            TileType::MONKEY_KID,
            TileType::PANDA_KID,
            TileType::ZEBRA_KID => true,
            default => false,
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
            TileType::COIN,
            TileType::KIOSK,
            TileType::BARROW,
            TileType::SNACKS,
            TileType::POPCORN => false,
            default => true,
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
}


#
ROOT=$(HOME)/projects/bga
GAME=zooloretto
SFTP=sftp://vagabond:@1.studio.boardgamearena.com:2022

STATS=modules/php/Stats.php
GENSTATS=$(ROOT)/bgautil/genstats/genstats.php
WORK=work
STUBS=$(WORK)/module/table/table.game.php
TESTSTUBS=$(WORK)/test/module/table/table.game.php
PSALM_CONFIG=psalm.xml
JS=$(GAME).js
COLORMAP=src/colormap.ts

.PHONY: build test psalm psalm-info deploy clean

build: $(JS) $(STUBS) # $(STATS)

$(JS): $(COLORMAP) src/*.ts
	npm run build:ts

$(STATS): $(GENSTATS) stats.json
	php $(GENSTATS) $(GAME)  > $(STATS)

$(COLORMAP): misc/colormap.php gameinfos.inc.php
	php misc/colormap.php > $(COLORMAP)

$(WORK):
	mkdir $(WORK)

$(STUBS): $(WORK) _ide_helper.php Makefile _local_ide_helper.php
	mkdir -p $(WORK)/module/table
	perl -p -e 's/  exit/\/\/ exit/;' -e 's/APP_GAMEMODULE_PATH = ""/APP_GAMEMODULE_PATH = "work\/"/' _ide_helper.php > $(STUBS)
	cat _local_ide_helper.php >> $(STUBS)

$(TESTSTUBS): $(WORK) _ide_helper.php Makefile
	mkdir -p $(WORK)/test/module/table
	perl -p -e 's/  exit/\/\/ exit/;' -e 's/APP_GAMEMODULE_PATH = ""/APP_GAMEMODULE_PATH = "work\/"/' _ide_helper.php > $(TESTSTUBS)

test: build $(TESTSTUBS)
	phpunit --bootstrap misc/autoload.php misc --testdox --display-warnings --display-deprecations --display-notices

psalm: build $(STUBS) $(PSALM_CONFIG)
	psalm -c $(PSALM_CONFIG) modules/php

psalm-info: build $(STUBS) $(PSALM_CONFIG)
	psalm --show-info=true -c $(PSALM_CONFIG) modules/php

deploy: build # test
	lftp -e 'cd $(GAME); mirror -R --exclude .git/ --exclude work/; exit' $(SFTP)

# TODO: should this remove colormap and stats as well?
clean:
	rm -rf $(WORK)

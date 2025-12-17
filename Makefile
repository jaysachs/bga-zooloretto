
#
ROOT=$(HOME)/projects/bga
GAME=zooloretto
SFTP=sftp://vagabond:@1.studio.boardgamearena.com:2022

STATS=modules/php/Stats.php
GENSTATS=$(ROOT)/bgautil/genstats/genstats.php
WORK=work
STUBS=$(WORK)/module/table/table.game.php
TS_STUBS=$(WORK)/bga-framework.d.ts
TESTSTUBS=$(WORK)/test/module/table/table.game.php
JS=$(GAME).js
COLORMAP=src/colormap.ts
PHPSTAN_LEVEL=10

.PHONY: build test phpstan deploy clean

build: $(JS) $(STUBS) # $(STATS)

$(JS): $(COLORMAP) src/*.ts tsconfig.json $(TS_STUBS)
	npm run build:ts

$(STATS): $(GENSTATS) stats.json
	php $(GENSTATS) $(GAME)  > $(STATS)

$(COLORMAP): misc/colormap.php gameinfos.inc.php
	php misc/colormap.php > $(COLORMAP)

$(WORK):
	mkdir $(WORK)

$(TS_STUBS): $(WORK) bga-framework.d.ts
	perl -p -e 's/bRealtime: boolean;/bRealtime: boolean;\n  notifqueue: GameNotifQueue;\n/' bga-framework.d.ts > $(TS_STUBS)

$(STUBS): $(WORK) _ide_helper.php Makefile _local_ide_helper.php
	mkdir -p $(WORK)/module/table
	perl -p -e 's/type_arg=null,/type_arg,/;' -e 's/  exit/\/\/ exit/;' -e 's/APP_GAMEMODULE_PATH = ""/APP_GAMEMODULE_PATH = "work\/"/;' -e 's/{}\(\)\;/{}\;/;' _ide_helper.php > $(STUBS)
	cat _local_ide_helper.php >> $(STUBS)

$(TESTSTUBS): $(WORK) _ide_helper.php Makefile
	mkdir -p $(WORK)/test/module/table
	perl -p -e 's/type_arg=null,/type_arg,/;' -e 's/  exit/\/\/ exit/;' -e 's/APP_GAMEMODULE_PATH = ""/APP_GAMEMODULE_PATH = "work\/"/' _ide_helper.php > $(TESTSTUBS)

test: build $(TESTSTUBS)
	phpunit --bootstrap misc/autoload.php misc --testdox

phpstan: build $(STUBS)
	phpstan --autoload-file=$(STUBS) --level=$(PHPSTAN_LEVEL) --memory-limit=1G analyse modules/php/Model modules/php modules/php/States

deploy: test
	lftp -e 'cd $(GAME); mirror -R --exclude .vscode/ --exclude .git/ --exclude work/ --exclude local/ --exclude bga-framework.d.ts --exclude node_modules/ --exclude _ide_helper.php; exit' $(SFTP)

# TODO: should this remove colormap and stats as well?
clean:
	rm -rf $(WORK)

BUILD_VER := $(shell php -r "require 'system/expressionengine/third_party/freemember/config.php'; echo FREEMEMBER_VERSION;")

all: dist
clean:
	rm -rf freemember-*
	rm -rf user_guide user_guide_src/_build
docs:
	rm -rf user_guide user_guide_src/_build
	$(MAKE) html --directory=user_guide_src
	cp -R user_guide_src/_build/html user_guide
dist: clean docs
	for i in `find system -name "*.php"`; do \
	  php -l $$i; \
	  if [ $$? -ne 0 ] ; then exit 1 ; fi \
	done
	zip -r freemember-$(BUILD_VER).zip README.md system user_guide -x "*/.*"
	unzip freemember-$(BUILD_VER).zip -d freemember-$(BUILD_VER)

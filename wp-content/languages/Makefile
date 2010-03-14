WP_DIR=$(shell dirname $(shell dirname $(shell pwd)))
POT_FILE=wordpress.pot

all:
	@echo "pot:   Generate new Template from MoinMoin source."
	@echo "zh:    Merge new pot template with zh_CN.po, and compile to mo file."

clean:
	rm -f *.mo

pot:
	@echo "Generating POT file from $(WP_DIR)"
	@php tools/makepot.php wp $(WP_DIR) $(POT_FILE)

%.po: $(POT_FILE)
	msgmerge $@ $(POT_FILE) > tmpfile.po
	mv tmpfile.po $@
	#msgfmt -o /dev/null --statistics -v $@

%.mo: %.po
	msgfmt --statistics -o $@ $<

zh:zh_CN.mo

.PHONY: all pot zh
# vim: noet

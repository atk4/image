SUBDIRS = $(wildcard [0-9].*)
.PHONY: $(SUBDIRS)
all: $(SUBDIRS)
$(SUBDIRS):
	cat Dockerfile  | sed 's/^FROM.*/FROM php:$@-alpine/' > $@/Dockerfile



SUBDIRS = $(wildcard ?.?)
.PHONY: $(SUBDIRS)
all: $(SUBDIRS)
$(SUBDIRS):
	cat Dockerfile  | sed 's/^FROM.*/FROM php:$@-alpine/' > $@/Dockerfile



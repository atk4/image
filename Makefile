SUBDIRS = $(wildcard [0-9].*)
.PHONY: $(SUBDIRS)
all: $(SUBDIRS)
$(SUBDIRS):
	@[ -f $@/Dockerfile ] || cat Dockerfile.template  | sed 's/^FROM.*/FROM php:$@-alpine/' > $@/Dockerfile | echo "Created $@/Dockerfile from Dockerfile.template"

clean:
	rm ?.?/Dockerfile

build: clean all
	# This is for your local testing. This command is not used in CI/CD
	(cd 7.2; docker build . -t atk4/image:7.2)
	(cd 7.3; docker build . -t atk4/image:7.3)
	(cd 7.4; docker build . -t atk4/image:7.4)
	(cd 8.0; docker build . -t atk4/image:8.0)

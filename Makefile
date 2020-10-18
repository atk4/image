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

test:
	# You can use this command to manually run test on 8.0, or modify it for any other image
	# Candidate tags are pushed even if CI/CD pipeline is not successful, so if it fails you can
	# debug it like this:
	docker run -v "$PWD":/usr/src/app -it atk4/image:candidate-8.0 php /usr/src/app/test.php

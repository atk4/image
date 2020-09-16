SUBDIRS = $(wildcard [0-9].*)
.PHONY: $(SUBDIRS)
all: $(SUBDIRS)
$(SUBDIRS):
	@[ -f $@/Dockerfile ] || cat Dockerfile.template  | sed 's/^FROM.*/FROM php:$@-alpine/' > $@/Dockerfile | echo "Created $@/Dockerfile from Dockerfile.template"


clean:
	rm ?.?/Dockerfile

build: clean all
	(cd 7.2; docker build . -t atk4/image:7.2)
	(cd 7.3; docker build . -t atk4/image:7.3)
	(cd 8.0.0alpha1; docker build . -t atk4/image:7.3)

test:
	docker run -v "$PWD":/usr/src/app -it atk4/image:candidate-8.0 php /usr/src/app/test.php
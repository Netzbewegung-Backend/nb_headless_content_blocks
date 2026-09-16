# See https://typo3.org/contribute/contribute-to-documentation/rest/templates/template-extensions/
.PHONY: help
help: ## Display this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"} /^[a-zA-Z_-]+:.*##/ { printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

.PHONY: docs
docs: ## Generate the documentation (from "Documentation") into Documentation-GENERATED-temp
	mkdir -p Documentation-GENERATED-temp && chmod 777 Documentation-GENERATED-temp

	docker run --rm -v "$(shell pwd)":/project --user=$(shell id -u):$(id -g) ghcr.io/typo3-documentation/render-guides:latest --config=Documentation --no-progress

	docker run --rm -v "$(shell pwd)":/project --entrypoint sh ghcr.io/typo3-documentation/render-guides:latest -c 'chmod -R a+rwX /project/Documentation-GENERATED-temp'

.PHONY: test-docs
test-docs: ## Test the documentation rendering (fails on warnings)
	mkdir -p Documentation-GENERATED-temp && chmod 777 Documentation-GENERATED-temp

	docker run --rm -v "$(shell pwd)":/project --user=$(shell id -u):$(id -g) ghcr.io/typo3-documentation/render-guides:latest --config=Documentation --no-progress --fail-on-log

	docker run --rm -v "$(shell pwd)":/project --entrypoint sh ghcr.io/typo3-documentation/render-guides:latest -c 'chmod -R a+rwX /project/Documentation-GENERATED-temp'

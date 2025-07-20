SSM_ENV=lus
SSM_PREFIX := /suma/${SSM_ENV}
ENV_FILE := .env
ENV_EXAMPLE ?= .env.example

REGION ?= us-east-1
AWS_PROFILE ?=

.PHONY: help fetch-secrets start clean

help:
	@echo "Usage: make [target] [AWS_PROFILE=your_profile]"
	@echo ""
	@echo "Targets:"
	@echo "  help          Show this help message"
	@echo "  fetch-secrets Fetch secrets from AWS SSM Parameter Store and write to $(ENV_FILE)"
	@echo "  start         Fetch secrets and start Docker Compose environment"
	@echo "  clean         Stop containers, remove volumes, and delete $(ENV_FILE)"
	@echo ""
	@echo "Environment variables:"
	@echo "  AWS_PROFILE   AWS CLI profile to use for fetching secrets (default: default profile)"
	@echo "  REGION        AWS region to use (default: $(REGION))"

fetch-secrets:
	@echo "Fetching secrets from SSM..."
	@cp .env.example .env || touch .env

	@$(MAKE) fetch-secrets-batch PARAMS="DB_ROOT_PASSWORD DB_HOST DB_PORT DB_NAME DB_USER DB_PASS SERVICE_URL SUMA_ADMIN_USER SUMA_ADMIN_PASS SUMA_ANALYTICS_TIMEZONE"
	@$(MAKE) fetch-secrets-batch PARAMS="SUMA_ANALYTICS_DISPLAY_FORMAT SUMA_ANALYTICS_RECIPIENTS SUMA_ANALYTICS_ERROR_RECIPIENTS SUMA_ANALYTICS_EMAIL_FROM SUMA_ANALYTICS_EMAIL_SUBJECT"

	@rm -f .env.bak
	@echo ".env updated from .env.example and SSM"

fetch-secrets-batch:
	@aws ssm get-parameters \
		--region $(REGION) \
		$(if $(AWS_PROFILE),--profile $(AWS_PROFILE)) \
		--names $(foreach p,$(PARAMS),"$(SSM_PREFIX)/$(p)") \
		--with-decryption \
		--query "Parameters[*].{Name:Name,Value:Value}" \
		--output text | \
		while read name value; do \
			key=$$(echo $$name | sed "s|$(SSM_PREFIX)/||"); \
			sed -i.bak "s|^$$key=.*|$$key=$$value|" .env || echo "$$key=$$value" >> .env; \
		done

start: fetch-secrets
	docker-compose up -d --build

clean:
	docker-compose down -v
	rm -f $(ENV_FILE)

.DEFAULT_GOAL := help

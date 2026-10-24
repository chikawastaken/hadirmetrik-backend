# ============================================================================
# HadirMetrik Backend — Developer Makefile
# ============================================================================
# Shortcut untuk perintah-perintah yang sering dipakai selama development.
# Jalankan `make help` untuk melihat daftar lengkap.
# ============================================================================

.DEFAULT_GOAL := help
.PHONY: help setup env install key db db-stop db-restart db-logs adminer \
        migrate migrate-fresh seed fresh serve test lint clean

# ---------------------------------------------------------------------------
# Warna (opsional, bisa diabaikan di terminal yang tidak support ANSI)
# ---------------------------------------------------------------------------
CYAN  := \033[36m
GREEN := \033[32m
RESET := \033[0m

# ---------------------------------------------------------------------------
# Help
# ---------------------------------------------------------------------------

help: ## Tampilkan daftar perintah yang tersedia
	@echo ""
	@echo "$(GREEN)HadirMetrik Backend — Makefile$(RESET)"
	@echo "=============================="
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(CYAN)%-18s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# ---------------------------------------------------------------------------
# Setup Awal (first-time)
# ---------------------------------------------------------------------------

setup: env install key db migrate seed ## 🚀 Setup lengkap pertama kali (clone → siap pakai)
	@echo "\n$(GREEN)✅ Setup selesai! Jalankan 'make serve' untuk memulai server.$(RESET)\n"

env: ## Salin .env.example → .env (skip jika sudah ada)
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "$(GREEN)✅ .env dibuat dari .env.example — silakan isi POSTGRES_PASSWORD dan SEED_*$(RESET)"; \
	else \
		echo "$(CYAN)ℹ️  .env sudah ada, dilewati.$(RESET)"; \
	fi

install: ## Install dependensi PHP via Composer
	composer install

key: ## Generate APP_KEY Laravel
	php artisan key:generate

# ---------------------------------------------------------------------------
# Database (Docker)
# ---------------------------------------------------------------------------

db: ## Jalankan container PostgreSQL (docker compose up -d db)
	docker compose up -d db
	@echo "$(GREEN)✅ PostgreSQL berjalan di 127.0.0.1:$${POSTGRES_PORT:-5433}$(RESET)"

db-stop: ## Hentikan container database
	docker compose down

db-restart: ## Restart container database
	docker compose restart db

db-logs: ## Tampilkan log container database (follow)
	docker compose logs -f db

db-reset: ## ⚠️  Hapus volume database dan buat ulang (data hilang!)
	docker compose down -v
	docker compose up -d db
	@echo "$(GREEN)✅ Database di-reset. Jalankan 'make migrate seed' untuk mengisi ulang.$(RESET)"

adminer: ## Jalankan Adminer (Web GUI) di http://localhost:8080
	docker compose --profile tools up -d
	@echo "$(GREEN)✅ Adminer tersedia di http://localhost:$${ADMINER_PORT:-8080}$(RESET)"

# ---------------------------------------------------------------------------
# Migrasi & Seeding
# ---------------------------------------------------------------------------

migrate: ## Jalankan migrasi database
	php artisan migrate

migrate-fresh: ## ⚠️  Drop semua tabel lalu migrasi ulang
	php artisan migrate:fresh

seed: ## Jalankan database seeder
	php artisan db:seed

fresh: migrate-fresh seed ## ⚠️  migrate:fresh + db:seed (reset skema + data)

# ---------------------------------------------------------------------------
# Development Server
# ---------------------------------------------------------------------------

serve: ## Jalankan server development Laravel (http://127.0.0.1:8000)
	php artisan serve

serve-network: ## Jalankan server yang bisa diakses dari jaringan lokal (HP/emulator)
	php artisan serve --host 0.0.0.0

# ---------------------------------------------------------------------------
# Testing & Quality
# ---------------------------------------------------------------------------

test: ## Jalankan automated test suite
	php artisan test

lint: ## Jalankan Laravel Pint (code style fixer)
	./vendor/bin/pint --test

lint-fix: ## Jalankan Laravel Pint dan perbaiki otomatis
	./vendor/bin/pint

# ---------------------------------------------------------------------------
# Utilitas
# ---------------------------------------------------------------------------

clean: ## Bersihkan cache Laravel (config, route, view, event)
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	php artisan event:clear
	@echo "$(GREEN)✅ Cache dibersihkan.$(RESET)"

routes: ## Tampilkan daftar route API
	php artisan route:list --path=api

# TEAM CHARTER - ai.eivie.cn (国宝云购)

> Multi-tenant SaaS Platform: E-commerce + Restaurant Management + Interactive Marketing + AI Content Generation
>
> Created: 2025-04-30 | Last Updated: 2025-04-30 | Version: 1.0

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Role Definitions](#2-role-definitions)
3. [Collaboration Workflow](#3-collaboration-workflow)
4. [Onboarding Checklist](#4-onboarding-checklist)
5. [Development Standards](#5-development-standards)
6. [Escalation Path](#6-escalation-path)
7. [Known Technical Debt](#7-known-technical-debt)

---

## 1. Project Overview

### 1.1 Platform Description

ai.eivie.cn (国宝云购) is a multi-tenant SaaS platform built on the ThinkPHP 6.x framework, combining:
- **AI Content Generation**: Image/video generation via Kling, Seedance, PixVerse, Jimeng, Ollama, VoxCPM2
- **E-commerce**: Product catalog, shopping cart, order management, payment processing
- **Restaurant Management**: Table booking, queue management, POS ordering
- **Interactive Marketing**: Lottery, voting, sign-in walls, screen interaction, HD activities
- **Mini Programs**: WeChat, Alipay, Baidu, QQ, Toutiao (5 platforms via UniApp)

### 1.2 Architecture Summary

| Layer | Technology | Key Paths |
|-------|-----------|-----------|
| Backend | ThinkPHP 6.x (PHP 7.1+) | `/app/controller/`, `/app/service/`, `/app/model/` |
| Frontend | Vue 2 + UniApp | `/uniapp/` (source), `/h5/` (build output) |
| Database | MySQL (4 connections) | `/config/database.php` |
| Cache | Redis | `/config/cache.php` |
| Queue | Think-Queue (database/Redis) | `/config/queue.php`, `/app/job/` |
| WebSocket | Workerman/GatewayWorker | `/config/gateway_worker.php`, `/app/common/Worker.php` |
| Vector DB | Milvus | `/app/service/MilvusService.php` |
| Cloud Storage | Tencent COS, Aliyun OSS, Qiniu | `/app/service/StorageService.php` |

### 1.3 Codebase Statistics

| Component | Count | Primary Location |
|-----------|-------|-----------------|
| PHP Controllers | ~414 | `/app/controller/` (including `/hd/`, `/api/` subdirs) |
| PHP Services | ~56 | `/app/service/` (including `/hd/`, `/workflow/` subdirs) |
| PHP Jobs | 7 | `/app/job/` |
| PHP Middleware | 8 + 4 (hd) | `/app/middleware/` |
| Vue Files | ~1,651 | `/uniapp/` |
| JS Files | ~21,000 | `/uniapp/` + `/h5/` (includes build artifacts) |
| Migration Scripts | ~80+ | Root level (`migrate_*.php`) |
| Config Files | 23 | `/config/` |

### 1.4 Key Module Map

```
app/
├── controller/
│   ├── hd/                    # HD (互动) activity module (~22 controllers)
│   ├── api/                   # AI Travel Photo sub-APIs (~8 controllers)
│   ├── AiTravelPhoto.php      # Main AI photo generation controller
│   ├── GenerationService.php  # 263KB god-class (6,783 lines)
│   └── ...                    # E-commerce, restaurant, marketing controllers
├── service/
│   ├── GenerationService.php  # Core AI generation orchestration (THE critical file)
│   ├── KlingAIService.php     # Kling AI provider integration
│   ├── MilvusService.php      # Vector database service
│   ├── AiModelService.php     # AI model configuration & routing
│   ├── AiModelInvokeService.php # Model invocation dispatcher
│   ├── workflow/              # Workflow node executors
│   │   ├── CharacterNodeExecutor.php
│   │   ├── VideoNodeExecutor.php
│   │   └── StoryboardNodeExecutor.php
│   └── ...                    # ~45 more services
├── job/
│   ├── GenerationJob.php      # Base generation job
│   ├── ImageGenerationJob.php # Image async processing
│   ├── VideoGenerationJob.php # Video async processing
│   ├── AutoTaggingJob.php     # Auto-tagging queue job
│   └── ...                    # 3 more jobs
├── middleware/
│   ├── AdminTokenAuth.php     # Admin auth (Token via Cache)
│   ├── UserTokenAuth.php      # User auth (Token via Cache)
│   ├── DeviceTokenAuth.php    # Device auth (for kiosk/selfie)
│   ├── SignatureVerify.php    # API signature verification
│   ├── RateLimiter.php        # Request rate limiting
│   └── hd/                    # HD-specific auth middleware
└── model/                     # Eloquent-style models
```

---

## 2. Role Definitions

### 2.1 Backend Developer (ThinkPHP) — 2-3 persons

#### Responsibilities
- Core business logic for e-commerce, restaurant, marketing modules
- RESTful API design and implementation
- Database schema design, migration writing, and optimization
- Controller and service layer development
- Payment integration (WeChat Pay, Alipay, PayPal)
- Multi-tenant isolation enforcement
- Queue job development and maintenance

#### Key Files/Modules Owned
```
/app/controller/          # All controllers (~414 files) — primary ownership
/app/service/             # Business services (non-AI) — primary ownership
/app/model/               # Data models — primary ownership
/app/job/                 # Queue job classes — primary ownership
/app/middleware/          # Auth and security middleware — co-ownership with Architect
/app/validate/            # Validation classes (only MapmarkOrder.php exists!) — NEEDS EXPANSION
/config/database.php      # DB connection config (4 connections)
/config/queue.php         # Queue configuration
migrate_*.php             # Migration scripts (80+ at root level)
upgrade*.php              # Database upgrade scripts
```

#### Required Skills
| Skill | Level | Notes |
|-------|-------|-------|
| PHP 7.1+ | Expert | Must understand strict types, type hints, closures |
| ThinkPHP 6.x | Expert | Middleware, events, queue, validation patterns |
| MySQL | Advanced | 4-connection routing, complex queries, optimization |
| Redis | Intermediate | Cache patterns, distributed locks |
| PSR-12 | Required | Code style standard |

#### Success Metrics
- Zero data integrity issues in production
- API response time < 500ms for 95th percentile (excluding AI generation)
- All new controllers have matching validation classes in `/app/validate/`
- Migration scripts are idempotent and tested before merge
- Controller methods < 100 lines; logic delegated to services

---

### 2.2 AI/Algorithm Engineer — 1-2 persons

#### Responsibilities
- AI model integration (Kling, Seedance, PixVerse, Jimeng, Ollama, VoxCPM2)
- Vector search pipeline (Milvus face features, similarity search)
- AI workflow design and node executor development
- Model parameter optimization (image resolution, video duration, quality settings)
- AI cost optimization (API key pooling, rate limiting, fallback routing)
- Image/video processing (cutout, watermark, face embedding)

#### Key Files/Modules Owned
```
/app/service/GenerationService.php      # THE critical file — 263KB, 6,783 lines
/app/service/KlingAIService.php         # Kling provider
/app/service/VolcengineVideoService.php # Seedance/Volcengine provider
/app/service/AiModelService.php         # Model configuration
/app/service/AiModelInvokeService.php   # Model invocation routing
/app/service/AiModelConfigService.php   # Model config CRUD
/app/service/ModelParameterValidator.php # Parameter validation
/app/service/ModelResponseParser.php    # Response parsing
/app/service/MilvusService.php          # Vector DB operations
/app/service/FaceEmbeddingService.php   # Face feature extraction
/app/service/CharacterConsistencyService.php # Character consistency
/app/service/AutoTaggingService.php     # AI auto-tagging
/app/service/OllamaChatService.php      # Local LLM integration
/app/service/CloudLLMService.php        # Cloud LLM integration
/app/service/workflow/                  # Workflow node executors
/app/service/SceneConfigService.php     # Scene parameter management
/app/service/SceneParameterService.php  # Scene parameter processing
/app/job/ImageGenerationJob.php         # Image async job
/app/job/VideoGenerationJob.php         # Video async job
/app/job/CutoutJob.php                  # Background cutout job
/app/job/FaceEmbeddingBackfillJob.php   # Vector backfill
/config/milvus.php                      # Milvus configuration
/config/aivideo.php                     # AI video configuration
```

#### Required Skills
| Skill | Level | Notes |
|-------|-------|-------|
| AI Model APIs | Expert | REST API integration, async polling, webhook handling |
| Python | Intermediate | For local model serving (Ollama), image processing scripts |
| Milvus/Vector DB | Advanced | Collection design, similarity search, metadata management |
| Image Processing | Advanced | PHP GD, ImageMagick, or Python PIL |
| PHP 7+ | Intermediate | Must navigate ThinkPHP codebase confidently |

#### Success Metrics
- All AI providers follow a consistent integration pattern (currently INCONSISTENT)
- Generation job success rate > 95%
- Model response time tracking and alerting in place
- Milvus metadata is properly stored for every vector insertion
- No blocking `sleep()` calls in queue workers (currently 22 files have `sleep()`)
- Provider fallback works automatically when primary API fails

---

### 2.3 Frontend Developer (UniApp/Vue) — 1-2 persons

#### Responsibilities
- Mini program development (WeChat, Alipay, Baidu, QQ, Toutiao)
- H5 mobile web application
- Component library design and maintenance
- Order card, generation result display, activity pages
- Cross-platform compatibility testing
- Build pipeline maintenance (HBuilderX / CLI)

#### Key Files/Modules Owned
```
/uniapp/                    # Main UniApp source (~1,651 Vue files)
/uniapp/App.vue             # Root component (91KB — needs splitting)
/uniapp/components/         # Component library (~130 components)
/uniapp/activity/           # Activity-specific pages (~24 subdirs)
/uniapp/admin/              # Admin pages (~21 subdirs)
/uniapp/adminExt/           # Extended admin pages (~25 subdirs)
/uniapp/carhailing/         # Car hailing module
/uniapp/ailvpai/            # AI travel photo module
/uniapp/pages/              # Core pages
/h5/                        # H5 build output (DO NOT EDIT DIRECTLY)
/h520260327/                # Previous build snapshot
auto_build_h5.sh            # H5 build script
build_h5.sh                 # Alternative build script
deploy_h5.sh                # H5 deployment script
```

#### Required Skills
| Skill | Level | Notes |
|-------|-------|-------|
| Vue.js 2.x | Expert | Options API, component lifecycle, mixins |
| UniApp | Expert | Platform-specific conditionals, native APIs |
| WeChat Mini Program | Advanced | Login, payment, share, subscribe messages |
| CSS/SCSS | Advanced | Responsive design, platform differences |
| HBuilderX | Intermediate | Build configuration, packaging, debugging |

#### Success Metrics
- All 5 mini program platforms build successfully
- Build output in `/h5/` passes Lighthouse score > 80
- Component reuse rate > 60% (new pages use existing components)
- Zero direct edits to `/h5/` directory (always build from `/uniapp/`)
- UI consistency across platforms verified before release

---

### 2.4 Full-stack / Architect — 1 person

#### Responsibilities
- System architecture design and evolution
- Technology selection and evaluation (new AI providers, libraries)
- Code review oversight and quality gate enforcement
- Performance bottleneck identification and resolution
- Security audit and vulnerability remediation
- Database connection strategy and optimization
- Cross-module integration design
- Production incident command

#### Key Files/Modules Owned
```
/app/service/GenerationService.php      # Architecture oversight (refactoring owner)
/app/BaseController.php                 # Base controller patterns
/app/common.php                         # Global helper functions
/app/middleware.php                     # Middleware registration
/app/provider.php                       # Service provider registration
/config/*.php                           # All configuration files (review authority)
composer.json                           # Dependency management
.env                                    # Environment configuration
/config/database.php                    # Multi-connection strategy
/app/middleware/hd/                     # HD module architecture
/sysadmin/                              # System admin module
/deploy/                                # Deployment configuration
/docker/                                # Docker configuration
queue_keepalive.sh                      # Queue process management
```

#### Required Skills
| Skill | Level | Notes |
|-------|-------|-------|
| Full-stack (PHP + Vue) | Expert | Must understand entire codebase |
| System Design | Expert | Scalability, reliability, multi-tenancy |
| Performance Tuning | Expert | Query optimization, caching strategy |
| Security | Advanced | SQL injection, XSS, CSRF, auth bypass prevention |
| DevOps | Intermediate | CI/CD, Docker, Nginx, monitoring |

#### Success Metrics
- GenerationService.php refactored from 263KB to < 50KB per service file
- Auth systems unified from 4 parallel systems to 1 (or clearly documented boundaries)
- Response format standardized to single format (currently 3 variants)
- All production incidents have post-mortem within 48 hours
- Architecture Decision Records (ADRs) written for major decisions

---

### 2.5 QA Engineer — 1 person

#### Responsibilities
- Automated API testing (PHPUnit for backend)
- Manual testing across all 5 mini program platforms
- Regression testing before each release
- Performance testing for critical paths (generation, payment, login)
- Test case writing and maintenance
- Bug triage and tracking

#### Key Files/Modules Owned
```
/test_*.php                     # PHP test scripts (many exist, inconsistent)
/test_*.html                    # Frontend test pages
/app/validate/                  # Validation class creation (collaborate with Backend)
test_api_config.sh              # API config test script
test_generation_pages.sh        # Generation page test script
test_api_endpoints.html         # API endpoint test page
test_api_frontend.html          # Frontend API test page
test_workflow_e2e.php           # Workflow end-to-end test
test_workflow.php               # Workflow unit test
verify_*.sh                     # Verification scripts
```

#### Required Skills
| Skill | Level | Notes |
|-------|-------|-------|
| PHPUnit | Intermediate | ThinkPHP testing patterns |
| API Testing | Advanced | Postman, curl, automated scripts |
| JavaScript/Jest | Basic | Frontend test awareness |
| Manual Testing | Expert | Cross-platform, edge cases |
| Test Strategy | Advanced | Risk-based testing, coverage planning |

#### Success Metrics
- All 414 controllers have at least smoke test coverage
- API test suite runs in < 15 minutes
- Regression test checklist executed before every production deploy
- Bug escape rate < 5% (bugs found in production / total bugs)
- Test documentation updated for every new feature

---

### 2.6 DevOps Engineer — 0.5-1 person

#### Responsibilities
- CI/CD pipeline setup and maintenance
- Server provisioning and configuration (Linux, Nginx, PHP-FPM)
- Docker container management
- Monitoring and alerting setup
- Database backup and disaster recovery
- SSL certificate management
- Queue worker process management
- Log aggregation and analysis

#### Key Files/Modules Owned
```
/deploy/                        # Deployment scripts and config
/docker/                        # Docker configuration
*.sh                            # All shell scripts (build, deploy, verify)
config.php                      # Server configuration
.env                            # Environment variables (access control)
nginx.conf                      # Nginx configuration (if exists)
php-fpm.conf                    # PHP-FPM configuration
queue_keepalive.sh              # Queue process supervisor
auto_build_h5.sh                # H5 build automation
deploy_h5.sh                    # H5 deployment
/runtime/                       # Runtime directory (log, cache, temp)
```

#### Required Skills
| Skill | Level | Notes |
|-------|-------|-------|
| Linux | Expert | Ubuntu/CentOS, shell scripting, process management |
| Nginx | Advanced | Reverse proxy, SSL, load balancing |
| PHP-FPM | Advanced | Pool configuration, opcache, memory limits |
| Docker | Intermediate | Container orchestration, volume management |
| MySQL | Advanced | Backup/restore, replication, performance |
| Redis | Intermediate | Persistence, clustering, monitoring |

#### Success Metrics
- Zero-downtime deployments for H5 updates
- Database backups verified weekly (restore test monthly)
- Queue worker auto-recovery on crash
- SSL certificates renewed 30 days before expiration
- Server resource alerts configured (CPU, memory, disk, queue depth)

---

## 3. Collaboration Workflow

### 3.1 Code Review Matrix

```
Code Author        →  Primary Reviewer      →  Secondary Reviewer (if needed)
─────────────────────────────────────────────────────────────────────────────
Backend Dev        →  Architect             →  Other Backend Dev
                   (all controllers,        (complex DB changes,
                    services, migrations)    payment logic)

AI Engineer        →  Architect             →  Backend Dev
                   (AI services,            (generation job changes,
                    Milvus, workflows)       queue integration)

Frontend Dev       →  Backend Dev           →  Architect
                   (UniApp components,      (architecture changes,
                    H5 pages)                 cross-platform issues)

Architect          →  Self-review +         →  N/A
                   Team notification        (all changes documented
                    in team channel)          in ADR)

QA Engineer        →  N/A                   →  Backend Dev
                   (test scripts only        (test strategy review)
                    reviewed by Backend)

DevOps             →  Architect             →  N/A
                   (deployment scripts,     (infrastructure changes)
                    server config)
```

### 3.2 Cross-Role Dependencies

```
┌──────────────┐    API Contract     ┌──────────────┐
│   Backend    │◄───────────────────►│  Frontend    │
│  Developer   │    (JSON schema)    │  Developer   │
└──────┬───────┘                     └──────┬───────┘
       │                                    │
       │ Service Interface                  │ UI Components
       ▼                                    ▼
┌──────────────┐    Model Integration ┌──────────────┐
│   Architect  │◄────────────────────►│ AI Engineer  │
│              │    (system design)   │              │
└──────┬───────┘                     └──────┬───────┘
       │                                    │
       │ Infrastructure                     │ Test Scenarios
       ▼                                    ▼
┌──────────────┐    Coverage Goals  ┌──────────────┐
│   DevOps     │◄──────────────────►│     QA       │
│   Engineer   │    (deploy gates)  │   Engineer   │
└──────────────┘                    └──────────────┘
```

**Dependency Rules:**

1. **Frontend waits for Backend**: API contracts must be defined before frontend implementation. Use mock data if backend is not ready.
2. **AI Engineer informs Backend**: Model integration changes that affect GenerationService MUST be reviewed by Architect.
3. **QA validates before Deploy**: No production deployment without QA sign-off on the affected module.
4. **DevOps notifies on Infrastructure Changes**: Any server/config change must be announced 24 hours in advance.

### 3.3 Communication Protocols

| Channel | Purpose | Response SLA |
|---------|---------|-------------|
| Daily Standup (15 min) | Status update, blockers | Mandatory |
| Weekly Tech Review (60 min) | Architecture decisions, code walkthroughs | Mandatory for all |
| Incident Channel (IM/Chat) | Production incidents | < 15 minutes |
| Code Review Comments | PR feedback | < 4 hours during business hours |
| ADR Document | Architecture decisions | Async review within 24 hours |
| Bug Tracker | Issue tracking | Triage within 1 business day |

### 3.4 Pull Request Workflow

```
1. Create feature branch from main
   Format: {role-prefix}/{type}/{short-description}
   Examples:
     backend/feature/order-unified-search
     ai/fix/milvus-metadata-storage
     frontend/refactor/app-vue-split
     arch/adrs/generation-service-split

2. Development → Self-test → Open PR

3. PR Requirements:
   - Description of change
   - Screenshots (frontend) or curl examples (API)
   - Database migration (if any)
   - Test results
   - Affected modules list

4. Review → Address comments → Approve (1-2 reviewers per matrix)

5. Squash merge to main

6. Deploy to staging → QA verification → Production deploy
```

---

## 4. Onboarding Checklist

### 4.1 Backend Developer (ThinkPHP)

#### Week 1 Tasks
- [ ] Clone repository and set up local development environment
- [ ] Run `composer install` and verify `/vendor/` directory
- [ ] Configure `/config/database.php` for local MySQL (4 connections)
- [ ] Configure Redis in `/config/cache.php`
- [ ] Run migration: check `migrate_*.php` scripts for required data
- [ ] Read `/app/BaseController.php` to understand base patterns
- [ ] Read `/app/common.php` to understand global helper functions
- [ ] Identify the 3 response formats used in the codebase and document them
- [ ] Trace one complete API flow: request → middleware → controller → service → response
- [ ] Deploy a simple test controller and verify it works

#### Access/Permissions Needed
- [ ] Git repository read/write access
- [ ] MySQL database access (at least read for all 4 connections)
- [ ] Redis access (local or staging)
- [ ] Server staging access (SSH)
- [ ] AI API keys for testing (Kling, Seedance, etc.)

#### Key Documentation to Read
- ThinkPHP 6.x documentation: https://www.kancloud.cn/manual/thinkphp6_0
- `/config/database.php` — understand 4-connection setup
- `/config/queue.php` — queue driver and processing
- `/app/middleware.php` — registered middleware
- `/app/service/GenerationService.php` — skim to understand scale (do not modify)
- `/app/middleware/AdminTokenAuth.php` — understand auth pattern
- `/app/middleware/UserTokenAuth.php` — understand auth pattern
- `/app/middleware/SignatureVerify.php` — understand API security

---

### 4.2 AI/Algorithm Engineer

#### Week 1 Tasks
- [ ] Complete Backend Week 1 checklist (same environment setup)
- [ ] Read all AI provider services:
  - `/app/service/KlingAIService.php`
  - `/app/service/VolcengineVideoService.php`
  - `/app/service/AiModelService.php`
  - `/app/service/AiModelInvokeService.php`
- [ ] Read `/app/service/MilvusService.php` and understand collection design
- [ ] Map out the current GenerationService flow (6,783 lines — use outline view)
- [ ] Read workflow executors in `/app/service/workflow/`
- [ ] Check Milvus: verify collection exists, test search endpoint
- [ ] Run `test_workflow.php` and `test_workflow_e2e.php`
- [ ] Document the inconsistencies between provider integrations

#### Access/Permissions Needed
- [ ] All Backend access (above)
- [ ] AI provider API accounts (Kling, Seedance, PixVerse, Jimeng, VoxCPM2)
- [ ] Ollama server access (if running locally)
- [ ] Milvus server access
- [ ] Cloud storage access (COS/OSS/Qiniu) for generated content

#### Key Documentation to Read
- `/app/service/GenerationService.php` — THE core file
- `/app/service/MilvusService.php` — vector DB patterns
- `/app/service/ModelParameterValidator.php` — parameter validation
- `/app/service/ModelResponseParser.php` — response parsing
- `/app/job/ImageGenerationJob.php` — async image processing
- `/app/job/VideoGenerationJob.php` — async video processing
- `/config/milvus.php` — Milvus configuration
- `/config/aivideo.php` — AI video configuration
- `/NEWAPI接入指南.md` — NEWAPI integration guide
- `/豆包接入模型.md` — Doubao model integration notes

---

### 4.3 Frontend Developer (UniApp)

#### Week 1 Tasks
- [ ] Clone repository and set up HBuilderX or UniApp CLI
- [ ] Open `/uniapp/` project in HBuilderX
- [ ] Read `/uniapp/App.vue` (91KB — understand the structure)
- [ ] Build H5 output: run `auto_build_h5.sh` or use HBuilderX
- [ ] Verify build output in `/h5/` directory
- [ ] Explore component library: `/uniapp/components/` (~130 components)
- [ ] Trace one complete page flow: page → API call → data binding → render
- [ ] Build and preview for WeChat mini program platform
- [ ] Build and preview for H5 platform
- [ ] Document any build issues or warnings

#### Access/Permissions Needed
- [ ] Git repository read/write access
- [ ] HBuilderX license (or CLI tools)
- [ ] WeChat Developer Tools
- [ ] WeChat mini program app credentials
- [ ] Alipay mini program app credentials (if applicable)
- [ ] Staging server access for H5 preview

#### Key Documentation to Read
- UniApp documentation: https://uniapp.dcloud.net.cn/
- `/uniapp/App.vue` — root component
- `/uniapp_build_checklist.md` — build checklist
- `/UNIAPP_BUILD_GUIDE.md` — build guide
- `/uniapp_build_warnings_fix.md` — common warning fixes
- `/H5端订单卡片布局未更新问题说明.md` — known H5 issue
- `/订单卡片布局更新-HBuilder X操作指南.md` — order card guide
- `/uniapp/pages.json` — page routing (if exists)
- `/uniapp/manifest.json` — app configuration

---

### 4.4 Full-stack / Architect

#### Week 1 Tasks
- [ ] Complete Backend Week 1 checklist
- [ ] Complete Frontend Week 1 checklist
- [ ] Perform full codebase analysis:
  - Count and categorize all controllers
  - Map all service dependencies
  - Identify all auth mechanisms
  - Document all response formats
- [ ] Review all 4 database connections and their purposes
- [ ] Audit the 4 auth systems:
  - `AdminTokenAuth` (cache-based)
  - `UserTokenAuth` (cache-based)
  - `DeviceTokenAuth` (service-based)
  - `hd/AdminAuth` (HD-specific)
  - `SignatureVerify` (API signature)
- [ ] Document the 3 response formats found in the codebase
- [ ] Review all `sleep()` calls (22 files) and assess impact
- [ ] Create refactoring roadmap for GenerationService.php

#### Access/Permissions Needed
- [ ] All Backend + Frontend access
- [ ] Production server read access (for auditing)
- [ ] All AI provider admin accounts
- [ ] Database admin access (for schema review)
- [ ] Monitoring/alerting access

#### Key Documentation to Read
- Everything listed for Backend + AI + Frontend roles
- `/config/database.php` — all 4 connections
- All `/app/middleware/*.php` files
- All `/app/middleware/hd/*.php` files
- `/app/common.php` — global helpers
- `/composer.json` — dependencies
- `/deploy/` — deployment configuration
- `/docker/` — container setup

---

### 4.5 QA Engineer

#### Week 1 Tasks
- [ ] Complete Backend Week 1 checklist (environment setup only)
- [ ] Inventory all existing test files (`test_*.php`, `test_*.html`, `verify_*.sh`)
- [ ] Run existing test scripts and document results
- [ ] Create API endpoint inventory from all controllers
- [ ] Set up test database (separate from dev/production)
- [ ] Create test accounts for each user type
- [ ] Document the test environment setup
- [ ] Identify the top 20 most-used API endpoints (by business importance)
- [ ] Create smoke test checklist for each major module

#### Access/Permissions Needed
- [ ] Git repository read access (write for test files)
- [ ] Test database access
- [ ] Staging server access
- [ ] All mini program developer access (for testing)
- [ ] AI API keys (for generation testing)

#### Key Documentation to Read
- All `test_*.php` and `test_*.html` files
- All `verify_*.sh` scripts
- `/app/controller/` — controller listing for API inventory
- `/docs/用户操作流程文档.md` — user flow documentation
- `/选片订单API接口文档.md` — photo order API docs
- `/选片支付API接口文档.md` — photo payment API docs

---

### 4.6 DevOps Engineer

#### Week 1 Tasks
- [ ] Complete Backend Week 1 checklist (environment setup)
- [ ] Audit server infrastructure:
  - List all servers and their roles
  - Document Nginx configuration
  - Document PHP-FPM configuration
  - Document MySQL setup (4 databases)
  - Document Redis setup
  - Document Milvus setup
- [ ] Review existing deployment scripts (`deploy_*.sh`, `build_*.sh`)
- [ ] Set up monitoring for:
  - Server resources (CPU, memory, disk)
  - PHP-FPM process health
  - MySQL connection pool
  - Redis memory usage
  - Queue depth
  - Milvus collection stats
- [ ] Review backup procedures
- [ ] Test disaster recovery (restore from backup)
- [ ] Document the current deployment process

#### Access/Permissions Needed
- [ ] Root/sudo access on all servers
- [ ] Database admin access
- [ ] Domain/DNS management access
- [ ] SSL certificate management access
- [ ] Cloud provider console access

#### Key Documentation to Read
- `/deploy/` — deployment scripts
- `/docker/` — Docker configuration
- `/queue_keepalive.sh` — queue process management
- `/auto_build_h5.sh` — H5 build automation
- `/deploy_h5.sh` — H5 deployment
- `/diagnose_api.sh` — API diagnostic script
- `/test_api_config.sh` — API config test
- `/config/database.php` — DB connections
- `/config/cache.php` — cache config
- `/config/queue.php` — queue config

---

## 5. Development Standards

### 5.1 Code Style

#### PHP (PSR-12)
```php
// ALL new PHP files MUST:
declare(strict_types=1);  // First line after opening tag

// Naming conventions:
// - Classes: PascalCase (e.g., GenerationService)
// - Methods: camelCase (e.g., processGeneration)
// - Properties: camelCase (e.g., generationResult)
// - Constants: UPPER_SNAKE_CASE (e.g., MAX_RETRIES)

// ALL controller methods MUST use typed parameters and return types:
public function getGenerationDetail(int $id): \think\response\Json
{
    // ...
}
```

**Enforcement**: Run PHP-CS-Fixer on all PRs before review.
```bash
# Install
composer require --dev friendsofphp/php-cs-fixer

# Check
vendor/bin/php-cs-fixer fix --dry-run --diff app/

# Fix
vendor/bin/php-cs-fixer fix app/
```

#### JavaScript/Vue (ESLint)
```javascript
// ALL new JS/Vue files MUST:
/* eslint-disable no-console */ // Only if justified

// Naming conventions:
// - Components: PascalCase (e.g., UnifiedOrderCard)
// - Methods/functions: camelCase (e.g., fetchOrderList)
// - Data properties: camelCase (e.g., orderList)
// - Constants: UPPER_SNAKE_CASE (e.g., API_BASE_URL)
```

**Enforcement**: Add ESLint to UniApp project.
```json
// /uniapp/.eslintrc.json
{
  "extends": ["eslint:recommended", "plugin:vue/recommended"],
  "rules": {
    "no-console": "warn",
    "vue/require-prop-types": "error"
  }
}
```

### 5.2 Git Workflow

#### Branch Naming
```
{type}/{short-description}
Types: feature | fix | refactor | perf | docs | test | chore | arch

Examples:
  backend/feature/unified-order-search
  ai/fix/milvus-metadata-not-stored
  frontend/refactor/split-app-vue
  test/add/generation-service-smoke-tests
  arch/adrs/generation-service-decomposition
  chore/upgrade/php-cs-fixer
```

#### Commit Message Format
```
{type}({scope}): {short description}

{optional body}

Refs: #{issue-number}
```

**Examples:**
```
fix(GenerationService): remove blocking sleep() in queue worker

Replace sleep(30) with delayed job requeue to prevent
blocking the entire queue worker process.

Refs: #142
```

```
feat(AI): add VoxCPM2 model provider integration

- Add VoxCPM2Service extending AiModelInvokeService
- Register model in ai_model table
- Add unit tests for parameter validation

Refs: #156
```

#### PR Requirements
| Requirement | Details |
|-------------|---------|
| Title | Follow commit message format |
| Description | What changed, why, how to test |
| Screenshots | Required for frontend changes |
| API examples | Required for API changes (`curl` examples) |
| Migration | Include migration script if schema changes |
| Tests | Include or update tests |
| Breaking changes | Document migration path |
| Reviewers | Per Code Review Matrix (Section 3.1) |

### 5.3 Testing Requirements

#### Backend (PHPUnit)
```php
// New services MUST have matching test class:
// /app/service/FooService.php → /tests/service/FooServiceTest.php

// Minimum test coverage for new code:
// - All public methods: at least one test
// - Error paths: test exception handling
// - Edge cases: null inputs, empty arrays, max values

// Example test structure:
namespace tests\service;
use PHPUnit\Framework\TestCase;

class FooServiceTest extends TestCase
{
    public function testProcessWithValidInput(): void
    {
        $service = new FooService();
        $result = $service->process(['key' => 'value']);
        $this->assertArrayHasKey('status', $result);
    }

    public function testProcessWithInvalidInputThrowsException(): void
    {
        $service = new FooService();
        $this->expectException(\InvalidArgumentException::class);
        $service->process([]);
    }
}
```

#### API Testing
```bash
# All new API endpoints MUST have curl test examples:
curl -X POST https://staging.ai.eivie.cn/api/endpoint \
  -H "User-Token: test-token" \
  -H "Content-Type: application/json" \
  -d '{"key": "value"}'

# Expected response format:
# {"code": 0, "msg": "success", "data": {...}}
```

#### Frontend Testing
- Manual testing required on all 5 platforms before merge
- Automated e2e testing: plan for future implementation
- Visual regression testing for critical UI components

### 5.4 Documentation Standards

#### PHP DocBlocks (Required)
```php
/**
 * Process AI generation request
 *
 * @param int $userId User ID requesting generation
 * @param array $params Generation parameters (see ModelParameterValidator)
 * @param string $provider AI provider identifier
 * @return array Generation result with keys: task_id, status, estimated_time
 * @throws \Exception If provider API call fails
 */
public function processGeneration(int $userId, array $params, string $provider): array
```

#### README/Documentation Files
- DO NOT create documentation files unless explicitly requested
- When documentation IS needed, place in `/docs/` directory
- Use Markdown format
- Include: purpose, usage examples, caveats

#### Architecture Decision Records (ADRs)
For significant architectural decisions, create an ADR:
```
/docs/adrs/001-generation-service-decomposition.md

# ADR-001: GenerationService Decomposition

## Status
Proposed

## Context
GenerationService.php is 263KB (6,783 lines), making it difficult to maintain...

## Decision
Split into 5 focused services...

## Consequences
- Positive: Improved maintainability, easier testing
- Negative: Migration complexity, temporary dual-write period
```

### 5.5 Response Format Standardization

**Current State**: 3 different response formats exist in the codebase.

**Target Standard**: Single format (use `api_response()` from `/app/common.php`):
```php
// SUCCESS
api_response(0, 'success', $data);
// Output: {"code": 0, "msg": "success", "data": {...}}

// ERROR
api_response(40001, 'Invalid parameter', null);
// Output: {"code": 40001, "msg": "Invalid parameter", "data": null}

// Error code ranges:
// 0          = Success
// 40000-49999 = Client errors (validation, auth)
// 50000-59999 = Server errors (internal, external API)
// 60000-69999 = Business logic errors
```

**Action**: All new code MUST use `api_response()`. Existing code to be migrated incrementally.

---

## 6. Escalation Path

### 6.1 Production Incidents

```
Level 1 — On-Call Developer (any role)
├── Acknowledge incident within 15 minutes
├── Assess severity (P0-P3)
├── Apply immediate mitigation if possible
└── If cannot resolve → escalate to Level 2

Level 2 — Architect + Relevant Role Lead
├── Diagnose root cause
├── Implement fix
├── Deploy hotfix if needed
└── If infrastructure issue → escalate to Level 3

Level 3 — Architect + DevOps (Full Team)
├── War room setup
├── Coordinate fix across all affected systems
├── Post-incident communication
└── Post-mortem within 48 hours
```

**Severity Classification:**
| Level | Description | Response Time | Example |
|-------|-------------|---------------|---------|
| P0 | Complete outage | 5 min | All APIs down, payment broken |
| P1 | Major feature broken | 30 min | AI generation not working |
| P2 | Partial degradation | 2 hours | Slow API responses, some errors |
| P3 | Minor bug | 1 business day | UI glitch, non-critical error |

### 6.2 Architecture Decisions

```
Step 1: Role identifies need for architecture decision
        ↓
Step 2: Write ADR draft (1-page max)
        ↓
Step 3: Share in Weekly Tech Review
        ↓
Step 4: Team discussion (15 min max)
        ↓
Step 5: Architect makes final decision
        ↓
Step 6: ADR documented in /docs/adrs/
        ↓
Step 7: Implementation assigned to relevant role
```

**Decisions requiring Architect approval:**
- New database connections or schema changes affecting multiple modules
- New AI provider integration
- Auth system changes
- Breaking API changes
- New third-party dependencies
- Queue architecture changes
- Response format changes

### 6.3 Performance Issues

```
Identified by:
├── QA (during testing) → Report in bug tracker → Backend triages
├── Monitoring alert → DevOps notifies → Architect investigates
├── User report → QA reproduces → Backend investigates
└── Developer (during dev) → Profile → Fix or escalate

Investigation path:
1. Developer profiles locally (Xdebug, query log)
2. Backend analyzes slow queries (MySQL slow query log)
3. Architect reviews architecture (caching strategy, N+1 queries)
4. DevOps checks server resources (CPU, memory, I/O)
5. AI Engineer checks external API latency (if AI-related)
```

**Performance budgets:**
| Operation | Budget | Owner |
|-----------|--------|-------|
| API response (non-AI) | < 500ms p95 | Backend |
| Page load (H5) | < 3s | Frontend |
| Generation job start | < 10s from queue | AI Engineer |
| Milvus search | < 100ms | AI Engineer |
| Queue processing | < 30s per job | Backend + AI |

### 6.4 Security Concerns

```
Discovered by: Anyone on team
        ↓
Report to: Architect IMMEDIATELY (not in public channel)
        ↓
Architect assesses severity:
├── Critical → Hotfix within 4 hours
├── High → Fix within 24 hours
└── Medium/Low → Fix in next sprint
        ↓
DevOps deploys hotfix
        ↓
Architect verifies fix
        ↓
Post-mortem (if critical/high)
```

**Security checklist for all PRs:**
- [ ] No hardcoded credentials or API keys
- [ ] SQL uses parameterized queries (ThinkPHP ORM, not raw SQL with concatenation)
- [ ] User input is validated (use validation classes, not manual checks)
- [ ] Auth middleware is applied to protected routes
- [ ] Sensitive data is not logged
- [ ] File uploads are validated (type, size, content)

---

## 7. Known Technical Debt

### 7.1 Critical (Must Address)

| Issue | Location | Impact | Owner | Effort |
|-------|----------|--------|-------|--------|
| GenerationService god-class | `/app/service/GenerationService.php` (263KB, 6,783 lines) | Unmaintainable, high bug risk | Architect + AI Engineer | 4-6 weeks |
| Blocking `sleep()` in queue workers | 22 files (see grep in Section 1.4) | Queue stalls, jobs delayed | AI Engineer | 1 week |
| Milvus metadata not stored | `/app/service/MilvusService.php` insert calls | Vectors untraceable | AI Engineer | 2 days |

### 7.2 High (Should Address)

| Issue | Location | Impact | Owner | Effort |
|-------|----------|--------|-------|--------|
| Inconsistent provider integration patterns | `/app/service/KlingAIService.php`, `/app/service/VolcengineVideoService.php`, etc. | Duplicated code, hard to add new providers | AI Engineer | 2 weeks |
| 4 parallel auth systems | 5 middleware files | Security risk, confusing | Architect | 3 weeks |
| 3 response formats | Throughout codebase | Frontend parsing complexity | Backend | 1 week (incremental) |
| No validation classes | `/app/validate/` (only 1 file) | Manual validation everywhere | Backend | Ongoing |

### 7.3 Medium (Nice to Have)

| Issue | Location | Impact | Owner | Effort |
|-------|----------|--------|-------|--------|
| App.vue is 91KB | `/uniapp/App.vue` | Slow load, hard to maintain | Frontend | 1 week |
| No CI/CD pipeline | N/A | Manual deployments | DevOps | 1 week |
| No PHPUnit tests | N/A | Manual testing only | QA | Ongoing |
| 80+ migration scripts at root | Root level (`migrate_*.php`) | Cluttered, hard to track | Backend | 3 days |
| Inconsistent error logging | Throughout codebase | Hard to debug | Backend | Ongoing |

---

## Appendix A: Quick Reference

### A.1 Key File Paths

```
Configuration:     /config/*.php
Controllers:       /app/controller/
Services:          /app/service/
Jobs:              /app/job/
Middleware:        /app/middleware/
Models:            /app/model/
Validation:        /app/validate/
Helpers:           /app/common.php
UniApp Source:     /uniapp/
H5 Build Output:   /h5/
Migrations:        /migrate_*.php (root)
Test Scripts:      /test_*.php, /test_*.html (root)
Deploy Scripts:    /deploy/, /*.sh (root)
Documentation:     /docs/
Runtime:           /runtime/
```

### A.2 Database Connections

```
Connection 1: mysql      — Main application database (config.php)
Connection 2: w7_mysql   — Secondary database (config.php w7 section)
Connection 3: huodong    — Activity/event database (config.php huodong section)
Connection 4: sysadmin   — System admin database (guobaosysadmin)
```

### A.3 AI Providers (Current)

```
Kling      — /app/service/KlingAIService.php
Seedance   — /app/service/VolcengineVideoService.php
PixVerse   — (integrated, see migrate_aishi_pixverse.php)
Jimeng     — /app/service/ (see migrate_jimeng*.php)
Ollama     — /app/service/OllamaChatService.php
VoxCPM2    — /app/service/ (see migrate_voxcpm.php)
```

### A.4 Auth Systems

```
AdminTokenAuth   — Cache-based admin authentication
UserTokenAuth    — Cache-based user authentication
DeviceTokenAuth  — Service-based device authentication (kiosks)
hd/AdminAuth     — HD module specific authentication
SignatureVerify  — API signature verification for external APIs
```

---

*This document is a living artifact. Update it as the team and project evolve.
Last reviewed: 2025-04-30*

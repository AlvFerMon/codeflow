# Graph Report - codeflow  (2026-10-13)

## Corpus Check
- 345 files · ~107,357 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1828 nodes · 4151 edges · 221 communities (164 shown, 57 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 42 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `f03b0764`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- VcsService
- initSeek
- EncryptionTest
- Project
- Illuminate\Http\RedirectResponse
- ApiResult
- Illuminate\Database\Migrations\Migration
- Illuminate\Http\Request
- Repository
- Illuminate\Auth\Access\Response
- RepositoryAdminTest
- RepositoryService
- Code/RepositoryController.php
- SessionStore
- MyersDiffTest
- User
- AppNotification
- Illuminate\Database\Eloquent\Relations\BelongsTo
- NotificationPagesTest
- Illuminate\View\View
- devDependencies
- Closure
- CodeFlowApi
- LoginViewModelTest
- CodeFlow — Propuesta de arquitectura (Fase 0)
- Issue
- Illuminate\Database\Eloquent\Relations\HasMany
- AppContainer
- IssueService
- ActivityPresenter
- IssuesWebTest
- MainScreen
- NotificationListScreen.kt
- Collab/PullRequestController.php
- ApiClient
- DemoSeeder.php
- Branch
- EncryptionTest.php
- .hash
- RepositoryCipher
- Illuminate\Database\Eloquent\Relations\BelongsToMany
- PullRequestsWebTest
- LoginViewModel
- Illuminate\Database\Eloquent\Model
- AppServiceProvider.php
- FilesTest
- composer.json
- scripts
- BrowseCodeTest
- CodeFlowLogo
- VerifyEmailViewModel
- CodeFlow — App Android
- HomeAndExploreTest
- ApiResultTest
- CodeFlowTheme
- SystemNotifier
- ActivityViewModel
- Commit
- FolderUploadTest
- formatRelativeDateTime
- ActivityCategory
- ActivityScreen.kt
- VisibilityTest
- ProjectPagesTest
- TestCase
- NotificationDtos.kt
- AuthInterceptor
- AuthTest
- NotificationTest
- BuildsCollabFixtures.php
- MainActivity.kt
- CodeFlow
- BaseUrlInterceptor
- require-dev
- setup
- IssueComment
- MemberPagesTest
- Repository.php
- 9. Diseño del VCS Engine
- PageDtoTest
- config
- BuildsProjects.php
- ActivityLog.php
- decodePage
- ServerUrlTest
- ChecksProjectRoles.php
- BuildsCodeFixtures.php
- 10. Endpoints REST iniciales
- psr-4
- require
- logging.php
- app.blade.php
- guest.blade.php
- 0. Decisiones tomadas y problemas detectados
- Puesta en marcha con Docker
- gradlew
- post-create-project-cmd
- issues/show.blade.php
- collab.blade.php
- pulls/show.blade.php
- CollabLabels
- extra
- mobile-layout.blade.php
- layout.blade.php
- issues/index.blade.php
- projects/edit.blade.php
- pulls/index.blade.php
- console.php
- 2. Estructura de carpetas
- AGENTS.md
- CLAUDE.md
- activity/index.blade.php
- branches/index.blade.php
- commits/index.blade.php
- commits/show.blade.php
- app-layout.blade.php
- guest-layout.blade.php
- 401.blade.php
- 403.blade.php
- 404.blade.php
- 419.blade.php
- 429.blade.php
- 500.blade.php
- 503.blade.php
- form.blade.php
- issues/create.blade.php
- members/index.blade.php
- account.blade.php
- activity.blade.php
- notifications.blade.php
- placeholder.blade.php
- projects/create.blade.php
- projects/show.blade.php
- pulls/create.blade.php
- blob.blade.php
- settings.blade.php
- repositories/show.blade.php
- entrypoint.sh

## God Nodes (most connected - your core abstractions)
1. `User` - 202 edges
2. `Repository` - 143 edges
3. `Project` - 102 edges
4. `PullRequest` - 83 edges
5. `Branch` - 65 edges
6. `TestCase` - 65 edges
7. `VcsService` - 62 edges
8. `Commit` - 54 edges
9. `Controller` - 52 edges
10. `Issue` - 50 edges

## Surprising Connections (you probably didn't know these)
- `FakeAuthRepository` --calls--> `User`  [EXTRACTED]
  android/app/src/test/java/com/codeflow/app/feature/auth/LoginViewModelTest.kt → backend/app/Models/User.php
- `makeBranch()` --references--> `Branch`  [EXTRACTED]
  backend/tests/Feature/Web/Collab/BuildsCollabFixtures.php → backend/app/Models/Branch.php
- `latestNotificationFor()` --references--> `User`  [EXTRACTED]
  backend/tests/Feature/Web/Collab/BuildsCollabFixtures.php → backend/app/Models/User.php
- `AppContainer` --calls--> `NotificationPoller`  [EXTRACTED]
  android/app/src/main/java/com/codeflow/app/core/di/AppContainer.kt → android/app/src/main/java/com/codeflow/app/core/notifications/NotificationPoller.kt
- `AppContainer` --calls--> `SystemNotifier`  [EXTRACTED]
  android/app/src/main/java/com/codeflow/app/core/di/AppContainer.kt → android/app/src/main/java/com/codeflow/app/core/notifications/SystemNotifier.kt

## Import Cycles
- None detected.

## Communities (221 total, 57 thin omitted)

### Community 0 - "VcsService"
Cohesion: 0.17
Nodes (4): CommitFile, VcsService, CommitHasher, Illuminate\Support\Collection

### Community 1 - "initSeek"
Cohesion: 0.05
Nodes (42): applyStyle(), initAllIconBars(), initIconBar(), measure(), navigate(), measureSlots(), prefersReducedMotion(), initAllOneTimeCodes() (+34 more)

### Community 3 - "Project"
Cohesion: 0.08
Nodes (12): ActivityController, MemberController, MemberController, ProjectController, ActivityLog, Project, ProjectMember, MembershipService (+4 more)

### Community 4 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.08
Nodes (12): EmailVerificationController, LoginController, PasswordResetController, RegisterController, Illuminate\Auth\Events\PasswordReset, Illuminate\Auth\Events\Registered, Illuminate\Http\RedirectResponse, Illuminate\Support\Facades\Auth (+4 more)

### Community 5 - "ApiResult"
Cohesion: 0.11
Nodes (13): ApiResult, Error, Json, T, LaravelErrorBody, map(), safeApiCall(), Success (+5 more)

### Community 6 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.08
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 7 - "Illuminate\Http\Request"
Cohesion: 0.09
Nodes (11): AuthController, BranchController, NotificationController, ProjectController, PullRequestController, RepositoryController, ProfileController, PullRequest (+3 more)

### Community 8 - "Repository"
Cohesion: 0.12
Nodes (4): HomeController, RepositoryController, PullRequestController, Repository

### Community 9 - "Illuminate\Auth\Access\Response"
Cohesion: 0.12
Nodes (6): App\Policies\Concerns\ChecksProjectRoles, IssuePolicy, ProjectPolicy, PullRequestPolicy, RepositoryPolicy, Illuminate\Auth\Access\Response

### Community 10 - "RepositoryAdminTest"
Cohesion: 0.12
Nodes (3): BranchesTest, RepositoryAdminTest, BuildsCodeFixtures

### Community 11 - "RepositoryService"
Cohesion: 0.13
Nodes (3): PullRequestService, RepositoryService, Activity

### Community 12 - "Code/RepositoryController.php"
Cohesion: 0.15
Nodes (7): App\Models\Concerns\OrdersRepositoryAttributes, Blob, Visibility, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Symfony\Component\HttpFoundation\HeaderUtils

### Community 13 - "SessionStore"
Cohesion: 0.10
Nodes (14): NotificationPoller, StateFlow, Loading, LoggedIn, LoggedOut, ServerConfig, SessionState, SessionStore (+6 more)

### Community 14 - "MyersDiffTest"
Cohesion: 0.11
Nodes (5): HunkBuilder, MyersDiff, ExampleTest, MyersDiffTest, PHPUnit\Framework\TestCase

### Community 15 - "User"
Cohesion: 0.09
Nodes (13): User, CodeFlowFlowTest, as(), makeProject(), makeRepository(), static, setUpTeam(), EmailVerificationTest (+5 more)

### Community 16 - "AppNotification"
Cohesion: 0.13
Nodes (16): ActivityDto, ActivityProjectDto, ActivityRepositoryDto, NotificationDto, ActivityRepository, DefaultActivityRepository, toDomain(), ActivityItem (+8 more)

### Community 19 - "Illuminate\View\View"
Cohesion: 0.10
Nodes (9): Controller, ActivityController, ExploreController, NotificationController, CommitController, ProjectCollabController, PlaceholderController, Illuminate\Support\Facades\Route (+1 more)

### Community 20 - "devDependencies"
Cohesion: 0.09
Nodes (22): devDependencies, concurrently, @fontsource/instrument-sans, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, optionalDependencies (+14 more)

### Community 21 - "Closure"
Cohesion: 0.09
Nodes (18): attempt(), resolveBranch(), resolveRef(), attempt(), failed(), messageFromResponse(), EnsureEmailIsVerified, OptionalSanctum (+10 more)

### Community 22 - "CodeFlowApi"
Cohesion: 0.15
Nodes (9): CodeFlowApi, JsonElement, AuthResponse, LoginRequest, MessageDto, UserDto, UserSummaryDto, VerifyEmailRequest (+1 more)

### Community 23 - "LoginViewModelTest"
Cohesion: 0.11
Nodes (4): FakeAuthRepository, FakeServerConfig, LoginViewModelTest, CompletableDeferred

### Community 24 - "CodeFlow — Propuesta de arquitectura (Fase 0)"
Cohesion: 0.10
Nodes (21): 11. Flujo de autenticación, 12. Flujo de creación de un proyecto, 13. Flujo de creación de un repositorio, 14. Flujo de creación de un Pull Request, 15. Arquitectura futura del módulo de IA (opcional), 16. Plan de desarrollo, 17. Definición exacta del MVP, 18. Riesgos técnicos (+13 more)

### Community 25 - "Issue"
Cohesion: 0.19
Nodes (3): IssueController, IssueController, Issue

### Community 26 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.13
Nodes (3): conversationPage(), Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Support\Carbon

### Community 27 - "AppContainer"
Cohesion: 0.11
Nodes (16): CodeFlowApplication, AppContainer, NotificationWorker, ProjectDto, ProjectSummaryDto, DefaultProjectRepository, ProjectRepository, StateFlow (+8 more)

### Community 30 - "IssuesWebTest"
Cohesion: 0.11
Nodes (3): IssuesWebTest, Phase3ScreensTest, BuildsCollabFixtures

### Community 31 - "MainScreen"
Cohesion: 0.19
Nodes (13): Expired, SessionEvent, AccountMenu(), ActivityScreen(), LoginScreen(), MainScreen(), MainTab, ACTIVITY (+5 more)

### Community 32 - "NotificationListScreen.kt"
Cohesion: 0.18
Nodes (7): NotificationCard(), NotificationListContent(), NotificationListPreview(), StateFlow, ViewModel, NotificationListUiState, NotificationListViewModel

### Community 33 - "Collab/PullRequestController.php"
Cohesion: 0.14
Nodes (8): App\Http\Controllers\Web\Code\Concerns\InteractsWithCode, App\Http\Controllers\Web\Collab\Concerns\HandlesServiceErrors, App\Http\Controllers\Web\Collab\Concerns\PaginatesConversation, BranchController, DiffSelection, Illuminate\Pagination\LengthAwarePaginator, Illuminate\Support\Facades\Gate, Illuminate\Validation\Rule

### Community 34 - "ApiClient"
Cohesion: 0.23
Nodes (6): ApiClient, Json, Interceptor, Response, UnauthorizedInterceptor, ActivityDtoTest

### Community 35 - "DemoSeeder.php"
Cohesion: 0.36
Nodes (3): DatabaseSeeder, DemoSeeder, Illuminate\Database\Seeder

### Community 36 - "Branch"
Cohesion: 0.21
Nodes (4): FileController, FileController, Closure, Branch

### Community 37 - "EncryptionTest.php"
Cohesion: 0.26
Nodes (4): KeyManager, Illuminate\Contracts\Encryption\DecryptException, Illuminate\Encryption\Encrypter, RuntimeException

### Community 38 - ".hash"
Cohesion: 0.08
Nodes (11): ProjectActivityNotification, VerificationCode, EmailVerificationService, static, UserFactory, AuthPagesTest, Illuminate\Auth\Events\Verified, Illuminate\Bus\Queueable (+3 more)

### Community 40 - "Illuminate\Database\Eloquent\Relations\BelongsToMany"
Cohesion: 0.14
Nodes (3): Illuminate\Database\Eloquent\Relations\BelongsToMany, Illuminate\Database\Eloquent\Relations\HasManyThrough, Illuminate\Database\Eloquent\Relations\MorphMany

### Community 42 - "LoginViewModel"
Cohesion: 0.19
Nodes (4): ServerUrl, StateFlow, ViewModel, LoginViewModel

### Community 43 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.21
Nodes (5): RepositoryEncrypted, PullRequestReview, Illuminate\Contracts\Database\Eloquent\CastsAttributes, Illuminate\Database\Eloquent\Model, LogicException

### Community 44 - "AppServiceProvider.php"
Cohesion: 0.21
Nodes (6): AppServiceProvider, VcsServiceProvider, Illuminate\Cache\RateLimiting\Limit, Illuminate\Support\Facades\RateLimiter, Illuminate\Support\ServiceProvider, Laravel\Sanctum\PersonalAccessToken

### Community 46 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 47 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 49 - "CodeFlowLogo"
Cohesion: 0.33
Nodes (9): CodeFlowLogo(), CodeFlowMark(), Color, Modifier, LoginContent(), LoginPreview(), LoginUiState, Dp (+1 more)

### Community 50 - "VerifyEmailViewModel"
Cohesion: 0.24
Nodes (7): VerifyEmailContent(), VerifyEmailPreview(), VerifyEmailScreen(), StateFlow, ViewModel, VerifyEmailUiState, VerifyEmailViewModel

### Community 51 - "CodeFlow — App Android"
Cohesion: 0.15
Nodes (13): Abrir el proyecto, Añadir una pantalla nueva, CodeFlow — App Android, Ejecutar contra el backend en Docker, Emulador, Estructura del proyecto, Generar un APK/AAB firmado (más adelante), Hoja de ruta (+5 more)

### Community 54 - "CodeFlowTheme"
Cohesion: 0.36
Nodes (11): EmptyView(), EmptyViewPreview(), ErrorView(), Modifier, LoadingView(), CodeFlowTheme(), ListFooter(), ProjectCard() (+3 more)

### Community 55 - "SystemNotifier"
Cohesion: 0.29
Nodes (3): SystemNotifier, PendingIntent, R

### Community 56 - "ActivityViewModel"
Cohesion: 0.29
Nodes (4): ActivityViewModel, StateFlow, ViewModel, Job

### Community 59 - "formatRelativeDateTime"
Cohesion: 0.31
Nodes (4): formatApiDateTime(), formatRelativeDateTime(), parseApiInstant(), DateFormatTest

### Community 60 - "ActivityCategory"
Cohesion: 0.20
Nodes (9): ActivityCategory, BRANCH, COMMIT, ISSUE, MEMBER, OTHER, PROJECT, PULL_REQUEST (+1 more)

### Community 61 - "ActivityScreen.kt"
Cohesion: 0.42
Nodes (9): ActivityContent(), ActivityList(), ActivityPreview(), ActivityRow(), categoryColor(), Color, ProjectFilter(), Tag() (+1 more)

### Community 62 - "VisibilityTest"
Cohesion: 0.10
Nodes (4): ActivityApiTest, ObserverTest, VisibilityTest, Tests\Concerns\BuildsProjects

### Community 64 - "TestCase"
Cohesion: 0.10
Nodes (14): ExampleTest, Phase4PerformanceTest, ResetLinkTest, BrowseAtCommitTest, LargeDiffTest, LoginTest, TestCase, TreeUnderTest (+6 more)

### Community 65 - "NotificationDtos.kt"
Cohesion: 0.39
Nodes (5): FlexibleStringSerializer, KSerializer, Decoder, Encoder, SerialDescriptor

### Community 66 - "AuthInterceptor"
Cohesion: 0.70
Nodes (3): AuthInterceptor, Interceptor, Response

### Community 69 - "BuildsCollabFixtures.php"
Cohesion: 0.25
Nodes (8): actAs(), asGuest(), buildCollabProject(), latestNotificationFor(), makeBranch(), makeIssue(), makePullRequest(), static

### Community 70 - "MainActivity.kt"
Cohesion: 0.46
Nodes (4): MainActivity, Bundle, ComponentActivity, Intent

### Community 71 - "CodeFlow"
Cohesion: 0.25
Nodes (5): CodeFlow, Documentación, Estructura del repositorio, Roles, Tests

### Community 72 - "BaseUrlInterceptor"
Cohesion: 0.70
Nodes (3): BaseUrlInterceptor, Interceptor, Response

### Community 73 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pao, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 74 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 76 - "MemberPagesTest"
Cohesion: 0.07
Nodes (5): MemberPagesTest, MobileVersionTest, NavigationAndUxTest, ProfilePagesTest, CreatesProjects

### Community 78 - "9. Diseño del VCS Engine"
Cohesion: 0.25
Nodes (8): 9.1 Modelo (inspirado en Git, adaptado a SQL), 9.2 Operaciones de la fachada `VcsEngine`, 9.3 Algoritmos, 9.4 Cifrado (envelope encryption), 9.5 Seguridad del motor, 9.6 Lo que el cifrado NO protege (para la memoria), 9.7 Cambios respecto al `VcsService` actual, 9. Diseño del VCS Engine

### Community 80 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 81 - "BuildsProjects.php"
Cohesion: 0.20
Nodes (8): actingAsUser(), buildProject(), makePublic(), requireVisibilityColumn(), Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum

### Community 83 - "decodePage"
Cohesion: 0.53
Nodes (5): decodePage(), JsonElement, KSerializer, T, PageDto

### Community 85 - "ChecksProjectRoles.php"
Cohesion: 0.73
Nodes (5): canSee(), isMember(), isRole(), requireRole(), roleOf()

### Community 87 - "BuildsCodeFixtures.php"
Cohesion: 0.47
Nodes (5): asGuestUser(), branch(), buildFixtures(), static, signIn()

### Community 88 - "10. Endpoints REST iniciales"
Cohesion: 0.33
Nodes (6): 10. Endpoints REST iniciales, Autenticación y perfil, Issues, Proyectos y miembros, Pull Requests, Repositorios y código

### Community 89 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 90 - "require"
Cohesion: 0.40
Nodes (5): require, laravel/framework, laravel/sanctum, laravel/tinker, php

### Community 91 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 92 - "app.blade.php"
Cohesion: 0.40
Nodes (4): layouts._confirm-dialog, layouts._icons, layouts._theme, layouts._theme-switch

### Community 93 - "guest.blade.php"
Cohesion: 0.40
Nodes (4): layouts._confirm-dialog, layouts._icons, layouts._theme, layouts._theme-switch

### Community 94 - "0. Decisiones tomadas y problemas detectados"
Cohesion: 0.40
Nodes (5): 0.1 Decisiones confirmadas, 0.2 Estado actual del repositorio, 0.3 Justificación de un VCS propio (para la memoria y la defensa), 0.4 Otros problemas del planteamiento, 0. Decisiones tomadas y problemas detectados

### Community 95 - "Puesta en marcha con Docker"
Cohesion: 0.40
Nodes (5): Puesta en marcha con Docker, Registro y verificación del email, Rendimiento en Docker (Windows), Subida de archivos y carpetas, Usuarios de prueba

### Community 96 - "gradlew"
Cohesion: 0.83
Nodes (3): gradlew script, die(), warn()

### Community 97 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 98 - "issues/show.blade.php"
Cohesion: 0.50
Nodes (3): partials._conversation-nav, repositories._header, issues._event

### Community 99 - "collab.blade.php"
Cohesion: 0.50
Nodes (3): issues._row, projects._header, pulls._row

### Community 100 - "pulls/show.blade.php"
Cohesion: 0.50
Nodes (3): partials._conversation-nav, repositories._header, pulls._event

### Community 102 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 109 - "2. Estructura de carpetas"
Cohesion: 0.67
Nodes (3): 2.1 Monorepo (confirmado), 2.2 Backend Laravel, 2. Estructura de carpetas

## Knowledge Gaps
- **194 isolated node(s):** `LaravelErrorBody`, `Loading`, `LoggedOut`, `Expired`, `ActivityProjectDto` (+189 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **57 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `VcsService`, `EncryptionTest`, `Project`, `Illuminate\Http\RedirectResponse`, `Illuminate\Http\Request`, `Repository`, `Illuminate\Auth\Access\Response`, `RepositoryService`, `Code/RepositoryController.php`, `NotificationPagesTest`, `LoginViewModelTest`, `Issue`, `IssueService`, `IssuesWebTest`, `Collab/PullRequestController.php`, `DemoSeeder.php`, `EncryptionTest.php`, `.hash`, `Illuminate\Database\Eloquent\Relations\BelongsToMany`, `AppServiceProvider.php`, `HomeAndExploreTest`, `VisibilityTest`, `TestCase`, `AuthTest`, `NotificationTest`, `BuildsCollabFixtures.php`, `IssueComment`, `MemberPagesTest`, `BuildsProjects.php`, `ChecksProjectRoles.php`, `BuildsCodeFixtures.php`?**
  _High betweenness centrality (0.352) - this node is a cross-community bridge._
- **Why does `FakeAuthRepository` connect `LoginViewModelTest` to `ApiResult`, `User`?**
  _High betweenness centrality (0.236) - this node is a cross-community bridge._
- **Why does `ApiResult` connect `ApiResult` to `NotificationListScreen.kt`, `LoginViewModel`, `SessionStore`, `AppNotification`, `VerifyEmailViewModel`, `ApiResultTest`, `LoginViewModelTest`, `ActivityViewModel`, `AppContainer`?**
  _High betweenness centrality (0.148) - this node is a cross-community bridge._
- **What connects `LaravelErrorBody`, `Loading`, `LoggedOut` to the rest of the system?**
  _194 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `initSeek` be split into smaller, more focused modules?**
  _Cohesion score 0.054354178842782 - nodes in this community are weakly interconnected._
- **Should `Project` be split into smaller, more focused modules?**
  _Cohesion score 0.07704918032786885 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Http\RedirectResponse` be split into smaller, more focused modules?**
  _Cohesion score 0.08108108108108109 - nodes in this community are weakly interconnected._
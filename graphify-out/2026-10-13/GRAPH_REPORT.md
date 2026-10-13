# Graph Report - codeflow  (2026-10-13)

## Corpus Check
- 343 files · ~106,290 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1823 nodes · 4146 edges · 221 communities (166 shown, 55 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 40 edges (avg confidence: 0.85)
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
- Illuminate\Http\Request
- ApiResult
- Illuminate\Database\Migrations\Migration
- Illuminate\Http\JsonResponse
- Repository
- Illuminate\Auth\Access\Response
- Branch
- PullRequest
- Controller
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
- Illuminate\Database\Eloquent\Model
- ActivityPresenter
- IssuesWebTest
- LoadingView
- NotificationListScreen.kt
- Illuminate\Foundation\Testing\RefreshDatabase
- .create
- ProjectService
- FileController
- TestCase
- .hash
- InteractsWithCode.php
- Illuminate\Database\Eloquent\Relations\BelongsToMany
- PullRequestsWebTest
- LoginViewModel
- Blob
- ProjectController
- MobileController
- composer.json
- scripts
- NotificationWorker
- CodeFlowLogo
- VerifyEmailViewModel
- CodeFlow — App Android
- HomeAndExploreTest
- ApiResultTest
- CodeFlowTheme
- SystemNotifier
- ActivityViewModel
- Phase4PerformanceTest.php
- FolderUploadTest
- formatRelativeDateTime
- ActivityCategory
- ActivityScreen.kt
- VisibilityTest
- ProjectPagesTest
- Tests\Feature\Web\Account\CreatesProjects
- NotificationDtos.kt
- ObserverTest
- AuthTest
- NotificationTest
- BuildsCollabFixtures.php
- MainActivity.kt
- CodeFlow
- NotificationController
- require-dev
- setup
- CodeFlowFlowTest
- MemberPagesTest
- MobileVersionTest
- 9. Diseño del VCS Engine
- PageDtoTest
- config
- BuildsProjects.php
- LoginTest
- decodePage
- ServerUrlTest
- ChecksProjectRoles.php
- UserFactory.php
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
- `CodeFlowApplication` --references--> `AppContainer`  [EXTRACTED]
  android/app/src/main/java/com/codeflow/app/CodeFlowApplication.kt → android/app/src/main/java/com/codeflow/app/core/di/AppContainer.kt
- `AppContainer` --calls--> `NotificationPoller`  [EXTRACTED]
  android/app/src/main/java/com/codeflow/app/core/di/AppContainer.kt → android/app/src/main/java/com/codeflow/app/core/notifications/NotificationPoller.kt

## Import Cycles
- None detected.

## Communities (221 total, 55 thin omitted)

### Community 0 - "VcsService"
Cohesion: 0.06
Nodes (8): Commit, CommitFile, VcsService, CommitHasher, BrowseCodeTest, FilesTest, RepositoryAdminTest, Illuminate\Support\Collection

### Community 1 - "initSeek"
Cohesion: 0.06
Nodes (40): applyStyle(), initAllIconBars(), initIconBar(), measure(), navigate(), measureSlots(), prefersReducedMotion(), initAllOneTimeCodes() (+32 more)

### Community 2 - "EncryptionTest"
Cohesion: 0.07
Nodes (13): AppServiceProvider, VcsServiceProvider, KeyManager, RepositoryCipher, ObjectStore, EncryptionTest, Illuminate\Cache\RateLimiting\Limit, Illuminate\Contracts\Encryption\DecryptException (+5 more)

### Community 3 - "Project"
Cohesion: 0.10
Nodes (11): ActivityController, ActivityController, MemberController, ActivityLog, Project, ProjectMember, MembershipService, Roles (+3 more)

### Community 4 - "Illuminate\Http\Request"
Cohesion: 0.09
Nodes (9): NotificationController, ProfileController, EmailVerificationController, LoginController, PasswordResetController, RegisterController, PullRequestController, Illuminate\Http\RedirectResponse (+1 more)

### Community 5 - "ApiResult"
Cohesion: 0.10
Nodes (15): ApiResult, Error, Json, T, LaravelErrorBody, map(), safeApiCall(), Success (+7 more)

### Community 6 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.08
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 7 - "Illuminate\Http\JsonResponse"
Cohesion: 0.10
Nodes (6): AuthController, CommitController, MemberController, ProjectController, RepositoryController, Illuminate\Http\JsonResponse

### Community 8 - "Repository"
Cohesion: 0.13
Nodes (5): App\Http\Controllers\Web\Collab\Concerns\HandlesServiceErrors, App\Http\Controllers\Web\Collab\Concerns\PaginatesConversation, RepositoryController, IssueController, Repository

### Community 9 - "Illuminate\Auth\Access\Response"
Cohesion: 0.12
Nodes (6): App\Policies\Concerns\ChecksProjectRoles, IssuePolicy, ProjectPolicy, PullRequestPolicy, RepositoryPolicy, Illuminate\Auth\Access\Response

### Community 10 - "Branch"
Cohesion: 0.11
Nodes (8): BranchController, FileController, Branch, RepositoryService, Activity, BranchesTest, BuildsCodeFixtures, Illuminate\Pagination\LengthAwarePaginator

### Community 11 - "PullRequest"
Cohesion: 0.16
Nodes (3): PullRequestController, PullRequest, PullRequestService

### Community 12 - "Controller"
Cohesion: 0.09
Nodes (14): Controller, PlaceholderController, Visibility, Illuminate\Auth\Events\PasswordReset, Illuminate\Auth\Events\Registered, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB (+6 more)

### Community 13 - "SessionStore"
Cohesion: 0.10
Nodes (14): NotificationPoller, StateFlow, Loading, LoggedIn, LoggedOut, ServerConfig, SessionState, SessionStore (+6 more)

### Community 14 - "MyersDiffTest"
Cohesion: 0.11
Nodes (5): HunkBuilder, MyersDiff, ExampleTest, MyersDiffTest, PHPUnit\Framework\TestCase

### Community 15 - "User"
Cohesion: 0.11
Nodes (12): User, as(), makeProject(), makeRepository(), static, setUpTeam(), EmailVerificationTest, Illuminate\Contracts\Auth\MustVerifyEmail (+4 more)

### Community 16 - "AppNotification"
Cohesion: 0.11
Nodes (15): ActivityDto, ActivityProjectDto, ActivityRepositoryDto, ProjectDto, ProjectSummaryDto, toDomain(), ActivityItem, AppNotification (+7 more)

### Community 17 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.09
Nodes (3): IssueComment, PullRequestComment, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 18 - "NotificationPagesTest"
Cohesion: 0.12
Nodes (6): ProjectActivityNotification, VerificationCode, NotificationPagesTest, Illuminate\Bus\Queueable, Illuminate\Notifications\Messages\MailMessage, Illuminate\Notifications\Notification

### Community 19 - "Illuminate\View\View"
Cohesion: 0.14
Nodes (6): App\Http\Controllers\Web\Code\Concerns\InteractsWithCode, BranchController, CommitController, ProjectCollabController, DiffSelection, Illuminate\View\View

### Community 20 - "devDependencies"
Cohesion: 0.09
Nodes (22): devDependencies, concurrently, @fontsource/instrument-sans, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, optionalDependencies (+14 more)

### Community 21 - "Closure"
Cohesion: 0.17
Nodes (10): EnsureEmailIsVerified, OptionalSanctum, RedirectUnverifiedUsers, RequireLoginOnPhones, ServerTiming, Closure, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions (+2 more)

### Community 22 - "CodeFlowApi"
Cohesion: 0.15
Nodes (8): CodeFlowApi, JsonElement, AuthResponse, LoginRequest, MessageDto, UserDto, UserSummaryDto, UnreadCountDto

### Community 23 - "LoginViewModelTest"
Cohesion: 0.11
Nodes (4): FakeAuthRepository, FakeServerConfig, LoginViewModelTest, CompletableDeferred

### Community 24 - "CodeFlow — Propuesta de arquitectura (Fase 0)"
Cohesion: 0.10
Nodes (21): 11. Flujo de autenticación, 12. Flujo de creación de un proyecto, 13. Flujo de creación de un repositorio, 14. Flujo de creación de un Pull Request, 15. Arquitectura futura del módulo de IA (opcional), 16. Plan de desarrollo, 17. Definición exacta del MVP, 18. Riesgos técnicos (+13 more)

### Community 25 - "Issue"
Cohesion: 0.17
Nodes (3): IssueController, Issue, IssueService

### Community 26 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.11
Nodes (4): conversationPage(), Illuminate\Database\Eloquent\Builder, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Support\Carbon

### Community 27 - "AppContainer"
Cohesion: 0.20
Nodes (12): AppContainer, ApiClient, Json, ActivityRepository, DefaultActivityRepository, DefaultProjectRepository, ProjectRepository, StateFlow (+4 more)

### Community 28 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.15
Nodes (4): PullRequestReview, NotificationService, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\MorphTo

### Community 30 - "IssuesWebTest"
Cohesion: 0.11
Nodes (3): IssuesWebTest, Phase3ScreensTest, BuildsCollabFixtures

### Community 31 - "LoadingView"
Cohesion: 0.19
Nodes (13): Expired, SessionEvent, AccountMenu(), LoadingView(), ActivityScreen(), MainScreen(), MainTab, ACTIVITY (+5 more)

### Community 32 - "NotificationListScreen.kt"
Cohesion: 0.18
Nodes (7): NotificationCard(), NotificationListContent(), NotificationListPreview(), StateFlow, ViewModel, NotificationListUiState, NotificationListViewModel

### Community 33 - "Illuminate\Foundation\Testing\RefreshDatabase"
Cohesion: 0.18
Nodes (6): ActivityApiTest, ResetLinkTest, Illuminate\Auth\Notifications\ResetPassword, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Support\Facades\Notification, Tests\Concerns\BuildsProjects

### Community 34 - ".create"
Cohesion: 0.20
Nodes (9): AuthInterceptor, Interceptor, Response, BaseUrlInterceptor, Interceptor, Response, Interceptor, Response (+1 more)

### Community 35 - "ProjectService"
Cohesion: 0.16
Nodes (6): ProjectService, DatabaseSeeder, DemoSeeder, Illuminate\Auth\Events\Verified, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash

### Community 37 - "TestCase"
Cohesion: 0.15
Nodes (5): ExampleTest, NavigationAndUxTest, TestCase, Illuminate\Foundation\Testing\TestCase, Illuminate\Http\UploadedFile

### Community 39 - "InteractsWithCode.php"
Cohesion: 0.16
Nodes (8): attempt(), resolveBranch(), resolveRef(), attempt(), failed(), messageFromResponse(), Illuminate\Http\Exceptions\HttpResponseException, Symfony\Component\HttpKernel\Exception\HttpException

### Community 40 - "Illuminate\Database\Eloquent\Relations\BelongsToMany"
Cohesion: 0.14
Nodes (3): Illuminate\Database\Eloquent\Relations\BelongsToMany, Illuminate\Database\Eloquent\Relations\HasManyThrough, Illuminate\Database\Eloquent\Relations\MorphMany

### Community 42 - "LoginViewModel"
Cohesion: 0.19
Nodes (4): ServerUrl, StateFlow, ViewModel, LoginViewModel

### Community 43 - "Blob"
Cohesion: 0.20
Nodes (5): App\Models\Concerns\OrdersRepositoryAttributes, RepositoryEncrypted, Blob, Illuminate\Contracts\Database\Eloquent\CastsAttributes, LogicException

### Community 44 - "ProjectController"
Cohesion: 0.18
Nodes (3): ExploreController, ProjectController, Illuminate\Support\Facades\Route

### Community 46 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 47 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 48 - "NotificationWorker"
Cohesion: 0.22
Nodes (6): CodeFlowApplication, NotificationWorker, Application, Context, CoroutineWorker, Result

### Community 49 - "CodeFlowLogo"
Cohesion: 0.31
Nodes (10): CodeFlowLogo(), CodeFlowMark(), Color, Modifier, LoginContent(), LoginPreview(), LoginScreen(), LoginUiState (+2 more)

### Community 50 - "VerifyEmailViewModel"
Cohesion: 0.24
Nodes (7): VerifyEmailContent(), VerifyEmailPreview(), VerifyEmailScreen(), StateFlow, ViewModel, VerifyEmailUiState, VerifyEmailViewModel

### Community 51 - "CodeFlow — App Android"
Cohesion: 0.15
Nodes (13): Abrir el proyecto, Añadir una pantalla nueva, CodeFlow — App Android, Ejecutar contra el backend en Docker, Emulador, Estructura del proyecto, Generar un APK/AAB firmado (más adelante), Hoja de ruta (+5 more)

### Community 52 - "HomeAndExploreTest"
Cohesion: 0.15
Nodes (3): HomeAndExploreTest, ProfilePagesTest, CreatesProjects

### Community 54 - "CodeFlowTheme"
Cohesion: 0.36
Nodes (10): EmptyView(), EmptyViewPreview(), ErrorView(), Modifier, CodeFlowTheme(), ListFooter(), ProjectCard(), ProjectListContent() (+2 more)

### Community 55 - "SystemNotifier"
Cohesion: 0.29
Nodes (3): SystemNotifier, PendingIntent, R

### Community 56 - "ActivityViewModel"
Cohesion: 0.29
Nodes (4): ActivityViewModel, StateFlow, ViewModel, Job

### Community 57 - "Phase4PerformanceTest.php"
Cohesion: 0.18
Nodes (5): Phase4PerformanceTest, Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum

### Community 59 - "formatRelativeDateTime"
Cohesion: 0.31
Nodes (4): formatApiDateTime(), formatRelativeDateTime(), parseApiInstant(), DateFormatTest

### Community 60 - "ActivityCategory"
Cohesion: 0.20
Nodes (9): ActivityCategory, BRANCH, COMMIT, ISSUE, MEMBER, OTHER, PROJECT, PULL_REQUEST (+1 more)

### Community 61 - "ActivityScreen.kt"
Cohesion: 0.42
Nodes (9): ActivityContent(), ActivityList(), ActivityPreview(), ActivityRow(), categoryColor(), Color, ProjectFilter(), Tag() (+1 more)

### Community 64 - "Tests\Feature\Web\Account\CreatesProjects"
Cohesion: 0.27
Nodes (4): BrowseAtCommitTest, LargeDiffTest, TreeUnderTest, Tests\Feature\Web\Account\CreatesProjects

### Community 65 - "NotificationDtos.kt"
Cohesion: 0.33
Nodes (6): FlexibleStringSerializer, KSerializer, NotificationDto, Decoder, Encoder, SerialDescriptor

### Community 69 - "BuildsCollabFixtures.php"
Cohesion: 0.25
Nodes (8): actAs(), asGuest(), buildCollabProject(), latestNotificationFor(), makeBranch(), makeIssue(), makePullRequest(), static

### Community 70 - "MainActivity.kt"
Cohesion: 0.46
Nodes (4): MainActivity, Bundle, ComponentActivity, Intent

### Community 71 - "CodeFlow"
Cohesion: 0.25
Nodes (5): CodeFlow, Documentación, Estructura del repositorio, Roles, Tests

### Community 73 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pao, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 74 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 78 - "9. Diseño del VCS Engine"
Cohesion: 0.25
Nodes (8): 9.1 Modelo (inspirado en Git, adaptado a SQL), 9.2 Operaciones de la fachada `VcsEngine`, 9.3 Algoritmos, 9.4 Cifrado (envelope encryption), 9.5 Seguridad del motor, 9.6 Lo que el cifrado NO protege (para la memoria), 9.7 Cambios respecto al `VcsService` actual, 9. Diseño del VCS Engine

### Community 80 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 81 - "BuildsProjects.php"
Cohesion: 0.38
Nodes (4): actingAsUser(), buildProject(), makePublic(), requireVisibilityColumn()

### Community 83 - "decodePage"
Cohesion: 0.53
Nodes (5): decodePage(), JsonElement, KSerializer, T, PageDto

### Community 85 - "ChecksProjectRoles.php"
Cohesion: 0.73
Nodes (5): canSee(), isMember(), isRole(), requireRole(), roleOf()

### Community 86 - "UserFactory.php"
Cohesion: 0.47
Nodes (3): static, UserFactory, Illuminate\Database\Eloquent\Factories\Factory

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
- **55 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `VcsService`, `EncryptionTest`, `Project`, `Illuminate\Http\Request`, `Illuminate\Http\JsonResponse`, `Repository`, `Illuminate\Auth\Access\Response`, `Branch`, `PullRequest`, `Controller`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `NotificationPagesTest`, `LoginViewModelTest`, `Issue`, `Illuminate\Database\Eloquent\Model`, `IssuesWebTest`, `Illuminate\Foundation\Testing\RefreshDatabase`, `ProjectService`, `.hash`, `Illuminate\Database\Eloquent\Relations\BelongsToMany`, `HomeAndExploreTest`, `Phase4PerformanceTest.php`, `VisibilityTest`, `ObserverTest`, `AuthTest`, `NotificationTest`, `BuildsCollabFixtures.php`, `CodeFlowFlowTest`, `MemberPagesTest`, `BuildsProjects.php`, `LoginTest`, `ChecksProjectRoles.php`, `UserFactory.php`, `BuildsCodeFixtures.php`?**
  _High betweenness centrality (0.359) - this node is a cross-community bridge._
- **Why does `FakeAuthRepository` connect `LoginViewModelTest` to `ApiResult`, `User`?**
  _High betweenness centrality (0.235) - this node is a cross-community bridge._
- **Why does `ApiResult` connect `ApiResult` to `NotificationListScreen.kt`, `LoginViewModel`, `SessionStore`, `VerifyEmailViewModel`, `ApiResultTest`, `LoginViewModelTest`, `ActivityViewModel`, `AppContainer`?**
  _High betweenness centrality (0.147) - this node is a cross-community bridge._
- **What connects `LaravelErrorBody`, `Loading`, `LoggedOut` to the rest of the system?**
  _194 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `VcsService` be split into smaller, more focused modules?**
  _Cohesion score 0.061507936507936505 - nodes in this community are weakly interconnected._
- **Should `initSeek` be split into smaller, more focused modules?**
  _Cohesion score 0.05925925925925926 - nodes in this community are weakly interconnected._
- **Should `EncryptionTest` be split into smaller, more focused modules?**
  _Cohesion score 0.0660377358490566 - nodes in this community are weakly interconnected._
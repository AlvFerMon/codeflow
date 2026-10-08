# CodeFlow — App Android

Cliente Android de CodeFlow escrito en **Kotlin + Jetpack Compose (Material 3)**. Consume la API REST de Laravel
(`backend/`) con un token personal de Sanctum por dispositivo.

Estado actual (esqueleto de la Fase 13): **Login**, **lista de proyectos** y **notificaciones** funcionando contra la API.
El resto de pantallas se irán añadiendo por fases (ver [Hoja de ruta](#hoja-de-ruta)).

## Requisitos

- **Android Studio** reciente (Ladybug 2024.2 o posterior).
- **JDK 17 o 21** para Gradle. Android Studio ya incluye uno (JetBrains Runtime): en
  *Settings › Build, Execution, Deployment › Build Tools › Gradle › Gradle JDK* deja seleccionado el JDK de Android Studio.
- Android SDK Platform 35 (Android Studio lo descarga solo al sincronizar).
- Un emulador (AVD) con Android 8.0 (API 26) o superior, o un móvil físico.
- El backend arrancado en Docker en el PC (ver el README principal).

## Abrir el proyecto

1. *File › Open…* y selecciona la carpeta **`android/`** del repositorio (no la raíz del monorepo).
2. Espera a que termine el *Gradle Sync* (la primera vez descarga Gradle 8.11.1 y las dependencias).
3. Android Studio crea `android/local.properties` con la ruta del SDK. Ese fichero **no se sube** al repositorio.

Desde terminal (Windows): `cd android` y `gradlew.bat assembleDebug` · tests: `gradlew.bat testDebugUnitTest`.

## Ejecutar contra el backend en Docker

La URL de la API es un campo de `BuildConfig` definido en `app/build.gradle.kts`:

| Build | `BASE_URL` |
|---|---|
| debug | `http://10.0.2.2:8080/api/` |
| release | `https://codeflow.example.com/api/` (provisional, `TODO`) |

### Emulador

1. Arranca el backend: la API debe responder en `http://localhost:8080/api` en el PC.
2. En Android Studio elige la configuración **app**, un emulador, y pulsa **Run ▶**.
3. Dentro del emulador, `10.0.2.2` es el `localhost` del PC, así que no hay que configurar nada más.

> **HTTP sin cifrar solo en debug.** `app/src/debug/res/xml/network_security_config.xml` permite tráfico HTTP
> únicamente hacia `10.0.2.2` y `localhost`. La build release no incluye ese fichero y solo admite HTTPS.

### Móvil físico (misma red Wi-Fi que el PC)

1. Averigua la IP LAN del PC (`ipconfig` en Windows, p. ej. `192.168.1.50`) y permite el puerto 8080 en el firewall.
2. Añade en `android/local.properties`:
   ```properties
   codeflow.debugApiUrl=http://192.168.1.50:8080/api/
   ```
3. Añade esa IP en `app/src/debug/res/xml/network_security_config.xml`:
   ```xml
   <domain includeSubdomains="false">192.168.1.50</domain>
   ```
4. Vuelve a sincronizar Gradle y ejecuta.

Alternativa con cable USB: `adb reverse tcp:8080 tcp:8080` y usar `codeflow.debugApiUrl=http://localhost:8080/api/`.

### Usuarios de prueba

Las credenciales de prueba están en el README principal.

## Estructura del proyecto

Un único módulo `app`, paquete `com.codeflow.app`, inyección de dependencias **manual** (sin Hilt).
Flujo: `Screen → ViewModel (StateFlow<UiState>) → Repository → Retrofit → API Laravel`.

```
android/
├── build.gradle.kts · settings.gradle.kts · gradle.properties
├── gradle/libs.versions.toml          # Catálogo de versiones (todas las dependencias)
├── gradle/wrapper/                    # Gradle Wrapper 8.11.1
└── app/
    ├── build.gradle.kts               # BASE_URL por build type, Compose, dependencias
    └── src/
        ├── main/java/com/codeflow/app/
        │   ├── CodeFlowApplication.kt     # Crea el AppContainer
        │   ├── MainActivity.kt
        │   ├── core/
        │   │   ├── di/AppContainer.kt     # Grafo de objetos + factorías de ViewModel
        │   │   ├── network/               # ApiClient, AuthInterceptor, UnauthorizedInterceptor, ApiResult
        │   │   ├── session/SessionStore.kt# Token y nombre en DataStore; isLoggedIn; evento de sesión caducada
        │   │   └── ui/                    # theme/ (CodeFlowTheme), components/ (Loading/Error/EmptyView), fechas
        │   ├── data/
        │   │   ├── remote/                # CodeFlowApi (Retrofit) + dto/ (JSON de Laravel)
        │   │   └── repository/            # AuthRepository, ProjectRepository, NotificationRepository
        │   ├── domain/model/              # Modelos que usa la UI
        │   ├── feature/                   # Una carpeta por funcionalidad: Screen + ViewModel + UiState
        │   │   ├── auth/                  # Login
        │   │   ├── projects/              # Lista de proyectos (+ barra con notificaciones y logout)
        │   │   ├── notifications/         # Lista de notificaciones (alertas resaltadas, marcar como leída)
        │   │   ├── repositories/          # (vacío) repos, ramas, árbol de archivos, commits y diff
        │   │   ├── issues/                # (vacío) issues y comentarios
        │   │   ├── pullrequests/          # (vacío) PRs, archivos cambiados, revisiones, merge
        │   │   ├── activity/              # (vacío) actividad del proyecto
        │   │   └── explore/               # (vacío) repositorios públicos
        │   └── navigation/CodeFlowNavHost.kt
        ├── debug/                         # network_security_config (HTTP solo hacia el backend local)
        └── test/                          # Tests JVM: ApiResult/safeApiCall, PageDto, LoginViewModel
```

Piezas clave:

- **`ApiResult` + `safeApiCall`**: los repositorios nunca lanzan excepciones. Los errores de Laravel
  (`{"message": "...", "errors": {"campo": ["..."]}}`) se convierten en `ApiResult.Error(code, message, fieldErrors)`.
  Sin red → `code = null`.
- **`AuthInterceptor`**: añade `Accept: application/json` y `Authorization: Bearer <token>`.
- **`UnauthorizedInterceptor`**: ante un **401** en una petición con token, borra la sesión y emite
  `SessionEvent.Expired`; la navegación vuelve al Login y se muestra un aviso.
- **`SessionStore`**: fuente de verdad de la sesión. `CodeFlowNavHost` arranca en Login o en Proyectos según haya token,
  y reacciona a los cambios (login, logout, caducidad).
- **Listados paginados**: `decodePage` acepta el paginador de Laravel (`data`, `current_page`, `last_page`) o un array plano.

### Añadir una pantalla nueva

1. DTOs en `data/remote/dto/` y endpoints en `CodeFlowApi`.
2. Repositorio (interfaz + implementación) en `data/repository/` que devuelva `ApiResult`.
3. `feature/<nombre>/`: `XxxUiState`, `XxxViewModel` (expone `StateFlow`) y `XxxScreen`.
4. Factoría del ViewModel en `AppContainer` y ruta en `CodeFlowNavHost`.

## Tests

```
gradlew.bat testDebugUnitTest      # Windows
./gradlew testDebugUnitTest        # Linux/macOS
```

O en Android Studio: clic derecho en `app/src/test` › *Run 'Tests in…'*.

## Generar un APK/AAB firmado (más adelante)

1. Cambia la `BASE_URL` de release en `app/build.gradle.kts` por la URL HTTPS real del servidor.
2. *Build › Generate Signed Bundle / APK…* → **Android App Bundle** (Google Play) o **APK** (instalación directa).
3. Crea un keystore nuevo (*Create new…*) o elige uno existente. **Guárdalo fuera del repositorio** y apunta las
   contraseñas en un gestor de contraseñas: si se pierde, no se pueden publicar actualizaciones con la misma firma.
   `*.jks`, `*.keystore` y `keystore.properties` están en `.gitignore`.
4. Elige la variante **release**. El resultado queda en `app/release/`.

## Hoja de ruta

| Fase | Pantallas / funcionalidad Android |
|---|---|
| 13 (actual) | Login, lista de proyectos, notificaciones (lista, contador de no leídas, marcar como leída) |
| 13 | Detalle de proyecto: miembros y actividad |
| 13 | Repositorios del proyecto, explorar repos públicos |
| 13 | Ramas, árbol de archivos y visor de archivos |
| 13 | Historial de commits y detalle de commit con diff |
| 13 | Issues: lista con filtros, detalle, comentarios |
| 13 | Pull Requests: lista, detalle, archivos cambiados, revisiones (aprobar / pedir cambios), merge |
| 19 | Notificaciones push con **Firebase Cloud Messaging** (`TODO` en `app/build.gradle.kts` y en el manifiesto) |

## Versiones

AGP 8.7.3 · Gradle 8.11.1 · Kotlin 2.1.0 (plugin Compose Compiler) · Compose BOM 2024.12.01 · compileSdk/targetSdk 35 ·
minSdk 26 · Java 17. El resto, en `gradle/libs.versions.toml`.

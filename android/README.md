# CodeFlow — App Android

Cliente Android de CodeFlow escrito en **Kotlin + Jetpack Compose (Material 3)**. Consume la API REST de Laravel
(`backend/`) con un token personal de Sanctum por dispositivo.

App sencilla para **seguir tus proyectos desde el móvil**:

- **Actividad**: quién ha hecho qué, en qué proyecto, repositorio y rama, y cuándo (filtro por proyecto, deslizar para actualizar).
- **Notificaciones**: lista con no leídas resaltadas, marcar una o todas como leídas, insignia en la barra inferior.
- **Avisos en la barra del móvil**: cada 30 s con la app abierta y cada 15 min en segundo plano (WorkManager).
- **Proyectos**: los proyectos de los que eres miembro.
- **Login** con el servidor configurable (IP del PC) y **verificación del correo** con el código de 6 dígitos.

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

El servidor **se escribe en la pantalla de login** (campo *Servidor*) y se recuerda en el móvil.
El valor inicial sale de `codeflow.debugApiUrl` en `local.properties` (por defecto `10.0.2.2:8080`, el emulador).

### Emulador

1. Arranca el backend: la API debe responder en `http://localhost:8080/api` en el PC.
2. En Android Studio elige la configuración **app**, un emulador, y pulsa **Run ▶**.
3. Deja el servidor `10.0.2.2:8080` (dentro del emulador es el `localhost` del PC).

### Móvil físico (misma red Wi-Fi que el PC)

1. En el PC, con `cmd`: `ipconfig` → apunta la *Dirección IPv4* de la Wi-Fi (p. ej. `192.168.1.50`).
2. Abre el puerto 8080 en el firewall (cmd **como administrador**, solo una vez):
   ```
   netsh advfirewall firewall add rule name="CodeFlow 8080" dir=in action=allow protocol=TCP localport=8080
   ```
3. Comprueba desde el navegador del móvil que abre `http://192.168.1.50:8080`.
4. Instala la app en el móvil: activa las *Opciones de desarrollador* y la *Depuración inalámbrica* (o USB),
   vincúlalo en Android Studio (*Pair Devices Using Wi-Fi*) y pulsa **Run ▶**.
5. En el login escribe `192.168.1.50:8080` en *Servidor*, tu email y tu contraseña.
6. Si la cuenta no está verificada, pide el código: en local el correo llega a **Mailpit** (`http://localhost:8025`).
7. Acepta el permiso de notificaciones (Android 13+) para recibir los avisos.

> **HTTP sin cifrar solo en debug.** `app/src/debug/res/xml/network_security_config.xml` permite HTTP hacia cualquier
> IP para poder usar la de la red local. La build release no incluye ese fichero y solo admite HTTPS.

> **Avisos**: Android solo permite tareas periódicas cada 15 min como mínimo. Con la app abierta se consulta cada 30 s.
> Mejora futura: Firebase Cloud Messaging para avisos instantáneos.

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
        │   │   ├── network/               # ApiClient, BaseUrlInterceptor, ServerUrl, Auth/UnauthorizedInterceptor, ApiResult
        │   │   ├── notifications/         # NotificationPoller, SystemNotifier (canales y avisos), NotificationWorker
        │   │   ├── session/SessionStore.kt# Token, usuario, verificación, servidor y avisos ya mostrados (DataStore)
        │   │   └── ui/                    # theme/, components/ (logo CF, menú de cuenta, Loading/Error/Empty), fechas
        │   ├── data/
        │   │   ├── remote/                # CodeFlowApi (Retrofit) + dto/ (JSON de Laravel)
        │   │   └── repository/            # Auth, Activity, Project y NotificationRepository
        │   ├── domain/model/              # Modelos que usa la UI
        │   ├── feature/                   # Una carpeta por funcionalidad: Screen + ViewModel + UiState
        │   │   ├── auth/                  # Login (con servidor) y verificación del correo
        │   │   ├── main/                  # Barra inferior, insignia, permiso y sondeo cada 30 s
        │   │   ├── activity/              # Feed de actividad con filtro por proyecto
        │   │   ├── notifications/         # Notificaciones (alertas resaltadas, marcar como leídas)
        │   │   ├── projects/              # Mis proyectos
        │   │   └── repositories/ issues/ pullrequests/ explore/   # (vacíos) mejoras futuras
        │   └── navigation/CodeFlowNavHost.kt
        ├── debug/                         # network_security_config (HTTP en la red local, solo debug)
        └── test/                          # Tests JVM: ApiResult, PageDto, ActivityDto, LoginViewModel, ServerUrl, fechas, avisos
```

Piezas clave:

- **`ApiResult` + `safeApiCall`**: los repositorios nunca lanzan excepciones. Los errores de Laravel
  (`{"message": "...", "errors": {"campo": ["..."]}}`) se convierten en `ApiResult.Error(code, message, fieldErrors)`.
  Sin red → `code = null`.
- **`AuthInterceptor`**: añade `Accept: application/json` y `Authorization: Bearer <token>`.
- **`UnauthorizedInterceptor`**: ante un **401** en una petición con token, borra la sesión y emite
  `SessionEvent.Expired`; la navegación vuelve al Login y se muestra un aviso.
- **`SessionStore`**: fuente de verdad de la sesión. `CodeFlowNavHost` muestra Login, verificación del correo o la
  pantalla principal según la sesión, y reacciona a los cambios (login, logout, caducidad).
- **`BaseUrlInterceptor`**: Retrofit usa una URL ficticia y el interceptor la cambia por el servidor elegido en el login,
  así se puede cambiar de servidor sin reiniciar la app.
- **`NotificationPoller`**: pide las no leídas y avisa solo de las que no se habían mostrado (la primera vez tras iniciar
  sesión no avisa de las antiguas).
- **Listados paginados**: `decodePage` acepta el paginador de Laravel (`data`, `current_page`, `last_page`) o un array plano.

### Añadir una pantalla nueva

1. DTOs en `data/remote/dto/` y endpoints en `CodeFlowApi`.
2. Repositorio (interfaz + implementación) en `data/repository/` que devuelva `ApiResult`.
3. `feature/<nombre>/`: `XxxUiState`, `XxxViewModel` (expone `StateFlow`) y `XxxScreen`.
4. Factoría del ViewModel en `AppContainer` y ruta (o pestaña) en `feature/main/MainScreen.kt`.

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

| Estado | Funcionalidad |
|---|---|
| ✅ | Login con servidor configurable y verificación del correo por código |
| ✅ | Actividad de mis proyectos (`GET /api/me/activity`) con filtro por proyecto |
| ✅ | Notificaciones: lista, insignia, marcar como leídas, avisos en la barra del sistema (sondeo) |
| ✅ | Proyectos de los que soy miembro |
| Futuro | Repositorios, ramas, commits con diff, issues y pull requests desde el móvil |
| Futuro | Notificaciones push instantáneas con Firebase Cloud Messaging |

## Versiones

AGP 8.7.3 · Gradle 8.11.1 · Kotlin 2.1.0 (plugin Compose Compiler) · Compose BOM 2024.12.01 · WorkManager 2.10.0 · compileSdk/targetSdk 35 ·
minSdk 26 · Java 17. El resto, en `gradle/libs.versions.toml`.

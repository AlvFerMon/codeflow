package com.codeflow.app.core.session

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.booleanPreferencesKey
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.emptyPreferences
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import java.io.IOException
import java.util.concurrent.atomic.AtomicBoolean
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import kotlinx.coroutines.runBlocking

private val Context.sessionDataStore: DataStore<Preferences> by preferencesDataStore(name = "session")

sealed interface SessionState {
    /** Todavía se está leyendo DataStore (solo al arrancar la app). */
    data object Loading : SessionState

    data object LoggedOut : SessionState

    /** @param emailVerified `false` → hay que introducir el código recibido por correo. */
    data class LoggedIn(val token: String, val userName: String?, val emailVerified: Boolean) : SessionState
}

sealed interface SessionEvent {
    /** La API respondió 401 a una petición autenticada: token caducado o revocado. */
    data object Expired : SessionEvent
}

/** Servidor al que se conecta la app (lo consulta el BaseUrlInterceptor en cada petición). */
interface ServerConfig {
    /** URL base de la API, terminada en `/api/`. */
    val serverUrl: StateFlow<String>

    suspend fun saveServerUrl(apiBaseUrl: String)
}

/**
 * Guarda en DataStore el token de Sanctum, el usuario, el servidor elegido y qué notificaciones
 * se han mostrado ya en la barra del sistema.
 *
 * TODO(seguridad): DataStore guarda en claro dentro del almacenamiento privado de la app.
 *  Valorar cifrar el token con Android Keystore (p. ej. Tink) antes de producción.
 */
class SessionStore(
    context: Context,
    private val scope: CoroutineScope,
    private val defaultServerUrl: String,
) : ServerConfig {

    private val dataStore = context.sessionDataStore

    private val prefs: Flow<Preferences> = dataStore.data
        .catch { e -> if (e is IOException) emit(emptyPreferences()) else throw e }

    val state: StateFlow<SessionState> = prefs
        .map { p ->
            val token = p[KEY_TOKEN]
            if (token.isNullOrBlank()) {
                SessionState.LoggedOut
            } else {
                // Sesiones guardadas antes de existir la verificación se consideran verificadas
                SessionState.LoggedIn(token, p[KEY_USER_NAME], p[KEY_EMAIL_VERIFIED] ?: true)
            }
        }
        .stateIn(scope, SharingStarted.Eagerly, SessionState.Loading)

    override val serverUrl: StateFlow<String> = prefs
        .map { it[KEY_SERVER_URL] ?: defaultServerUrl }
        .stateIn(scope, SharingStarted.Eagerly, defaultServerUrl)

    private val _events = MutableSharedFlow<SessionEvent>(extraBufferCapacity = 1)
    val events: SharedFlow<SessionEvent> = _events.asSharedFlow()

    /** Evita emitir varias veces "sesión caducada" si fallan varias peticiones a la vez. */
    private val expiring = AtomicBoolean(false)

    /**
     * Token actual para el [com.codeflow.app.core.network.AuthInterceptor].
     * Se llama desde los hilos de OkHttp (nunca desde el hilo principal), por eso es aceptable
     * el `runBlocking` del caso raro en que DataStore aún no se haya leído.
     */
    fun currentToken(): String? = when (val current = state.value) {
        is SessionState.LoggedIn -> current.token
        SessionState.LoggedOut -> null
        SessionState.Loading -> runBlocking { prefs.first()[KEY_TOKEN] }
    }

    /** URL del servidor para el BaseUrlInterceptor (mismo razonamiento que [currentToken]). */
    fun currentServerUrl(): String =
        if (state.value is SessionState.Loading) {
            runBlocking { prefs.first()[KEY_SERVER_URL] ?: defaultServerUrl }
        } else {
            serverUrl.value
        }

    override suspend fun saveServerUrl(apiBaseUrl: String) {
        dataStore.edit { it[KEY_SERVER_URL] = apiBaseUrl }
    }

    suspend fun saveSession(token: String, userName: String, emailVerified: Boolean) {
        dataStore.edit { p ->
            p[KEY_TOKEN] = token
            p[KEY_USER_NAME] = userName
            p[KEY_EMAIL_VERIFIED] = emailVerified
            // Las notificaciones antiguas de otra cuenta no deben aparecer como nuevas
            p.remove(KEY_NOTIFIED_IDS)
        }
        expiring.set(false)
    }

    suspend fun markEmailVerified() {
        dataStore.edit { it[KEY_EMAIL_VERIFIED] = true }
    }

    /** Cierra la sesión. El servidor elegido se conserva para el próximo inicio de sesión. */
    suspend fun clear() {
        dataStore.edit { p ->
            p.remove(KEY_TOKEN)
            p.remove(KEY_USER_NAME)
            p.remove(KEY_EMAIL_VERIFIED)
            p.remove(KEY_NOTIFIED_IDS)
        }
    }

    /**
     * IDs de las notificaciones ya mostradas en la barra del sistema.
     * `null` = todavía no se ha sondeado nunca con esta sesión.
     */
    suspend fun notifiedIds(): Set<String>? =
        prefs.first()[KEY_NOTIFIED_IDS]?.split(',')?.filter { it.isNotBlank() }?.toSet()

    suspend fun saveNotifiedIds(ids: Collection<String>) {
        dataStore.edit { it[KEY_NOTIFIED_IDS] = ids.joinToString(",") }
    }

    /** Llamado por el UnauthorizedInterceptor ante un 401. */
    fun onUnauthorized() {
        if (state.value !is SessionState.LoggedIn) return
        if (!expiring.compareAndSet(false, true)) return
        _events.tryEmit(SessionEvent.Expired)
        scope.launch { clear() }
    }

    private companion object {
        val KEY_TOKEN = stringPreferencesKey("auth_token")
        val KEY_USER_NAME = stringPreferencesKey("user_name")
        val KEY_EMAIL_VERIFIED = booleanPreferencesKey("email_verified")
        val KEY_SERVER_URL = stringPreferencesKey("server_url")
        val KEY_NOTIFIED_IDS = stringPreferencesKey("notified_ids")
    }
}

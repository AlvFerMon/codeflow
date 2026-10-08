package com.codeflow.app.core.session

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
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
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.flow.filter
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
    data class LoggedIn(val token: String, val userName: String?) : SessionState
}

sealed interface SessionEvent {
    /** La API respondió 401 a una petición autenticada: token caducado o revocado. */
    data object Expired : SessionEvent
}

/**
 * Guarda el token de Sanctum y el nombre del usuario en DataStore.
 *
 * TODO(seguridad): DataStore guarda en claro dentro del almacenamiento privado de la app.
 *  Valorar cifrar el token con Android Keystore (p. ej. Tink) antes de producción.
 */
class SessionStore(
    context: Context,
    private val scope: CoroutineScope,
) {
    private val dataStore = context.sessionDataStore

    val state: StateFlow<SessionState> = dataStore.data
        .catch { e -> if (e is IOException) emit(emptyPreferences()) else throw e }
        .map { prefs ->
            val token = prefs[KEY_TOKEN]
            if (token.isNullOrBlank()) SessionState.LoggedOut
            else SessionState.LoggedIn(token, prefs[KEY_USER_NAME])
        }
        .stateIn(scope, SharingStarted.Eagerly, SessionState.Loading)

    val isLoggedIn: Flow<Boolean> = state
        .filter { it !is SessionState.Loading }
        .map { it is SessionState.LoggedIn }
        .distinctUntilChanged()

    val userName: Flow<String?> = state.map { (it as? SessionState.LoggedIn)?.userName }

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
        SessionState.Loading -> runBlocking { dataStore.data.first()[KEY_TOKEN] }
    }

    suspend fun saveSession(token: String, userName: String) {
        dataStore.edit { prefs ->
            prefs[KEY_TOKEN] = token
            prefs[KEY_USER_NAME] = userName
        }
        expiring.set(false)
    }

    suspend fun clear() {
        dataStore.edit { it.clear() }
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
    }
}

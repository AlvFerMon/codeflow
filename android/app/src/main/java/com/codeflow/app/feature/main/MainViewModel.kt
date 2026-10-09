package com.codeflow.app.feature.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.codeflow.app.core.network.ServerUrl
import com.codeflow.app.core.notifications.NotificationPoller
import com.codeflow.app.core.session.SessionState
import com.codeflow.app.core.session.SessionStore
import com.codeflow.app.data.repository.AuthRepository
import com.codeflow.app.data.repository.NotificationRepository
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch

/** Estado común de la pantalla principal: insignia de no leídas, cuenta y sondeo. */
class MainViewModel(
    private val sessionStore: SessionStore,
    private val authRepository: AuthRepository,
    notificationRepository: NotificationRepository,
    private val poller: NotificationPoller,
) : ViewModel() {

    val unreadCount: StateFlow<Int> = notificationRepository.unreadCount

    val userName: StateFlow<String?> = sessionStore.state
        .map { (it as? SessionState.LoggedIn)?.userName }
        .stateIn(viewModelScope, SharingStarted.Eagerly, null)

    val server: StateFlow<String> = sessionStore.serverUrl
        .map { ServerUrl.display(it) }
        .stateIn(viewModelScope, SharingStarted.Eagerly, ServerUrl.display(sessionStore.serverUrl.value))

    /** Lo llama la pantalla cada 30 s mientras está visible. */
    suspend fun poll() = poller.poll()

    fun logout() {
        viewModelScope.launch { authRepository.logout() }
    }
}

package com.codeflow.app.core.notifications

import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.core.session.SessionState
import com.codeflow.app.core.session.SessionStore
import com.codeflow.app.data.repository.NotificationRepository
import com.codeflow.app.domain.model.AppNotification
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock

/**
 * Consulta las notificaciones no leídas y muestra en la barra del sistema las que aún no se
 * hayan avisado. Lo usan el WorkManager (cada 15 min en segundo plano) y la pantalla principal
 * (cada 30 s mientras la app está abierta).
 *
 * La primera vez tras iniciar sesión solo se memorizan las existentes: no se avisa de golpe de
 * todo lo pendiente.
 */
class NotificationPoller(
    private val sessionStore: SessionStore,
    private val notificationRepository: NotificationRepository,
    private val notifier: SystemNotifier,
) {
    private val mutex = Mutex()

    suspend fun poll() = mutex.withLock {
        // En segundo plano el proceso puede estar recién creado: esperar a leer la sesión guardada
        val session = sessionStore.state.first { it !is SessionState.Loading }
        if (session !is SessionState.LoggedIn || !session.emailVerified) return@withLock

        notificationRepository.refreshUnreadCount()
        val result = notificationRepository.getUnread()
        if (result !is ApiResult.Success) return@withLock

        val unread = result.data
        val alreadyNotified = sessionStore.notifiedIds()
        val fresh = newNotifications(unread, alreadyNotified)
        if (fresh.isNotEmpty()) notifier.show(fresh)

        // Se guardan solo las no leídas actuales: la lista no crece indefinidamente.
        sessionStore.saveNotifiedIds(unread.map { it.id }.take(MAX_REMEMBERED))
    }

    companion object {
        private const val MAX_REMEMBERED = 100

        /** Notificaciones a avisar. `alreadyNotified == null` → primer sondeo: ninguna. */
        fun newNotifications(unread: List<AppNotification>, alreadyNotified: Set<String>?): List<AppNotification> =
            if (alreadyNotified == null) emptyList() else unread.filterNot { it.id in alreadyNotified }
    }
}

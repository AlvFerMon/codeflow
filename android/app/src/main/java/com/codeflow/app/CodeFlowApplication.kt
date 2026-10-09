package com.codeflow.app

import android.app.Application
import com.codeflow.app.core.di.AppContainer
import com.codeflow.app.core.notifications.NotificationWorker
import com.codeflow.app.core.session.SessionState
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.flow.filterNot
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.launch

/**
 * Punto de entrada de la app: crea el [AppContainer] (inyección de dependencias manual)
 * una sola vez por proceso y programa/cancela el sondeo de notificaciones según la sesión.
 */
class CodeFlowApplication : Application() {

    lateinit var container: AppContainer
        private set

    override fun onCreate() {
        super.onCreate()
        container = AppContainer(this)
        container.systemNotifier.createChannels()

        container.appScope.launch {
            container.sessionStore.state
                .filterNot { it is SessionState.Loading }
                .map { it is SessionState.LoggedIn && it.emailVerified }
                .distinctUntilChanged()
                .collect { active ->
                    if (active) {
                        NotificationWorker.schedule(this@CodeFlowApplication)
                    } else {
                        NotificationWorker.cancel(this@CodeFlowApplication)
                        container.notificationRepository.reset()
                        container.systemNotifier.cancelAll()
                    }
                }
        }
    }
}
